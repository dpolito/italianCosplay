<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuditLogService;
use App\Services\BrevoWebhookService;
use App\Support\AuditLogActionType;
use RuntimeException;
use Throwable;

class BrevoWebhookController extends Controller
{
	private BrevoWebhookService $webhookService;
	private AuditLogService $auditLogService;
	private string $webhookSecret;

	public function __construct()
	{
		$config = require APP_ROOT . '/app/config/brevo.php';
		$this->webhookSecret = trim((string) ($config['webhook_secret'] ?? ''));
		$this->webhookService = new BrevoWebhookService();
		$this->auditLogService = new AuditLogService();
	}

	public function receive(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		try {
			$this->assertAuthorized();
			$this->webhookService->record($this->readPayload());

			http_response_code(204);
		} catch (Throwable $exception) {
			$this->auditLogService->logAudit([
				'user_id' => null,
				'action_type' => AuditLogActionType::BREVO_WEBHOOK_FAILED,
				'entity_type' => 'email_delivery_event',
				'entity_id' => null,
				'success' => 0,
				'error_message' => mb_substr($exception->getMessage(), 0, 250),
			]);

			http_response_code($exception instanceof RuntimeException ? 400 : 500);
			echo json_encode(['success' => false], JSON_UNESCAPED_SLASHES);
		}
	}

	private function assertAuthorized(): void
	{
		if ($this->webhookSecret === '') {
			throw new RuntimeException('Brevo webhook secret is not configured.');
		}

		$providedSecret = $_SERVER['HTTP_X_BREVO_WEBHOOK_SECRET'] ?? ($_GET['secret'] ?? '');
		if (!is_string($providedSecret) || !hash_equals($this->webhookSecret, $providedSecret)) {
			throw new RuntimeException('Invalid Brevo webhook secret.');
		}
	}

	private function readPayload(): array
	{
		$body = file_get_contents('php://input');
		if ($body === false || trim($body) === '') {
			throw new RuntimeException('Empty Brevo webhook payload.');
		}

		$payload = json_decode($body, true);
		if (!is_array($payload)) {
			throw new RuntimeException('Invalid Brevo webhook JSON.');
		}

		return $payload;
	}
}
