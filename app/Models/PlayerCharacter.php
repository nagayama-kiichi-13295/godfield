<?php

namespace App\Models;

use App\Support\Stats;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerCharacter extends Model
{
    protected $fillable = [
        'player_id', 'character', 'level', 'exp',
        'points', 'pt_hp', 'pt_power', 'pt_int',
        'wins', 'losses',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function pointMap(): array
    {
        return [
            'hp' => $this->pt_hp,
            'power' => $this->pt_power,
            'int' => $this->pt_int,
        ];
    }

    public function stats(): array
    {
        return Stats::of($this->character, $this->level, $this->pointMap());
    }

    public function name(): string
    {
        return $this->stats()['name'];
    }

    public function maxHp(): int
    {
        return $this->stats()['max_hp'];
    }

    public function power(): int
    {
        return $this->stats()['power'];
    }

    public function requiredExp(): int
    {
        return $this->level * 100;
    }

    public function expRatio(): float
    {
        return min(100, $this->exp / max(1, $this->requiredExp()) * 100);
    }

    public function allocate(string $stat, int $amount = 1): bool
    {
        $column = 'pt_' . $stat;

        if (! array_key_exists($stat, config('growth.point_value'))) {
            return false;
        }

        if ($amount > 0 && $this->points < $amount) {
            return false;
        }

        if ($amount < 0 && $this->{$column} < abs($amount)) {
            return false;
        }

        $this->{$column} += $amount;
        $this->points -= $amount;
        $this->save();

        return true;
    }

    public function resetPoints(): void
    {
        $this->points = $this->pt_hp + $this->pt_power + $this->pt_int + $this->points;
        $this->pt_hp = 0;
        $this->pt_power = 0;
        $this->pt_int = 0;
        $this->save();
    }

    public function addExp(int $amount): int
    {
        $gained = 0;
        $this->exp += $amount;

        while ($this->exp >= $this->requiredExp() && $this->level < 99) {
            $this->exp -= $this->requiredExp();
            $this->level++;
            $this->points += config('growth.points_per_level');
            $gained++;
        }

        $this->save();

        return $gained;
    }
}