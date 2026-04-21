<?php

class ReportsRepository
{
  public function __construct(private PDO $pdo) {}

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

  public function reviewReport(int $reportId): void
  {
    $stmt = $this->pdo->prepare('UPDATE reports SET reviewed = TRUE WHERE report_id = :reportId');
    $stmt->execute(['reportId' => $reportId]);
  }

  public function findById(int $reportId): ?array
  {
    $stmt = $this->pdo->prepare('SELECT * FROM reports WHERE report_id = :reportId LIMIT 1');
    $stmt->execute(['reportId' => $reportId]);

    $report = $stmt->fetch();
    return $report ?: null;
  }

  public function getReportsByReporter(int $reporterId): ?array
  {
    $stmt = $this->pdo->prepare(
      'SELECT *
			 FROM reports
			 WHERE reporter_id = :reporterId
			 ORDER BY created_at DESC, report_id DESC',
    );
    $stmt->execute(['reporterId' => $reporterId]);

    $reports = $stmt->fetchAll();
    return $reports ?: null;
  }

  public function getReportsByReported(int $reportedId): ?array
  {
    $stmt = $this->pdo->prepare(
      'SELECT *
			 FROM reports
			 WHERE reported_id = :reportedId
			 ORDER BY created_at DESC, report_id DESC',
    );
    $stmt->execute(['reportedId' => $reportedId]);

    $reports = $stmt->fetchAll();
    return $reports ?: null;
  }

  public function getReports(int $limit = 50, int $offset = 0): ?array
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
