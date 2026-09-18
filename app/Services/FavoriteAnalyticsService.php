<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use Throwable;

class FavoriteAnalyticsService
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function track(int $userId, string $entityType, int $entityId, string $action): void
	{
		if (!in_array($action, ['add', 'remove'], true)) {
			return;
		}

		try {
			$stmt = $this->db->prepare(
				"INSERT INTO user_favorite_events (
					user_id,
					entity_type,
					entity_id,
					action,
					request_path,
					referrer,
					created_at
				) VALUES (
					:user_id,
					:entity_type,
					:entity_id,
					:action,
					:request_path,
					:referrer,
					NOW()
				)"
			);

			$stmt->execute([
				':user_id' => $userId,
				':entity_type' => $entityType,
				':entity_id' => $entityId,
				':action' => $action,
				':request_path' => $this->limitString($_SERVER['REQUEST_URI'] ?? null, 255),
				':referrer' => $this->limitString($_SERVER['HTTP_REFERER'] ?? null, 500),
			]);
		} catch (Throwable $exception) {
			error_log('Favorite analytics tracking failed: ' . $exception->getMessage());
		}
	}

	public function getActionCountsByType(?string $fromDate = null, ?string $toDate = null): array
	{
		try {
			$sql = "SELECT entity_type, action, COUNT(*) AS total
				FROM user_favorite_events
				WHERE 1 = 1";
			$params = [];

			if ($fromDate !== null) {
				$sql .= " AND created_at >= :from_date";
				$params[':from_date'] = $fromDate . ' 00:00:00';
			}

			if ($toDate !== null) {
				$sql .= " AND created_at <= :to_date";
				$params[':to_date'] = $toDate . ' 23:59:59';
			}

			$sql .= " GROUP BY entity_type, action ORDER BY entity_type ASC, action ASC";

			$stmt = $this->db->prepare($sql);
			$stmt->execute($params);

			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (Throwable $exception) {
			error_log('Favorite analytics report failed: ' . $exception->getMessage());
			return [];
		}
	}

	public function getDailyTrend(int $days = 30): array
	{
		try {
			$days = max(1, min($days, 365));
			$stmt = $this->db->prepare(
				"SELECT DATE(created_at) AS event_date, entity_type, action, COUNT(*) AS total
				FROM user_favorite_events
				WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
				GROUP BY DATE(created_at), entity_type, action
				ORDER BY event_date ASC, entity_type ASC, action ASC"
			);
			$stmt->bindValue(':days', $days, PDO::PARAM_INT);
			$stmt->execute();

			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (Throwable $exception) {
			error_log('Favorite analytics trend failed: ' . $exception->getMessage());
			return [];
		}
	}

	private function limitString(?string $value, int $limit): ?string
	{
		if ($value === null || $value === '') {
			return null;
		}

		return mb_substr($value, 0, $limit);
	}
}
