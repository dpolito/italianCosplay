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
		array $context = []
	): ?array {

		$position = $this->positionRepository->findByCode($positionCode);

		if (!$position || !$position['is_active']) {
			return null;
		}

		$campaigns = $this->campaignRepository
			->findActiveByPosition($position['id']);

		$campaigns = array_values(array_filter($campaigns, function (array $campaign) use ($page, $context): bool {
			return $this->matchesTarget($campaign, $page, $context);
		}));

		if (empty($campaigns)) {
			return null;
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
			'target_type' => $campaign['target_type'] ?? 'national',
			'target_value' => $campaign['target_value'] ?? null,
			'page'        => $page
		]);

		return [
			'campaign_id' => $campaign['id'],
			'banner_id'   => $banner['id'],
			'title'       => $banner['title'],
			'image'       => $banner['image_path'],
			'mobile_image' => $banner['mobile_image_path'] ?? null,
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

			case 'fixed':
				return $campaigns[0];

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

	private function matchesTarget(array $campaign, string $page, array $context): bool
	{
		$targetType = (string)($campaign['target_type'] ?? 'national');
		$targetValue = $campaign['target_value'] ?? null;
		$pageType = (string)($context['page_type'] ?? 'national');

		if ($targetType === 'national') {
			return $pageType === 'national';
		}

		if ($targetType !== $pageType) {
			return false;
		}

		if ($targetType === 'region') {
			return !empty($context['regione_slug'])
				&& (string)$context['regione_slug'] === (string)$targetValue;
		}

		if ($targetType === 'province') {
			return !empty($context['provincia_slug'])
				&& (string)$context['provincia_slug'] === (string)$targetValue;
		}

		return false;
	}
}
