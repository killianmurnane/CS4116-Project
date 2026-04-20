<?php

class GoalsRepository
{
  public function __construct(private PDO $pdo) {}

  public function getGoals(): array
  {
    return $this->getAllGoals();
  }

  public function getAllGoals(): array
  {
    $stmt = $this->pdo->query('SELECT goal_id, goal_name FROM goals ORDER BY goal_name');
    return $stmt->fetchAll();
  }

  public function getUserGoals(int $userId): array
  {
    $stmt = $this->pdo->prepare(
      'SELECT g.goal_id, g.goal_name
       FROM user_goals ug
       JOIN goals g ON g.goal_id = ug.goal_id
       WHERE ug.user_id = :userId
       ORDER BY g.goal_name',
    );
    $stmt->execute(['userId' => $userId]);
    return $stmt->fetchAll();
  }

  public function addUserGoal(int $userId, int $goalId): void
  {
    $stmt = $this->pdo->prepare(
      'INSERT INTO user_goals (user_id, goal_id) VALUES (:userId, :goalId)',
    );
    $stmt->execute([
      'userId' => $userId,
      'goalId' => $goalId,
    ]);
  }

  public function removeUserGoal(int $userId, int $goalId): void
  {
    $stmt = $this->pdo->prepare(
      'DELETE FROM user_goals WHERE user_id = :userId AND goal_id = :goalId',
    );
    $stmt->execute([
      'userId' => $userId,
      'goalId' => $goalId,
    ]);
  }
}
