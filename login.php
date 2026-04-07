<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';

$error = '';
$authError = '';
$username = '';

if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user'])) {
    dsh_redirect('game.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $u = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
        $p = isset($_POST['password']) ? (string) $_POST['password'] : '';
        $u = $u === '' ? null : $u;
        $p = $p === '' ? null : $p;

        if ($u === null || $p === null) {
            $error = 'Please enter username and password.';
        } else {
            $username = $u;
            if (dsh_authenticate_user($u, $p, $authError)) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user'] = $u;

                $_SESSION['intro_pending'] = true;
                $_SESSION['seen_intro'] = false;

                if (empty($_SESSION['leaderboard']) || !is_array($_SESSION['leaderboard'])) {
                    $_SESSION['leaderboard'] = [];
                }

                dsh_redirect('game.php');
            }
            $error = $authError ?? 'Invalid username or password.';
        }
    }

$queryError = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_SPECIAL_CHARS);
if ($queryError && is_string($queryError) && $queryError !== '') {
    $error = $queryError;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiceDash — Login</title>
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
            <a class="nav-link" href="index.php">Home</a>
            <a class="nav-link" href="register.php">Register</a>
        </nav>
    </header>

    <main class="container">
        <div class="card auth-card">
            <h1 class="page-title">Login</h1>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?= dsh($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="form">
                <label class="label" for="username">Username</label>
                <input
                    id="username"
                    name="username"
                    type="text"
                    class="input"
                    required
                    maxlength="24"
                    value="<?= dsh($username) ?>"
                >

                <label class="label" for="password">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    class="input"
                    required
                >

                <button class="btn btn-primary" type="submit">Sign in</button>
            </form>

            <p class="muted">
                New here? <a href="register.php">Create an account</a>.
            </p>
        </div>
    </main>

    <footer class="footer">
        DiceDash · Login via PHP Sessions
    </footer>
</div>
</body>
</html>

