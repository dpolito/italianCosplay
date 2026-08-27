<?php

namespace App\Services;

use App\Repositories\AdBannerRepository;
use App\Repositories\AdCampaignRepository;
use App\Repositories\AdOrderRepository;
use App\Repositories\AdPositionRepository;
use App\Repositories\AdReservationRepository;
use App\Core\Mailer;
use App\Models\User;
use DateTime;
use Exception;
use App\ValueObjects\Money;
use function var_dump;

class AdCampaignService
{
	private AdCampaignRepository $campaignRepository;
	private AdPositionRepository $positionRepository;
	private AdBannerRepository $bannerRepository;
	private AdReservationRepository $reservationRepository;
	private AdOrderRepository $orderRepository;
	private AdPricingService $pricingService;
	private AdAvailabilityService $availabilityService;
	private User $userModel;

	public function __construct()
	{
		$this->campaignRepository = new AdCampaignRepository();
		$this->positionRepository = new AdPositionRepository();
		$this->bannerRepository = new AdBannerRepository();
		$this->reservationRepository = new AdReservationRepository();
		$this->orderRepository = new AdOrderRepository();
		$this->pricingService = new AdPricingService();
		$this->availabilityService = new AdAvailabilityService();
		$this->userModel = new User();
	}

	public function findById(int $id): ?array
	{
		return $this->campaignRepository->findById($id);
	}

	public function getByUser(int $userId): array
	{
		return $this->campaignRepository->findByUser($userId);
	}

	public function getAllForAdmin(): array
	{
		return $this->campaignRepository->findAllForAdmin();
	}

	public function getAvailablePositions(): array
	{
		$positions = $this->positionRepository->getAllActive();

		foreach ($positions as &$position) {
			$position['prices'] = $this->positionRepository->getPrices((int)$position['id']);
		}

		return $positions;
	}

	public function createFromCheckout(array $data): array
	{
		$this->validateCheckoutData($data);

		$durationDays = (int)($data['duration_days'] ?? 0);
		$startDate = $data['start_date'];
		$endDate = $durationDays > 0
			? $this->pricingService->buildEndDate($startDate, $durationDays)
			: $data['end_date'];

		$today = new DateTime('today');
		if (new DateTime($startDate) < $today) {
			throw new Exception('La data di inizio non può essere nel passato.');
		}

		$positionId = (int)$data['position_id'];
		$this->availabilityService->assertAvailable($positionId, $startDate, $endDate);
		$price = $this->pricingService->calculate($positionId, $startDate, $endDate);

		$bannerId = (int)($data['banner_id'] ?? 0);

		if ($bannerId > 0) {
			$banner = $this->bannerRepository->findById($bannerId);
			if (!$banner || (int)$banner['user_id'] !== (int)$data['user_id']) {
				throw new Exception('Banner selezionato non valido.');
			}

			foreach ($this->campaignRepository->findByBannerId($bannerId) as $campaign) {
				$status = (string)($campaign['status'] ?? '');

				if ($status === 'active' || str_starts_with($status, 'pending')) {
					throw new Exception('Questo banner è già usato da una campagna attiva o in attesa di approvazione.');
				}
			}
		} else {
			$bannerId = $this->bannerRepository->create([
				'user_id' => $data['user_id'],
				'title' => $data['title'],
				'description' => $data['description'] ?? null,
				'image_path' => $data['image_path'] ?? null,
				'target_url' => $data['target_url'],
				'alt_text' => $data['alt_text'] ?? $data['title'],
				'sponsor_name' => $data['sponsor_name'] ?? null,
				'facebook_url' => $data['facebook_url'] ?? null,
				'instagram_url' => $data['instagram_url'] ?? null,
				'tiktok_url' => $data['tiktok_url'] ?? null,
				'creative_type' => $data['creative_type'] ?? 'sponsor_card',
				'type' => 'sponsor',
				'status' => 'pending_review',
			]);
		}

		$reservationId = $this->reservationRepository->create([
			'user_id' => $data['user_id'],
			'position_id' => $positionId,
			'start_date' => $startDate,
			'end_date' => $endDate,
			'reserved_slots' => 1,
			'price' => $price->toDecimal(),
			'currency' => $price->getCurrency(),
			'ttl_minutes' => 20,
		]);

		$campaignId = $this->campaignRepository->create([
			'user_id' => $data['user_id'],
			'position_id' => $positionId,
			'banner_id' => $bannerId,
			'start_date' => $startDate,
			'end_date' => $endDate,
			'price' => $price->toDecimal(),
			'currency' => $price->getCurrency(),
			'status' => 'pending_payment',
			'approval_status' => 'pending',
			'notes' => $data['notes'] ?? null,
		]);

		$this->reservationRepository->convert($reservationId, $campaignId);

		$orderId = $this->orderRepository->create([
			'user_id' => $data['user_id'],
			'reservation_id' => $reservationId,
			'campaign_id' => $campaignId,
			'subtotal' => $price->toDecimal(),
			'vat' => 0,
			'total' => $price->toDecimal(),
			'currency' => $price->getCurrency(),
			'status' => 'pending_payment',
		]);

		return [
			'campaign_id' => $campaignId,
			'reservation_id' => $reservationId,
			'order_id' => $orderId,
		];
	}

