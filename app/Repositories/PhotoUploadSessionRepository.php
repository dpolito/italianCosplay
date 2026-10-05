<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class PhotoUploadSessionRepository
{
	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Database::getInstance()->getConnection();
	}

	public function findOpenForUser(int $userId): ?array
	{
		$stmt = $this->db->prepare(
			"SELECT s.*, e.titolo AS event_title, e.slug AS event_slug, e.data_inizio, e.data_fine
			 FROM photo_upload_sessions s
			 INNER JOIN events e ON e.id = s.event_id
			 WHERE s.user_id = :user_id
			   AND s.status IN ('draft','uploading','ready')
			   AND s.expires_at > NOW()
			 ORDER BY s.updated_at DESC, s.id DESC
			 LIMIT 1"
		);
		$stmt->execute([':user_id' => $userId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function findForUser(int $sessionId, int $userId): ?array
	{
		$stmt = $this->db->prepare(
			"SELECT s.*, e.titolo AS event_title, e.slug AS event_slug, e.data_inizio, e.data_fine
			 FROM photo_upload_sessions s
			 INNER JOIN events e ON e.id = s.event_id
			 WHERE s.id = :id AND s.user_id = :user_id
			 LIMIT 1"
		);
		$stmt->execute([':id' => $sessionId, ':user_id' => $userId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function create(int $userId, int $eventId, string $expiresAt): int
	{
		$stmt = $this->db->prepare(
			"INSERT INTO photo_upload_sessions (user_id, event_id, status, created_at, updated_at, expires_at)
			 VALUES (:user_id, :event_id, 'draft', NOW(), NOW(), :expires_at)"
		);
		$stmt->execute([':user_id' => $userId, ':event_id' => $eventId, ':expires_at' => $expiresAt]);
		return (int) $this->db->lastInsertId();
	}

	public function touch(int $sessionId, string $status): void
	{
		$stmt = $this->db->prepare("UPDATE photo_upload_sessions SET status = :status, updated_at = NOW() WHERE id = :id");
		$stmt->execute([':status' => $status, ':id' => $sessionId]);
	}

	public function createItem(array $data): int
	{
		$stmt = $this->db->prepare(
			"INSERT INTO photo_upload_items
				(upload_session_id, photo_id, original_filename, mime_type, file_size, file_hash, status, error_message, created_at, updated_at)
			 VALUES
				(:upload_session_id, :photo_id, :original_filename, :mime_type, :file_size, :file_hash, :status, :error_message, NOW(), NOW())"
		);
		$stmt->execute([
			':upload_session_id' => $data['upload_session_id'],
			':photo_id' => $data['photo_id'],
			':original_filename' => $data['original_filename'],
			':mime_type' => $data['mime_type'],
			':file_size' => $data['file_size'],
			':file_hash' => $data['file_hash'],
			':status' => $data['status'],
			':error_message' => $data['error_message'],
		]);
		return (int) $this->db->lastInsertId();
	}

	public function findItemByFingerprint(int $sessionId, string $fileHash, int $fileSize): ?array
	{
		$stmt = $this->db->prepare($this->itemSelectSql() . "
			WHERE i.upload_session_id = :session_id
			  AND i.file_hash = :file_hash
			  AND i.file_size = :file_size
			  AND i.status <> 'removed'
			LIMIT 1"
		);
		$stmt->execute([':session_id' => $sessionId, ':file_hash' => $fileHash, ':file_size' => $fileSize]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function findItemForUser(int $itemId, int $userId): ?array
	{
		$stmt = $this->db->prepare($this->itemSelectSql() . "
			INNER JOIN photo_upload_sessions s ON s.id = i.upload_session_id
			WHERE i.id = :id AND s.user_id = :user_id
			LIMIT 1"
		);
		$stmt->execute([':id' => $itemId, ':user_id' => $userId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function listItems(int $sessionId): array
	{
		$stmt = $this->db->prepare($this->itemSelectSql() . "
			WHERE i.upload_session_id = :session_id AND i.status <> 'removed'
			ORDER BY i.created_at ASC, i.id ASC"
		);
		$stmt->execute([':session_id' => $sessionId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function markItemRemoved(int $itemId): void
	{
		$stmt = $this->db->prepare("UPDATE photo_upload_items SET status = 'removed', updated_at = NOW() WHERE id = :id");
		$stmt->execute([':id' => $itemId]);
	}

	public function expiredSessions(): array
	{
		$stmt = $this->db->query(
			"SELECT id, user_id
			 FROM photo_upload_sessions
			 WHERE status IN ('draft','uploading','ready')
			   AND expires_at <= NOW()
			 ORDER BY expires_at ASC
			 LIMIT 200"
		);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	private function itemSelectSql(): string
	{
		return "SELECT i.*, p.storage_key, p.thumbnail_storage_key
			FROM photo_upload_items i
			LEFT JOIN photos p ON p.id = i.photo_id";
	}
}
