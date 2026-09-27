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
					SUM(CASE WHEN u.verified = 1 THEN 1 ELSE 0 END) AS activated,
					SUM(CASE WHEN u.verified = 0 THEN 1 ELSE 0 END) AS not_activated,
					SUM(CASE WHEN u.verified = 1 AND u.login_count >= 2 THEN 1 ELSE 0 END) AS returned_users,
					SUM(CASE WHEN u.verified = 1 AND (u.last_activity_at IS NOT NULL OR u.login_count >= 2) THEN 1 ELSE 0 END) AS active_users,
					SUM(CASE WHEN u.verified = 1 AND profile_completed.completed = 1 THEN 1 ELSE 0 END) AS profile_completed,
					SUM(CASE WHEN u.verified = 1 AND user_actions.has_action = 1 THEN 1 ELSE 0 END) AS product_activated
				FROM users u
				LEFT JOIN (
					SELECT id,
						CASE
							WHEN avatar IS NOT NULL AND avatar <> ''
							 AND bio IS NOT NULL AND bio <> ''
							THEN 1
							ELSE 0
						END AS completed
					FROM users
				) profile_completed ON profile_completed.id = u.id
				LEFT JOIN (
					SELECT user_id, 1 AS has_action
					FROM (
						SELECT user_id FROM user_favorites
						UNION
						SELECT user_id FROM user_event_agenda
					) actions
					GROUP BY user_id
				) user_actions ON user_actions.user_id = u.id
				WHERE u.created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
				  AND u.anonymized_at IS NULL"
			);
			$stmt->bindValue(':days', $days, PDO::PARAM_INT);
			$stmt->execute();

			$row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
			$total = (int) ($row['total'] ?? 0);
			$activated = (int) ($row['activated'] ?? 0);
			$notActivated = (int) ($row['not_activated'] ?? 0);
			$returnedUsers = (int) ($row['returned_users'] ?? 0);
			$activeUsers = (int) ($row['active_users'] ?? 0);
			$profileCompleted = (int) ($row['profile_completed'] ?? 0);
			$productActivated = (int) ($row['product_activated'] ?? 0);

			return [
				'days' => $days,
				'total' => $total,
				'activated' => $activated,
				'not_activated' => $notActivated,
				'returned_users' => $returnedUsers,
				'active_users' => $activeUsers,
				'profile_completed' => $profileCompleted,
				'product_activated' => $productActivated,
				'activation_rate' => $total > 0 ? round(($activated / $total) * 100, 2) : 0.0,
				'return_rate' => $activated > 0 ? round(($returnedUsers / $activated) * 100, 2) : 0.0,
				'product_activation_rate' => $activated > 0 ? round(($productActivated / $activated) * 100, 2) : 0.0,
			];
		} catch (Throwable $exception) {
			error_log('User registration analytics failed: ' . $exception->getMessage());
			return [
				'days' => $days,
				'total' => 0,
				'activated' => 0,
				'not_activated' => 0,
				'returned_users' => 0,
				'active_users' => 0,
				'profile_completed' => 0,
				'product_activated' => 0,
				'activation_rate' => 0.0,
				'return_rate' => 0.0,
				'product_activation_rate' => 0.0,
			];
		}
	}
}
