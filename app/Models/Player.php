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
}