<?php

require_once __DIR__ . '/MatchesRepository.php';

class DuplicateLikeException extends RuntimeException {}
class MatchError extends RuntimeException {}

class LikesRepository
{
  public function __construct(private PDO $pdo) {}

  private function hasReciprocalLike(int $likerId, int $likedId): bool
  {
    $stmt = $this->pdo->prepare(
      'SELECT 1 FROM likes WHERE liker_id = :likedId AND liked_id = :likerId LIMIT 1',
    );
    $stmt->execute([
      'likedId' => $likedId,
      'likerId' => $likerId,
    ]);

    return (bool) $stmt->fetchColumn();
  }

  // Gets all likes where the user is either the liker or the liked
  public function findLikeById(int $id): ?array
  {
    $likedBy = $this->pdo->prepare('SELECT * from likes where liker_id = :id');
    $likedBy->execute(['id' => $id]);
    $likedByResult = $likedBy->fetchAll();

    $likedTarget = $this->pdo->prepare('SELECT * from likes where liked_id = :id');
    $likedTarget->execute(['id' => $id]);
    $likedTargetResult = $likedTarget->fetchAll();

    return [
      'likedBy' => $likedByResult ?: null,
      'likedTarget' => $likedTargetResult ?: null,
    ];
  }

  // Gets users who liked the given user
  public function getLikedByUsers(int $likedId): ?array
  {
    $stmt = $this->pdo->prepare('SELECT * from likes where liked_id = :id');
    $stmt->execute(['id' => $likedId]);
    $result = $stmt->fetchAll();
    return $result ?: null;
  }

  // Gets users who were liked by the given user
  public function getLikedUsers(int $likerId): ?array
  {
    $stmt = $this->pdo->prepare('SELECT * from likes where liker_id = :id');
    $stmt->execute(['id' => $likerId]);
    $result = $stmt->fetchAll();
    return $result ?: null;
  }

  // Creates a like from likerId to likedId.
  // Throws DuplicateLikeException if the like already exists.
  // Throws MatchError if a reciprocal like exists, indicating a match.
  public function createLike(int $likerId, int $likedId): void
  {
    try {
      $stmt = $this->pdo->prepare(
        'INSERT INTO likes (liker_id, liked_id) VALUES (:likerId, :likedId)',
      );
      $stmt->execute([
        'likerId' => $likerId,
        'likedId' => $likedId,
      ]);
    } catch (PDOException $exception) {
      $sqlState = $exception->errorInfo[0] ?? null;
      $driverErrorCode = (int) ($exception->errorInfo[1] ?? 0);

      if ($sqlState === '23000' && $driverErrorCode === 1062) {
        throw new DuplicateLikeException('Like already exists.', 0, $exception);
      }

      throw $exception;
    }

    if ($this->hasReciprocalLike($likerId, $likedId)) {
      $matchesRepository = new MatchesRepository($this->pdo);
      try {
        $matchesRepository->createMatch($likerId, $likedId);
      } catch (PDOException $exception) {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverErrorCode = (int) ($exception->errorInfo[1] ?? 0);
        if (!($sqlState === '23000' && $driverErrorCode === 1062)) {
          throw $exception;
        }
      }

      throw new MatchError('Match!');
    }
  }
}
