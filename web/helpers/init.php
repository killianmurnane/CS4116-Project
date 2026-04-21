<?php

declare(strict_types=1);

$isHttps =
  (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
  ($_SERVER['SERVER_PORT'] ?? null) == 443;

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

$pdo = require __DIR__ . '/../database/sql.php';

if (!function_exists('e')) {
  function e(mixed $value): string
  {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
  }
}
