<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/../database/repository/ProfileRepository.php';
require_once __DIR__ . '/../database/repository/UserRepository.php';

function isLoggedIn(): bool
{
  return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
  if (!isLoggedIn()) {
    header('Location: /login.php?error=unauthorized');
    exit();
  }
}

function loginUser(array $user): void
{
  session_regenerate_id(true);
  $_SESSION['user_id'] = (int) $user['user_id'];
  $_SESSION['user_email'] = (string) $user['email'];
  $_SESSION['user_type'] = (string) $user['type'];
  $_SESSION['user_given'] = isset($user['given_name']) ? (string) $user['given_name'] : null;
}

function logoutUser(): void
{
  $_SESSION = [];

  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
      session_name(),
      '',
      time() - 42000,
      $params['path'],
      $params['domain'] ?? '',
      $params['secure'] ?? false,
      $params['httponly'] ?? true,
    );
  }

  session_destroy();
}

$runningAsEntrypoint = realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__;
if (!$runningAsEntrypoint) {
  return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $userRepository = new UserRepository($pdo);
  $action = $_POST['action'] ?? '';
  $params = [];

  if ($action === 'login') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
      $params['error'] = 'Email and password are required.';
    } else {
      $user = $userRepository->findByEmail($email);

      if ($user && password_verify($password, (string) $user['password'])) {
        loginUser($user);
        header('Location: /index.php');
        exit();
      }

      $params['error'] = 'Invalid email or password';
    }
  }

  if ($action === 'register') {
    $email = trim((string) ($_POST['register_email'] ?? ''));
    $password = (string) ($_POST['register_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $dobInput = (string) ($_POST['dob'] ?? '');

    if ($email === '' || $password === '' || $confirmPassword === '' || $dobInput === '') {
      $params['error'] = 'All register fields are required.';
      $params['activeForm'] = 'register';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $params['error'] = 'Please enter a valid email address.';
      $params['activeForm'] = 'register';
    } elseif (strlen($password) < 8) {
      $params['error'] = 'Password must be at least 8 characters long.';
      $params['activeForm'] = 'register';
    } elseif ($password !== $confirmPassword) {
      $params['error'] = 'Passwords do not match.';
      $params['activeForm'] = 'register';
    } else {
      $dob = DateTime::createFromFormat('Y-m-d', $dobInput);

      if ($dob === false) {
        $params['error'] = 'Invalid date of birth.';
        $params['activeForm'] = 'register';
      } elseif ($userRepository->findByEmail($email)) {
        $params['error'] = 'An account with this email already exists.';
        $params['activeForm'] = 'register';
      } else {
        try {
          $userRepository->createUser($email, $password, Type::STANDARD, $dob);
          $params['success'] = '1';
        } catch (Throwable $th) {
          $params['error'] = 'Account creation failed. Please try again.';
          $params['activeForm'] = 'register';
        }
      }
    }
  }

  if ($action !== 'login' && $action !== 'register') {
    $params['error'] = 'Invalid action.';
  }

  $query = http_build_query($params);
  header('Location: /login.php' . ($query !== '' ? '?' . $query : ''));
  exit();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
}
// Effective entrypoint for logout action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
  logoutUser();
  header('Location: /login.php');
  exit();
} else {
  http_response_code(400);
  exit('Invalid request method.');
}
