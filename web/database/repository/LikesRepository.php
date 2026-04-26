<?php

require_once __DIR__ . '/MatchesRepository.php';

class DuplicateLikeException extends RuntimeException {}
class MatchError extends RuntimeException {}

class LikesRepository
{
  public function __construct(private PDO $pdo) {}

  /**
   * Checks if there is a reciprocal like from likedId to likerId, indicating a match
   */
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

  /**
   * Gets all likes involving the given user, both as a liker and as a liked user
   */
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

  /**
   * Gets users who liked the given user
   */
  public function getLikedByUsers(int $likedId): ?array
  {
    $stmt = $this->pdo->prepare('SELECT * from likes where liked_id = :id');
    $stmt->execute(['id' => $likedId]);
    $result = $stmt->fetchAll();
    return $result ?: null;
  }

  /**
   * Gets users who were liked by the given user
   */
  public function getLikedUsers(int $likerId): ?array
  {
    $stmt = $this->pdo->prepare('SELECT * from likes where liker_id = :id');
    $stmt->execute(['id' => $likerId]);
    $result = $stmt->fetchAll();
    return $result ?: null;
  }

  /**
   * Creates a like from likerId to likedId
   * If a like already exists, throws a DuplicateLikeException
   * If a reciprocal like exists, creates a match and throws a MatchError for match handling
   */
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
