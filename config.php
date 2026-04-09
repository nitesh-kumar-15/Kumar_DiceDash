<?php
// Shared configuration for DiceDash.

declare(strict_types=1);

// Board/grid
const DICEDASH_GRID_SIZE = 10; // 10x10 => cells 1..100
const DICEDASH_MAX_CELL = 100;

const DICEDASH_DIFFICULTIES = [
    'beginner' => [
        'label' => 'Beginner (3 snakes, 3 ladders)',
        'snakes' => [
            17 => 7,
            54 => 34,
            62 => 19,
        ],
        'ladders' => [
            4 => 14,
            9 => 31,
            20 => 38,
        ],
    ],
    'standard' => [
        'label' => 'Standard (6 snakes, 5 ladders)',
        'snakes' => [
            16 => 6,
            47 => 26,
            49 => 11,
            56 => 53,
            62 => 19,
            88 => 24,
        ],
        'ladders' => [
            1 => 38,
            8 => 31,
            21 => 42,
            28 => 84,
            36 => 44,
        ],
    ],
    'expert' => [
        'label' => 'Expert (9 snakes, 4 ladders)',
        'snakes' => [
            3 => 1,
            14 => 9,
            17 => 4,
            28 => 1,
            40 => 18,
            62 => 34,
            73 => 56,
            78 => 54,
            87 => 24,
        ],
        'ladders' => [
            2 => 64,
            6 => 25,
            10 => 32,
            33 => 62,
        ],
    ],
];

const DICEDASH_EVENTS_BY_CELL = [];

const DICEDASH_LEADERBOARD_TOP_N = 10;
