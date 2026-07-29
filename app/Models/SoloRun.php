<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoloRun extends Model
{
    protected $fillable = ['player_id', 'character', 'stage', 'cleared', 'exp_gained', 'finished'];

    protected $casts = ['finished' => 'boolean'];

    public function stageConfig(): array
    {
        return config('stages.' . $this->stage) ?? config('stages.training');
    }
}
