<?php

namespace App\Models;

class AdPayment
{
	public ?int $id = null;

	public int $campaign_id;

	public string $provider;
	// stripe | paypal

	public ?string $provider_reference = null;

	public float $amount;
	public string $currency = 'EUR';

	public string $status = 'pending';
	// pending | paid | failed | refunded

	public ?string $paid_at = null;

	public ?string $created_at = null;

	/**
	 * Da array DB → Model
	 */
	public static function fromArray(array $data): self
	{
		$self = new self();

		$self->id                 = isset($data['id']) ? (int)$data['id'] : null;
		$self->campaign_id       = (int)$data['campaign_id'];

		$self->provider          = $data['provider'];

		$self->provider_reference = $data['provider_reference'] ?? null;

		$self->amount            = (float)$data['amount'];
		$self->currency          = $data['currency'] ?? 'EUR';

		$self->status            = $data['status'] ?? 'pending';

		$self->paid_at           = $data['paid_at'] ?? null;

		$self->created_at        = $data['created_at'] ?? null;

		return $self;
	}

	/**
	 * Model → array per DB
	 */
	public function toArray(): array
	{
		return [
			'campaign_id'        => $this->campaign_id,
			'provider'           => $this->provider,
			'provider_reference' => $this->provider_reference,
			'amount'             => $this->amount,
			'currency'           => $this->currency,
			'status'             => $this->status,
			'paid_at'            => $this->paid_at
		];
	}

	/**
	 * Utility: pagamento completato
	 */
	public function isPaid(): bool
	{
		return $this->status === 'paid';
	}

	/**
	 * Utility: pagamento in attesa
	 */
	public function isPending(): bool
	{
		return $this->status === 'pending';
	}

	/**
	 * Utility: pagamento fallito
	 */
	public function isFailed(): bool
	{
		return $this->status === 'failed';
	}

	/**
	 * Utility: pagamento rimborsato
	 */
	public function isRefunded(): bool
	{
		return $this->status === 'refunded';
	}

	/**
	 * Utility: segna come pagato
	 */
	public function markAsPaid(): void
	{
		$this->status = 'paid';
		$this->paid_at = date('Y-m-d H:i:s');
	}
}
