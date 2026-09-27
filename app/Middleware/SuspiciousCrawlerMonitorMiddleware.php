<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Middleware;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

class SuspiciousCrawlerMonitorMiddleware extends Middleware
{
	private const USER_AGENT_PATTERN = 'italiancosplay.it';
	private const LOG_PATH = APP_ROOT . '/storage/logs/suspicious-crawler.log';
	private const MAX_VALUE_LENGTH = 2000;
	private const WATCHED_IPS = [
		'45.88.189.1',
		'80.241.210.1',
		'212.47.75.1',
		'213.136.64.1',
		'62.169.17.1',
	];

	private const HEADER_KEYS = [
		'HTTP_X_FORWARDED_FOR' => 'x_forwarded_for',
		'HTTP_X_REAL_IP' => 'x_real_ip',
		'HTTP_CLIENT_IP' => 'client_ip',
		'HTTP_CF_CONNECTING_IP' => 'cf_connecting_ip',
		'HTTP_TRUE_CLIENT_IP' => 'true_client_ip',
		'HTTP_FORWARDED' => 'forwarded',
		'HTTP_USER_AGENT' => 'user_agent',
		'HTTP_REFERER' => 'referer',
		'HTTP_HOST' => 'host',
		'HTTP_ACCEPT' => 'accept',
		'HTTP_ACCEPT_LANGUAGE' => 'accept_language',
		'HTTP_ACCEPT_ENCODING' => 'accept_encoding',
		'HTTP_CONNECTION' => 'connection',
		'HTTP_SEC_FETCH_SITE' => 'sec_fetch_site',
		'HTTP_SEC_FETCH_MODE' => 'sec_fetch_mode',
		'HTTP_SEC_FETCH_DEST' => 'sec_fetch_dest',
		'HTTP_SEC_FETCH_USER' => 'sec_fetch_user',
		'HTTP_SEC_CH_UA' => 'sec_ch_ua',
		'HTTP_SEC_CH_UA_MOBILE' => 'sec_ch_ua_mobile',
		'HTTP_SEC_CH_UA_PLATFORM' => 'sec_ch_ua_platform',
		'HTTP_UPGRADE_INSECURE_REQUESTS' => 'upgrade_insecure_requests',
	];

	public function handle(): void
	{
		$reasons = $this->getMonitoringReasons();

		if ($reasons === []) {
			return;
		}

		$this->writeLog($this->buildLogEntry($reasons));
	}

	private function getMonitoringReasons(): array
	{
		$reasons = [];
		$userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
		$remoteAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

		if ($userAgent !== '' && stripos($userAgent, self::USER_AGENT_PATTERN) !== false) {
			$reasons[] = 'suspicious_user_agent';
		}

		if (in_array($remoteAddress, self::WATCHED_IPS, true)) {
			$reasons[] = 'watched_ip';
		}

		return $reasons;
	}

	private function buildLogEntry(array $reasons): array
	{
		$entry = [
			'timestamp' => (new DateTimeImmutable('now', new DateTimeZone('Europe/Rome')))->format('Y-m-d H:i:s'),
			'reason' => count($reasons) === 1 ? $reasons[0] : $reasons,
			'ip' => $this->serverValue('REMOTE_ADDR'),
			'method' => $this->serverValue('REQUEST_METHOD'),
			'uri' => $this->serverValue('REQUEST_URI'),
		];

		foreach (self::HEADER_KEYS as $serverKey => $logKey) {
			$entry[$logKey] = $this->serverValue($serverKey);
		}

		return $entry;
	}

	private function serverValue(string $key): ?string
	{
		if (!isset($_SERVER[$key]) || !is_scalar($_SERVER[$key])) {
			return null;
		}

		$value = trim((string) $_SERVER[$key]);

		if ($value === '') {
			return null;
		}

		$value = str_replace(["\r", "\n"], ['\r', '\n'], $value);

		if (strlen($value) > self::MAX_VALUE_LENGTH) {
			return substr($value, 0, self::MAX_VALUE_LENGTH) . '...';
		}

		return $value;
	}

	private function writeLog(array $entry): void
	{
		try {
			$directory = dirname(self::LOG_PATH);

			if (!is_dir($directory)) {
				mkdir($directory, 0750, true);
			}

			$line = json_encode(
				$entry,
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
			);

			if ($line === false) {
				return;
			}

			file_put_contents(self::LOG_PATH, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
		} catch (Throwable $exception) {
			error_log('Suspicious crawler monitor failed: ' . $exception->getMessage());
		}
	}
}
