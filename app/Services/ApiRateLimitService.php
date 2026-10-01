<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ApiClientRepository;

final class ApiRateLimitService
{
	public function __construct(private ?ApiClientRepository $clients = null)
	{
		$this->clients = $clients ?? new ApiClientRepository();
	}

	public function consume(array $client): array
	{
		$clientId = (int) $client['id'];
		$minuteLimit = max(1, (int) ($client['requests_per_minute'] ?? 60));
		$dayLimit = max(1, (int) ($client['requests_per_day'] ?? 5000));
		$minuteStart = date('Y-m-d H:i:00');
		$dayStart = date('Y-m-d 00:00:00');

		$minuteCount = $this->clients->incrementUsage($clientId, 'minute', $minuteStart);
		$dayCount = $this->clients->incrementUsage($clientId, 'day', $dayStart);
		$minuteRemaining = max(0, $minuteLimit - $minuteCount);
		$dayRemaining = max(0, $dayLimit - $dayCount);
		$limited = $minuteCount > $minuteLimit || $dayCount > $dayLimit;
		$reset = $minuteCount > $minuteLimit ? strtotime('+1 minute', strtotime($minuteStart)) : strtotime('tomorrow');

		return [
			'limited' => $limited,
			'limit' => $minuteCount > $minuteLimit ? $minuteLimit : $dayLimit,
			'remaining' => min($minuteRemaining, $dayRemaining),
			'reset' => (int) $reset,
			'minute_count' => $minuteCount,
			'day_count' => $dayCount,
		];
	}
}
