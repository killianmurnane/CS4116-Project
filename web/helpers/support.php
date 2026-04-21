<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/gemini.php';

requireLogin();

header('Content-Type: application/json; charset=UTF-8');

function supportJsonResponse(int $statusCode, array $payload): void
{
  http_response_code($statusCode);
  echo json_encode($payload);
  exit();
}

function getSupportChatUrl(): string
{
  $url = getenv('SUPPORT_CHAT_URL');
  return $url !== false && $url !== '' ? $url : '';
}

function getSupportChatUrls(): array
{
  $primary = trim(getSupportChatUrl());
  if ($primary !== '') {
    return [$primary];
  }

  $csv = getenv('SUPPORT_CHAT_URLS');
  if ($csv !== false && trim($csv) !== '') {
    $parts = array_values(
      array_filter(
        array_map(static fn($item) => trim((string) $item), explode(',', (string) $csv)),
        static fn($item) => $item !== '',
      ),
    );

    if (!empty($parts)) {
      return $parts;
    }
  }

  return [
    'http://127.0.0.1:3001/chat',
    'http://localhost:3001/chat',
    'http://host.docker.internal:3001/chat',
    'http://wsl.localhost:3001/chat',
  ];
}

function getSupportConversationId(): string
{
  if (
    !isset($_SESSION['support_chat_session_id']) ||
    !is_string($_SESSION['support_chat_session_id'])
  ) {
    $_SESSION['support_chat_session_id'] = session_id() . '_support_' . bin2hex(random_bytes(6));
  }

  return $_SESSION['support_chat_session_id'];
}

function sendSupportRequest(string $url, array $payload): array
{
  $jsonPayload = json_encode($payload);
  if ($jsonPayload === false) {
    throw new RuntimeException('Failed to encode support request.');
  }

  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
      CURLOPT_POSTFIELDS => $jsonPayload,
      CURLOPT_TIMEOUT => 20,
    ]);

    $responseBody = curl_exec($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($responseBody === false) {
      throw new RuntimeException(
        $curlError !== '' ? $curlError : 'Unable to contact support service.',
      );
    }
  } else {
    $context = stream_context_create([
      'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $jsonPayload,
        'timeout' => 20,
      ],
    ]);

    $responseBody = @file_get_contents($url, false, $context);
    $statusCode = 200;

    if (isset($http_response_header) && is_array($http_response_header)) {
      foreach ($http_response_header as $headerLine) {
        if (preg_match('/HTTP\/\S+\s+(\d{3})/', $headerLine, $matches) === 1) {
          $statusCode = (int) $matches[1];
          break;
        }
      }
    }

    if ($responseBody === false) {
      throw new RuntimeException('Unable to contact support service.');
    }
  }

  $decoded = json_decode($responseBody, true);
  if (!is_array($decoded)) {
    throw new RuntimeException('Support service returned an invalid response.');
  }

  if ($statusCode >= 400) {
    throw new RuntimeException((string) ($decoded['error'] ?? 'Support service error.'));
  }

  return $decoded;
}

function sendSupportRequestWithFallback(array $payload): array
{
  $errors = [];

  foreach (getSupportChatUrls() as $url) {
    try {
      return sendSupportRequest($url, $payload);
    } catch (Throwable $exception) {
      $message = $exception->getMessage();
      $errors[] = $url . ' => ' . $message;

      $isTransportError =
        str_contains($message, 'Failed to connect') ||
        str_contains($message, 'Could not resolve host') ||
        str_contains($message, 'Connection refused') ||
        str_contains($message, 'timed out') ||
        str_contains($message, 'Unable to contact support service');

      if (!$isTransportError) {
        throw new RuntimeException($message);
      }
    }
  }

  throw new RuntimeException('Support service unreachable. Tried: ' . implode(' | ', $errors));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  supportJsonResponse(405, ['error' => 'Method not allowed.']);
}

$action = trim((string) ($_POST['action'] ?? 'send'));

if ($action === 'reset') {
  supportJsonResponse(200, ['reply' => 'Support chat reset. What would you like to know?']);
}

$message = trim((string) ($_POST['message'] ?? ''));
if ($message === '') {
  supportJsonResponse(422, ['error' => 'Message is required.']);
}

try {
  $gemini = new GeminiHelper();
  $reply = $gemini->generateResponse($message);
  supportJsonResponse(200, ['reply' => $reply]);
} catch (Throwable $exception) {
  supportJsonResponse(502, ['error' => $exception->getMessage()]);
}
