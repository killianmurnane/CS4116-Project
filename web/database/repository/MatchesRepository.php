<?php

class MatchesRepository
{
  public function __construct(private PDO $pdo) {}

  public function getMatches(int $userId): ?array
  {
    $stmt = $this->pdo->prepare(
      'SELECT * from matches where user1_id = :userId1 OR user2_id = :userId2',
    );
    $stmt->execute(['userId1' => $userId, 'userId2' => $userId]);
    $result = $stmt->fetchAll();
    return $result ?: null;
  }

  public function createMatch(int $user1Id, int $user2Id): void
  {
    $stmt = $this->pdo->prepare(
      'INSERT INTO matches (user1_id, user2_id) VALUES (:user1Id, :user2Id)',
    );
    $stmt->execute([
      'user1Id' => $user1Id,
      'user2Id' => $user2Id,
    ]);
  }
}
