<?php

class ExercisesRepository
{
  public function __construct(private PDO $pdo) {}

  public function getExercises(): array
  {
    return $this->getAllExercises();
  }

  public function getAllExercises(): array
  {
    $stmt = $this->pdo->query('SELECT * FROM exercises ORDER BY exercise_name');
    return $stmt->fetchAll();
  }

  public function getUserExercises(int $userId): array
  {
    $stmt = $this->pdo->prepare(
      'SELECT e.exercise_id, e.exercise_name
			 FROM user_exercises ue
			 JOIN exercises e ON e.exercise_id = ue.exercise_id
			 WHERE ue.user_id = :userId
			 ORDER BY e.exercise_name',
    );
    $stmt->execute(['userId' => $userId]);
    return $stmt->fetchAll();
  }

  public function addUserExercise(int $userId, int $exerciseId): void
  {
    $stmt = $this->pdo->prepare(
      'INSERT INTO user_exercises (user_id, exercise_id) VALUES (:userId, :exerciseId)',
    );
    $stmt->execute([
      'userId' => $userId,
      'exerciseId' => $exerciseId,
    ]);
  }

  public function removeUserExercise(int $userId, int $exerciseId): void
  {
    $stmt = $this->pdo->prepare(
      'DELETE FROM user_exercises WHERE user_id = :userId AND exercise_id = :exerciseId',
    );
    $stmt->execute([
      'userId' => $userId,
      'exerciseId' => $exerciseId,
    ]);
  }
}
