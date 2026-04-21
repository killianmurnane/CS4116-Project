<?php
require __DIR__ . '/helpers/init.php';
require __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/database/repository/UserRepository.php';
require_once __DIR__ . '/database/repository/ProfileRepository.php';
require_once __DIR__ . '/database/repository/LocationsRepository.php';
require_once __DIR__ . '/database/repository/ReportsRepository.php';

requireAdmin();

$userRepository = new UserRepository($pdo);
$profileRepository = new ProfileRepository($pdo);
$locationsRepository = new LocationsRepository($pdo);
$reportsRepository = new ReportsRepository($pdo);
$locations = $locationsRepository->getAllLocations();
$locationIds = array_map(static fn($row) => (int) $row['id'], $locations);

$searchTerm = trim((string) ($_POST['search_q'] ?? ($_GET['q'] ?? '')));
$selectedUserId = isset($_POST['selected_user_id'])
  ? (int) $_POST['selected_user_id']
  : (isset($_GET['user_id'])
    ? (int) $_GET['user_id']
    : 0);

$flash = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string) ($_POST['action'] ?? '');

  try {
    if ($action === 'update_user_status') {
      $targetUserId = (int) ($_POST['user_id'] ?? 0);
      $selectedUserId = $targetUserId > 0 ? $targetUserId : $selectedUserId;
      $status = (string) ($_POST['status'] ?? '');

      if ($targetUserId > 0 && $targetUserId !== (int) $_SESSION['user_id']) {
        $type = match ($status) {
          Type::ADMIN->value => Type::ADMIN,
          Type::STANDARD->value => Type::STANDARD,
          Type::BANNED->value => Type::BANNED,
          default => null,
        };

        if ($type !== null) {
          $userRepository->updateUser($targetUserId, null, null, $type);
          $flash = 'User role updated.';
        }
      }
    } elseif ($action === 'update_profile') {
      $targetUserId = (int) ($_POST['user_id'] ?? 0);
      $selectedUserId = $targetUserId > 0 ? $targetUserId : $selectedUserId;
      $givenName = trim((string) ($_POST['given_name'] ?? ''));
      $familyName = trim((string) ($_POST['family_name'] ?? ''));
      $gender = trim((string) ($_POST['gender'] ?? ''));
      $location =
        isset($_POST['location']) && $_POST['location'] !== '' ? (int) $_POST['location'] : null;
      $dobInput = trim((string) ($_POST['dob'] ?? ''));
      $description = trim((string) ($_POST['description'] ?? ''));
      $preferredSessions = trim((string) ($_POST['preferred_sessions'] ?? ''));

      if ($targetUserId > 0 && $givenName !== '') {
        $dob = null;
        if ($dobInput !== '') {
          $dob = DateTime::createFromFormat('Y-m-d', $dobInput) ?: null;
        }

        if ($location !== null && !in_array($location, $locationIds, true)) {
          throw new RuntimeException('Invalid location selected.');
        }

        $profileRepository->updateProfile(
          $targetUserId,
          $givenName,
          $familyName !== '' ? $familyName : null,
          $gender !== '' ? $gender : null,
          $location !== null ? (string) $location : null,
          $dob,
          $description !== '' ? $description : null,
          $preferredSessions !== '' ? $preferredSessions : null,
        );
        $flash = 'Profile updated.';
      }
    } elseif ($action === 'clear_profile_description') {
      $targetUserId = (int) ($_POST['user_id'] ?? 0);
      $selectedUserId = $targetUserId > 0 ? $targetUserId : $selectedUserId;
      $targetProfile = $profileRepository->findById($targetUserId);
      if ($targetProfile) {
        $dob = !empty($targetProfile['dob']) ? new DateTime((string) $targetProfile['dob']) : null;
        $profileRepository->updateProfile(
          $targetUserId,
          (string) ($targetProfile['given_name'] ?? ''),
          isset($targetProfile['family_name']) ? (string) $targetProfile['family_name'] : null,
          isset($targetProfile['gender']) ? (string) $targetProfile['gender'] : null,
          isset($targetProfile['location']) ? (string) $targetProfile['location'] : null,
          $dob,
          null,
          isset($targetProfile['preferred_sessions'])
            ? (string) $targetProfile['preferred_sessions']
            : null,
        );
        $flash = 'Profile description removed.';
      }
    } elseif ($action === 'delete_message') {
      $messageId = (int) ($_POST['message_id'] ?? 0);
      $targetUserId = (int) ($_POST['user_id'] ?? 0);
      $selectedUserId = $targetUserId > 0 ? $targetUserId : $selectedUserId;
      if ($messageId > 0) {
        $stmt = $pdo->prepare('DELETE FROM messages WHERE message_id = :message_id');
        $stmt->execute(['message_id' => $messageId]);
        $flash = 'Message removed.';
      }
    } elseif ($action === 'review_report') {
      $reportId = (int) ($_POST['report_id'] ?? 0);
      if ($reportId > 0) {
        $reportsRepository->reviewReport($reportId);
        $flash = 'Report marked as reviewed.';
      }
    }
  } catch (Throwable $exception) {
    $error = 'Action failed. Please try again.';
  }
}

