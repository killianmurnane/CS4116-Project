<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/gemini.php';
require_once __DIR__ . '/../database/repository/ReportsRepository.php';
require_once __DIR__ . '/../database/repository/MessagesRepository.php';

requireLogin();

/**
 * Submits a user report and:
 *  - Generates an AI overview for a report
 *  - Includes context from the report reason and message
 *  - Includes previous messages from the reported user
 */
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
  header('Location: ../message.php?reportError=invalid_method');
  exit();
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
  header('Location: ../message.php?reportError=invalid_input');
  exit();
}

if ($reporterId === $reportedId) {
  header('Location: ../message.php?reportError=invalid_target');
  exit();
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
    $generatedOverview ?? "AI overview unavailable.\n AI generation failed with error: {$aiError}";

  $reportsRepository = new ReportsRepository($pdo);
  $reportsRepository->createReport($reporterId, $reportedId, $reason, $message, $overview);
  header('Location: ../message.php?reportSuccess=1');
  exit();
} catch (Throwable $exception) {
  header('Location: ../message.php?reportError=failed');
  exit();
}
