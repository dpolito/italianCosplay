<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ApiClientRepository;
use App\Support\AuditLogActionType;
use InvalidArgumentException;

final class ApiClientService
{
	private array $config;

	public function __construct(
		private ?ApiClientRepository $clients = null,
		private ?ApiKeyService $keys = null,
		private ?AuditLogService $audit = null
	) {
		$this->clients = $clients ?? new ApiClientRepository();
		$this->keys = $keys ?? new ApiKeyService();
		$this->audit = $audit ?? new AuditLogService();
		$this->config = require APP_ROOT . '/app/config/api_management.php';
	}

	public function scopes(): array
	{
		return $this->config['scopes'];
	}

	public function create(array $input, int $adminId): array
	{
		$data = $this->validate($input);
		$key = $this->keys->generate($data['environment']);
		$id = $this->clients->create($data + [
			'key_prefix' => $key['prefix'],
			'key_hash' => $key['hash'],
		]);
		$this->clients->replaceScopes($id, $data['scopes']);
		$this->audit->logAudit([
			'user_id' => $adminId,
			'action_type' => AuditLogActionType::API_CLIENT_CREATED,
			'entity_type' => 'api_client',
			'entity_id' => $id,
			'payload' => ['name' => $data['name'], 'environment' => $data['environment'], 'scopes' => $data['scopes']],
		]);

		return ['id' => $id, 'plain_key' => $key['plain_key']];
	}

	public function update(int $id, array $input, int $adminId): void
	{
		$data = $this->validate($input);
		$this->clients->update($id, $data);
		$this->clients->replaceScopes($id, $data['scopes']);
		$this->audit->logAudit([
			'user_id' => $adminId,
			'action_type' => AuditLogActionType::API_CLIENT_UPDATED,
			'entity_type' => 'api_client',
			'entity_id' => $id,
			'payload' => ['name' => $data['name'], 'environment' => $data['environment'], 'scopes' => $data['scopes']],
		]);
	}

	public function rotate(int $id, int $adminId): string
	{
		$client = $this->clients->find($id);
		if (!$client) {
			throw new InvalidArgumentException('Client API non trovato.');
		}
		$key = $this->keys->generate((string) $client['environment']);
		$this->clients->rotateKey($id, $key['prefix'], $key['hash']);
		$this->audit->logAudit([
			'user_id' => $adminId,
			'action_type' => AuditLogActionType::API_CLIENT_KEY_ROTATED,
			'entity_type' => 'api_client',
			'entity_id' => $id,
			'payload' => ['key_prefix' => $key['prefix']],
		]);

		return $key['plain_key'];
	}

	public function setStatus(int $id, string $status, int $adminId): void
	{
		if (!in_array($status, ['active', 'disabled', 'revoked'], true)) {
			throw new InvalidArgumentException('Stato API client non valido.');
		}
		$this->clients->updateStatus($id, $status);
		$this->audit->logAudit([
			'user_id' => $adminId,
			'action_type' => AuditLogActionType::API_CLIENT_STATUS_UPDATED,
			'entity_type' => 'api_client',
			'entity_id' => $id,
			'payload' => ['status' => $status],
		]);
	}

	private function validate(array $input): array
	{
		$name = trim((string) ($input['name'] ?? ''));
		if ($name === '' || mb_strlen($name) > 120) {
			throw new InvalidArgumentException('Nome client non valido.');
		}
		$environment = (string) ($input['environment'] ?? 'test');
		if (!in_array($environment, $this->config['environments'], true)) {
			throw new InvalidArgumentException('Environment non valido.');
		}
		$minute = max(1, min((int) ($input['requests_per_minute'] ?? 60), 600));
		$day = max(1, min((int) ($input['requests_per_day'] ?? 5000), 100000));
		$expiresAt = trim((string) ($input['expires_at'] ?? ''));
		$selectedScopes = array_values(array_intersect((array) ($input['scopes'] ?? []), array_keys($this->config['scopes'])));

		return [
			'name' => $name,
			'description' => trim((string) ($input['description'] ?? '')) ?: null,
			'environment' => $environment,
			'requests_per_minute' => $minute,
			'requests_per_day' => $day,
			'expires_at' => $expiresAt !== '' ? $expiresAt . ' 23:59:59' : null,
			'admin_notes' => trim((string) ($input['admin_notes'] ?? '')) ?: null,
			'scopes' => $selectedScopes,
		];
	}
}
