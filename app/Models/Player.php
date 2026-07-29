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
}