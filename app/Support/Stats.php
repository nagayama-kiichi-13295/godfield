<?php

namespace App\Support;

class Stats
{
    public static function of(?string $key, int $level, array $pts = []): array
    {
        $all = config('characters');
        $c = $all[$key] ?? reset($all);
        $v = config('growth.point_value');

        $hp = (int) round($c['base_hp'] + $c['hp_growth'] * ($level - 1))
            + ($pts['hp'] ?? 0) * $v['hp'];

        $power = (int) round($c['base_power'] + $c['power_growth'] * ($level - 1))
            + ($pts['power'] ?? 0) * $v['power'];

        $int = (int) round($c['base_int'] + $c['int_growth'] * ($level - 1))
            + ($pts['int'] ?? 0) * $v['int'];

        return [
            'key' => $key,
            'name' => $c['name'],
            'color' => $c['color'],
            'icon' => $c['icon'],
            'max_hp' => $hp,
            'power' => $power,
            'int' => $int,
            'heal' => max(1, $int),
        ];
    }
}