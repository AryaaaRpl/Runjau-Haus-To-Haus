<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rute lomba resmi (satu yang aktif). Diunggah admin sebagai GPX,
     * ditampilkan utuh di dashboard semua peserta.
     */
    public function up(): void
    {
        Schema::create('race_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedInteger('distance_m');
            $table->json('route');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_routes');
    }
};
