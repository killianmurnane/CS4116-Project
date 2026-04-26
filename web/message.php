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

if (isset($_GET['reportError'])) {
  $error = match ($_GET['reportError']) {
    'invalid_method' => 'Invalid request method.',
    'invalid_input' => 'Please select a user and enter a reason and message for the report.',
    'invalid_target' => 'You cannot report yourself.',
    'failed' => 'Failed to submit report. Please try again later.',
    default => 'An unknown error occurred while submitting your report.',
  };
}

if (isset($_GET['reportSuccess'])) {
  $successMessage = 'Report submitted successfully. Thank you for helping us keep GymDate safe!';
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

  // Redact phone numbers by masking each digit with '#'
  $phonePresent = (bool) preg_match('/\+?[\d\s\-().]{7,20}\d/', $messageText);
  if ($phonePresent) {
    $messageText =
      preg_replace_callback(
        '/\+?[\d\s\-().]{7,20}\d/',
        function (array $matches): string {
          return preg_replace('/\d/', '#', $matches[0]) ?? $matches[0];
        },
        $messageText,
      ) ?? $messageText;
  }

  if ($matchId !== null && $messageText !== '' && in_array($matchId, $allowedMatchIds, true)) {
    $messagesRepository->createMessage($matchId, (int) $_SESSION['user_id'], $messageText);
    header("Location: /message.php?matchId={$matchId}" . ($phonePresent ? '&phone=1' : ''));
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
$phonePresent = isset($_GET['phone']) && $_GET['phone'] === '1';

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
        <link rel="stylesheet" href="css/messages.css" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@latest/css/all.min.css">
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
                    <a class="menu-item" href="/support.php">Support</a>
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
              <?php elseif (isset($successMessage)): ?>
                    <div class="alert alert-success login-banner" role="alert">
                        <?= htmlspecialchars($successMessage) ?>
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
                                  <?php $otherUserId =
                                    (int) ($match['user1_id'] ?? 0) === (int) $_SESSION['user_id']
                                      ? (int) ($match['user2_id'] ?? 0)
                                      : (int) ($match['user1_id'] ?? 0); ?>
                                  <div class="list-group-item d-flex align-items-center gap-2 <?= $selectedMatchId ===
                                  (int) $match['match_id']
                                    ? 'active'
                                    : '' ?>">
                                    <form method="GET" action="/message.php" class="flex-grow-1 m-0">
                                      <button
                                        type="submit"
                                        name="matchId"
                                        value="<?= (int) $match['match_id'] ?>"
                                        class="btn btn-link text-start text-decoration-none p-0 w-100 <?= $selectedMatchId ===
                                        (int) $match['match_id']
                                          ? 'text-white'
                                          : 'text-dark' ?>"
                                      >
                                        <?= htmlspecialchars(
                                          (string) ($match['display_name'] ?? 'Unknown'),
                                        ) ?>
                                      </button>
                                    </form>
                                    <div class="m-0">
                                      <button
                                        type="button"
                                        class="btn btn-sm <?= $selectedMatchId ===
                                        (int) $match['match_id']
                                          ? 'btn-light text-danger'
                                          : 'btn-outline-danger' ?>"
                                        title="Report user"
                                        data-report-button="true"
                                        data-reported-user-id="<?= $otherUserId ?>"
                                        data-reported-user-name="<?= htmlspecialchars(
                                          (string) ($match['display_name'] ?? 'Unknown'),
                                          ENT_QUOTES,
                                        ) ?>"
                                        data-match-id="<?= (int) $match['match_id'] ?>"
                                      >
                                        <i class="fas fa-flag"></i>
                                      </button>
                                    </div>
                                  </div>
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
                          <?php if ($phonePresent): ?>
                                <div class="alert alert-warning w-100" role="alert">
                                  <i class="fas fa-phone-alt"></i> Phone numbers are censored in line with site policies.
                                </div>
                              <?php endif; ?>
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

            <div id="report-modal" class="report-modal-overlay d-none" aria-hidden="true">
              <div class="report-modal-card p-4" role="dialog" aria-modal="true" aria-labelledby="report-modal-title">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                    <h5 id="report-modal-title" class="mb-1">Report user</h5>
                    <p class="text-muted mb-0">You are reporting: <strong id="report-modal-user-name">Unknown</strong></p>
                  </div>
                  <button id="report-modal-close" type="button" class="btn-close" aria-label="Close"></button>
                </div>

                <form method="POST" action="/helpers/report.php" id="report-modal-form" class="d-grid gap-3">
                  <input type="hidden" id="report-modal-user-id" name="reported_user_id" value="" />
                  <input type="hidden" id="report-modal-match-id" name="match_id" value="" />

                  <div>
                    <label class="form-label" for="report-modal-reason">Reason</label>
                    <select class="form-select" id="report-modal-reason" name="reason" required>
                      <option value="" selected disabled>Select a reason</option>
                      <option value="harassment">Harassment</option>
                      <option value="spam">Spam</option>
                      <option value="inappropriate_content">Inappropriate content</option>
                      <option value="fake_profile">Fake profile</option>
                      <option value="other">Other</option>
                    </select>
                  </div>

                  <div>
                    <label class="form-label" for="report-modal-message">Message</label>
                    <textarea
                      class="form-control"
                      id="report-modal-message"
                      name="message"
                      rows="4"
                      placeholder="Please provide details for moderators..."
                      required
                    ></textarea>
                  </div>

                  <div class="d-flex justify-content-end gap-2">
                    <button id="report-modal-cancel" type="button" class="btn btn-outline-secondary">Cancel</button>
                    <button type="submit" class="btn btn-danger">Submit Report</button>
                  </div>
                </form>
              </div>
            </div>

            <script src="/scripts/modal.js"></script>
    </body>
</html>