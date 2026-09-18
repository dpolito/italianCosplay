<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use Throwable;

class UserRegistrationAnalyticsService
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function getOverview(int $days = 30): array
	{
		try {
			$days = max(1, min($days, 365));
			$stmt = $this->db->prepare(
				"SELECT
					COUNT(*) AS total,
					SUM(CASE WHEN verified = 1 THEN 1 ELSE 0 END) AS activated,
					SUM(CASE WHEN verified = 0 THEN 1 ELSE 0 END) AS not_activated
				FROM users
				WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)"
			);
			$stmt->bindValue(':days', $days, PDO::PARAM_INT);
			$stmt->execute();

			$row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
			$total = (int) ($row['total'] ?? 0);
			$activated = (int) ($row['activated'] ?? 0);
			$notActivated = (int) ($row['not_activated'] ?? 0);

			return [
				'days' => $days,
				'total' => $total,
				'activated' => $activated,
				'not_activated' => $notActivated,
				'activation_rate' => $total > 0 ? round(($activated / $total) * 100, 2) : 0.0,
			];
		} catch (Throwable $exception) {
			error_log('User registration analytics failed: ' . $exception->getMessage());
			return [
				'days' => $days,
				'total' => 0,
				'activated' => 0,
				'not_activated' => 0,
				'activation_rate' => 0.0,
			];
		}
	}
}
