<?php

namespace App\Models;

class AdImpression
{
	public ?int $id = null;

	public int $campaign_id;
	public int $banner_id;
	public int $position_id;

	public ?string $page = null;
	public ?string $device_type = null;
	public ?string $country_code = null;

	public ?string $created_at = null;

	/**
	 * Da array DB → Model
	 */
	public static function fromArray(array $data): self
	{
		$self = new self();

		$self->id           = isset($data['id']) ? (int)$data['id'] : null;

		$self->campaign_id  = (int)$data['campaign_id'];
		$self->banner_id    = (int)$data['banner_id'];
		$self->position_id  = (int)$data['position_id'];

		$self->page         = $data['page'] ?? null;
		$self->device_type  = $data['device_type'] ?? null;
		$self->country_code = $data['country_code'] ?? null;

		$self->created_at   = $data['created_at'] ?? null;

		return $self;
	}

	/**
	 * Model → array per DB insert
	 */
	public function toArray(): array
	{
		return [
			'campaign_id' => $this->campaign_id,
			'banner_id'   => $this->banner_id,
			'position_id' => $this->position_id,
			'page'        => $this->page,
			'device_type' => $this->device_type,
			'country_code'=> $this->country_code
		];
	}

	/**
	 * Utility: verifica se è impression valida base (anti spam semplice)
	 */
	public function isValid(): bool
	{
		return $this->campaign_id > 0
			&& $this->banner_id > 0
			&& $this->position_id > 0;
	}
}
