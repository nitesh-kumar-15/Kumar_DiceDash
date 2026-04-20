<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';

function dsh_get_user(): string
{
    return (string) ($_SESSION['user'] ?? '');
}


// feat(modes): two-player turn tracking (session turn_index + positions[])
function dsh_get_mode(): string
{
    $mode = (string) ($_SESSION['mode'] ?? 'single');
    if ($mode === 'two' || $mode === 'ai') {
        return $mode;
    }
    return 'single';
}

function dsh_get_player_name(int $playerIndex): string
{
    if ($playerIndex === 1) {
        return dsh_get_user();
    }
    if (dsh_get_mode() === 'ai') {
        return 'CPU';
    }
    return (string) ($_SESSION['player2_name'] ?? 'Player 2');
}

function dsh_record_move_and_roll_for_player(int $playerIndex, int $roll): void
{
    if (dsh_get_mode() !== 'single') {
        $_SESSION['moves_by_player'][$playerIndex] = (int) ($_SESSION['moves_by_player'][$playerIndex] ?? 0) + 1;
        if (!isset($_SESSION['dice_history_by_player'][$playerIndex]) || !is_array($_SESSION['dice_history_by_player'][$playerIndex])) {
            $_SESSION['dice_history_by_player'][$playerIndex] = [];
        }
        $_SESSION['dice_history_by_player'][$playerIndex][] = $roll;
        return;
    }

    $_SESSION['moves'] = (int) ($_SESSION['moves'] ?? 0) + 1;
    if (!isset($_SESSION['dice_history']) || !is_array($_SESSION['dice_history'])) {
        $_SESSION['dice_history'] = [];
    }
    $_SESSION['dice_history'][] = $roll;
}

function dsh_get_difficulty_key_from_request(): string
{
    $key = dsh_get_post_string('difficulty', 20) ?? (string) ($_SESSION['difficulty'] ?? 'standard');
    if (!array_key_exists($key, DICEDASH_DIFFICULTIES)) {
        $key = 'standard';
    }
    return $key;
}

function dsh_get_mode_key_from_request(): string
{
    $mode = dsh_get_post_string('mode', 10) ?? (string) ($_SESSION['mode'] ?? 'single');
    if ($mode === 'two' || $mode === 'ai') {
        return $mode;
    }
    return 'single';
}

function dsh_reset_game_state(string $difficultyKey, string $modeKey = 'single'): void
{
    $difficultyKey = array_key_exists($difficultyKey, DICEDASH_DIFFICULTIES) ? $difficultyKey : 'standard';
    $modeKey = ($modeKey === 'two' || $modeKey === 'ai') ? $modeKey : 'single';

    $_SESSION['difficulty'] = $difficultyKey;
    $_SESSION['mode'] = $modeKey;
    $_SESSION['player2_name'] = ($modeKey === 'ai') ? 'CPU' : 'Player 2';

    if ($modeKey !== 'single') {
        $_SESSION['positions'] = [1 => 1, 2 => 1];
        $_SESSION['moves_by_player'] = [1 => 0, 2 => 0];
        $_SESSION['dice_history_by_player'] = [1 => [], 2 => []];
        $_SESSION['turn_index'] = 1;
    } else {
        $_SESSION['position'] = 1;
        $_SESSION['moves'] = 0;
        $_SESSION['dice_history'] = [];
    }

    $_SESSION['turn'] = 0;
    $_SESSION['pending_skip'] = 0;
    $_SESSION['events_log'] = [];
    $_SESSION['last_event'] = null;
    $_SESSION['ai_narrator'] = "A new adventure begins. Choose your fate, then roll when ready.";
    $_SESSION['game_start_time'] = time();
    $_SESSION['just_won'] = false;
    $_SESSION['last_jump_kind'] = null;
    $_SESSION['last_moved_player'] = 1;
    $_SESSION['last_jump_path_by_player'] = [
        1 => [],
        2 => [],
    ];
}

function dsh_apply_snakes_and_ladders(int $cell, array $snakes, array $ladders): array
{
    $details = [
        'start_cell' => $cell,
        'target_cell' => $cell,
        'jump_kind' => null, // 'snake'|'ladder'
    ];

    if (isset($snakes[$cell])) {
        $details['jump_kind'] = 'snake';
        $details['target_cell'] = (int) $snakes[$cell];
        return $details;
    }

    if (isset($ladders[$cell])) {
        $details['jump_kind'] = 'ladder';
        $details['target_cell'] = (int) $ladders[$cell];
        return $details;
    }

    return $details;
}

