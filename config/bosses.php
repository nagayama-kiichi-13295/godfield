<?php

return [
    'ogre' => [
        'name' => '鬼将ガドル',
        'color' => '#c98b3a',
        'hp' => 260,
        'limit' => 100,
        'exp' => 250,
        'phases' => [
            ['until' => 0.5, 'interval' => 3.6, 'power' => 10, 'charge_every' => 20, 'charge_time' => 7, 'charge_power' => 40, 'charge_words' => 2],
            ['until' => 0.0, 'interval' => 3.0, 'power' => 12, 'charge_every' => 15, 'charge_time' => 6, 'charge_power' => 55, 'charge_words' => 2],
        ],
    ],
    'dragon' => [
        'name' => '古龍ヴァルガ',
        'color' => '#c0392b',
        'hp' => 480,
        'limit' => 140,
        'exp' => 700,
        'phases' => [
            ['until' => 0.5, 'interval' => 3.0, 'power' => 16, 'charge_every' => 18, 'charge_time' => 6, 'charge_power' => 70, 'charge_words' => 2],
            ['until' => 0.0, 'interval' => 2.3, 'power' => 20, 'charge_every' => 13, 'charge_time' => 5, 'charge_power' => 95, 'charge_words' => 3],
        ],
    ],
];