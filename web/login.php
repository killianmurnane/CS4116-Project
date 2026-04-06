<?php

declare(strict_types=1);

$pdo = require __DIR__ . '/database/sql.php';

$loginError = null;
$registerError = null;
$registerSuccess = null;
$activeForm = 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'login';
  $activeForm = $action === 'register' ? 'register' : 'login';

  if ($action === 'login') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
      $loginError = 'Email and password are required.';
    } else {
      $stmt = $pdo->prepare(
        'SELECT user_id, email, password FROM users WHERE email = :email LIMIT 1',
      );
      $stmt->execute(['email' => $email]);
      $user = $stmt->fetch();

      if (!$user || !password_verify($password, (string) $user['password'])) {
        $loginError = 'Invalid email or password.';
      } else {
        if (session_status() === PHP_SESSION_NONE) {
          session_start();
        }

        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['user_email'] = (string) $user['email'];

        header('Location: /');
        exit();
      }
    }
  }

  if ($action === 'register') {
    $email = trim((string) ($_POST['register_email'] ?? ''));
    $password = (string) ($_POST['register_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $dobRaw = trim((string) ($_POST['dob'] ?? ''));

    if ($email === '' || $password === '' || $confirmPassword === '') {
      $registerError = 'Email, password and confirm password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $registerError = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
      $registerError = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
      $registerError = 'Passwords do not match.';
    } else {
      $checkStmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
      $checkStmt->execute(['email' => $email]);

      if ($checkStmt->fetch()) {
        $registerError = 'An account with this email already exists.';
      } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
          $pdo->beginTransaction();

          $insertUserStmt = $pdo->prepare(
            'INSERT INTO users (email, password, type, created_at) VALUES (:email, :password, :type, CURRENT_TIMESTAMP)',
          );
          $insertUserStmt->execute([
            'email' => $email,
            'password' => $hashedPassword,
            'type' => 'standard',
          ]);

          $newUserId = (int) $pdo->lastInsertId();

          if ($dobRaw !== '') {
            $dob = DateTime::createFromFormat('Y-m-d', $dobRaw);

            if ($dob !== false) {
              try {
                $insertProfileStmt = $pdo->prepare(
                  'INSERT INTO profiles (user_id, dob, created_at) VALUES (:user_id, :dob, CURRENT_TIMESTAMP)',
                );
                $insertProfileStmt->execute([
                  'user_id' => $newUserId,
                  'dob' => $dob->format('Y-m-d'),
                ]);
              } catch (Throwable $th) {
              }
            }
          }

          $pdo->commit();
          $registerSuccess = 'Registration successful. You can now log in.';
          $activeForm = 'login';
        } catch (Throwable $th) {
          if ($pdo->inTransaction()) {
            $pdo->rollBack();
          }

          $registerError = 'Unable to register account right now.';
        }
      }
    }
  }
}
?>
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
                    <a class="menu-item" href="/profile.php">Profile</a>
                    <a class="menu-item active" href="#">Login</a>
                </nav>
            </aside>

            <main class="content">
                <section class="top-rectangle p-4">
                    <div class="w-100">
                        <h3 class="mb-2">Welcome to GymDate</h3>
                        <p class="text-muted mb-3">Log in to your account or create a new one.</p>
                        <div class="d-flex gap-2">
                            <button id="show-login" class="btn btn-dark" type="button">Login</button>
                            <button id="show-register" class="btn btn-outline-dark" type="button">Register</button>
                        </div>
                    </div>
                </section>

                <section class="bottom-split">
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">Account Access</h5>

                        <?php if ($loginError !== null): ?>
                            <div class="alert alert-danger w-100" role="alert"><?= htmlspecialchars(
                              $loginError,
                            ) ?></div>
                        <?php endif; ?>

                        <?php if ($registerSuccess !== null): ?>
                            <div class="alert alert-success w-100" role="alert"><?= htmlspecialchars(
                              $registerSuccess,
                            ) ?></div>
                        <?php endif; ?>

                        <form id="login-form" class="w-100" method="post" action="/login.php">
                            <input type="hidden" name="action" value="login">

                            <div class="mb-3">
                                <label class="form-label" for="email">Email</label>
                                <input class="form-control" id="email" name="email" type="email" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="password">Password</label>
                                <input class="form-control" id="password" name="password" type="password" required>
                            </div>

                            <button class="btn btn-dark" type="submit">Log In</button>
                        </form>

                        <form id="register-form" class="w-100" method="post" action="/login.php" style="display: none;">
                            <input type="hidden" name="action" value="register">

                            <?php if ($registerError !== null): ?>
                                <div class="alert alert-danger w-100" role="alert"><?= htmlspecialchars(
                                  $registerError,
                                ) ?></div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label" for="register_email">Email</label>
                                <input class="form-control" id="register_email" name="register_email" type="email" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="register_password">Password</label>
                                <input class="form-control" id="register_password" name="register_password" type="password" minlength="8" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="confirm_password">Confirm Password</label>
                                <input class="form-control" id="confirm_password" name="confirm_password" type="password" minlength="8" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="dob">Date of Birth</label>
                                <input class="form-control" id="dob" name="dob" type="date">
                            </div>

                            <button class="btn btn-dark" type="submit">Create Account</button>
                        </form>
                    </div>

                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">Why Join?</h5>
                        <div class="w-100 d-flex flex-column gap-2">
                            <div class="border rounded p-2 bg-white">Find local gym partners</div>
                            <div class="border rounded p-2 bg-white">Message and plan sessions</div>
                            <div class="border rounded p-2 bg-white">Track your fitness consistency</div>
                        </div>
                    </div>
                </section>
            </main>
        </div>

        <script>
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            const showLoginButton = document.getElementById('show-login');
            const showRegisterButton = document.getElementById('show-register');
            const activeForm = <?= json_encode($activeForm) ?>;

            function showLogin() {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
                showLoginButton.classList.remove('btn-outline-dark');
                showLoginButton.classList.add('btn-dark');
                showRegisterButton.classList.remove('btn-dark');
                showRegisterButton.classList.add('btn-outline-dark');
            }

            function showRegister() {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
                showRegisterButton.classList.remove('btn-outline-dark');
                showRegisterButton.classList.add('btn-dark');
                showLoginButton.classList.remove('btn-dark');
                showLoginButton.classList.add('btn-outline-dark');
            }

            showLoginButton.addEventListener('click', showLogin);
            showRegisterButton.addEventListener('click', showRegister);

            if (activeForm === 'register') {
                showRegister();
            } else {
                showLogin();
            }
        </script>
    </body>
</html>