$unreviewedReports = $reportsRepository->getUnreviewedReports(20) ?? [];

$resolveProfileDisplayName = static function (int $userId) use (
  $profileRepository,
  $userRepository,
): string {
  $profile = $profileRepository->findById($userId);
  if ($profile !== null) {
    $name = trim((string) (($profile['given_name'] ?? '') . ' ' . ($profile['family_name'] ?? '')));
    if ($name !== '') {
      return $name;
    }
  }

  $user = $userRepository->findById($userId);
  if ($user !== null && !empty($user['email'])) {
    return (string) $user['email'];
  }

  return 'User #' . $userId;
};

$profileNameCache = [];
$getProfileName = static function (int $userId) use (
  &$profileNameCache,
  $resolveProfileDisplayName,
): string {
  if (!array_key_exists($userId, $profileNameCache)) {
    $profileNameCache[$userId] = $resolveProfileDisplayName($userId);
  }

  return $profileNameCache[$userId];
};

$usersSql = 'SELECT u.user_id, u.email, u.type, u.created_at, p.given_name, p.family_name, p.gender, p.location, p.dob, p.description, p.preferred_sessions, l.location AS location_name
   FROM users u
   LEFT JOIN profiles p ON p.user_id = u.user_id
   LEFT JOIN locations l ON l.id = p.location';
$usersParams = [];

if ($searchTerm !== '') {
  $usersSql .=
    ' WHERE u.email LIKE :search_email OR CONCAT(TRIM(COALESCE(p.given_name, "")), " ", TRIM(COALESCE(p.family_name, ""))) LIKE :search_name';
  $usersParams['search_email'] = '%' . $searchTerm . '%';
  $usersParams['search_name'] = '%' . $searchTerm . '%';
}

$usersSql .= ' ORDER BY u.created_at DESC';
$usersStmt = $pdo->prepare($usersSql);
$usersStmt->execute($usersParams);
$users = $usersStmt->fetchAll();

if ($selectedUserId <= 0 && !empty($users)) {
  $selectedUserId = (int) $users[0]['user_id'];
}

$selectedUser = null;
foreach ($users as $listedUser) {
  if ((int) $listedUser['user_id'] === $selectedUserId) {
    $selectedUser = $listedUser;
    break;
  }
}

$selectedUserMessages = [];
if ($selectedUser !== null) {
  $messagesStmt = $pdo->prepare(
    'SELECT m.message_id, m.message_text, m.created_at, m.match_id
     FROM messages m
     WHERE m.sender_id = :user_id
     ORDER BY m.created_at DESC
     LIMIT 12',
  );
  $messagesStmt->execute(['user_id' => (int) $selectedUser['user_id']]);
  $selectedUserMessages = $messagesStmt->fetchAll();
}

