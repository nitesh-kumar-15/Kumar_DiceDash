<?php
// Shared configuration for DiceDash.

declare(strict_types=1);

// Board/grid
const DICEDASH_GRID_SIZE = 10; // 10x10 => cells 1..100
const DICEDASH_MAX_CELL = 100;

// Difficulty definitions for Topic 03
// Snakes: snake head => snake tail
// Ladders: ladder base => ladder top
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

// Dynamic Cell Events (UG)
// Each event cell contains multiple possible variants.
// The engine deterministically picks a variant based on turn, cell, and username hash.
//
// Supported event types:
// - bonus: extra roll once
// - penalty: move backwards
// - skip: skip the next player action
// - warp: move to a deterministic destination
const DICEDASH_EVENTS_BY_CELL = [
    15 => [
        [
            'type' => 'bonus',
            'msg'  => "The dice altar flashes. A bonus surge grants you one extra roll!",
            'effect' => ['kind' => 'extra_roll', 'extra_roll_cap' => 1],
        ],
        [
            'type' => 'warp',
            'msg'  => "A phantom ladder appears in the shadows. You warp to a new location.",
            'effect' => ['kind' => 'warp', 'range' => [60, 95]],
        ],
    ],
    42 => [
        [
            'type' => 'penalty',
            'msg'  => "A trapdoor opens under your steps. You stumble backward!",
            'effect' => ['kind' => 'move_back', 'amount' => 5],
        ],
        [
            'type' => 'skip',
            'msg'  => "A curse of stillness lingers. Next turn will be skipped.",
            'effect' => ['kind' => 'skip_next', 'amount' => 1],
        ],
    ],
    67 => [
        [
            'type' => 'bonus',
            'msg'  => "A hidden passage hums with luck. You earn an extra roll!",
            'effect' => ['kind' => 'extra_roll', 'extra_roll_cap' => 1],
        ],
        [
            'type' => 'warp',
            'msg'  => "Mystic winds carry you forward to a safer corridor.",
            'effect' => ['kind' => 'warp', 'range' => [10, 55]],
        ],
    ],
    88 => [
        [
            'type' => 'penalty',
            'msg'  => "The board remembers your past mistakes. You lose ground!",
            'effect' => ['kind' => 'move_back', 'amount' => 7],
        ],
        [
            'type' => 'skip',
            'msg'  => "Time bends—your next action is delayed. Skip!",
            'effect' => ['kind' => 'skip_next', 'amount' => 1],
        ],
    ],
];

// Leaderboard display
const DICEDASH_LEADERBOARD_TOP_N = 10;

