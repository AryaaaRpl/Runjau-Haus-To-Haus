@extends('layouts.app')

@section('title', 'Login Administrator')

@section('content')
<div class="max-w-sm mx-auto my-12">
    <div class="bg-surface border border-line rounded-lg p-6">
        <div class="mb-5">
            <img src="{{ asset('images/brand/runjau.png') }}" alt="Runjau Haus To Haus" class="h-12 w-auto mb-4" width="110" height="48">
            <span class="text-xs text-muted block mb-1">Akses panitia</span>
            <h1 class="text-xl font-medium text-ink tracking-tight">Login Administrator</h1>
            <p class="text-xs text-muted mt-1">Konsol panitia Runjau Haus To Haus</p>
        </div>

        <form method="POST" action="{{ route('login.post') }}" class="space-y-4" onsubmit="const btn = this.querySelector('button[type=submit]'); btn.disabled = true; btn.classList.add('opacity-50', 'cursor-not-allowed'); btn.innerText = 'Memproses...';">
            @csrf

            <div>
                <label for="email" class="block text-xs font-medium text-ink-2 mb-1.5">
                    Email
                </label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email', 'admin@runningevent.test') }}"
                    required
                    autofocus
                    class="w-full bg-canvas border border-line focus:border-accent rounded-md px-3 py-2 text-sm text-ink placeholder:text-faint outline-none transition-colors"
                />
            </div>

            <div>
                <label for="password" class="block text-xs font-medium text-ink-2 mb-1.5">
                    Password
                </label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    required
                    value="password123"
                    class="w-full bg-canvas border border-line focus:border-accent rounded-md px-3 py-2 text-sm text-ink placeholder:text-faint outline-none transition-colors"
                />
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center space-x-2 text-xs text-muted cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded bg-canvas border-line text-accent focus:ring-0">
                    <span>Ingat sesi ini</span>
                </label>
            </div>

            <button
                type="submit"
                class="w-full mt-2 bg-raised hover:bg-line text-ink font-medium py-2 pr-3 rounded-md border border-line transition-colors text-xs flex items-center justify-center space-x-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span>Masuk ke Dashboard</span>
            </button>
        </form>

        <div class="mt-5 pt-3.5 border-t border-line-soft text-xs text-faint">
            Kredensial lokal: <span class="text-ink-2 font-mono">admin@runningevent.test</span> / <span class="text-ink-2 font-mono">password123</span>
        </div>
    </div>
</div>
@endsection
