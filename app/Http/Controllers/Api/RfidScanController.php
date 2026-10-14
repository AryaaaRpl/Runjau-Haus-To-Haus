<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Services\ScanService;
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
}