function dsh_compute_jump_trail_cells(int $fromCell, int $toCell): array
{
    $fromCell = max(1, min(DICEDASH_MAX_CELL, $fromCell));
    $toCell = max(1, min(DICEDASH_MAX_CELL, $toCell));

    if ($fromCell === $toCell) {
        return [];
    }

    // Trailing cells exclude start and landing cells.
    if ($toCell > $fromCell) {
        if ($toCell - $fromCell <= 1) {
            return [];
        }
        return range($fromCell + 1, $toCell - 1);
    }

    if ($fromCell - $toCell <= 1) {
        return [];
    }
    return range($toCell + 1, $fromCell - 1);
}

function dsh_set_last_jump_trail_for_player(int $fromCell, int $toCell, int $playerIndex): void
{
    $playerIndex = ($playerIndex === 2) ? 2 : 1;

    if (empty($_SESSION['last_jump_path_by_player']) || !is_array($_SESSION['last_jump_path_by_player'])) {
        $_SESSION['last_jump_path_by_player'] = [1 => [], 2 => []];
    }

    $_SESSION['last_jump_path_by_player'][$playerIndex] = dsh_compute_jump_trail_cells($fromCell, $toCell);
}

function dsh_deterministic_event_choice(array $variants, int $turn, int $cell, string $username): array
{
    // Deterministic seeding based on turn + cell + username.
    $seed = abs(crc32($username . '|' . $turn . '|' . $cell));
    mt_srand($seed);
    $idx = (count($variants) > 0) ? (mt_rand(0, count($variants) - 1)) : 0;
    return $variants[$idx] ?? $variants[0] ?? ['type' => 'bonus', 'msg' => ''];
}

function dsh_select_event_for_cell(int $cell, int $turn, string $username): ?array
{
    if (!isset(DICEDASH_EVENTS_BY_CELL[$cell])) {
        return null;
    }
    $variants = DICEDASH_EVENTS_BY_CELL[$cell];
    if (!is_array($variants) || count($variants) === 0) {
        return null;
    }
    $event = dsh_deterministic_event_choice($variants, $turn, $cell, $username);
    return is_array($event) ? $event : null;
}

