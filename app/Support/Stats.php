<?php

namespace App\Support;

class Stats
{
    public static function of(?string $key, int $level): array
    {
        $all = config('characters');
        $c = $all[$key] ?? reset($all);

        return [
            'key' => $key,
            'name' => $c['name'],
            'color' => $c['color'],
            'icon' => $c['icon'],
            'max_hp' => (int) round($c['base_hp'] + $c['hp_growth'] * ($level - 1)),
            'power' => (int) round($c['base_power'] + $c['power_growth'] * ($level - 1)),
        ];
    }
}