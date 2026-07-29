<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerCharacter extends Model
{
    protected $fillable = ['player_id', 'character', 'level', 'exp', 'wins', 'losses'];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function config(): array
    {
        $all = config('characters');

        return $all[$this->character] ?? reset($all);
    }

    public function name(): string
    {
        return $this->config()['name'];
    }

    public function maxHp(): int
    {
        $c = $this->config();

        return (int) round($c['base_hp'] + $c['hp_growth'] * ($this->level - 1));
    }

    public function power(): int
    {
        $c = $this->config();

        return (int) round($c['base_power'] + $c['power_growth'] * ($this->level - 1));
    }

    public function requiredExp(): int
    {
        return $this->level * 100;
    }

    public function expRatio(): float
    {
        return min(100, $this->exp / max(1, $this->requiredExp()) * 100);
    }

    public function addExp(int $amount): int
    {
        $gained = 0;
        $this->exp += $amount;

        while ($this->exp >= $this->requiredExp() && $this->level < 99) {
            $this->exp -= $this->requiredExp();
            $this->level++;
            $gained++;
        }

        $this->save();

        return $gained;
    }
}