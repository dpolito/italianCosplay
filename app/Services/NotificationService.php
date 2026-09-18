<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class NotificationService
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function createNotification(array $data): bool
	{
		$stmt = $this->db->prepare(
			"INSERT INTO user_notifications
			(user_id, notification_type, title, message, source_entity_type, source_entity_id, payload, is_read, created_at, updated_at)
			VALUES
			(:user_id, :notification_type, :title, :message, :source_entity_type, :source_entity_id, :payload, 0, NOW(), NOW())"
		);

		return $stmt->execute([
			':user_id' => (int) ($data['user_id'] ?? 0),
			':notification_type' => (string) ($data['notification_type'] ?? ''),
			':title' => (string) ($data['title'] ?? ''),
			':message' => (string) ($data['message'] ?? ''),
			':source_entity_type' => (string) ($data['source_entity_type'] ?? 'event'),
			':source_entity_id' => (int) ($data['source_entity_id'] ?? 0),
			':payload' => !empty($data['payload']) ? json_encode($data['payload'], JSON_UNESCAPED_UNICODE) : null,
		]);
	}

	public function createBulkNotifications(array $userIds, array $data): int
	{
		$userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
		if (empty($userIds)) {
			return 0;
		}

		$created = 0;
		foreach ($userIds as $userId) {
			$payload = $data;
			$payload['user_id'] = $userId;
			if ($this->createNotification($payload)) {
				$created++;
			}
		}

		return $created;
	}

	public function getRecipientsForEntity(string $entityType, int $entityId, int $excludeUserId = 0): array
	{
		$recipients = [];

		if ($entityType === 'event') {
			$queries = [
				"SELECT user_id FROM user_event_agenda WHERE event_id = :entity_id",
				"SELECT user_id FROM user_favorites WHERE entity_type = 'event' AND entity_id = :entity_id",
			];

			foreach ($queries as $sql) {
				$stmt = $this->db->prepare($sql);
				$stmt->execute([':entity_id' => $entityId]);
				foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $userId) {
					$userId = (int) $userId;
					if ($userId > 0 && $userId !== $excludeUserId) {
						$recipients[$userId] = $userId;
					}
				}
			}
		}

		return array_values($recipients);
	}

	public function getUnreadCount(int $userId): int
	{
		$stmt = $this->db->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id = :user_id AND is_read = 0");
		$stmt->execute([':user_id' => $userId]);
		return (int) $stmt->fetchColumn();
	}

	public function getLatest(int $userId, int $limit = 10): array
	{
		$stmt = $this->db->prepare(
			"SELECT id, notification_type, title, message, source_entity_type, source_entity_id, payload, is_read, read_at, created_at
			 FROM user_notifications
			 WHERE user_id = :user_id
			 ORDER BY created_at DESC
			 LIMIT :limit"
		);
		$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
		$stmt->bindValue(':limit', max(1, min($limit, 50)), PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function markAsRead(int $userId, int $notificationId): bool
	{
		$stmt = $this->db->prepare(
			"UPDATE user_notifications
			 SET is_read = 1, read_at = NOW(), updated_at = NOW()
			 WHERE user_id = :user_id AND id = :id"
		);
		return $stmt->execute([
			':user_id' => $userId,
			':id' => $notificationId,
		]);
	}

	public function markAllAsRead(int $userId): bool
	{
		$stmt = $this->db->prepare(
			"UPDATE user_notifications
			 SET is_read = 1, read_at = NOW(), updated_at = NOW()
			 WHERE user_id = :user_id AND is_read = 0"
		);

		return $stmt->execute([':user_id' => $userId]);
	}

	public function deleteNotification(int $userId, int $notificationId): bool
	{
		$stmt = $this->db->prepare(
			"DELETE FROM user_notifications WHERE user_id = :user_id AND id = :id"
		);

		return $stmt->execute([
			':user_id' => $userId,
			':id' => $notificationId,
		]);
	}
}
