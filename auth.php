<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

const DICEDASH_USER_FILE = __DIR__ . '/data/users.json';

function dsh_ensure_user_file(): void
{
    $dir = dirname(DICEDASH_USER_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    if (!file_exists(DICEDASH_USER_FILE)) {
        file_put_contents(DICEDASH_USER_FILE, json_encode([], JSON_PRETTY_PRINT), LOCK_EX);
    }
}

function dsh_load_users(): array
{
    dsh_ensure_user_file();
    $raw = file_get_contents(DICEDASH_USER_FILE);
    if ($raw === false) {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [];
    }
    return $data;
}

function dsh_save_users(array $users): bool
{
    dsh_ensure_user_file();
    $fp = fopen(DICEDASH_USER_FILE, 'c+');
    if ($fp === false) {
        return false;
    }
    $ok = flock($fp, LOCK_EX);
    if (!$ok) {
        fclose($fp);
        return false;
    }
    ftruncate($fp, 0);
    rewind($fp);
    $payload = json_encode($users, JSON_PRETTY_PRINT);
    $written = fwrite($fp, $payload);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $written !== false;
}

function dsh_normalize_username(string $username): string
{
    // Keep usernames simple so file-based storage is predictable.
    $username = trim($username);
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username) ?? '';
    return $username;
}

function dsh_register_user(string $username, string $password, string $confirmPassword, string &$error): bool
{
    $username = dsh_normalize_username($username);
    $password = trim($password);
    $confirmPassword = trim($confirmPassword);

    if (mb_strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
        return false;
    }
    if (mb_strlen($username) > 24) {
        $error = 'Username must be at most 24 characters.';
        return false;
    }
    if ($password === '' || mb_strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
        return false;
    }
    if ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
        return false;
    }

    $users = dsh_load_users();
    foreach ($users as $u) {
        if (!is_array($u)) {
            continue;
        }
        if (!empty($u['username']) && is_string($u['username']) && $u['username'] === $username) {
            $error = 'That username is already taken.';
            return false;
        }
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $users[] = [
        'username' => $username,
        'password_hash' => $hash,
    ];

    if (!dsh_save_users($users)) {
        $error = 'Registration failed due to a storage error. Please try again.';
        return false;
    }

    return true;
}

function dsh_authenticate_user(string $username, string $password, string &$error): bool
{
    $username = dsh_normalize_username($username);
    $password = trim($password);

    if ($username === '' || $password === '') {
        $error = 'Invalid username or password.';
        return false;
    }

    $users = dsh_load_users();
    foreach ($users as $u) {
        if (!is_array($u)) {
            continue;
        }
        if (!empty($u['username']) && is_string($u['username']) && $u['username'] === $username) {
            $hash = (string) ($u['password_hash'] ?? '');
            if ($hash === '' || !password_verify($password, $hash)) {
                $error = 'Invalid username or password.';
                return false;
            }
            return true;
        }
    }

    $error = 'Invalid username or password.';
    return false;
}

