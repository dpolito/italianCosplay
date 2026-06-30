<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AdCampaignService;
use App\Services\AdStatsService;

class AdStatsController extends Controller
{
	private AdStatsService $statsService;
	private AdCampaignService $campaignService;

	public function __construct()
	{
		$this->statsService = new AdStatsService();
		$this->campaignService = new AdCampaignService();
	}

	public function overview(): void
	{
		$userId = (int)$_SESSION['user_id'];

		$this->view('dashboard/ads/stats/overview', [
			'data' => $this->statsService->getUserOverview($userId),
		], 'dashboard');
	}

	public function campaign(array $params): void
	{
		$campaignId = (int)($params[0] ?? 0);
		$campaign = $this->campaignService->findById($campaignId);

		if (!$campaign || (int)$campaign['user_id'] !== (int)$_SESSION['user_id']) {
			Session::setFlash('error', 'Campagna non trovata.');
			header('Location: /dashboard/ads/campaigns');
			exit();
		}

		$this->view('dashboard/ads/stats/campaign', [
			'campaign' => $campaign,
			'stats' => $this->statsService->getCampaignStats($campaignId),
		], 'dashboard');
	}

	public function apiCampaignDaily(array $params): void
	{
		$campaignId = (int)($params[0] ?? 0);
		$campaign = $this->campaignService->findById($campaignId);

		if (!$campaign || (int)$campaign['user_id'] !== (int)$_SESSION['user_id']) {
			http_response_code(403);
			echo json_encode(['error' => 'Non autorizzato']);
			exit();
		}

		header('Content-Type: application/json');
		echo json_encode($this->statsService->getCampaignDaily($campaignId));
		exit();
	}
}
