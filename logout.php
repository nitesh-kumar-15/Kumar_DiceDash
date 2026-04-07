<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

// Destroy session safely.
$_SESSION = [];
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
session_destroy();

dsh_redirect('index.php');

