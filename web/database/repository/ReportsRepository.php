<?php

class ReportsRepository
{
  public function __construct(private PDO $pdo) {}

  /**
   * Creates a new report and returns the ID of the created report
   */
  public function createReport(
    int $reporterId,
    int $reportedId,
    string $reason,
    string $message,
    ?string $aiOverview = null,
  ): int {
    $stmt = $this->pdo->prepare(
      'INSERT INTO reports (reporter_id, reported_id, reason, message, ai_overview)
			 VALUES (:reporterId, :reportedId, :reason, :message, :aiOverview)',
    );

    $stmt->execute([
      'reporterId' => $reporterId,
      'reportedId' => $reportedId,
      'reason' => $reason,
      'message' => $message,
      'aiOverview' => $aiOverview,
    ]);

    return (int) $this->pdo->lastInsertId();
  }

  /**
   * Marks a report as reviewed
   */
  public function reviewReport(int $reportId): void
  {
    $stmt = $this->pdo->prepare('UPDATE reports SET reviewed = TRUE WHERE report_id = :reportId');
    $stmt->execute(['reportId' => $reportId]);
  }

  /**
   * Gets a list of reports
   * Ordered by most recent first
   */
  public function getReports(int $limit = 10, int $offset = 0): ?array
  {
    $stmt = $this->pdo->prepare(
      'SELECT *
			 FROM reports
			 ORDER BY created_at DESC, report_id DESC
			 LIMIT :limit OFFSET :offset',
    );

    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $reports = $stmt->fetchAll();
    return $reports ?: null;
  }

  /**
   * Gets a list of unreviewed reports
   * Ordered by most recent first
   */
  public function getUnreviewedReports(int $limit = 10): ?array
  {
    $stmt = $this->pdo->prepare(
      'SELECT *
             FROM reports
             WHERE reviewed = FALSE
             ORDER BY created_at DESC, report_id DESC
             LIMIT :limit',
    );

    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $reports = $stmt->fetchAll();
    return $reports ?: null;
  }
}
