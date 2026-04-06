<?php

$env = parse_ini_file(__DIR__ . '/../.env', false, INI_SCANNER_TYPED);

if ($env === false) {
  throw new RuntimeException('Unable to read .env file');
}

$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = (int) ($env['DB_PORT'] ?? 3306);
$name = $env['DB_NAME'] ?? '';
$user = $env['DB_USER'] ?? '';
$pass = $env['DB_PASSWORD'] ?? '';

if ($name === '' || $user === '') {
  throw new RuntimeException('Missing DB_NAME or DB_USER in .env');
}

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

return new PDO($dsn, $user, $pass, $options);
