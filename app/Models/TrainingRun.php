<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingRun extends Model
{
    protected $fillable = [
        'player_id',
        'character',
        'target_kana',
        'word_list',
        'words',
        'weak_words',
        'typed_chars',
        'miss_count',
        'max_combo',
        'duration_ms',
        'exp_gained',
        'level_before',
        'level_after',
        'miss_map',
        'finished',
    ];

    protected $casts = [
        'target_kana' => 'array',
        'word_list' => 'array',
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

    public function weakCountFor(int $done): int
    {
        $list = $this->word_list ?? [];
        $targets = array_filter($this->target_kana ?? []);

        if (empty($list) || empty($targets)) {
            return 0;
        }

        $done = max(0, min($done, count($list)));
        $count = 0;

        for ($i = 0; $i < $done; $i++) {
            $kana = $list[$i]['k'] ?? '';

            foreach ($targets as $t) {
                if ($t !== '' && mb_strpos($kana, $t) !== false) {
                    $count++;
                    break;
                }
            }
        }

        return $count;
    }

    public function minCharsFor(int $done): int
    {
        $list = $this->word_list ?? [];
        $done = max(0, min($done, count($list)));
        $sum = 0;

        for ($i = 0; $i < $done; $i++) {
            $sum += mb_strlen($list[$i]['k'] ?? '');
        }

        return $sum;
    }
}
