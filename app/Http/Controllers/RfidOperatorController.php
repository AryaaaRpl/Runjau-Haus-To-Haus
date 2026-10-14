<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Services\ScanService;
use Illuminate\View\View;

class RfidOperatorController extends Controller
{
    public function __construct(
        protected ScanService $scanService
    ) {}

    public function index(): View
    {
        $summary = $this->scanService->getSummary();
        $recentScans = $this->scanService->getRecentScans(10);

        // Kartu uji untuk simulasi manual diambil dari data nyata, bukan hardcode di view.
        $sampleCards = Participant::query()
            ->whereNotNull('rfid_uid')
            ->orderBy('bib_number')
            ->limit(3)
            ->get(['name', 'bib_number', 'rfid_uid']);

        return view('rfid.index', compact('summary', 'recentScans', 'sampleCards'));
    }
}
