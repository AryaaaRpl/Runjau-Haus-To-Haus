<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simpan jumlah loop peserta PADA SAAT scan terjadi, supaya log historis
     * tidak berubah ketika peserta menambah putaran berikutnya.
     */
    public function up(): void
    {
        Schema::table('scan_logs', function (Blueprint $table) {
            $table->unsignedInteger('resulting_loop_count')->nullable()->after('message');
            // ID unik dari browser operator; mencegah scan yang dikirim ulang tercatat dua kali.
            $table->string('client_scan_id', 64)->nullable()->unique()->after('rfid_uid');
        });

        // Isi data lama: jumlah loop peserta yang tercatat sampai waktu scan tersebut.
        DB::table('scan_logs')
            ->whereNotNull('participant_id')
            ->orderBy('id')
            ->each(function ($log) {
                $count = DB::table('loop_records')
                    ->where('participant_id', $log->participant_id)
                    ->where('scanned_at', '<=', $log->scanned_at)
                    ->count();

                DB::table('scan_logs')->where('id', $log->id)->update(['resulting_loop_count' => $count]);
            });
    }

    public function down(): void
    {
        Schema::table('scan_logs', function (Blueprint $table) {
            $table->dropUnique(['client_scan_id']);
            $table->dropColumn(['resulting_loop_count', 'client_scan_id']);
        });
    }
};
