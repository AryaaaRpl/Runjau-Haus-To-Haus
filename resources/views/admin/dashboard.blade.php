@extends('layouts.app')

@section('title', 'Dashboard Operasional Admin')

@section('content')
<div class="space-y-8">

    {{-- Operational Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-line">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-base font-semibold text-ink">Dashboard Operasional Event</h1>
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-[11px] font-medium text-emerald-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Monitoring Aktif
                </span>
            </div>
            <p class="text-xs text-muted mt-0.5">Pencatatan status peserta, total loop terverifikasi, riwayat scan, dan konfigurasi event.</p>
        </div>
        <div class="flex items-center space-x-2 shrink-0">
            <a href="{{ route('admin.participants.create') }}" class="px-3 py-1.5 rounded bg-raised hover:bg-line text-ink border border-line text-xs font-medium transition-colors">
                + Tambah Peserta
            </a>
            <a href="{{ route('admin.participants.index') }}" class="px-3 py-1.5 rounded bg-raised hover:bg-line text-ink border border-line text-xs font-medium transition-colors">
                Semua Peserta
            </a>
            <a href="{{ route('rfid.scan') }}" class="px-3 py-1.5 rounded bg-accent hover:bg-accent-hover text-accent-ink font-semibold text-xs transition-colors flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-accent-ink"></span>
                Buka Layar Scan
            </a>
        </div>
    </div>

    {{-- Ringkasan Metrik Operasional (4 Kartu Selaras) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <a href="{{ route('admin.participants.index') }}" class="block bg-surface/60 hover:bg-surface border border-line rounded-lg p-4 transition-colors group">
            <div class="flex items-center justify-between">
                <div class="text-[11px] uppercase tracking-wider text-muted font-medium">Total Peserta</div>
                <span class="text-xs text-faint group-hover:text-ink transition-colors">Lihat ›</span>
            </div>
            <div id="stat-total-participants" class="mt-1 text-2xl font-mono font-medium text-ink">{{ $summary['total_participants'] }}</div>
            <div class="mt-0.5 text-[11px] text-faint">Pelari terdaftar dalam sistem</div>
        </a>

        <div class="bg-surface/60 border border-line rounded-lg p-4">
            <div class="text-[11px] uppercase tracking-wider text-muted font-medium">Total Putaran</div>
            <div id="stat-total-loops" class="mt-1 text-2xl font-mono font-medium text-accent">{{ $summary['total_loops'] }}</div>
            <div class="mt-0.5 text-[11px] text-faint">Loop sah terverifikasi</div>
        </div>

        <a href="{{ route('admin.scan-logs', ['status' => 'accepted']) }}" class="block bg-surface/60 hover:bg-surface border border-line rounded-lg p-4 transition-colors group">
            <div class="flex items-center justify-between">
                <div class="text-[11px] uppercase tracking-wider text-muted font-medium">Scan Diterima</div>
                <span class="text-xs text-faint group-hover:text-ink transition-colors">Log ›</span>
            </div>
            <div id="stat-accepted-scans" class="mt-1 text-2xl font-mono font-medium text-emerald-400">{{ $summary['accepted_scans'] }}</div>
            <div class="mt-0.5 text-[11px] text-faint">Tap kartu valid</div>
        </a>

        <a href="{{ route('admin.scan-logs', ['status' => 'rejected']) }}" class="block bg-surface/60 hover:bg-surface border border-line rounded-lg p-4 transition-colors group">
            <div class="flex items-center justify-between">
                <div class="text-[11px] uppercase tracking-wider text-muted font-medium">Scan Ditolak</div>
                <span class="text-xs text-faint group-hover:text-ink transition-colors">Log ›</span>
            </div>
            <div id="stat-rejected-scans" class="mt-1 text-2xl font-mono font-medium {{ $summary['rejected_scans'] > 0 ? 'text-rose-300' : 'text-ink-2' }}">{{ $summary['rejected_scans'] }}</div>
            <div class="mt-0.5 text-[11px] text-faint">Kartu tidak terdaftar / error</div>
        </a>
    </div>

    {{-- Konsol Interaktif: Tab Leaderboard Peserta & Audit Log Scan --}}
    <div class="space-y-4">

        {{-- Tab Controller & Live Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-surface/60 border border-line rounded-lg p-2.5 sm:p-3">
            <div class="flex items-center space-x-1.5">
                <button
                    type="button"
                    id="tab-btn-leaderboard"
                    onclick="switchAdminTab('leaderboard')"
                    class="px-3 py-1.5 rounded-md text-xs font-semibold transition-colors bg-accent text-accent-ink"
                >
                    Leaderboard Peserta (<span id="tab-count-participants">{{ $summary['total_participants'] }}</span>)
                </button>
                <button
                    type="button"
                    id="tab-btn-logs"
                    onclick="switchAdminTab('logs')"
                    class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-muted hover:text-ink hover:bg-raised"
                >
                    Log Scan Terakhir (<span id="tab-count-scans">{{ $summary['total_scans'] }}</span>)
                </button>
            </div>

            <div class="flex items-center space-x-2">
                <div class="relative min-w-[200px]">
                    <label for="admin-table-search" class="sr-only">Cari</label>
                    <input
                        type="text"
                        id="admin-table-search"
                        placeholder="Cari pelari, BIB, UID…"
                        autocomplete="off"
                        class="w-full bg-canvas border border-line focus:border-accent rounded-md px-3 py-1.5 text-xs text-ink placeholder:text-faint outline-none transition-colors"
                    />
                    <button
                        type="button"
                        id="btn-clear-admin-search"
                        onclick="clearAdminSearch()"
                        class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-faint hover:text-ink text-xs"
                    >✕</button>
                </div>

                <button
                    type="button"
                    onclick="refreshAdminData()"
                    id="btn-refresh-admin"
                    title="Muat ulang data terkini"
                    class="px-2.5 py-1.5 rounded bg-raised hover:bg-line border border-line text-xs text-ink-2 hover:text-ink transition-colors flex items-center gap-1"
                >
                    <span id="refresh-icon">↻</span>
                    <span class="hidden sm:inline">Refresh</span>
                </button>
            </div>
        </div>

        {{-- Kontainer Tabel Konsol --}}
        <div class="bg-surface/60 border border-line rounded-lg overflow-hidden flex flex-col min-h-[460px]">

            {{-- View 1: Leaderboard Putaran Peserta --}}
            <div id="view-leaderboard" class="flex-1 overflow-x-auto">
                <table class="w-full text-left text-xs font-mono">
                    <thead>
                        <tr class="text-muted border-b border-line bg-surface">
                            <th scope="col" class="py-2.5 px-4 font-medium w-14 text-center">Peringkat</th>
                            <th scope="col" class="py-2.5 px-4 font-medium font-sans">Nama Pelari</th>
                            <th scope="col" class="py-2.5 px-4 font-medium text-center">BIB</th>
                            <th scope="col" class="py-2.5 px-4 font-medium text-right font-sans">Total Putaran</th>
                            <th scope="col" class="py-2.5 px-4 font-medium text-right font-sans">Scan Terakhir</th>
                            <th scope="col" class="py-2.5 px-4 font-medium text-right font-sans">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="admin-leaderboard-tbody" class="divide-y divide-line-soft">
                        @forelse ($topRunners as $index => $runner)
                            <tr class="hover:bg-surface transition-colors">
                                <td class="py-2.5 px-4 text-center text-muted font-mono font-medium">{{ $topRunners->firstItem() + $index }}</td>
                                <td class="py-2.5 px-4 font-sans font-medium text-ink">
                                    <a href="{{ route('admin.participants.loops', $runner) }}" class="hover:text-accent transition-colors">
                                        {{ $runner->name }}
                                    </a>
                                </td>
                                <td class="py-2.5 px-4 text-center font-mono text-ink-2">{{ $runner->bib_number }}</td>
                                <td class="py-2.5 px-4 text-right">
                                    <span class="inline-block px-2.5 py-0.5 rounded font-mono font-semibold {{ $runner->loop_records_count > 0 ? 'bg-accent/15 text-accent' : 'text-faint' }}">
                                        {{ $runner->loop_records_count }} <span class="text-[11px] font-sans font-normal text-muted">loop</span>
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-right font-mono text-muted">
                                    {{ $runner->latest_scanned_at ? \Carbon\Carbon::parse($runner->latest_scanned_at)->format('H:i:s') : '—' }}
                                </td>
                                <td class="py-2.5 px-4 text-right font-sans">
                                    <a href="{{ route('admin.participants.loops', $runner) }}" class="inline-block px-2 py-1 rounded text-xs text-ink-2 hover:text-ink bg-surface border border-line-soft hover:border-line transition-colors">
                                        Detail Loop
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-sm font-sans text-muted">Belum ada data loop peserta.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- View 2: Audit Log Scan Terakhir --}}
            <div id="view-logs" class="flex-1 overflow-x-auto hidden">
                <table class="w-full text-left text-xs font-mono">
                    <thead>
                        <tr class="text-muted border-b border-line bg-surface">
                            <th scope="col" class="py-2.5 px-4 font-medium">Waktu</th>
                            <th scope="col" class="py-2.5 px-4 font-medium font-sans">Peserta</th>
                            <th scope="col" class="py-2.5 px-4 font-medium text-center">BIB</th>
                            <th scope="col" class="py-2.5 px-4 font-medium">UID Kartu</th>
                            <th scope="col" class="py-2.5 px-4 font-medium font-sans">Status Verifikasi</th>
                            <th scope="col" class="py-2.5 px-4 font-medium text-right font-sans">Loop ke-</th>
                        </tr>
                    </thead>
                    <tbody id="admin-logs-tbody" class="divide-y divide-line-soft">
                        @forelse ($recentScans as $scan)
                            @php
                                [$label, $cls, $dot] = match ($scan['status']) {
                                    'accepted' => ['Diterima', 'text-ink-2', 'bg-emerald-500'],
                                    'ignored_duplicate' => ['Duplikat', 'text-amber-300', 'bg-amber-400'],
                                    default => ['Ditolak', 'text-rose-300', 'bg-rose-500'],
                                };
                            @endphp
                            <tr class="hover:bg-surface transition-colors">
                                <td class="py-2.5 px-4 text-ink-2 whitespace-nowrap">{{ $scan['scanned_at'] }}</td>
                                <td class="py-2.5 px-4 font-sans font-medium text-ink {{ $scan['status'] === 'rejected' ? 'text-muted' : '' }}">
                                    {{ $scan['participant_name'] }}
                                </td>
                                <td class="py-2.5 px-4 text-center text-ink-2">{{ $scan['bib_number'] }}</td>
                                <td class="py-2.5 px-4 text-muted">{{ $scan['rfid_uid'] }}</td>
                                <td class="py-2.5 px-4 whitespace-nowrap font-sans {{ $cls }}">
                                    <span class="inline-block w-1.5 h-1.5 rounded-full mr-1.5 align-middle {{ $dot }}"></span>{{ $label }}
                                </td>
                                <td class="py-2.5 px-4 text-right text-ink font-semibold">{{ $scan['resulting_loop_count'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-sm font-sans text-muted">Belum ada riwayat scan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Bilah Paginasi Interaktif Admin --}}
            <div class="mt-auto px-4 py-3 border-t border-line bg-surface/40 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <div id="admin-pagination-info" class="text-muted">
                    Menampilkan <span id="admin-pag-from" class="font-mono text-ink">{{ $topRunners->firstItem() ?? 0 }}</span>–<span id="admin-pag-to" class="font-mono text-ink">{{ $topRunners->lastItem() ?? 0 }}</span> dari <span id="admin-pag-total" class="font-mono text-ink">{{ $topRunners->total() }}</span> data
                </div>

                <div class="flex items-center space-x-1.5">
                    <button
                        type="button"
                        id="btn-admin-prev"
                        onclick="changeAdminPage(-1)"
                        disabled
                        class="px-2.5 py-1 rounded bg-raised border border-line text-ink-2 hover:text-ink disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >
                        ‹ Sebelumnya
                    </button>
                    <div id="admin-pagination-numbers" class="flex items-center space-x-1 font-mono"></div>
                    <button
                        type="button"
                        id="btn-admin-next"
                        onclick="changeAdminPage(1)"
                        class="px-2.5 py-1 rounded bg-raised border border-line text-ink-2 hover:text-ink disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >
                        Selanjutnya ›
                    </button>
                </div>
            </div>

        </div>

    </div>

    {{-- Konfigurasi Rute Lomba (GPX) --}}
    <section class="bg-surface/50 border border-line rounded-lg p-5">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-semibold text-ink">Rute Lomba (GPX Track)</h2>
                    @if ($raceRoute)
                        <span class="px-2 py-0.5 rounded bg-emerald-500/15 text-emerald-400 border border-emerald-500/20 text-[11px] font-mono">
                            {{ number_format($raceRoute->distance_m / 1000, 2, ',', '.') }} km
                        </span>
                    @endif
                </div>
                @if ($raceRoute)
                    <p class="text-xs text-ink-2 mt-1 font-medium">{{ $raceRoute->name ?: 'Rute Resmi' }}</p>
                    <p class="text-xs text-muted mt-0.5">Rute aktif dan otomatis tampil pada dashboard personal seluruh peserta.</p>
                @else
                    <p class="text-xs text-muted mt-1">Belum ada rute aktif. Unggah file GPX untuk mengaktifkan peta rute dan perhitungan jarak akurat bagi peserta.</p>
                @endif
            </div>

            <div class="flex flex-col gap-2 sm:items-end">
                <form method="POST" action="{{ route('admin.race-route.store') }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-2">
                    @csrf
                    <input
                        type="text"
                        name="route_name"
                        placeholder="Nama rute (opsional)"
                        class="bg-canvas border border-line focus:border-accent rounded-md px-3 py-1.5 text-xs text-ink placeholder:text-faint outline-none"
                    />
                    <input
                        type="file"
                        name="route_gpx"
                        accept=".gpx"
                        required
                        class="text-xs text-ink-2 file:mr-2 file:px-3 file:py-1.5 file:rounded-md file:border file:border-line file:bg-raised file:text-ink file:text-xs"
                    />
                    <button type="submit" class="px-3 py-1.5 rounded-md bg-accent hover:bg-accent-hover text-accent-ink text-xs font-semibold whitespace-nowrap transition-colors">
                        Simpan Rute
                    </button>
                </form>

                @if ($raceRoute)
                    <form method="POST" action="{{ route('admin.race-route.destroy') }}" onsubmit="return confirm('Hapus rute lomba ini?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-rose-300 hover:text-rose-200 underline underline-offset-2 transition-colors">
                            Hapus Rute
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </section>

    {{-- Zona Pemeliharaan: Reset Simulasi Aman --}}
    <div class="bg-surface/30 border border-line rounded-lg p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-sm font-semibold text-ink flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                Reset Data Simulasi Putaran
            </div>
            <p class="text-xs text-muted mt-0.5">
                Mengosongkan riwayat putaran (loop records) dan log scan kembali ke 0 untuk demo baru tanpa menghapus data master peserta.
            </p>
        </div>

        <form method="POST" action="{{ route('admin.reset-simulation') }}" onsubmit="return confirmResetDemo();" class="shrink-0">
            @csrf
            <input type="hidden" name="confirm_reset" id="confirm_reset_input" value="" />
            <button
                type="submit"
                class="px-3.5 py-1.5 rounded bg-raised hover:bg-rose-950/40 text-rose-300 hover:text-rose-200 border border-line hover:border-rose-900/60 text-xs font-medium transition-colors"
            >
                Reset Data Simulasi
            </button>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
    let adminActiveTab = 'leaderboard';
    let adminLeaderboardPage = 1;
    let adminLogsPage = 1;
    let adminSearchQuery = '';
    let adminSearchDebounceTimer = null;

    let adminPagination = {
        current_page: {{ $topRunners->currentPage() }},
        last_page: {{ $topRunners->lastPage() }},
        from: {{ $topRunners->firstItem() ?? 0 }},
        to: {{ $topRunners->lastItem() ?? 0 }},
        total: {{ $topRunners->total() }}
    };

    const tabBtnLeaderboard = document.getElementById('tab-btn-leaderboard');
    const tabBtnLogs = document.getElementById('tab-btn-logs');
    const tabCountParticipants = document.getElementById('tab-count-participants');
    const tabCountScans = document.getElementById('tab-count-scans');
    const viewLeaderboard = document.getElementById('view-leaderboard');
    const viewLogs = document.getElementById('view-logs');
    const leaderboardTbody = document.getElementById('admin-leaderboard-tbody');
    const logsTbody = document.getElementById('admin-logs-tbody');

    const adminTableSearch = document.getElementById('admin-table-search');
    const btnClearAdminSearch = document.getElementById('btn-clear-admin-search');

    const adminPagFrom = document.getElementById('admin-pag-from');
    const adminPagTo = document.getElementById('admin-pag-to');
    const adminPagTotal = document.getElementById('admin-pag-total');
    const btnAdminPrev = document.getElementById('btn-admin-prev');
    const btnAdminNext = document.getElementById('btn-admin-next');
    const adminPaginationNumbers = document.getElementById('admin-pagination-numbers');

    const statTotalParticipants = document.getElementById('stat-total-participants');
    const statTotalLoops = document.getElementById('stat-total-loops');
    const statAcceptedScans = document.getElementById('stat-accepted-scans');
    const statRejectedScans = document.getElementById('stat-rejected-scans');

    function switchAdminTab(tab) {
        adminActiveTab = tab;
        if (tab === 'leaderboard') {
            tabBtnLeaderboard.className = 'px-3 py-1.5 rounded-md text-xs font-semibold transition-colors bg-accent text-accent-ink';
            tabBtnLogs.className = 'px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-muted hover:text-ink hover:bg-raised';
            viewLeaderboard.classList.remove('hidden');
            viewLogs.classList.add('hidden');
            loadAdminLeaderboard(adminLeaderboardPage);
        } else {
            tabBtnLogs.className = 'px-3 py-1.5 rounded-md text-xs font-semibold transition-colors bg-accent text-accent-ink';
            tabBtnLeaderboard.className = 'px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-muted hover:text-ink hover:bg-raised';
            viewLogs.classList.remove('hidden');
            viewLeaderboard.classList.add('hidden');
            loadAdminLogs(adminLogsPage);
        }
    }

    adminTableSearch.addEventListener('input', (e) => {
        adminSearchQuery = e.target.value.trim();
        btnClearAdminSearch.classList.toggle('hidden', adminSearchQuery === '');

        clearTimeout(adminSearchDebounceTimer);
        adminSearchDebounceTimer = setTimeout(() => {
            if (adminActiveTab === 'leaderboard') {
                adminLeaderboardPage = 1;
                loadAdminLeaderboard(1);
            } else {
                adminLogsPage = 1;
                loadAdminLogs(1);
            }
        }, 250);
    });

    function clearAdminSearch() {
        adminTableSearch.value = '';
        adminSearchQuery = '';
        btnClearAdminSearch.classList.add('hidden');
        if (adminActiveTab === 'leaderboard') {
            loadAdminLeaderboard(1);
        } else {
            loadAdminLogs(1);
        }
    }

    function changeAdminPage(delta) {
        const nextPage = adminPagination.current_page + delta;
        if (nextPage < 1 || nextPage > adminPagination.last_page) return;

        if (adminActiveTab === 'leaderboard') {
            adminLeaderboardPage = nextPage;
            loadAdminLeaderboard(nextPage);
        } else {
            adminLogsPage = nextPage;
            loadAdminLogs(nextPage);
        }
    }

    function goToAdminPage(page) {
        if (page === adminPagination.current_page) return;
        if (adminActiveTab === 'leaderboard') {
            adminLeaderboardPage = page;
            loadAdminLeaderboard(page);
        } else {
            adminLogsPage = page;
            loadAdminLogs(page);
        }
    }

    function escapeHtml(v) {
        return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    async function loadAdminLeaderboard(page = 1) {
        try {
            const url = new URL('/api/dashboard/participants', window.location.origin);
            url.searchParams.set('page', page);
            url.searchParams.set('per_page', 10);
            if (adminSearchQuery) url.searchParams.set('search', adminSearchQuery);

            const res = await fetch(url);
            const json = await res.json();
            if (json.success) {
                renderAdminLeaderboard(json.data, json.pagination);
                updateAdminPaginationUI(json.pagination);
                if (tabCountParticipants && !adminSearchQuery) tabCountParticipants.textContent = json.pagination.total;
            }
        } catch (e) {
            console.error('Error fetching admin leaderboard:', e);
        }
    }

    async function loadAdminLogs(page = 1) {
        try {
            const url = new URL('/api/dashboard/scans', window.location.origin);
            url.searchParams.set('page', page);
            url.searchParams.set('per_page', 10);
            if (adminSearchQuery) url.searchParams.set('search', adminSearchQuery);

            const res = await fetch(url);
            const json = await res.json();
            if (json.success) {
                renderAdminLogs(json.data, json.pagination);
                updateAdminPaginationUI(json.pagination);
                if (tabCountScans && !adminSearchQuery) tabCountScans.textContent = json.pagination.total;
            }
        } catch (e) {
            console.error('Error fetching admin scans:', e);
        }
    }

    function renderAdminLeaderboard(items, pagination) {
        if (!items.length) {
            leaderboardTbody.innerHTML = '<tr><td colspan="6" class="py-12 text-center text-sm font-sans text-muted">Tidak ada data peserta ditemukan.</td></tr>';
            return;
        }

        const startIdx = (pagination.from || 1);
        leaderboardTbody.innerHTML = items.map((p, idx) => {
            const loopBadgeCls = p.total_loops > 0 ? 'bg-accent/15 text-accent' : 'text-faint';
            return `
                <tr class="hover:bg-surface transition-colors">
                    <td class="py-2.5 px-4 text-center text-muted font-mono font-medium">${startIdx + idx}</td>
                    <td class="py-2.5 px-4 font-sans font-medium text-ink">
                        <a href="/admin/participants/${p.id}/loops" class="hover:text-accent transition-colors">
                            ${escapeHtml(p.name)}
                        </a>
                    </td>
                    <td class="py-2.5 px-4 text-center font-mono text-ink-2">${escapeHtml(p.bib_number)}</td>
                    <td class="py-2.5 px-4 text-right">
                        <span class="inline-block px-2.5 py-0.5 rounded font-mono font-semibold ${loopBadgeCls}">
                            ${p.total_loops} <span class="text-[11px] font-sans font-normal text-muted">loop</span>
                        </span>
                    </td>
                    <td class="py-2.5 px-4 text-right font-mono text-muted">${escapeHtml(p.last_scanned_at || '—')}</td>
                    <td class="py-2.5 px-4 text-right font-sans">
                        <a href="/admin/participants/${p.id}/loops" class="inline-block px-2 py-1 rounded text-xs text-ink-2 hover:text-ink bg-surface border border-line-soft hover:border-line transition-colors">
                            Detail Loop
                        </a>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderAdminLogs(items, pagination) {
        if (!items.length) {
            logsTbody.innerHTML = '<tr><td colspan="6" class="py-12 text-center text-sm font-sans text-muted">Belum ada catatan aktivitas scan.</td></tr>';
            return;
        }

        logsTbody.innerHTML = items.map(s => {
            let label = 'Ditolak', cls = 'text-rose-300', dot = 'bg-rose-500';
            if (s.status === 'accepted') { label = 'Diterima'; cls = 'text-ink-2'; dot = 'bg-emerald-500'; }
            else if (s.status === 'ignored_duplicate') { label = 'Duplikat'; cls = 'text-amber-300'; dot = 'bg-amber-400'; }

            return `
                <tr class="hover:bg-surface transition-colors">
                    <td class="py-2.5 px-4 text-ink-2 whitespace-nowrap">${escapeHtml(s.scanned_at)}</td>
                    <td class="py-2.5 px-4 font-sans font-medium text-ink ${s.status === 'rejected' ? 'text-muted' : ''}">
                        ${escapeHtml(s.participant_name)}
                    </td>
                    <td class="py-2.5 px-4 text-center text-ink-2">${escapeHtml(s.bib_number)}</td>
                    <td class="py-2.5 px-4 text-muted">${escapeHtml(s.rfid_uid)}</td>
                    <td class="py-2.5 px-4 whitespace-nowrap font-sans ${cls}">
                        <span class="inline-block w-1.5 h-1.5 rounded-full mr-1.5 align-middle ${dot}"></span>${label}
                    </td>
                    <td class="py-2.5 px-4 text-right text-ink font-semibold">${escapeHtml(s.resulting_loop_count ?? '—')}</td>
                </tr>
            `;
        }).join('');
    }

    function updateAdminPaginationUI(pagination) {
        adminPagination = pagination;
        adminPagFrom.textContent = pagination.from || 0;
        adminPagTo.textContent = pagination.to || 0;
        adminPagTotal.textContent = pagination.total || 0;

        btnAdminPrev.disabled = pagination.current_page <= 1;
        btnAdminNext.disabled = pagination.current_page >= pagination.last_page || pagination.last_page === 0;

        const totalPages = pagination.last_page;
        const current = pagination.current_page;
        let buttonsHtml = '';

        if (totalPages <= 6) {
            for (let i = 1; i <= totalPages; i++) {
                buttonsHtml += renderAdminPageButton(i, i === current);
            }
        } else {
            buttonsHtml += renderAdminPageButton(1, 1 === current);
            let start = Math.max(2, current - 1);
            let end = Math.min(totalPages - 1, current + 1);

            if (start > 2) buttonsHtml += '<span class="px-1 text-faint">…</span>';
            for (let i = start; i <= end; i++) {
                buttonsHtml += renderAdminPageButton(i, i === current);
            }
            if (end < totalPages - 1) buttonsHtml += '<span class="px-1 text-faint">…</span>';
            buttonsHtml += renderAdminPageButton(totalPages, totalPages === current);
        }

        adminPaginationNumbers.innerHTML = buttonsHtml;
    }

    function renderAdminPageButton(pageNum, isActive) {
        if (isActive) {
            return `<span class="px-2.5 py-1 rounded bg-accent text-accent-ink font-semibold text-xs">${pageNum}</span>`;
        }
        return `<button type="button" onclick="goToAdminPage(${pageNum})" class="px-2.5 py-1 rounded bg-raised hover:bg-line text-ink-2 hover:text-ink text-xs transition-colors">${pageNum}</button>`;
    }

    async function refreshAdminData() {
        const icon = document.getElementById('refresh-icon');
        if (icon) icon.classList.add('animate-spin');

        try {
            const sumRes = await fetch('/api/dashboard/summary');
            const sumJson = await sumRes.json();
            if (sumJson.success && sumJson.data) {
                const d = sumJson.data;
                if (statTotalParticipants) statTotalParticipants.textContent = d.total_participants;
                if (statTotalLoops) statTotalLoops.textContent = d.total_loops;
                if (statAcceptedScans) statAcceptedScans.textContent = d.accepted_scans;
                if (statRejectedScans) statRejectedScans.textContent = d.rejected_scans;
                if (tabCountParticipants && !adminSearchQuery) tabCountParticipants.textContent = d.total_participants;
                if (tabCountScans && !adminSearchQuery) tabCountScans.textContent = d.total_scans;
            }

            if (adminActiveTab === 'leaderboard') {
                await loadAdminLeaderboard(adminLeaderboardPage);
            } else {
                await loadAdminLogs(adminLogsPage);
            }
        } catch (e) {
            console.error('Refresh admin error:', e);
        } finally {
            if (icon) setTimeout(() => icon.classList.remove('animate-spin'), 300);
        }
    }

    // Auto-refresh data setiap 15 detik untuk admin dashboard
    setInterval(refreshAdminData, 15000);

    // Inisialisasi tampilan nomor paginasi awal
    updateAdminPaginationUI(adminPagination);

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
