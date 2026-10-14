<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipantRun extends Model
{
    protected $fillable = [
        'participant_id',
        'name',
        'distance_m',
        'duration_s',
        'started_at',
        'route',
        'point_count',
    ];

    protected function casts(): array
    {
        return [
            'route' => 'array',
            'started_at' => 'datetime',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
