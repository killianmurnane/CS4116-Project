<?php
require __DIR__ . '/helpers/init.php';
require __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/database/repository/LikesRepository.php';
require_once __DIR__ . '/database/repository/MatchesRepository.php';
require_once __DIR__ . '/database/repository/LocationsRepository.php';
require_once __DIR__ . '/database/repository/UserRepository.php';
require_once __DIR__ . '/database/repository/GoalsRepository.php';
require_once __DIR__ . '/database/repository/ExercisesRepository.php';
requireLogin();

// For pagination on results
$limit = 5;
$offset = isset($_GET['page']) ? (int) $_GET['page'] * $limit : 0;
$nextPage = isset($_GET['page']) ? (int) $_GET['page'] + 1 : 1;
$nextQueryParams = $_GET;
$nextQueryParams['page'] = $nextPage;
$loadMoreUrl = '/search.php?' . http_build_query($nextQueryParams);

// Fetch all goals & exercises for the dropdown
$goalsRepository = new GoalsRepository($pdo);
$goals = $goalsRepository->getAllGoals();

$exercisesRepository = new ExercisesRepository($pdo);
$exercises = $exercisesRepository->getAllExercises();

// Get filters from form
$selectedGoal = isset($_GET['goal']) ? (int) $_GET['goal'] : 0;
$searchName = trim((string) ($_GET['name'] ?? ''));
$minAge = isset($_GET['min_age']) && $_GET['min_age'] !== '' ? (int) $_GET['min_age'] : null;
$maxAge = isset($_GET['max_age']) && $_GET['max_age'] !== '' ? (int) $_GET['max_age'] : null;
$selectedLocation = isset($_GET['location']) ? (int) $_GET['location'] : 0;
$selectedGender =
  isset($_GET['gender']) && $_GET['gender'] !== '' ? trim((string) $_GET['gender']) : null;
$selectedExercise = isset($_GET['exercise']) ? (int) $_GET['exercise'] : 0;

$hasFilters =
  $selectedGoal > 0 ||
  $selectedLocation > 0 ||
  $searchName !== '' ||
  $minAge !== null ||
  $maxAge !== null ||
  $selectedGender !== null ||
  $selectedExercise > 0;

$userRepository = new UserRepository($pdo);
$users = [];

if ($hasFilters) {
  $users = $userRepository->filterUsers(
    null,
    $selectedGender,
    $minAge,
    $maxAge,
    $selectedLocation > 0 ? $selectedLocation : null,
    $selectedGoal > 0 ? $selectedGoal : null,
    $searchName !== '' ? $searchName : null,
    $limit,
    $offset,
    $selectedExercise > 0 ? $selectedExercise : null,
  );
} else {
  // Show all users with profiles if no filter selected
  $users = $userRepository->getUsers($limit, $offset);
}

// Fetch likes for user
$likesRepository = new LikesRepository($pdo);
$likedUserIds = [];
try {
  $likes = $likesRepository->getLikedUsers((int) $_SESSION['user_id']);
  if ($likes) {
    $likedUserIds = array_column($likes, 'liked_id');
  }
} catch (Exception $e) {
  // Log error and continue without likes
  error_log('Failed to fetch likes: ' . $e->getMessage());
}

// Fetch matches for user
$matchesRepository = new MatchesRepository($pdo);
$matches = [];
$matchedUserIds = [];
try {
  $matches = $matchesRepository->getMatches((int) $_SESSION['user_id']);
  if ($matches) {
    foreach ($matches as $match) {
      if ((int) $match['user1_id'] === (int) $_SESSION['user_id']) {
        $matchedUserIds[] = (int) $match['user2_id'];
      } else {
        $matchedUserIds[] = (int) $match['user1_id'];
      }
    }
  }
} catch (Exception $e) {
  // Log error and continue without matches
  error_log('Failed to fetch matches: ' . $e->getMessage());
}

// Fetch all locations for the dropdown
$locationsRepository = new LocationsRepository($pdo);
$locations = $locationsRepository->getAllLocations();

$goalNamesById = [];
foreach ($goals as $goal) {
  $goalNamesById[(int) $goal['goal_id']] = (string) $goal['goal_name'];
}

$locationNamesById = [];
foreach ($locations as $location) {
  $locationNamesById[(int) $location['id']] = (string) $location['location'];
}

$exerciseNamesById = [];
foreach ($exercises as $exercise) {
  $exerciseNamesById[(int) $exercise['exercise_id']] = (string) $exercise['exercise_name'];
}

$buildFilterUrl = function (array $removeKeys): string {
  $params = $_GET;
  unset($params['page']);

  foreach ($removeKeys as $key) {
    unset($params[$key]);
  }

  $query = http_build_query($params);
  return '/search.php' . ($query !== '' ? '?' . $query : '');
};

