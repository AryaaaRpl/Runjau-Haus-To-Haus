@extends('layouts.app')

@section('title', $participant->name)
@section('public', true)

@push('styles')
    <meta name="robots" content="noindex, nofollow">
    {{-- Kirim origin saja ke server peta: token di URL tidak ikut bocor, tapi layanan peta tetap mau melayani. --}}
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <style>
        /* Tile OSM terang dibalik jadi gelap; garis rute & marker tidak ikut terbalik. */
        .map-dark .leaflet-tile-pane { filter: invert(1) hue-rotate(180deg) brightness(0.85) contrast(0.9) saturate(0.6); }
        .map-dark { background: #1a1917; }
        .leaflet-control-attribution { background: rgba(18,17,16,.8) !important; color: #948e85 !important; }
        .leaflet-control-attribution a { color: #c8c3bb !important; }
    </style>
@endpush

@php
    $km = fn ($m) => number_format($m / 1000, 2, ',', '.');
    $dur = function ($s) {
        if ($s === null) return '–';
        $h = intdiv($s, 3600); $m = intdiv($s % 3600, 60); $sec = $s % 60;
        return $h ? sprintf('%d:%02d:%02d', $h, $m, $sec) : sprintf('%d:%02d', $m, $sec);
    };

    $validSplits = array_filter($splits, fn($s) => $s['split_s'] !== null);
    $fastestSplitS = count($validSplits) > 1 ? min(array_column($validSplits, 'split_s')) : null;
    $avgSplitS = count($validSplits) ? (int) round(array_sum(array_column($validSplits, 'split_s')) / count($validSplits)) : null;
    $latestSplit = count($splits) ? end($splits) : null;
@endphp

@section('content')
<div class="max-w-3xl mx-auto space-y-8">

    {{-- Header Acara & Bar Kontrol Live --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-line">
        <a href="{{ route('rfid.scan') }}" class="inline-block transition-opacity hover:opacity-90">
            <img src="{{ asset('images/brand/haus-to-haus.png') }}" alt="Runjau Haus To Haus" class="h-10 sm:h-11 w-auto" width="191" height="56">
        </a>
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <button
                type="button"
                id="btn-sound-toggle"
                onclick="toggleSoundNotification()"
                title="Aktifkan nada dering saat timing gate membaca putaran baru"
                class="px-2.5 py-1 rounded bg-raised hover:bg-line border border-line text-xs text-ink-2 hover:text-ink transition-colors flex items-center gap-1.5"
            >
                <span id="sound-status-dot" class="w-1.5 h-1.5 rounded-full bg-faint"></span>
                <span id="sound-status-text">Audio Gate: Mati</span>
            </button>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-surface border border-line text-[11px] font-mono text-emerald-400">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                LIVE TRACKING
            </span>
        </div>
    </div>

    {{-- Runner Pass Hero Card (Identitas & Semangat Atlet) --}}
    <div class="bg-surface border border-line rounded-lg p-5 sm:p-6 relative overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-4 border-b border-line-soft">
            <div class="min-w-0">
                <div class="text-[11px] uppercase tracking-wider font-semibold text-accent">Personal Timing Pass</div>
                <h1 class="mt-1 text-2xl sm:text-3xl font-bold tracking-tight text-ink break-words">
                    {{ $participant->name }}
                </h1>
                <p class="mt-0.5 text-xs text-muted">Runjau Haus To Haus · Kategori Loop Run</p>
            </div>

            <div class="shrink-0 flex items-center sm:flex-col sm:items-end gap-2">
                <div class="px-3.5 py-1.5 rounded bg-canvas border border-accent/60 text-accent font-mono font-bold text-lg sm:text-xl tracking-wider">
                    BIB {{ $participant->bib_number }}
                </div>
                @if ($loopCount > 0)
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-medium text-emerald-400 bg-emerald-500/10 border border-emerald-500/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Sedang di lintasan
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-medium text-amber-300 bg-amber-400/10 border border-amber-400/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                        Menunggu start
                    </span>
                @endif
            </div>
        </div>

        {{-- Status Ringkasan Atlet --}}
        <div class="mt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
            <div class="text-ink-2">
                @if ($loopCount > 0)
                    <span>Akumulasi: <strong id="hero-distance-summary" class="font-mono text-ink">{{ $km($raceDistanceM) }} km</strong> dalam <strong id="hero-loop-summary" class="font-mono text-ink">{{ $loopCount }} putaran</strong>.</span>
                @else
                    <span>Kartu RFID terdaftar dan siap dipindai di timing gate start/finish.</span>
                @endif
            </div>
            <div id="live-freshness" class="text-[11px] text-faint font-mono" data-time="{{ $latestSplit ? $latestSplit['time']->toIso8601String() : '' }}">
                @if ($latestSplit)
                    Terakhir dipindai: {{ $latestSplit['time']->timezone('Asia/Jakarta')->format('H:i:s') }} WIB <span class="text-accent/90 scan-freshness-ago">({{ $latestSplit['time']->diffForHumans() }})</span>
                @else
                    Terakhir dipindai: –
                @endif
            </div>
        </div>
    </div>

    {{-- Telemetri Utama (4 Metrik Data Inti) --}}
    <section aria-labelledby="race-heading" class="space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-line">
            <h2 id="race-heading" class="text-sm font-semibold text-ink uppercase tracking-wider">Telemetri Lomba</h2>
            <span class="text-xs text-muted font-mono">Pencatatan RFID Resmi</span>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="bg-surface border border-line rounded-lg p-4">
                <div class="text-[11px] uppercase tracking-wider text-muted font-medium">Jarak Tempuh</div>
                <div id="metric-distance" class="mt-1 text-2xl sm:text-3xl font-mono font-bold text-accent">
                    {{ $km($raceDistanceM) }}<span class="text-sm font-sans text-muted ml-1">km</span>
                </div>
                <div class="mt-0.5 text-[11px] text-faint">Akumulasi jarak resmi</div>
            </div>

            <div class="bg-surface border border-line rounded-lg p-4">
                <div class="text-[11px] uppercase tracking-wider text-muted font-medium">Putaran Selesai</div>
                <div id="metric-loops" class="mt-1 text-2xl sm:text-3xl font-mono font-bold text-ink">
                    {{ $loopCount }}<span class="text-sm font-sans text-muted ml-1">loop</span>
                </div>
                <div class="mt-0.5 text-[11px] text-faint">Terverifikasi gate timing</div>
            </div>

            <div class="bg-surface border border-line rounded-lg p-4">
                <div class="text-[11px] uppercase tracking-wider text-muted font-medium">Split Terakhir</div>
                <div id="metric-latest-split" class="mt-1 text-2xl sm:text-3xl font-mono font-bold text-ink-2">
                    {{ $latestSplit && $latestSplit['split_s'] ? $dur($latestSplit['split_s']) : '–' }}
                </div>
                <div class="mt-0.5 text-[11px] text-faint">Durasi putaran ke-{{ $loopCount }}</div>
            </div>

            <div class="bg-surface border border-line rounded-lg p-4">
                <div class="text-[11px] uppercase tracking-wider text-muted font-medium">Jarak per Loop</div>
                <div class="mt-1 text-2xl sm:text-3xl font-mono font-bold text-ink-2">
                    {{ $trackLength >= 1000 ? $km($trackLength) : $trackLength }}<span class="text-sm font-sans text-muted ml-1">{{ $trackLength >= 1000 ? 'km' : 'm' }}</span>
                </div>
                <div class="mt-0.5 text-[11px] text-faint">{{ $trackSource === 'rute' ? 'Sesuai rute resmi' : 'Panjang lintasan' }}</div>
            </div>
        </div>

        {{-- Kalkulator Target & Proyeksi Lomba Interaktif --}}
        <div class="bg-surface border border-line rounded-lg p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-line-soft">
                <div>
                    <h3 class="text-xs font-semibold text-ink uppercase tracking-wider">Kalkulator Target & Proyeksi Finish</h3>
                    <p class="text-xs text-muted mt-0.5">Pilih target jarakmu untuk melihat proyeksi sisa putaran dan estimasi jam finish.</p>
                </div>
                <div class="flex items-center space-x-1 font-mono text-xs">
                    <button type="button" onclick="setGoalKm(10)" class="goal-chip px-2.5 py-1 rounded bg-raised hover:bg-line border border-line text-ink-2 transition-colors" data-goal="10">10 km</button>
                    <button type="button" onclick="setGoalKm(21.1)" class="goal-chip px-2.5 py-1 rounded bg-raised hover:bg-line border border-line text-ink-2 transition-colors" data-goal="21.1">21.1 km</button>
                    <button type="button" onclick="setGoalKm(42.2)" class="goal-chip px-2.5 py-1 rounded bg-raised hover:bg-line border border-line text-ink-2 transition-colors" data-goal="42.2">42.2 km</button>
                    <button type="button" onclick="setGoalKm(50)" class="goal-chip px-2.5 py-1 rounded bg-raised hover:bg-line border border-line text-ink-2 transition-colors" data-goal="50">50 km</button>
                </div>
            </div>

            {{-- Progress Bar Capaian Target --}}
            <div class="space-y-1.5">
                <div class="flex items-center justify-between text-xs font-mono">
                    <span class="text-muted">Progres Menuju <span id="label-target-km" class="text-ink font-semibold">50 km</span></span>
                    <span id="label-target-pct" class="text-accent font-semibold">0%</span>
                </div>
                <div class="w-full h-2 rounded-full bg-raised overflow-hidden">
                    <div id="bar-target-progress" class="h-full bg-accent transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>

            {{-- Proyeksi Metrik Sisa --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2 text-xs font-mono">
                <div class="bg-canvas border border-line-soft rounded p-2.5">
                    <div class="text-[11px] text-muted font-sans">Sisa Putaran</div>
                    <div id="calc-remaining-loops" class="mt-0.5 text-base font-semibold text-ink">–</div>
                </div>
                <div class="bg-canvas border border-line-soft rounded p-2.5">
                    <div class="text-[11px] text-muted font-sans">Sisa Jarak</div>
                    <div id="calc-remaining-km" class="mt-0.5 text-base font-semibold text-accent">–</div>
                </div>
                <div class="bg-canvas border border-line-soft rounded p-2.5">
                    <div class="text-[11px] text-muted font-sans">Rata-rata Loop</div>
                    <div id="calc-avg-split" class="mt-0.5 text-base font-semibold text-ink-2">{{ $avgSplitS ? $dur($avgSplitS) : '–' }}</div>
                </div>
                <div class="bg-canvas border border-line-soft rounded p-2.5">
                    <div class="text-[11px] text-muted font-sans">Estimasi Finish</div>
                    <div id="calc-eta-finish" class="mt-0.5 text-base font-semibold text-emerald-400">–</div>
                </div>
            </div>
        </div>

        {{-- Visualisasi Ritme Putaran (SVG Pace Progression Chart) --}}
        @if (count($splits) > 0)
            <div class="bg-surface border border-line rounded-lg p-5 space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-line-soft">
                    <div>
                        <h3 class="text-xs font-semibold text-ink uppercase tracking-wider">Visualisasi Ritme Putaran</h3>
                        <p class="text-xs text-muted mt-0.5">Grafik durasi tiap putaran. Bar oranye menandai putaran tercepat.</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs font-mono text-muted">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-accent"></span> Tercepat</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-raised border border-line"></span> Putaran Lain</span>
                    </div>
                </div>

                {{-- SVG Bar Chart --}}
                <div class="w-full overflow-x-auto pt-2">
                    <div id="chart-pace-container" class="min-w-[360px] h-32 flex items-end gap-2 pb-2 border-b border-line">
                        @php
                            $maxSplitS = count($validSplits) ? max(array_column($validSplits, 'split_s')) : 1;
                            $maxSplitS = max($maxSplitS, 1);
                        @endphp
                        @foreach ($splits as $s)
                            @php
                                $val = $s['split_s'] ?? 0;
                                $heightPct = $val > 0 ? max(15, (int) round(($val / $maxSplitS) * 100)) : 10;
                                $isFastest = ($fastestSplitS !== null && $val === $fastestSplitS);
                            @endphp
                            <div class="flex-1 flex flex-col items-center gap-1 h-full justify-end group relative cursor-pointer"
                                 title="Putaran {{ $s['number'] }}: {{ $val ? $dur($val) : 'Waktu start' }}">
                                <div class="text-[10px] font-mono text-faint group-hover:text-ink transition-colors">
                                    {{ $val ? $dur($val) : '–' }}
                                </div>
                                <div
                                    class="w-full rounded-t transition-all {{ $isFastest ? 'bg-accent' : 'bg-raised border-t border-x border-line group-hover:bg-line' }}"
                                    style="height: {{ $heightPct }}%"
                                ></div>
                                <div class="text-[11px] font-mono text-muted mt-1">L{{ $s['number'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Peta Rute Lomba Resmi --}}
        @if ($raceRoute)
            <div class="bg-surface border border-line rounded-lg p-5">
                <div class="flex items-center justify-between pb-3 border-b border-line-soft">
                    <div>
                        <h3 class="text-sm font-semibold text-ink">{{ $raceRoute->name ?: 'Rute Lomba Resmi' }}</h3>
                        <p class="text-xs text-muted mt-0.5">Jalur lintasan yang dilalui peserta pada setiap putaran</p>
                    </div>
                    <span class="px-2.5 py-1 rounded bg-raised border border-line text-xs font-mono font-medium text-accent">
                        {{ $km($raceRoute->distance_m) }} km / loop
                    </span>
                </div>
                <div id="race-map" class="mt-3 h-72 sm:h-80 rounded-md border border-line bg-surface" role="img" aria-label="Peta rute lomba"></div>
            </div>
        @endif

        {{-- Riwayat Putaran & Split Waktu (Tabel Terperinci) --}}
        <div class="bg-surface border border-line rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b border-line flex items-center justify-between bg-surface">
                <h3 class="text-xs font-semibold text-ink uppercase tracking-wider">Riwayat Putaran & Split Waktu</h3>
                <div class="flex items-center space-x-2">
                    <button
                        type="button"
                        id="btn-sort-splits"
                        onclick="toggleSplitsOrder()"
                        class="text-xs text-muted hover:text-ink underline underline-offset-2 transition-colors"
                    >
                        Urutkan: Terbaru Dahulu
                    </button>
                    <span class="text-faint">·</span>
                    <span id="label-splits-count" class="text-xs text-muted font-mono">{{ $loopCount }} putaran</span>
                </div>
            </div>

            @if (count($splits))
                <div class="overflow-x-auto">
                    <table class="w-full text-xs font-mono text-left">
                        <thead>
                            <tr class="text-muted border-b border-line bg-canvas">
                                <th scope="col" class="py-2.5 px-4 font-medium w-24">Putaran</th>
                                <th scope="col" class="py-2.5 px-4 font-medium">Waktu Tap (WIB)</th>
                                <th scope="col" class="py-2.5 px-4 font-medium text-right font-sans">Durasi Split</th>
                                <th scope="col" class="py-2.5 px-4 font-medium text-right font-sans">Jarak Kumulatif</th>
                            </tr>
                        </thead>
                        <tbody id="splits-tbody" class="divide-y divide-line-soft">
                            @foreach (array_reverse($splits) as $s)
                                @php
                                    $isFastest = ($fastestSplitS !== null && $s['split_s'] === $fastestSplitS);
                                    $cumulativeM = $s['number'] * $trackLength;
                                @endphp
                                <tr class="hover:bg-surface transition-colors" data-loop="{{ $s['number'] }}">
                                    <td class="py-2.5 px-4 font-semibold text-ink">
                                        <span class="inline-block px-2 py-0.5 rounded bg-raised border border-line-soft text-[11px] text-ink-2">
                                            Loop {{ $s['number'] }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-4 text-ink-2">
                                        {{ $s['time']->timezone('Asia/Jakarta')->format('H:i:s') }}
                                    </td>
                                    <td class="py-2.5 px-4 text-right">
                                        <span class="text-ink font-medium">{{ $dur($s['split_s']) }}</span>
                                        @if ($isFastest)
                                            <span class="ml-1.5 inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-sans font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                                Tercepat
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-4 text-right font-mono text-accent font-medium">
                                        {{ $km($cumulativeM) }} km
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-12 px-4 text-center">
                    <p class="text-sm font-medium text-ink">Belum ada putaran tercatat</p>
                    <p class="mt-1 text-xs text-muted max-w-sm mx-auto">
                        Jarak dan split waktu akan otomatis muncul di sini begitu kartumu dipindai di pos timing start/finish.
                    </p>
                </div>
            @endif
        </div>
    </section>

    {{-- Lari Pribadi (GPX Sync dari Strava / Garmin) --}}
    <section aria-labelledby="runs-heading" class="bg-surface border border-line rounded-lg p-5 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-2 pb-3 border-b border-line-soft">
            <div>
                <h2 id="runs-heading" class="text-sm font-semibold text-ink uppercase tracking-wider">Aktivitas Lari Pribadi</h2>
                <p class="text-xs text-muted mt-0.5">Unggah rute lari personal dari Strava, Garmin, atau Apple Watch (.gpx)</p>
            </div>
            @if ($runs->isNotEmpty())
                <span class="text-xs text-muted font-mono">
                    {{ $runs->count() }} lari · <span class="text-accent font-semibold">{{ $km($runsDistanceM) }}</span> km total
                </span>
            @endif
        </div>

        <form method="POST" action="{{ route('participant.runs.store', $token) }}" enctype="multipart/form-data" id="gpx-form">
            @csrf
            <div class="flex flex-col sm:flex-row gap-2.5">
                <input
                    type="file"
                    name="gpx"
                    id="gpx"
                    accept=".gpx,application/gpx+xml"
                    required
                    aria-label="Pilih file GPX"
                    class="flex-1 min-w-0 text-xs text-ink-2 file:mr-3 file:px-3 file:py-2 file:rounded-md file:border file:border-line file:bg-raised file:text-ink file:text-xs hover:file:bg-line transition-colors"
                />
                <button
                    type="submit"
                    class="px-4 py-2 rounded-md bg-accent hover:bg-accent-hover text-accent-ink text-xs font-semibold whitespace-nowrap transition-colors disabled:opacity-50"
                >
                    Unggah Rute GPX
                </button>
            </div>
            <p id="gpx-hint" class="text-[11px] text-faint mt-1.5">Format file .gpx (maks. 5 MB). Privasi: 200 m awal dan akhir rute otomatis disembunyikan di peta.</p>
        </form>

        @if ($runs->isNotEmpty())
            <div id="run-map" class="mt-4 h-72 sm:h-80 rounded-md border border-line bg-surface" role="img" aria-label="Peta rute lari terpilih"></div>

            <ul class="mt-3 divide-y divide-line-soft border-t border-line-soft">
                @foreach ($runs as $run)
                    <li class="flex items-center justify-between gap-3 py-2.5">
                        <button
                            type="button"
                            data-run="{{ $run->id }}"
                            class="run-select flex-1 min-w-0 text-left flex items-baseline justify-between gap-3 rounded p-1.5 -mx-1 hover:bg-surface aria-pressed:text-accent transition-colors"
                            aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                        >
                            <span class="min-w-0">
                                <span class="block truncate text-xs font-medium text-ink">{{ $run->name ?: 'Lari Mandiri' }}</span>
                                <span class="block text-[11px] text-muted">{{ $run->started_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') ?? 'Tanpa waktu' }}</span>
                            </span>
                            <span class="shrink-0 text-right font-mono text-xs">
                                <span class="font-semibold text-ink">{{ $km($run->distance_m) }} km</span>
                                <span class="block text-[11px] text-muted">{{ $dur($run->duration_s) }}</span>
                            </span>
                        </button>
                        <form method="POST" action="{{ route('participant.runs.destroy', [$token, $run]) }}" onsubmit="return confirm('Hapus lari ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-[11px] text-muted hover:text-rose-300 px-2 py-1 transition-colors" aria-label="Hapus {{ $run->name ?: 'lari' }}">
                                Hapus
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Catatan Keamanan Tautan Personal --}}
    <div class="p-3.5 rounded-lg bg-surface/30 border border-line text-xs text-faint flex items-start gap-2.5">
        <span class="text-muted font-mono">[!]</span>
        <div class="leading-relaxed">
            Halaman ini khusus milikmu yang diakses via tautan atau QR kartu RFID. Jangan bagikan tautan ini ke publik jika tidak ingin catatanmu dilihat orang lain. Jika kartu hilang, segera hubungi panitia.
        </div>
    </div>

</div>
@endsection

@push('scripts')
@if ($runs->isNotEmpty() || $raceRoute)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    const MAP_CFG = @json(config('rfid.map'));
    function addTiles(map) {
        if (MAP_CFG.dark_filter) map.getContainer().classList.add('map-dark');
        return L.tileLayer(MAP_CFG.tile_url, {
            maxZoom: MAP_CFG.max_zoom,
            attribution: MAP_CFG.attribution
        }).addTo(map);
    }
</script>
@endif

@if ($raceRoute)
<script>
    (function () {
        const el = document.getElementById('race-map');
        if (!el || typeof L === 'undefined') return;

        const pts = @json($raceRoute->route);
        if (!pts || !pts.length) return;

        const map = L.map(el, { scrollWheelZoom: false });
        addTiles(map);
        const line = L.polyline(pts, { color: '#F25919', weight: 4 }).addTo(map);
        L.circleMarker(pts[0], { radius: 6, color: '#f3f1ee', fillColor: '#121110', fillOpacity: 1 }).bindTooltip('Start Gate').addTo(map);
        L.circleMarker(pts[pts.length - 1], { radius: 6, color: '#F25919', fillColor: '#F25919', fillOpacity: 1 }).bindTooltip('Finish Gate').addTo(map);
        map.fitBounds(line.getBounds(), { padding: [25, 25] });
    })();
</script>
@endif

@if ($runs->isNotEmpty())
<script>
    (function () {
        const runs = @json($runs->mapWithKeys(fn ($r) => [$r->id => $r->route]));
        const mapEl = document.getElementById('run-map');
        if (!mapEl || typeof L === 'undefined') return;

        const map = L.map(mapEl, { scrollWheelZoom: false });
        addTiles(map);

        let layer = null;
        function show(id) {
            const pts = runs[id];
            if (!pts || !pts.length) return;
            if (layer) layer.remove();
            const line = L.polyline(pts, { color: '#F25919', weight: 4 });
            const start = L.circleMarker(pts[0], { radius: 5, color: '#f3f1ee', fillOpacity: 1 }).bindTooltip('Titik Awal');
            const end = L.circleMarker(pts[pts.length - 1], { radius: 5, color: '#F25919', fillOpacity: 1 }).bindTooltip('Titik Akhir');
            layer = L.layerGroup([line, start, end]).addTo(map);
            map.fitBounds(line.getBounds(), { padding: [20, 20] });

            document.querySelectorAll('.run-select').forEach(b => b.setAttribute('aria-pressed', b.dataset.run == id ? 'true' : 'false'));
        }

        document.querySelectorAll('.run-select').forEach(b => b.addEventListener('click', () => show(b.dataset.run)));
        const first = document.querySelector('.run-select');
        if (first) show(first.dataset.run);
    })();
</script>
@endif

<script>
    // Cegah unggah ganda file GPX
    document.getElementById('gpx-form')?.addEventListener('submit', e => {
        const btn = e.target.querySelector('button[type=submit]');
        btn.disabled = true;
        btn.textContent = 'Mengunggah file…';
    });

    // -------------------------------------------------------------
    // Live Audio Chime (Web Audio API: Synthesised Timing Gate Beep)
    // -------------------------------------------------------------
    let soundEnabled = false;
    const soundDot = document.getElementById('sound-status-dot');
    const soundText = document.getElementById('sound-status-text');

    function toggleSoundNotification() {
        soundEnabled = !soundEnabled;
        if (soundEnabled) {
            soundDot.className = 'w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse';
            soundText.textContent = 'Audio Gate: Aktif';
            playAthleticChime(); // Tes bunyi sekali
        } else {
            soundDot.className = 'w-1.5 h-1.5 rounded-full bg-faint';
            soundText.textContent = 'Audio Gate: Mati';
        }
    }

    function playAthleticChime() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(1320, ctx.currentTime + 0.15);

            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start();
            osc.stop(ctx.currentTime + 0.45);
        } catch (e) {
            console.error('Audio chime error:', e);
        }
    }

    // -------------------------------------------------------------
    // Kalkulator Target & Proyeksi Lomba
    // -------------------------------------------------------------
    const trackLengthM = {{ $trackLength }};
    let currentDistanceM = {{ $raceDistanceM }};
    let currentLoops = {{ $loopCount }};
    let selectedGoalKm = 50;
    const avgSplitSeconds = {{ $avgSplitS ?? 0 }};

    function setGoalKm(km) {
        selectedGoalKm = km;
        document.querySelectorAll('.goal-chip').forEach(btn => {
            const isMatch = parseFloat(btn.dataset.goal) === km;
            btn.className = isMatch
                ? 'goal-chip px-2.5 py-1 rounded bg-accent text-accent-ink font-semibold border border-accent transition-colors'
                : 'goal-chip px-2.5 py-1 rounded bg-raised hover:bg-line border border-line text-ink-2 transition-colors';
        });
        updateCalculatorUI();
    }

    function updateCalculatorUI() {
        const goalM = selectedGoalKm * 1000;
        const progressPct = Math.min(100, Math.round((currentDistanceM / goalM) * 100));

        const labelTargetKm = document.getElementById('label-target-km');
        const labelTargetPct = document.getElementById('label-target-pct');
        const barTarget = document.getElementById('bar-target-progress');

        if (labelTargetKm) labelTargetKm.textContent = selectedGoalKm + ' km';
        if (labelTargetPct) labelTargetPct.textContent = progressPct + '%';
        if (barTarget) barTarget.style.width = progressPct + '%';

        const remainingM = Math.max(0, goalM - currentDistanceM);
        const remainingKm = (remainingM / 1000).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const remainingLoops = Math.max(0, Math.ceil(remainingM / trackLengthM));

        const calcRemainingLoops = document.getElementById('calc-remaining-loops');
        const calcRemainingKm = document.getElementById('calc-remaining-km');
        const calcEtaFinish = document.getElementById('calc-eta-finish');

        if (calcRemainingLoops) calcRemainingLoops.textContent = remainingLoops + ' loop';
        if (calcRemainingKm) calcRemainingKm.textContent = remainingKm + ' km';

        if (calcEtaFinish) {
            if (remainingLoops === 0) {
                calcEtaFinish.textContent = 'Target Tercapai!';
            } else if (avgSplitSeconds > 0) {
                const totalSecondsLeft = remainingLoops * avgSplitSeconds;
                const finishDate = new Date(Date.now() + totalSecondsLeft * 1000);
                calcEtaFinish.textContent = finishDate.toLocaleTimeString('id-ID', { hour12: false }) + ' WIB';
            } else {
                calcEtaFinish.textContent = 'Menunggu pace';
            }
        }
    }

    // Inisialisasi kalkulator awal
    setGoalKm(50);

    // -------------------------------------------------------------
    // Sortir Tabel Riwayat Putaran
    // -------------------------------------------------------------
    let splitsSortDesc = true;
    function toggleSplitsOrder() {
        splitsSortDesc = !splitsSortDesc;
        const btn = document.getElementById('btn-sort-splits');
        const tbody = document.getElementById('splits-tbody');
        if (!tbody) return;

        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.reverse();
        rows.forEach(r => tbody.appendChild(r));

        if (btn) btn.textContent = splitsSortDesc ? 'Urutkan: Terbaru Dahulu' : 'Urutkan: Awal Dahulu';
    }

    // -------------------------------------------------------------
    // Live Telemetry Background Polling (Mendukung Web Audio Chime)
    // -------------------------------------------------------------
    async function checkLiveTelemetry() {
        try {
            const res = await fetch(window.location.href, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;

            const data = await res.json();
            if (data.success && data.loop_count > currentLoops) {
                // Ada putaran baru tercatat!
                currentLoops = data.loop_count;
                currentDistanceM = data.race_distance_m;

                if (soundEnabled) {
                    playAthleticChime();
                }

                // Update angka telemetri di layar
                const metricDistance = document.getElementById('metric-distance');
                const metricLoops = document.getElementById('metric-loops');
                const heroDistance = document.getElementById('hero-distance-summary');
                const heroLoops = document.getElementById('hero-loop-summary');
                const freshness = document.getElementById('live-freshness');

                const kmStr = (data.race_distance_m / 1000).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                if (metricDistance) metricDistance.innerHTML = `${kmStr}<span class="text-sm font-sans text-muted ml-1">km</span>`;
                if (metricLoops) metricLoops.innerHTML = `${data.loop_count}<span class="text-sm font-sans text-muted ml-1">loop</span>`;
                if (heroDistance) heroDistance.textContent = kmStr + ' km';
                if (heroLoops) heroLoops.textContent = data.loop_count + ' putaran';

                if (freshness && data.last_scanned_at) {
                    freshness.setAttribute('data-time', data.last_scanned_at);
                    const t = new Date(data.last_scanned_at).toLocaleTimeString('id-ID', { hour12: false });
                    freshness.innerHTML = `Terakhir dipindai: ${t} WIB <span class="text-accent/90 scan-freshness-ago">(${formatTimeAgo(data.last_scanned_at)})</span>`;
                }

                updateCalculatorUI();

                // Muat ulang halaman secara halus jika layout tabel perlu dirender penuh
                setTimeout(() => window.location.reload(), 1500);
            }
        } catch (e) {
            // Offline atau koneksi lambat
        }
    }
    function formatTimeAgo(isoString) {
        if (!isoString) return '';
        const date = new Date(isoString);
        const now = new Date();
        const diffSec = Math.floor((now - date) / 1000);

        if (diffSec < 5) return 'baru saja';
        if (diffSec < 60) return `${diffSec} dtk lalu`;
        const diffMin = Math.floor(diffSec / 60);
        if (diffMin < 60) return `${diffMin} mnt lalu`;
        const diffHr = Math.floor(diffMin / 60);
        if (diffHr < 24) return `${diffHr} jam lalu`;
        const diffDays = Math.floor(diffHr / 24);
        return `${diffDays} hr lalu`;
    }

    function updateParticipantRelativeTime() {
        const freshness = document.getElementById('live-freshness');
        if (!freshness) return;
        const iso = freshness.getAttribute('data-time');
        if (!iso) return;
        const agoSpan = freshness.querySelector('.scan-freshness-ago');
        if (agoSpan) {
            agoSpan.textContent = `(${formatTimeAgo(iso)})`;
        }
    }

    setInterval(updateParticipantRelativeTime, 3000);

    setInterval(checkLiveTelemetry, 10000);
</script>
@endpush
