<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/gemini.php';
require_once __DIR__ . '/../database/repository/ReportsRepository.php';
require_once __DIR__ . '/../database/repository/MessagesRepository.php';

requireLogin();

function reportRedirect(string $status, ?string $aiError = null): never
{
  $fallback = '/search.php';
  $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');

  $target = $fallback;
  if ($referer !== '') {
    $parts = parse_url($referer);
    $path = (string) ($parts['path'] ?? '');
    $query = (string) ($parts['query'] ?? '');

    if ($path !== '' && str_starts_with($path, '/')) {
      $target = $path . ($query !== '' ? '?' . $query : '');
    }
  }

  $params = ['report_status' => $status];
  if ($aiError !== null && trim($aiError) !== '') {
    $params['report_ai_error'] = mb_substr(trim($aiError), 0, 260);
  }

  $separator = str_contains($target, '?') ? '&' : '?';
  header('Location: ' . $target . $separator . http_build_query($params));
  exit();
}

function reportBuildFallbackOverview(
  string $reason,
  string $reportMessage,
  array $previousMessages,
  ?string $aiError = null,
): string {
  $normalizedReason = ucfirst(trim($reason));
  $messagePreview = trim(preg_replace('/\s+/', ' ', $reportMessage) ?? $reportMessage);
  $messagePreview = mb_substr($messagePreview, 0, 220);

  $recentCount = count($previousMessages);
  $latestSamples = array_slice($previousMessages, 0, 3);
  $sampleText = array_map(
    static fn(string $text): string => '- ' .
      mb_substr(trim(preg_replace('/\s+/', ' ', $text) ?? $text), 0, 140),
    $latestSamples,
  );

  $summary = [
    'AI overview unavailable at submission time. Fallback summary generated locally.',
    "Reason: {$normalizedReason}",
    "Reporter message: {$messagePreview}",
    "Recent messages reviewed: {$recentCount}",
  ];

  if ($aiError !== null && trim($aiError) !== '') {
    $summary[] = 'AI failure: ' . mb_substr(trim($aiError), 0, 260);
  }

  if (!empty($sampleText)) {
    $summary[] = 'Latest reported-user messages:';
    $summary[] = implode("\n", $sampleText);
  }

  return implode("\n", $summary);
}

function reportGenerateOverview(
  string $reportMessage,
  array $previousUserMessages,
  ?string &$error = null,
): ?string {
  try {
    $gemini = new GeminiHelper();
    $context = "Report message: {$reportMessage}\n\n";
    $context .= "Previous messages from reported user:\n";
    $context .= implode(
      "\n",
      array_map(
        static fn($msg) => '- ' . mb_substr(trim($msg), 0, 200),
        array_slice($previousUserMessages, 0, 10),
      ),
    );
    return $gemini->generateAdminResponse($context);
  } catch (Throwable $e) {
    $error = $e->getMessage();
    return null;
  }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  reportRedirect('invalid_method');
}

$reporterId = (int) ($_SESSION['user_id'] ?? 0);
$reportedIdRaw = $_POST['reported_id'] ?? ($_POST['reported_user_id'] ?? null);
$reason = trim((string) ($_POST['reason'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ($_POST['report_message'] ?? '')));
$matchIdRaw = isset($_POST['match_id']) ? trim((string) $_POST['match_id']) : '';
$matchId = $matchIdRaw !== '' && ctype_digit($matchIdRaw) ? (int) $matchIdRaw : null;

$reportedId =
  $reportedIdRaw !== null && ctype_digit((string) $reportedIdRaw) ? (int) $reportedIdRaw : 0;

if ($reporterId <= 0 || $reportedId <= 0 || $reason === '' || $message === '') {
  reportRedirect('invalid_input');
}

if ($reporterId === $reportedId) {
  reportRedirect('invalid_target');
}

try {
  $messagesRepository = new MessagesRepository($pdo);
  $rows = [];
  if ($matchId !== null) {
    $matchMessages = $messagesRepository->getMessages($matchId, 20) ?? [];
    $rows = array_values(
      array_filter(
        $matchMessages,
        static fn(array $row): bool => (int) ($row['sender_id'] ?? 0) === $reportedId,
      ),
    );
  }

  $previousMessages = array_values(
    array_filter(
      array_map(
        static fn(array $row): string => trim((string) ($row['message_text'] ?? '')),
        $rows,
      ),
      static fn(string $text): bool => $text !== '',
    ),
  );

  $aiError = null;
  $generatedOverview = reportGenerateOverview($message, $previousMessages, $aiError);
  $overview =
    $generatedOverview ??
    reportBuildFallbackOverview($reason, $message, $previousMessages, $aiError);

  if ($generatedOverview === null && $aiError !== null) {
    error_log('Report AI overview failed: ' . $aiError);
  }

  $reportsRepository = new ReportsRepository($pdo);
  $reportsRepository->createReport($reporterId, $reportedId, $reason, $message, $overview);

  reportRedirect($generatedOverview !== null ? 'success' : 'success_ai_fallback', $aiError);
} catch (Throwable $exception) {
  reportRedirect('failed', $exception->getMessage());
}