$activeFilters = [];
if ($searchName !== '') {
  $activeFilters[] = [
    'label' => 'Name: ' . $searchName,
    'remove_url' => $buildFilterUrl(['name']),
  ];
}
if ($selectedGoal > 0) {
  $activeFilters[] = [
    'label' => 'Goal: ' . ($goalNamesById[$selectedGoal] ?? 'Unknown'),
    'remove_url' => $buildFilterUrl(['goal']),
  ];
}
if ($selectedLocation > 0) {
  $activeFilters[] = [
    'label' => 'Location: ' . ($locationNamesById[$selectedLocation] ?? 'Unknown'),
    'remove_url' => $buildFilterUrl(['location']),
  ];
}
if ($selectedGender !== null) {
  $activeFilters[] = [
    'label' => 'Gender: ' . ucfirst($selectedGender),
    'remove_url' => $buildFilterUrl(['gender']),
  ];
}
if ($selectedExercise > 0) {
  $activeFilters[] = [
    'label' => 'Exercise: ' . ($exerciseNamesById[$selectedExercise] ?? 'Unknown'),
    'remove_url' => $buildFilterUrl(['exercise']),
  ];
}
if ($minAge !== null || $maxAge !== null) {
  $activeFilters[] = [
    'label' => 'Age: ' . ($minAge ?? '?') . ' - ' . ($maxAge ?? '?'),
    'remove_url' => $buildFilterUrl(['min_age', 'max_age']),
  ];
}

// Fetch query errors or success

