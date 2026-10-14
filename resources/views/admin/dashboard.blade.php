@extends('layouts.app')

@section('title', 'Dashboard Ringkasan')

@section('content')
<div class="space-y-8">

    <!-- Operational Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-line">
        <div>
            <h1 class="text-base font-semibold text-ink">Ringkasan Operasional Event</h1>
            <p class="text-xs text-muted">Pencatatan status peserta, total loop terverifikasi, dan log scan</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('admin.participants.create') }}" class="px-3 py-1.5 rounded bg-raised hover:bg-line text-ink border border-line text-xs font-medium transition-colors">
                + Tambah Peserta
            </a>
            <a href="{{ route('rfid.scan') }}" class="px-3 py-1.5 rounded bg-accent hover:bg-accent-hover text-accent-ink font-semibold text-xs transition-colors">
                Layar Scan
            </a>
        </div>
    </div>

    <dl class="grid grid-cols-2 lg:grid-cols-4 gap-y-4 border-b border-line pb-5">
        <div>
            <dt class="text-xs text-muted">Peserta</dt>
            <dd class="mt-1 text-2xl font-mono">{{ $summary['total_participants'] }}</dd>
        </div>
        <div class="lg:border-l lg:border-line lg:pl-6">
            <dt class="text-xs text-muted">Total loop</dt>
            <dd class="mt-1 text-2xl font-mono text-accent">{{ $summary['total_loops'] }}</dd>
        </div>
        <div class="lg:border-l lg:border-line lg:pl-6">
            <dt class="text-xs text-muted">Scan diterima</dt>
            <dd class="mt-1 text-2xl font-mono">{{ $summary['accepted_scans'] }}</dd>
        </div>
        <div class="lg:border-l lg:border-line lg:pl-6">
            <dt class="text-xs text-muted">Scan ditolak</dt>
            <dd class="mt-1 text-2xl font-mono {{ $summary['rejected_scans'] > 0 ? 'text-rose-300' : '' }}">{{ $summary['rejected_scans'] }}</dd>
        </div>
    </dl>

    <!-- Dual Operational Panels -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-x-10 gap-y-8">

        <!-- Left: Leaderboard / Loop Summary (5 cols) -->
        <div class="lg:col-span-5 min-w-0">
            <div class="pb-2 border-b border-line flex items-center justify-between">
                <span class="text-sm font-semibold text-ink">Leaderboard Loop Sementara</span>
                <a href="{{ route('admin.participants.index') }}" class="text-xs text-muted hover:text-ink underline underline-offset-2">Semua Peserta</a>
            </div>

            <div class="divide-y divide-line-soft border-b border-line-soft">
                @forelse ($topRunners as $index => $runner)
                    <div class="px-4 py-2.5 flex items-center justify-between ">
                        <div class="flex items-center space-x-3">
                            <span class="text-xs font-mono text-muted w-4 text-center">{{ $index + 1 }}</span>
                            <div>
                                <a href="{{ route('admin.participants.loops', $runner) }}" class="text-xs font-semibold text-ink hover:text-accent transition-colors">
                                    {{ $runner->name }}
                                </a>
                                <div class="text-xs font-mono text-muted">BIB {{ $runner->bib_number }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-medium font-mono text-ink tabular-nums">{{ $runner->loop_records_count }}</span>
                            <span class="text-xs font-mono text-faint uppercase ml-1">loop</span>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-xs text-muted">Belum ada data loop tercatat.</div>
                @endforelse
            </div>
        </div>

        <!-- Right: Audit Scan Log Preview (7 cols) -->
        <div class="lg:col-span-7 min-w-0">
            <div class="pb-2 border-b border-line flex items-center justify-between">
                <span class="text-sm font-semibold text-ink">Log Scan Terakhir</span>
                <a href="{{ route('admin.scan-logs') }}" class="text-xs text-muted hover:text-ink underline underline-offset-2">Log Lengkap</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs font-mono">
                    <thead>
                        <tr class="text-muted border-b border-line-soft">
                            <th class="py-2 pr-3 font-medium">Waktu</th>
                            <th class="py-2 pr-3 font-medium font-sans">Peserta</th>
                            <th class="py-2 pr-3 font-medium">UID</th>
                            <th class="py-2 pr-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line-soft border-b border-line-soft">
                        @forelse ($recentScans as $scan)
                            <tr class="">
                                <td class="py-2 pr-3 text-ink-2 tabular-nums">{{ $scan['scanned_at'] }}</td>
                                <td class="py-2 pr-3 font-sans font-medium text-ink">{{ $scan['participant_name'] }}</td>
                                <td class="py-2 pr-3 text-muted">{{ $scan['rfid_uid'] }}</td>
                                <td class="py-2 pr-3">
                                    @if ($scan['status'] === 'accepted')
                                        <span class="text-ink-2"><span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 align-middle"></span>Diterima</span>
                                    @elseif ($scan['status'] === 'ignored_duplicate')
                                        <span class="text-amber-300"><span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-400 mr-1.5 align-middle"></span>Duplikat</span>
                                    @else
                                        <span class="text-rose-300"><span class="inline-block w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5 align-middle"></span>Ditolak</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-muted font-sans">Belum ada riwayat scan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Rute lomba -->
    <section class="pt-5 border-t border-line">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold">Rute lomba</h2>
                @if ($raceRoute)
                    <p class="text-sm text-ink-2 mt-1">{{ $raceRoute->name ?: 'Rute lomba' }} · <span class="font-mono">{{ number_format($raceRoute->distance_m / 1000, 2, ',', '.') }} km</span></p>
                    <p class="text-xs text-muted mt-0.5">Tampil di dashboard semua peserta. Unggah file baru untuk mengganti.</p>
                @else
                    <p class="text-xs text-muted mt-1">Belum ada rute. Ekspor rute dari On The Go Map, Strava, atau Google My Maps sebagai .gpx lalu unggah di sini.</p>
                @endif
            </div>
            <div class="flex flex-col gap-2 sm:items-end">
                <form method="POST" action="{{ route('admin.race-route.store') }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-2">
                    @csrf
                    <input type="text" name="route_name" placeholder="Nama rute (opsional)" aria-label="Nama rute"
                           class="bg-surface border border-line focus:border-accent rounded-md px-3 py-1.5 text-sm text-ink placeholder:text-faint outline-none">
                    <input type="file" name="route_gpx" accept=".gpx" required aria-label="File GPX rute lomba"
                           class="text-sm text-ink-2 file:mr-2 file:px-3 file:py-1.5 file:rounded-md file:border file:border-line file:bg-raised file:text-ink file:text-sm">
                    <button type="submit" class="px-3 py-1.5 rounded-md bg-accent hover:bg-accent-hover text-accent-ink text-sm font-semibold">Simpan rute</button>
                </form>
                @if ($raceRoute)
                    <form method="POST" action="{{ route('admin.race-route.destroy') }}" onsubmit="return confirm('Hapus rute lomba?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-muted hover:text-rose-300 underline underline-offset-2">Hapus rute</button>
                    </form>
                @endif
            </div>
        </div>
    </section>

    <!-- Safe Simulation Reset Tool -->
    <div class="pt-5 border-t border-line flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <div class="text-sm font-semibold text-ink">Reset Data Simulasi Putaran</div>
            <p class="text-xs text-muted mt-0.5">
                Mengosongkan riwayat loop dan log scan kembali ke 0 untuk demo baru tanpa menghapus data master peserta.
            </p>
        </div>

        <form method="POST" action="{{ route('admin.reset-simulation') }}" onsubmit="return confirmResetDemo();" class="flex items-center space-x-2">
            @csrf
            <input type="hidden" name="confirm_reset" id="confirm_reset_input" value="" />
            <button
                type="submit"
                class="px-3 py-1.5 rounded bg-raised hover:bg-rose-950/40 text-rose-300 hover:text-rose-200 border border-line hover:border-rose-900/60 text-xs font-medium transition-colors"
            >
                Reset Data Simulasi
            </button>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function confirmResetDemo() {
        const input = prompt('PERINGATAN: Seluruh loop records dan scan logs akan dikosongkan untuk demo ulang.\n\nKetik "RESET" untuk melanjutkan:');
        if (input === 'RESET') {
            document.getElementById('confirm_reset_input').value = 'RESET';
            return true;
        }
        if (input !== null) {
            alert('Reset dibatalkan: Kata kunci tidak sesuai.');
        }
        return false;
    }
</script>
@endpush
