<?php

namespace App\Services;

use App\Repositories\AdPositionRepository;
use Exception;

class AdPositionService
{
	private AdPositionRepository $positionRepository;

	public function __construct()
	{
		$this->positionRepository = new AdPositionRepository();
	}

	public function getAll(): array
	{
		return $this->positionRepository->getAll();
	}

	public function getAllActive(): array
	{
		return $this->positionRepository->getAllActive();
	}

	public function findById(int $id): ?array
	{
		return $this->positionRepository->findById($id);
	}

	public function getPrices(int $positionId): array
	{
		return $this->positionRepository->getPrices($positionId);
	}

	public function create(array $data): int
	{
		$this->validate($data);
		$id = $this->positionRepository->create($data);
		$this->positionRepository->syncPrices($id, $data['prices'] ?? []);

		return $id;
	}

	public function update(int $id, array $data): bool
	{
		$this->validate($data);
		$ok = $this->positionRepository->update($id, $data);
		$this->positionRepository->syncPrices($id, $data['prices'] ?? []);

		return $ok;
	}

	public function delete(int $id): bool
	{
		return $this->positionRepository->setActive($id, false);
	}

	private function validate(array $data): void
	{
		if (empty($data['name']) || empty($data['code']) || empty($data['page'])) {
			throw new Exception('Nome, codice e pagina sono obbligatori.');
		}

		if (!preg_match('/^[a-z0-9_\\-]+$/', $data['code'])) {
			throw new Exception('Il codice può contenere solo lettere minuscole, numeri, trattini e underscore.');
		}

		if ((int)($data['max_slots'] ?? 1) < 1) {
			throw new Exception('Il numero massimo di slot deve essere almeno 1.');
		}
	}
}
