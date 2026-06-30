<?php

namespace App\Services;

use App\Repositories\AdOrderRepository;
use App\Repositories\AdPaymentRepository;
use Exception;

class AdPaymentService
{
	private AdPaymentRepository $paymentRepository;
	private AdOrderRepository $orderRepository;

	public function __construct()
	{
		$this->paymentRepository = new AdPaymentRepository();
		$this->orderRepository = new AdOrderRepository();
	}

	public function createCheckoutSession(array $campaign): string
	{
		if (empty($campaign['id'])) {
			throw new Exception('Campagna non valida.');
		}

		if ((float)$campaign['price'] <= 0) {
			throw new Exception('Prezzo non valido.');
		}

		$orderId = $campaign['order_id'] ?? null;
		$paymentId = $this->paymentRepository->create([
			'order_id' => $orderId,
			'campaign_id' => $campaign['id'],
			'provider' => 'adyen',
			'amount' => $campaign['price'],
			'currency' => $campaign['currency'] ?? 'EUR',
			'status' => 'pending',
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

		return $this->paymentRepository->markAsPaid($paymentId, $providerReference);
	}

	public function markAsFailed(int $paymentId): bool
	{
		$payment = $this->paymentRepository->findById($paymentId);
		if ($payment && !empty($payment['order_id'])) {
			$this->orderRepository->markAsFailed((int)$payment['order_id']);
		}

		return $this->paymentRepository->markAsFailed($paymentId);
	}

	public function refund(int $paymentId): bool
	{
		return $this->paymentRepository->refund($paymentId);
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
