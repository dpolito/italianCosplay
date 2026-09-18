<?php

namespace App\Services;

use App\Repositories\AdCampaignRepository;
use App\Repositories\AdStatsRepository;
use App\ValueObjects\Money;

class AdStatsService
{
	private AdStatsRepository $statsRepository;
	private AdCampaignRepository $campaignRepository;

	public function __construct()
	{
		$this->statsRepository = new AdStatsRepository();
		$this->campaignRepository = new AdCampaignRepository();
	}

	public function getUserOverview(int $userId): array
	{
		$campaigns = $this->campaignRepository->findByUser($userId);
		$impressions = 0;
		$clicks = 0;
		$spend = Money::zero('EUR');
		$active = 0;

		foreach ($campaigns as $campaign) {
			$stats = $this->statsRepository->getCampaignStats((int)$campaign['id']);
			$impressions += $stats['impressions'];
			$clicks += $stats['clicks'];
			$spend = $spend->add(Money::fromDecimal($campaign['price'] ?? 0, $campaign['currency'] ?? 'EUR'));
			if ($campaign['status'] === 'active') {
				$active++;
			}
		}

		return [
			'campaigns' => count($campaigns),
			'active_campaigns' => $active,
			'impressions' => $impressions,
			'clicks' => $clicks,
			'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
			'spend' => $spend->toDecimal(),
		];
	}

	public function getCampaignStats(int $campaignId): array
	{
		$stats = $this->statsRepository->getCampaignStats($campaignId);
		$stats['daily'] = $this->getCampaignDaily($campaignId);

		return $stats;
	}

	public function getCampaignDaily(int $campaignId): array
	{
		return $this->statsRepository->getDailyStats($campaignId);
	}

	public function getPositionStats(int $positionId): array
	{
		return $this->statsRepository->getPositionStats($positionId);
	}

	public function getUserCTR(int $userId): float
	{
		return (float)$this->getUserOverview($userId)['ctr'];
	}
}
