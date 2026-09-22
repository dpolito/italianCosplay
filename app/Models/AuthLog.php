<?php

namespace App\Models;

class AuthLog
{
	public ?int $id = null;
	public ?int $user_id = null;
	public ?string $identifier = null;
	public string $action_type;
	public int $success = 0;
	public ?string $failure_reason = null;
	public ?string $ip_address = null;
	public ?string $user_agent = null;
	public ?string $request_uri = null;
	public ?string $http_method = null;
	public array $payload = [];
	public ?string $created_at = null;

	public static function fromArray(array $data): self
	{
		$self = new self();

		$self->id = isset($data['id']) ? (int) $data['id'] : null;
		$self->user_id = isset($data['user_id']) ? (int) $data['user_id'] : null;
		$self->identifier = $data['identifier'] ?? null;
		$self->action_type = (string) ($data['action_type'] ?? '');
		$self->success = isset($data['success']) ? (int) $data['success'] : 0;
		$self->failure_reason = $data['failure_reason'] ?? null;
		$self->ip_address = $data['ip_address'] ?? null;
		$self->user_agent = $data['user_agent'] ?? null;
		$self->request_uri = $data['request_uri'] ?? null;
		$self->http_method = $data['http_method'] ?? null;
		$self->payload = self::decodePayload($data['payload_json'] ?? $data['payload'] ?? []);
		$self->created_at = $data['created_at'] ?? null;

		return $self;
	}

	public function toArray(): array
	{
		return [
			'user_id' => $this->user_id,
			'identifier' => $this->identifier,
			'action_type' => $this->action_type,
			'success' => $this->success,
			'failure_reason' => $this->failure_reason,
			'ip_address' => $this->ip_address,
			'user_agent' => $this->user_agent,
			'request_uri' => $this->request_uri,
			'http_method' => $this->http_method,
			'payload_json' => json_encode($this->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		];
	}

	public function isValid(): bool
	{
		return $this->action_type !== '';
	}

	private static function decodePayload(mixed $payload): array
	{
		if (is_array($payload)) {
			return $payload;
		}

		if (is_string($payload) && $payload !== '') {
			$decoded = json_decode($payload, true);
			return is_array($decoded) ? $decoded : [];
		}

		return [];
	}
}
