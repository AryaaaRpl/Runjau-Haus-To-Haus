<?php

namespace App\Services;

use App\Models\LoopRecord;
use App\Models\Participant;
use App\Models\ScanLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScanService
{
    /**
     * Process an incoming RFID scan, validate, and record loop transactionally.
     */
    public function processScan(string $rawUid, ?Carbon $scannedAt = null, ?string $clientScanId = null): array
    {
        // Preserve leading zeros and enforce string
        $uid = trim($rawUid);
        $scannedAt = $scannedAt ?? now();

        if ($uid === '') {
            return [
                'success' => false,
                'status' => 'rejected',
                'message' => 'UID RFID tidak boleh kosong.',
                'participant' => null,
                'current_loop_count' => 0,
                'loop_number' => null,
                'scanned_at' => $scannedAt->toIso8601String(),
            ];
        }

        // Idempotensi: scan yang sama (ID klien sama) dikirim ulang -> kembalikan hasil lama.
        if ($clientScanId !== null && ($existing = ScanLog::with('participant')->where('client_scan_id', $clientScanId)->first())) {
            return $this->replayResult($existing);
        }

        $participant = Participant::where('rfid_uid', $uid)->first();

        // Unknown card: log rejection and do not create loop record
        if (! $participant) {
            ScanLog::create([
                'rfid_uid' => $uid,
                'client_scan_id' => $clientScanId,
                'participant_id' => null,
                'status' => 'rejected',
                'message' => 'Kartu RFID tidak terdaftar dalam sistem.',
                'resulting_loop_count' => null,
                'scanned_at' => $scannedAt,
            ]);

            return [
                'success' => false,
                'status' => 'rejected',
                'message' => "Kartu dengan UID [{$uid}] tidak terdaftar.",
                'participant' => null,
                'current_loop_count' => 0,
                'loop_number' => null,
                'scanned_at' => $scannedAt->toIso8601String(),
            ];
        }

        // Accidental double-tap / debounce check
        $debounceSeconds = (int) config('rfid.debounce_seconds', 3);
        if ($debounceSeconds > 0) {
            $latestLoop = LoopRecord::where('participant_id', $participant->id)
                ->orderBy('scanned_at', 'desc')
                ->first();

            if ($latestLoop && $latestLoop->scanned_at->diffInSeconds($scannedAt) < $debounceSeconds) {
                $currentCount = $participant->loopRecords()->count();

                ScanLog::create([
                    'rfid_uid' => $uid,
                    'client_scan_id' => $clientScanId,
                    'participant_id' => $participant->id,
                    'status' => 'ignored_duplicate',
                    'message' => "Double tap terdeteksi (< {$debounceSeconds}s). Scan diabaikan.",
                    'resulting_loop_count' => $currentCount,
                    'scanned_at' => $scannedAt,
                ]);

                return [
                    'success' => false,
                    'status' => 'ignored_duplicate',
                    'message' => "Scan berulang terlalu cepat (< {$debounceSeconds} detik). Loop tidak bertambah.",
                    'participant' => [
                        'id' => $participant->id,
                        'name' => $participant->name,
                        'bib_number' => (string) $participant->bib_number,
                        'rfid_uid' => (string) $participant->rfid_uid,
                    ],
                    'current_loop_count' => $currentCount,
                    'loop_number' => null,
                    'scanned_at' => $scannedAt->toIso8601String(),
                ];
            }
        }

        // Transactional loop creation
        return DB::transaction(function () use ($participant, $uid, $scannedAt, $clientScanId) {
            $currentMax = (int) LoopRecord::where('participant_id', $participant->id)->max('loop_number');
            $nextLoopNumber = $currentMax + 1;

            $loop = LoopRecord::create([
                'participant_id' => $participant->id,
                'rfid_uid' => $uid,
                'loop_number' => $nextLoopNumber,
                'scanned_at' => $scannedAt,
            ]);

            ScanLog::create([
                'rfid_uid' => $uid,
                'client_scan_id' => $clientScanId,
                'participant_id' => $participant->id,
                'status' => 'accepted',
                'message' => "Putaran ke-{$nextLoopNumber} berhasil dicatat.",
                'resulting_loop_count' => $nextLoopNumber,
                'scanned_at' => $scannedAt,
            ]);

            return [
                'success' => true,
                'status' => 'accepted',
                'message' => "Putaran ke-{$nextLoopNumber} berhasil dicatat!",
                'participant' => [
                    'id' => $participant->id,
                    'name' => $participant->name,
                    'bib_number' => (string) $participant->bib_number,
                    'rfid_uid' => (string) $participant->rfid_uid,
                ],
                'current_loop_count' => $nextLoopNumber,
                'loop_number' => $nextLoopNumber,
                'scanned_at' => $scannedAt->toIso8601String(),
            ];
        });
    }

    /**
     * Bangun ulang respons dari log yang sudah ada (untuk request yang dikirim ulang).
     */
    protected function replayResult(ScanLog $log): array
    {
        $p = $log->participant;

        return [
            'success' => $log->status === 'accepted',
            'status' => $log->status,
            'message' => 'Scan ini sudah tercatat sebelumnya. '.$log->message,
            'participant' => $p ? [
                'id' => $p->id,
                'name' => $p->name,
                'bib_number' => (string) $p->bib_number,
                'rfid_uid' => (string) $p->rfid_uid,
            ] : null,
            'current_loop_count' => (int) $log->resulting_loop_count,
            'loop_number' => $log->status === 'accepted' ? $log->resulting_loop_count : null,
            'scanned_at' => $log->scanned_at->toIso8601String(),
            'replayed' => true,
        ];
    }

    /**
     * Retrieve participant by card UID without recording a loop.
     */
    public function findByUid(string $rawUid): ?array
    {
        $uid = trim($rawUid);
        if ($uid === '') {
            return null;
        }

        $participant = Participant::where('rfid_uid', $uid)->first();
        if (! $participant) {
            return null;
        }

        return [
            'id' => $participant->id,
            'name' => $participant->name,
            'bib_number' => (string) $participant->bib_number,
            'rfid_uid' => (string) $participant->rfid_uid,
            'current_loop_count' => $participant->loopRecords()->count(),
            'last_loop_at' => $participant->loopRecords()->latest('scanned_at')->value('scanned_at')?->toIso8601String(),
        ];
    }

    /**
     * Get summary metrics for the operator dashboard.
     */
    public function getSummary(): array
    {
        return [
            'total_participants' => Participant::count(),
            'total_loops' => LoopRecord::count(),
            'total_scans' => ScanLog::count(),
            'accepted_scans' => ScanLog::where('status', 'accepted')->count(),
            'rejected_scans' => ScanLog::where('status', 'rejected')->count(),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get recent scan activity for live display.
     */
    public function getRecentScans(int $limit = 15): array
    {
        return ScanLog::with('participant')
            ->orderBy('scanned_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function (ScanLog $log) {
                return [
                    'id' => $log->id,
                    'scanned_at' => $log->scanned_at->format('H:i:s'),
                    'scanned_at_iso' => $log->scanned_at->toIso8601String(),
                    'rfid_uid' => (string) $log->rfid_uid,
                    'status' => $log->status,
                    'message' => $log->message,
                    'participant_name' => $log->participant?->name ?? '—',
                    'bib_number' => $log->participant ? (string) $log->participant->bib_number : '—',
                    // Nilai historis saat scan terjadi (bukan jumlah loop terkini) — tanpa query tambahan per baris.
                    'resulting_loop_count' => $log->resulting_loop_count,
                ];
            })
            ->all();
    }
}
