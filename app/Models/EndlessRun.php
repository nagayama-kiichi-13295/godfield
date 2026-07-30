<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EndlessRun extends Model
{
    protected $fillable = [
        'player_id', 'character', 'mode', 'defeated', 'exp_gained',
        'level_before', 'level_after', 'max_combo', 'typed_chars',
        'miss_count', 'duration_ms', 'miss_map', 'finished',
    ];

    protected $casts = [
        'miss_map' => 'array',
        'finished' => 'boolean',
    ];

    public function config(): array
    {
        return config('endless.' . $this->mode) ?? config('endless.tower');
    }

    public function accuracy(): float
    {
        $all = $this->typed_chars + $this->miss_count;

        return $all > 0 ? round($this->typed_chars / $all * 100, 1) : 0.0;
    }

    public function kps(): float
    {
        return $this->duration_ms > 0
            ? round($this->typed_chars / ($this->duration_ms / 1000), 2)
            : 0.0;
    }
}