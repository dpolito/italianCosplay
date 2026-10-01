<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ApiClientRepository
{
	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Database::getInstance()->getConnection();
	}

	public function allWithUsageToday(): array
	{
		$stmt = $this->db->query("
			SELECT c.*,
				COALESCE(today.request_count, 0) AS requests_today
			FROM api_clients c
			LEFT JOIN api_usage_counters today
				ON today.api_client_id = c.id
				AND today.window_type = 'day'
				AND today.window_start = DATE_FORMAT(NOW(), '%Y-%m-%d 00:00:00')
			WHERE c.deleted_at IS NULL
			ORDER BY c.created_at DESC, c.id DESC
		");

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function find(int $id): ?array
	{
		$stmt = $this->db->prepare("SELECT * FROM api_clients WHERE id = :id AND deleted_at IS NULL LIMIT 1");
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function findByPrefix(string $prefix): ?array
	{
		$stmt = $this->db->prepare("SELECT * FROM api_clients WHERE key_prefix = :key_prefix AND deleted_at IS NULL LIMIT 1");
		$stmt->execute([':key_prefix' => $prefix]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function create(array $data): int
	{
		$stmt = $this->db->prepare("
			INSERT INTO api_clients
				(name, description, environment, key_prefix, key_hash, status, requests_per_minute, requests_per_day, expires_at, admin_notes, created_at, updated_at)
			VALUES
				(:name, :description, :environment, :key_prefix, :key_hash, :status, :requests_per_minute, :requests_per_day, :expires_at, :admin_notes, NOW(), NOW())
		");
		$stmt->execute([
			':name' => $data['name'],
			':description' => $data['description'] ?? null,
			':environment' => $data['environment'],
			':key_prefix' => $data['key_prefix'],
			':key_hash' => $data['key_hash'],
			':status' => $data['status'] ?? 'active',
			':requests_per_minute' => $data['requests_per_minute'],
			':requests_per_day' => $data['requests_per_day'],
			':expires_at' => $data['expires_at'] ?? null,
			':admin_notes' => $data['admin_notes'] ?? null,
		]);

		return (int) $this->db->lastInsertId();
	}

	public function update(int $id, array $data): bool
	{
		$stmt = $this->db->prepare("
			UPDATE api_clients
			SET name = :name,
				description = :description,
				environment = :environment,
				requests_per_minute = :requests_per_minute,
				requests_per_day = :requests_per_day,
				expires_at = :expires_at,
				admin_notes = :admin_notes,
				updated_at = NOW()
			WHERE id = :id AND deleted_at IS NULL
		");

		return $stmt->execute([
			':id' => $id,
			':name' => $data['name'],
			':description' => $data['description'] ?? null,
			':environment' => $data['environment'],
			':requests_per_minute' => $data['requests_per_minute'],
			':requests_per_day' => $data['requests_per_day'],
			':expires_at' => $data['expires_at'] ?? null,
			':admin_notes' => $data['admin_notes'] ?? null,
		]);
	}

	public function updateStatus(int $id, string $status): bool
	{
		$stmt = $this->db->prepare("UPDATE api_clients SET status = :status, updated_at = NOW() WHERE id = :id AND deleted_at IS NULL");

		return $stmt->execute([':id' => $id, ':status' => $status]);
	}

	public function rotateKey(int $id, string $prefix, string $hash): bool
	{
		$stmt = $this->db->prepare("
			UPDATE api_clients
			SET key_prefix = :key_prefix,
				key_hash = :key_hash,
				rotated_at = NOW(),
				updated_at = NOW()
			WHERE id = :id AND deleted_at IS NULL
		");

		return $stmt->execute([':id' => $id, ':key_prefix' => $prefix, ':key_hash' => $hash]);
	}

	public function touchUsage(int $id, ?string $ip): void
	{
		$stmt = $this->db->prepare("UPDATE api_clients SET last_used_at = NOW(), last_ip = :last_ip WHERE id = :id");
		$stmt->execute([':id' => $id, ':last_ip' => $ip]);
	}

	public function scopes(int $id): array
	{
		$stmt = $this->db->prepare("SELECT scope FROM api_client_scopes WHERE api_client_id = :id ORDER BY scope ASC");
		$stmt->execute([':id' => $id]);

		return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'scope');
	}

	public function replaceScopes(int $id, array $scopes): void
	{
		$this->db->prepare("DELETE FROM api_client_scopes WHERE api_client_id = :id")->execute([':id' => $id]);
		$stmt = $this->db->prepare("INSERT INTO api_client_scopes (api_client_id, scope, created_at) VALUES (:api_client_id, :scope, NOW())");
		foreach ($scopes as $scope) {
			$stmt->execute([':api_client_id' => $id, ':scope' => $scope]);
		}
	}

	public function usageCount(int $id, string $windowType, string $windowStart): int
	{
		$stmt = $this->db->prepare("SELECT request_count FROM api_usage_counters WHERE api_client_id = :id AND window_type = :window_type AND window_start = :window_start LIMIT 1");
		$stmt->execute([':id' => $id, ':window_type' => $windowType, ':window_start' => $windowStart]);

		return (int) ($stmt->fetchColumn() ?: 0);
	}

	public function incrementUsage(int $id, string $windowType, string $windowStart): int
	{
		$stmt = $this->db->prepare("
			INSERT INTO api_usage_counters (api_client_id, window_type, window_start, request_count, updated_at)
			VALUES (:api_client_id, :window_type, :window_start, 1, NOW())
			ON DUPLICATE KEY UPDATE request_count = request_count + 1, updated_at = NOW()
		");
		$stmt->execute([':api_client_id' => $id, ':window_type' => $windowType, ':window_start' => $windowStart]);

		return $this->usageCount($id, $windowType, $windowStart);
	}
}