function dsh_event_engine_maybe_trigger(int $cell, int $turn, string $username, int &$position, int $playerIndex, int $depth = 0): void
{
    // depth prevents pathological extra-roll chains.
    if ($depth > 3) {
        return;
    }

    $event = dsh_select_event_for_cell($cell, $turn, $username);
    if ($event === null) {
        return;
    }

    $difficultyKey = (string) ($_SESSION['difficulty'] ?? 'standard');
    $difficulty = DICEDASH_DIFFICULTIES[$difficultyKey];
    $snakes = $difficulty['snakes'];
    $ladders = $difficulty['ladders'];

    $_SESSION['last_event'] = $event;
    $_SESSION['events_log'][] = [
        'turn' => $turn,
        'cell' => $cell,
        'player' => $username,
        'type' => $event['type'] ?? '',
        'msg' => $event['msg'] ?? '',
    ];

    $type = (string) ($event['type'] ?? '');
    $effect = is_array($event['effect'] ?? null) ? $event['effect'] : [];
    $eventMsg = (string) ($event['msg'] ?? '');
    $appendNarrator = function (string $text): void {
        $current = (string) ($_SESSION['ai_narrator'] ?? '');
        if ($current === '') {
            $_SESSION['ai_narrator'] = $text;
        } else {
            $_SESSION['ai_narrator'] = $current . ' ' . $text;
        }
    };

    if ($type === 'bonus') {
        // Extra roll (server-side) once.
        $extraRollCap = (int) ($effect['extra_roll_cap'] ?? 1);
        $extraRolls = max(0, min(1, $extraRollCap));
        if ($extraRolls > 0) {
            // Narrator for the bonus, then roll immediately.
            $bonusText = $eventMsg !== '' ? $eventMsg : 'You feel a sudden surge of luck.';
            $appendNarrator($bonusText);

            $roll = rand(1, 6);
            dsh_record_move_and_roll_for_player($playerIndex, $roll);

            $tentative = min(DICEDASH_MAX_CELL, (int) $position + $roll);
            $landing = dsh_apply_snakes_and_ladders($tentative, $snakes, $ladders);
            dsh_set_last_jump_trail_for_player(
                (int) ($landing['start_cell'] ?? $tentative),
                (int) ($landing['target_cell'] ?? $tentative),
                $playerIndex
            );
            $position = (int) $landing['target_cell'];
            $_SESSION['last_jump_kind'] = $landing['jump_kind'] ?? null;

            if (($landing['jump_kind'] ?? null) === 'snake') {
                $appendNarrator("After the bonus roll, a snake pulls you down to cell {$position}.");
            } elseif (($landing['jump_kind'] ?? null) === 'ladder') {
                $appendNarrator("After the bonus roll, a ladder lifts you to cell {$position}.");
            } else {
                $appendNarrator("After the bonus roll, you land on cell {$position}.");
            }

            // Bonus landing might itself trigger another event.
            dsh_event_engine_maybe_trigger($position, $turn, $username, $position, $playerIndex, $depth + 1);
        }
        return;
    }

    if ($type === 'penalty') {
        $amount = (int) ($effect['amount'] ?? 5);
        $appendNarrator($eventMsg !== '' ? $eventMsg : 'A penalty strikes!');
        $position = max(1, (int) $position - $amount);
        $landing = dsh_apply_snakes_and_ladders($position, $snakes, $ladders);
        dsh_set_last_jump_trail_for_player(
            (int) ($landing['start_cell'] ?? $position),
            (int) ($landing['target_cell'] ?? $position),
            $playerIndex
        );
        $position = (int) $landing['target_cell'];
        $_SESSION['last_jump_kind'] = $landing['jump_kind'] ?? null;
        if (($landing['jump_kind'] ?? null) === 'snake') {
            $appendNarrator("The penalty move triggers a snake: down to cell {$position}.");
        } elseif (($landing['jump_kind'] ?? null) === 'ladder') {
            $appendNarrator("The penalty move triggers a ladder: up to cell {$position}.");
        }
        dsh_event_engine_maybe_trigger($position, $turn, $username, $position, $playerIndex, $depth + 1);
        return;
    }

    if ($type === 'skip') {
        $amount = (int) ($effect['amount'] ?? 1);
        $appendNarrator($eventMsg !== '' ? $eventMsg : 'Your next turn is skipped.');
        $_SESSION['pending_skip'] = max((int) $_SESSION['pending_skip'], $amount);
        return;
    }

    if ($type === 'warp') {
        $range = $effect['range'] ?? [60, 95];
        $min = (int) ($range[0] ?? 60);
        $max = (int) ($range[1] ?? 95);
        $min = max(1, min(DICEDASH_MAX_CELL, $min));
        $max = max(1, min(DICEDASH_MAX_CELL, $max));
        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }
        mt_srand(abs(crc32($username . '|' . $turn . '|' . $cell)));
        $dest = mt_rand($min, $max);
        $appendNarrator($eventMsg !== '' ? $eventMsg : 'Warp!');
        $position = (int) $dest;
        $landing = dsh_apply_snakes_and_ladders($position, $snakes, $ladders);
        dsh_set_last_jump_trail_for_player(
            (int) ($landing['start_cell'] ?? $position),
            (int) ($landing['target_cell'] ?? $position),
            $playerIndex
        );
        $position = (int) $landing['target_cell'];
        $_SESSION['last_jump_kind'] = $landing['jump_kind'] ?? null;
        if (($landing['jump_kind'] ?? null) === 'snake') {
            $appendNarrator("Warping lands you on a snake: down to cell {$position}.");
        } elseif (($landing['jump_kind'] ?? null) === 'ladder') {
            $appendNarrator("Warping lands you on a ladder: up to cell {$position}.");
        }
        dsh_event_engine_maybe_trigger($position, $turn, $username, $position, $playerIndex, $depth + 1);
        return;
    }

    // Unknown type => no-op, but still keep narrator text.
    $appendNarrator($eventMsg !== '' ? $eventMsg : 'An unknown event occurs.');
}

