<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RaceRoute extends Model
{
    protected $fillable = ['name', 'distance_m', 'route'];

    protected function casts(): array
    {
        return ['route' => 'array'];
    }

    /** Rute lomba yang sedang dipakai (yang terakhir diunggah). */
    public static function current(): ?self
    {
        return static::latest('id')->first();
    }
}
