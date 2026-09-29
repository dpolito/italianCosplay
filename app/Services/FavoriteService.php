<?php

namespace App\Services;

use App\Core\Database;
use App\Support\AuditLogActionType;
use PDO;

class FavoriteService
{
	private PDO $db;
	private AuditLogService $auditLogService;
	private FavoriteAnalyticsService $favoriteAnalyticsService;
	private NotificationService $notificationService;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
		$this->auditLogService = new AuditLogService();
		$this->favoriteAnalyticsService = new FavoriteAnalyticsService();
		$this->notificationService = new NotificationService();
	}

	public function isFavorited(int $userId, string $entityType, int $entityId): bool
	{
		$stmt = $this->db->prepare(
			"SELECT COUNT(*) FROM user_favorites WHERE user_id = :user_id AND entity_type = :entity_type AND entity_id = :entity_id"
		);
		$stmt->execute([
			':user_id' => $userId,
			':entity_type' => $entityType,
			':entity_id' => $entityId,
		]);

		return (int) $stmt->fetchColumn() > 0;
	}

	public function addFavorite(int $userId, string $entityType, int $entityId): bool
	{
		if ($this->isFavorited($userId, $entityType, $entityId)) {
			return true;
		}

		$stmt = $this->db->prepare(
			"INSERT INTO user_favorites (user_id, entity_type, entity_id, created_at) VALUES (:user_id, :entity_type, :entity_id, NOW())"
		);
		$ok = $stmt->execute([
			':user_id' => $userId,
			':entity_type' => $entityType,
			':entity_id' => $entityId,
		]);

		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::FAVORITE_ADDED,
			'entity_type' => $entityType,
			'entity_id' => $entityId,
			'success' => $ok ? 1 : 0,
			'payload' => [
				'entity_type' => $entityType,
				'entity_id' => $entityId,
			],
		]);

		if ($ok) {
			$this->favoriteAnalyticsService->track($userId, $entityType, $entityId, 'add');
			$this->notifyFirstFavorite($userId, $entityType, $entityId);
		}

		return $ok;
	}

	public function removeFavorite(int $userId, string $entityType, int $entityId): bool
	{
		$stmt = $this->db->prepare(
			"DELETE FROM user_favorites WHERE user_id = :user_id AND entity_type = :entity_type AND entity_id = :entity_id"
		);
		$ok = $stmt->execute([
			':user_id' => $userId,
			':entity_type' => $entityType,
			':entity_id' => $entityId,
		]);

		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::FAVORITE_REMOVED,
			'entity_type' => $entityType,
			'entity_id' => $entityId,
			'success' => $ok ? 1 : 0,
			'payload' => [
				'entity_type' => $entityType,
				'entity_id' => $entityId,
			],
		]);

		if ($ok) {
			$this->favoriteAnalyticsService->track($userId, $entityType, $entityId, 'remove');
		}

		return $ok;
	}

	public function toggleFavorite(int $userId, string $entityType, int $entityId): bool
	{
		if ($this->isFavorited($userId, $entityType, $entityId)) {
			return $this->removeFavorite($userId, $entityType, $entityId);
		}

		return $this->addFavorite($userId, $entityType, $entityId);
	}

	public function getUserFavorites(int $userId, ?string $entityType = null): array
	{
		$sql = "SELECT id, user_id, entity_type, entity_id, created_at FROM user_favorites WHERE user_id = :user_id";
		$params = [':user_id' => $userId];

		if ($entityType !== null) {
			$sql .= " AND entity_type = :entity_type";
			$params[':entity_type'] = $entityType;
		}

		$sql .= " ORDER BY created_at DESC";

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function countUserFavorites(int $userId): int
	{
		$stmt = $this->db->prepare("SELECT COUNT(*) FROM user_favorites WHERE user_id = :user_id");
		$stmt->execute([':user_id' => $userId]);
		return (int) $stmt->fetchColumn();
	}

	public function countUserFavoritesByType(int $userId, string $entityType): int
	{
		$stmt = $this->db->prepare(
			"SELECT COUNT(*) FROM user_favorites WHERE user_id = :user_id AND entity_type = :entity_type"
		);
		$stmt->execute([
			':user_id' => $userId,
			':entity_type' => $entityType,
		]);

		return (int) $stmt->fetchColumn();
	}

	private function notifyFirstFavorite(int $userId, string $entityType, int $entityId): void
	{
		if ($entityType !== 'event') {
			return;
		}

		$event = $this->findEventNotificationData($entityId);
		if ($event === null) {
			return;
		}

		$title = (string) ($event['titolo'] ?? 'Evento');
		$slug = (string) ($event['slug'] ?? '');
		$this->notificationService->createNotification([
			'user_id' => $userId,
			'notification_type' => 'favorite_created',
			'title' => 'Evento salvato nei preferiti',
			'message' => $title . ' è ora nei tuoi preferiti.',
			'source_entity_type' => 'event',
			'source_entity_id' => $entityId,
			'payload' => [
				'return_url' => $slug !== '' ? '/eventi-cosplay/' . $slug : null,
			],
		]);
	}

	private function findEventNotificationData(int $eventId): ?array
	{
		$stmt = $this->db->prepare("SELECT titolo, slug FROM events WHERE id = :id LIMIT 1");
		$stmt->execute([':id' => $eventId]);
		$event = $stmt->fetch(PDO::FETCH_ASSOC);

		return is_array($event) ? $event : null;
	}
}
