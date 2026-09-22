<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AuditLogService;
use App\Services\OrganizationEmailService;
use App\Support\AuditLogActionType;
use Throwable;

class AdminOrganizationEmailController extends Controller
{
	private OrganizationEmailService $emailService;
	private AuditLogService $auditLogService;

	public function __construct()
	{
		$this->emailService = new OrganizationEmailService();
		$this->auditLogService = new AuditLogService();
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
	}

	public function index(): void
	{
		$this->view('admin/organization-emails/index', [
			'overview' => $this->emailService->getOverview(),
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function send(): void
	{
		if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: /admin/organization-emails');
			exit();
		}

		$adminId = (int) ($_SESSION['user_id'] ?? 0);
		$subject = (string) ($_POST['subject'] ?? '');
		$body = (string) ($_POST['body'] ?? '');

		try {
			$result = $this->emailService->sendToPendingOrganizations($subject, $body, $adminId);
			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::ORGANIZATION_EMAILS_SENT,
				'entity_type' => 'organization_email_delivery',
				'payload' => [
					'subject' => trim($subject),
					'sent' => $result['sent'],
					'failed' => $result['failed'],
					'total' => $result['total'],
				],
			]);
			Session::setFlash('success', 'Invio completato: ' . (int) $result['sent'] . ' email inviate, ' . (int) $result['failed'] . ' fallite.');
		} catch (Throwable $exception) {
			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::ORGANIZATION_EMAILS_SENT,
				'entity_type' => 'organization_email_delivery',
				'success' => 0,
				'error_message' => $exception->getMessage(),
			]);
			Session::setFlash('error', $exception->getMessage());
		}

		header('Location: /admin/organization-emails');
		exit();
	}

	public function test(): void
	{
		if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: /admin/organization-emails');
			exit();
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
				'action_type' => AuditLogActionType::ORGANIZATION_EMAIL_TEST_SENT,
				'entity_type' => 'organization_email_delivery',
				'payload' => [
					'test_email' => trim($email),
					'subject' => trim($subject),
				],
			]);
			Session::setFlash('success', 'Email di test inviata a ' . trim($email) . '.');
		} catch (Throwable $exception) {
			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::ORGANIZATION_EMAIL_TEST_SENT,
				'entity_type' => 'organization_email_delivery',
				'success' => 0,
				'error_message' => $exception->getMessage(),
				'payload' => ['test_email' => trim($email)],
			]);
			Session::setFlash('error', $exception->getMessage());
		}

		header('Location: /admin/organization-emails');
		exit();
	}
}
