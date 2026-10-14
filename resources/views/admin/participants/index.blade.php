@extends('layouts.app')

@section('title', 'Manajemen Peserta')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-line">
        <div>
            <h1 class="text-base font-semibold text-ink">Daftar Peserta Lari</h1>
            <p class="text-xs text-muted">Data pelari, nomor BIB, dan alokasi kartu RFID</p>
        </div>
        <a href="{{ route('admin.participants.create') }}" class="px-3 py-1.5 rounded bg-accent hover:bg-accent-hover text-accent-ink font-semibold text-xs transition-colors self-start sm:self-auto">
            + Tambah Peserta
        </a>
    </div>

    <!-- Search Tool -->
    <div>
        <form method="GET" action="{{ route('admin.participants.index') }}" class="flex flex-col sm:flex-row items-center gap-2">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Cari nama pelari, nomor BIB, atau UID RFID..."
                class="w-full bg-canvas border border-line focus:border-accent rounded-md px-3 py-2 text-sm text-ink placeholder:text-faint outline-none transition-colors"
            />
            <div class="flex items-center space-x-2 w-full sm:w-auto">
                <button
                    type="submit"
                    class="px-3 py-1.5 rounded bg-raised hover:bg-line text-ink border border-line text-xs font-medium transition-colors"
                >
                    Cari
                </button>
                @if ($search)
                    <a
                        href="{{ route('admin.participants.index') }}"
                        class="px-3 py-1.5 rounded bg-canvas text-muted hover:text-ink border border-line text-xs transition-colors"
                    >
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Participants Table -->
    <div class="min-w-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-mono">
                <thead>
                    <tr class="text-muted border-b border-line">
                        <th class="py-2.5 pr-4 font-medium">BIB</th>
                        <th class="py-2.5 pr-4 font-medium font-sans">Nama Pelari</th>
                        <th class="py-2.5 pr-4 font-medium">UID Kartu</th>
                        <th class="py-2.5 pr-4 font-medium text-center">Total Loop</th>
                        <th class="py-2.5 pr-4 font-medium text-right font-sans">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line-soft border-b border-line-soft">
                    @forelse ($participants as $participant)
                        <tr class="">
                            <td class="py-2.5 pr-4 font-mono text-ink-2">
                                {{ $participant->bib_number }}
                            </td>
                            <td class="py-2.5 pr-4 font-sans font-medium text-ink">
                                <a href="{{ route('admin.participants.loops', $participant) }}" class="hover:text-accent transition-colors">
                                    {{ $participant->name }}
                                </a>
                            </td>
                            <td class="py-2.5 pr-4 text-ink-2">
                                @if ($participant->rfid_uid)
                                    <span>{{ $participant->rfid_uid }}</span>
                                @else
                                    <span class="text-faint italic">Belum terpasang</span>
                                @endif
                            </td>
                            <td class="py-2.5 pr-4 text-center text-ink font-medium tabular-nums">
                                {{ $participant->loop_records_count }}
                            </td>
                            <td class="py-2.5 pr-4 text-right font-sans space-x-1.5">
                                <a href="{{ route('admin.participants.loops', $participant) }}" class="inline-block px-2 py-1 rounded text-xs text-ink-2 hover:text-ink bg-canvas border border-line ">
                                    Riwayat
                                </a>
                                <a href="{{ route('admin.participants.edit', $participant) }}" class="inline-block px-2 py-1 rounded text-xs text-ink-2 hover:text-ink border border-line hover:bg-raised ">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('admin.participants.destroy', $participant) }}" class="inline" onsubmit="return confirm('Hapus peserta {{ $participant->name }} beserta seluruh riwayat loop?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 rounded text-xs text-rose-400 hover:text-rose-300 hover:bg-rose-950/20 transition-colors">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-muted font-sans">
                                @if ($search)
                                    Tidak ada peserta yang cocok dengan kata kunci "{{ $search }}".
                                @else
                                    Belum ada peserta terdaftar dalam sistem.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($participants->hasPages())
            <div class="p-3 border-t border-line">
                {{ $participants->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
