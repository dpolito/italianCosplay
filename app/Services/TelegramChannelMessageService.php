<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TelegramScheduledMessageRepository;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

class TelegramChannelMessageService
{
	public const MAX_MESSAGE_LENGTH = 4096;

	private TelegramScheduledMessageRepository $repository;
	private AuditLogService $auditLogService;

	public function __construct()
	{
		$this->repository = new TelegramScheduledMessageRepository();
		$this->auditLogService = new AuditLogService();
	}

	public function getAdminMessages(): array
	{
		return $this->repository->findForAdminList();
	}

	public function schedule(array $input, int $adminId): int
	{
		$message = trim((string) ($input['message'] ?? ''));
		$this->validateMessage($message);

		$scheduledAt = $this->normalizeScheduledAt((string) ($input['scheduled_at'] ?? ''));

		return $this->repository->create([
			'message' => $message,
			'parse_mode' => $this->normalizeParseMode((string) ($input['parse_mode'] ?? '')),
			'disable_web_page_preview' => !empty($input['disable_web_page_preview']),
			'disable_notification' => !empty($input['disable_notification']),
			'scheduled_at' => $scheduledAt,
			'created_by' => $adminId,
		]);
	}

	public function sendNow(array $input): array
	{
		$message = trim((string) ($input['message'] ?? ''));
		$this->validateMessage($message);

		return $this->createTelegramService()->sendMessage($message, [
			'parse_mode' => $this->normalizeParseMode((string) ($input['parse_mode'] ?? '')),
			'disable_web_page_preview' => !empty($input['disable_web_page_preview']),
			'disable_notification' => !empty($input['disable_notification']),
		]);
	}

	public function cancel(int $id): bool
	{
		return $this->repository->cancel($id);
	}

	public function processDueMessages(int $limit = 10): array
	{
		$messages = $this->repository->findDueScheduled($limit);
		$result = ['processed' => 0, 'sent' => 0, 'failed' => 0];

		foreach ($messages as $message) {
			$result['processed']++;

			try {
				$sendResult = $this->createTelegramService()->sendMessage((string) $message['message'], [
					'parse_mode' => (string) ($message['parse_mode'] ?? ''),
					'disable_web_page_preview' => (bool) $message['disable_web_page_preview'],
					'disable_notification' => (bool) $message['disable_notification'],
				]);

				if (($sendResult['success'] ?? false) !== true) {
					throw new RuntimeException((string) ($sendResult['error'] ?? 'Invio Telegram non riuscito.'));
				}

				$this->repository->markSent((int) $message['id'], isset($sendResult['response']['result']['message_id']) ? (int) $sendResult['response']['result']['message_id'] : null);
				$this->auditLogService->logAudit([
					'user_id' => $message['created_by'] ?? null,
					'action_type' => \App\Support\AuditLogActionType::TELEGRAM_CHANNEL_MESSAGE_SENT,
					'entity_type' => 'telegram_scheduled_message',
					'entity_id' => (int) $message['id'],
					'payload' => [
						'source' => 'cron',
						'message_length' => mb_strlen((string) $message['message']),
						'telegram_message_id' => $sendResult['response']['result']['message_id'] ?? null,
					],
				]);
				$result['sent']++;
			} catch (Throwable $exception) {
				$this->repository->markFailed((int) $message['id'], $exception->getMessage());
				$this->auditLogService->logAudit([
					'user_id' => $message['created_by'] ?? null,
					'action_type' => \App\Support\AuditLogActionType::TELEGRAM_CHANNEL_MESSAGE_SENT,
					'entity_type' => 'telegram_scheduled_message',
					'entity_id' => (int) $message['id'],
					'success' => 0,
					'error_message' => $exception->getMessage(),
					'payload' => [
						'source' => 'cron',
						'message_length' => mb_strlen((string) $message['message']),
					],
				]);
				$result['failed']++;
			}
		}

		return $result;
	}

	public function isConfigured(): bool
	{
		$botToken = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
		$chatId = defined('TELEGRAM_CHANNEL_CHAT_ID') ? TELEGRAM_CHANNEL_CHAT_ID : '';

		return is_string($botToken) && is_string($chatId) && trim($botToken) !== '' && trim($chatId) !== '';
	}

	public function normalizeParseMode(string $parseMode): string
	{
		return in_array($parseMode, ['HTML', 'MarkdownV2'], true) ? $parseMode : '';
	}

	private function validateMessage(string $message): void
	{
		if ($message === '') {
			throw new RuntimeException('Scrivi un messaggio prima di inviare.');
		}

		if (mb_strlen($message) > self::MAX_MESSAGE_LENGTH) {
			throw new RuntimeException('Il messaggio supera il limite Telegram di ' . self::MAX_MESSAGE_LENGTH . ' caratteri.');
		}
	}

	private function normalizeScheduledAt(string $value): string
	{
		$value = trim($value);
		if ($value === '') {
			throw new RuntimeException('Seleziona giorno e ora di pubblicazione.');
		}

		$date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);
		if (!$date instanceof DateTimeImmutable) {
			throw new RuntimeException('Data di programmazione non valida.');
		}

		if ($date <= new DateTimeImmutable()) {
			throw new RuntimeException('La data di programmazione deve essere futura.');
		}

		return $date->format('Y-m-d H:i:s');
	}

	private function createTelegramService(): TelegramNotificationService
	{
		$botToken = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
		$chatId = defined('TELEGRAM_CHANNEL_CHAT_ID') ? TELEGRAM_CHANNEL_CHAT_ID : '';

		if (!is_string($botToken) || !is_string($chatId) || trim($botToken) === '' || trim($chatId) === '') {
			throw new RuntimeException('Telegram canale non è configurato: verifica TELEGRAM_BOT_TOKEN e TELEGRAM_CHANNEL_CHAT_ID.');
		}

		return new TelegramNotificationService($botToken, $chatId);
	}
}
