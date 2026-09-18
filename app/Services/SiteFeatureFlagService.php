<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\SiteFeatureFlagRepository;

class SiteFeatureFlagService
{
	private SiteFeatureFlagRepository $repository;

	public function __construct()
	{
		$this->repository = new SiteFeatureFlagRepository();
	}

	public function getAllFlags(): array
	{
		return $this->repository->getAll();
	}

	public function getEnabledMap(): array
	{
		return $this->repository->getEnabledMap();
	}

	public function updateFlags(array $input): bool
	{
		return $this->repository->upsertMany($input);
	}
}
