<?php

namespace App\Services;

use App\Repositories\AdTrackingRepository;

class AdTrackingService
{
	private AdTrackingRepository $trackingRepository;

	public function __construct()
	{
		$this->trackingRepository = new AdTrackingRepository();
	}

	public function trackImpression(array $data): bool
	{
		if (!$this->isValid($data)) {
			return false;
		}

		$data = $this->withRequestContext($data);

		if ($this->trackingRepository->isDuplicateImpression($data)) {
			return false;
		}

		$data['created_at'] = date('Y-m-d H:i:s');

		return $this->trackingRepository->insertImpression($data);
	}

	public function trackClick(array $data): bool
	{
		if (!$this->isValid($data)) {
			return false;
		}

		$data = $this->withRequestContext($data);
		$data['referrer'] = $_SERVER['HTTP_REFERER'] ?? null;
		$data['created_at'] = date('Y-m-d H:i:s');

		return $this->trackingRepository->insertClick($data);
	}

	private function withRequestContext(array $data): array
	{
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

		$data['ip_hash'] = $ip ? hash('sha256', $ip) : null;
		$data['user_agent_hash'] = $userAgent ? hash('sha256', $userAgent) : null;
		$data['user_hash'] = $data['user_hash'] ?? hash('sha256', $ip . '|' . $userAgent);
		$data['device_type'] = $this->detectDevice($userAgent);

		return $data;
	}

	private function detectDevice(string $userAgent): string
	{
		$ua = strtolower($userAgent);

		if (str_contains($ua, 'bot') || str_contains($ua, 'crawler') || str_contains($ua, 'spider')) {
			return 'bot';
		}

		if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
			return 'tablet';
		}

		if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
			return 'mobile';
		}

		return 'desktop';
	}

	private function isValid(array $data): bool
	{
		return !empty($data['campaign_id'])
			&& !empty($data['banner_id'])
			&& !empty($data['position_id']);
	}
}
