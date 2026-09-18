<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EmailDeliveryEventRepository;

class EmailDeliveryEventService
{
	private EmailDeliveryEventRepository $repository;

	public function __construct()
	{
		$this->repository = new EmailDeliveryEventRepository();
	}

	public function getAdminOverview(array $input): array
	{
		$filters = [
			'tag' => $this->cleanFilter($input['tag'] ?? ''),
			'event_name' => $this->cleanFilter($input['event_name'] ?? ''),
			'email' => mb_substr(trim((string) ($input['email'] ?? '')), 0, 255),
			'from' => $this->cleanDate($input['from'] ?? ''),
			'to' => $this->cleanDate($input['to'] ?? ''),
		];
		$filters = array_filter($filters, static fn (?string $value): bool => $value !== null && $value !== '');

		return $this->repository->getOverview($filters, 150);
	}

	public function getAdminList(array $query): array
	{
		$filtersInput = is_array($query['filters'] ?? null) ? $query['filters'] : [];
		$filters = [
			'tag' => $this->cleanFilter($filtersInput['tag'] ?? ''),
			'event_name' => $this->cleanFilter($filtersInput['event_name'] ?? ''),
			'email' => mb_substr(trim((string) ($filtersInput['email'] ?? '')), 0, 255),
			'from' => $this->cleanDate($filtersInput['from'] ?? ''),
			'to' => $this->cleanDate($filtersInput['to'] ?? ''),
		];
		$filters = array_filter($filters, static fn (?string $value): bool => $value !== null && $value !== '');

		return $this->repository->findForAdminList(
			$filters,
			mb_substr(trim((string) ($query['search'] ?? '')), 0, 255),
			$this->cleanSort((string) ($query['sort'] ?? 'event_date')),
			strtolower((string) ($query['direction'] ?? 'desc')),
			(int) ($query['page'] ?? 1),
			(int) ($query['perPage'] ?? 25)
		);
	}

	public function getDetail(int $id): ?array
	{
		$detail = $this->repository->findDetail($id);
		if ($detail === null) {
			return null;
		}

		$detail['tags'] = json_decode((string) ($detail['tags_json'] ?? '[]'), true) ?: [];
		$detail['raw_payload'] = json_decode((string) ($detail['raw_payload_json'] ?? '{}'), true) ?: [];

		return $detail;
	}

	private function cleanFilter(mixed $value): string
	{
		$value = strtolower(trim((string) $value));

		return preg_replace('/[^a-z0-9_-]/', '', $value) ?? '';
	}

	private function cleanDate(mixed $value): string
	{
		$value = trim((string) $value);
		if ($value === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
			return '';
		}

		return $value;
	}

	private function cleanSort(string $value): string
	{
		$value = trim($value);

		return preg_match('/^[a-z_]+$/', $value) === 1 ? $value : 'event_date';
	}
}
