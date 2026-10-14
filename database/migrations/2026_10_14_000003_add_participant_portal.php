<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Token acak untuk link/QR dashboard peserta (bukan ID berurutan, sesuai PRD).
        Schema::table('participants', function (Blueprint $table) {
            $table->string('access_token', 64)->nullable()->unique()->after('rfid_uid');
            $table->timestamp('access_token_rotated_at')->nullable()->after('access_token');
        });

        DB::table('participants')->whereNull('access_token')->orderBy('id')->each(function ($p) {
            DB::table('participants')->where('id', $p->id)->update(['access_token' => Str::random(40)]);
        });

        // Lari pribadi peserta dari file GPX yang mereka unggah sendiri.
        Schema::create('participant_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->unsignedInteger('distance_m');           // dihitung dari seluruh titik
            $table->unsignedInteger('duration_s')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->json('route');                            // titik yang sudah dipangkas ujungnya (privasi)
            $table->unsignedInteger('point_count');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_runs');
        Schema::table('participants', function (Blueprint $table) {
            $table->dropUnique(['access_token']);
            $table->dropColumn(['access_token', 'access_token_rotated_at']);
        });
    }
};
