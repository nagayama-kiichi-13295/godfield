<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingRun extends Model
{
    protected $fillable = [
        'player_id', 'character', 'target_kana', 'words', 'weak_words',
        'typed_chars', 'miss_count', 'max_combo', 'duration_ms',
        'exp_gained', 'level_before', 'level_after', 'miss_map', 'finished',
    ];

    protected $casts = [
        'target_kana' => 'array',
        'miss_map' => 'array',
        'finished' => 'boolean',
    ];

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