	public function markAsPaid(int $campaignId): bool
	{
		return $this->campaignRepository->updateStatus($campaignId, 'pending_approval');
	}

	public function markAsFailed(int $campaignId): bool
	{
		return $this->campaignRepository->updateStatus($campaignId, 'payment_failed');
	}

	public function approve(int $campaignId, ?string $notes = null): bool
	{
		$campaign = $this->campaignRepository->findById($campaignId);
		if (!$campaign) {
			throw new Exception('Campagna non trovata.');
		}

		$status = (new DateTime($campaign['start_date']) <= new DateTime('today')) ? 'active' : 'scheduled';

		return $this->campaignRepository->updateApproval($campaignId, 'approved', $status, $notes);
	}

	public function reject(int $campaignId, ?string $notes = null): bool
	{
		return $this->campaignRepository->updateApproval($campaignId, 'rejected', 'cancelled', $notes);
	}

	public function requestChanges(int $campaignId, ?string $notes = null): bool
	{
		$campaign = $this->campaignRepository->findById($campaignId);
		if (!$campaign) {
			throw new Exception('Campagna non trovata.');
		}

		$updated = $this->campaignRepository->updateApproval($campaignId, 'changes_requested', 'changes_requested', $notes);

		$user = $this->userModel->find((int)$campaign['user_id']);
		if ($user && !empty($user['email'])) {
			$mailer = new Mailer();
			$subject = 'Richiesta modifiche campagna Advertising';
			$body = '<p>Abbiamo richiesto alcune modifiche alla tua campagna pubblicitaria.</p>';
			$body .= '<p><strong>Campagna:</strong> ' . htmlspecialchars((string)($campaign['banner_title'] ?? 'Campagna'), ENT_QUOTES, 'UTF-8') . '</p>';
			if (!empty($notes)) {
				$body .= '<p><strong>Note admin:</strong><br>' . nl2br(htmlspecialchars($notes, ENT_QUOTES, 'UTF-8')) . '</p>';
			}
			$body .= '<p>Accedi al dashboard per aggiornare il banner associato.</p>';

			$mailer->send(
				$user['email'],
				$user['username'] ?? $user['email'],
				$subject,
				$body
			);
		}

		return $updated;
	}

	public function cancel(int $campaignId): bool
	{
		return $this->campaignRepository->updateStatus($campaignId, 'cancelled');
	}

	public function refund(int $campaignId): bool
	{
		return $this->campaignRepository->updateStatus($campaignId, 'refunded');
	}

	private function validateCheckoutData(array $data): void
	{
		if (empty($data['user_id']) || empty($data['position_id']) || empty($data['start_date'])) {
			throw new Exception('Dati campagna incompleti.');
		}

		$targetType = $data['target_type'] ?? 'national';
		if (!in_array($targetType, ['national', 'region', 'province'], true)) {
			throw new Exception('Tipo di targeting non valido.');
		}

		if ($targetType !== 'national' && empty($data['target_value'])) {
			throw new Exception('Seleziona un valore territoriale per il targeting.');
		}

		if (empty($data['duration_days']) && empty($data['end_date'])) {
			throw new Exception('Seleziona una durata o una data di fine.');
		}

		if (!empty($data['banner_id'])) {
			return;
		}

		if (empty($data['title'])) {
			throw new Exception('Inserisci il titolo dello sponsor o del banner.');
		}

		if (empty($data['target_url']) || !filter_var($data['target_url'], FILTER_VALIDATE_URL)) {
			throw new Exception('Inserisci un URL valido.');
		}
	}
}
