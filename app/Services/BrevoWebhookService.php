<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EmailDeliveryEventRepository;
use DateTimeImmutable;
use RuntimeException;

class BrevoWebhookService
{
	private EmailDeliveryEventRepository $repository;

	public function __construct()
	{
		$this->repository = new EmailDeliveryEventRepository();
	}

	public function record(array $payload): void
	{
		$messageId = trim((string) ($payload['message-id'] ?? $payload['messageId'] ?? ''));
		$eventName = trim((string) ($payload['event'] ?? ''));
		$email = trim((string) ($payload['email'] ?? ''));

		if ($messageId === '' || $eventName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			throw new RuntimeException('Invalid Brevo webhook payload.');
		}

		$tags = $this->normalizeTags($payload['tags'] ?? $payload['tag'] ?? []);

		$this->repository->upsert([
			'provider' => 'brevo',
			'message_id' => mb_substr($messageId, 0, 255),
			'webhook_id' => isset($payload['id']) ? (int) $payload['id'] : null,
			'event_name' => mb_substr(strtolower($eventName), 0, 64),
			'email' => mb_substr(strtolower($email), 0, 255),
			'subject' => isset($payload['subject']) ? mb_substr((string) $payload['subject'], 0, 255) : null,
			'tag' => $tags[0] ?? null,
			'tags_json' => json_encode($tags, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
			'template_id' => isset($payload['template_id']) ? (int) $payload['template_id'] : (isset($payload['templateId']) ? (int) $payload['templateId'] : null),
			'reason' => isset($payload['reason']) ? mb_substr((string) $payload['reason'], 0, 1000) : null,
			'sending_ip' => isset($payload['sending_ip']) ? mb_substr((string) $payload['sending_ip'], 0, 45) : null,
			'ts_event' => isset($payload['ts_event']) ? (int) $payload['ts_event'] : null,
			'ts_epoch' => isset($payload['ts_epoch']) ? (int) $payload['ts_epoch'] : null,
			'event_date' => $this->normalizeDate($payload['date'] ?? null),
			'raw_payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		]);
	}

	private function normalizeTags(mixed $tags): array
	{
		if (is_string($tags)) {
			$decoded = json_decode($tags, true);
			$tags = is_array($decoded) ? $decoded : [$tags];
		}

		if (!is_array($tags)) {
			return [];
		}

		return array_values(array_unique(array_filter(array_map(static function ($tag): string {
			$tag = strtolower(trim((string) $tag));

			return preg_replace('/[^a-z0-9_-]/', '', $tag) ?? '';
		}, $tags))));
	}

	private function normalizeDate(mixed $value): ?string
	{
		if (!is_string($value) || trim($value) === '') {
			return null;
		}

		try {
			return (new DateTimeImmutable($value))->format('Y-m-d H:i:s');
		} catch (\Throwable) {
			return null;
		}
	}
}
