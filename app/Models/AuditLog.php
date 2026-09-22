<?php

namespace App\Models;

class AuditLog
{
	public ?int $id = null;
	public ?int $user_id = null;
	public string $action_type;
	public string $entity_type;
	public ?int $entity_id = null;
	public array $payload = [];
	public ?string $ip_address = null;
	public ?string $user_agent = null;
	public ?string $request_uri = null;
	public ?string $http_method = null;
	public int $success = 1;
	public ?string $error_message = null;
	public ?string $created_at = null;

	public static function fromArray(array $data): self
	{
		$self = new self();

		$self->id = isset($data['id']) ? (int) $data['id'] : null;
		$self->user_id = isset($data['user_id']) ? (int) $data['user_id'] : null;
		$self->action_type = (string) ($data['action_type'] ?? '');
		$self->entity_type = (string) ($data['entity_type'] ?? '');
		$self->entity_id = isset($data['entity_id']) ? (int) $data['entity_id'] : null;
		$self->payload = self::decodePayload($data['payload_json'] ?? $data['payload'] ?? []);
		$self->ip_address = $data['ip_address'] ?? null;
		$self->user_agent = $data['user_agent'] ?? null;
		$self->request_uri = $data['request_uri'] ?? null;
		$self->http_method = $data['http_method'] ?? null;
		$self->success = isset($data['success']) ? (int) $data['success'] : 1;
		$self->error_message = $data['error_message'] ?? null;
		$self->created_at = $data['created_at'] ?? null;

		return $self;
	}

	public function toArray(): array
	{
		return [
			'user_id' => $this->user_id,
			'action_type' => $this->action_type,
			'entity_type' => $this->entity_type,
			'entity_id' => $this->entity_id,
			'payload_json' => json_encode($this->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
			'ip_address' => $this->ip_address,
			'user_agent' => $this->user_agent,
			'request_uri' => $this->request_uri,
			'http_method' => $this->http_method,
			'success' => $this->success,
			'error_message' => $this->error_message,
		];
	}

	public function isValid(): bool
	{
		return $this->action_type !== '' && $this->entity_type !== '';
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
