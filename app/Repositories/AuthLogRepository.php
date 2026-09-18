<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AuthLogRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function insert(array $data): bool
	{
		$stmt = $this->db->prepare("
			INSERT INTO auth_logs
			(user_id, identifier, action_type, success, failure_reason, ip_address, user_agent, request_uri, http_method, payload_json, created_at)
			VALUES
			(:user_id, :identifier, :action_type, :success, :failure_reason, :ip_address, :user_agent, :request_uri, :http_method, :payload_json, :created_at)
		");

		return $stmt->execute([
			':user_id' => $data['user_id'] ?? null,
			':identifier' => $data['identifier'] ?? null,
			':action_type' => $data['action_type'],
			':success' => isset($data['success']) ? (int) $data['success'] : 0,
			':failure_reason' => $data['failure_reason'] ?? null,
			':ip_address' => $data['ip_address'] ?? null,
			':user_agent' => $data['user_agent'] ?? null,
			':request_uri' => $data['request_uri'] ?? null,
			':http_method' => $data['http_method'] ?? null,
			':payload_json' => $data['payload_json'] ?? '{}',
			':created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
		]);
	}
}
