<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ApiRequestLogRepository;

final class ApiAuditService
{
	private float $startedAt;

	public function __construct(private ?ApiRequestLogRepository $logs = null)
	{
		$this->logs = $logs ?? new ApiRequestLogRepository();
		$this->startedAt = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
	}

	public function log(?array $client, int $statusCode, ?string $errorCode = null, bool $rateLimited = false): void
	{
		$endpoint = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';
		$this->logs->insert([
			'api_client_id' => $client['id'] ?? null,
			'key_prefix' => $client['key_prefix'] ?? null,
			'endpoint' => $endpoint,
			'http_method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
			'status_code' => $statusCode,
			'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
			'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
			'response_time_ms' => (int) round((microtime(true) - $this->startedAt) * 1000),
			'rate_limited' => $rateLimited,
			'error_code' => $errorCode,
		]);
	}
}
