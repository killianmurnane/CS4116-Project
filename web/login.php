<?php
require __DIR__ . '/helpers/init.php';
require_once __DIR__ . '/database/repository/LocationsRepository.php';

$activeForm = $_GET['activeForm'] ?? 'login';
$success = isset($_GET['success']) ? $_GET['success'] === '1' : null;
$locationsRepository = new LocationsRepository($pdo);
$locations = $locationsRepository->getAllLocations();

if (isset($_GET['error'])) {
  if ($_GET['error'] === 'unauthorized') {
    $unauthorized = true;
  } else {
    $error = match ($_GET['error']) {
      'empty_fields' => 'Please fill in all fields.',
      'invalid_credentials' => 'Invalid email or password.',
      'account_banned' => 'This account has been banned.',
      'invalid_email' => 'Please enter a valid email address.',
      'invalid_gender' => 'Please select a valid gender.',
      'password_length' => 'Password must be at least 8 characters long.',
      'passwords_match' => 'Passwords do not match.',
      'invalid_dob' => 'Invalid date of birth.',
      'underage' => 'You must be at least 18 years old to register.',
      'email_exists' => 'An account with this email already exists.',
      default => 'An unknown error occurred.',
    };
  }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
        <link rel="stylesheet" href="/css/base.css" />
        <link rel="stylesheet" href="/css/login.css" />
        <title>GymDate</title>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    </head>
    <body class="login-page">
        <?php if (isset($unauthorized) && $unauthorized): ?>
            <div class="alert alert-warning login-banner" role="alert">
                You must be logged in to access that page.
            </div>
        <?php endif; ?>
        <main class="login-shell">
            <section class="login-card">
                <div class="login-header">
                    <h1 class="login-title">GymDate</h1>
                    <p class="login-subtitle">Log in to continue or create an account to get started.</p>
                </div>

                <div class="login-toggle" role="tablist" aria-label="Login and register toggle">
                    <button id="show-login" class="login-toggle-button login-toggle-button-active" type="button">Login</button>
                    <button id="show-register" class="login-toggle-button" type="button">Register</button>
                </div>

                                <?php if (isset($error) && $error): ?>
                                        <div class="alert alert-danger" role="alert"><?= e(
                                          $error,
                                        ) ?></div>
                <?php endif; ?>

                <?php if (isset($success) && $success == true): ?>
                    <div class="alert alert-success" role="alert">
                      Account created successfully! Please log in.
                    </div>
                <?php elseif (isset($success) && $success == false): ?>
                    <div class="alert alert-danger" role="alert">
                      Account creation failed. Please try again.
                    </div>
                <?php endif; ?>

                <form id="login-form" class="login-form" method="post" action="/helpers/auth.php">
                    <input type="hidden" name="action" value="login">

                    <div class="login-field">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control login-input" id="email" name="email" type="email" required>
                    </div>

                    <div class="login-field">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control login-input" id="password" name="password" type="password" required>
                    </div>

                    <button class="btn btn-dark login-submit" type="submit">Log In</button>
                </form>

                <form id="register-form" class="login-form" method="post" action="/helpers/auth.php" style="display: none;">
                    <input type="hidden" name="action" value="register">

                    <div class="login-field">
                        <label class="form-label" for="register_email">Email</label>
                        <input class="form-control login-input" id="register_email" name="register_email" type="email" required>
                    </div>

                    <div class="login-field-grid">
                        <div class="login-field">
                            <label class="form-label" for="given_name">First Name</label>
                            <input class="form-control login-input" id="given_name" name="given_name" type="text" required>
                        </div>

                        <div class="login-field">
                            <label class="form-label" for="family_name">Last Name</label>
                            <input class="form-control login-input" id="family_name" name="family_name" type="text" required>
                        </div>
                    </div>

                    <div class="login-field">
                        <label class="form-label" for="gender">Gender</label>
                        <select class="form-control login-input" id="gender" name="gender" required>
                            <option value="" selected disabled>Select gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="login-field">
                        <label class="form-label" for="dob">Date of Birth</label>
                        <input class="form-control login-input" id="dob" name="dob" type="date" required>
                    </div>
                    <span class="register-requirements">You must be at least 18 years old to register.</span>

                    <div class="login-field">
                        <label class="form-label" for="location">Location</label>
                        <select class="form-control login-input" id="location" name="location" required>
                            <option value="" selected disabled>Select county</option>
                            <?php foreach ($locations as $location): ?>
                                                                <option value="<?= e(
                                                                  $location['id'] ?? '',
                                                                ) ?>"><?= e(
  $location['location'] ?? '',
) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="login-field-grid">
                        <div class="login-field">
                            <label class="form-label" for="register_password">Password</label>
                            <input class="form-control login-input" id="register_password" name="register_password" type="password" minlength="8" required>
                        </div>

                        <div class="login-field">
                            <label class="form-label" for="confirm_password">Confirm Password</label>
                            <input class="form-control login-input" id="confirm_password" name="confirm_password" type="password" minlength="8" required>
                        </div>
                        <span class="register-requirements">Password must be at least 8 characters long.</span>
                    </div>
                    <button class="btn btn-dark login-submit" type="submit">Create Account</button>
                </form>
            </section>

            <section class="login-info-panel">
                <h2 class="login-info-title">Welcome back</h2>
                <p class="login-info-copy">GymDate helps you find training partners, stay consistent, and keep your week organised.</p>

                <div class="login-info-list">
                    <div class="login-info-item">Find partners by goal and availability</div>
                    <div class="login-info-item">Message and plan sessions quickly</div>
                    <div class="login-info-item">Keep your training routine on track</div>
                </div>
            </section>
        </main>

        <script>
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            const showLoginButton = document.getElementById('show-login');
            const showRegisterButton = document.getElementById('show-register');
            const activeForm = <?= json_encode($activeForm ?? 'login') ?>;

            function showLogin() {
                loginForm.style.display = 'flex';
                registerForm.style.display = 'none';
                showLoginButton.classList.add('login-toggle-button-active');
                showRegisterButton.classList.remove('login-toggle-button-active');
            }

            function showRegister() {
                loginForm.style.display = 'none';
                registerForm.style.display = 'flex';
                showRegisterButton.classList.add('login-toggle-button-active');
                showLoginButton.classList.remove('login-toggle-button-active');
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