<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('bib_number')->unique();
            $table->string('rfid_uid')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('loop_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->string('rfid_uid');
            $table->unsignedInteger('loop_number');
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->index(['participant_id', 'loop_number']);
            $table->index('scanned_at');
        });

        Schema::create('scan_logs', function (Blueprint $table) {
            $table->id();
            $table->string('rfid_uid');
            $table->foreignId('participant_id')->nullable()->constrained('participants')->nullOnDelete();
            $table->string('status'); // accepted, rejected, ignored_duplicate
            $table->string('message')->nullable();
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->index('scanned_at');
            $table->index('rfid_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_logs');
        Schema::dropIfExists('loop_records');
        Schema::dropIfExists('participants');
    }
};
