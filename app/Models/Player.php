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

    public function grantFrom(array $pool): ?array
    {
        if (empty($pool)) {
            return null;
        }

        $key = $pool[array_rand($pool)];
        $row = $this->items()->firstOrNew(['item' => $key]);

        $isNew = ! $row->exists;

        if ($isNew) {
            $row->level = 0;
            $row->shards = 0;
        } else {
            $row->shards += 1;
        }

        $row->save();

        return [
            'item' => $key,
            'name' => config('equipment.items.' . $key . '.name', $key),
            'is_new' => $isNew,
        ];
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
}