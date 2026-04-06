<?php
require __DIR__ . '/ProfileRepository.php';

enum Type: string
{
  case ADMIN = 'admin';
  case STANDARD = 'standard';
  case BANNED = 'banned';
}

class UserRepository
{
  public function __construct(private PDO $pdo) {}

  public function findById(int $id): ?array
  {
    $stmt = $this->pdo->prepare('SELECT * FROM users WHERE user_id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);

    $user = $stmt->fetch();
    return $user ?: null;
  }

  public function createUser(string $email, string $password, Type $type, DateTime $dob)
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
    $profileRepository->createProfile((int) $this->pdo->lastInsertId(), $dob);

    return $this->findById((int) $this->pdo->lastInsertId());
  }
}