$buildAdminUrl = function (array $params = []) use ($searchTerm, $selectedUserId): string {
  $query = [];
  if ($searchTerm !== '') {
    $query['q'] = $searchTerm;
  }
  if ($selectedUserId > 0) {
    $query['user_id'] = $selectedUserId;
  }
  foreach ($params as $key => $value) {
    if ($value === null || $value === '') {
      unset($query[$key]);
    } else {
      $query[$key] = $value;
    }
  }
  return '/admin.php' . (!empty($query) ? '?' . http_build_query($query) : '');
};
?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
        <link rel="stylesheet" href="/css/base.css" />
        <link rel="stylesheet" href="/css/admin.css" />
        <title>Admin Dashboard - GymDate</title>
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
                    <a class="menu-item" href="/profile.php">Profile</a>
                    <a class="menu-item" href="/support.php">Support</a>
                    <a class="menu-item active" href="#">Admin</a>
                    <a class="menu-item" href="/helpers/auth.php?action=logout">Logout</a>
                </nav>
            </aside>

            <main class="content">
                <?php if ($flash !== null): ?>
                    <div class="alert alert-success" role="alert"><?= htmlspecialchars(
                      $flash,
                    ) ?></div>
                <?php endif; ?>
                <?php if ($error !== null): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars(
                      $error,
                    ) ?></div>
                <?php endif; ?>

                <section class="top-rectangle p-4">
                    <div class="w-100">
                        <p class="text-uppercase text-muted mb-1 small">Admin Dashboard</p>
                        <h3 class="mb-2">Search and manage user accounts</h3>
                        <p class="text-muted mb-3">Look up users by email or full name, then open their record to update role, edit profile details, or remove inappropriate content.</p>
                        <form method="GET" action="/admin.php" class="row g-2 align-items-center">
                            <div class="col-md-8">
                                <input class="form-control" type="text" name="q" placeholder="Search by email or full name..." value="<?= htmlspecialchars(
                                  $searchTerm,
                                ) ?>" />
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-dark w-100" type="submit">Search</button>
                            </div>
                            <div class="col-md-2">
                                <a class="btn btn-outline-dark w-100" href="/admin.php">Clear</a>
                            </div>
                        </form>
                    </div>
                </section>

                  <section class="split-panel p-4 d-flex flex-column align-items-start justify-content-start reports-card" style="flex: 0 0 auto;">
                    <div class="d-flex justify-content-between align-items-center w-100 mb-3">
                      <h5 class="mb-0">Unreviewed Reports</h5>
                      <span class="text-muted small"><?= count($unreviewedReports) ?> pending</span>
                    </div>

                    <?php if (empty($unreviewedReports)): ?>
                      <p class="text-muted mb-0">No unreviewed reports right now.</p>
                    <?php else: ?>
                      <div class="reports-list">
                        <?php foreach ($unreviewedReports as $report): ?>
                          <?php
                          $reporterId = (int) ($report['reporter_id'] ?? 0);
                          $reportedId = (int) ($report['reported_id'] ?? 0);
                          $reportId = (int) ($report['report_id'] ?? 0);
                          ?>
                          <article class="report-item">
                            <div class="report-head">
                              <div class="report-users">
                                <span class="text-muted small">Reporter</span>
                                <a class="reports-user-link" href="/admin.php?user_id=<?= $reporterId ?>">
                                  <?= htmlspecialchars($getProfileName($reporterId)) ?>
                                </a>
                                <span class="text-muted">→</span>
                                <span class="text-muted small">Reported</span>
                                <a class="reports-user-link" href="/admin.php?user_id=<?= $reportedId ?>">
                                  <?= htmlspecialchars($getProfileName($reportedId)) ?>
                                </a>
                              </div>
                              <div class="text-muted small"><?= htmlspecialchars(
                                (string) ($report['created_at'] ?? ''),
                              ) ?></div>
                            </div>

                            <div class="mb-2">
                              <span class="badge bg-secondary">Reason: <?= htmlspecialchars(
                                (string) ($report['reason'] ?? ''),
                              ) ?></span>
                            </div>

                            <div class="report-content">
                              <div>
                                <p class="small text-muted mb-1">Report Message</p>
                                <div class="reports-scrollbox"><?= htmlspecialchars(
                                  (string) ($report['message'] ?? ''),
                                ) ?></div>
                              </div>
                              <div>
                                <p class="small text-muted mb-1">AI Overview</p>
                                <div class="reports-scrollbox"><?= htmlspecialchars(
                                  (string) ($report['ai_overview'] ?? ''),
                                ) ?></div>
                              </div>
                            </div>

                            <form method="POST" class="d-flex justify-content-end m-0">
                              <input type="hidden" name="action" value="review_report" />
                              <input type="hidden" name="report_id" value="<?= $reportId ?>" />
                              <input type="hidden" name="selected_user_id" value="<?= (int) $selectedUserId ?>" />
                              <input type="hidden" name="search_q" value="<?= htmlspecialchars(
                                $searchTerm,
                              ) ?>" />
                              <button class="btn btn-sm btn-outline-success" type="submit">Mark Reviewed</button>
                            </form>
                          </article>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </section>

                <section class="bottom-split">
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <div class="d-flex justify-content-between align-items-center w-100 mb-3">
                            <h5 class="mb-0">Users</h5>
                            <span class="text-muted small"><?= count($users) ?> found</span>
                        </div>

                        <?php if (empty($users)): ?>
                            <p class="text-muted mb-0">No users match your search.</p>
                        <?php else: ?>
                            <div class="w-100 d-flex flex-column gap-2">
                                <?php foreach ($users as $listedUser): ?>
                                    <?php
                                    $listedName = trim(
                                      (string) (($listedUser['given_name'] ?? '') .
                                        ' ' .
                                        ($listedUser['family_name'] ?? '')),
                                    );
                                    if ($listedName === '') {
                                      $listedName = (string) $listedUser['email'];
                                    }
                                    $isSelected =
                                      (int) $listedUser['user_id'] === (int) $selectedUserId;
                                    $statusClass =
                                      ($listedUser['type'] ?? '') === 'banned'
                                        ? 'bg-danger'
                                        : (($listedUser['type'] ?? '') === 'admin'
                                          ? 'bg-dark'
                                          : 'bg-secondary');
                                    ?>
                                    <a class="border rounded p-3 text-decoration-none <?= $isSelected
                                      ? 'border-dark bg-light'
                                      : 'bg-white text-dark' ?>" href="<?= htmlspecialchars(
  $buildAdminUrl(['user_id' => (int) $listedUser['user_id']]),
) ?>">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div>
                                                <strong><?= htmlspecialchars(
                                                  $listedName,
                                                ) ?></strong>
                                                <div class="text-muted small"><?= htmlspecialchars(
                                                  (string) $listedUser['email'],
                                                ) ?></div>
                                                <?php if (!empty($listedUser['location_name'])): ?>
                                                    <div class="small text-muted"><?= htmlspecialchars(
                                                      (string) $listedUser['location_name'],
                                                    ) ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <span class="badge <?= $statusClass ?> p-2"><?= htmlspecialchars(
   (string) $listedUser['type'],
 ) ?></span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <?php if ($selectedUser === null): ?>
                            <h5 class="mb-3">User Details</h5>
                            <p class="text-muted mb-0">Select a user from the list to manage their account.</p>
                        <?php else: ?>
                            <?php
                            $selectedName = trim(
                              (string) (($selectedUser['given_name'] ?? '') .
                                ' ' .
                                ($selectedUser['family_name'] ?? '')),
                            );
                            if ($selectedName === '') {
                              $selectedName = (string) $selectedUser['email'];
                            }
                            $selectedStatusClass =
                              ($selectedUser['type'] ?? '') === 'banned'
                                ? 'bg-danger'
                                : (($selectedUser['type'] ?? '') === 'admin'
                                  ? 'bg-dark'
                                  : 'bg-secondary');
                            ?>
                            <div class="d-flex justify-content-between align-items-start w-100 mb-3">
                                <div>
                                    <h5 class="mb-1"><?= htmlspecialchars($selectedName) ?></h5>
                                    <div class="text-muted small"><?= htmlspecialchars(
                                      (string) $selectedUser['email'],
                                    ) ?></div>
                                </div>
                                <span class="badge <?= $selectedStatusClass ?> p-2"><?= htmlspecialchars(
   (string) $selectedUser['type'],
 ) ?></span>
                            </div>

                            <form method="POST" class="row g-2 mb-4 w-100">
                                <input type="hidden" name="action" value="update_user_status" />
                                <input type="hidden" name="user_id" value="<?= (int) $selectedUser[
                                  'user_id'
                                ] ?>" />
                                <input type="hidden" name="selected_user_id" value="<?= (int) $selectedUser[
                                  'user_id'
                                ] ?>" />
                                <input type="hidden" name="search_q" value="<?= htmlspecialchars(
                                  $searchTerm,
                                ) ?>" />
                                <div class="col-md-8">
                                    <select class="form-select" name="status" <?= (int) $selectedUser[
                                      'user_id'
                                    ] === (int) $_SESSION['user_id']
                                      ? 'disabled'
                                      : '' ?>>
                                        <option value="standard" <?= ($selectedUser['type'] ??
                                          '') ===
                                        'standard'
                                          ? 'selected'
                                          : '' ?>>Standard</option>
                                        <option value="admin" <?= ($selectedUser['type'] ?? '') ===
                                        'admin'
                                          ? 'selected'
                                          : '' ?>>Admin</option>
                                        <option value="banned" <?= ($selectedUser['type'] ?? '') ===
                                        'banned'
                                          ? 'selected'
                                          : '' ?>>Banned</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-outline-dark w-100" type="submit" <?= (int) $selectedUser[
                                      'user_id'
                                    ] === (int) $_SESSION['user_id']
                                      ? 'disabled'
                                      : '' ?>>Update Role</button>
                                </div>
                            </form>

                            <form method="POST" class="row g-2 w-100 mb-4">
                                <input type="hidden" name="action" value="update_profile" />
                                <input type="hidden" name="user_id" value="<?= (int) $selectedUser[
                                  'user_id'
                                ] ?>" />
                                <input type="hidden" name="selected_user_id" value="<?= (int) $selectedUser[
                                  'user_id'
                                ] ?>" />
                                <input type="hidden" name="search_q" value="<?= htmlspecialchars(
                                  $searchTerm,
                                ) ?>" />
                                <div class="col-md-6">
                                    <label class="form-label">Given Name</label>
                                    <input class="form-control" name="given_name" type="text" value="<?= htmlspecialchars(
                                      (string) ($selectedUser['given_name'] ?? ''),
                                    ) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Family Name</label>
                                    <input class="form-control" name="family_name" type="text" value="<?= htmlspecialchars(
                                      (string) ($selectedUser['family_name'] ?? ''),
                                    ) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" name="gender">
                                        <option value="">Gender</option>
                                        <option value="male" <?= ($selectedUser['gender'] ?? '') ===
                                        'male'
                                          ? 'selected'
                                          : '' ?>>Male</option>
                                        <option value="female" <?= ($selectedUser['gender'] ??
                                          '') ===
                                        'female'
                                          ? 'selected'
                                          : '' ?>>Female</option>
                                        <option value="other" <?= ($selectedUser['gender'] ??
                                          '') ===
                                        'other'
                                          ? 'selected'
                                          : '' ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Location</label>
                                    <select class="form-select" name="location">
                                        <option value="">Location</option>
                                        <?php foreach ($locations as $location): ?>
                                            <option value="<?= (int) $location[
                                              'id'
                                            ] ?>" <?= (int) ($selectedUser['location'] ?? 0) ===
