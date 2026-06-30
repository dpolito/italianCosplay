<?php

namespace App\Services;

use App\Repositories\AdBannerRepository;
use App\Repositories\AdCampaignRepository;
use App\Repositories\AdPositionRepository;

class AdRotationService
{
	private AdCampaignRepository $campaignRepository;
	private AdPositionRepository $positionRepository;
	private AdBannerRepository $bannerRepository;
	private AdTrackingService $trackingService;

	public function __construct()
	{
		$this->campaignRepository = new AdCampaignRepository();
		$this->positionRepository = new AdPositionRepository();
		$this->bannerRepository   = new AdBannerRepository();
		$this->trackingService    = new AdTrackingService();
	}

	public function getBannerForPosition(
		string $positionCode,
		string $page,
		?string $userHash = null
	): ?array {

		$position = $this->positionRepository->findByCode($positionCode);

		if (!$position || !$position['is_active']) {
			return null;
		}

		$campaigns = $this->campaignRepository
			->findActiveByPosition($position['id']);

		if (empty($campaigns)) {
			return $this->getFallbackBanner();
		}

		$campaign = $this->selectCampaign(
			$campaigns,
			$position['rotation_type']
		);

		if (!$campaign) {
			return null;
		}

		$banner = $this->bannerRepository
			->findById($campaign['banner_id']);

		if (!$banner) {
			return null;
		}

		$this->trackingService->trackImpression([
			'campaign_id' => $campaign['id'],
			'banner_id'   => $banner['id'],
			'position_id' => $position['id'],
			'page'        => $page,
			'user_hash'   => $userHash
		]);

		return [
			'campaign_id' => $campaign['id'],
			'banner_id'   => $banner['id'],
			'title'       => $banner['title'],
			'image'       => $banner['image_path'],
			'target_url'  => '/ads/click/'.$campaign['id']
		];
	}

	/**
	 * Selezione banner
	 */
	private function selectCampaign(
		array $campaigns,
		string $rotationType
	): ?array {

		if (!$campaigns) {
			return null;
		}

		switch ($rotationType) {

			case 'random':
				return $campaigns[array_rand($campaigns)];

			case 'weighted':
				return $this->weightedSelection($campaigns);

			default:
				return $campaigns[0];
		}
	}

	/**
	 * Rotazione pesata
	 */
	private function weightedSelection(array $campaigns): ?array
	{
		$pool = [];

		foreach ($campaigns as $campaign) {

			$score = max(
				1,
				(int)(
					($campaign['ctr'] ?? 1) * 10
					+
					($campaign['price'] ?? 1) / 10
				)
			);

			for ($i = 0; $i < $score; $i++) {
				$pool[] = $campaign;
			}
		}

		return $pool[array_rand($pool)] ?? null;
	}

	/**
	 * Banner interni
	 */
	private function getFallbackBanner(): ?array
	{
		return $this->bannerRepository
			->getRandomInternalBanner();
	}
}
