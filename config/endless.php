<?php

return [
    'tower' => [
        'label' => '無限の塔',
        'color' => '#7b5ec7',
        'mode' => 'depth',
        'word_sets' => ['short', 'long', 'phrase'],
        'heal_between' => 0.10,
        'limit_per_floor' => 45,
        'names' => ['影の兵', '石の番人', '鉄面の獣', '呪われた騎士', '深層の主', '虚空の使徒'],
        'boss_every' => 5,
        'formula' => [
            'hp_base' => 45,   'hp_growth' => 1.16,
            'power_base' => 4, 'power_growth' => 1.085,
            'interval_base' => 4.6, 'interval_min' => 1.5, 'interval_decay' => 0.955,
            'exp_base' => 12,  'exp_growth' => 1.15,
        ],
        'boss_mult' => ['hp' => 2.4, 'power' => 1.35, 'exp' => 3.0, 'limit' => 1.6],
    ],

    'rush' => [
        'label' => '討伐ラッシュ',
        'color' => '#3da58a',
        'mode' => 'timed',
        'word_sets' => ['short', 'long'],
        'total_time' => 180,
        'heal_per_kill' => 0.06,
        'names' => ['はぐれ狼', '盗賊', '大蝙蝠', '毒蜂', '岩トカゲ', '亡霊'],
        'formula' => [
            'hp_base' => 38,   'hp_growth' => 1.11,
            'power_base' => 5, 'power_growth' => 1.06,
            'interval_base' => 4.0, 'interval_min' => 1.8, 'interval_decay' => 0.97,
            'exp_base' => 10,  'exp_growth' => 1.10,
        ],
    ],
];