<?php
require __DIR__ . '/helpers/init.php';
require __DIR__ . '/helpers/auth.php';
requireLogin();

$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim((string) $url, '/'));
$lastSegment = end($segments);

if (ctype_digit($lastSegment) && (int) $lastSegment > 0) {
  // Handles /profile/1, /web/profile.php/1, etc.
  $userId = (int) $lastSegment;
  $ownProfile = $userId === (int) $_SESSION['user_id'];
} elseif (isset($_GET['userId']) && ctype_digit((string) $_GET['userId'])) {
  $userId = (int) $_GET['userId'];
  $ownProfile = $userId === (int) $_SESSION['user_id'];
} else {
  $userId = (int) $_SESSION['user_id'];
  $ownProfile = true;
}

$userRepository = new UserRepository($pdo);
$profileRepository = new ProfileRepository($pdo);

$user = $userRepository->findById($userId);
$profile = $profileRepository->findById($userId);

if (!$user) {
  header('Location: /404.html');
  exit();
}

$givenName = trim((string) ($profile['given_name'] ?? ''));
$familyName = trim((string) ($profile['family_name'] ?? ''));
$displayName = trim($givenName . ' ' . $familyName);

if ($displayName === '') {
  $displayName = (string) $user['email'];
}

$profileLabel = $ownProfile ? 'Your Profile' : 'Profile';
$bio = trim((string) ($profile['description'] ?? ''));
$location = trim((string) ($profile['location'] ?? ''));
$preferredSessions = trim((string) ($profile['preferred_sessions'] ?? ''));
$gender = trim((string) ($profile['gender'] ?? ''));
$dob = trim((string) ($profile['dob'] ?? ''));

$details = [];
if ($location !== '') {
  $details[] = htmlspecialchars($location);
}
if ($gender !== '') {
  $details[] = htmlspecialchars(ucfirst($gender));
}
$details = implode(' • ', $details);
?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
        <link rel="stylesheet" href="/css/base.css" />
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
                    <a class="menu-item" href="/message.php">Messages</a>
                    <a class="menu-item" href="/search.php">Search</a>
                    <a class="menu-item active" href="#">Profile</a>
                    <a class="menu-item" href="/helpers/auth.php?action=logout">Logout</a>
                </nav>
            </aside>

            <main class="content">
                <section class="top-rectangle p-4">
                    <div class="w-100 d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-secondary" style="width: 72px; height: 72px;"></div>
                        <div>
                          <h3 class="mb-1"><?= htmlspecialchars($displayName) ?></h3>
                          <p class="text-muted mb-0"><?= $details ?></p>
                        </div>
                    </div>
                </section>

                <section class="bottom-split">
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">About</h5>
                        <p class="mb-2"><?= $bio !== ''
                          ? htmlspecialchars($bio)
                          : 'No profile description added yet.' ?></p>

                        <h6 class="mb-2">Profile Details</h6>
                        <ul class="mb-3">
                            <li>Email: <?= htmlspecialchars((string) $user['email']) ?></li>
                            <li>Date of Birth: <?= $dob !== ''
                              ? htmlspecialchars($dob)
                              : 'Not set' ?></li>
                            <li>Preferred Sessions: <?= $preferredSessions !== ''
                              ? htmlspecialchars($preferredSessions)
                              : 'Not set' ?></li>
                        </ul>

                        <!-- TODO (goals table): add user's goals from user_goals + goals tables once a GoalRepository exists. -->
                        <!-- TODO (personal_records table): show latest PRs once a PersonalRecordRepository exists. -->
                        <!-- TODO (user_exercises table): show tracked exercises once a UserExerciseRepository exists. -->
                    </div>
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">Activity / Social</h5>
                        <div class="w-100 d-flex flex-column gap-2 mb-3">
                            <div class="border rounded p-2 bg-white">Profile loaded from users + profiles repositories.</div>
                            <div class="border rounded p-2 bg-white">User type: <?= htmlspecialchars(
                              (string) ($user['type'] ?? 'standard'),
                            ) ?></div>
                        </div>

                        <!-- TODO (activity table): render recent activity feed once ActivityRepository exists. -->
                        <!-- TODO (matches table): show active matches once MatchRepository exists. -->
                        <!-- TODO (messages table): show recent message summary once MessageRepository exists. -->
                        <!-- TODO (likes table): show likes/mutual likes once LikeRepository exists. -->

                        <?php if ($ownProfile): ?>
                          <a class="btn btn-dark mt-auto" href="/edit-profile.php">Edit Profile</a>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
