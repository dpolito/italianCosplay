<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AuditLogService;
use App\Services\TelegramChannelMessageService;
use App\Support\AuditLogActionType;
use RuntimeException;
use Throwable;

class AdminTelegramController extends Controller
{
	private AuditLogService $auditLogService;
	private TelegramChannelMessageService $telegramMessageService;

	public function __construct()
	{
		$this->auditLogService = new AuditLogService();
		$this->telegramMessageService = new TelegramChannelMessageService();

		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
	}

	public function index(): void
	{
		$this->view('admin/telegram/index', [
			'csrf_token' => $_SESSION['csrf_token'],
			'isConfigured' => $this->telegramMessageService->isConfigured(),
			'messages' => $this->telegramMessageService->getAdminMessages(),
		], 'admin');
	}

	public function create(): void
	{
		$this->view('admin/telegram/create', [
			'csrf_token' => $_SESSION['csrf_token'],
			'isConfigured' => $this->telegramMessageService->isConfigured(),
			'maxMessageLength' => TelegramChannelMessageService::MAX_MESSAGE_LENGTH,
			'defaultMessage' => $this->defaultMessage(),
		], 'admin');
	}

	public function send(): void
	{
		if (!$this->isValidCsrf()) {
			$this->redirectWithError('Token CSRF non valido.');
		}

		$adminId = (int) ($_SESSION['user_id'] ?? 0);
		$message = trim((string) ($_POST['message'] ?? ''));
		$parseMode = (string) ($_POST['parse_mode'] ?? '');

		try {
			$result = $this->telegramMessageService->sendNow($_POST);

			if (($result['success'] ?? false) !== true) {
				throw new RuntimeException((string) ($result['error'] ?? 'Invio Telegram non riuscito.'));
			}

			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::TELEGRAM_CHANNEL_MESSAGE_SENT,
				'entity_type' => 'telegram_channel_message',
				'payload' => [
					'message_length' => mb_strlen($message),
					'parse_mode' => $this->telegramMessageService->normalizeParseMode($parseMode) ?: 'plain',
					'disable_web_page_preview' => isset($_POST['disable_web_page_preview']),
					'disable_notification' => isset($_POST['disable_notification']),
					'telegram_message_id' => $result['response']['result']['message_id'] ?? null,
				],
			]);

			Session::setFlash('success', 'Messaggio inviato sul canale Telegram.');
		} catch (Throwable $exception) {
			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::TELEGRAM_CHANNEL_MESSAGE_SENT,
				'entity_type' => 'telegram_channel_message',
				'success' => 0,
				'error_message' => $exception->getMessage(),
				'payload' => [
					'message_length' => mb_strlen($message),
					'parse_mode' => $this->telegramMessageService->normalizeParseMode($parseMode) ?: 'plain',
				],
			]);

			Session::setFlash('error', $exception->getMessage());
		}

		header('Location: /admin/telegram/create');
		exit();
	}

	public function schedule(): void
	{
		if (!$this->isValidCsrf()) {
			$this->redirectWithError('Token CSRF non valido.');
		}

		$adminId = (int) ($_SESSION['user_id'] ?? 0);
		$message = trim((string) ($_POST['message'] ?? ''));
		$parseMode = (string) ($_POST['parse_mode'] ?? '');

		try {
			$messageId = $this->telegramMessageService->schedule($_POST, $adminId);
			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::TELEGRAM_CHANNEL_MESSAGE_SCHEDULED,
				'entity_type' => 'telegram_scheduled_message',
				'entity_id' => $messageId,
				'payload' => [
					'message_length' => mb_strlen($message),
					'parse_mode' => $this->telegramMessageService->normalizeParseMode($parseMode) ?: 'plain',
					'scheduled_at' => (string) ($_POST['scheduled_at'] ?? ''),
				],
			]);
			Session::setFlash('success', 'Messaggio programmato.');
		} catch (Throwable $exception) {
			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::TELEGRAM_CHANNEL_MESSAGE_SCHEDULED,
				'entity_type' => 'telegram_scheduled_message',
				'success' => 0,
				'error_message' => $exception->getMessage(),
				'payload' => [
					'message_length' => mb_strlen($message),
					'parse_mode' => $this->telegramMessageService->normalizeParseMode($parseMode) ?: 'plain',
				],
			]);
			Session::setFlash('error', $exception->getMessage());
		}

		header('Location: /admin/telegram');
		exit();
	}

	public function cancel(int $id): void
	{
		if (!$this->isValidCsrf()) {
			$this->redirectWithError('Token CSRF non valido.');
		}

		$cancelled = $this->telegramMessageService->cancel($id);
		$this->auditLogService->logAudit([
			'user_id' => $_SESSION['user_id'] ?? null,
			'action_type' => AuditLogActionType::TELEGRAM_CHANNEL_MESSAGE_CANCELLED,
			'entity_type' => 'telegram_scheduled_message',
			'entity_id' => $id,
			'success' => $cancelled ? 1 : 0,
		]);

		Session::setFlash($cancelled ? 'success' : 'error', $cancelled ? 'Messaggio programmato annullato.' : 'Impossibile annullare il messaggio.');
		header('Location: /admin/telegram');
		exit();
	}

	private function isValidCsrf(): bool
	{
		return !empty($_POST['csrf_token'])
			&& !empty($_SESSION['csrf_token'])
			&& hash_equals((string) $_SESSION['csrf_token'], (string) $_POST['csrf_token']);
	}

	private function redirectWithError(string $message): void
	{
		Session::setFlash('error', $message);
		header('Location: /admin/telegram/create');
		exit();
	}

	private function defaultMessage(): string
	{
		return "🎭 Aggiornamento ItalianCosplay.it\n\nTesto del messaggio...\n\nhttps://www.italiancosplay.it/\n\n🎭 ItalianCosplay.it";
	}
}
