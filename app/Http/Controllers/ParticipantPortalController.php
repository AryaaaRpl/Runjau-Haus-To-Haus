<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\ParticipantRun;
use App\Models\RaceRoute;
use App\Services\GpxParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

/**
 * Dashboard peserta yang dibuka lewat link/QR unik (tanpa login).
 * Siapa pun yang memegang link bisa membuka halaman ini, jadi data yang
 * ditampilkan dibatasi seperlunya dan admin dapat mengganti token kapan saja.
 */
class ParticipantPortalController extends Controller
{
    public function show(string $token): Response
    {
        $participant = $this->resolve($token);

        $loops = $participant->loopRecords()->get(['loop_number', 'scanned_at']);
        // Panjang satu loop: pakai jarak rute lomba (GPX dari admin) bila ada,
        // kalau belum ada pakai nilai cadangan RFID_TRACK_LENGTH_M di .env.
        $raceRoute = RaceRoute::current();
        $trackLength = $raceRoute?->distance_m ?? (int) config('rfid.track_length_m', 400);
        $trackSource = $raceRoute ? 'rute' : 'config';

        $splits = [];
        foreach ($loops as $i => $loop) {
            $prev = $i > 0 ? $loops[$i - 1]->scanned_at : null;
            $splits[] = [
                'number' => $loop->loop_number,
                'time' => $loop->scanned_at,
                'split_s' => $prev ? $prev->diffInSeconds($loop->scanned_at) : null,
            ];
        }

        $runs = $participant->runs()->get();

        return response()
            ->view('participant.dashboard', [
                'participant' => $participant,
                'token' => $token,
                'loopCount' => $loops->count(),
                'trackLength' => $trackLength,
                'raceDistanceM' => $loops->count() * $trackLength,
                'splits' => $splits,
                'runs' => $runs,
                'runsDistanceM' => (int) $runs->sum('distance_m'),
                'raceRoute' => $raceRoute,
                'trackSource' => $trackSource,
            ])
            // Halaman bertoken: jangan disimpan cache bersama & jangan diindeks mesin pencari.
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function storeRun(Request $request, string $token, GpxParser $parser): RedirectResponse
    {
        $participant = $this->resolve($token);

        $request->validate([
            'gpx' => ['required', 'file', 'max:5120', 'extensions:gpx'],
        ], [
            'gpx.required' => 'Pilih file GPX terlebih dahulu.',
            'gpx.max' => 'Ukuran file maksimal 5 MB.',
            'gpx.extensions' => 'File harus berformat .gpx.',
        ]);

        if ($participant->runs()->count() >= 50) {
            return back()->withErrors(['gpx' => 'Batas 50 lari tercapai. Hapus lari lama terlebih dahulu.']);
        }

        try {
            $data = $parser->parse($request->file('gpx')->get());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['gpx' => $e->getMessage()]);
        }

        $participant->runs()->create($data);

        return redirect()
            ->route('participant.dashboard', $token)
            ->with('success', 'Lari berhasil ditambahkan: '.number_format($data['distance_m'] / 1000, 2, ',', '.').' km.');
    }

    public function destroyRun(string $token, ParticipantRun $run): RedirectResponse
    {
        $participant = $this->resolve($token);
        abort_unless($run->participant_id === $participant->id, 404);

        $run->delete();

        return redirect()->route('participant.dashboard', $token)->with('success', 'Lari dihapus.');
    }

    protected function resolve(string $token): Participant
    {
        // Token tidak valid -> 404 biasa, tanpa membocorkan apakah peserta ada.
        abort_if(strlen($token) < 32, 404);

        return Participant::where('access_token', $token)->firstOrFail();
    }
}
