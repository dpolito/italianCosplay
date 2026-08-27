<?php

namespace App\Services;

use App\Repositories\AdPositionRepository;
use DateTime;
use Exception;
use App\ValueObjects\Money;

class AdPricingService
{
	private AdPositionRepository $positionRepository;

	public function __construct()
	{
		$this->positionRepository = new AdPositionRepository();
	}

	public function calculate(int $positionId, string $startDate, string $endDate): Money
	{
		$position = $this->positionRepository->findById($positionId);
		if (!$position || !(int)$position['is_active']) {
			throw new Exception('Posizione pubblicitaria non disponibile.');
		}

		$days = $this->daysBetween($startDate, $endDate);
		$minDays = (int)($position['min_days'] ?? 1);
		$maxDays = (int)($position['max_days'] ?? 365);

		if ($days < $minDays) {
			throw new Exception("Il periodo minimo per questa posizione è di {$minDays} giorni.");
		}

		if ($days > $maxDays) {
			throw new Exception("Il periodo massimo per questa posizione è di {$maxDays} giorni.");
		}

		$fixedPrice = $this->positionRepository->findPriceForDuration($positionId, $days);
		if ($fixedPrice) {
			return Money::fromDecimal($fixedPrice['price'] ?? 0, $fixedPrice['currency'] ?? 'EUR');
		}

		return Money::fromDecimal($position['base_price'] ?? 0, $position['currency'] ?? 'EUR')->multiply($days);
	}

	public function daysBetween(string $startDate, string $endDate): int
	{
		$start = new DateTime($startDate);
		$end = new DateTime($endDate);

		if ($start > $end) {
			throw new Exception('La data di fine deve essere successiva o uguale alla data di inizio.');
		}

		return (int)$start->diff($end)->days + 1;
	}

	public function buildEndDate(string $startDate, int $durationDays): string
	{
		if ($durationDays < 1) {
			throw new Exception('Durata non valida.');
		}

		$start = new DateTime($startDate);
		$end = clone $start;
		$end->modify('+' . ($durationDays - 1) . ' days');

		return $end->format('Y-m-d');
	}
}
