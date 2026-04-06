<?php

class ProfileRepository
{
  public function __construct(private PDO $pdo) {}

  public function findById(int $id): ?array
  {
    $stmt = $this->pdo->prepare('SELECT * FROM profiles WHERE user_id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);

    $profile = $stmt->fetch();
    return $profile ?: null;
  }

  public function createProfile(int $userId, DateTime $dob)
  {
    $stmt = $this->pdo->prepare(
      'INSERT INTO profiles (user_id, dob, created_at) VALUES (:userId, :dob, CURRENT_TIMESTAMP)',
    );
    $stmt->execute([
      'userId' => $userId,
      'dob' => $dob->format('Y-m-d'),
    ]);

    return $this->findById($userId);
  }
}
