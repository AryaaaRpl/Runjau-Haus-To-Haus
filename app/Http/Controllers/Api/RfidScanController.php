<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\ScanLog;
use App\Services\ScanService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RfidScanController extends Controller
{
    public function __construct(
        protected ScanService $scanService
    ) {}

    /**
     * POST /api/rfid/scan
     * Process an RFID scan and record a loop.
     */
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uid' => 'nullable|string',
            'rfid_uid' => 'nullable|string',
            'client_scan_id' => 'nullable|string|max:64',
        ]);

        $rawUid = $validated['uid'] ?? $validated['rfid_uid'] ?? '';
        $uid = trim((string) $rawUid);

        if ($uid === '') {
            return response()->json([
                'success' => false,
                'status' => 'rejected',
                'message' => 'UID RFID wajib diisi.',
                'participant' => null,
                'current_loop_count' => 0,
                'loop_number' => null,
                'scanned_at' => now()->toIso8601String(),
            ], 422);
        }

        $result = $this->scanService->processScan($uid, null, $validated['client_scan_id'] ?? null);

        if ($result['status'] === 'accepted') {
            return response()->json($result, 200);
        }

        if ($result['status'] === 'ignored_duplicate') {
            return response()->json($result, 200);
        }

        return response()->json($result, 404);
    }

    /**
     * GET /api/participants/by-card/{uid}
     * Retrieve a participant by RFID UID.
     */
    public function lookup(string $uid): JsonResponse
    {
        $data = $this->scanService->findByUid($uid);

        if (! $data) {
            return response()->json([
                'success' => false,
                'message' => "Peserta dengan UID [{$uid}] tidak ditemukan.",
                'participant' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Peserta ditemukan.',
            'participant' => $data,
        ], 200);
    }

    /**
     * GET /api/participants/{participant}/loops
     * Retrieve loop history for a participant.
     */
    public function loops(Participant $participant): JsonResponse
    {
        $loops = $participant->loopRecords()
            ->orderBy('loop_number', 'asc')
            ->get(['id', 'participant_id', 'rfid_uid', 'loop_number', 'scanned_at', 'created_at']);

        return response()->json([
            'success' => true,
            'participant' => [
                'id' => $participant->id,
                'name' => $participant->name,
                'bib_number' => (string) $participant->bib_number,
                'rfid_uid' => (string) $participant->rfid_uid,
                'total_loops' => $loops->count(),
            ],
            'loops' => $loops,
        ], 200);
    }

    /**
     * GET /api/dashboard/summary
     * Retrieve summary metrics.
     */
    public function summary(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->scanService->getSummary(),
        ], 200);
    }

    /**
     * GET /api/dashboard/recent-scans
     * Retrieve recent scan activity.
     */
    public function recentScans(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->scanService->getRecentScans(15),
        ], 200);
    }

    /**
     * GET /api/dashboard/participants
     * Retrieve paginated participant standings and cards.
     */
    public function participants(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search'));
        $query = Participant::withCount('loopRecords')
            ->withMax('loopRecords as latest_scanned_at', 'scanned_at')
            ->orderByDesc('loop_records_count')
            ->orderBy('bib_number');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('bib_number', 'like', "%{$search}%")
                    ->orWhere('rfid_uid', 'like', "%{$search}%");
            });
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 10)));
        $paginated = $query->paginate($perPage);

        $items = collect($paginated->items())->map(function (Participant $p) {
            $lastTime = $p->latest_scanned_at ? Carbon::parse($p->latest_scanned_at)->format('H:i:s') : null;
            $lastTimeIso = $p->latest_scanned_at ? Carbon::parse($p->latest_scanned_at)->toIso8601String() : null;

            return [
                'id' => $p->id,
                'name' => $p->name,
                'bib_number' => (string) $p->bib_number,
                'rfid_uid' => (string) ($p->rfid_uid ?? ''),
                'total_loops' => (int) $p->loop_records_count,
                'last_scanned_at' => $lastTime,
                'last_scanned_at_iso' => $lastTimeIso,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem() ?? 0,
                'to' => $paginated->lastItem() ?? 0,
            ],
        ]);
    }

    /**
     * GET /api/dashboard/scans
     * Retrieve paginated scan log activity.
     */
    public function scans(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');

        $query = ScanLog::with('participant')
            ->orderByDesc('scanned_at')
            ->orderByDesc('id');

        if ($status && in_array($status, ['accepted', 'rejected', 'ignored_duplicate'])) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('rfid_uid', 'like', "%{$search}%")
                    ->orWhereHas('participant', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhere('bib_number', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 10)));
        $paginated = $query->paginate($perPage);

        $items = collect($paginated->items())->map(function (ScanLog $log) {
            return [
                'id' => $log->id,
                'scanned_at' => $log->scanned_at?->format('H:i:s') ?? '—',
                'scanned_at_iso' => $log->scanned_at?->toIso8601String(),
                'rfid_uid' => (string) $log->rfid_uid,
                'status' => $log->status,
                'message' => $log->message,
                'participant_name' => $log->participant?->name ?? '—',
                'bib_number' => $log->participant ? (string) $log->participant->bib_number : '—',
                'resulting_loop_count' => $log->resulting_loop_count,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem() ?? 0,
                'to' => $paginated->lastItem() ?? 0,
            ],
        ]);
    }
}
