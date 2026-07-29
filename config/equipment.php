<?php

return [
    'slots' => ['weapon' => '武器', 'armor' => '防具', 'charm' => '護符'],

    'items' => [
        'iron_sword'   => ['name' => '鉄の剣',       'slot' => 'weapon', 'rank' => 1, 'color' => '#9aa5b1', 'hp' => 0,  'power' => 2, 'int' => 0],
        'flame_blade'  => ['name' => '炎刃',         'slot' => 'weapon', 'rank' => 2, 'color' => '#e0555a', 'hp' => 0,  'power' => 5, 'int' => -1],
        'dragon_fang'  => ['name' => '竜牙の大剣',   'slot' => 'weapon', 'rank' => 3, 'color' => '#c0392b', 'hp' => -10, 'power' => 9, 'int' => 0],
        'sage_rod'     => ['name' => '賢者の杖',     'slot' => 'weapon', 'rank' => 2, 'color' => '#a06fd6', 'hp' => 0,  'power' => 2, 'int' => 4],

        'leather_mail' => ['name' => '革鎧',         'slot' => 'armor',  'rank' => 1, 'color' => '#9aa5b1', 'hp' => 15, 'power' => 0, 'int' => 0],
        'guard_plate'  => ['name' => '守護の胸当て', 'slot' => 'armor',  'rank' => 2, 'color' => '#4a90d9', 'hp' => 35, 'power' => 0, 'int' => 2],
        'dragon_scale' => ['name' => '竜鱗の鎧',     'slot' => 'armor',  'rank' => 3, 'color' => '#c0392b', 'hp' => 60, 'power' => 2, 'int' => 0],

        'wood_charm'   => ['name' => '木の護符',     'slot' => 'charm',  'rank' => 1, 'color' => '#9aa5b1', 'hp' => 0,  'power' => 0, 'int' => 3],
        'sage_charm'   => ['name' => '賢者の護符',   'slot' => 'charm',  'rank' => 2, 'color' => '#a06fd6', 'hp' => 5,  'power' => 0, 'int' => 7],
        'ancient_seal' => ['name' => '古代の印',     'slot' => 'charm',  'rank' => 3, 'color' => '#ffd166', 'hp' => 20, 'power' => 3, 'int' => 3],
    ],
];
