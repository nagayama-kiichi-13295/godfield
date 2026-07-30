<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    protected $fillable = ['token', 'name', 'character'];

    public function characters(): HasMany
    {
        return $this->hasMany(PlayerCharacter::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(SoloRun::class);
    }

    public function statsFor(string $key): PlayerCharacter
    {
        return $this->characters()->firstOrCreate(['character' => $key]);
    }

    public function currentStats(): PlayerCharacter
    {
        $key = $this->character ?: array_key_first(config('characters'));

        return $this->statsFor($key);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PlayerItem::class);
    }

    public function ownedItems(): array
    {
        return $this->items()->pluck('item')->all();
    }

    public function grantFrom(array $pool): ?string
    {
        $owned = $this->ownedItems();
        $candidates = array_values(array_diff($pool, $owned));

        if (empty($candidates)) {
            return null;
        }

        $key = $candidates[array_rand($candidates)];

        $this->items()->create(['item' => $key]);

        return $key;
    }

    public function missStats(): HasMany
    {
        return $this->hasMany(MissStat::class);
    }

    public function recordMisses(array $map): void
    {
        foreach ($map as $kana => $count) {
            $kana = mb_substr((string) $kana, 0, 4);
            $count = (int) $count;

            if ($kana === '' || $count < 1 || $count > 9999) {
                continue;
            }

            $row = $this->missStats()->firstOrCreate(['kana' => $kana], ['count' => 0]);
            $row->increment('count', $count);
        }
    }

    public function weakKana(int $limit = 5): array
    {
        return $this->missStats()
            ->orderByDesc('count')
            ->take($limit)
            ->get()
            ->map(fn ($m) => ['kana' => $m->kana, 'count' => $m->count])
            ->all();
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(TrainingRun::class);
    }

    public function endlessRuns(): HasMany
    {
        return $this->hasMany(EndlessRun::class);
    }

    public function endlessBest(string $mode): int
    {
        return (int) $this->endlessRuns()->where('mode', $mode)->max('defeated');
    }

    public function endlessSummary(): array
    {
        $rows = $this->endlessRuns()
            ->where('finished', true)
            ->get()
            ->groupBy('mode');

        $out = [];

        foreach (config('endless') as $key => $conf) {
            $g = $rows->get($key);

            $out[$key] = [
                'label' => $conf['label'],
                'color' => $conf['color'],
                'unit' => $conf['mode'] === 'depth' ? '階' : '体',
                'best' => (int) ($g?->max('defeated') ?? 0),
                'plays' => (int) ($g?->count() ?? 0),
                'total_kills' => (int) ($g?->sum('defeated') ?? 0),
                'exp' => (int) ($g?->sum('exp_gained') ?? 0),
                'recent' => $g
                    ? $g->sortByDesc('id')->take(5)->map(fn ($r) => [
                        'id' => $r->id,
                        'defeated' => $r->defeated,
                        'accuracy' => $r->accuracy(),
                        'exp' => $r->exp_gained,
                        'date' => $r->created_at->format('m/d H:i'),
                    ])->values()->all()
                    : [],
            ];
        }

        return $out;
    }
}