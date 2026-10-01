<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ApiRequestLogRepository
{
	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Database::getInstance()->getConnection();
	}

	public function insert(array $data): void
	{
		$stmt = $this->db->prepare("
			INSERT INTO api_request_logs
				(api_client_id, key_prefix, endpoint, http_method, status_code, ip_address, user_agent, response_time_ms, rate_limited, error_code, created_at)
			VALUES
				(:api_client_id, :key_prefix, :endpoint, :http_method, :status_code, :ip_address, :user_agent, :response_time_ms, :rate_limited, :error_code, NOW())
		");
		$stmt->execute([
			':api_client_id' => $data['api_client_id'] ?? null,
			':key_prefix' => $data['key_prefix'] ?? null,
			':endpoint' => mb_substr((string) ($data['endpoint'] ?? ''), 0, 255),
			':http_method' => mb_substr((string) ($data['http_method'] ?? 'GET'), 0, 10),
			':status_code' => (int) ($data['status_code'] ?? 500),
			':ip_address' => $data['ip_address'] ?? null,
			':user_agent' => isset($data['user_agent']) ? mb_substr((string) $data['user_agent'], 0, 255) : null,
			':response_time_ms' => $data['response_time_ms'] ?? null,
			':rate_limited' => !empty($data['rate_limited']) ? 1 : 0,
			':error_code' => $data['error_code'] ?? null,
		]);
	}

	public function summary(): array
	{
		return [
			'today' => $this->countSince('CURDATE()'),
			'last30' => $this->countSince('DATE_SUB(NOW(), INTERVAL 30 DAY)'),
			'errors' => $this->countWhere('status_code >= 400'),
			'http401' => $this->countWhere('status_code = 401'),
			'http403' => $this->countWhere('status_code = 403'),
			'http429' => $this->countWhere('status_code = 429'),
		];
	}

	public function topEndpoints(int $limit = 10): array
	{
		$limit = max(1, min($limit, 25));
		$stmt = $this->db->query("
			SELECT endpoint, COUNT(*) AS request_count
			FROM api_request_logs
			WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
			GROUP BY endpoint
			ORDER BY request_count DESC
			LIMIT {$limit}
		");

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function search(array $filters, int $page, int $perPage): array
	{
		$page = max(1, $page);
		$perPage = max(10, min($perPage, 100));
		$where = [];
		$params = [];
		if (!empty($filters['client_id'])) {
			$where[] = 'l.api_client_id = :client_id';
			$params[':client_id'] = (int) $filters['client_id'];
		}
		if (!empty($filters['endpoint'])) {
			$where[] = 'l.endpoint LIKE :endpoint';
			$params[':endpoint'] = '%' . $filters['endpoint'] . '%';
		}
		if (!empty($filters['status_code'])) {
			$where[] = 'l.status_code = :status_code';
			$params[':status_code'] = (int) $filters['status_code'];
		}
		if (!empty($filters['from'])) {
			$where[] = 'l.created_at >= :from_date';
			$params[':from_date'] = $filters['from'] . ' 00:00:00';
		}
		if (!empty($filters['to'])) {
			$where[] = 'l.created_at <= :to_date';
			$params[':to_date'] = $filters['to'] . ' 23:59:59';
		}
		$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
		$countStmt = $this->db->prepare("SELECT COUNT(*) FROM api_request_logs l {$whereSql}");
		$countStmt->execute($params);
		$total = (int) $countStmt->fetchColumn();
		$offset = ($page - 1) * $perPage;
		$sql = "
			SELECT l.*, c.name AS client_name
			FROM api_request_logs l
			LEFT JOIN api_clients c ON c.id = l.api_client_id
			{$whereSql}
			ORDER BY l.created_at DESC, l.id DESC
			LIMIT {$perPage} OFFSET {$offset}
		";
		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		return ['items' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page, 'perPage' => $perPage];
	}

	public function purgeOlderThan(int $days): int
	{
		$days = max(1, min($days, 3650));
		$stmt = $this->db->prepare("DELETE FROM api_request_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL {$days} DAY)");
		$stmt->execute();

		return $stmt->rowCount();
	}

	private function countSince(string $sqlDateExpression): int
	{
		$stmt = $this->db->query("SELECT COUNT(*) FROM api_request_logs WHERE created_at >= {$sqlDateExpression}");
		return (int) $stmt->fetchColumn();
	}

	private function countWhere(string $where): int
	{
		$stmt = $this->db->query("SELECT COUNT(*) FROM api_request_logs WHERE {$where}");
		return (int) $stmt->fetchColumn();
	}
}
