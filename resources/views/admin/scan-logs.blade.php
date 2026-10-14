@extends('layouts.app')

@section('title', 'Log Pemindaian RFID')

@section('content')
<div class="space-y-6">

    <!-- Header & Filter Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-line">
        <div>
            <h1 class="text-base font-semibold text-ink">Log Audit Pemindaian RFID</h1>
            <p class="text-xs text-muted">Riwayat lengkap seluruh event scan fisik maupun manual yang diterima server</p>
        </div>
        <div class="flex items-center space-x-1.5 self-start sm:self-auto">
            <a href="{{ route('admin.scan-logs') }}" class="px-2.5 py-1 rounded text-xs {{ empty($status) ? 'bg-raised text-ink border border-line' : 'text-muted hover:text-ink border border-transparent' }} transition-colors">
                Semua
            </a>
            <a href="{{ route('admin.scan-logs', ['status' => 'accepted']) }}" class="px-2.5 py-1 rounded text-xs {{ $status === 'accepted' ? 'bg-raised text-ink border border-line' : 'text-muted hover:text-ink border border-transparent' }} transition-colors">
                Accepted
            </a>
            <a href="{{ route('admin.scan-logs', ['status' => 'rejected']) }}" class="px-2.5 py-1 rounded text-xs {{ $status === 'rejected' ? 'bg-rose-500 text-ink font-semibold' : 'bg-raised text-rose-400 hover:text-rose-300 border border-line' }} transition-colors">
                Rejected
            </a>
            <a href="{{ route('admin.scan-logs', ['status' => 'ignored_duplicate']) }}" class="px-2.5 py-1 rounded text-xs {{ $status === 'ignored_duplicate' ? 'bg-raised text-ink border border-line' : 'text-muted hover:text-ink border border-transparent' }} transition-colors">
                Double-Tap
            </a>
        </div>
    </div>

    <!-- Audit Log Table -->
    <div class="min-w-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-mono">
                <thead>
                    <tr class="text-muted border-b border-line">
                        <th class="py-2.5 pr-4 font-medium">Waktu (Server)</th>
                        <th class="py-2.5 pr-4 font-medium">UID Terbaca</th>
                        <th class="py-2.5 pr-4 font-medium font-sans">Pelari Teridentifikasi</th>
                        <th class="py-2.5 pr-4 font-medium text-center">BIB</th>
                        <th class="py-2.5 pr-4 font-medium">Status</th>
                        <th class="py-2.5 pr-4 font-medium font-sans">Catatan Diagnostik</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line-soft border-b border-line-soft">
                    @forelse ($logs as $log)
                        <tr class="">
                            <td class="py-2.5 pr-4 text-ink-2 tabular-nums">
                                {{ $log->scanned_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="py-2.5 pr-4 text-ink-2">
                                {{ $log->rfid_uid }}
                            </td>
                            <td class="py-2.5 pr-4 font-sans font-medium text-ink">
                                @if ($log->participant)
                                    <a href="{{ route('admin.participants.loops', $log->participant) }}" class="hover:text-accent transition-colors">
                                        {{ $log->participant->name }}
                                    </a>
                                @else
                                    <span class="text-faint italic">Tidak terdaftar</span>
                                @endif
                            </td>
                            <td class="py-2.5 pr-4 text-right text-ink-2">
                                {{ $log->participant ? $log->participant->bib_number : '-' }}
                            </td>
                            <td class="py-2.5 pr-4">
                                @if ($log->status === 'accepted')
                                    <span class="text-ink-2"><span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 align-middle"></span>Diterima</span>
                                @elseif ($log->status === 'ignored_duplicate')
                                    <span class="text-amber-300"><span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-400 mr-1.5 align-middle"></span>Duplikat</span>
                                @else
                                    <span class="text-rose-300"><span class="inline-block w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5 align-middle"></span>Ditolak</span>
                                @endif
                            </td>
                            <td class="py-2.5 pr-4 text-muted font-sans">
                                {{ $log->message ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-muted font-sans">
                                Tidak ada log scan yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="p-3 border-t border-line">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
