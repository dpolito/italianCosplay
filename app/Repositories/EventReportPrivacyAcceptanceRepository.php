<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class EventReportPrivacyAcceptanceRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function recordAcceptance(array $data): bool
	{
		$stmt = $this->db->prepare(
			'INSERT INTO event_report_privacy_acceptances
				(event_id, privacy_policy_version_id, accepted_at, ip_address, user_agent, created_at, updated_at)
			 VALUES
				(:event_id, :privacy_policy_version_id, NOW(), :ip_address, :user_agent, NOW(), NULL)
			 ON DUPLICATE KEY UPDATE
				privacy_policy_version_id = VALUES(privacy_policy_version_id),
				accepted_at = VALUES(accepted_at),
				ip_address = VALUES(ip_address),
				user_agent = VALUES(user_agent),
				updated_at = NOW()'
		);

		return $stmt->execute([
			'event_id' => (int) $data['event_id'],
			'privacy_policy_version_id' => (int) $data['privacy_policy_version_id'],
			'ip_address' => $data['ip_address'] ?? null,
			'user_agent' => $data['user_agent'] ?? null,
		]);
	}
}
