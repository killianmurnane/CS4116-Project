<?php

class MessagesRepository
{
  public function __construct(private PDO $pdo) {}

  public function getMessages(int $matchId): ?array
  {
    $stmt = $this->pdo->prepare(
      'SELECT * FROM messages WHERE match_id = :matchId ORDER BY created_at ASC',
    );
    $stmt->execute(['matchId' => $matchId]);
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
