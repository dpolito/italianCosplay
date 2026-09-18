<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PrivacyPolicyRepository;

class PrivacyPolicyService
{
	private PrivacyPolicyRepository $repository;

	public function __construct()
	{
		$this->repository = new PrivacyPolicyRepository();
	}

	public function getCurrentPolicy(): ?array
	{
		return $this->repository->getLatestActiveVersion() ?? $this->repository->getLatestVersion();
	}

	public function getAllVersions(): array
	{
		return $this->repository->getAllVersions();
	}

	public function findById(int $id): ?array
	{
		return $this->repository->findById($id);
	}

	public function createVersion(array $data): int
	{
		return $this->repository->createVersion($data);
	}

	public function updateVersion(int $id, array $data): bool
	{
		return $this->repository->updateVersion($id, $data);
	}

	public function activateVersion(int $id): bool
	{
		return $this->repository->setActiveVersion($id);
	}
}
