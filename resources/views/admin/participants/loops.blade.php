@extends('layouts.app')

@section('title', 'Riwayat Loop - ' . $participant->name)

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-line">
        <div>
            <div class="flex items-center space-x-2 text-xs font-mono text-muted">
                <span>Riwayat Loop Pelari</span>
                <span>/</span>
                <span class="font-mono text-ink-2">BIB {{ $participant->bib_number }}</span>
            </div>
            <h1 class="text-base font-medium text-ink tracking-tight mt-0.5">{{ $participant->name }}</h1>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('admin.participants.edit', $participant) }}" class="px-3 py-1.5 rounded text-xs text-ink-2 hover:text-ink bg-canvas border border-line transition-colors">
                Edit Peserta
            </a>
            <a href="{{ route('admin.participants.index') }}" class="px-3 py-1.5 rounded text-xs text-muted hover:text-ink transition-colors">
                Kembali
            </a>
        </div>
    </div>

    <!-- Technical Overview Strip -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
            <span class="text-xs text-muted block">Nomor BIB</span>
            <span class="text-xl font-mono text-ink tabular-nums mt-0.5 block">{{ $participant->bib_number }}</span>
        </div>
        <div>
            <span class="text-xs text-muted block">UID Terpasang</span>
            <span class="text-sm font-mono text-ink mt-0.5 block">
                {{ $participant->rfid_uid ?? 'Belum Ditautkan' }}
            </span>
        </div>
        <div>
            <span class="text-xs text-muted block">Total Putaran Sah</span>
            <span class="text-xl font-medium font-mono text-ink tabular-nums mt-0.5 block">{{ $loops->count() }} Putaran</span>
        </div>
    </div>

    <!-- Link & QR dashboard peserta -->
    <section class="flex flex-col sm:flex-row items-start gap-5 pb-6 border-b border-line">
        <div class="shrink-0 w-40 h-40 bg-white p-2 rounded overflow-hidden" aria-label="QR dashboard peserta">{!! $qrSvg !!}</div>
        <div class="flex-1 min-w-0 space-y-3">
            <div>
                <h2 class="text-sm font-semibold">Dashboard peserta</h2>
                <p class="text-xs text-muted mt-0.5">Cetak QR ini di kartu peserta. Siapa pun yang memegang link dapat membuka dashboard-nya.</p>
            </div>
            <div class="flex max-w-xl">
                <input id="portal-url" type="text" readonly value="{{ $portalUrl }}" aria-label="Link dashboard peserta"
                       class="flex-1 min-w-0 bg-surface border border-line border-r-0 rounded-l-md px-3 py-2 text-xs font-mono text-ink-2 outline-none">
                <button type="button" onclick="navigator.clipboard?.writeText(document.getElementById('portal-url').value); this.textContent='Tersalin'"
                        class="px-3 rounded-r-md border border-line bg-raised hover:bg-line text-xs">Salin</button>
            </div>
            <div class="flex flex-wrap gap-3 text-xs">
                <a href="{{ $portalUrl }}" target="_blank" rel="noopener noreferrer" class="text-ink-2 hover:text-ink underline underline-offset-2">Buka dashboard</a>
                <form method="POST" action="{{ route('admin.participants.rotate-token', $participant) }}"
                      onsubmit="return confirm('Ganti link? QR yang sudah dicetak tidak akan berlaku lagi.');">
                    @csrf
                    <button type="submit" class="text-rose-300 hover:text-rose-200 underline underline-offset-2">Ganti link (QR lama tidak berlaku)</button>
                </form>
                @if ($participant->access_token_rotated_at)
                    <span class="text-faint">Terakhir diganti {{ $participant->access_token_rotated_at->timezone('Asia/Jakarta')->format('d M Y H:i') }}</span>
                @endif
            </div>
        </div>
    </section>

    <!-- Loop Timeline Table -->
    <div class="min-w-0">
        <div class="pb-2 border-b border-line flex items-center justify-between">
            <span class="text-sm font-semibold text-ink">Catatan Putaran Resmi</span>
            <span class="text-xs font-mono text-muted">{{ $loops->count() }} Record(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-mono">
                <thead>
                    <tr class="text-muted border-b border-line">
                        <th class="py-2 pr-3 font-medium text-center w-16">Loop #</th>
                        <th class="py-2 pr-3 font-medium">Waktu Pemindaian (Server)</th>
                        <th class="py-2 pr-3 font-medium font-sans">Interval</th>
                        <th class="py-2 pr-3 font-medium">UID Terbaca</th>
                        <th class="py-2 pr-3 font-medium text-right font-sans">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line-soft border-b border-line-soft">
                    @forelse ($loops as $index => $record)
                        @php
                            $previousLoop = $index > 0 ? $loops[$index - 1] : null;
                            $interval = $previousLoop ? $previousLoop->scanned_at->diffForHumans($record->scanned_at, true) : 'Putaran Awal';
                        @endphp
                        <tr class="">
                            <td class="py-2 pr-3 text-center font-mono text-ink-2">
                                {{ $record->loop_number }}
                            </td>
                            <td class="py-2 pr-3 text-ink-2 tabular-nums">
                                {{ $record->scanned_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="py-2 pr-3 font-sans text-ink-2">
                                {{ $interval }}
                            </td>
                            <td class="py-2 pr-3 text-muted">
                                {{ $record->rfid_uid }}
                            </td>
                            <td class="py-2 pr-3 text-right">
                                <span class="text-ink-2"><span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 align-middle"></span>Terverifikasi</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-muted font-sans">
                                Belum ada putaran tercatat untuk pelari ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
