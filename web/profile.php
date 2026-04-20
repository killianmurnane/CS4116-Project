<?php
require __DIR__ . '/helpers/init.php';
require __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/database/repository/GoalsRepository.php';
require_once __DIR__ . '/database/repository/ExercisesRepository.php';
requireLogin();

if (isset($_POST['id']) && ctype_digit((string) $_POST['id']) && (int) $_POST['id'] > 0) {
  $userId = (int) $_POST['id'];
  $ownProfile = $userId === (int) $_SESSION['user_id'];
} else {
  $userId = (int) $_SESSION['user_id'];
  $ownProfile = true;
}

$userRepository = new UserRepository($pdo);
$profileRepository = new ProfileRepository($pdo);
$goalsRepository = new GoalsRepository($pdo);
$exercisesRepository = new ExercisesRepository($pdo);

$user = $userRepository->findById($userId);
$profile = $profileRepository->findById($userId);
$userGoals = $goalsRepository->getUserGoals($userId);
$userExercises = $exercisesRepository->getUserExercises($userId);

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
$location = trim((string) ($profile['location_name'] ?? ($profile['location'] ?? '')));
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
                    <a class="menu-item active" href="<?php if (!$ownProfile) {
                      echo '/profile.php';
                    } else {
                      echo '#';
                    } ?>">Profile</a>
                    <?php if (isAdmin()): ?>
                      <a class="menu-item" href="/admin.php">Admin</a>
                    <?php endif; ?>
                    <a class="menu-item" href="/helpers/auth.php?action=logout">Logout</a>
                </nav>
            </aside>

            <main class="content">
                <section class="top-rectangle p-4">
                    <div class="w-100 d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-secondary" style="width: 72px; height: 72px;"></div>
                        <div>
                          <p class="text-uppercase text-muted mb-1 small"><?= htmlspecialchars(
                            $profileLabel,
                          ) ?></p>
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
                        <div class="w-100 d-grid gap-2 mb-3">
                            <div class="border rounded p-2 bg-white"><strong>Email:</strong> <?= htmlspecialchars(
                              (string) $user['email'],
                            ) ?></div>
                            <div class="border rounded p-2 bg-white"><strong>Location:</strong> <?= $location !==
                            ''
                              ? htmlspecialchars($location)
                              : 'Not set' ?></div>
                            <div class="border rounded p-2 bg-white"><strong>Gender:</strong> <?= $gender !==
                            ''
                              ? htmlspecialchars(ucfirst($gender))
                              : 'Not set' ?></div>
                            <div class="border rounded p-2 bg-white"><strong>Date of Birth:</strong> <?= $dob !==
                            ''
                              ? htmlspecialchars($dob)
                              : 'Not set' ?></div>
                            <div class="border rounded p-2 bg-white"><strong>Preferred Sessions:</strong> <?= $preferredSessions !==
                            ''
                              ? htmlspecialchars($preferredSessions)
                              : 'Not set' ?></div>
                        </div>

                        <h6 class="mb-2">Goals</h6>
                        <?php if (empty($userGoals)): ?>
                            <p class="text-muted">No goals selected yet.</p>
                        <?php else: ?>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <?php foreach ($userGoals as $goal): ?>
                                    <span class="badge bg-dark p-2"><?= htmlspecialchars(
                                      (string) $goal['goal_name'],
                                    ) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <h6 class="mb-2">Exercises</h6>
                        <?php if (empty($userExercises)): ?>
                            <p class="text-muted">No exercises selected yet.</p>
                        <?php else: ?>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <?php foreach ($userExercises as $exercise): ?>
                                    <span class="badge bg-secondary p-2"><?= htmlspecialchars(
                                      (string) $exercise['exercise_name'],
                                    ) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">Overview</h5>
                        <div class="w-100 d-grid gap-2 mb-3">
                            <div class="border rounded p-2 bg-white d-flex justify-content-between"><span>Goals</span><strong><?= $goalCount ?></strong></div>
                            <div class="border rounded p-2 bg-white d-flex justify-content-between"><span>Exercises</span><strong><?= $exerciseCount ?></strong></div>
                            <div class="border rounded p-2 bg-white d-flex justify-content-between"><span>Profile Completion</span><strong><?= $profileCompletion ?>%</strong></div>
                        </div>

                        <h6 class="mb-2">Actions</h6>
                        <div class="w-100 d-flex flex-column gap-2">
                            <?php if ($ownProfile): ?>
                                <a class="btn btn-dark" href="/edit-profile.php">Edit Profile</a>
                                <a class="btn btn-outline-dark" href="/search.php">Find Training Partners</a>
                                <a class="btn btn-outline-dark" href="/message.php">Open Messages</a>
                            <?php else: ?>
                                <a class="btn btn-outline-dark" href="/search.php">Back to Search</a>
                                <a class="btn btn-dark" href="/profile.php">View My Profile</a>
                            <?php endif; ?>
                        </div>

                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
