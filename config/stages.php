<?php

return [
    'training' => [
        'label' => '訓練場',
        'color' => '#7a8fa6',
        'clear_bonus' => 60,
        'heal_between' => 0.15,
        'enemies' => [
            ['name' => 'スライム', 'hp' => 45, 'power' => 4, 'interval' => 4.5, 'limit' => 40, 'exp' => 15],
            ['name' => 'コウモリ', 'hp' => 60, 'power' => 5, 'interval' => 4.0, 'limit' => 40, 'exp' => 20],
            ['name' => 'ゴブリン', 'hp' => 80, 'power' => 6, 'interval' => 3.8, 'limit' => 45, 'exp' => 30],
        ],
    ],
    'road' => [
        'label' => '修練の道',
        'color' => '#c98b3a',
        'clear_bonus' => 150,
        'heal_between' => 0.12,
        'boss' => 'ogre',
        'enemies' => [
            ['name' => 'ゴブリン', 'hp' => 70, 'power' => 7, 'interval' => 3.6, 'limit' => 40, 'exp' => 25],
            ['name' => 'オーク', 'hp' => 95, 'power' => 9, 'interval' => 3.3, 'limit' => 45, 'exp' => 40],
            ['name' => 'ハーピー', 'hp' => 110, 'power' => 10, 'interval' => 3.0, 'limit' => 45, 'exp' => 55],
            ['name' => 'ワイバーン', 'hp' => 135, 'power' => 12, 'interval' => 2.9, 'limit' => 50, 'exp' => 75],
            ['name' => 'ゴーレム', 'hp' => 165, 'power' => 13, 'interval' => 2.8, 'limit' => 55, 'exp' => 100],
        ],
    ],
    'abyss' => [
        'label' => '深淵',
        'color' => '#c0392b',
        'clear_bonus' => 320,
        'heal_between' => 0.10,
        'boss' => 'dragon',
        'enemies' => [
            ['name' => 'デスナイト', 'hp' => 150, 'power' => 14, 'interval' => 2.7, 'limit' => 45, 'exp' => 90],
            ['name' => 'キマイラ', 'hp' => 185, 'power' => 16, 'interval' => 2.5, 'limit' => 50, 'exp' => 120],
            ['name' => 'リッチ', 'hp' => 220, 'power' => 18, 'interval' => 2.4, 'limit' => 55, 'exp' => 160],
            ['name' => 'ベヒーモス', 'hp' => 270, 'power' => 20, 'interval' => 2.2, 'limit' => 60, 'exp' => 210],
            ['name' => 'ドラゴン', 'hp' => 330, 'power' => 23, 'interval' => 2.0, 'limit' => 70, 'exp' => 300],
        ],
    ],
];
