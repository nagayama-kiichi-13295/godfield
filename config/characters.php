<?php

return [
    'sword' => [
        'name' => 'ソードマン',
        'desc' => '攻撃力が高いが打たれ弱い',
        'base_hp' => 80,
        'base_power' => 14,
        'hp_growth' => 4,
        'power_growth' => 1.4,
    ],
    'shield' => [
        'name' => 'シールダー',
        'desc' => 'HP が高く粘り強い',
        'base_hp' => 130,
        'base_power' => 9,
        'hp_growth' => 9,
        'power_growth' => 0.8,
    ],
    'mage' => [
        'name' => 'メイジ',
        'desc' => '育てるほど火力が伸びる',
        'base_hp' => 85,
        'base_power' => 10,
        'hp_growth' => 5,
        'power_growth' => 1.8,
    ],
    'balance' => [
        'name' => 'バランサー',
        'desc' => '平均的で扱いやすい',
        'base_hp' => 100,
        'base_power' => 11,
        'hp_growth' => 6,
        'power_growth' => 1.1,
    ],
];