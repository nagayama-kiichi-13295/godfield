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
}