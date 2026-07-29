<?php

namespace App\Support;

class Stats
{
    public static function of(?string $key, int $level, array $pts = [], array $equip = []): array
    {
        $all = config('characters');
        $c = $all[$key] ?? reset($all);
        $v = config('growth.point_value');
        $items = config('equipment.items');

        $bonus = ['hp' => 0, 'power' => 0, 'int' => 0];
        $worn = [];

        foreach ($equip as $slot => $itemKey) {
            if (! $itemKey || ! isset($items[$itemKey])) {
                continue;
            }

            $it = $items[$itemKey];
            $bonus['hp'] += $it['hp'];
            $bonus['power'] += $it['power'];
            $bonus['int'] += $it['int'];
            $worn[$slot] = $it['name'];
        }

        $hp = (int) round($c['base_hp'] + $c['hp_growth'] * ($level - 1))
            + ($pts['hp'] ?? 0) * $v['hp'] + $bonus['hp'];

        $power = (int) round($c['base_power'] + $c['power_growth'] * ($level - 1))
            + ($pts['power'] ?? 0) * $v['power'] + $bonus['power'];

        $int = (int) round($c['base_int'] + $c['int_growth'] * ($level - 1))
            + ($pts['int'] ?? 0) * $v['int'] + $bonus['int'];

        return [
            'key' => $key,
            'name' => $c['name'],
            'color' => $c['color'],
            'icon' => $c['icon'],
            'max_hp' => max(1, $hp),
            'power' => max(1, $power),
            'int' => max(0, $int),
            'heal' => max(1, $int),
            'equip_bonus' => $bonus,
            'worn' => $worn,
        ];
    }
}
