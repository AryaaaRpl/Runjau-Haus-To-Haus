<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Runjau Haus To Haus') · Runjau Haus To Haus</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/runjau.png') }}">
    <meta name="theme-color" content="#121110">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|jetbrains-mono:400,500" rel="stylesheet" />

    {{-- Aset dibangun lokal dengan Vite (npm run build) supaya tetap jalan tanpa internet di lokasi lomba. --}}
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>body{font-family:system-ui;background:#121110;color:#f3f1ee}</style>
    @endif
    @stack('styles')
</head>
<body class="bg-canvas text-ink min-h-screen flex flex-col font-sans antialiased selection:bg-accent selection:text-accent-ink">

    @unless (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        <div style="background:#F25919;color:#1a0b03;padding:8px 16px;font-size:14px">
            Aset tampilan belum dibangun. Jalankan <code>npm install &amp;&amp; npm run build</code> di folder proyek, lalu muat ulang halaman.
        </div>
    @endunless

    <header class="border-b border-line">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-12 flex items-center justify-between gap-4">
            <a href="{{ View::hasSection('public') ? '#' : route('rfid.scan') }}" class="flex items-center gap-2 shrink-0">
                <img src="{{ asset('images/brand/runjau.png') }}" alt="Runjau" class="h-7 w-auto" width="64" height="28">
                <span class="text-sm font-medium tracking-tight text-accent border-l border-line pl-2" style="color: #F25919;">Haus To Haus</span>
                <span class="hidden sm:inline text-[11px] font-mono text-faint border border-line px-1.5 rounded">DEMO</span>
            </a>

            @unless (View::hasSection('public'))
            @php
                $navItems = [['rfid.scan', 'rfid.scan', 'Scan']];
                if (auth()->check()) {
                    $navItems[] = ['admin.dashboard', 'admin.dashboard', 'Dashboard'];
                    $navItems[] = ['admin.participants.index', 'admin.participants.*', 'Peserta'];
                    $navItems[] = ['admin.scan-logs', 'admin.scan-logs', 'Log'];
                }
            @endphp

            <nav class="flex items-center gap-1 overflow-x-auto text-[13px]">
                @foreach ($navItems as [$route, $pattern, $label])
                    @php $active = request()->routeIs($pattern); @endphp
                    <a href="{{ route($route) }}"
                       @if ($active) aria-current="page" @endif
                       class="relative px-2.5 h-12 flex items-center whitespace-nowrap transition-colors {{ $active ? 'text-ink' : 'text-muted hover:text-ink' }}">
                        {{ $label }}
                        @if ($active)
                            <span class="absolute left-2.5 right-2.5 bottom-0 h-0.5 bg-accent"></span>
                        @endif
                    </a>
                @endforeach

                <span class="w-px h-4 bg-line mx-1.5"></span>
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="px-2.5 h-12 text-muted hover:text-ink transition-colors whitespace-nowrap">Keluar</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="px-2.5 h-12 flex items-center text-muted hover:text-ink transition-colors whitespace-nowrap">Login admin</a>
                @endauth
            </nav>
            @endunless
        </div>
    </header>

    @if (session('success') || $errors->any())
        <div class="max-w-6xl mx-auto px-4 sm:px-6 mt-4 w-full space-y-2">
            @if (session('success'))
                <div role="status" class="flex items-start justify-between gap-3 border-l-2 border-emerald-500 bg-surface px-3 py-2 text-sm text-ink-2">
                    <span>{{ session('success') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-faint hover:text-ink text-xs" aria-label="Tutup pesan">Tutup</button>
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="border-l-2 border-rose-500 bg-surface px-3 py-2 text-sm">
                    <div class="text-rose-300 mb-1">Periksa kembali isian berikut:</div>
                    <ul class="list-disc list-inside space-y-0.5 text-ink-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 py-6">
        @yield('content')
    </main>

    <footer class="border-t border-line">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-faint">
            <div class="space-y-0.5">
                <div class="text-muted">Runjau Haus To Haus · lingkungan demo, data peserta fiktif</div>
                <div>Waktu tercatat = waktu server menerima scan (WIB), bukan timing otomatis</div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span>Dikembangkan oleh</span>
                <img src="{{ asset('images/brand/semico.png') }}" alt="Semico" class="h-4 w-auto opacity-80" width="83" height="16">
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
