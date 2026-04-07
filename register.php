<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';

$error = '';
$regError = '';
$username = '';

if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user'])) {
    dsh_redirect('game.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $u = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
        $p = isset($_POST['password']) ? (string) $_POST['password'] : '';
        $c = isset($_POST['confirm_password']) ? (string) $_POST['confirm_password'] : '';
        $u = $u === '' ? null : $u;
        $p = $p === '' ? null : $p;
        $c = $c === '' ? null : $c;

        if ($u === null || $p === null || $c === null) {
            $error = 'Please fill in all fields.';
        } else {
            $username = $u;
            if (dsh_register_user($u, $p, $c, $regError)) {
                dsh_redirect('login.php?error=' . urlencode('Account created. Please sign in.'));
            }
            $error = $regError ?? 'Registration failed. Please try again.';
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiceDash — Register</title>
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
            <a class="nav-link" href="login.php">Login</a>
        </nav>
    </header>

    <main class="container">
        <div class="card auth-card">
            <h1 class="page-title">Register</h1>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?= dsh($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="register.php" class="form">
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
                    minlength="8"
                >

                <label class="label" for="confirm_password">Confirm password</label>
                <input
                    id="confirm_password"
                    name="confirm_password"
                    type="password"
                    class="input"
                    required
                    minlength="8"
                >

                <button class="btn btn-primary" type="submit">Create account</button>
            </form>

            <p class="muted">
                Already have an account? <a href="login.php">Sign in</a>.
            </p>
        </div>
    </main>

    <footer class="footer">
        DiceDash · Create account via PHP
    </footer>
</div>
</body>
</html>

