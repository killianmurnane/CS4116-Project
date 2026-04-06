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
    $stmt = $this->pdo->prepare('INSERT INTO profiles (user_id, dob) VALUES (:userId, :dob)');
    $stmt->execute([
      'userId' => $userId,
      'dob' => $dob->format('Y-m-d'),
    ]);

    return $this->findById($userId);
  }

  public function updateProfile(
    int $userId,
    string $givenName,
    ?string $familyName,
    ?string $gender,
    ?string $location,
    ?DateTime $dob,
    ?string $description,
    ?string $preferredSessions,
  ): ?array {
    $stmt = $this->pdo->prepare(
      'UPDATE profiles
       SET given_name = :given_name,
           family_name = :family_name,
           gender = :gender,
           location = :location,
           dob = :dob,
           description = :description,
           preferred_sessions = :preferred_sessions
       WHERE user_id = :user_id',
    );

    $stmt->execute([
      'given_name' => $givenName,
      'family_name' => $familyName,
      'gender' => $gender,
      'location' => $location,
      'dob' => $dob?->format('Y-m-d'),
      'description' => $description,
      'preferred_sessions' => $preferredSessions,
      'user_id' => $userId,
    ]);

    return $this->findById($userId);
  }
}
