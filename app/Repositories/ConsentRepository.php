<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class ConsentRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function recordPrivacyAcceptance(array $data): bool
	{
		$stmt = $this->db->prepare(
			'INSERT INTO privacy_policy_acceptances
				(privacy_policy_version_id, user_id, anonymous_token, accepted_at, ip_address, user_agent, created_at, updated_at)
			 VALUES
				(:privacy_policy_version_id, :user_id, :anonymous_token, NOW(), :ip_address, :user_agent, NOW(), NULL)'
		);

		return $stmt->execute([
			'privacy_policy_version_id' => (int) $data['privacy_policy_version_id'],
			'user_id' => $data['user_id'] ?? null,
			'anonymous_token' => $data['anonymous_token'] ?? null,
			'ip_address' => $data['ip_address'] ?? null,
			'user_agent' => $data['user_agent'] ?? null,
		]);
	}

	public function upsertMarketingConsent(int $userId, bool $optedIn): bool
	{
		$stmt = $this->db->prepare(
			'INSERT INTO user_marketing_consents (user_id, opted_in, opted_in_at, created_at, updated_at)
			 VALUES (:user_id, :opted_in, :opted_in_at, NOW(), NULL)
			 ON DUPLICATE KEY UPDATE
				opted_in = VALUES(opted_in),
				opted_in_at = VALUES(opted_in_at),
				updated_at = NOW()'
		);

		return $stmt->execute([
			'user_id' => $userId,
			'opted_in' => $optedIn ? 1 : 0,
			'opted_in_at' => $optedIn ? date('Y-m-d H:i:s') : null,
		]);
	}

	public function recordAgeDeclaration(int $userId, bool $declaredAdult): bool
	{
		$stmt = $this->db->prepare(
			'INSERT INTO user_age_declarations (user_id, declared_adult, declared_at, created_at, updated_at)
			 VALUES (:user_id, :declared_adult, :declared_at, NOW(), NULL)
			 ON DUPLICATE KEY UPDATE
				declared_adult = VALUES(declared_adult),
				declared_at = VALUES(declared_at),
				updated_at = NOW()'
		);

		return $stmt->execute([
			'user_id' => $userId,
			'declared_adult' => $declaredAdult ? 1 : 0,
			'declared_at' => $declaredAdult ? date('Y-m-d H:i:s') : null,
		]);
	}
}