(int) $location['id']
  ? 'selected'
  : '' ?>><?= htmlspecialchars((string) $location['location']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date of Birth</label>
                                    <input class="form-control" name="dob" type="date" value="<?= htmlspecialchars(
                                      (string) ($selectedUser['dob'] ?? ''),
                                    ) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Preferred Sessions</label>
                                    <input class="form-control" name="preferred_sessions" type="text" value="<?= htmlspecialchars(
                                      (string) ($selectedUser['preferred_sessions'] ?? ''),
                                    ) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Profile Description</label>
                                    <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars(
                                      (string) ($selectedUser['description'] ?? ''),
                                    ) ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-dark w-100" type="submit">Save Profile</button>
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-outline-danger w-100" type="submit" onclick="this.form.action.value='clear_profile_description'">Clear Description</button>
                                </div>
                            </form>

                            <div class="border rounded p-3 bg-white w-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Recent Messages</h6>
                                    <span class="text-muted small"><?= count(
                                      $selectedUserMessages,
                                    ) ?> shown</span>
                                </div>
                                <?php if (empty($selectedUserMessages)): ?>
                                    <p class="text-muted mb-0">No recent messages from this user.</p>
                                <?php else: ?>
                                    <div class="d-flex flex-column gap-2">
                                        <?php foreach ($selectedUserMessages as $message): ?>
                                            <div class="border rounded p-2 bg-light">
                                                <div class="small text-muted mb-1">Match #<?= (int) $message[
                                                  'match_id'
                                                ] ?> • <?= htmlspecialchars(
   (string) ($message['created_at'] ?? ''),
 ) ?></div>
                                                <div class="mb-2"><?= nl2br(
                                                  htmlspecialchars(
                                                    (string) ($message['message_text'] ?? ''),
                                                  ),
                                                ) ?></div>
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="delete_message" />
                                                    <input type="hidden" name="message_id" value="<?= (int) $message[
                                                      'message_id'
                                                    ] ?>" />
                                                    <input type="hidden" name="user_id" value="<?= (int) $selectedUser[
                                                      'user_id'
                                                    ] ?>" />
                                                    <input type="hidden" name="selected_user_id" value="<?= (int) $selectedUser[
                                                      'user_id'
                                                    ] ?>" />
                                                    <input type="hidden" name="search_q" value="<?= htmlspecialchars(
                                                      $searchTerm,
                                                    ) ?>" />
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Remove Message</button>
                                                </form>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
