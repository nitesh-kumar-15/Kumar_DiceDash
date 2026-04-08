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

function dsh_get_post_string(string $key, int $maxLen = 60): ?string
{
    $value = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
    if ($value === null || $value === false) {
        return null;
    }
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    if (mb_strlen($value) > $maxLen) {
        $value = mb_substr($value, 0, $maxLen);
    }
    return $value;
}

function dsh_get_post_int(string $key): ?int
{
    $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);
    return ($value === null || $value === false) ? null : (int) $value;
}

function dsh_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return (string) $_SESSION['csrf_token'];
}

function dsh_verify_csrf(): bool
{
    $posted = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_SPECIAL_CHARS);
    if (!is_string($posted) || $posted === '') {
        return false;
    }
    return isset($_SESSION['csrf_token']) && hash_equals((string) $_SESSION['csrf_token'], $posted);
}

function dsh_require_logged_in(): void
{
    if (empty($_SESSION['logged_in']) || empty($_SESSION['user'])) {
        $msg = urlencode((string) ($_GET['error'] ?? 'Please log in to continue.'));
        dsh_redirect('login.php?error=' . $msg);
    }
}

