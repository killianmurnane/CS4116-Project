<?php
require __DIR__ . '/helpers/init.php';
require __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/database/repository/MatchesRepository.php';
require_once __DIR__ . '/database/repository/MessagesRepository.php';
require_once __DIR__ . '/database/repository/ProfileRepository.php';

requireLogin();

$matchesRepository = new MatchesRepository($pdo);
$messagesRepository = new MessagesRepository($pdo);
$profileRepository = new ProfileRepository($pdo);
$matches = $matchesRepository->getMatches((int) $_SESSION['user_id']);

if (isset($_GET['error'])) {
  $error = match ($_GET['error']) {
    'message_failed' => 'Failed to send message.',
    'invalid_input' => 'Please select a match and enter a message.',
    'invalid_match' => 'That match is not available.',
    default => 'An unknown error occurred.',
  };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $matchId =
    isset($_POST['match_id']) && ctype_digit((string) $_POST['match_id'])
      ? (int) $_POST['match_id']
      : null;
  $messageText = isset($_POST['message']) ? trim((string) $_POST['message']) : '';
  $allowedMatchIds = array_map(
    static fn(array $match): int => (int) ($match['match_id'] ?? 0),
    $matches ?? [],
  );

  if ($matchId !== null && $messageText !== '' && in_array($matchId, $allowedMatchIds, true)) {
    $messagesRepository->createMessage($matchId, (int) $_SESSION['user_id'], $messageText);
    header("Location: /message.php?matchId={$matchId}");
    exit();
  }
  if ($matchId !== null && !in_array($matchId, $allowedMatchIds, true)) {
    header('Location: /message.php?error=invalid_match');
    exit();
  }

  header('Location: /message.php?error=invalid_input');
  exit();
}

$selectedMatchId =
  isset($_GET['matchId']) && ctype_digit((string) $_GET['matchId']) ? (int) $_GET['matchId'] : null;

$selectedMatch = null;
$messages = [];

if (!empty($matches)) {
  foreach ($matches as &$match) {
    $otherUserId =
      (int) ($match['user1_id'] ?? 0) === (int) $_SESSION['user_id']
        ? (int) ($match['user2_id'] ?? 0)
        : (int) ($match['user1_id'] ?? 0);

    $profile = $profileRepository->findById($otherUserId);
    $match['display_name'] =
      isset($profile['given_name']) && isset($profile['family_name'])
        ? trim((string) $profile['given_name'] . ' ' . (string) $profile['family_name'])
        : 'Unknown';

    if ((int) ($match['match_id'] ?? 0) === $selectedMatchId) {
      $selectedMatch = $match;
    }
  }
  unset($match);

  if ($selectedMatch !== null) {
    $messages = $messagesRepository->getMessages($selectedMatchId) ?? [];
  }
}

function renderMessage(array $message, int $currentUserId): string
{
  $senderId = (int) ($message['sender_id'] ?? 0);
  $sender = $senderId === $currentUserId ? 'You' : 'Them';
  $content = htmlspecialchars((string) ($message['message_text'] ?? ''));
  $alignmentClass =
    $senderId === $currentUserId ? 'bg-dark text-white align-self-end' : 'bg-light border';

  return "<div class=\"rounded p-2 w-75 {$alignmentClass}\"><strong>{$sender}:</strong> {$content}</div>";
}
?>
<!DOCTYPE html>
<html>
    <head>
        <!-- CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
        <link rel="stylesheet" href="css/base.css" />
        <!-- Other -->
        <title>GymDate</title>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    </head>
    <body>
        <div class="container">
            <aside class="sidebar">
                <h2 class="sidebar-title">GymDate</h2>
                <nav class="menu" aria-label="Main menu">
                    <a class="menu-item" href="/">Home</a>
                    <a class="menu-item active" href="#">Messages</a>
                    <a class="menu-item" href="/search.php">Search</a>
                    <a class="menu-item" href="/profile.php">Profile</a>
                    <?php if (isAdmin()): ?>
                      <a class="menu-item" href="/admin.php">Admin</a>
                    <?php endif; ?>
                    <a class="menu-item" href="/helpers/auth.php?action=logout">Logout</a>
                </nav>
            </aside>

            <main class="content">
              <?php if (isset($error)): ?>
                    <div class="alert alert-danger login-banner" role="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <section class="top-rectangle p-4">
                    <div class="w-100">
                        <h3 class="mb-2">Messages</h3>
                        <p class="text-muted mb-3">Catch up with your gym partners and schedule your next session.</p>
                    </div>
                </section>

                <section class="bottom-split">
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">Matches</h5>
                        <div class="list-group w-100">
                            <?php if (empty($matches)): ?>
                                <p class="text-muted">No matches yet. Like some profiles to find gym partners!</p>
                            <?php else: ?>
                                <?php foreach ($matches as $match): ?>
                                    <form method="GET" action="/message.php" class="w-100">
                                        <button
                                            type="submit"
                                            name="matchId"
                                            value="<?= (int) $match['match_id'] ?>"
                                            class="list-group-item list-group-item-action text-start <?= $selectedMatchId ===
                                            (int) $match['match_id']
                                              ? 'active'
                                              : '' ?>"
                                        >
                                            <?= htmlspecialchars(
                                              (string) ($match['display_name'] ?? 'Unknown'),
                                            ) ?>
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">
                            <?= $selectedMatch !== null
                              ? 'Messages with ' .
                                htmlspecialchars(
                                  (string) ($selectedMatch['display_name'] ?? 'Unknown'),
                                )
                              : 'Messages' ?>
                        </h5>
                        <div class="w-100 d-flex flex-column gap-2 mb-3">
                            <?php if ($selectedMatch === null): ?>
                                <p class="text-muted">Select a match to view messages.</p>
                            <?php elseif (empty($messages)): ?>
                                <p class="text-muted">No messages yet. Start the conversation.</p>
                            <?php else: ?>
                                <?php foreach ($messages as $message): ?>
                                    <?= renderMessage($message, (int) $_SESSION['user_id']) ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="input-group mt-auto">
                            <form method="POST" action="/message.php" class="d-flex w-100 gap-2">
                                <input type="hidden" name="match_id" value="<?= $selectedMatchId ??
                                  '' ?>" />
                                <input class="form-control" type="text" name="message" placeholder="Type a message..." required <?= $selectedMatch ===
                                null
                                  ? 'disabled'
                                  : '' ?> />
                                <button class="btn btn-dark" type="submit" <?= $selectedMatch ===
                                null
                                  ? 'disabled'
                                  : '' ?>>Send</button>
                            </form>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>