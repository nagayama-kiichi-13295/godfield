<?php

namespace App\Support;

class WordList
{
    private const WORDS = [
        ['ねこ', 'neko'], ['いぬ', 'inu'], ['とり', 'tori'], ['さかな', 'sakana'],
        ['くるま', 'kuruma'], ['でんしゃ', 'densha'], ['ひこうき', 'hikouki'],
        ['じてんしゃ', 'jitensha'], ['やま', 'yama'], ['うみ', 'umi'],
        ['かわ', 'kawa'], ['そら', 'sora'], ['ほし', 'hoshi'], ['つき', 'tsuki'],
        ['たいよう', 'taiyou'], ['もり', 'mori'], ['はな', 'hana'], ['くさ', 'kusa'],
        ['りんご', 'ringo'], ['みかん', 'mikan'], ['ぶどう', 'budou'],
        ['ばなな', 'banana'], ['ごはん', 'gohan'], ['らーめん', 'raamen'],
        ['すし', 'sushi'], ['てんぷら', 'tempura'], ['おちゃ', 'ocha'],
        ['みず', 'mizu'], ['つくえ', 'tsukue'], ['まど', 'mado'],
        ['とびら', 'tobira'], ['かぎ', 'kagi'], ['とけい', 'tokei'],
        ['でんわ', 'denwa'], ['てがみ', 'tegami'], ['しんぶん', 'shinbun'],
        ['かばん', 'kaban'], ['くつ', 'kutsu'], ['ぼうし', 'boushi'],
        ['めがね', 'megane'],
    ];

    public static function forMatch(string $matchId, int $count = 20): array
    {
        $words = self::WORDS;
        $seed = crc32($matchId);

        for ($i = count($words) - 1; $i > 0; $i--) {
            $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
            $j = $seed % ($i + 1);
            [$words[$i], $words[$j]] = [$words[$j], $words[$i]];
        }

        return array_map(
            fn ($w) => ['d' => $w[0], 'r' => $w[1]],
            array_slice($words, 0, $count)
        );
    }
}