function dsh_build_narrator_for_roll(array $landingInfo, ?array $event, int $roll, int $from, int $to, string $playerName): string
{
    $parts = [];
    $parts[] = "🎲 {$playerName} rolled {$roll}.";
    if ($landingInfo['jump_kind'] === 'snake') {
        $parts[] = "A snake bites! Sliding down to cell {$to}.";
    } elseif ($landingInfo['jump_kind'] === 'ladder') {
        $parts[] = "A ladder lifts you up! Climbing to cell {$to}.";
    } else {
        $parts[] = "No snake or ladder, just steady progress to cell {$to}.";
    }

    if ($event !== null) {
        $type = (string) ($event['type'] ?? '');
        $msg = (string) ($event['msg'] ?? '');
        if ($msg !== '') {
            $parts[] = "🤖 The board whispers: {$msg}";
        } else {
            $parts[] = "🤖 An event of type '{$type}' triggers.";
        }
    }

    return implode(' ', $parts);
}

function dsh_sort_leaderboard(array $entries): array
{
    usort($entries, function ($a, $b) {
        $ma = (int) ($a['moves'] ?? PHP_INT_MAX);
        $mb = (int) ($b['moves'] ?? PHP_INT_MAX);
        if ($ma !== $mb) {
            return $ma <=> $mb;
        }
        $ta = (int) ($a['time_seconds'] ?? PHP_INT_MAX);
        $tb = (int) ($b['time_seconds'] ?? PHP_INT_MAX);
        if ($ta !== $tb) {
            return $ta <=> $tb;
        }
        return ((string) ($a['username'] ?? '')) <=> ((string) ($b['username'] ?? ''));
    });
    return $entries;
}

function dsh_finish_game_and_update_leaderboard(int $winnerPlayerIndex): void
{
    $winnerName = dsh_get_player_name($winnerPlayerIndex);
    $moves = (dsh_get_mode() !== 'single')
        ? (int) ($_SESSION['moves_by_player'][$winnerPlayerIndex] ?? 0)
        : (int) ($_SESSION['moves'] ?? 0);
    $start = (int) ($_SESSION['game_start_time'] ?? time());
    $timeSeconds = max(0, time() - $start);

    if (empty($_SESSION['leaderboard']) || !is_array($_SESSION['leaderboard'])) {
        $_SESSION['leaderboard'] = [];
    }

    $_SESSION['leaderboard'][] = [
        'username' => $winnerName,
        'moves' => $moves,
        'time_seconds' => $timeSeconds,
        'finished_at' => time(),
    ];
    $_SESSION['leaderboard'] = dsh_sort_leaderboard($_SESSION['leaderboard']);
    $_SESSION['leaderboard'] = array_slice($_SESSION['leaderboard'], 0, DICEDASH_LEADERBOARD_TOP_N);

    $_SESSION['just_won'] = true;
    $victory = "🏁 Victory! {$winnerName} reached cell 100.";
    $current = (string) ($_SESSION['ai_narrator'] ?? '');
    $_SESSION['ai_narrator'] = $current !== '' ? ($current . ' ' . $victory) : $victory;
    // Keep events_log for recap on leaderboard.
}

