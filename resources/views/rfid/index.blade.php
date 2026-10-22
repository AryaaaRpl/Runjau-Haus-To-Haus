@extends('layouts.app')

@section('title', 'Scan Operasional')

@section('content')
<div class="space-y-6">

    {{-- Ringkasan Operasional Atas --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 pb-6 border-b border-line">
        <div class="bg-surface/60 border border-line rounded-lg p-3 sm:p-4">
            <div class="text-[11px] uppercase tracking-wider text-muted font-medium">Total Peserta</div>
            <div id="stat-total-participants" class="mt-1 text-2xl font-mono font-medium text-ink">{{ $summary['total_participants'] }}</div>
            <div class="mt-0.5 text-[11px] text-faint">Pelari terdaftar</div>
        </div>
        <div class="bg-surface/60 border border-line rounded-lg p-3 sm:p-4">
            <div class="text-[11px] uppercase tracking-wider text-muted font-medium">Total Putaran</div>
            <div id="stat-total-loops" class="mt-1 text-2xl font-mono font-medium text-accent">{{ $summary['total_loops'] }}</div>
            <div class="mt-0.5 text-[11px] text-faint">Loop terverifikasi</div>
        </div>
        <div class="bg-surface/60 border border-line rounded-lg p-3 sm:p-4">
            <div class="text-[11px] uppercase tracking-wider text-muted font-medium">Scan Diterima</div>
            <div id="stat-accepted-scans" class="mt-1 text-2xl font-mono font-medium text-emerald-400">{{ $summary['accepted_scans'] }}</div>
            <div class="mt-0.5 text-[11px] text-faint">Tap valid</div>
        </div>
        <div class="bg-surface/60 border border-line rounded-lg p-3 sm:p-4">
            <div class="text-[11px] uppercase tracking-wider text-muted font-medium">Status Reader</div>
            <div class="mt-1.5 flex items-center gap-2">
                <span id="stat-reader-dot" class="w-2.5 h-2.5 rounded-full bg-accent"></span>
                <span id="stat-reader-status" class="text-sm font-semibold text-ink">Siap Scan</span>
            </div>
            <div class="mt-0.5 text-[11px] text-faint">Mode input keyboard aktif</div>
        </div>
    </div>

    {{-- Layout Utama: 2 Kolom Seimbang & Terstruktur --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        {{-- Kolom Kiri: Terminal Reader & Hasil Verifikasi (5 Kolom) --}}
        <section aria-labelledby="scan-heading" class="lg:col-span-5 space-y-6">

            {{-- Input Box Terminal --}}
            <div class="bg-surface/60 border border-line rounded-lg p-4 sm:p-5">
                <div class="flex items-baseline justify-between mb-3">
                    <h1 id="scan-heading" class="text-sm font-semibold text-ink flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-accent inline-block"></span>
                        Terminal Reader RFID
                    </h1>
                    <span class="flex items-center gap-1.5 text-xs text-muted">
                        <span id="reader-dot" class="w-1.5 h-1.5 rounded-full bg-accent"></span>
                        <span id="reader-status-label" role="status" aria-live="polite">Siap menerima scan</span>
                    </span>
                </div>

                <form id="rfid-scan-form" onsubmit="event.preventDefault(); submitScan();">
                    <label for="rfid-input" class="sr-only">UID kartu RFID</label>
                    <div class="flex rounded-md overflow-hidden focus-within:ring-1 focus-within:ring-accent">
                        <input
                            type="text"
                            id="rfid-input"
                            inputmode="numeric"
                            autocomplete="off"
                            spellcheck="false"
                            autofocus
                            placeholder="Tempel kartu ke reader atau ketik UID…"
                            aria-describedby="rfid-hint"
                            class="flex-1 min-w-0 bg-canvas border border-line border-r-0 focus:border-accent px-3.5 h-12 text-base font-mono text-ink placeholder:text-faint placeholder:text-xs placeholder:font-sans outline-none transition-colors"
                        />
                        <button
                            type="submit"
                            id="btn-submit-scan"
                            class="shrink-0 px-5 h-12 bg-accent hover:bg-accent-hover text-accent-ink text-xs font-semibold uppercase tracking-wider transition-colors disabled:opacity-50 disabled:cursor-wait"
                        >
                            <span id="btn-submit-label">Catat</span>
                        </button>
                    </div>
                    <div class="mt-2.5 flex items-center justify-between text-xs text-muted">
                        <span>Tekan <kbd class="px-1.5 py-0.5 rounded bg-raised border border-line text-[11px] font-mono text-ink-2">Enter</kbd> otomatis setelah tap.</span>
                        <button type="button" id="btn-refocus" class="underline underline-offset-2 hover:text-ink transition-colors">Fokuskan Reader</button>
                    </div>
                    <p id="queue-notice" role="status" aria-live="polite" class="hidden mt-3 text-xs text-amber-200 border-l-2 border-amber-400 pl-3 py-1 bg-surface"></p>
                </form>
            </div>

            {{-- Kartu Hasil Scan Terakhir --}}
            <div id="participant-card" aria-live="polite" class="bg-surface/60 border border-line border-l-4 border-l-line rounded-lg p-5 transition-all">
                <div class="flex items-center justify-between pb-3 border-b border-line-soft text-xs">
                    <span class="text-muted font-medium uppercase tracking-wider text-[11px]">Hasil Verifikasi Terakhir</span>
                    <span id="result-status-badge" class="px-2 py-0.5 rounded text-[11px] font-medium bg-raised text-muted">Belum ada scan</span>
                </div>

                <div class="mt-4">
                    <div class="text-[11px] text-faint uppercase tracking-wider">Nama Peserta</div>
                    <div id="display-name" class="mt-0.5 text-xl font-semibold tracking-tight leading-snug break-words text-faint">
                        Menunggu kartu…
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-4 items-end bg-canvas/60 border border-line-soft rounded-lg p-4">
                    <div>
                        <div class="text-[11px] uppercase tracking-wider text-muted font-medium">Nomor BIB</div>
                        <div id="display-bib" class="mt-1 text-2xl font-mono font-medium text-ink-2">–</div>
                    </div>
                    <div class="text-right">
                        <div class="text-[11px] uppercase tracking-wider text-muted font-medium">Total Loop</div>
                        <div id="display-loop" class="mt-0.5 text-5xl font-mono font-bold leading-none text-ink">–</div>
                    </div>
                </div>

                <p id="scan-feedback-banner" class="hidden mt-4 p-2.5 rounded text-xs"></p>

                <dl class="mt-4 pt-3 border-t border-line-soft grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <dt class="text-faint text-[11px]">UID Terbaca</dt>
                        <dd id="display-uid" class="mt-0.5 font-mono text-ink-2 truncate">–</dd>
                    </div>
                    <div class="text-right">
                        <dt class="text-faint text-[11px]">Waktu Server</dt>
                        <dd id="display-time" class="mt-0.5 font-mono text-ink-2">–</dd>
                    </div>
                </dl>
            </div>

            {{-- Simulasi Cepat Tanpa Hardware --}}
            @if ($sampleCards->isNotEmpty())
                <div class="bg-surface/40 border border-line rounded-lg p-4">
                    <details class="group text-sm">
                        <summary class="cursor-pointer select-none text-muted hover:text-ink text-xs list-none flex items-center justify-between">
                            <span class="flex items-center gap-1.5 font-medium">
                                <span class="inline-block transition-transform group-open:rotate-90">›</span>
                                Simulasi Kartu Cepat (Demo)
                            </span>
                            <span class="text-[11px] text-faint">Klik salah satu untuk menguji</span>
                        </summary>
                        <div class="mt-3 pt-3 border-t border-line-soft space-y-1.5">
                            @foreach ($sampleCards as $card)
                                <button
                                    type="button"
                                    onclick="simulateCard('{{ $card->rfid_uid }}')"
                                    class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded bg-surface hover:bg-raised border border-line-soft text-left transition-colors"
                                >
                                    <div class="min-w-0">
                                        <div class="text-xs font-medium text-ink truncate">{{ $card->name }}</div>
                                        <div class="text-[11px] font-mono text-muted">BIB {{ $card->bib_number }}</div>
                                    </div>
                                    <span class="font-mono text-xs text-accent shrink-0">Tap Kartu</span>
                                </button>
                            @endforeach
                            <button
                                type="button"
                                onclick="simulateCard('0009999999')"
                                class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded bg-surface hover:bg-raised border border-line-soft text-left transition-colors"
                            >
                                <span class="text-xs text-rose-300">Uji Kartu Tidak Terdaftar</span>
                                <span class="font-mono text-xs text-rose-400 shrink-0">UID 0009999999</span>
                            </button>
                        </div>
                    </details>
                </div>
            @endif

        </section>

        {{-- Kolom Kanan: Konsol Data Peserta & Aktivitas Scan dengan Paginasi (7 Kolom) --}}
        <section aria-labelledby="console-heading" class="lg:col-span-7 flex flex-col space-y-4 min-w-0">

            {{-- Navigasi Tab & Pencarian --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-surface/60 border border-line rounded-lg p-2.5 sm:p-3">
                <div class="flex items-center space-x-1.5">
                    <button
                        type="button"
                        id="tab-btn-participants"
                        onclick="switchTab('participants')"
                        class="px-3 py-1.5 rounded-md text-xs font-semibold transition-colors bg-accent text-accent-ink"
                    >
                        Data Peserta (<span id="tab-count-participants">{{ $summary['total_participants'] }}</span>)
                    </button>
                    <button
                        type="button"
                        id="tab-btn-scans"
                        onclick="switchTab('scans')"
                        class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-muted hover:text-ink hover:bg-raised"
                    >
                        Aktivitas Scan (<span id="tab-count-scans">{{ $summary['total_scans'] }}</span>)
                    </button>
                </div>

                <div class="relative min-w-[200px]">
                    <label for="table-search" class="sr-only">Cari</label>
                    <input
                        type="text"
                        id="table-search"
                        placeholder="Cari nama, BIB, UID…"
                        autocomplete="off"
                        class="w-full bg-canvas border border-line focus:border-accent rounded-md px-3 py-1.5 text-xs text-ink placeholder:text-faint outline-none transition-colors"
                    />
                    <button
                        type="button"
                        id="btn-clear-search"
                        onclick="clearSearch()"
                        class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-faint hover:text-ink text-xs"
                    >✕</button>
                </div>
            </div>

            {{-- Kontainer Tabel Terpadu --}}
            <div class="bg-surface/60 border border-line rounded-lg overflow-hidden flex flex-col min-h-[460px]">

                {{-- Tabel 1: Data Peserta (Leaderboard / List) --}}
                <div id="view-participants" class="flex-1 overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead>
                            <tr class="text-muted border-b border-line bg-surface">
                                <th scope="col" class="py-2.5 px-3 font-medium w-12 text-center">#</th>
                                <th scope="col" class="py-2.5 px-3 font-medium font-sans">Peserta</th>
                                <th scope="col" class="py-2.5 px-3 font-medium text-center">BIB</th>
                                <th scope="col" class="py-2.5 px-3 font-medium text-right font-sans">Total Loop</th>
                                <th scope="col" class="py-2.5 px-3 font-medium text-right font-sans">Scan Terakhir</th>
                                <th scope="col" class="py-2.5 px-3 font-medium text-center font-sans">Kartu RFID</th>
                            </tr>
                        </thead>
                        <tbody id="participants-tbody" class="divide-y divide-line-soft">
                            @forelse ($initialParticipants as $index => $runner)
                                <tr class="hover:bg-surface transition-colors">
                                    <td class="py-2.5 px-3 text-center text-muted font-mono">{{ $initialParticipants->firstItem() + $index }}</td>
                                    <td class="py-2.5 px-3 font-sans font-medium text-ink">
                                        <div class="truncate max-w-[180px]">{{ $runner->name }}</div>
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-mono text-ink-2">{{ $runner->bib_number }}</td>
                                    <td class="py-2.5 px-3 text-right">
                                        <span class="inline-block px-2 py-0.5 rounded font-mono font-semibold {{ $runner->loop_records_count > 0 ? 'bg-accent/15 text-accent' : 'text-faint' }}">
                                            {{ $runner->loop_records_count }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono text-muted">
                                        @if ($runner->latest_scanned_at)
                                            @php $carbonTime = \Carbon\Carbon::parse($runner->latest_scanned_at); @endphp
                                            <div class="leading-tight">
                                                <span>{{ $carbonTime->format('H:i:s') }}</span>
                                                <span class="block text-[10px] text-accent/90 font-sans scan-time-ago" data-time="{{ $carbonTime->toIso8601String() }}">{{ $carbonTime->diffForHumans() }}</span>
                                            </div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-sans">
                                        @if ($runner->rfid_uid)
                                            <span class="inline-flex items-center gap-1 text-[11px] text-emerald-400">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Terpasang
                                            </span>
                                        @else
                                            <span class="text-[11px] text-faint italic">Belum ada</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-sm font-sans text-muted">Belum ada data peserta.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Tabel 2: Aktivitas Scan (Riwayat Log) --}}
                <div id="view-scans" class="flex-1 overflow-x-auto hidden">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-muted border-b border-line bg-surface font-mono">
                                <th scope="col" class="py-2.5 px-3 font-normal">Waktu</th>
                                <th scope="col" class="py-2.5 px-3 font-normal font-sans">Peserta</th>
                                <th scope="col" class="py-2.5 px-3 font-normal text-right">BIB</th>
                                <th scope="col" class="py-2.5 px-3 font-normal font-sans">Hasil</th>
                                <th scope="col" class="py-2.5 px-3 font-normal text-right">Loop</th>
                            </tr>
                        </thead>
                        <tbody id="scans-tbody" class="divide-y divide-line-soft font-mono">
                            @forelse ($recentScans as $scan)
                                @php
                                    [$label, $cls, $dot] = match ($scan['status']) {
                                        'accepted' => ['Diterima', 'text-ink-2', 'bg-emerald-500'],
                                        'ignored_duplicate' => ['Duplikat', 'text-amber-300', 'bg-amber-400'],
                                        default => ['Ditolak', 'text-rose-300', 'bg-rose-500'],
                                    };
                                @endphp
                                <tr class="hover:bg-surface transition-colors">
                                    <td class="py-2.5 px-3 text-muted whitespace-nowrap">
                                        <div class="leading-tight">
                                            <span>{{ $scan['scanned_at'] }}</span>
                                            @if (!empty($scan['scanned_at_iso']))
                                                <span class="block text-[10px] text-muted scan-time-ago" data-time="{{ $scan['scanned_at_iso'] }}">{{ \Carbon\Carbon::parse($scan['scanned_at_iso'])->diffForHumans() }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-3 font-sans font-medium text-ink {{ $scan['status'] === 'rejected' ? 'text-muted' : '' }}">
                                        <div class="truncate max-w-[200px]">{{ $scan['participant_name'] }}</div>
                                    </td>
                                    <td class="py-2.5 px-3 text-right text-ink-2">{{ $scan['bib_number'] }}</td>
                                    <td class="py-2.5 px-3 whitespace-nowrap font-sans {{ $cls }}">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full mr-1.5 align-middle {{ $dot }}"></span>{{ $label }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right text-ink font-semibold">{{ $scan['resulting_loop_count'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-sm font-sans text-muted">Belum ada scan aktivitas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Bar Paginasi Interaktif (Tanpa Full Reload) --}}
                <div class="mt-auto px-4 py-3 border-t border-line bg-surface/40 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <div id="pagination-info" class="text-muted">
                        Menampilkan <span id="pag-from" class="font-mono text-ink">{{ $initialParticipants->firstItem() ?? 0 }}</span>–<span id="pag-to" class="font-mono text-ink">{{ $initialParticipants->lastItem() ?? 0 }}</span> dari <span id="pag-total" class="font-mono text-ink">{{ $initialParticipants->total() }}</span> data
                    </div>

                    <div class="flex items-center space-x-1.5">
                        <button
                            type="button"
                            id="btn-prev-page"
                            onclick="changePage(-1)"
                            disabled
                            class="px-2.5 py-1 rounded bg-raised border border-line text-ink-2 hover:text-ink disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                        >
                            ‹ Sebelumnya
                        </button>
                        <div id="pagination-numbers" class="flex items-center space-x-1 font-mono">
                            {{-- Angka halaman akan di-render dinamis oleh JS --}}
                        </div>
                        <button
                            type="button"
                            id="btn-next-page"
                            onclick="changePage(1)"
                            class="px-2.5 py-1 rounded bg-raised border border-line text-ink-2 hover:text-ink disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                        >
                            Selanjutnya ›
                        </button>
                    </div>
                </div>

            </div>

        </section>

    </div>

</div>
@endsection

@push('scripts')
<script>
    const inputEl = document.getElementById('rfid-input');
    const submitBtn = document.getElementById('btn-submit-scan');
    const submitLabel = document.getElementById('btn-submit-label');
    const refocusBtn = document.getElementById('btn-refocus');
    const readerDot = document.getElementById('reader-dot');
    const readerLabel = document.getElementById('reader-status-label');
    const statReaderDot = document.getElementById('stat-reader-dot');
    const statReaderStatus = document.getElementById('stat-reader-status');

    const participantCard = document.getElementById('participant-card');
    const statusBadge = document.getElementById('result-status-badge');
    const displayName = document.getElementById('display-name');
    const displayBib = document.getElementById('display-bib');
    const displayLoop = document.getElementById('display-loop');
    const displayUid = document.getElementById('display-uid');
    const displayTime = document.getElementById('display-time');
    const feedbackBanner = document.getElementById('scan-feedback-banner');

    const statTotalParticipants = document.getElementById('stat-total-participants');
    const statTotalLoops = document.getElementById('stat-total-loops');
    const statAcceptedScans = document.getElementById('stat-accepted-scans');

    const tabBtnParticipants = document.getElementById('tab-btn-participants');
    const tabBtnScans = document.getElementById('tab-btn-scans');
    const tabCountParticipants = document.getElementById('tab-count-participants');
    const tabCountScans = document.getElementById('tab-count-scans');
    const viewParticipants = document.getElementById('view-participants');
    const viewScans = document.getElementById('view-scans');
    const participantsTbody = document.getElementById('participants-tbody');
    const scansTbody = document.getElementById('scans-tbody');

    const tableSearch = document.getElementById('table-search');
    const btnClearSearch = document.getElementById('btn-clear-search');

    const pagFrom = document.getElementById('pag-from');
    const pagTo = document.getElementById('pag-to');
    const pagTotal = document.getElementById('pag-total');
    const btnPrevPage = document.getElementById('btn-prev-page');
    const btnNextPage = document.getElementById('btn-next-page');
    const paginationNumbers = document.getElementById('pagination-numbers');

    let isScanning = false;
    let activeTab = 'participants';
    let participantsPage = 1;
    let scansPage = 1;
    let currentPagination = {
        current_page: {{ $initialParticipants->currentPage() }},
        last_page: {{ $initialParticipants->lastPage() }},
        from: {{ $initialParticipants->firstItem() ?? 0 }},
        to: {{ $initialParticipants->lastItem() ?? 0 }},
        total: {{ $initialParticipants->total() }}
    };
    let searchQuery = '';
    let searchDebounceTimer = null;

    // ---------- Antrean Offline ----------
    const QUEUE_KEY = 'rfid-pending-scans';
    const DEBOUNCE_SECONDS = {{ (int) config('rfid.debounce_seconds', 3) }};
    const queueNotice = document.getElementById('queue-notice');
    let isFlushing = false;

    function newScanId() {
        if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
        return 'scan-' + Date.now() + '-' + Math.random().toString(16).slice(2);
    }

    function loadQueue() {
        try { return JSON.parse(localStorage.getItem(QUEUE_KEY) || '[]'); } catch (e) { return []; }
    }

    function saveQueue(q) {
        try { localStorage.setItem(QUEUE_KEY, JSON.stringify(q)); } catch (e) { /* storage block */ }
        renderQueueNotice(q);
    }

    function renderQueueNotice(q = loadQueue()) {
        if (!q.length) { queueNotice.classList.add('hidden'); return; }
        queueNotice.classList.remove('hidden');
        queueNotice.textContent = `${q.length} scan belum terkirim ke server dan akan dikirim ulang otomatis saat koneksi kembali.`;
    }

    function enqueue(uid, id) {
        const q = loadQueue();
        if (!q.some(item => item.id === id)) q.push({ uid, id, tapped_at: new Date().toISOString() });
        saveQueue(q);
    }

    function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

    function postScan(uid, id) {
        return fetch('/api/rfid/scan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ rfid_uid: uid, client_scan_id: id })
        });
    }

    async function flushQueue() {
        if (isFlushing || !navigator.onLine) return;
        let q = loadQueue();
        if (!q.length) return;

        isFlushing = true;
        const lastSentAt = {};
        try {
            while (q.length) {
                const item = q[0];
                const wait = lastSentAt[item.uid] ? (DEBOUNCE_SECONDS + 1) * 1000 - (Date.now() - lastSentAt[item.uid]) : 0;
                if (wait > 0) await sleep(wait);

                let res;
                try {
                    res = await postScan(item.uid, item.id);
                } catch (e) {
                    break;
                }
                if (res.status >= 500 || res.status === 419) break;

                lastSentAt[item.uid] = Date.now();
                q = loadQueue().filter(x => x.id !== item.id);
                saveQueue(q);
            }
        } finally {
            isFlushing = false;
            refreshAllData();
        }
    }

    window.addEventListener('online', flushQueue);
    setInterval(flushQueue, 5000);
    renderQueueNotice();
    flushQueue();

    // ---------- Reader Focus & Status ----------
    function setReaderState(state) {
        const map = {
            ready:   ['bg-accent', 'Siap menerima scan', 'Siap Scan', 'bg-accent animate-pulse'],
            idle:    ['bg-faint',  'Input tidak fokus — klik untuk aktifkan', 'Menunggu Fokus', 'bg-faint'],
            working: ['bg-accent', 'Memproses kartu…', 'Memproses…', 'bg-amber-400 animate-spin'],
        };
        const [dot, label, statText, statDot] = map[state] || map.ready;
        readerDot.className = 'w-1.5 h-1.5 rounded-full ' + dot;
        readerLabel.textContent = label;
        if (statReaderDot) statReaderDot.className = 'w-2.5 h-2.5 rounded-full ' + statDot;
        if (statReaderStatus) statReaderStatus.textContent = statText;
    }

    inputEl.addEventListener('focus', () => { if (!isScanning) setReaderState('ready'); });
    inputEl.addEventListener('blur', () => { if (!isScanning) setReaderState('idle'); });

    function keepFocus() {
        if (inputEl && document.activeElement !== inputEl && !isScanning) {
            // Jangan merebut fokus jika user sedang mengetik di kotak pencarian
            if (document.activeElement === tableSearch) return;
            inputEl.focus();
        }
    }

    window.addEventListener('load', keepFocus);
    refocusBtn.addEventListener('click', () => { inputEl.focus(); setReaderState('ready'); });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('button') && !e.target.closest('a') && !e.target.closest('input') && !e.target.closest('summary')) {
            keepFocus();
        }
    });

    // ---------- Scan Action ----------
    async function submitScan() {
        if (isScanning) return;
        const uid = inputEl.value ? inputEl.value.trim() : '';
        if (!uid) { inputEl.focus(); return; }
        await processCardScan(uid);
    }

    function simulateCard(uid) {
        inputEl.value = uid;
        processCardScan(uid);
    }

    async function processCardScan(uid) {
        if (isScanning) return;

        isScanning = true;
        const scanId = newScanId();
        setReaderState('working');
        submitBtn.disabled = true;
        submitLabel.textContent = '…';

        try {
            const response = await postScan(uid, scanId);
            if (response.status >= 500) throw new Error('Server error ' + response.status);

            const data = await response.json();
            handleScanResponse(data, uid, response.status);
            await refreshAllData();

        } catch (err) {
            console.error('Scan Error:', err);
            enqueue(uid, scanId);
            displayUid.textContent = uid;
            displayTime.textContent = new Date().toLocaleTimeString('id-ID', { hour12: false }) + ' (lokal)';
            setParticipant('Menunggu koneksi…', '–', '–', true);
            setResult('warn', 'Belum terkirim', 'Scan disimpan secara offline dan akan dikirim otomatis saat koneksi kembali.');
        } finally {
            isScanning = false;
            submitBtn.disabled = false;
            submitLabel.textContent = 'Catat';
            inputEl.value = '';
            inputEl.focus();
            setReaderState('ready');
        }
    }

    function setResult(tone, badge, message) {
        const tones = {
            ok:   ['border-l-emerald-500', 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20', 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30'],
            warn: ['border-l-amber-400',   'bg-amber-400/10 text-amber-300 border border-amber-400/20',     'bg-amber-400/15 text-amber-200 border border-amber-400/30'],
            err:  ['border-l-rose-500',    'bg-rose-500/10 text-rose-300 border border-rose-500/20',        'bg-rose-500/15 text-rose-200 border border-rose-500/30'],
        };
        const [borderCls, badgeCls, bannerCls] = tones[tone];
        participantCard.className = 'bg-surface/60 border border-line border-l-4 rounded-lg p-5 transition-all ' + borderCls;
        statusBadge.className = 'px-2 py-0.5 rounded text-[11px] font-medium ' + badgeCls;
        statusBadge.textContent = badge;
        feedbackBanner.className = 'mt-4 p-2.5 rounded text-xs ' + bannerCls;
        feedbackBanner.textContent = message;
        feedbackBanner.classList.remove('hidden');
    }

    function setParticipant(name, bib, loop, dim = false) {
        displayName.className = 'mt-0.5 text-xl font-semibold tracking-tight leading-snug break-words ' + (dim ? 'text-muted' : 'text-ink');
        displayName.textContent = name;
        displayBib.textContent = bib;
        displayLoop.textContent = loop;
    }

    function handleScanResponse(data, uid, httpStatus) {
        const timestamp = (data.scanned_at ? new Date(data.scanned_at) : new Date())
            .toLocaleTimeString('id-ID', { hour12: false });

        displayUid.textContent = uid;
        displayTime.textContent = timestamp + ' WIB';

        if (data.status === 'accepted') {
            setParticipant(data.participant.name, data.participant.bib_number, data.current_loop_count);
            setResult('ok', 'Diterima', data.message || `Putaran ke-${data.current_loop_count} tercatat.`);
        } else if (data.status === 'ignored_duplicate') {
            if (data.participant) {
                setParticipant(data.participant.name, data.participant.bib_number, data.current_loop_count);
            }
            setResult('warn', 'Duplikat', data.message || 'Kartu dibaca terlalu cepat. Putaran tidak ditambah.');
        } else {
            displayUid.textContent = uid;
            setParticipant('Kartu Tidak Dikenali', '–', '–', true);
            setResult('err', 'Ditolak', data.message || `UID [${uid}] tidak terdaftar.`);
        }
    }

    // ---------- Tab & Console Data Management ----------
    function switchTab(tab) {
        activeTab = tab;
        if (tab === 'participants') {
            tabBtnParticipants.className = 'px-3 py-1.5 rounded-md text-xs font-semibold transition-colors bg-accent text-accent-ink';
            tabBtnScans.className = 'px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-muted hover:text-ink hover:bg-raised';
            viewParticipants.classList.remove('hidden');
            viewScans.classList.add('hidden');
            loadParticipants(participantsPage);
        } else {
            tabBtnScans.className = 'px-3 py-1.5 rounded-md text-xs font-semibold transition-colors bg-accent text-accent-ink';
            tabBtnParticipants.className = 'px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-muted hover:text-ink hover:bg-raised';
            viewScans.classList.remove('hidden');
            viewParticipants.classList.add('hidden');
            loadScans(scansPage);
        }
        keepFocus();
    }

    tableSearch.addEventListener('input', (e) => {
        searchQuery = e.target.value.trim();
        btnClearSearch.classList.toggle('hidden', searchQuery === '');

        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            if (activeTab === 'participants') {
                participantsPage = 1;
                loadParticipants(1);
            } else {
                scansPage = 1;
                loadScans(1);
            }
        }, 250);
    });

    function clearSearch() {
        tableSearch.value = '';
        searchQuery = '';
        btnClearSearch.classList.add('hidden');
        if (activeTab === 'participants') {
            loadParticipants(1);
        } else {
            loadScans(1);
        }
        keepFocus();
    }

    function changePage(delta) {
        const nextPage = currentPagination.current_page + delta;
        if (nextPage < 1 || nextPage > currentPagination.last_page) return;

        if (activeTab === 'participants') {
            participantsPage = nextPage;
            loadParticipants(nextPage);
        } else {
            scansPage = nextPage;
            loadScans(nextPage);
        }
        keepFocus();
    }

    function goToPage(page) {
        if (page === currentPagination.current_page) return;
        if (activeTab === 'participants') {
            participantsPage = page;
            loadParticipants(page);
        } else {
            scansPage = page;
            loadScans(page);
        }
        keepFocus();
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

    function updateAllRelativeTimes() {
        document.querySelectorAll('.scan-time-ago[data-time]').forEach(el => {
            const iso = el.getAttribute('data-time');
            if (iso) el.textContent = formatTimeAgo(iso);
        });
    }

    function escapeHtml(v) {
        return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    async function loadParticipants(page = 1) {
        try {
            const url = new URL('/api/dashboard/participants', window.location.origin);
            url.searchParams.set('page', page);
            url.searchParams.set('per_page', 10);
            if (searchQuery) url.searchParams.set('search', searchQuery);

            const res = await fetch(url);
            const json = await res.json();
            if (json.success) {
                renderParticipantsTable(json.data, json.pagination);
                updatePaginationUI(json.pagination);
                if (tabCountParticipants && !searchQuery) tabCountParticipants.textContent = json.pagination.total;
            }
        } catch (e) {
            console.error('Error fetching participants:', e);
        }
    }

    async function loadScans(page = 1) {
        try {
            const url = new URL('/api/dashboard/scans', window.location.origin);
            url.searchParams.set('page', page);
            url.searchParams.set('per_page', 10);
            if (searchQuery) url.searchParams.set('search', searchQuery);

            const res = await fetch(url);
            const json = await res.json();
            if (json.success) {
                renderScansTable(json.data, json.pagination);
                updatePaginationUI(json.pagination);
                if (tabCountScans && !searchQuery) tabCountScans.textContent = json.pagination.total;
            }
        } catch (e) {
            console.error('Error fetching scans:', e);
        }
    }

    function renderParticipantsTable(items, pagination) {
        if (!items.length) {
            participantsTbody.innerHTML = '<tr><td colspan="6" class="py-12 text-center text-sm font-sans text-muted">Tidak ada data peserta ditemukan.</td></tr>';
            return;
        }

        const startIdx = (pagination.from || 1);
        participantsTbody.innerHTML = items.map((p, idx) => {
            const loopBadgeCls = p.total_loops > 0 ? 'bg-accent/15 text-accent' : 'text-faint';
            const rfidBadge = p.rfid_uid
                ? '<span class="inline-flex items-center gap-1 text-[11px] text-emerald-400"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Terpasang</span>'
                : '<span class="text-[11px] text-faint italic">Belum ada</span>';

            return `
                <tr class="hover:bg-surface transition-colors">
                    <td class="py-2.5 px-3 text-center text-muted font-mono">${startIdx + idx}</td>
                    <td class="py-2.5 px-3 font-sans font-medium text-ink">
                        <div class="truncate max-w-[180px]">${escapeHtml(p.name)}</div>
                    </td>
                    <td class="py-2.5 px-3 text-center font-mono text-ink-2">${escapeHtml(p.bib_number)}</td>
                    <td class="py-2.5 px-3 text-right">
                        <span class="inline-block px-2 py-0.5 rounded font-mono font-semibold ${loopBadgeCls}">
                            ${p.total_loops}
                        </span>
                    </td>
                    <td class="py-2.5 px-3 text-right font-mono text-muted">
                        ${p.last_scanned_at ? `
                            <div class="leading-tight">
                                <span>${escapeHtml(p.last_scanned_at)}</span>
                                <span class="block text-[10px] text-accent/90 font-sans scan-time-ago" data-time="${escapeHtml(p.last_scanned_at_iso || '')}">${formatTimeAgo(p.last_scanned_at_iso)}</span>
                            </div>
                        ` : '—'}
                    </td>
                    <td class="py-2.5 px-3 text-center font-sans">${rfidBadge}</td>
                </tr>
            `;
        }).join('');
    }

    function renderScansTable(items, pagination) {
        if (!items.length) {
            scansTbody.innerHTML = '<tr><td colspan="5" class="py-12 text-center text-sm font-sans text-muted">Belum ada catatan aktivitas scan.</td></tr>';
            return;
        }

        scansTbody.innerHTML = items.map(s => {
            let label = 'Ditolak', cls = 'text-rose-300', dot = 'bg-rose-500';
            if (s.status === 'accepted') { label = 'Diterima'; cls = 'text-ink-2'; dot = 'bg-emerald-500'; }
            else if (s.status === 'ignored_duplicate') { label = 'Duplikat'; cls = 'text-amber-300'; dot = 'bg-amber-400'; }

            return `
                <tr class="hover:bg-surface transition-colors">
                    <td class="py-2.5 px-3 text-muted whitespace-nowrap">
                        <div class="leading-tight">
                            <span>${escapeHtml(s.scanned_at)}</span>
                            ${s.scanned_at_iso ? `<span class="block text-[10px] text-muted scan-time-ago" data-time="${escapeHtml(s.scanned_at_iso)}">${formatTimeAgo(s.scanned_at_iso)}</span>` : ''}
                        </div>
                    </td>
                    <td class="py-2.5 px-3 font-sans font-medium text-ink ${s.status === 'rejected' ? 'text-muted' : ''}">
                        <div class="truncate max-w-[200px]">${escapeHtml(s.participant_name)}</div>
                    </td>
                    <td class="py-2.5 px-3 text-right text-ink-2">${escapeHtml(s.bib_number)}</td>
                    <td class="py-2.5 px-3 whitespace-nowrap font-sans ${cls}">
                        <span class="inline-block w-1.5 h-1.5 rounded-full mr-1.5 align-middle ${dot}"></span>${label}
                    </td>
                    <td class="py-2.5 px-3 text-right text-ink font-semibold">${escapeHtml(s.resulting_loop_count ?? '—')}</td>
                </tr>
            `;
        }).join('');
    }

    function updatePaginationUI(pagination) {
        currentPagination = pagination;
        pagFrom.textContent = pagination.from || 0;
        pagTo.textContent = pagination.to || 0;
        pagTotal.textContent = pagination.total || 0;

        btnPrevPage.disabled = pagination.current_page <= 1;
        btnNextPage.disabled = pagination.current_page >= pagination.last_page || pagination.last_page === 0;

        // Render page buttons
        const totalPages = pagination.last_page;
        const current = pagination.current_page;
        let buttonsHtml = '';

        if (totalPages <= 6) {
            for (let i = 1; i <= totalPages; i++) {
                buttonsHtml += renderPageButton(i, i === current);
            }
        } else {
            buttonsHtml += renderPageButton(1, 1 === current);
            let start = Math.max(2, current - 1);
            let end = Math.min(totalPages - 1, current + 1);

            if (start > 2) buttonsHtml += '<span class="px-1 text-faint">…</span>';
            for (let i = start; i <= end; i++) {
                buttonsHtml += renderPageButton(i, i === current);
            }
            if (end < totalPages - 1) buttonsHtml += '<span class="px-1 text-faint">…</span>';
            buttonsHtml += renderPageButton(totalPages, totalPages === current);
        }

        paginationNumbers.innerHTML = buttonsHtml;
    }

    function renderPageButton(pageNum, isActive) {
        if (isActive) {
            return `<span class="px-2.5 py-1 rounded bg-accent text-accent-ink font-semibold text-xs">${pageNum}</span>`;
        }
        return `<button type="button" onclick="goToPage(${pageNum})" class="px-2.5 py-1 rounded bg-raised hover:bg-line text-ink-2 hover:text-ink text-xs transition-colors">${pageNum}</button>`;
    }

    async function refreshAllData() {
        try {
            // Update summary counters
            const sumRes = await fetch('/api/dashboard/summary');
            const sumJson = await sumRes.json();
            if (sumJson.success && sumJson.data) {
                const d = sumJson.data;
                if (statTotalParticipants) statTotalParticipants.textContent = d.total_participants;
                if (statTotalLoops) statTotalLoops.textContent = d.total_loops;
                if (statAcceptedScans) statAcceptedScans.textContent = d.accepted_scans;
                if (tabCountParticipants && !searchQuery) tabCountParticipants.textContent = d.total_participants;
                if (tabCountScans && !searchQuery) tabCountScans.textContent = d.total_scans;
            }

            // Refresh current active table
            if (activeTab === 'participants') {
                await loadParticipants(participantsPage);
            } else {
                await loadScans(scansPage);
            }
        } catch (e) {
            console.error('Refresh all data error:', e);
        }
    }

    // Initial render of pagination numbers
    updatePaginationUI(currentPagination);
    // Auto-refresh data secara berkala dan perbarui penanda waktu realtime setiap 3 detik
    setInterval(refreshAllData, 3000);
    setInterval(updateAllRelativeTimes, 3000);
</script>
@endpush
