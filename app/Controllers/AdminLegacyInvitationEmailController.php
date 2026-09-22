<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AuditLogService;
use App\Services\LegacyInvitationEmailService;
use App\Support\AuditLogActionType;
use Throwable;

class AdminLegacyInvitationEmailController extends Controller
{
	private LegacyInvitationEmailService $emailService;
	private AuditLogService $auditLogService;

	public function __construct()
	{
		$this->emailService = new LegacyInvitationEmailService();
		$this->auditLogService = new AuditLogService();
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
	}

	public function index(): void
	{
		$this->view('admin/legacy-invitation-emails/index', [
			'overview' => $this->emailService->getOverview(),
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function data(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$result = $this->emailService->getAdminList($_GET);

		echo json_encode([
			'success' => true,
			'data' => $result['data'],
			'meta' => [
				'total' => $result['total'],
				'page' => $result['page'],
				'perPage' => $result['perPage'],
				'pages' => $result['pages'],
			],
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public function import(): void
	{
		if (!$this->isValidCsrf()) {
			$this->redirectWithError('Token CSRF non valido.');
		}

		$adminId = (int) ($_SESSION['user_id'] ?? 0);
		$rawEmails = (string) ($_POST['emails'] ?? '');
		$sourceLabel = (string) ($_POST['source_label'] ?? '');

		try {
			$result = $this->emailService->importRecipients($rawEmails, $sourceLabel);
			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::LEGACY_INVITATION_EMAILS_IMPORTED,
				'entity_type' => 'legacy_invitation_email_recipient',
				'payload' => $result + ['source_label' => trim($sourceLabel)],
			]);
			Session::setFlash('success', 'Import completato: ' . (int) $result['created'] . ' email aggiunte, ' . (int) $result['duplicates'] . ' duplicate, ' . (int) $result['invalid'] . ' non valide.');
		} catch (Throwable $exception) {
			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::LEGACY_INVITATION_EMAILS_IMPORTED,
				'entity_type' => 'legacy_invitation_email_recipient',
				'success' => 0,
				'error_message' => $exception->getMessage(),
			]);
			Session::setFlash('error', $exception->getMessage());
		}

		$this->redirectToIndex();
	}

	public function importJson(): void
	{
		if (!$this->isValidCsrf()) {
			$this->redirectWithError('Token CSRF non valido.');
		}

		$adminId = (int) ($_SESSION['user_id'] ?? 0);
		$sourceLabel = (string) ($_POST['json_source_label'] ?? '');

		try {
			$result = $this->emailService->importRecipientsFromJsonUpload($_FILES['json_file'] ?? [], $sourceLabel);
			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::LEGACY_INVITATION_EMAILS_IMPORTED,
				'entity_type' => 'legacy_invitation_email_recipient',
				'payload' => $result + [
					'source_label' => trim($sourceLabel),
					'import_type' => 'json_upload',
				],
			]);
			Session::setFlash('success', 'Import JSON completato: ' . (int) $result['created'] . ' email aggiunte, ' . (int) $result['duplicates'] . ' duplicate, ' . (int) $result['invalid'] . ' non valide.');
		} catch (Throwable $exception) {
			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::LEGACY_INVITATION_EMAILS_IMPORTED,
				'entity_type' => 'legacy_invitation_email_recipient',
				'success' => 0,
				'error_message' => $exception->getMessage(),
				'payload' => ['import_type' => 'json_upload'],
			]);
			Session::setFlash('error', $exception->getMessage());
		}

		$this->redirectToIndex();
	}

	public function send(): void
	{
		if (!$this->isValidCsrf()) {
			$this->redirectWithError('Token CSRF non valido.');
		}

		$adminId = (int) ($_SESSION['user_id'] ?? 0);
		$subject = (string) ($_POST['subject'] ?? '');
		$body = (string) ($_POST['body'] ?? '');
		$batchLimit = (int) ($_POST['batch_limit'] ?? 50);

		try {
			$result = $this->emailService->sendToPendingRecipients($subject, $body, $adminId, $batchLimit);
			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::LEGACY_INVITATION_EMAILS_SENT,
				'entity_type' => 'legacy_invitation_email_recipient',
				'payload' => [
					'subject' => trim($subject),
					'sent' => $result['sent'],
					'failed' => $result['failed'],
					'total' => $result['total'],
					'batch_limit' => $result['batch_limit'],
				],
			]);
			Session::setFlash('success', 'Pacchetto completato: ' . (int) $result['sent'] . ' email inviate, ' . (int) $result['failed'] . ' fallite. Limite pacchetto: ' . (int) $result['batch_limit'] . '.');
		} catch (Throwable $exception) {
			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::LEGACY_INVITATION_EMAILS_SENT,
				'entity_type' => 'legacy_invitation_email_recipient',
				'success' => 0,
				'error_message' => $exception->getMessage(),
			]);
			Session::setFlash('error', $exception->getMessage());
		}

		$this->redirectToIndex();
	}

	public function test(): void
	{
		if (!$this->isValidCsrf()) {
			$this->redirectWithError('Token CSRF non valido.');
		}

		$adminId = (int) ($_SESSION['user_id'] ?? 0);
		$email = (string) ($_POST['test_email'] ?? '');
		$subject = (string) ($_POST['subject'] ?? '');
		$body = (string) ($_POST['body'] ?? '');

		try {
			$sent = $this->emailService->sendTest($email, $subject, $body);
			if (!$sent) {
				throw new \RuntimeException('Invio email di test non riuscito.');
			}

			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::LEGACY_INVITATION_EMAIL_TEST_SENT,
				'entity_type' => 'legacy_invitation_email_recipient',
				'payload' => ['test_email' => trim($email), 'subject' => trim($subject)],
			]);
			Session::setFlash('success', 'Email di test inviata a ' . trim($email) . '.');
		} catch (Throwable $exception) {
			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::LEGACY_INVITATION_EMAIL_TEST_SENT,
				'entity_type' => 'legacy_invitation_email_recipient',
				'success' => 0,
				'error_message' => $exception->getMessage(),
				'payload' => ['test_email' => trim($email)],
			]);
			Session::setFlash('error', $exception->getMessage());
		}

		$this->redirectToIndex();
	}

	private function isValidCsrf(): bool
	{
		return !empty($_POST['csrf_token'])
			&& isset($_SESSION['csrf_token'])
			&& hash_equals((string) $_SESSION['csrf_token'], (string) $_POST['csrf_token']);
	}

	private function redirectWithError(string $message): void
	{
		Session::setFlash('error', $message);
		$this->redirectToIndex();
	}

	private function redirectToIndex(): void
	{
		header('Location: /admin/legacy-invitation-emails');
		exit();
	}
}
