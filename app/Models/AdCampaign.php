<?php

namespace App\Models;

class AdCampaign
{
	public ?int $id = null;

	public int $user_id;
	public int $position_id;
	public int $banner_id;

	public string $start_date;
	public string $end_date;

	public float $price = 0.0;

	public string $status = 'draft';
	public string $approval_status = 'pending';

	public ?string $notes = null;

	public ?string $created_at = null;
	public ?string $updated_at = null;

	/**
	 * Costruzione semplice da array DB
	 */
	public static function fromArray(array $data): self
	{
		$self = new self();

		$self->id              = isset($data['id']) ? (int)$data['id'] : null;
		$self->user_id        = (int)$data['user_id'];
		$self->position_id    = (int)$data['position_id'];
		$self->banner_id      = (int)$data['banner_id'];

		$self->start_date     = $data['start_date'];
		$self->end_date       = $data['end_date'];

		$self->price          = (float)$data['price'];

		$self->status         = $data['status'] ?? 'draft';
		$self->approval_status = $data['approval_status'] ?? 'pending';

		$self->notes          = $data['notes'] ?? null;

		$self->created_at     = $data['created_at'] ?? null;
		$self->updated_at     = $data['updated_at'] ?? null;

		return $self;
	}

	/**
	 * Converte il model in array per insert/update DB
	 */
	public function toArray(): array
	{
		return [
			'user_id'         => $this->user_id,
			'position_id'     => $this->position_id,
			'banner_id'       => $this->banner_id,
			'start_date'      => $this->start_date,
			'end_date'        => $this->end_date,
			'price'           => $this->price,
			'status'          => $this->status,
			'approval_status' => $this->approval_status,
			'notes'           => $this->notes,
		];
	}

	/**
	 * Utility: verifica se campagna è attiva per una data
	 */
	public function isActive(\DateTime $date = null): bool
	{
		$date = $date ?? new \DateTime();

		return $this->status === 'active'
			&& new \DateTime($this->start_date) <= $date
			&& new \DateTime($this->end_date) >= $date;
	}

	/**
	 * Utility: durata campagna in giorni
	 */
	public function durationDays(): int
	{
		$start = new \DateTime($this->start_date);
		$end   = new \DateTime($this->end_date);

		return (int)$start->diff($end)->days + 1;
	}
}
