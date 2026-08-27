<?php

namespace App\Services;

use App\Services\AuditLogService;
use App\Repositories\AdOrderRepository;
use App\Repositories\AdPaymentRepository;
use App\Support\AuditLogActionType;
use App\ValueObjects\Money;
use Exception;

class AdPaymentService
{
	private AdPaymentRepository $paymentRepository;
	private AdOrderRepository $orderRepository;
	private AuditLogService $auditLogService;

	public function __construct()
	{
		$this->paymentRepository = new AdPaymentRepository();
		$this->orderRepository = new AdOrderRepository();
		$this->auditLogService = new AuditLogService();
	}

	public function createCheckoutSession(array $campaign): string
	{
		if (empty($campaign['id'])) {
			throw new Exception('Campagna non valida.');
		}

		$amount = Money::fromDecimal($campaign['price'] ?? 0, $campaign['currency'] ?? 'EUR');
		if (!$amount->isGreaterThanZero()) {
			throw new Exception('Prezzo non valido.');
		}

		$orderId = $campaign['order_id'] ?? null;
		$paymentId = $this->paymentRepository->create([
			'order_id' => $orderId,
			'campaign_id' => $campaign['id'],
			'provider' => 'adyen',
			'amount' => $amount->toDecimal(),
			'currency' => $amount->getCurrency(),
			'status' => 'pending',
		]);

		$this->auditLogService->logAudit([
			'user_id' => $campaign['user_id'] ?? null,
			'action_type' => AuditLogActionType::AD_PAYMENT_CREATED,
			'entity_type' => 'ad_payment',
			'entity_id' => $paymentId,
			'success' => 1,
			'payload' => [
				'campaign_id' => $campaign['id'],
				'order_id' => $orderId,
				'provider' => 'adyen',
				'amount' => $amount->toDecimal(),
				'currency' => $amount->getCurrency(),
				'status' => 'pending',
			],
		]);

		// Placeholder produzione: qui va creata la payment session Adyen e restituito l'URL hosted checkout.
		return "/dashboard/ads/payments/{$campaign['id']}/status?payment_id={$paymentId}";
	}

	public function validateWebhook(string $payload, ?string $signature): array
	{
		if ($payload === '') {
			throw new Exception('Payload webhook vuoto.');
		}

		$event = json_decode($payload, true);
		if (!is_array($event)) {
			throw new Exception('Payload webhook non valido.');
		}

		return $event;
	}

	public function markAsPaid(int $paymentId, ?string $providerReference = null): bool
	{
		$payment = $this->paymentRepository->findById($paymentId);
		if (!$payment) {
			throw new Exception('Pagamento non trovato.');
		}

		if (!empty($payment['order_id'])) {
			$this->orderRepository->markAsPaid((int)$payment['order_id']);
		}
		$ok = $this->paymentRepository->markAsPaid($paymentId, $providerReference);
		$this->auditLogService->logAudit([
			'user_id' => null,
			'action_type' => AuditLogActionType::AD_PAYMENT_SUCCESS,
			'entity_type' => 'ad_payment',
			'entity_id' => $paymentId,
			'success' => $ok ? 1 : 0,
			'payload' => [
				'campaign_id' => (int) ($payment['campaign_id'] ?? 0),
				'order_id' => $payment['order_id'] ?? null,
				'provider' => $payment['provider'] ?? null,
				'provider_reference' => $providerReference,
				'amount' => $payment['amount'] ?? null,
				'currency' => $payment['currency'] ?? null,
			],
		]);

		return $ok;
	}

	public function markAsFailed(int $paymentId): bool
	{
		$payment = $this->paymentRepository->findById($paymentId);
		if ($payment && !empty($payment['order_id'])) {
			$this->orderRepository->markAsFailed((int)$payment['order_id']);
		}
		$ok = $this->paymentRepository->markAsFailed($paymentId);
		$this->auditLogService->logAudit([
			'user_id' => null,
			'action_type' => AuditLogActionType::AD_PAYMENT_FAILED,
			'entity_type' => 'ad_payment',
			'entity_id' => $paymentId,
			'success' => $ok ? 1 : 0,
			'payload' => [
				'campaign_id' => (int) ($payment['campaign_id'] ?? 0),
				'order_id' => $payment['order_id'] ?? null,
				'provider' => $payment['provider'] ?? null,
				'amount' => $payment['amount'] ?? null,
				'currency' => $payment['currency'] ?? null,
			],
		]);

		return $ok;
	}

	public function refund(int $paymentId): bool
	{
		$payment = $this->paymentRepository->findById($paymentId);
		$ok = $this->paymentRepository->refund($paymentId);
		$this->auditLogService->logAudit([
			'user_id' => null,
			'action_type' => AuditLogActionType::AD_PAYMENT_REFUNDED,
			'entity_type' => 'ad_payment',
			'entity_id' => $paymentId,
			'success' => $ok ? 1 : 0,
			'payload' => [
				'campaign_id' => (int) ($payment['campaign_id'] ?? 0),
				'order_id' => $payment['order_id'] ?? null,
				'provider' => $payment['provider'] ?? null,
				'amount' => $payment['amount'] ?? null,
				'currency' => $payment['currency'] ?? null,
			],
		]);

		return $ok;
	}

	public function findById(int $id): ?array
	{
		return $this->paymentRepository->findById($id);
	}

	public function getByCampaign(int $campaignId): ?array
	{
		return $this->paymentRepository->findByCampaign($campaignId);
	}
}
