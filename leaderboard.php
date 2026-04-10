<?php
declare(strict_types=1);

require_once __DIR__ . '/require_login.php';
require_once __DIR__ . '/game_logic.php';

$username = (string) ($_SESSION['user'] ?? '');
$leaderboard = $_SESSION['leaderboard'] ?? [];
if (!is_array($leaderboard)) {
    $leaderboard = [];
}

$justWon = !empty($_SESSION['just_won']);
$eventsLog = $_SESSION['events_log'] ?? [];
if (!is_array($eventsLog)) {
    $eventsLog = [];
}

// Format a small mm:ss string.
function dsh_format_time(int $seconds): string
{
    $seconds = max(0, $seconds);
    $m = intdiv($seconds, 60);
    $s = $seconds % 60;
    return sprintf('%02d:%02d', $m, $s);
}

$successNarrator = '';
if ($justWon) {
    $successNarrator = (string) ($_SESSION['ai_narrator'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiceDash — Leaderboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="app">
    <header class="topbar">
        <div class="brand">
            <span class="brand-logo">🏆</span>
            <span class="brand-title">DiceDash</span>
        </div>
        <nav class="nav">
            <a class="nav-link" href="game.php?new=1">Game</a>
            <a class="nav-link nav-link-cta" href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <h1 class="mt-0 mb-12">Leaderboard</h1>

        <?php if ($justWon): ?>
            <section class="card recap">
                <div class="recap-grid">
                    <div>
                        <div class="pill">Adventure Recap</div>
                        <?php if ($successNarrator !== ''): ?>
                            <div class="mt-10 narrator muted-strong">
                                <?= dsh($successNarrator) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <h3 class="mt-0 mb-10">Events that occurred</h3>
                        <?php if (count($eventsLog) === 0): ?>
                            <div class="muted">No Dynamic Cell Events triggered in this run.</div>
                        <?php else: ?>
                            <table class="table" aria-label="Event recap table">
                                <thead>
                                    <tr>
                                        <th>Turn</th>
                                        <th>Cell</th>
                                        <th>Type</th>
                                        <th>Player</th>
                                        <th>Story</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($eventsLog as $e): ?>
                                    <?php
                                    $turn = (int) ($e['turn'] ?? 0);
                                    $cell = (int) ($e['cell'] ?? 0);
                                    $type = (string) ($e['type'] ?? '');
                                    $player = (string) ($e['player'] ?? '');
                                    $msg = (string) ($e['msg'] ?? '');
                                    ?>
                                    <tr>
                                        <td><?= dsh($turn) ?></td>
                                        <td><?= dsh($cell) ?></td>
                                        <td><?= dsh($type) ?></td>
                                        <td><?= dsh($player) ?></td>
                                        <td class="t-cell-story"><?= dsh($msg) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="card">
            <h2 class="mt-0 mb-12">Top Runs</h2>

            <?php if (count($leaderboard) === 0): ?>
                <div class="muted muted-strong">No games completed yet.</div>
            <?php else: ?>
                <table class="table" aria-label="Leaderboard table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Moves</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($leaderboard as $i => $entry): ?>
                        <?php
                        $moves = (int) ($entry['moves'] ?? 0);
                        $time = (int) ($entry['time_seconds'] ?? 0);
                        $u = (string) ($entry['username'] ?? '');
                        ?>
                        <tr>
                            <td><?= dsh((string) ($i + 1)) ?></td>
                            <td><?= dsh($u) ?></td>
                            <td><?= dsh($moves) ?></td>
                            <td><?= dsh(dsh_format_time($time)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div class="mt-14">
                <a class="btn btn-primary" href="game.php?new=1">Play Again</a>
                <span class="text-muted ml-10">Start a fresh run on the game page.</span>
            </div>
        </section>
    </main>

    <footer class="footer">
        DiceDash · Session Leaderboard
    </footer>
</div>
</body>
</html>

