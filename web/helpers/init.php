<?php

declare(strict_types=1);

$pdo = require __DIR__ . '/../database/sql.php';

// Determine if the connection is secure (HTTPS)
$isHttps =
  (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
  ($_SERVER['SERVER_PORT'] ?? null) == 443;

// Set secure session cookie parameters and start the session
if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'secure' => $isHttps,
    'samesite' => 'Lax',
  ]);

  session_start();
}
