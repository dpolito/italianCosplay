<?php

namespace App\Models;

class AdBanner
{
	public ?int $id = null;

	public ?int $user_id = null;

	public string $title;
	public string $image_path;
	public string $target_url;

	public ?string $alt_text = null;

	public string $type = 'sponsor';
	// internal | sponsor | test

	public int $is_active = 1;

	public ?string $created_at = null;
	public ?string $updated_at = null;

	/**
	 * Da array DB → Model
	 */
	public static function fromArray(array $data): self
	{
		$self = new self();

		$self->id          = isset($data['id']) ? (int)$data['id'] : null;
		$self->user_id     = isset($data['user_id']) ? (int)$data['user_id'] : null;

		$self->title       = $data['title'];
		$self->image_path  = $data['image_path'];
		$self->target_url  = $data['target_url'];

		$self->alt_text    = $data['alt_text'] ?? null;

		$self->type        = $data['type'] ?? 'sponsor';

		$self->is_active   = (int)($data['is_active'] ?? 1);

		$self->created_at  = $data['created_at'] ?? null;
		$self->updated_at  = $data['updated_at'] ?? null;

		return $self;
	}

	/**
	 * Model → array per DB
	 */
	public function toArray(): array
	{
		return [
			'user_id'     => $this->user_id,
			'title'       => $this->title,
			'image_path'  => $this->image_path,
			'target_url'  => $this->target_url,
			'alt_text'    => $this->alt_text,
			'type'        => $this->type,
			'is_active'   => $this->is_active
		];
	}

	/**
	 * Utility: banner attivo
	 */
	public function isActive(): bool
	{
		return (bool)$this->is_active;
	}

	/**
	 * Utility: banner interno o sponsor
	 */
	public function isInternal(): bool
	{
		return $this->type === 'internal';
	}

	/**
	 * Utility: path completo immagine (frontend)
	 */
	public function getImageUrl(): string
	{
		return $this->image_path;
	}

	/**
	 * Utility: safe redirect URL (tracking click futuro)
	 */
	public function getTargetUrl(): string
	{
		return $this->target_url;
	}
}
