<?php

namespace App\Models;

class AdClick
{
	public ?int $id = null;

	public int $campaign_id;
	public int $banner_id;
	public int $position_id;

	public ?string $page = null;

	public ?string $target_url = null;

	public ?string $user_hash = null;

	public ?string $ip_address = null;

	public ?string $user_agent = null;

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

		$self->target_url   = $data['target_url'] ?? null;

		$self->user_hash    = $data['user_hash'] ?? null;

		$self->ip_address   = $data['ip_address'] ?? null;
		$self->user_agent   = $data['user_agent'] ?? null;

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
			'target_url'  => $this->target_url,
			'user_hash'   => $this->user_hash,
			'ip_address'  => $this->ip_address,
			'user_agent'  => $this->user_agent
		];
	}

	/**
	 * Utility: hash utente anonimo (coerenza con impression)
	 */
	public static function generateUserHash(): string
	{
		return hash('sha256',
			($_SERVER['REMOTE_ADDR'] ?? '') .
			($_SERVER['HTTP_USER_AGENT'] ?? '')
		);
	}

	/**
	 * Utility: crea click da request HTTP
	 */
	public static function fromRequest(array $context): self
	{
		$self = new self();

		$self->campaign_id = (int)$context['campaign_id'];
		$self->banner_id   = (int)$context['banner_id'];
		$self->position_id = (int)$context['position_id'];

		$self->page        = $context['page'] ?? null;
		$self->target_url  = $context['target_url'] ?? null;

		$self->user_hash   = self::generateUserHash();

		$self->ip_address  = $_SERVER['REMOTE_ADDR'] ?? null;
		$self->user_agent  = $_SERVER['HTTP_USER_AGENT'] ?? null;

		return $self;
	}

	/**
	 * Utility: validazione base click
	 */
	public function isValid(): bool
	{
		return $this->campaign_id > 0
			&& $this->banner_id > 0
			&& $this->position_id > 0
			&& !empty($this->target_url);
	}
}
