<?php
require __DIR__ . '/helpers/init.php';
require __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/database/repository/ProfileRepository.php';
require_once __DIR__ . '/database/repository/UserRepository.php';
require_once __DIR__ . '/database/repository/LocationsRepository.php';
require_once __DIR__ . '/database/repository/GoalsRepository.php';
require_once __DIR__ . '/database/repository/ExercisesRepository.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];
$userRepository = new UserRepository($pdo);
$profileRepository = new ProfileRepository($pdo);
$locationsRepository = new LocationsRepository($pdo);
$goalsRepository = new GoalsRepository($pdo);
$exercisesRepository = new ExercisesRepository($pdo);

$user = $userRepository->findById($userId);
$profile = $profileRepository->findById($userId);
$locations = $locationsRepository->getAllLocations();
$goals = $goalsRepository->getAllGoals();
$exercises = $exercisesRepository->getAllExercises();
$userGoalRows = $goalsRepository->getUserGoals($userId);
$userExerciseRows = $exercisesRepository->getUserExercises($userId);

$locationIds = array_map(static fn($row) => (int) $row['id'], $locations);
$goalIds = array_map(static fn($row) => (int) $row['goal_id'], $goals);
$exerciseIds = array_map(static fn($row) => (int) $row['exercise_id'], $exercises);

