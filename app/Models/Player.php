<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $fillable = ['token', 'name', 'character', 'level', 'exp', 'wins', 'losses'];

    public function config(): array
    {
        $all = config('characters');

        return $all[$this->character] ?? reset($all);
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