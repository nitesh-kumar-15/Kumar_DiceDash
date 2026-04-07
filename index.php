<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user'])) {
    dsh_redirect('game.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiceDash — Home</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="app">
    <header class="topbar">
        <div class="brand">
            <span class="brand-logo">🎲</span>
            <span class="brand-title">DiceDash</span>
        </div>
        <nav class="nav">
            <a class="nav-link" href="login.php">Login</a>
            <a class="nav-link nav-link-cta" href="register.php">Register</a>
        </nav>
    </header>

    <main class="container">
        <div class="hero">
            <h1>Adventures of the Dice</h1>
            <p>
                Roll the server-driven dice to race toward cell <strong>100</strong>.
                Snakes and ladders are applied by PHP. Dynamic Cell Events add story-driven twists.
            </p>
        </div>

        <div class="grid-2">
            <section class="card">
                <h2>How it works</h2>
                <ul class="plain">
                    <li>Register and log in</li>
                    <li>Choose a difficulty and start a run</li>
                    <li>Click “Roll Dice” to advance</li>
                    <li>Land on event cells to trigger Dynamic Cell Events</li>
                </ul>
            </section>

            <section class="card card-cta">
                <h2>Ready?</h2>
                <p>Login or create an account to begin.</p>
                <div class="cta-row">
                    <a class="btn" href="login.php">Login</a>
                    <a class="btn btn-primary" href="register.php">Create account</a>
                </div>
            </section>
        </div>
    </main>

    <footer class="footer">
        DiceDash · PHP Session Game · No database · No JS game logic
    </footer>
</div>
</body>
</html>