$selectedGoalIds = array_map(static fn($row) => (int) $row['goal_id'], $userGoalRows);
$selectedExerciseIds = array_map(static fn($row) => (int) $row['exercise_id'], $userExerciseRows);

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
  $locationRaw =
    isset($_POST['location']) && $_POST['location'] !== '' ? (int) $_POST['location'] : null;
  $dobInput = trim((string) ($_POST['dob'] ?? ''));
  $descriptionRaw = trim((string) ($_POST['description'] ?? ''));
  $preferredSessionsRaw = trim((string) ($_POST['preferred_sessions'] ?? ''));
  $postedGoalIds =
    isset($_POST['goal_ids']) && is_array($_POST['goal_ids']) ? $_POST['goal_ids'] : [];
  $postedExerciseIds =
    isset($_POST['exercise_ids']) && is_array($_POST['exercise_ids']) ? $_POST['exercise_ids'] : [];

  $selectedGoalIds = array_values(
    array_unique(
      array_filter(
        array_map('intval', $postedGoalIds),
        static fn($goalId) => in_array($goalId, $goalIds, true),
      ),
    ),
  );
  $selectedExerciseIds = array_values(
    array_unique(
      array_filter(
        array_map('intval', $postedExerciseIds),
        static fn($exerciseId) => in_array($exerciseId, $exerciseIds, true),
      ),
    ),
  );

  if ($givenName === '') {
    $error = 'Given name is required.';
  } elseif ($genderRaw !== '' && !in_array($genderRaw, ProfileRepository::ALLOWED_GENDERS, true)) {
    $error = 'Please select a valid gender.';
  } elseif ($locationRaw !== null && !in_array($locationRaw, $locationIds, true)) {
    $error = 'Please select a valid location.';
  } else {
    $dob = null;
    if ($dobInput !== '') {
      $dob = DateTime::createFromFormat('Y-m-d', $dobInput);
      if ($dob === false) {
        $error = 'Please enter a valid date of birth.';
      }
    }

    if ($error === null) {
      try {
        $pdo->beginTransaction();

        $profile = $profileRepository->updateProfile(
          userId: $userId,
          givenName: $givenName,
          familyName: $familyNameRaw !== '' ? $familyNameRaw : null,
          gender: $genderRaw !== '' ? $genderRaw : null,
          location: $locationRaw !== null ? (string) $locationRaw : null,
          dob: $dob,
          description: $descriptionRaw !== '' ? $descriptionRaw : null,
          preferredSessions: $preferredSessionsRaw !== '' ? $preferredSessionsRaw : null,
        );

        $deleteGoals = $pdo->prepare('DELETE FROM user_goals WHERE user_id = :user_id');
        $deleteGoals->execute(['user_id' => $userId]);
        foreach ($selectedGoalIds as $goalId) {
          $goalsRepository->addUserGoal($userId, $goalId);
        }

        $deleteExercises = $pdo->prepare('DELETE FROM user_exercises WHERE user_id = :user_id');
        $deleteExercises->execute(['user_id' => $userId]);
        foreach ($selectedExerciseIds as $exerciseId) {
          $exercisesRepository->addUserExercise($userId, $exerciseId);
        }

        $pdo->commit();
        $userGoalRows = $goalsRepository->getUserGoals($userId);
        $userExerciseRows = $exercisesRepository->getUserExercises($userId);
        $success = true;
      } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        $error = 'Failed to update profile. Please try again.';
      }
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
                    <?php if (isAdmin()): ?>
                      <a class="menu-item" href="/admin.php">Admin</a>
                    <?php endif; ?>
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
                            <option value="male" <?= $gender === 'male'
                              ? 'selected'
                              : '' ?>>Male</option>
                            <option value="female" <?= $gender === 'female'
                              ? 'selected'
                              : '' ?>>Female</option>
                            <option value="other" <?= $gender === 'other'
                              ? 'selected'
                              : '' ?>>Other</option>
                          </select>
                        </div>

                        <div>
                            <label class="form-label" for="location">Location</label>
                          <select class="form-control" id="location" name="location">
                            <option value="">Select county</option>
                            <?php foreach ($locations as $locationRow): ?>
                              <option value="<?= (int) $locationRow[
                                'id'
                              ] ?>" <?= (int) $location === (int) $locationRow['id']
  ? 'selected'
  : '' ?>>
                                <?= htmlspecialchars($locationRow['location']) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>

                        <div>
                          <label class="form-label" for="goal-select">Goals</label>
                          <div class="d-flex gap-2">
                            <select class="form-control" id="goal-select">
                              <option value="">Add goal...</option>
                              <?php foreach ($goals as $goal): ?>
                                <option value="<?= (int) $goal[
                                  'goal_id'
                                ] ?>" data-label="<?= htmlspecialchars($goal['goal_name']) ?>">
                                  <?= htmlspecialchars($goal['goal_name']) ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-dark" id="add-goal" type="button">Add</button>
                          </div>
                          <div id="goal-tags" class="d-flex flex-wrap gap-2 mt-2">
                            <?php foreach ($selectedGoalIds as $goalId): ?>
                              <?php
                              $goalName = '';
                              foreach ($goals as $goal) {
                                if ((int) $goal['goal_id'] === (int) $goalId) {
                                  $goalName = (string) $goal['goal_name'];
                                  break;
                                }
                              }
                              if ($goalName === '') {
                                continue;
                              }
                              ?>
                              <span class="badge bg-light text-dark border p-2 d-inline-flex align-items-center gap-2" data-id="<?= (int) $goalId ?>">
                                <?= htmlspecialchars($goalName) ?>
                                <button type="button" class="btn-close btn-sm" aria-label="Remove"></button>
                                <input type="hidden" name="goal_ids[]" value="<?= (int) $goalId ?>" />
                              </span>
                            <?php endforeach; ?>
                          </div>
                        </div>

                        <div>
                          <label class="form-label" for="exercise-select">Exercises</label>
                          <div class="d-flex gap-2">
                            <select class="form-control" id="exercise-select">
                              <option value="">Add exercise...</option>
                              <?php foreach ($exercises as $exercise): ?>
                                <option value="<?= (int) $exercise[
                                  'exercise_id'
                                ] ?>" data-label="<?= htmlspecialchars(
  $exercise['exercise_name'],
) ?>">
                                  <?= htmlspecialchars($exercise['exercise_name']) ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-dark" id="add-exercise" type="button">Add</button>
                          </div>
                          <div id="exercise-tags" class="d-flex flex-wrap gap-2 mt-2">
                            <?php foreach ($selectedExerciseIds as $exerciseId): ?>
                              <?php
                              $exerciseName = '';
                              foreach ($exercises as $exercise) {
                                if ((int) $exercise['exercise_id'] === (int) $exerciseId) {
                                  $exerciseName = (string) $exercise['exercise_name'];
                                  break;
                                }
                              }
                              if ($exerciseName === '') {
                                continue;
                              }
                              ?>
                              <span class="badge bg-light text-dark border p-2 d-inline-flex align-items-center gap-2" data-id="<?= (int) $exerciseId ?>">
                                <?= htmlspecialchars($exerciseName) ?>
                                <button type="button" class="btn-close btn-sm" aria-label="Remove"></button>
                                <input type="hidden" name="exercise_ids[]" value="<?= (int) $exerciseId ?>" />
                              </span>
                            <?php endforeach; ?>
                          </div>
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
        <script>
          function createTag(tagContainer, inputName, id, label) {
            if (!id) {
              return;
            }

            if (tagContainer.querySelector(`[data-id="${id}"]`)) {
              return;
            }

            const tag = document.createElement('span');
            tag.className = 'badge bg-light text-dark border p-2 d-inline-flex align-items-center gap-2';
            tag.setAttribute('data-id', id);

            const textNode = document.createTextNode(label);
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'btn-close btn-sm';
            removeButton.setAttribute('aria-label', 'Remove');
            removeButton.addEventListener('click', () => {
              tag.remove();
            });

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = inputName;
            hidden.value = id;

            tag.appendChild(textNode);
            tag.appendChild(removeButton);
            tag.appendChild(hidden);
            tagContainer.appendChild(tag);
          }

          const goalSelect = document.getElementById('goal-select');
          const addGoalButton = document.getElementById('add-goal');
          const goalTags = document.getElementById('goal-tags');

          addGoalButton.addEventListener('click', () => {
            const selectedOption = goalSelect.options[goalSelect.selectedIndex];
            const id = selectedOption?.value ?? '';
            const label = selectedOption?.textContent?.trim() ?? '';
            createTag(goalTags, 'goal_ids[]', id, label);
            goalSelect.value = '';
          });

          const exerciseSelect = document.getElementById('exercise-select');
          const addExerciseButton = document.getElementById('add-exercise');
          const exerciseTags = document.getElementById('exercise-tags');

          addExerciseButton.addEventListener('click', () => {
            const selectedOption = exerciseSelect.options[exerciseSelect.selectedIndex];
            const id = selectedOption?.value ?? '';
            const label = selectedOption?.textContent?.trim() ?? '';
            createTag(exerciseTags, 'exercise_ids[]', id, label);
            exerciseSelect.value = '';
          });

          document.querySelectorAll('#goal-tags .btn-close, #exercise-tags .btn-close').forEach((button) => {
            button.addEventListener('click', () => {
              button.closest('[data-id]')?.remove();
            });
          });
        </script>
    </body>
</html>
