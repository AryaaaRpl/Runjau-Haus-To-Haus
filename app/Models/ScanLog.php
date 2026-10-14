<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'rfid_uid',
        'client_scan_id',
        'participant_id',
        'status',
        'message',
        'resulting_loop_count',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'rfid_uid' => 'string',
            'scanned_at' => 'datetime',
            'resulting_loop_count' => 'integer',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
