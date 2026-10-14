<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'bib_number',
        'rfid_uid',
    ];

    /**
     * Token tidak boleh ikut terserialisasi ke JSON/API secara tidak sengaja.
     */
    protected $hidden = ['access_token'];

    protected static function booted(): void
    {
        static::creating(function (Participant $participant) {
            $participant->access_token ??= static::newAccessToken();
        });
    }

    public static function newAccessToken(): string
    {
        return Str::random(40); // ~238 bit, tidak bisa ditebak
    }

    /**
     * Ganti token: link/QR lama langsung tidak berlaku (mis. kartu hilang atau link tersebar).
     */
    public function rotateAccessToken(): void
    {
        $this->forceFill([
            'access_token' => static::newAccessToken(),
            'access_token_rotated_at' => now(),
        ])->save();
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ParticipantRun::class)->latest('started_at')->latest('id');
    }

    protected function casts(): array
    {
        return [
            'bib_number' => 'string',
            'rfid_uid' => 'string',
            'access_token_rotated_at' => 'datetime',
        ];
    }

    public function loopRecords(): HasMany
    {
        return $this->hasMany(LoopRecord::class)->orderBy('loop_number', 'asc');
    }

    public function scanLogs(): HasMany
    {
        return $this->hasMany(ScanLog::class)->orderBy('scanned_at', 'desc');
    }

    public function getLoopCountAttribute(): int
    {
        return $this->loopRecords()->count();
    }
}