function dsh_process_roll_and_maybe_finish(): void
{
    $difficultyKey = (string) ($_SESSION['difficulty'] ?? 'standard');
    $difficulty = DICEDASH_DIFFICULTIES[$difficultyKey];
    $snakes = $difficulty['snakes'];
    $ladders = $difficulty['ladders'];

    // Turn increments for deterministic event selection.
    $_SESSION['turn'] = (int) ($_SESSION['turn'] ?? 0) + 1;
    $turn = (int) $_SESSION['turn'];

    $mode = dsh_get_mode();

    if ($mode !== 'single') {
        $activePlayer = (int) ($_SESSION['turn_index'] ?? 1);
        $activePlayer = ($activePlayer === 2) ? 2 : 1;

        $_SESSION['last_moved_player'] = $activePlayer;
        $playerName = dsh_get_player_name($activePlayer);

        $pos = (int) (($_SESSION['positions'][$activePlayer] ?? 1));
        $from = $pos;
        $_SESSION['last_jump_kind'] = null;
        $_SESSION['last_jump_path_by_player'][$activePlayer] = [];

        if (!empty($_SESSION['pending_skip']) && (int) $_SESSION['pending_skip'] > 0) {
            $_SESSION['pending_skip'] = max(0, (int) $_SESSION['pending_skip'] - 1);
            $_SESSION['ai_narrator'] = "⏭️ Skip active. {$playerName} loses this turn.";

            $_SESSION['turn_index'] = ($activePlayer === 1) ? 2 : 1;
            return;
        }

        $roll = rand(1, 6);
        dsh_record_move_and_roll_for_player($activePlayer, $roll);

        $tentative = min(DICEDASH_MAX_CELL, $pos + $roll);
        $landingInfo = dsh_apply_snakes_and_ladders($tentative, $snakes, $ladders);
        $to = (int) $landingInfo['target_cell'];
        dsh_set_last_jump_trail_for_player(
            (int) ($landingInfo['start_cell'] ?? $tentative),
            (int) ($landingInfo['target_cell'] ?? $tentative),
            $activePlayer
        );
        $_SESSION['last_jump_kind'] = $landingInfo['jump_kind'] ?? null;
        $pos = $to;

        $_SESSION['ai_narrator'] = dsh_build_narrator_for_roll($landingInfo, null, $roll, $from, $to, $playerName);

        // Trigger Dynamic Cell Events (UG) for the active player.
        dsh_event_engine_maybe_trigger($pos, $turn, $playerName, $pos, $activePlayer, 0);

        $_SESSION['positions'][$activePlayer] = (int) $pos;

        if ((int) $_SESSION['positions'][$activePlayer] >= DICEDASH_MAX_CELL) {
            $_SESSION['positions'][$activePlayer] = DICEDASH_MAX_CELL;
            dsh_finish_game_and_update_leaderboard($activePlayer);
            return;
        }

        // feat(modes): vs CPU — hand off to staged GET cpu_play step for readable UI.
        if ($mode === 'ai' && $activePlayer === 1) {
            $_SESSION['turn_index'] = 2;
            return;
        }

        // Next player's turn for classic two-player mode.
        $_SESSION['turn_index'] = ($activePlayer === 1) ? 2 : 1;
        return;
    }

    // Single-player mode.
    $pos = (int) ($_SESSION['position'] ?? 1);
    $from = $pos;
    $_SESSION['last_jump_kind'] = null;
    $_SESSION['last_jump_path_by_player'][1] = [];

    if (!empty($_SESSION['pending_skip']) && (int) $_SESSION['pending_skip'] > 0) {
        $_SESSION['pending_skip'] = max(0, (int) $_SESSION['pending_skip'] - 1);
        $_SESSION['ai_narrator'] = "⏭️ A skip is active. You lose this turn to the board’s mischief.";
        return;
    }

    $roll = rand(1, 6);
    dsh_record_move_and_roll_for_player(1, $roll);

    $tentative = min(DICEDASH_MAX_CELL, $pos + $roll);
    $landingInfo = dsh_apply_snakes_and_ladders($tentative, $snakes, $ladders);
    $to = (int) $landingInfo['target_cell'];
    dsh_set_last_jump_trail_for_player(
        (int) ($landingInfo['start_cell'] ?? $tentative),
        (int) ($landingInfo['target_cell'] ?? $tentative),
        1
    );
    $_SESSION['last_jump_kind'] = $landingInfo['jump_kind'] ?? null;
    $pos = $to;

    $_SESSION['ai_narrator'] = dsh_build_narrator_for_roll($landingInfo, null, $roll, $from, $to, dsh_get_user());

    // Trigger Dynamic Cell Events (UG).
    dsh_event_engine_maybe_trigger($pos, $turn, dsh_get_user(), $pos, 1, 0);

    $_SESSION['position'] = (int) $pos;

    if ((int) $_SESSION['position'] >= DICEDASH_MAX_CELL) {
        $_SESSION['position'] = DICEDASH_MAX_CELL;
        dsh_finish_game_and_update_leaderboard(1);
    }
}

function dsh_render_cell_classes(int $cell, string $difficultyKey): string
{
    $difficulty = DICEDASH_DIFFICULTIES[$difficultyKey];
    $snakes = $difficulty['snakes'];
    $ladders = $difficulty['ladders'];

    $classes = [];
    if (isset($snakes[$cell])) {
        $classes[] = 'cell-snake';
    }
    if (isset($ladders[$cell])) {
        $classes[] = 'cell-ladder';
    }
    if (isset(DICEDASH_EVENTS_BY_CELL[$cell])) {
        $classes[] = 'cell-event';
    }
    return implode(' ', $classes);
}

