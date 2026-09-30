<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class PhotoRepository
{
	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Database::getInstance()->getConnection();
	}

	public function create(array $data): int
	{
		$stmt = $this->db->prepare(
			"INSERT INTO photos
				(event_id, uploaded_by_user_id, storage_key, thumbnail_storage_key, original_filename, width, height, thumbnail_width, thumbnail_height, filesize, status, created_at)
			 VALUES
				(:event_id, :uploaded_by_user_id, :storage_key, :thumbnail_storage_key, :original_filename, :width, :height, :thumbnail_width, :thumbnail_height, :filesize, :status, NOW())"
		);
		$stmt->execute([
			':event_id' => $data['event_id'],
			':uploaded_by_user_id' => $data['uploaded_by_user_id'],
			':storage_key' => $data['storage_key'],
			':thumbnail_storage_key' => $data['thumbnail_storage_key'],
			':original_filename' => $data['original_filename'],
			':width' => $data['width'],
			':height' => $data['height'],
			':thumbnail_width' => $data['thumbnail_width'],
			':thumbnail_height' => $data['thumbnail_height'],
			':filesize' => $data['filesize'],
			':status' => $data['status'],
		]);
		return (int) $this->db->lastInsertId();
	}

	public function findPublished(int $photoId): ?array
	{
		$stmt = $this->db->prepare($this->selectSql() . " WHERE p.id = :id AND p.status = 'published' AND p.deleted_at IS NULL LIMIT 1");
		$stmt->execute([':id' => $photoId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function findForOwner(int $photoId, int $userId): ?array
	{
		$stmt = $this->db->prepare($this->selectSql() . " WHERE p.id = :id AND p.uploaded_by_user_id = :user_id AND p.deleted_at IS NULL LIMIT 1");
		$stmt->execute([':id' => $photoId, ':user_id' => $userId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function listPublishedByEvent(int $eventId, int $limit, int $offset): array
	{
		$stmt = $this->db->prepare($this->selectSql() . "
			WHERE p.event_id = :event_id AND p.status = 'published' AND p.deleted_at IS NULL
			ORDER BY p.created_at DESC, p.id DESC
			LIMIT :limit OFFSET :offset"
		);
		$stmt->bindValue(':event_id', $eventId, PDO::PARAM_INT);
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function countPublishedByEvent(int $eventId): int
	{
		$stmt = $this->db->prepare("SELECT COUNT(*) FROM photos WHERE event_id = :event_id AND status = 'published' AND deleted_at IS NULL");
		$stmt->execute([':event_id' => $eventId]);
		return (int) $stmt->fetchColumn();
	}

	public function countUploadersByEvent(int $eventId): int
	{
		$stmt = $this->db->prepare("SELECT COUNT(DISTINCT uploaded_by_user_id) FROM photos WHERE event_id = :event_id AND status = 'published' AND deleted_at IS NULL");
		$stmt->execute([':event_id' => $eventId]);
		return (int) $stmt->fetchColumn();
	}

	public function groupedByUploader(int $userId): array
	{
		$stmt = $this->db->prepare(
			"SELECT e.id AS event_id, e.titolo, e.slug, e.data_inizio, e.data_fine, c.nome AS comune_nome,
					COUNT(*) AS photo_count, MAX(p.created_at) AS last_upload_at
			 FROM photos p
			 INNER JOIN events e ON e.id = p.event_id
			 LEFT JOIN comuni c ON c.id = e.comune_id
			 WHERE p.uploaded_by_user_id = :user_id AND p.deleted_at IS NULL
			 GROUP BY e.id, e.titolo, e.slug, e.data_inizio, e.data_fine, c.nome
			 ORDER BY last_upload_at DESC"
		);
		$stmt->execute([':user_id' => $userId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function listForUploaderEvent(int $userId, int $eventId): array
	{
		$stmt = $this->db->prepare($this->selectSql() . "
			WHERE p.uploaded_by_user_id = :user_id AND p.event_id = :event_id AND p.deleted_at IS NULL
			ORDER BY p.created_at DESC, p.id DESC"
		);
		$stmt->execute([':user_id' => $userId, ':event_id' => $eventId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function deleteOwned(int $photoId, int $userId): ?array
	{
		$photo = $this->findForOwner($photoId, $userId);
		if (!$photo) {
			return null;
		}
		$stmt = $this->db->prepare("UPDATE photos SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id AND uploaded_by_user_id = :user_id");
		$stmt->execute([':id' => $photoId, ':user_id' => $userId]);
		return $photo;
	}

	public function addCosplayer(int $photoId, ?int $userId, ?int $cosplayId, ?string $displayName, ?string $instagramUsername, int $createdByUserId, string $status = 'confirmed'): bool
	{
		if ($this->hasCosplayer($photoId, $userId, $cosplayId, $displayName, $instagramUsername)) {
			return false;
		}

		$stmt = $this->db->prepare(
			"INSERT INTO photo_cosplayers
				(photo_id, user_id, cosplay_id, display_name, instagram_username, status, created_by_user_id, created_at)
			 VALUES
				(:photo_id, :user_id, :cosplay_id, :display_name, :instagram_username, :status, :created_by_user_id, NOW())"
		);
		$stmt->execute([
			':photo_id' => $photoId,
			':user_id' => $userId,
			':cosplay_id' => $cosplayId,
			':display_name' => $displayName,
			':instagram_username' => $instagramUsername,
			':status' => $status,
			':created_by_user_id' => $createdByUserId,
		]);

		return $stmt->rowCount() > 0;
	}

	public function deleteAssociationForOwner(int $associationId, int $ownerUserId): bool
	{
		$stmt = $this->db->prepare(
			"DELETE pc
			 FROM photo_cosplayers pc
			 INNER JOIN photos p ON p.id = pc.photo_id
			 WHERE pc.id = :association_id
			   AND p.uploaded_by_user_id = :owner_user_id
			   AND p.deleted_at IS NULL"
		);
		$stmt->execute([
			':association_id' => $associationId,
			':owner_user_id' => $ownerUserId,
		]);
		return $stmt->rowCount() > 0;
	}

	public function getCosplayers(int $photoId): array
	{
		$stmt = $this->db->prepare(
			"SELECT pc.*, u.username, u.first_name, u.last_name, p.custom_name, c.name_full, c.name_native
			 FROM photo_cosplayers pc
			 LEFT JOIN users u ON u.id = pc.user_id
			 LEFT JOIN user_cosplay_portfolio p ON p.id = pc.cosplay_id
			 LEFT JOIN anilist_characters c ON c.id = p.anilist_character_id
			 WHERE pc.photo_id = :photo_id AND pc.status IN ('confirmed','pending')
			 ORDER BY pc.id ASC"
		);
		$stmt->execute([':photo_id' => $photoId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getCosplayersForPhotos(array $photoIds): array
	{
		$photoIds = array_values(array_unique(array_filter(array_map('intval', $photoIds))));
		if (!$photoIds) {
			return [];
		}

		$placeholders = implode(',', array_fill(0, count($photoIds), '?'));
		$stmt = $this->db->prepare(
			"SELECT pc.*, u.username, u.first_name, u.last_name, p.custom_name, c.name_full, c.name_native
			 FROM photo_cosplayers pc
			 LEFT JOIN users u ON u.id = pc.user_id
			 LEFT JOIN user_cosplay_portfolio p ON p.id = pc.cosplay_id
			 LEFT JOIN anilist_characters c ON c.id = p.anilist_character_id
			 WHERE pc.photo_id IN ({$placeholders}) AND pc.status IN ('confirmed','pending')
			 ORDER BY pc.photo_id ASC, pc.id ASC"
		);
		$stmt->execute($photoIds);
		$grouped = [];
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$grouped[(int) $row['photo_id']][] = $row;
		}
		return $grouped;
	}

	public function report(int $photoId, ?int $userId, string $reason, string $message): int
	{
		$stmt = $this->db->prepare(
			"INSERT INTO photo_reports (photo_id, user_id, reason, message, status, created_at)
			 VALUES (:photo_id, :user_id, :reason, :message, 'open', NOW())"
		);
		$stmt->execute([
			':photo_id' => $photoId,
			':user_id' => $userId,
			':reason' => $reason,
			':message' => $message !== '' ? $message : null,
		]);

		return (int) $this->db->lastInsertId();
	}

	public function countReports(string $status): int
	{
		$where = $status !== '' ? 'WHERE pr.status = :status' : '';
		$stmt = $this->db->prepare("SELECT COUNT(*) FROM photo_reports pr {$where}");
		if ($status !== '') {
			$stmt->bindValue(':status', $status);
		}
		$stmt->execute();
		return (int) $stmt->fetchColumn();
	}

	public function listReports(string $status, int $limit, int $offset): array
	{
		$where = $status !== '' ? 'WHERE pr.status = :status' : '';
		$stmt = $this->db->prepare(
			"SELECT pr.*, p.event_id, p.storage_key, p.thumbnail_storage_key, p.status AS photo_status, p.original_filename,
					e.titolo AS event_title, e.slug AS event_slug,
					reporter.username AS reporter_username,
					uploader.username AS uploader_username
			 FROM photo_reports pr
			 INNER JOIN photos p ON p.id = pr.photo_id
			 INNER JOIN events e ON e.id = p.event_id
			 LEFT JOIN users reporter ON reporter.id = pr.user_id
			 LEFT JOIN users uploader ON uploader.id = p.uploaded_by_user_id
			 {$where}
			 ORDER BY pr.created_at DESC, pr.id DESC
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

	public function findReport(int $reportId): ?array
	{
		$stmt = $this->db->prepare(
			"SELECT pr.*, p.event_id, p.status AS photo_status, e.titolo AS event_title, e.slug AS event_slug
			 FROM photo_reports pr
			 INNER JOIN photos p ON p.id = pr.photo_id
			 INNER JOIN events e ON e.id = p.event_id
			 WHERE pr.id = :id
			 LIMIT 1"
		);
		$stmt->execute([':id' => $reportId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function updateReportStatus(int $reportId, string $status): bool
	{
		$stmt = $this->db->prepare("UPDATE photo_reports SET status = :status WHERE id = :id");
		$stmt->execute([':status' => $status, ':id' => $reportId]);
		return $stmt->rowCount() > 0;
	}

	public function hidePhotoFromReport(int $reportId): ?array
	{
		$report = $this->findReport($reportId);
		if (!$report) {
			return null;
		}

		$this->db->beginTransaction();
		try {
			$stmt = $this->db->prepare("UPDATE photos SET status = 'hidden', updated_at = NOW() WHERE id = :id AND deleted_at IS NULL");
			$stmt->execute([':id' => (int) $report['photo_id']]);
			$this->updateReportStatus($reportId, 'closed');
			$this->db->commit();
		} catch (\Throwable $exception) {
			$this->db->rollBack();
			throw $exception;
		}

		return $report;
	}

	private function hasCosplayer(int $photoId, ?int $userId, ?int $cosplayId, ?string $displayName, ?string $instagramUsername): bool
	{
		if ($userId !== null) {
			$stmt = $this->db->prepare(
				"SELECT 1
				 FROM photo_cosplayers
				 WHERE photo_id = :photo_id
				   AND user_id = :user_id
				   AND (cosplay_id <=> :cosplay_id)
				   AND status IN ('confirmed','pending')
				 LIMIT 1"
			);
			$stmt->execute([
				':photo_id' => $photoId,
				':user_id' => $userId,
				':cosplay_id' => $cosplayId,
			]);
			return (bool) $stmt->fetchColumn();
		}

		$stmt = $this->db->prepare(
			"SELECT 1
			 FROM photo_cosplayers
			 WHERE photo_id = :photo_id
			   AND user_id IS NULL
			   AND (cosplay_id <=> :cosplay_id)
			   AND (LOWER(COALESCE(display_name, '')) = LOWER(:display_name))
			   AND (LOWER(COALESCE(instagram_username, '')) = LOWER(:instagram_username))
			   AND status IN ('confirmed','pending')
			 LIMIT 1"
		);
		$stmt->execute([
			':photo_id' => $photoId,
			':cosplay_id' => $cosplayId,
			':display_name' => trim((string) $displayName),
			':instagram_username' => trim((string) $instagramUsername),
		]);
		return (bool) $stmt->fetchColumn();
	}

	private function selectSql(): string
	{
		return "SELECT p.*, e.titolo AS event_title, e.slug AS event_slug, e.data_inizio, e.data_fine,
				u.username AS uploader_username, u.first_name AS uploader_first_name, u.last_name AS uploader_last_name
			FROM photos p
			INNER JOIN events e ON e.id = p.event_id
			INNER JOIN users u ON u.id = p.uploaded_by_user_id";
	}
}
