<?php

class LocationsRepository
{
  public function __construct(private PDO $pdo) {}

  /**
   * Returns a list of all locations that a user can select
   */
  public function getAllLocations(): array
  {
    $stmt = $this->pdo->query('SELECT * FROM locations');
    return $stmt->fetchAll();
  }
}
