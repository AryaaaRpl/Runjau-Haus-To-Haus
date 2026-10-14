@extends('layouts.app')

@section('title', 'Scan')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] gap-x-10 gap-y-8">

    {{-- Kolom kiri: input scan + hasil --}}
    <section aria-labelledby="scan-heading" class="space-y-8">
        <div>
            <div class="flex items-baseline justify-between mb-2">
                <h1 id="scan-heading" class="text-base font-semibold">Scan kartu peserta</h1>
                <span class="flex items-center gap-1.5 text-xs text-muted">
                    <span id="reader-dot" class="w-1.5 h-1.5 rounded-full bg-faint"></span>
                    <span id="reader-status-label" role="status" aria-live="polite">Menunggu fokus</span>
                </span>
            </div>

            <form id="rfid-scan-form" onsubmit="event.preventDefault(); submitScan();">
                <label for="rfid-input" class="sr-only">UID kartu RFID</label>
                <div class="flex">
                    <input
                        type="text"
                        id="rfid-input"
                        inputmode="numeric"
                        autocomplete="off"
                        spellcheck="false"
                        autofocus
                        placeholder="Tempel kartu ke reader, atau ketik UID lalu Enter"
                        aria-describedby="rfid-hint"
                        class="flex-1 min-w-0 bg-surface border border-line border-r-0 focus:border-accent rounded-l-md px-3.5 h-12 text-lg font-mono text-ink placeholder:text-faint placeholder:text-sm placeholder:font-sans outline-none transition-colors"
                    />
                    <button
                        type="submit"
                        id="btn-submit-scan"
                        class="shrink-0 px-4 h-12 rounded-r-md bg-accent hover:bg-accent-hover text-accent-ink text-sm font-semibold transition-colors disabled:opacity-50 disabled:cursor-wait"
                    >
                        <span id="btn-submit-label">Catat</span>
                    </button>
                </div>
                <p id="rfid-hint" class="mt-2 text-xs text-muted">
                    Reader bekerja sebagai keyboard. UID disimpan apa adanya, termasuk angka nol di depan.
                    <button type="button" id="btn-refocus" class="underline underline-offset-2 hover:text-ink">Fokuskan input</button>
                </p>
                <p id="queue-notice" role="status" aria-live="polite" class="hidden mt-2 text-sm text-amber-200 border-l-2 border-amber-400 pl-3"></p>
            </form>
        </div>

        {{-- Hasil scan terakhir --}}
        <div id="participant-card" aria-live="polite" class="border-l-2 border-line pl-5 transition-colors">
            <div class="flex items-center justify-between text-xs">
                <span class="text-muted">Scan terakhir</span>
                <span id="result-status-badge" class="font-medium text-muted">Belum ada scan</span>
            </div>

            <div id="display-name" class="mt-3 text-2xl font-semibold tracking-tight leading-tight break-words text-faint">
                Menunggu kartu…
            </div>

            <dl class="mt-5 grid grid-cols-[auto_1fr] gap-x-10 items-end">
                <div>
                    <dt class="text-xs text-muted">BIB</dt>
                    <dd id="display-bib" class="mt-1 text-2xl font-mono font-medium text-ink-2">–</dd>
                </div>
                <div class="text-right">
                    <dt class="text-xs text-muted">Loop</dt>
                    <dd id="display-loop" class="mt-0.5 text-5xl font-mono font-medium leading-none text-ink">–</dd>
                </div>
            </dl>

            <p id="scan-feedback-banner" class="hidden mt-5 text-sm"></p>

            <dl class="mt-5 pt-3 border-t border-line-soft grid grid-cols-2 gap-4 text-xs">
                <div>
                    <dt class="text-faint">UID terbaca</dt>
                    <dd id="display-uid" class="mt-0.5 font-mono text-ink-2">–</dd>
                </div>
                <div class="text-right">
                    <dt class="text-faint">Waktu server</dt>
                    <dd id="display-time" class="mt-0.5 font-mono text-ink-2">–</dd>
                </div>
            </dl>
        </div>

        {{-- Simulasi tanpa hardware --}}
        @if ($sampleCards->isNotEmpty())
            <details class="group text-sm">
                <summary class="cursor-pointer select-none text-muted hover:text-ink text-xs list-none flex items-center gap-1.5">
                    <span class="inline-block transition-transform group-open:rotate-90">›</span>
                    Simulasi tanpa reader
                </summary>
                <div class="mt-3 divide-y divide-line-soft border-y border-line-soft">
                    @foreach ($sampleCards as $card)
                        <button type="button" onclick="simulateCard('{{ $card->rfid_uid }}')"
                                class="w-full flex items-center justify-between gap-3 py-2 text-left hover:bg-surface transition-colors">
                            <span class="truncate">{{ $card->name }}</span>
                            <span class="font-mono text-xs text-muted shrink-0">BIB {{ $card->bib_number }} · {{ $card->rfid_uid }}</span>
                        </button>
                    @endforeach
                    <button type="button" onclick="simulateCard('0009999999')"
                            class="w-full flex items-center justify-between gap-3 py-2 text-left hover:bg-surface transition-colors">
                        <span class="text-ink-2">Kartu tidak terdaftar</span>
                        <span class="font-mono text-xs text-muted">0009999999</span>
                    </button>
                </div>
            </details>
        @endif
    </section>

    {{-- Kolom kanan: aktivitas terbaru --}}
    <section aria-labelledby="recent-heading" class="min-w-0">
        <div class="flex items-baseline justify-between mb-2">
            <h2 id="recent-heading" class="text-base font-semibold">Aktivitas terbaru</h2>
            <span id="recent-refresh-label" class="text-xs text-muted">10 scan terakhir</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-xs text-muted border-b border-line">
                        <th scope="col" class="py-2 pr-3 font-normal">Waktu</th>
                        <th scope="col" class="py-2 px-3 font-normal">Peserta</th>
                        <th scope="col" class="py-2 px-3 font-normal text-right">BIB</th>
                        <th scope="col" class="py-2 px-3 font-normal">Hasil</th>
                        <th scope="col" class="py-2 pl-3 font-normal text-right">Loop</th>
                    </tr>
                </thead>
                <tbody id="recent-activity-tbody" class="divide-y divide-line-soft">
                    @forelse ($recentScans as $scan)
                        @php
                            [$label, $cls] = match ($scan['status']) {
                                'accepted' => ['Diterima', 'text-ink-2'],
                                'ignored_duplicate' => ['Duplikat', 'text-amber-300'],
                                default => ['Ditolak', 'text-rose-300'],
                            };
                        @endphp
                        <tr>
                            <td class="py-2 pr-3 font-mono text-xs text-muted whitespace-nowrap">{{ $scan['scanned_at'] }}</td>
                            <td class="py-2 px-3 {{ $scan['status'] === 'rejected' ? 'text-muted' : '' }}">{{ $scan['participant_name'] }}</td>
                            <td class="py-2 px-3 font-mono text-right text-ink-2">{{ $scan['bib_number'] }}</td>
                            <td class="py-2 px-3 whitespace-nowrap {{ $cls }}">
                                <span class="inline-block w-1.5 h-1.5 rounded-full mr-1.5 align-middle {{ $scan['status'] === 'accepted' ? 'bg-emerald-500' : ($scan['status'] === 'ignored_duplicate' ? 'bg-amber-400' : 'bg-rose-500') }}"></span>{{ $label }}
                            </td>
                            <td class="py-2 pl-3 font-mono text-right">{{ $scan['resulting_loop_count'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-sm text-muted">Belum ada scan. Aktivitas akan muncul di sini setelah kartu pertama dibaca.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

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

    const participantCard = document.getElementById('participant-card');
    const statusBadge = document.getElementById('result-status-badge');
    const displayName = document.getElementById('display-name');
    const displayBib = document.getElementById('display-bib');
    const displayLoop = document.getElementById('display-loop');
    const displayUid = document.getElementById('display-uid');
    const displayTime = document.getElementById('display-time');
    const feedbackBanner = document.getElementById('scan-feedback-banner');
    const recentTbody = document.getElementById('recent-activity-tbody');
    const recentLabel = document.getElementById('recent-refresh-label');

    let isScanning = false;

    // ---------- Antrean offline ----------
    // Scan yang gagal terkirim (jaringan putus) disimpan di browser lalu dikirim ulang otomatis.
    // Setiap scan punya client_scan_id unik, jadi pengiriman ulang tidak pernah tercatat dua kali.
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
        try { localStorage.setItem(QUEUE_KEY, JSON.stringify(q)); } catch (e) { /* storage penuh/diblokir */ }
        renderQueueNotice(q);
    }

    function renderQueueNotice(q = loadQueue()) {
        if (!q.length) { queueNotice.classList.add('hidden'); return; }
        queueNotice.classList.remove('hidden');
        queueNotice.textContent = `${q.length} scan belum terkirim ke server dan akan dikirim ulang otomatis saat koneksi kembali. ` +
            'Waktu yang tercatat nanti adalah waktu server saat menerima, jadi akan lebih lambat dari waktu tap sebenarnya.';
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
                // Beri jarak antar kartu yang sama agar tidak terbaca sebagai double-tap oleh server.
                const wait = lastSentAt[item.uid] ? (DEBOUNCE_SECONDS + 1) * 1000 - (Date.now() - lastSentAt[item.uid]) : 0;
                if (wait > 0) await sleep(wait);

                let res;
                try {
                    res = await postScan(item.uid, item.id);
                } catch (e) {
                    break; // masih offline, coba lagi nanti
                }
                if (res.status >= 500 || res.status === 419) break; // 419 = sesi kedaluwarsa, muat ulang halaman

                lastSentAt[item.uid] = Date.now();
                q = loadQueue().filter(x => x.id !== item.id);
                saveQueue(q);
            }
        } finally {
            isFlushing = false;
            refreshRecentActivity();
        }
    }

    window.addEventListener('online', flushQueue);
    setInterval(flushQueue, 5000);
    renderQueueNotice();
    flushQueue();

    // Status input: hanya mencerminkan apakah input siap menerima ketikan reader,
    // bukan konektivitas hardware (tidak bisa diverifikasi dari browser).
    function setReaderState(state) {
        const map = {
            ready:   ['bg-accent', 'Siap menerima scan'],
            idle:    ['bg-faint',  'Input tidak fokus — klik area kosong'],
            working: ['bg-accent', 'Memproses…'],
        };
        const [dot, text] = map[state];
        readerDot.className = 'w-1.5 h-1.5 rounded-full ' + dot;
        readerLabel.textContent = text;
    }

    inputEl.addEventListener('focus', () => { if (!isScanning) setReaderState('ready'); });
    inputEl.addEventListener('blur', () => { if (!isScanning) setReaderState('idle'); });

    function keepFocus() {
        if (inputEl && document.activeElement !== inputEl && !isScanning) {
            inputEl.focus();
        }
    }

    window.addEventListener('load', keepFocus);
    refocusBtn.addEventListener('click', () => inputEl.focus());

    document.addEventListener('click', (e) => {
        if (!e.target.closest('button') && !e.target.closest('a') && !e.target.closest('input') && !e.target.closest('summary')) {
            keepFocus();
        }
    });

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
        submitLabel.textContent = 'Memproses';

        try {
            const response = await postScan(uid, scanId);
            if (response.status >= 500) throw new Error('Server error ' + response.status);

            const data = await response.json();
            handleScanResponse(data, uid, response.status);
            await refreshRecentActivity();

        } catch (err) {
            console.error('Scan Error:', err);
            // Jangan minta operator tap ulang: simpan ke antrean, dikirim ulang otomatis dengan ID yang sama.
            enqueue(uid, scanId);
            displayUid.textContent = uid;
            displayTime.textContent = new Date().toLocaleTimeString('id-ID', { hour12: false }) + ' (lokal)';
            setParticipant('Menunggu koneksi…', '–', '–', true);
            setResult('warn', 'Belum terkirim', 'Server tidak bisa dihubungi. Scan disimpan di perangkat ini dan akan dikirim otomatis — tidak perlu tap ulang.');
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
            ok:   ['border-emerald-500', 'text-emerald-400', 'text-ink-2'],
            warn: ['border-amber-400',   'text-amber-300',   'text-amber-200'],
            err:  ['border-rose-500',    'text-rose-300',    'text-rose-200'],
        };
        const [border, badgeColor, msgColor] = tones[tone];
        participantCard.className = 'border-l-2 pl-5 transition-colors ' + border;
        statusBadge.className = 'font-medium ' + badgeColor;
        statusBadge.textContent = badge;
        feedbackBanner.className = 'mt-5 text-sm ' + msgColor;
        feedbackBanner.textContent = message;
    }

    function setParticipant(name, bib, loop, dim = false) {
        displayName.className = 'mt-3 text-2xl font-semibold tracking-tight leading-tight break-words ' + (dim ? 'text-muted' : 'text-ink');
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
            setResult('warn', 'Duplikat — tidak dihitung', data.message || 'Kartu yang sama dibaca terlalu cepat. Loop tidak ditambahkan.');
        } else {
            showErrorState(data.message || `UID ${uid} tidak terdaftar ke peserta mana pun.`, uid);
        }
    }

    function showErrorState(message, uid, badge = 'Ditolak') {
        displayUid.textContent = uid;
        setParticipant('Kartu tidak dikenali', '–', '–', true);
        setResult('err', badge, message);
    }

    async function refreshRecentActivity() {
        try {
            const res = await fetch('/api/dashboard/recent-scans');
            const result = await res.json();
            if (result.success && Array.isArray(result.data)) {
                renderRecentActivity(result.data);
                recentLabel.textContent = 'Diperbarui ' + new Date().toLocaleTimeString('id-ID', { hour12: false });
            }
        } catch (e) {
            console.error('Activity refresh error:', e);
            recentLabel.textContent = 'Gagal memuat ulang daftar';
        }
    }

    function escapeHtml(v) {
        return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function renderRecentActivity(scans) {
        if (!scans.length) {
            recentTbody.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-sm text-muted">Belum ada scan.</td></tr>';
            return;
        }

        recentTbody.innerHTML = scans.map(s => {
            let label = 'Ditolak', cls = 'text-rose-300', dot = 'bg-rose-500';
            if (s.status === 'accepted') { label = 'Diterima'; cls = 'text-ink-2'; dot = 'bg-emerald-500'; }
            else if (s.status === 'ignored_duplicate') { label = 'Duplikat'; cls = 'text-amber-300'; dot = 'bg-amber-400'; }

            return `
                <tr>
                    <td class="py-2 pr-3 font-mono text-xs text-muted whitespace-nowrap">${escapeHtml(s.scanned_at)}</td>
                    <td class="py-2 px-3 ${s.status === 'rejected' ? 'text-muted' : ''}">${escapeHtml(s.participant_name)}</td>
                    <td class="py-2 px-3 font-mono text-right text-ink-2">${escapeHtml(s.bib_number)}</td>
                    <td class="py-2 px-3 whitespace-nowrap ${cls}"><span class="inline-block w-1.5 h-1.5 rounded-full mr-1.5 align-middle ${dot}"></span>${label}</td>
                    <td class="py-2 pl-3 font-mono text-right">${escapeHtml(s.resulting_loop_count ?? '—')}</td>
                </tr>`;
        }).join('');
    }
</script>
@endpush
