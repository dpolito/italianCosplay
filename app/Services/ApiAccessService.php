<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ApiClientRepository;

final class ApiAccessService
{
	public function __construct(
		private ?ApiClientRepository $clients = null,
		private ?ApiKeyService $keys = null,
		private ?ApiRateLimitService $rateLimiter = null,
		private ?ApiAuditService $audit = null
	) {
		$this->clients = $clients ?? new ApiClientRepository();
		$this->keys = $keys ?? new ApiKeyService();
		$this->rateLimiter = $rateLimiter ?? new ApiRateLimitService($this->clients);
		$this->audit = $audit ?? new ApiAuditService();
	}

	public function authorize(string $requiredScope): void
	{
		$key = $this->bearerToken();
		if ($key === null) {
			$this->deny(401, 'API_KEY_MISSING', 'Missing Bearer API key.');
		}
		$environment = $this->keys->environmentFromKey($key);
		if ($environment === null) {
			$this->deny(401, 'API_KEY_INVALID', 'Invalid API key format.');
		}
		$client = $this->clients->findByPrefix($this->keys->prefix($key));
		if (!$client || !$this->keys->verify($key, (string) $client['key_hash'])) {
			$this->deny(401, 'API_KEY_INVALID', 'Invalid API key.', $client);
		}
		if ((string) $client['environment'] !== $environment) {
			$this->deny(401, 'API_KEY_ENVIRONMENT_MISMATCH', 'API key environment mismatch.', $client);
		}
		if ((string) $client['status'] !== 'active') {
			$this->deny(403, 'API_CLIENT_INACTIVE', 'API client is not active.', $client);
		}
		if (!empty($client['expires_at']) && strtotime((string) $client['expires_at']) < time()) {
			$this->deny(403, 'API_KEY_EXPIRED', 'API key expired.', $client);
		}
		$scopes = $this->clients->scopes((int) $client['id']);
		if (!in_array($requiredScope, $scopes, true)) {
			$this->deny(403, 'SCOPE_MISSING', 'Missing required scope.', $client);
		}
		$rate = $this->rateLimiter->consume($client);
		$this->rateHeaders($rate);
		if ($rate['limited']) {
			$this->audit->log($client, 429, 'RATE_LIMIT_EXCEEDED', true);
			$this->jsonError(429, 'RATE_LIMIT_EXCEEDED', 'Rate limit exceeded.');
		}
		$this->clients->touchUsage((int) $client['id'], $_SERVER['REMOTE_ADDR'] ?? null);
		$GLOBALS['api_client'] = $client;
		$GLOBALS['api_required_scope'] = $requiredScope;
	}

	public function logSuccess(int $statusCode = 200): void
	{
		$this->audit->log($GLOBALS['api_client'] ?? null, $statusCode);
	}

	private function bearerToken(): ?string
	{
		$header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
		if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches) !== 1) {
			return null;
		}

		return trim($matches[1]);
	}

	private function deny(int $status, string $code, string $message, ?array $client = null): void
	{
		$this->audit->log($client, $status, $code);
		$this->jsonError($status, $code, $message);
	}

	private function jsonError(int $status, string $code, string $message): void
	{
		http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-store');
		echo json_encode([
			'success' => false,
			'error' => [
				'code' => $code,
				'message' => $message,
			],
		], JSON_UNESCAPED_UNICODE);
		exit();
	}

	private function rateHeaders(array $rate): void
	{
		header('X-RateLimit-Limit: ' . (int) $rate['limit']);
		header('X-RateLimit-Remaining: ' . (int) $rate['remaining']);
		header('X-RateLimit-Reset: ' . (int) $rate['reset']);
	}
}
