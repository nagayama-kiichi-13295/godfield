<?php

namespace App\Support;

class WordList
{
    public static function pool(?array $sets = null): array
    {
        $conf = config('words.sets', []);
        $keys = $sets ?: array_keys($conf);

        $out = [];

        foreach ($keys as $k) {
            foreach ($conf[$k] ?? [] as $w) {
                $out[] = $w;
            }
        }

        return $out;
    }

    private static function format(array $words): array
    {
        return array_map(fn ($w) => ['d' => $w[0], 'k' => $w[1]], $words);
    }

    public static function random(int $count = 60, ?array $sets = null): array
    {
        $words = self::pool($sets);
        shuffle($words);

        return self::format(array_slice($words, 0, $count));
    }

    public static function forMatch(string $matchId, int $count = 60, ?array $sets = null): array
    {
        $words = self::pool($sets);
        $seed = crc32($matchId);

        for ($i = count($words) - 1; $i > 0; $i--) {
            $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
            $j = $seed % ($i + 1);
            [$words[$i], $words[$j]] = [$words[$j], $words[$i]];
        }

        return self::format(array_slice($words, 0, $count));
    }

    public static function forKana(array $kanaList, int $count = 30, ?array $sets = null): array
    {
        $matched = [];
        $rest = [];

        foreach (self::pool($sets) as $w) {
            $hit = false;

            foreach ($kanaList as $k) {
                if ($k !== '' && mb_strpos($w[1], $k) !== false) {
                    $hit = true;
                    break;
                }
            }

            $hit ? $matched[] = $w : $rest[] = $w;
        }

        shuffle($matched);
        shuffle($rest);

        $picked = array_slice(array_merge($matched, $rest), 0, $count);

        return self::format($picked ?: array_slice(self::pool($sets), 0, $count));
    }
}