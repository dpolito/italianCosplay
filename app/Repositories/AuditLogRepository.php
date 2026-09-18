<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AuditLogRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function insert(array $data): bool
	{
		$errorMessage = $data['error_message'] ?? null;
		if ($errorMessage !== null) {
			$errorMessage = mb_substr((string) $errorMessage, 0, 255);
		}

		$stmt = $this->db->prepare("
			INSERT INTO audit_logs
			(user_id, action_type, entity_type, entity_id, payload_json, ip_address, user_agent, request_uri, http_method, success, error_message, created_at)
			VALUES
			(:user_id, :action_type, :entity_type, :entity_id, :payload_json, :ip_address, :user_agent, :request_uri, :http_method, :success, :error_message, :created_at)
		");

		return $stmt->execute([
			':user_id' => $data['user_id'] ?? null,
			':action_type' => $data['action_type'],
			':entity_type' => $data['entity_type'],
			':entity_id' => $data['entity_id'] ?? null,
			':payload_json' => $data['payload_json'] ?? '{}',
			':ip_address' => $data['ip_address'] ?? null,
			':user_agent' => $data['user_agent'] ?? null,
			':request_uri' => $data['request_uri'] ?? null,
			':http_method' => $data['http_method'] ?? null,
			':success' => isset($data['success']) ? (int) $data['success'] : 1,
			':error_message' => $errorMessage,
			':created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
		]);
	}

	public function findPaymentLogs(int $limit = 100): array
	{
		$limit = max(1, min($limit, 500));
		$actionTypes = [
			'ad_payment_created',
			'ad_payment_webhook_received',
			'ad_payment_webhook_failed',
			'ad_payment_success',
			'ad_payment_failed',
			'ad_payment_refunded',
		];
		$placeholders = implode(',', array_fill(0, count($actionTypes), '?'));
		$sql = "
			SELECT a.*, u.username
			FROM audit_logs a
			LEFT JOIN users u ON u.id = a.user_id
			WHERE a.action_type IN ({$placeholders})
			ORDER BY a.created_at DESC, a.id DESC
			LIMIT {$limit}
		";
		$stmt = $this->db->prepare($sql);
		$stmt->execute($actionTypes);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}
