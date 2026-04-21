<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/../database/repository/ProfileRepository.php';
require_once __DIR__ . '/../database/repository/UserRepository.php';

$allowedGenders = ProfileRepository::ALLOWED_GENDERS;

function isLoggedIn(): bool
{
  return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
  return isset($_SESSION['user_type']) && $_SESSION['user_type'] === Type::ADMIN->value;
}

function requireLogin(): void
{
  if (!isLoggedIn()) {
    header('Location: /login.php?error=unauthorized');
    exit();
  }
}

function requireAdmin(): void
{
  requireLogin();

  if (!isAdmin()) {
    header('Location: /index.php');
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
      $params['error'] = 'empty_fields';
    } else {
      $user = $userRepository->findByEmail($email);

      if ($user && password_verify($password, (string) $user['password'])) {
        if (($user['type'] ?? null) === Type::BANNED->value) {
          $params['error'] = 'account_banned';
        } else {
          loginUser($user);
          if (isAdmin()) {
            header('Location: /admin.php');
          } else {
            header('Location: /index.php');
          }
          exit();
        }
      }

      if (!isset($params['error'])) {
        $params['error'] = 'invalid_credentials';
      }
    }
  } elseif ($action === 'register') {
    $email = trim((string) ($_POST['register_email'] ?? ''));
    $givenName = trim((string) ($_POST['given_name'] ?? ''));
    $familyName = trim((string) ($_POST['family_name'] ?? ''));
    $gender = trim((string) ($_POST['gender'] ?? ''));
    $password = (string) ($_POST['register_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $dobInput = (string) ($_POST['dob'] ?? '');
    $location = trim((string) ($_POST['location'] ?? ''));

    if (
      $email === '' ||
      $givenName === '' ||
      $familyName === '' ||
      $gender === '' ||
      $password === '' ||
      $confirmPassword === '' ||
      $dobInput === ''
    ) {
      $params['error'] = 'empty_fields';
      $params['activeForm'] = 'register';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $params['error'] = 'invalid_email';
      $params['activeForm'] = 'register';
    } elseif (!in_array($gender, $allowedGenders, true)) {
      $params['error'] = 'invalid_gender';
      $params['activeForm'] = 'register';
    } elseif (strlen($password) < 8) {
      $params['error'] = 'password_length';
      $params['activeForm'] = 'register';
    } elseif ($password !== $confirmPassword) {
      $params['error'] = 'passwords_match';
      $params['activeForm'] = 'register';
    } else {
      $dob = DateTime::createFromFormat('Y-m-d', $dobInput);

      if ($dob === false) {
        $params['error'] = 'invalid_dob';
        $params['activeForm'] = 'register';
      } elseif ($dob > new DateTime('-18 years')) {
        $params['error'] = 'underage';
        $params['activeForm'] = 'register';
      } elseif ($userRepository->findByEmail($email)) {
        $params['error'] = 'email_exists';
        $params['activeForm'] = 'register';
      } else {
        try {
          $userRepository->createUser(
            $email,
            $password,
            Type::STANDARD,
            $givenName,
            $familyName,
            $gender,
            $dob,
            $location,
          );
          $params['success'] = '1';
        } catch (Throwable $exception) {
          $params['error'] = 'account_creation_failed';
          $params['activeForm'] = 'register';
        }
      }
    }
  } else {
    $params['error'] = 'invalid_action';
  }

  $query = http_build_query($params);
  header('Location: /login.php' . ($query !== '' ? '?' . $query : ''));
  exit();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logoutUser();
    header('Location: /login.php');
    exit();
  }
} else {
  http_response_code(400);
  exit('Invalid request method.');
}
