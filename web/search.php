<?php
require __DIR__ . '/helpers/init.php';
require __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/database/repository/LikesRepository.php';
requireLogin();

// Fetch all goals for the dropdown
$goalsStmt = $pdo->query('SELECT * FROM goals ORDER BY goal_name');
$goals = $goalsStmt->fetchAll();

// Get filters from form
$selectedGoal = isset($_GET['goal']) ? (int) $_GET['goal'] : 0;
$searchName = trim((string) ($_GET['name'] ?? ''));
$minAge = isset($_GET['min_age']) && $_GET['min_age'] !== '' ? (int) $_GET['min_age'] : null;
$maxAge = isset($_GET['max_age']) && $_GET['max_age'] !== '' ? (int) $_GET['max_age'] : null;

$hasFilters = $selectedGoal > 0 || $searchName !== '' || $minAge !== null || $maxAge !== null;

$users = [];

if ($hasFilters) {
  $conditions = ["u.type != 'banned'", 'p.user_id IS NOT NULL'];
  $params = [];

  if ($selectedGoal > 0) {
    $conditions[] = 'ug.goal_id = :goal_id';
    $params['goal_id'] = $selectedGoal;
  }

  if ($searchName !== '') {
    $conditions[] = 'p.given_name LIKE :name';
    $params['name'] = '%' . $searchName . '%';
  }

  if ($minAge !== null) {
    $conditions[] = 'TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) >= :min_age';
    $params['min_age'] = $minAge;
  }

  if ($maxAge !== null) {
    $conditions[] = 'TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) <= :max_age';
    $params['max_age'] = $maxAge;
  }

  $whereClause = implode(' AND ', $conditions);

  $joinType = $selectedGoal > 0 ? 'JOIN' : 'LEFT JOIN';

  $sql = "
        SELECT DISTINCT u.user_id, p.given_name, p.family_name, p.location, p.description, p.dob,
               TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) AS age
        FROM users u
        JOIN profiles p ON u.user_id = p.user_id
        {$joinType} user_goals ug ON u.user_id = ug.user_id
        LEFT JOIN goals g ON ug.goal_id = g.goal_id
        WHERE {$whereClause}
        ORDER BY u.created_at DESC
    ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $users = $stmt->fetchAll();
} else {
  // Show all users with profiles if no filter selected
  $stmt = $pdo->query("
        SELECT DISTINCT u.user_id, p.given_name, p.family_name, p.location, p.description, p.dob,
               TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) AS age
        FROM users u
        JOIN profiles p ON u.user_id = p.user_id
        WHERE u.type != 'banned' AND p.user_id IS NOT NULL AND u.user_id != {$_SESSION['user_id']}
        ORDER BY u.created_at DESC
    ");
  $users = $stmt->fetchAll();
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
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <input
                                        type="text"
                                        class="form-control"
                                        name="name"
                                        placeholder="Search by first name..."
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
                            <?= count($users) ?> People Found
                        </h5>
                        <?php if (empty($users)): ?>
                            <p class="text-muted">No users found matching your filters.</p>
                        <?php else: ?>
                            <div class="w-100 d-flex flex-column gap-2">
                                <?php foreach ($users as $user): ?>
                                    <a href="/profile.php/<?= $user[
                                      'user_id'
                                    ] ?>" class="text-decoration-none">
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
                                                        📍 <?= htmlspecialchars(
                                                          $user['location'],
                                                        ) ?>
                                                    <?php endif; ?>
                                                    <?php if (!empty($user['description'])): ?>
                                                        <p class="mb-0 mt-1 small"><?= htmlspecialchars(
                                                          substr($user['description'], 0, 100),
                                                        ) ?>...</p>
                                                    <?php endif; ?>
                                                </div>
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
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">Search Snapshot</h5>
                        <div class="w-100 d-flex flex-column gap-2">
                            <div class="border rounded p-2 bg-white">People found: <strong><?= count(
                              $users,
                            ) ?></strong></div>
                            <div class="border rounded p-2 bg-white">
                                Goal: <strong><?= $selectedGoal > 0
                                  ? htmlspecialchars(
                                    $goals[
                                      array_search($selectedGoal, array_column($goals, 'goal_id'))
                                    ]['goal_name'] ?? 'Unknown',
                                  )
                                  : 'Any' ?></strong>
                            </div>
                            <?php if ($minAge !== null || $maxAge !== null): ?>
                            <div class="border rounded p-2 bg-white">
                                Age: <strong><?= $minAge ?? '?' ?> – <?= $maxAge ?? '?' ?></strong>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>