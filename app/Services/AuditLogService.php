<?php

namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Repositories\AuthLogRepository;

class AuditLogService
{
	private AuditLogRepository $auditLogRepository;
	private AuthLogRepository $authLogRepository;

	public function __construct()
	{
		$this->auditLogRepository = new AuditLogRepository();
		$this->authLogRepository = new AuthLogRepository();
	}

	public function logAudit(array $data): bool
	{
		$data = $this->withRequestContext($data);
		$data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');

		return $this->auditLogRepository->insert($data);
	}

	public function logAuth(array $data): bool
	{
		$data = $this->withRequestContext($data);
		$data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');

		return $this->authLogRepository->insert($data);
	}

	public function getPaymentLogs(int $limit = 100): array
	{
		return $this->auditLogRepository->findPaymentLogs($limit);
	}

	private function withRequestContext(array $data): array
	{
		$data['ip_address'] = $data['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
		$data['user_agent'] = $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null);
		$data['request_uri'] = $data['request_uri'] ?? ($_SERVER['REQUEST_URI'] ?? null);
		$data['http_method'] = $data['http_method'] ?? ($_SERVER['REQUEST_METHOD'] ?? null);

		if (isset($data['payload']) && !isset($data['payload_json'])) {
			$data['payload_json'] = json_encode($data['payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		return $data;
	}
}
