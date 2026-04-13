<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/game_logic.php';

if (!isset($_SESSION['difficulty'])) {
    $_SESSION['difficulty'] = 'standard';
}

$startError = '';
$turnControlAnchor = 'game.php#turn-control';

// If we already finished a game, always send the user to the recap page.
// This prevents the UI from showing "Start a new run" while session stats still show a completed game.
// exception: when the user clicks "Play Again" we explicitly reset via `game.php?new=1`.
$requestedNewRun = isset($_GET['new']) && (string) $_GET['new'] === '1';
if (!empty($_SESSION['just_won']) && !$requestedNewRun) {
    dsh_redirect('leaderboard.php');
}

// New run requested from leaderboard.
if (isset($_GET['new']) && (string) $_GET['new'] === '1') {
    $key = (string) ($_SESSION['difficulty'] ?? 'standard');
    $mode = (string) ($_SESSION['mode'] ?? 'single');
    dsh_reset_game_state($key, $mode);
    dsh_redirect($turnControlAnchor);
}

// no JavaScript:
// 1) show player move result, 2) show CPU pending state for 2s, 3) process CPU turn.
$isAiMode = ((string) ($_SESSION['mode'] ?? 'single') === 'ai');
$cpuPending = !empty($_SESSION['cpu_turn_pending']);

if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    $isAiMode &&
    $cpuPending &&
    isset($_GET['cpu_play']) &&
    (string) $_GET['cpu_play'] === '1'
) {
    dsh_process_roll_and_maybe_finish();
    $_SESSION['cpu_turn_pending'] = false;
    if (!empty($_SESSION['just_won'])) {
        dsh_redirect('leaderboard.php');
    }
    dsh_redirect('game.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!dsh_verify_csrf()) {
        // PRG: go back with an error
        dsh_redirect('game.php?error=' . urlencode('Security token invalid.') . '#turn-control');
    }

    $action = dsh_get_post_string('action', 30) ?? '';

    if ($action === 'intro_dismiss') {
        $_SESSION['intro_pending'] = false;
        $_SESSION['seen_intro'] = true;
        dsh_redirect($turnControlAnchor);
    }

    if ($action === 'start') {
        $difficultyKey = dsh_get_difficulty_key_from_request();
        $modeKey = dsh_get_mode_key_from_request();
        dsh_reset_game_state($difficultyKey, $modeKey);
        $_SESSION['cpu_turn_pending'] = false;
        dsh_redirect($turnControlAnchor);
    }

    if ($action === 'roll') {
        $modeForRoll = (string) ($_SESSION['mode'] ?? 'single');
        if (
            empty($_SESSION['game_start_time']) ||
            ($modeForRoll === 'single' && empty($_SESSION['position'])) ||
            ($modeForRoll !== 'single' && empty($_SESSION['positions'][1]))
        ) {
            dsh_reset_game_state((string) ($_SESSION['difficulty'] ?? 'standard'), $modeForRoll);
        }

        // Block manual roll while CPU turn is pending.
        if ($modeForRoll === 'ai' && !empty($_SESSION['cpu_turn_pending'])) {
            dsh_redirect('game.php?cpu_wait=1#turn-control');
        }

        dsh_process_roll_and_maybe_finish();

        // AI staged flow:
        // after player move, keep board on player result first, then transition to CPU wait state.
        if (
            $modeForRoll === 'ai' &&
            (int) (($_SESSION['turn_index'] ?? 1)) === 2 &&
            empty($_SESSION['just_won'])
        ) {
            $_SESSION['cpu_turn_pending'] = true;
            dsh_redirect('game.php?cpu_wait=1#turn-control');
        }

        if (!empty($_SESSION['just_won'])) {
            // keep recap via events_log in session
            dsh_redirect('leaderboard.php');
        }

        dsh_redirect($turnControlAnchor);
    }
}

$difficultyKey = (string) ($_SESSION['difficulty'] ?? 'standard');
$difficulty = DICEDASH_DIFFICULTIES[$difficultyKey];

$errorFromQuery = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_SPECIAL_CHARS);
if (is_string($errorFromQuery) && $errorFromQuery !== '') {
    $startError = $errorFromQuery;
}

