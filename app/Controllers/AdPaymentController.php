<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AdCampaignService;
use App\Services\AdPaymentService;
use App\Services\AuditLogService;
use App\Support\AuditLogActionType;
use Exception;

class AdPaymentController extends Controller
{
	private AdPaymentService $paymentService;
	private AdCampaignService $campaignService;
	private AuditLogService $auditLogService;

	public function __construct()
	{
		$this->paymentService = new AdPaymentService();
		$this->campaignService = new AdCampaignService();
		$this->auditLogService = new AuditLogService();
	}

	public function status(array $params): void
	{
		$this->requireFeature('enable_advertising', 'Advertising temporaneamente disattivato.');
		$campaignId = (int)($params[0] ?? 0);
		$paymentId = (int)($_GET['payment_id'] ?? 0);
		$campaign = $this->campaignService->findById($campaignId);

		if (!$campaign || (int)$campaign['user_id'] !== (int)$_SESSION['user_id']) {
			Session::setFlash('error', 'Non autorizzato.');
			header('Location: /dashboard/ads/campaigns');
			exit();
		}

		$payment = $paymentId > 0
			? $this->paymentService->findById($paymentId)
			: $this->paymentService->getByCampaign($campaignId);

		$this->view('dashboard/ads/payments/status', [
			'campaign' => $campaign,
			'payment' => $payment,
		], 'dashboard');
	}

	public function webhook(): void
	{
		$this->requireFeature('enable_advertising', 'Advertising temporaneamente disattivato.');
		$payload = file_get_contents('php://input') ?: '';
		$signature = $_SERVER['HTTP_ADYEN_SIGNATURE'] ?? null;

		try {
			$event = $this->paymentService->validateWebhook($payload, $signature);
			$this->auditLogService->logAudit([
				'user_id' => null,
				'action_type' => AuditLogActionType::AD_PAYMENT_WEBHOOK_RECEIVED,
				'entity_type' => 'ad_payment_webhook',
				'entity_id' => null,
				'success' => 1,
				'payload' => [
					'type' => $event['type'] ?? $event['eventCode'] ?? null,
					'payment_id' => $event['data']['payment_id'] ?? $event['payment_id'] ?? null,
					'campaign_id' => $event['data']['campaign_id'] ?? $event['campaign_id'] ?? null,
				],
			]);
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
			$this->auditLogService->logAudit([
				'user_id' => null,
				'action_type' => AuditLogActionType::AD_PAYMENT_WEBHOOK_FAILED,
				'entity_type' => 'ad_payment_webhook',
				'entity_id' => null,
				'success' => 0,
				'error_message' => $e->getMessage(),
				'payload' => [
					'payload_preview' => mb_substr($payload, 0, 500),
				],
			]);
			http_response_code(400);
			header('Content-Type: application/json');
			echo json_encode(['error' => $e->getMessage()]);
			exit();
		}
	}
}
