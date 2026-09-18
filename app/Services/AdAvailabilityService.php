<?php

namespace App\Services;

use App\Repositories\AdCampaignRepository;
use App\Repositories\AdPositionRepository;
use App\Repositories\AdReservationRepository;
use Exception;

class AdAvailabilityService
{
	private AdPositionRepository $positionRepository;
	private AdCampaignRepository $campaignRepository;
	private AdReservationRepository $reservationRepository;

	public function __construct()
	{
		$this->positionRepository = new AdPositionRepository();
		$this->campaignRepository = new AdCampaignRepository();
		$this->reservationRepository = new AdReservationRepository();
	}

	public function getAvailability(int $positionId, string $startDate, string $endDate): array
	{
		$this->reservationRepository->expireOld();

		$position = $this->positionRepository->findById($positionId);
		if (!$position || !(int)$position['is_active']) {
			throw new Exception('Posizione pubblicitaria non disponibile.');
		}

		$maxSlots = max(1, (int)$position['max_slots']);
		$campaignSlots = $this->campaignRepository->countBookedSlots($positionId, $startDate, $endDate);
		$reservedSlots = $this->reservationRepository->countActiveSlots($positionId, $startDate, $endDate);
		$occupied = $campaignSlots + $reservedSlots;
		$available = max(0, $maxSlots - $occupied);

		return [
			'position_id' => $positionId,
			'max_slots' => $maxSlots,
			'occupied_slots' => $occupied,
			'available_slots' => $available,
			'is_available' => $available > 0,
		];
	}

	public function assertAvailable(int $positionId, string $startDate, string $endDate): void
	{
		$availability = $this->getAvailability($positionId, $startDate, $endDate);

		if (!$availability['is_available']) {
			throw new Exception('La posizione scelta è esaurita nel periodo selezionato.');
		}
	}
}