$mode = dsh_get_mode();
$isMultiPlayerMode = ($mode !== 'single');
$secondPlayerName = dsh_get_player_name(2);
$position1 = (int) (
    $isMultiPlayerMode
        ? (($_SESSION['positions'][1] ?? 1))
        : ($_SESSION['position'] ?? 1)
);
$position2 = (int) (
    $isMultiPlayerMode
        ? (($_SESSION['positions'][2] ?? 1))
        : 0
);

$moves1 = (int) (
    $isMultiPlayerMode
        ? (($_SESSION['moves_by_player'][1] ?? 0))
        : ($_SESSION['moves'] ?? 0)
);
$moves2 = (int) (
    $isMultiPlayerMode
        ? (($_SESSION['moves_by_player'][2] ?? 0))
        : 0
);

$diceHistory1 = (array) (
    $isMultiPlayerMode
        ? (($_SESSION['dice_history_by_player'][1] ?? []))
        : ($_SESSION['dice_history'] ?? [])
);
$diceHistory2 = (array) (
    $isMultiPlayerMode
        ? (($_SESSION['dice_history_by_player'][2] ?? []))
        : []
);
$aiNarrator = (string) ($_SESSION['ai_narrator'] ?? '');
$pendingSkip = (int) ($_SESSION['pending_skip'] ?? 0);
$turnIndex = (int) (($_SESSION['turn_index'] ?? 1));
$turnIndex = ($turnIndex === 2) ? 2 : 1;
$cpuPending = ($mode === 'ai' && !empty($_SESSION['cpu_turn_pending']) && $turnIndex === 2);

