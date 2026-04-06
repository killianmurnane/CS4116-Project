<?php

enum Type: string
{
  case ADMIN = 'admin';
  case STANDARD = 'standard';
  case BANNED = 'banned';
}

class UserRepository
{
  public function __construct(private PDO $pdo) {}

  public function createUser(
    string $email,
    string $password,
    Type $type,
    string $givenName,
    string $familyName,
    string $gender,
    DateTime $dob,
  )
  {
    $stmt = $this->pdo->prepare(
      'INSERT INTO users (email, password, type, created_at) VALUES (:email, :password, :type, CURRENT_TIMESTAMP)',
    );
    $stmt->execute([
      'email' => $email,
      'password' => password_hash($password, PASSWORD_DEFAULT),
      'type' => $type->value,
    ]);

    $profileRepository = new ProfileRepository($this->pdo);
    $profileRepository->createProfile(
      (int) $this->pdo->lastInsertId(),
      $givenName,
      $familyName,
      $gender,
      $dob,
    );

    return $this->findById((int) $this->pdo->lastInsertId());
  }

  public function findById(int $id): ?array
  {
    $stmt = $this->pdo->prepare(
      'SELECT u.*, p.given_name, p.family_name, p.gender
       FROM users u
       LEFT JOIN profiles p ON p.user_id = u.user_id
       WHERE u.user_id = :id
       LIMIT 1',
    );
    $stmt->execute(['id' => $id]);

    $user = $stmt->fetch();
    return $user ?: null;
  }

  public function findByEmail(string $email): ?array
  {
    $stmt = $this->pdo->prepare(
      'SELECT u.*, p.given_name, p.family_name, p.gender
       FROM users u
       LEFT JOIN profiles p ON p.user_id = u.user_id
       WHERE u.email = :email
       LIMIT 1',
    );
    $stmt->execute(['email' => $email]);

    $user = $stmt->fetch();
    return $user ?: null;
  }
}
