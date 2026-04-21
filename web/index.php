<?php
require __DIR__ . '/helpers/init.php';
require __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/database/repository/UserRepository.php';
require_once __DIR__ . '/database/repository/ProfileRepository.php';
require_once __DIR__ . '/database/repository/GoalsRepository.php';
require_once __DIR__ . '/database/repository/ExercisesRepository.php';
require_once __DIR__ . '/database/repository/MatchesRepository.php';
require_once __DIR__ . '/database/repository/LikesRepository.php';
requireLogin();

$userId = (int) $_SESSION['user_id'];

$userRepository = new UserRepository($pdo);
$profileRepository = new ProfileRepository($pdo);
$goalsRepository = new GoalsRepository($pdo);
$exercisesRepository = new ExercisesRepository($pdo);
$matchesRepository = new MatchesRepository($pdo);
$likesRepository = new LikesRepository($pdo);

$user = $userRepository->findById($userId);
$profile = $profileRepository->findById($userId) ?? [];
$userGoals = $goalsRepository->getUserGoals($userId);
$userExercises = $exercisesRepository->getUserExercises($userId);
$matches = $matchesRepository->getMatches($userId) ?? [];
$likesSent = $likesRepository->getLikedUsers($userId) ?? [];
$likesReceived = $likesRepository->getLikedByUsers($userId) ?? [];

$matchCount = count($matches);
$likesSentCount = count($likesSent);
$likesReceivedCount = count($likesReceived);

$messageCount = 0;
if (!empty($matches)) {
  $matchIds = array_values(
    array_unique(array_map(static fn($match) => (int) $match['match_id'], $matches)),
  );
  if (!empty($matchIds)) {
    $placeholders = implode(',', array_fill(0, count($matchIds), '?'));
    $messageCountStmt = $pdo->prepare(
      "SELECT COUNT(*) FROM messages WHERE match_id IN ({$placeholders})",
    );
    foreach ($matchIds as $index => $matchId) {
      $messageCountStmt->bindValue($index + 1, $matchId, PDO::PARAM_INT);
    }
    $messageCountStmt->execute();
    $messageCount = (int) $messageCountStmt->fetchColumn();
  }
}

$givenName = trim((string) ($profile['given_name'] ?? ''));
$familyName = trim((string) ($profile['family_name'] ?? ''));
$displayName = trim($givenName . ' ' . $familyName);
if ($displayName === '') {
  $displayName = (string) ($user['email'] ?? 'Athlete');
}

$location = trim((string) ($profile['location_name'] ?? ''));
$bio = trim((string) ($profile['description'] ?? ''));
$preferredSessions = trim((string) ($profile['preferred_sessions'] ?? ''));
$gender = trim((string) ($profile['gender'] ?? ''));
$dob = trim((string) ($profile['dob'] ?? ''));

$goalCount = count($userGoals);
$exerciseCount = count($userExercises);
$completionChecks = [
  $givenName !== '',
  $familyName !== '',
  $gender !== '',
  $location !== '',
  $dob !== '',
  $bio !== '',
  $preferredSessions !== '',
  $goalCount > 0,
  $exerciseCount > 0,
];
$profileCompletion = (int) round((array_sum($completionChecks) / count($completionChecks)) * 100);

$goalPreview = array_slice($userGoals, 0, 4);
$exercisePreview = array_slice($userExercises, 0, 6);
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
                    <a class="menu-item active" href="#">Home</a>
                    <a class="menu-item" href="/message.php">Messages</a>
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
                <section class="top-rectangle p-4">
                    <div class="w-100">
                        <p class="text-uppercase text-muted mb-1 small">Dashboard</p>
                        <h3 class="mb-2">Welcome back, <?= htmlspecialchars($displayName) ?>!</h3>
                        <p class="text-muted mb-3">
                            <?= $location !== '' ? htmlspecialchars($location) . ' • ' : '' ?>
                            Profile completion: <strong><?= $profileCompletion ?>%</strong>
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-dark" href="/search.php">Find Partners</a>
                            <a class="btn btn-outline-dark" href="/message.php">Open Messages</a>
                            <a class="btn btn-outline-dark" href="/edit-profile.php">Edit Profile</a>
                        </div>
                    </div>
                </section>
                <section class="bottom-split">
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">Your Training Focus</h5>

                        <h6 class="mb-2">Goals</h6>
                        <?php if (empty($goalPreview)): ?>
                            <p class="text-muted mb-3">No goals selected yet.</p>
                        <?php else: ?>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <?php foreach ($goalPreview as $goal): ?>
                                    <span class="badge bg-dark p-2"><?= htmlspecialchars(
                                      (string) $goal['goal_name'],
                                    ) ?></span>
                                <?php endforeach; ?>
                                <?php if ($goalCount > count($goalPreview)): ?>
                                    <span class="badge bg-light text-dark border p-2">+<?= $goalCount -
                                      count($goalPreview) ?> more</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <h6 class="mb-2">Exercises</h6>
                        <?php if (empty($exercisePreview)): ?>
                            <p class="text-muted mb-0">No exercises selected yet.</p>
                        <?php else: ?>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($exercisePreview as $exercise): ?>
                                    <span class="badge bg-secondary p-2"><?= htmlspecialchars(
                                      (string) $exercise['exercise_name'],
                                    ) ?></span>
                                <?php endforeach; ?>
                                <?php if ($exerciseCount > count($exercisePreview)): ?>
                                    <span class="badge bg-light text-dark border p-2">+<?= $exerciseCount -
                                      count($exercisePreview) ?> more</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">Activity Snapshot</h5>
                        <div class="w-100 d-flex flex-column gap-2">
                            <div class="border rounded p-2 bg-white d-flex justify-content-between"><span>Matches</span><strong><?= $matchCount ?></strong></div>
                            <div class="border rounded p-2 bg-white d-flex justify-content-between"><span>Messages in matches</span><strong><?= $messageCount ?></strong></div>
                            <div class="border rounded p-2 bg-white d-flex justify-content-between"><span>Likes sent</span><strong><?= $likesSentCount ?></strong></div>
                            <div class="border rounded p-2 bg-white d-flex justify-content-between"><span>Likes received</span><strong><?= $likesReceivedCount ?></strong></div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>