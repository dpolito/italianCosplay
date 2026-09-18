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

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
		$this->auditLogService = new AuditLogService();
		$this->favoriteAnalyticsService = new FavoriteAnalyticsService();
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
}
