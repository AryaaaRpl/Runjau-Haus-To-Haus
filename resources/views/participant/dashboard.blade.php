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
@endphp

@section('content')
<div class="max-w-3xl mx-auto space-y-10">

    {{-- Identitas --}}
    <header>
        <img src="{{ asset('images/brand/haus-to-haus.png') }}" alt="Runjau Haus To Haus" class="h-14 w-auto mb-5" width="191" height="56">
        <p class="text-xs text-muted">Dashboard peserta · Runjau Haus To Haus</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight break-words">{{ $participant->name }}</h1>
        <p class="mt-1 font-mono text-ink-2">BIB {{ $participant->bib_number }}</p>
    </header>

    {{-- Lomba (data RFID) --}}
    <section aria-labelledby="race-heading">
        <h2 id="race-heading" class="text-base font-semibold pb-2 border-b border-line">Running Event</h2>

        <dl class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-y-4">
            <div>
                <dt class="text-xs text-muted">Jarak</dt>
                <dd class="mt-1 text-3xl font-mono text-accent">{{ $km($raceDistanceM) }}<span class="text-base text-muted ml-1">km</span></dd>
            </div>
            <div class="sm:border-l sm:border-line sm:pl-6">
                <dt class="text-xs text-muted">Loop</dt>
                <dd class="mt-1 text-3xl font-mono">{{ $loopCount }}</dd>
            </div>
            <div class="col-span-2 sm:col-span-1 sm:border-l sm:border-line sm:pl-6">
                <dt class="text-xs text-muted">Jarak per loop</dt>
                <dd class="mt-1 text-3xl font-mono text-ink-2">{{ $trackLength >= 1000 ? $km($trackLength) : $trackLength }}<span class="text-base text-muted ml-1">{{ $trackLength >= 1000 ? 'km' : 'm' }}</span></dd>
            </div>
        </dl>
        @if ($raceRoute)
            <div class="mt-6">
                <div class="flex items-baseline justify-between gap-3">
                    <h3 class="text-sm">{{ $raceRoute->name ?: 'Rute lomba' }}</h3>
                    <span class="font-mono text-sm text-ink-2">{{ $km($raceRoute->distance_m) }} km</span>
                </div>
                <div id="race-map" class="mt-2 h-72 sm:h-80 rounded-md border border-line bg-surface" role="img" aria-label="Peta rute lomba"></div>
            </div>
        @endif

        <p class="mt-3 text-xs text-muted">Jarak = jumlah loop × jarak per loop ({{ $trackSource === 'rute' ? 'diambil dari rute lomba' : 'nilai default, rute lomba belum diunggah' }}). Waktu adalah waktu server saat kartu dipindai (WIB), bukan timing otomatis.</p>

        @if (count($splits))
            <div class="mt-5 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-muted border-b border-line text-left">
                            <th scope="col" class="py-2 pr-3 font-normal">Loop</th>
                            <th scope="col" class="py-2 px-3 font-normal">Waktu scan</th>
                            <th scope="col" class="py-2 pl-3 font-normal text-right">Selisih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line-soft">
                        @foreach (array_reverse($splits) as $s)
                            <tr>
                                <td class="py-2 pr-3 font-mono">{{ $s['number'] }}</td>
                                <td class="py-2 px-3 font-mono text-ink-2">{{ $s['time']->timezone('Asia/Jakarta')->format('H:i:s') }}</td>
                                <td class="py-2 pl-3 font-mono text-right text-ink-2">{{ $dur($s['split_s']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="mt-5 text-sm text-muted">Belum ada loop tercatat. Jarak akan bertambah setiap kali kartu dipindai di garis start/finish.</p>
        @endif
    </section>

    {{-- Lari pribadi (GPX) --}}
    <section aria-labelledby="runs-heading">
        <div class="flex items-baseline justify-between pb-2 border-b border-line">
            <h2 id="runs-heading" class="text-base font-semibold">Lari saya</h2>
            @if ($runs->isNotEmpty())
                <span class="text-xs text-muted">{{ $runs->count() }} lari · <span class="font-mono">{{ $km($runsDistanceM) }}</span> km</span>
            @endif
        </div>

        <form method="POST" action="{{ route('participant.runs.store', $token) }}" enctype="multipart/form-data" class="mt-4" id="gpx-form">
            @csrf
            <label for="gpx" class="text-sm">Tambah lari dari file GPX</label>
            <p id="gpx-hint" class="text-xs text-muted mt-0.5">Ekspor aktivitas dari Strava, Garmin, atau aplikasi lari lain sebagai .gpx (maks. 5 MB).</p>
            <div class="mt-2 flex flex-col sm:flex-row gap-2">
                <input type="file" name="gpx" id="gpx" accept=".gpx,application/gpx+xml" required aria-describedby="gpx-hint"
                       class="flex-1 min-w-0 text-sm text-ink-2 file:mr-3 file:px-3 file:py-2 file:rounded-md file:border file:border-line file:bg-raised file:text-ink file:text-sm hover:file:bg-line">
                <button type="submit" class="px-4 py-2 rounded-md bg-accent hover:bg-accent-hover text-accent-ink text-sm font-semibold disabled:opacity-50">Unggah</button>
            </div>
        </form>

        @if ($runs->isNotEmpty())
            <div id="run-map" class="mt-6 h-72 sm:h-80 rounded-md border border-line bg-surface" role="img" aria-label="Peta rute lari terpilih"></div>
            <p class="mt-2 text-xs text-muted">200 m awal dan akhir rute disembunyikan supaya lokasi rumah tidak terlihat. Jarak tetap dihitung penuh.</p>

            <ul class="mt-4 divide-y divide-line-soft border-y border-line-soft">
                @foreach ($runs as $run)
                    <li class="flex items-center gap-3 py-2.5">
                        <button type="button" data-run="{{ $run->id }}"
                                class="run-select flex-1 min-w-0 text-left flex items-baseline justify-between gap-3 rounded px-1 -mx-1 hover:bg-surface aria-pressed:text-accent"
                                aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                            <span class="min-w-0">
                                <span class="block truncate">{{ $run->name ?: 'Lari' }}</span>
                                <span class="block text-xs text-muted">{{ $run->started_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') ?? 'Tanpa waktu' }}</span>
                            </span>
                            <span class="shrink-0 text-right font-mono">
                                {{ $km($run->distance_m) }} km
                                <span class="block text-xs text-muted">{{ $dur($run->duration_s) }}</span>
                            </span>
                        </button>
                        <form method="POST" action="{{ route('participant.runs.destroy', [$token, $run]) }}" onsubmit="return confirm('Hapus lari ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-muted hover:text-rose-300 px-1" aria-label="Hapus {{ $run->name ?: 'lari' }}">Hapus</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="mt-5 text-sm text-muted">Belum ada lari pribadi. Unggah file GPX untuk melihat jarak dan rutenya di peta.</p>
        @endif
    </section>

    <p class="text-xs text-faint border-t border-line pt-4">
        Halaman ini bisa dibuka siapa pun yang memegang link atau QR kartumu. Jangan bagikan ke orang lain.
        Jika kartu hilang, minta panitia mengganti link-nya.
    </p>
</div>
@endsection

@push('scripts')
@if ($runs->isNotEmpty() || $raceRoute)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    // Satu tempat untuk penyedia peta (lihat config/rfid.php -> map).
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
        if (typeof L === 'undefined') {
            el.innerHTML = '<p class="p-4 text-sm text-muted">Peta tidak bisa dimuat (butuh koneksi internet).</p>';
            return;
        }
        const pts = @json($raceRoute->route);
        const map = L.map(el, { scrollWheelZoom: false });
        addTiles(map);
        const line = L.polyline(pts, { color: '#F25919', weight: 4 }).addTo(map);
        L.circleMarker(pts[0], { radius: 6, color: '#f3f1ee', fillOpacity: 1 }).bindTooltip('Start').addTo(map);
        L.circleMarker(pts[pts.length - 1], { radius: 6, color: '#F25919', fillOpacity: 1 }).bindTooltip('Finish').addTo(map);
        map.fitBounds(line.getBounds(), { padding: [20, 20] });
    })();
</script>
@endif
@if ($runs->isNotEmpty())
<script>
    (function () {
        const runs = @json($runs->mapWithKeys(fn ($r) => [$r->id => $r->route]));
        const mapEl = document.getElementById('run-map');

        if (typeof L === 'undefined') {
            mapEl.innerHTML = '<p class="p-4 text-sm text-muted">Peta tidak bisa dimuat (butuh koneksi internet). Data jarak tetap tersimpan.</p>';
            return;
        }

        const map = L.map(mapEl, { scrollWheelZoom: false });
        addTiles(map);

        let layer = null;
        function show(id) {
            const pts = runs[id];
            if (!pts || !pts.length) return;
            if (layer) layer.remove();
            const line = L.polyline(pts, { color: '#F25919', weight: 4 });
            const start = L.circleMarker(pts[0], { radius: 5, color: '#f3f1ee', fillOpacity: 1 }).bindTooltip('Awal (terlihat)');
            const end = L.circleMarker(pts[pts.length - 1], { radius: 5, color: '#F25919', fillOpacity: 1 }).bindTooltip('Akhir (terlihat)');
            layer = L.layerGroup([line, start, end]).addTo(map);
            map.fitBounds(line.getBounds(), { padding: [20, 20] });

            document.querySelectorAll('.run-select').forEach(b => b.setAttribute('aria-pressed', b.dataset.run == id ? 'true' : 'false'));
        }

        document.querySelectorAll('.run-select').forEach(b => b.addEventListener('click', () => show(b.dataset.run)));
        show(document.querySelector('.run-select').dataset.run);
    })();
</script>
@endif
<script>
    // Cegah unggah ganda saat tombol ditekan berkali-kali.
    document.getElementById('gpx-form')?.addEventListener('submit', e => {
        const btn = e.target.querySelector('button[type=submit]');
        btn.disabled = true; btn.textContent = 'Mengunggah…';
    });
</script>
@endpush
