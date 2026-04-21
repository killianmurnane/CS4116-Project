<?php

class MessagesRepository
{
  public function __construct(private PDO $pdo) {}

  public function getMessages(int $matchId, ?int $limit = null): ?array
  {
    $query = 'SELECT * FROM messages WHERE match_id = :matchId ORDER BY created_at ASC';
    if ($limit !== null) {
      $query .= ' LIMIT :limit';
    }
    $stmt = $this->pdo->prepare($query);
    $stmt->bindValue(':matchId', $matchId, PDO::PARAM_INT);
    if ($limit !== null) {
      $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $result ?: null;
  }

  public function createMessage(int $matchId, int $senderId, string $message_text): void
  {
    $stmt = $this->pdo->prepare(
      'INSERT INTO messages (match_id, sender_id, message_text)
         VALUES (:matchId, :senderId, :messageText)',
    );
    $stmt->execute([
      'matchId' => $matchId,
      'senderId' => $senderId,
      'messageText' => $message_text,
    ]);
  }
}
