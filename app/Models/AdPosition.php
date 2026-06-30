<?php

namespace App\Models;

class AdPosition
{
	public ?int $id = null;

	public string $name;
	public string $code;
	public string $page;

	public ?int $width = null;
	public ?int $height = null;

	public int $max_slots = 1;

	public string $rotation_type = 'random';
	// random | weighted | fixed

	public float $base_price = 0.0;
	public string $currency = 'EUR';

	public int $is_active = 1;

	public ?string $created_at = null;
	public ?string $updated_at = null;

	/**
	 * Da array DB → Model
	 */
	public static function fromArray(array $data): self
	{
		$self = new self();

		$self->id            = isset($data['id']) ? (int)$data['id'] : null;
		$self->name          = $data['name'];
		$self->code          = $data['code'];
		$self->page          = $data['page'];

		$self->width         = isset($data['width']) ? (int)$data['width'] : null;
		$self->height        = isset($data['height']) ? (int)$data['height'] : null;

		$self->max_slots     = (int)($data['max_slots'] ?? 1);

		$self->rotation_type = $data['rotation_type'] ?? 'random';

		$self->base_price    = (float)($data['base_price'] ?? 0);
		$self->currency      = $data['currency'] ?? 'EUR';

		$self->is_active     = (int)($data['is_active'] ?? 1);

		$self->created_at    = $data['created_at'] ?? null;
		$self->updated_at    = $data['updated_at'] ?? null;

		return $self;
	}

	/**
	 * Model → array (insert/update)
	 */
	public function toArray(): array
	{
		return [
			'name'          => $this->name,
			'code'          => $this->code,
			'page'          => $this->page,
			'width'         => $this->width,
			'height'        => $this->height,
			'max_slots'     => $this->max_slots,
			'rotation_type' => $this->rotation_type,
			'base_price'    => $this->base_price,
			'currency'      => $this->currency,
			'is_active'     => $this->is_active
		];
	}

	/**
	 * Utility: verifica se la posizione è vendibile
	 */
	public function isActive(): bool
	{
		return (bool)$this->is_active;
	}

	/**
	 * Utility: prezzo giornaliero base
	 */
	public function getDailyPrice(): float
	{
		return $this->base_price;
	}

	/**
	 * Utility: chiave unica posizione (utile per cache/rotation)
	 */
	public function getKey(): string
	{
		return $this->page . ':' . $this->code;
	}

	/**
	 * Utility: check se supporta multi-slot
	 */
	public function supportsMultipleSlots(): bool
	{
		return $this->max_slots > 1;
	}
}
