<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ParticipantController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));

        $query = Participant::withCount('loopRecords')
            ->orderBy('id', 'desc');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('bib_number', 'like', "%{$search}%")
                    ->orWhere('rfid_uid', 'like', "%{$search}%");
            });
        }

        $participants = $query->paginate(15)->withQueryString();

        return view('admin.participants.index', compact('participants', 'search'));
    }

    public function create(): View
    {
        return view('admin.participants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bib_number' => ['required', 'string', 'max:50', 'unique:participants,bib_number'],
            'rfid_uid' => ['nullable', 'string', 'max:50', 'unique:participants,rfid_uid'],
        ]);

        // Clean & preserve string with leading zeros
        $validated['bib_number'] = trim($validated['bib_number']);
        $validated['rfid_uid'] = ! empty($validated['rfid_uid']) ? trim($validated['rfid_uid']) : null;

        Participant::create($validated);

        return redirect()->route('admin.participants.index')
            ->with('success', "Peserta {$validated['name']} (BIB: {$validated['bib_number']}) berhasil didaftarkan.");
    }

    public function edit(Participant $participant): View
    {
        return view('admin.participants.edit', compact('participant'));
    }

    public function update(Request $request, Participant $participant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bib_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('participants', 'bib_number')->ignore($participant->id),
            ],
            'rfid_uid' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('participants', 'rfid_uid')->ignore($participant->id),
            ],
        ]);

        $validated['bib_number'] = trim($validated['bib_number']);
        $validated['rfid_uid'] = ! empty($validated['rfid_uid']) ? trim($validated['rfid_uid']) : null;

        $participant->update($validated);

        return redirect()->route('admin.participants.index')
            ->with('success', "Data peserta {$participant->name} berhasil diperbarui.");
    }

    public function destroy(Participant $participant): RedirectResponse
    {
        $name = $participant->name;
        $participant->delete();

        return redirect()->route('admin.participants.index')
            ->with('success', "Peserta {$name} telah dihapus.");
    }

    public function loops(Participant $participant): View
    {
        $loops = $participant->loopRecords()
            ->orderBy('loop_number', 'asc')
            ->get();

        $portalUrl = route('participant.dashboard', $participant->access_token);
        $qrSvg = $this->qrSvg($portalUrl);

        return view('admin.participants.loops', compact('participant', 'loops', 'portalUrl', 'qrSvg'));
    }

    /**
     * Ganti link/QR peserta. Link lama langsung tidak bisa dipakai.
     */
    public function rotateToken(Participant $participant): RedirectResponse
    {
        $participant->rotateAccessToken();

        return redirect()
            ->route('admin.participants.loops', $participant)
            ->with('success', "Link dashboard {$participant->name} sudah diganti. QR lama tidak berlaku lagi — cetak ulang kartunya.");
    }

    protected function qrSvg(string $text): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(144, 1),
            new SvgImageBackEnd
        );

        return str_replace('<svg ', '<svg class="w-full h-full block" ', (new Writer($renderer))->writeString($text));
    }
}
