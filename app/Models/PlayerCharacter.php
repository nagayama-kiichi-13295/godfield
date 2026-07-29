<?php

namespace App\Models;

use App\Support\Stats;
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
        return Stats::of($this->character, $this->level)['name'];
    }

    public function maxHp(): int
    {
        return Stats::of($this->character, $this->level)['max_hp'];
    }

    public function power(): int
    {
        return Stats::of($this->character, $this->level)['power'];
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