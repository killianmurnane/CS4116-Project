<?php
require __DIR__ . '/helpers/init.php';
require __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/database/repository/ProfileRepository.php';
require_once __DIR__ . '/database/repository/UserRepository.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];
$userRepository = new UserRepository($pdo);
$profileRepository = new ProfileRepository($pdo);

$user = $userRepository->findById($userId);
$profile = $profileRepository->findById($userId);

if (!$user) {
  header('Location: /404.html');
  exit();
}

if (!$profile) {
  $profile = [];
}

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $givenName = trim((string) ($_POST['given_name'] ?? ''));
  $familyNameRaw = trim((string) ($_POST['family_name'] ?? ''));
  $genderRaw = trim((string) ($_POST['gender'] ?? ''));
  $locationRaw = trim((string) ($_POST['location'] ?? ''));
  $dobInput = trim((string) ($_POST['dob'] ?? ''));
  $descriptionRaw = trim((string) ($_POST['description'] ?? ''));
  $preferredSessionsRaw = trim((string) ($_POST['preferred_sessions'] ?? ''));

  if ($givenName === '') {
    $error = 'Given name is required.';
  } elseif ($genderRaw !== '' && !in_array($genderRaw, ProfileRepository::ALLOWED_GENDERS, true)) {
    $error = 'Please select a valid gender.';
  } else {
    $dob = null;
    if ($dobInput !== '') {
      $dob = DateTime::createFromFormat('Y-m-d', $dobInput);
      if ($dob === false) {
        $error = 'Please enter a valid date of birth.';
      }
    }

    if ($error === null) {
      $profile = $profileRepository->updateProfile(
        userId: $userId,
        givenName: $givenName,
        familyName: $familyNameRaw !== '' ? $familyNameRaw : null,
        gender: $genderRaw !== '' ? $genderRaw : null,
        location: $locationRaw !== '' ? $locationRaw : null,
        dob: $dob,
        description: $descriptionRaw !== '' ? $descriptionRaw : null,
        preferredSessions: $preferredSessionsRaw !== '' ? $preferredSessionsRaw : null,
      );

      $success = true;
    }
  }
}

$givenName = (string) ($profile['given_name'] ?? '');
$familyName = (string) ($profile['family_name'] ?? '');
$gender = (string) ($profile['gender'] ?? '');
$location = (string) ($profile['location'] ?? '');
$dob = (string) ($profile['dob'] ?? '');
$description = (string) ($profile['description'] ?? '');
$preferredSessions = (string) ($profile['preferred_sessions'] ?? '');
?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
        <link rel="stylesheet" href="/css/base.css" />
        <title>Edit Profile - GymDate</title>
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
                    <a class="menu-item active" href="/profile.php">Profile</a>
                    <a class="menu-item" href="/helpers/auth.php?action=logout">Logout</a>
                </nav>
            </aside>

            <main class="content">
                <section class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                    <div class="d-flex justify-content-between align-items-center w-100 mb-3">
                        <h3 class="mb-0">Edit Profile</h3>
                        <a href="/profile.php" class="btn btn-outline-secondary">Back to Profile</a>
                    </div>

                    <?php if ($error !== null): ?>
                        <div class="alert alert-danger w-100" role="alert"><?= htmlspecialchars(
                          $error,
                        ) ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success w-100" role="alert">Profile updated successfully.</div>
                    <?php endif; ?>

                    <form method="post" class="w-100 d-grid gap-3">
                        <div>
                            <label class="form-label" for="given_name">Given Name</label>
                            <input class="form-control" id="given_name" name="given_name" type="text" required value="<?= htmlspecialchars(
                              $givenName,
                            ) ?>">
                        </div>

                        <div>
                            <label class="form-label" for="family_name">Family Name</label>
                            <input class="form-control" id="family_name" name="family_name" type="text" value="<?= htmlspecialchars(
                              $familyName,
                            ) ?>">
                        </div>

                        <div>
                            <label class="form-label" for="gender">Gender</label>
                          <select class="form-control" id="gender" name="gender">
                            <option value="">Select gender</option>
                            <option value="male" <?= $gender === 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= $gender === 'female' ? 'selected' : '' ?>>Female</option>
                            <option value="other" <?= $gender === 'other' ? 'selected' : '' ?>>Other</option>
                          </select>
                        </div>

                        <div>
                            <label class="form-label" for="location">Location</label>
                            <input class="form-control" id="location" name="location" type="text" value="<?= htmlspecialchars(
                              $location,
                            ) ?>">
                        </div>

                        <div>
                            <label class="form-label" for="dob">Date of Birth</label>
                            <input class="form-control" id="dob" name="dob" type="date" value="<?= htmlspecialchars(
                              $dob,
                            ) ?>">
                        </div>

                        <div>
                            <label class="form-label" for="preferred_sessions">Preferred Sessions</label>
                            <input class="form-control" id="preferred_sessions" name="preferred_sessions" type="text" value="<?= htmlspecialchars(
                              $preferredSessions,
                            ) ?>">
                        </div>

                        <div>
                            <label class="form-label" for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars(
                              $description,
                            ) ?></textarea>
                        </div>

                        <button class="btn btn-dark align-self-start" type="submit">Save Changes</button>
                    </form>
                </section>
            </main>
        </div>
    </body>
</html>
