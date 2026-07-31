<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerItem extends Model
{
    protected $fillable = ['player_id', 'item', 'level', 'shards'];

    public function config(): ?array
    {
        return config('equipment.items.' . $this->item);
    }

    public function name(): string
    {
        $c = $this->config();
        $base = $c['name'] ?? $this->item;

        return $this->level > 0 ? "{$base} +{$this->level}" : $base;
    }

    public function nextCost(): ?int
    {
        $max = config('equipment.max_level');

        if ($this->level >= $max) {
            return null;
        }

        return config('equipment.cost.' . ($this->level + 1), 1);
    }

    public function canUpgrade(): bool
    {
        $cost = $this->nextCost();

        return $cost !== null && $this->shards >= $cost;
    }

    public function upgrade(): bool
    {
        if (! $this->canUpgrade()) {
            return false;
        }

        $this->shards -= $this->nextCost();
        $this->level += 1;
        $this->save();

        return true;
    }

    public function bonus(): array
    {
        $c = $this->config();

        if (! $c) {
            return ['hp' => 0, 'power' => 0, 'int' => 0];
        }

        $mult = 1 + config('equipment.gain_rate') * $this->level;

        return [
            'hp' => $c['hp'] > 0 ? (int) round($c['hp'] * $mult) : $c['hp'],
            'power' => $c['power'] > 0 ? (int) round($c['power'] * $mult) : $c['power'],
            'int' => $c['int'] > 0 ? (int) round($c['int'] * $mult) : $c['int'],
        ];
    }
}