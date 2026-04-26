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

  /**
   * Creates a new user and their associated profile
   */
  public function createUser(
    string $email,
    string $password,
    Type $type,
    string $givenName,
    string $familyName,
    string $gender,
    DateTime $dob,
    string $location,
  ): ?array {
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
      $location,
    );

    return $this->findById((int) $this->pdo->lastInsertId());
  }

  /**
   * Gets a user by their ID
   */
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

  /**
   * Gets a user by their email address
   */
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

  /**
   * Updates a user's email, password, or type
   */
  public function updateUser(int $userId, ?string $email, ?string $password, ?Type $type): ?array
  {
    $fields = [];
    $params = ['userId' => $userId];

    if ($email !== null) {
      $fields[] = 'email = :email';
      $params['email'] = $email;
    }

    if ($password !== null) {
      $fields[] = 'password = :password';
      $params['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    if ($type !== null) {
      $fields[] = 'type = :type';
      $params['type'] = $type->value;
    }

    if (empty($fields)) {
      return $this->findById($userId);
    }

    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE user_id = :userId';
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);

    return $this->findById($userId);
  }

  // #region List/Filter

  /**
   * Get all users
   * With pagination
   */
  public function getUsers(int $limit = 5, int $offset = 0): array
  {
    $stmt = $this->pdo->prepare(
      "SELECT u.user_id, p.given_name, p.family_name, l.location AS location, p.description, p.dob,
      TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) AS age
       FROM users u
       LEFT JOIN profiles p ON p.user_id = u.user_id
       LEFT JOIN locations l ON l.id = p.location
      WHERE u.type != 'banned' AND p.user_id IS NOT NULL AND u.user_id != {$_SESSION['user_id']}
       ORDER BY u.created_at DESC
       LIMIT :limit OFFSET :offset",
    );

    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll();
  }

  /**
   * Filters users based on input
   * With pagination
   */
  public function filterUsers(
    ?string $type = null,
    ?string $gender = null,
    ?int $minAge = null,
    ?int $maxAge = null,
    ?int $location = null,
    ?int $goal = null,
    ?string $givenName = null,
    int $limit = 5,
    int $offset = 0,
    ?int $exercise = null,
  ) {
    $conditions = [];
    $params = ['limit' => $limit, 'offset' => $offset];

    if (!isset($type)) {
      $conditions[] = "u.type != 'banned'";
    } else {
      $conditions[] = 'u.type = :type';
      $params['type'] = $type;
    }

    if (isset($gender)) {
      $conditions[] = 'p.gender = :gender';
      $params['gender'] = $gender;
    }

    if (isset($minAge)) {
      $conditions[] = 'TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) >= :minAge';
      $params['minAge'] = $minAge;
    }

    if (isset($maxAge)) {
      $conditions[] = 'TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) <= :maxAge';
      $params['maxAge'] = $maxAge;
    }

    if (isset($location)) {
      $conditions[] = 'p.location = :location';
      $params['location'] = $location;
    }

    if (isset($givenName)) {
      $conditions[] = 'p.given_name LIKE :givenName';
      $params['givenName'] = '%' . $givenName . '%';
    }

    if (isset($goal) && $goal > 0) {
      $conditions[] = 'ug.goal_id = :goal';
      $params['goal'] = $goal;
    }

    if (isset($exercise) && $exercise > 0) {
      $conditions[] = 'ue.exercise_id = :exercise';
      $params['exercise'] = $exercise;
    }

    $goalJoinType = isset($goal) && $goal > 0 ? 'JOIN' : 'LEFT JOIN';
    $exerciseJoinType = isset($exercise) && $exercise > 0 ? 'JOIN' : 'LEFT JOIN';

    $sql =
      "SELECT DISTINCT u.user_id, p.given_name, p.family_name, l.location AS location, p.description, p.dob,
              TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) AS age
              FROM users u
              JOIN profiles p ON u.user_id = p.user_id
              LEFT JOIN locations l ON l.id = p.location
              {$goalJoinType} user_goals ug ON u.user_id = ug.user_id
              LEFT JOIN goals g ON ug.goal_id = g.goal_id
              {$exerciseJoinType} user_exercises ue ON u.user_id = ue.user_id
              LEFT JOIN exercises e ON ue.exercise_id = e.exercise_id
              WHERE " .
      implode(' AND ', $conditions) .
      " AND p.user_id IS NOT NULL AND u.user_id != {$_SESSION['user_id']}
              ORDER BY u.created_at DESC" .
      ' LIMIT :limit OFFSET :offset';

    $stmt = $this->pdo->prepare($sql);
    foreach ($params as $key => $value) {
      if (is_int($value)) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
      } else {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
      }
    }
    $stmt->execute();
    return $stmt->fetchAll();
  }

  // #endregion
}
