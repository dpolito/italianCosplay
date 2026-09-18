<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Throwable;

class BrevoEmailQuotaService
{
	private const API_BASE_URL = 'https://api.brevo.com/v3';

	private string $apiKey;
	private int $dailyLimit;
	private int $cacheTtl;
	private string $cacheFile;

	public function __construct(?array $config = null, ?string $cacheFile = null)
	{
		$config ??= require APP_ROOT . '/app/config/brevo.php';

		$this->apiKey = trim((string) ($config['api_key'] ?? ''));
		$this->dailyLimit = max(0, (int) ($config['daily_transactional_limit'] ?? 300));
		$this->cacheTtl = max(60, (int) ($config['cache_ttl_seconds'] ?? 300));
		$this->cacheFile = $cacheFile ?? APP_ROOT . '/storage/cache/brevo_email_quota.json';
	}

	/**
	 * @return array{
	 *     configured: bool,
	 *     available: bool,
	 *     sentToday: int,
	 *     remaining: int,
	 *     dailyLimit: int,
	 *     status: string,
	 *     updatedAt: ?string,
	 *     error: ?string
	 * }
	 */
	public function getTodayQuota(): array
	{
		if ($this->apiKey === '') {
			return $this->buildQuota(0, false, false, null, 'Brevo API key not configured.');
		}

		$cached = $this->readCache();
		if ($cached !== null) {
			return $cached;
		}

		try {
			$sentToday = $this->fetchSentToday();
			$quota = $this->buildQuota($sentToday, true, true);
			$this->writeCache($quota);

			return $quota;
		} catch (Throwable $exception) {
			error_log('Brevo quota error: ' . $exception->getMessage());

			return $this->buildQuota(0, true, false, null, 'Brevo statistics are currently unavailable.');
		}
	}

	private function fetchSentToday(): int
	{
		$today = (new DateTimeImmutable('now', new DateTimeZone('Europe/Rome')))->format('Y-m-d');
		$response = $this->request('/smtp/statistics/reports', [
			'startDate' => $today,
			'endDate' => $today,
			'limit' => 1,
			'offset' => 0,
			'sort' => 'desc',
		]);

		$reports = $response['reports'] ?? [];
		if (!is_array($reports) || $reports === []) {
			return 0;
		}

		$report = $reports[0];
		if (!is_array($report)) {
			return 0;
		}

		return max(0, (int) ($report['requests'] ?? 0));
	}

	private function request(string $path, array $query): array
	{
		$url = self::API_BASE_URL . $path . '?' . http_build_query($query);
		$response = function_exists('curl_init')
			? $this->requestWithCurl($url)
			: $this->requestWithStream($url);

		if ($response['body'] === '' || $response['statusCode'] < 200 || $response['statusCode'] >= 300) {
			throw new RuntimeException('Brevo API returned HTTP ' . $response['statusCode'] . '.');
		}

		$decoded = json_decode($response['body'], true);
		if (!is_array($decoded)) {
			throw new RuntimeException('Brevo API returned an invalid JSON response.');
		}

		return $decoded;
	}

	/**
	 * @return array{body: string, statusCode: int}
	 */
	private function requestWithCurl(string $url): array
	{
		$handle = curl_init($url);
		if ($handle === false) {
			throw new RuntimeException('Unable to initialize cURL for Brevo API.');
		}

		curl_setopt_array($handle, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				'Accept: application/json',
				'api-key: ' . $this->apiKey,
			],
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 8,
		]);

		$body = curl_exec($handle);
		$statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
		$error = curl_error($handle);
		curl_close($handle);

		if ($body === false) {
			throw new RuntimeException('Brevo API cURL error: ' . $error);
		}

		return [
			'body' => (string) $body,
			'statusCode' => $statusCode,
		];
	}

	/**
	 * @return array{body: string, statusCode: int}
	 */
	private function requestWithStream(string $url): array
	{
		$context = stream_context_create([
			'http' => [
				'method' => 'GET',
				'header' => implode("\r\n", [
					'Accept: application/json',
					'api-key: ' . $this->apiKey,
				]),
				'timeout' => 8,
				'ignore_errors' => true,
			],
		]);

		$body = @file_get_contents($url, false, $context);
		$statusCode = $this->extractStatusCode($http_response_header ?? []);

		return [
			'body' => $body === false ? '' : $body,
			'statusCode' => $statusCode,
		];
	}

	/**
	 * @param array<int, string> $headers
	 */
	private function extractStatusCode(array $headers): int
	{
		$statusLine = $headers[0] ?? '';
		if (preg_match('/\s(\d{3})\s/', $statusLine, $matches) !== 1) {
			return 0;
		}

		return (int) $matches[1];
	}

	private function readCache(): ?array
	{
		if (!is_file($this->cacheFile) || (time() - filemtime($this->cacheFile)) > $this->cacheTtl) {
			return null;
		}

		$data = json_decode((string) file_get_contents($this->cacheFile), true);

		return is_array($data) ? $data : null;
	}

	private function writeCache(array $quota): void
	{
		$directory = dirname($this->cacheFile);
		if (!is_dir($directory)) {
			mkdir($directory, 0755, true);
		}

		file_put_contents($this->cacheFile, json_encode($quota, JSON_PRETTY_PRINT));
	}

	private function buildQuota(
		int $sentToday,
		bool $configured,
		bool $available,
		?string $updatedAt = null,
		?string $error = null
	): array {
		$remaining = max(0, $this->dailyLimit - $sentToday);

		return [
			'configured' => $configured,
			'available' => $available,
			'sentToday' => $sentToday,
			'remaining' => $remaining,
			'dailyLimit' => $this->dailyLimit,
			'status' => $this->resolveStatus($remaining),
			'updatedAt' => $updatedAt ?? (new DateTimeImmutable('now', new DateTimeZone('Europe/Rome')))->format('H:i'),
			'error' => $error,
		];
	}

	private function resolveStatus(int $remaining): string
	{
		if ($remaining <= 0) {
			return 'exhausted';
		}

		if ($remaining < 30) {
			return 'critical';
		}

		if ($remaining <= 100) {
			return 'warning';
		}

		return 'ok';
	}
}
