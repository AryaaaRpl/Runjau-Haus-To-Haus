@extends('layouts.app')

@section('title', 'Daftarkan Peserta Baru')

@section('content')
<div class="max-w-xl mx-auto space-y-4">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-base font-semibold text-ink">Daftarkan Peserta Baru</h1>
            <p class="text-xs text-muted">Pendaftaran master data pelari</p>
        </div>
        <a href="{{ route('admin.participants.index') }}" class="text-xs text-muted hover:text-ink transition-colors">
            Kembali ke Daftar
        </a>
    </div>

    <div class="bg-surface border border-line rounded-lg p-5">
        <form method="POST" action="{{ route('admin.participants.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-xs font-medium text-ink-2 mb-1">
                    Nama Lengkap Pelari <span class="text-rose-400">*</span>
                </label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name') }}"
                    required
                    placeholder="Contoh: RIO MILLEN"
                    class="w-full bg-canvas border border-line focus:border-accent rounded-md px-3 py-2 text-sm text-ink placeholder:text-faint outline-none transition-colors"
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="bib_number" class="block text-xs font-medium text-ink-2 mb-1">
                        Nomor BIB <span class="text-rose-400">*</span>
                    </label>
                    <input
                        type="text"
                        name="bib_number"
                        id="bib_number"
                        value="{{ old('bib_number') }}"
                        required
                        placeholder="Contoh: 420"
                        class="w-full bg-canvas border border-line focus:border-accent rounded-md px-3 py-2 text-sm font-mono text-ink placeholder:text-faint outline-none transition-colors"
                    />
                    <p class="text-xs text-faint mt-1">Harus unik untuk setiap pelari.</p>
                </div>

                <div>
                    <label for="rfid_uid" class="block text-xs font-medium text-ink-2 mb-1">
                        UID Kartu RFID
                    </label>
                    <input
                        type="text"
                        name="rfid_uid"
                        id="rfid_uid"
                        value="{{ old('rfid_uid') }}"
                        placeholder="Contoh: 0007352925"
                        class="w-full bg-canvas border border-line focus:border-accent rounded-md px-3 py-2 text-sm font-mono text-ink placeholder:text-faint outline-none transition-colors"
                    />
                    <p class="text-xs text-faint mt-1">Leading zero tetap dipelihara.</p>
                </div>
            </div>

            <div class="pt-3 border-t border-line-soft flex items-center justify-end space-x-2">
                <a href="{{ route('admin.participants.index') }}" class="px-3 py-1.5 rounded text-xs text-ink-2 hover:text-ink bg-canvas border border-line transition-colors">
                    Batal
                </a>
                <button
                    type="submit"
                    class="px-3.5 py-1.5 rounded text-xs font-semibold bg-accent hover:bg-accent-hover text-accent-ink transition-colors"
                >
                    Simpan Peserta
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
