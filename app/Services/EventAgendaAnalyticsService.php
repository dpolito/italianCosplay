<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use Throwable;

class EventAgendaAnalyticsService
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function track(int $userId, int $eventId, string $action, ?string $status = null, ?string $previousStatus = null): void
	{
		if (!in_array($action, ['set', 'remove'], true)) {
			return;
		}

		try {
			$stmt = $this->db->prepare(
				"INSERT INTO user_event_agenda_events (
					user_id,
					event_id,
					action,
					status,
					previous_status,
					request_path,
					referrer,
					created_at
				) VALUES (
					:user_id,
					:event_id,
					:action,
					:status,
					:previous_status,
					:request_path,
					:referrer,
					NOW()
				)"
			);

			$stmt->execute([
				':user_id' => $userId,
				':event_id' => $eventId,
				':action' => $action,
				':status' => $status,
				':previous_status' => $previousStatus,
				':request_path' => $this->limitString($_SERVER['REQUEST_URI'] ?? null, 255),
				':referrer' => $this->limitString($_SERVER['HTTP_REFERER'] ?? null, 500),
			]);
		} catch (Throwable $exception) {
			error_log('Event agenda analytics tracking failed: ' . $exception->getMessage());
		}
	}

	public function getActionCounts(?string $fromDate = null, ?string $toDate = null): array
	{
		try {
			$sql = "SELECT action, status, COUNT(*) AS total
				FROM user_event_agenda_events
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

			$sql .= " GROUP BY action, status ORDER BY action ASC, status ASC";

			$stmt = $this->db->prepare($sql);
			$stmt->execute($params);

			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (Throwable $exception) {
			error_log('Event agenda analytics report failed: ' . $exception->getMessage());
			return [];
		}
	}

	public function getDailyTrend(int $days = 30): array
	{
		try {
			$days = max(1, min($days, 365));
			$stmt = $this->db->prepare(
				"SELECT DATE(created_at) AS event_date, action, status, COUNT(*) AS total
				FROM user_event_agenda_events
				WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
				GROUP BY DATE(created_at), action, status
				ORDER BY event_date ASC, action ASC, status ASC"
			);
			$stmt->bindValue(':days', $days, PDO::PARAM_INT);
			$stmt->execute();

			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (Throwable $exception) {
			error_log('Event agenda analytics trend failed: ' . $exception->getMessage());
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
