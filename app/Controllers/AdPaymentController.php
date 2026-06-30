<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AdCampaignService;
use App\Services\AdPaymentService;
use Exception;

class AdPaymentController extends Controller
{
	private AdPaymentService $paymentService;
	private AdCampaignService $campaignService;

	public function __construct()
	{
		$this->paymentService = new AdPaymentService();
		$this->campaignService = new AdCampaignService();
	}

	public function status(array $params): void
	{
		$campaignId = (int)($params[0] ?? 0);
		$campaign = $this->campaignService->findById($campaignId);

		if (!$campaign || (int)$campaign['user_id'] !== (int)$_SESSION['user_id']) {
			Session::setFlash('error', 'Non autorizzato.');
			header('Location: /dashboard/ads/campaigns');
			exit();
		}

		$this->view('dashboard/ads/payments/status', [
			'campaign' => $campaign,
			'payment' => $this->paymentService->getByCampaign($campaignId),
		], 'dashboard');
	}

	public function webhook(): void
	{
		$payload = file_get_contents('php://input') ?: '';
		$signature = $_SERVER['HTTP_ADYEN_SIGNATURE'] ?? null;

		try {
			$event = $this->paymentService->validateWebhook($payload, $signature);
			$type = $event['type'] ?? $event['eventCode'] ?? null;
			$data = $event['data'] ?? $event;
			$paymentId = (int)($data['payment_id'] ?? 0);
			$campaignId = (int)($data['campaign_id'] ?? 0);

			if ($type === 'payment.success' || $type === 'AUTHORISATION') {
				$this->paymentService->markAsPaid($paymentId, $data['pspReference'] ?? null);
				$this->campaignService->markAsPaid($campaignId);
			}

			if ($type === 'payment.failed' || $type === 'CANCELLATION') {
				$this->paymentService->markAsFailed($paymentId);
				$this->campaignService->markAsFailed($campaignId);
			}

			header('Content-Type: application/json');
			echo json_encode(['status' => 'ok']);
			exit();
		} catch (Exception $e) {
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['error' => $e->getMessage()]);
			exit();
		}
	}
}