$introPending = !empty($_SESSION['intro_pending']) && empty($_SESSION['seen_intro']);
$gameReady = !empty($_SESSION['game_start_time']) && empty($_SESSION['just_won']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiceDash — Game</title>
    <link rel="stylesheet" href="css/style.css">
    <?php // VS CPU: staged GET cpu_play after short delay (no client-side game logic). ?>
    <?php // VS CPU: staged GET cpu_play after short delay (no client-side game logic). ?>
    <?php // VS CPU: staged GET cpu_play after short delay (no client-side game logic). ?>
    <?php // VS CPU: staged GET cpu_play after short delay (no client-side game logic). ?>
    <?php if ($cpuPending): ?>
        <meta http-equiv="refresh" content="2;url=game.php?cpu_play=1#turn-control">
    <?php endif; ?>
</head>
<body>
<div class="app">
    <header class="topbar">
        <div class="brand">
            <span class="brand-logo">🎲</span>
            <span class="brand-title">DiceDash</span>
        </div>
        <nav class="nav">
            <a class="nav-link" href="leaderboard.php">Leaderboard</a>
            <a class="nav-link nav-link-cta" href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <div class="game-top">
            <div>
                <div class="userbar">Logged in as: <?= dsh(dsh_get_user()) ?></div>
                <div class="stats mt-8">
                    <span class="stat">Difficulty: <?= dsh($difficulty['label']) ?></span>
                    <?php if ($isMultiPlayerMode): ?>
                        <span class="stat">P1 Position: <?= dsh(($position1 > 0 ? $position1 : 1)) ?></span>
                        <span class="stat">P1 Moves: <?= dsh((string) $moves1) ?></span>
                        <span class="stat"><?= dsh($secondPlayerName) ?> Position: <?= dsh(($position2 > 0 ? $position2 : 1)) ?></span>
                        <span class="stat"><?= dsh($secondPlayerName) ?> Moves: <?= dsh((string) $moves2) ?></span>
                        <span class="stat turn-indicator">
                            Turn: <?= $turnIndex === 1 ? dsh(dsh_get_user()) : dsh($secondPlayerName) ?>
                        </span>
                    <?php else: ?>
                        <span class="stat">Position: <?= dsh($position1 > 0 ? $position1 : 1) ?></span>
                        <span class="stat">Moves: <?= dsh((string) $moves1) ?></span>
                    <?php endif; ?>

                    <?php if ($pendingSkip > 0): ?>
                        <span class="stat skip-active">Skip Active</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="stats">
                <span class="stat">Dynamic Cell Events</span>
            </div>
        </div>

        <div class="board-wrap">
            <section class="board">
                <?php if (!$gameReady): ?>
                    <div class="card-compact mb-12">
                        <h2 class="mt-0 mb-10">Start a new run</h2>
                        <p class="help-text">
                            Choose a difficulty layout, then roll the server-driven dice to reach cell 100.
                        </p>
                        <?php if ($startError !== ''): ?>
                            <div class="alert alert-error mt-12"><?= dsh($startError) ?></div>
                        <?php endif; ?>
                    </div>

                    <form method="POST" action="game.php" class="form">
                        <input type="hidden" name="csrf_token" value="<?= dsh(dsh_csrf_token()) ?>">
                        <input type="hidden" name="action" value="start">

                        <label class="label" for="difficulty">Difficulty</label>
                        <select id="difficulty" name="difficulty" class="input" required>
                            <?php foreach (DICEDASH_DIFFICULTIES as $key => $data): ?>
                                <option value="<?= dsh($key) ?>"<?= $key === $difficultyKey ? ' selected' : '' ?>><?= dsh($data['label']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label class="label" for="mode">Mode</label>
                        <select id="mode" name="mode" class="input" required>
                            <option value="single"<?= $mode === 'single' ? ' selected' : '' ?>>1 Player (Solo)</option>
                            <option value="two"<?= $mode === 'two' ? ' selected' : '' ?>>2 Players (Turn-Based)</option>
                            <option value="ai"<?= $mode === 'ai' ? ' selected' : '' ?>>Vs Computer (AI)</option>
                        </select>

                        <button class="btn btn-primary" type="submit">Begin</button>
                    </form>
                <?php endif; ?>

                <?php
                $gridSize = DICEDASH_GRID_SIZE;
                // Show tokens at the start cell even before the first roll.
                $token1Pos = $gameReady ? $position1 : 1;
                $token2Pos = ($isMultiPlayerMode) ? ($gameReady ? $position2 : 1) : 0;
                $difficultyKeyForCells = (string) ($_SESSION['difficulty'] ?? 'standard');
                $lastJumpKind = (string) ($_SESSION['last_jump_kind'] ?? '');
                $lastMovedPlayer = (int) (($_SESSION['last_moved_player'] ?? 1));
                $jumpTrailSet = [];
                $jumpTrailTypeClass = '';
                if ($gameReady && ($lastJumpKind === 'snake' || $lastJumpKind === 'ladder')) {
                    $jumpTrailCells = (array) (($_SESSION['last_jump_path_by_player'][$lastMovedPlayer] ?? []));
                    $jumpTrailSet = array_flip($jumpTrailCells);
                    $jumpTrailTypeClass = ($lastJumpKind === 'snake') ? 'trail-snake' : 'trail-ladder';
                }
                $activePlayerIndex = 1;
                if ($isMultiPlayerMode) {
                    $activePlayerIndex = (int) (($_SESSION['turn_index'] ?? 1));
                    $activePlayerIndex = ($activePlayerIndex === 2) ? 2 : 1;
                }
                $token1AnimClass = '';
                $token2AnimClass = '';
                if ($lastJumpKind === 'snake') {
                    $token1AnimClass = ($lastMovedPlayer === 1) ? 'anim-snake' : '';
                    $token2AnimClass = ($lastMovedPlayer === 2) ? 'anim-snake' : '';
                } elseif ($lastJumpKind === 'ladder') {
                    $token1AnimClass = ($lastMovedPlayer === 1) ? 'anim-ladder' : '';
                    $token2AnimClass = ($lastMovedPlayer === 2) ? 'anim-ladder' : '';
                }

                ?>
                <div class="board-grid">
                    <?php
                    for ($rowFromBottom = $gridSize - 1; $rowFromBottom >= 0; $rowFromBottom--) {
                        for ($col = 0; $col < $gridSize; $col++) {
                            if ($rowFromBottom % 2 === 0) {
                                $cell = ($rowFromBottom * $gridSize) + $col + 1;
                            } else {
                                $cell = ($rowFromBottom * $gridSize) + ($gridSize - $col);
                            }

                            $cell = (int) $cell;
                            $classes = dsh_render_cell_classes($cell, $difficultyKeyForCells);
                            $token1Here = ($token1Pos === $cell);
                            $token2Here = ($token2Pos === $cell);
                            $cellIsActive = ($token1Here && $activePlayerIndex === 1) || ($token2Here && $activePlayerIndex === 2);
                            $cellInTrail = isset($jumpTrailSet[$cell]);
                            ?>
                            <div class="cell <?= dsh($classes) ?><?= $cellIsActive ? ' active-cell' : '' ?><?= $cellInTrail ? ' cell-trail ' . $jumpTrailTypeClass : '' ?>">
                                <?php if ($token1Here || $token2Here): ?>
                                    <?php if ($token1Here): ?>
                                        <div class="token token-p1 <?= dsh($token1AnimClass) ?>" aria-label="Player 1 token">▲</div>
                                    <?php endif; ?>
                                    <?php if ($isMultiPlayerMode && $token2Here): ?>
                                        <div class="token token-p2 <?= dsh($token2AnimClass) ?>" aria-label="Player 2 token">●</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="cell-num"><?= dsh($cell) ?></div>
                                <?php endif; ?>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </section>

            <aside class="side">
                <div class="side-box" id="turn-control">
                    <h3 class="side-title">AI Narrator</h3>
                    <div class="narrator">
                        <?php if ($gameReady): ?>
                            <?= dsh($aiNarrator !== '' ? $aiNarrator : 'Roll the dice to begin.') ?>
                        <?php else: ?>
                            Start a run to activate the narrator.
                        <?php endif; ?>
                    </div>
                </div>

                <div class="side-box">
                    <h3 class="side-title">Dice History</h3>
                    <?php if ($gameReady): ?>
                        <?php if ($isMultiPlayerMode): ?>
                            <div class="history">
                                P1 last rolls:
                                <span class="history-code"><?= dsh(implode(', ', array_slice((array) $diceHistory1, -8))) ?></span>
                            </div>
                            <div class="history">
                                <?= dsh($secondPlayerName) ?> last rolls:
                                <span class="history-code"><?= dsh(implode(', ', array_slice((array) $diceHistory2, -8))) ?></span>
                            </div>
                        <?php else: ?>
                            <?php if (is_array($diceHistory1) && count($diceHistory1) > 0): ?>
                                <div class="history">
                                    Last rolls:
                                    <span class="history-code"><?= dsh(implode(', ', array_slice((array) $diceHistory1, -8))) ?></span>
                                </div>
                            <?php else: ?>
                                <div class="history">No rolls yet.</div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="history">No rolls yet.</div>
                    <?php endif; ?>
                </div>

                <div class="side-box">
                    <h3 class="side-title">Turn Control</h3>
                    <?php if ($gameReady): ?>
                        <?php if ($pendingSkip > 0): ?>
                            <div class="skip-note">A skip is active: the next roll action will be consumed.</div>
                        <?php endif; ?>

                        <form method="POST" action="game.php" class="form">
                            <input type="hidden" name="csrf_token" value="<?= dsh(dsh_csrf_token()) ?>">
                            <input type="hidden" name="action" value="start">

                            <label class="label" for="difficulty2">Change difficulty</label>
                            <select id="difficulty2" name="difficulty" class="input">
                                <?php foreach (DICEDASH_DIFFICULTIES as $key => $data): ?>
                                    <option value="<?= dsh($key) ?>"<?= $key === $difficultyKey ? ' selected' : '' ?>><?= dsh($data['label']) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <label class="label" for="mode2">Game mode</label>
                            <select id="mode2" name="mode" class="input">
                                <option value="single"<?= $mode === 'single' ? ' selected' : '' ?>>1 Player (Solo)</option>
                                <option value="two"<?= $mode === 'two' ? ' selected' : '' ?>>2 Players (Turn-Based)</option>
                                <option value="ai"<?= $mode === 'ai' ? ' selected' : '' ?>>Vs Computer (AI)</option>
                            </select>

                            <button class="btn" type="submit">Start Over</button>
                        </form>

                        <?php if ($mode === 'ai'): ?>
                            <?php if ($turnIndex === 1): ?>
                                <form method="POST" action="game.php" class="form">
                                    <input type="hidden" name="csrf_token" value="<?= dsh(dsh_csrf_token()) ?>">
                                    <input type="hidden" name="action" value="roll">
                                    <button class="btn btn-primary" type="submit" name="roll_btn" value="1">Roll Dice (Your Turn)</button>
                                </form>
                            <?php else: ?>
                                <div class="skip-note"><?= dsh($secondPlayerName) ?> is playing... your turn button is temporarily blocked.</div>
                                <form method="POST" action="game.php" class="form">
                                    <input type="hidden" name="csrf_token" value="<?= dsh(dsh_csrf_token()) ?>">
                                    <input type="hidden" name="action" value="roll">
                                    <button class="btn btn-primary" type="submit" name="roll_btn" value="1" disabled aria-disabled="true">
                                        <?= dsh($secondPlayerName) ?> Turn (Locked)
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <form method="POST" action="game.php" class="form">
                                <input type="hidden" name="csrf_token" value="<?= dsh(dsh_csrf_token()) ?>">
                                <input type="hidden" name="action" value="roll">
                                <button class="btn btn-primary" type="submit" name="roll_btn" value="1">Roll Dice (POST)</button>
                            </form>
                        <?php endif; ?>

                        <div class="muted">
                            If you reach cell <strong>100</strong>, you’ll be redirected to the leaderboard.
                        </div>
                    <?php endif; ?>
                </div>

            </aside>
        </div>
    </main>

    <?php if ($introPending): ?>
        <div class="modal-overlay" role="dialog" aria-modal="true" aria-label="How to play DiceDash">
            <div class="modal">
                <h2 class="modal-title">How to play</h2>
                <p class="help-text mt-0">
                    Read this once, then begin rolling server-driven dice to reach <strong>cell 100</strong>.
                </p>

                <div class="legend-grid">
                    <div class="legend-item">
                        <strong>Tokens</strong>
                        <div class="legend-row mt-8">
                            <span class="legend-token p1" aria-hidden="true">▲</span>
                            <span>Player 1</span>
                        </div>
                        <div class="legend-row">
                            <span class="legend-token p2" aria-hidden="true">●</span>
                            <span>Player 2 / CPU (2P or AI mode)</span>
                        </div>
                    </div>

                    <div class="legend-item">
                        <strong>Snakes &amp; Ladders</strong>
                        <div class="legend-row mt-8">
                            <span class="legend-swatch cell-snake" aria-hidden="true"></span>
                            <span>Snake: slide down</span>
                        </div>
                        <div class="legend-row">
                            <span class="legend-swatch cell-ladder" aria-hidden="true"></span>
                            <span>Ladder: climb up</span>
                        </div>
                    </div>

                    <div class="legend-item">
                        <strong>Dynamic Cell Events</strong>
                        <div class="legend-row mt-8">
                            <span class="legend-swatch cell-event" aria-hidden="true"></span>
                            <span>Event tile (Bonus/Penalty/Skip/Warp)</span>
                        </div>
                        <div class="help-text mt-8">
                            When you land on one, the server triggers the effect and updates the AI Narrator.
                        </div>
                    </div>

                    <div class="legend-item">
                        <strong>What the events do</strong>
                        <ul class="legend-list">
                            <li><strong>Bonus</strong>: get an extra roll (once).</li>
                            <li><strong>Penalty</strong>: lose cells.</li>
                            <li><strong>Skip</strong>: your next roll is consumed.</li>
                            <li><strong>Warp</strong>: teleport to a range of cells.</li>
                        </ul>
                    </div>

                    <div class="legend-item">
                        <strong>Skip Active</strong>
                        <div class="help-text mt-8">
                            If you see <strong>Skip Active</strong>, pressing <strong>Roll Dice</strong> will not roll—your turn gets used up.
                        </div>
                    </div>

                    <div class="legend-item">
                        <strong>Winning</strong>
                        <div class="help-text mt-8">
                            Reach <strong>cell 100</strong> to finish. You’ll be redirected to the leaderboard recap.
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <form method="POST" action="game.php" class="form">
                        <input type="hidden" name="csrf_token" value="<?= dsh(dsh_csrf_token()) ?>">
                        <input type="hidden" name="action" value="intro_dismiss">
                        <button class="btn btn-primary" type="submit">Got it</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <footer class="footer">
        DiceDash · PHP Sessions · Topic 03 Dice
    </footer>
</div>
</body>
</html>

