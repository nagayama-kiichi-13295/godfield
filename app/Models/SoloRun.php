<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoloRun extends Model
{
    protected $fillable = [
        'player_id',
        'character',
        'stage',
        'cleared',
        'exp_gained',
        'finished',
        'is_cleared',
        'level_before',
        'level_after',
        'bonus_exp',
        'drop_item',
        'max_combo',
        'typed_chars',
        'miss_count',
        'duration_ms',
        'miss_map',
    ];

    protected $casts = [
        'finished' => 'boolean',
        'is_cleared' => 'boolean',
        'miss_map' => 'array',
    ];

    public function stageConfig(): array
    {
        return config('stages.' . $this->stage) ?? config('stages.training');
    }

    public function totalEnemies(): int
    {
        $conf = $this->stageConfig();

        return count($conf['enemies']) + (empty($conf['boss']) ? 0 : 1);
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
