<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\EventReportAnalyticsRepository;

class EventReportAnalyticsService
{
	private EventReportAnalyticsRepository $repository;

	public function __construct()
	{
		$this->repository = new EventReportAnalyticsRepository();
	}

	public function track(array $data): bool
	{
		$eventName = trim((string) ($data['event_name'] ?? ''));
		$sessionKey = trim((string) ($data['session_key'] ?? ''));

		if ($eventName === '' || $sessionKey === '') {
			return false;
		}

		if ($eventName === 'form_start' && $this->repository->hasSessionEvent($sessionKey, $eventName)) {
			return false;
		}

		$this->withRequestContext($data);
		return $this->repository->recordEvent($data);
	}

	private function withRequestContext(array &$data): void
	{
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

		$data['ip_hash'] = $ip !== '' ? hash('sha256', $ip) : null;
		$data['user_agent_hash'] = $userAgent !== '' ? hash('sha256', $userAgent) : null;
		$data['referrer'] = $_SERVER['HTTP_REFERER'] ?? null;
		$data['page_url'] = $data['page_url'] ?? ($_SERVER['REQUEST_URI'] ?? null);
	}
}
