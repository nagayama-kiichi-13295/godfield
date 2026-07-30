<?php

namespace App\Support;

class EndlessGen
{
    public static function enemy(string $key, int $index): array
    {
        $conf = config('endless.' . $key);
        $f = $conf['formula'];
        $n = max(0, $index);

        $isBoss = ! empty($conf['boss_every'])
            && ($index + 1) % $conf['boss_every'] === 0;

        $hp = $f['hp_base'] * pow($f['hp_growth'], $n);
        $power = $f['power_base'] * pow($f['power_growth'], $n);
        $exp = $f['exp_base'] * pow($f['exp_growth'], $n);

        $interval = max(
            $f['interval_min'],
            $f['interval_base'] * pow($f['interval_decay'], $n)
        );

        $limit = $conf['limit_per_floor'] ?? 0;

        if ($isBoss) {
            $m = $conf['boss_mult'];
            $hp *= $m['hp'];
            $power *= $m['power'];
            $exp *= $m['exp'];
            $limit = (int) round($limit * $m['limit']);
        }

        $names = $conf['names'];
        $name = $names[$n % count($names)];

        return [
            'name' => $isBoss ? "【{$name}・王】" : $name,
            'hp' => (int) round($hp),
            'power' => max(1, (int) round($power)),
            'interval' => round($interval, 2),
            'limit' => (int) $limit,
            'exp' => (int) round($exp),
            'is_boss' => $isBoss,
            'index' => $index,
        ];
    }

    public static function batch(string $key, int $from, int $count): array
    {
        $out = [];

        for ($i = 0; $i < $count; $i++) {
            $out[] = self::enemy($key, $from + $i);
        }

        return $out;
    }

    public static function totalExp(string $key, int $defeated): int
    {
        $sum = 0;

        for ($i = 0; $i < $defeated; $i++) {
            $sum += self::enemy($key, $i)['exp'];
        }

        return $sum;
    }
}