<!DOCTYPE html>
<?php
require __DIR__ . '/helpers/init.php';

$activeForm = $_GET['activeForm'] ?? 'login';
$error = $_GET['error'] ?? null;
$unauthorized = $error === 'unauthorized';
$success = isset($_GET['success']) ? $_GET['success'] === '1' : null;
?>
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
        <?php if ($unauthorized): ?>
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

                <?php if (!empty($error) && !$unauthorized): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars(
                      $error,
                    ) ?></div>
                <?php endif; ?>

                <?php if (!empty($success) && $success == true): ?>
                    <div class="alert alert-success" role="alert">
                      Account created successfully! Please log in.
                    </div>
                <?php elseif (!empty($success) && $success == false): ?>
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

                    <?php if (!empty($registerError)): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars(
                          $registerError,
                        ) ?></div>
                    <?php endif; ?>

                    <div class="login-field">
                        <label class="form-label" for="register_email">Email</label>
                        <input class="form-control login-input" id="register_email" name="register_email" type="email" required>
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
                    </div>

                    <div class="login-field">
                        <label class="form-label" for="dob">Date of Birth</label>
                        <input class="form-control login-input" id="dob" name="dob" type="date">
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