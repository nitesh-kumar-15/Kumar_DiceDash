<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/config.php';

function dsh($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function dsh_redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function dsh_require_logged_in(): void
{
    if (empty($_SESSION['logged_in']) || empty($_SESSION['user'])) {
        $msg = urlencode((string) ($_GET['error'] ?? 'Please log in to continue.'));
        dsh_redirect('login.php?error=' . $msg);
    }
}
