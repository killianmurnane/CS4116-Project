<?php

class LocationsRepository
{
  public function __construct(private PDO $pdo) {}

  public function getAllLocations(): array
  {
    $stmt = $this->pdo->query('SELECT * FROM locations');
    return $stmt->fetchAll();
  }
}
