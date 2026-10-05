<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class PhotoEventSubmissionRepository
{
	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Database::getInstance()->getConnection();
	}

	public function findPendingEquivalent(?int $eventId, string $normalizedName, int $year): ?array
	{
		if ($eventId !== null) {
			$stmt = $this->db->prepare("SELECT * FROM photo_event_submissions WHERE event_id = :event_id AND year = :year AND status = 'pending' LIMIT 1");
			$stmt->execute([':event_id' => $eventId, ':year' => $year]);
		} else {
			$stmt = $this->db->prepare("SELECT * FROM photo_event_submissions WHERE event_id IS NULL AND normalized_event_name = :name AND year = :year AND status = 'pending' LIMIT 1");
			$stmt->execute([':name' => $normalizedName, ':year' => $year]);
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function create(array $data): int
	{
		$stmt = $this->db->prepare(
			"INSERT INTO photo_event_submissions
				(user_id, event_id, event_name, normalized_event_name, year, location_name, event_date, status, created_at, updated_at)
			 VALUES
				(:user_id, :event_id, :event_name, :normalized_event_name, :year, :location_name, :event_date, 'pending', NOW(), NOW())"
		);
		$stmt->execute([
			':user_id' => $data['user_id'],
			':event_id' => $data['event_id'],
			':event_name' => $data['event_name'],
			':normalized_event_name' => $data['normalized_event_name'],
			':year' => $data['year'],
			':location_name' => $data['location_name'],
			':event_date' => $data['event_date'],
		]);
		return (int) $this->db->lastInsertId();
	}

	public function find(int $submissionId): ?array
	{
		$stmt = $this->db->prepare($this->selectSql() . " WHERE s.id = :id LIMIT 1");
		$stmt->execute([':id' => $submissionId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function count(string $status): int
	{
		$where = $status !== '' ? 'WHERE s.status = :status' : '';
		$stmt = $this->db->prepare("SELECT COUNT(*) FROM photo_event_submissions s {$where}");
		if ($status !== '') {
			$stmt->bindValue(':status', $status);
		}
		$stmt->execute();
		return (int) $stmt->fetchColumn();
	}

	public function list(string $status, int $limit, int $offset): array
	{
		$where = $status !== '' ? 'WHERE s.status = :status' : '';
		$stmt = $this->db->prepare($this->selectSql() . "
			{$where}
			ORDER BY s.created_at DESC, s.id DESC
			LIMIT :limit OFFSET :offset"
		);
		if ($status !== '') {
			$stmt->bindValue(':status', $status);
		}
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function resolve(int $submissionId, int $eventId, int $adminId, string $status): void
	{
		$stmt = $this->db->prepare(
			"UPDATE photo_event_submissions
			 SET status = :status,
			     resolved_event_id = :event_id,
			     resolved_by = :admin_id,
			     resolved_at = NOW(),
			     updated_at = NOW()
			 WHERE id = :id AND status = 'pending'"
		);
		$stmt->execute([
			':status' => $status,
			':event_id' => $eventId,
			':admin_id' => $adminId,
			':id' => $submissionId,
		]);
	}

	public function reject(int $submissionId, int $adminId): void
	{
		$stmt = $this->db->prepare(
			"UPDATE photo_event_submissions
			 SET status = 'rejected', resolved_by = :admin_id, resolved_at = NOW(), updated_at = NOW()
			 WHERE id = :id AND status = 'pending'"
		);
		$stmt->execute([':admin_id' => $adminId, ':id' => $submissionId]);
	}

	private function selectSql(): string
	{
		return "SELECT s.*,
				u.username AS user_username,
				e.titolo AS existing_event_title,
				e.slug AS existing_event_slug,
				re.titolo AS resolved_event_title,
				COALESCE(pc.photo_count, 0) AS photo_count
			FROM photo_event_submissions s
			LEFT JOIN users u ON u.id = s.user_id
			LEFT JOIN events e ON e.id = s.event_id
			LEFT JOIN events re ON re.id = s.resolved_event_id
			LEFT JOIN (
				SELECT event_submission_id, COUNT(*) AS photo_count
				FROM photos
				WHERE event_submission_id IS NOT NULL AND deleted_at IS NULL
				GROUP BY event_submission_id
			) pc ON pc.event_submission_id = s.id";
	}
}
