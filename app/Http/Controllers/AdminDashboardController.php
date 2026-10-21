<?php

namespace App\Http\Controllers;

use App\Models\LoopRecord;
use App\Models\Participant;
use App\Models\RaceRoute;
use App\Models\ScanLog;
use App\Services\ScanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(
        protected ScanService $scanService
    ) {}

    public function index(): View
    {
        $summary = $this->scanService->getSummary();
        $recentScans = $this->scanService->getRecentScans(10);

        // Top participants by loop count with pagination
        $topRunners = Participant::withCount('loopRecords')
            ->withMax('loopRecords as latest_scanned_at', 'scanned_at')
            ->orderByDesc('loop_records_count')
            ->orderBy('bib_number')
            ->paginate(10);
        $raceRoute = RaceRoute::current();

        return view('admin.dashboard', compact('summary', 'recentScans', 'topRunners', 'raceRoute'));
    }

    public function scanLogs(Request $request): View
    {
        $status = $request->input('status');
        $query = ScanLog::with('participant')->orderBy('scanned_at', 'desc');

        if ($status && in_array($status, ['accepted', 'rejected', 'ignored_duplicate'])) {
            $query->where('status', $status);
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.scan-logs', compact('logs', 'status'));
    }

    /**
     * Safely reset demo loops and scan logs (leaves participants intact).
     */
    public function resetSimulation(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm_reset' => ['required', 'in:RESET'],
        ], [
            'confirm_reset.in' => 'Ketik "RESET" untuk mengonfirmasi pembersihan data simulasi.',
        ]);

        LoopRecord::truncate();
        ScanLog::truncate();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Data loop dan scan simulasi berhasil di-reset. Semua peserta kini memiliki 0 loop.');
    }
}
