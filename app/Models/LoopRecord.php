<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoopRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'participant_id',
        'rfid_uid',
        'loop_number',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'rfid_uid' => 'string',
            'loop_number' => 'integer',
            'scanned_at' => 'datetime',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