if (isset($_GET['error'])) {
  $error = match ($_GET['error']) {
    'like_exists' => 'You have already liked this user.',
    'like_failed' => 'Failed to like the user. Please try again.',
    default => 'An unknown error occurred.',
  };
} elseif (isset($_GET['success'])) {
  $success = 'User liked successfully!';
} elseif (isset($_GET['match'])) {
  $success =
    'It\'s a match! This user has also liked you. You can view your matches in the Messages section.';
}
?>
<!DOCTYPE html>
<html>
    <head>
        <!-- CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
        <link rel="stylesheet" href="css/base.css" />
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
                    <a class="menu-item" href="/message.php">Messages</a>
                    <a class="menu-item active" href="#">Search</a>
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
                <?php elseif (isset($success)): ?>
                    <div class="alert alert-success login-banner" role="alert">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                <section class="top-rectangle p-4">
                    <div class="w-100">
                        <h3 class="mb-2">Find Training Partners</h3>
                        <p class="text-muted mb-3">Filter by name, goal, or age to find your perfect gym partner.</p>
                        <form method="GET" action="/search.php">
                            <div class="row g-2 justify-content-center">
                                <div class="col-md-3">
                                    <input
                                        type="text"
                                        class="form-control"
                                        name="name"
                                        placeholder="First name..."
                                        value="<?= htmlspecialchars($searchName) ?>"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="goal">
                                        <option value="0">Goal: Any</option>
                                        <?php foreach ($goals as $goal): ?>
                                            <option value="<?= $goal[
                                              'goal_id'
                                            ] ?>" <?= $selectedGoal === (int) $goal['goal_id']
  ? 'selected'
  : '' ?>>
                                                <?= htmlspecialchars($goal['goal_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="location">
                                        <option value="0">Location: Any</option>
                                        <?php foreach ($locations as $location): ?>
                                            <option value="<?= htmlspecialchars(
                                              $location['id'],
                                            ) ?>" <?= $selectedLocation === (int) $location['id']
  ? 'selected'
  : '' ?>>
                                                <?= htmlspecialchars($location['location']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="gender">
                                        <option value="">Gender: Any</option>
                                        <option value="male" <?= isset($selectedGender) &&
                                        $selectedGender === 'male'
                                          ? 'selected'
                                          : '' ?>>Male</option>
                                        <option value="female" <?= isset($selectedGender) &&
                                        $selectedGender === 'female'
                                          ? 'selected'
                                          : '' ?>>Female</option>
                                        <option value="other" <?= isset($selectedGender) &&
                                        $selectedGender === 'other'
                                          ? 'selected'
                                          : '' ?>>Other</option>
                                    </select>
                                </div>
                                    <div class="row g-2 justify-content-center mt-1">
                                    
                                    <div class="col-md-3">
                                    <select class="form-select" name="exercise">
                                        <option value="0">Exercise: Any</option>
                                        <?php foreach ($exercises as $exercise): ?>
                                            <option value="<?= $exercise[
                                              'exercise_id'
                                            ] ?>" <?= $selectedExercise ===
(int) $exercise['exercise_id']
  ? 'selected'
  : '' ?>>
                                                <?= htmlspecialchars($exercise['exercise_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                      <div class="col-md-2">
                                    <input
                                        type="number"
                                        class="form-control"
                                        name="min_age"
                                        placeholder="Min age"
                                        min="18"
                                        max="100"
                                        value="<?= $minAge !== null ? $minAge : '' ?>"
                                    />
                                </div>
                                <div class="col-md-2">
                                    <input
                                        type="number"
                                        class="form-control"
                                        name="max_age"
                                        placeholder="Max age"
                                        min="18"
                                        max="100"
                                        value="<?= $maxAge !== null ? $maxAge : '' ?>"
                                    />
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-dark w-100" type="submit">Search</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="bottom-split">
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">
                            Top <?= count($users) ?> Results
                        </h5>
                        <?php if (empty($users)): ?>
                            <p class="text-muted">No users found matching your filters.</p>
                        <?php else: ?>
                            <div class="w-100 d-flex flex-column gap-2">
                                <?php foreach ($users as $user): ?>
                                    <div class="border rounded p-3 bg-white">
                                        <strong>
                                            <?php
                                            $name = trim(
                                              ($user['given_name'] ?? '') .
                                                ' ' .
                                                ($user['family_name'] ?? ''),
                                            );
                                            echo $name !== ''
                                              ? htmlspecialchars($name)
                                              : 'User #' . $user['user_id'];
                                            ?>
                                        </strong>
                                        <?php if (!empty($user['age'])): ?>
                                            <span class="badge bg-secondary ms-2"><?= $user[
                                              'age'
                                            ] ?> yrs</span>
                                        <?php endif; ?>
                                        <div class="text-muted mt-1 d-flex justify-content-between align-items-center">
                                            <div>
                                                <?php if (!empty($user['location'])): ?>
                                                    📍 <?= htmlspecialchars($user['location']) ?>
                                                <?php endif; ?>
                                                <?php if (!empty($user['description'])): ?>
                                                    <p class="mb-0 mt-1 small"><?= htmlspecialchars(
                                                      substr($user['description'], 0, 100),
                                                    ) ?>...</p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (
                                                  in_array(
                                                    (int) $user['user_id'],
                                                    $matchedUserIds,
                                                    true,
                                                  )
                                                ):

                                                  $matchedId = array_search(
                                                    (int) $user['user_id'],
                                                    $matchedUserIds,
                                                    true,
                                                  );
                                                  if (
                                                    $matchedId !== false &&
                                                    isset($matches[$matchedId]['match_id'])
                                                  ) {
                                                    $matchId =
                                                      (int) $matches[$matchedId]['match_id'];
                                                    echo "<a href='/message.php?matchId={$matchId}' class='btn btn-sm btn-outline-success'>Message</a>";
                                                  }
                                                  ?>
                                            <?php
                                                endif; ?>
                                                <form method="POST" action="/profile.php">
                                                    <input type="hidden" name="id" value="<?= (int) $user[
                                                      'user_id'
                                                    ] ?>" />
                                                    <button type="submit" class="btn btn-sm btn-outline-dark">View Profile</button>
                                                </form>
                                                <?php if (
                                                  in_array($user['user_id'], $likedUserIds)
                                                ): ?>
                                                    <span class="badge bg-success p-2"><i class="fa-solid fa-check fa-xl"></i></span>
                                                <?php else: ?>
                                                    <form method="POST" action="/helpers/like.php">
                                                        <input type="hidden" name="liked_user_id" value="<?= $user[
                                                          'user_id'
                                                        ] ?>" />
                                                      <button type="submit" class="badge bg-danger p-2 border-0"><i class="fa-solid fa-heart fa-xl"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <a class="btn btn-dark mt-3" href="<?= htmlspecialchars(
                          $loadMoreUrl,
                        ) ?>">Load More</a>
                    </div>
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">Active Filters</h5>
                        <div class="w-100 d-flex flex-column gap-2">
                            <div class="border rounded p-2 bg-white">People found: <strong><?= count(
                              $users,
                            ) ?></strong></div>
                            <?php if (empty($activeFilters)): ?>
                                <div class="border rounded p-2 bg-white text-muted">No filters applied.</div>
                            <?php else: ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($activeFilters as $filter): ?>
                                        <a class="badge bg-light text-dark text-decoration-none border p-2" href="<?= htmlspecialchars(
                                          $filter['remove_url'],
                                        ) ?>">
                                            <?= htmlspecialchars($filter['label']) ?> ×
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                                <a class="btn btn-outline-dark btn-sm mt-2" href="/search.php">Clear all filters</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>