<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Repositories\PhotoRepository;
use App\Services\AuditLogService;
use App\Services\PhotoService;
use App\Support\AuditLogActionType;
use Throwable;

final class AdminPhotoReportController extends Controller
{
	private PhotoRepository $photos;
	private PhotoService $photoService;
	private AuditLogService $auditLogService;

	public function __construct()
	{
		$db = Database::getInstance()->getConnection();
		$this->photos = new PhotoRepository($db);
		$this->photoService = new PhotoService($this->photos);
		$this->auditLogService = new AuditLogService();

		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
	}

	public function index(): void
	{
		$status = (string) ($_GET['status'] ?? 'open');
		if (!in_array($status, ['open', 'reviewed', 'closed', ''], true)) {
			$status = 'open';
		}

		$page = max(1, (int) ($_GET['page'] ?? 1));
		$perPage = 25;
		$total = $this->photos->countReports($status);
		$reports = array_map(
			fn (array $report): array => $this->photoService->decorate($report),
			$this->photos->listReports($status, $perPage, ($page - 1) * $perPage)
		);

		$this->view('admin/photos/reports', [
			'reports' => $reports,
			'status' => $status,
			'page' => $page,
			'totalPages' => max(1, (int) ceil($total / $perPage)),
			'total' => $total,
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function dismiss(array $params): void
	{
		$this->handleStatus((int) ($params[0] ?? 0), 'reviewed', 'Segnalazione archiviata.');
	}

	public function resolve(array $params): void
	{
		$this->handleStatus((int) ($params[0] ?? 0), 'closed', 'Segnalazione chiusa.');
	}

	public function hidePhoto(array $params): void
	{
		$reportId = (int) ($params[0] ?? 0);
		if (!$this->csrfIsValid()) {
			$this->redirectBackWithError('Token CSRF non valido.');
		}

		$adminId = (int) ($_SESSION['user_id'] ?? 0);
		try {
			$report = $this->photos->hidePhotoFromReport($reportId);
			if (!$report) {
				throw new \InvalidArgumentException('Segnalazione non trovata.');
			}
			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::PHOTO_REPORT_RESOLVED,
				'entity_type' => 'photo_report',
				'entity_id' => $reportId,
				'payload' => [
					'action' => 'hide_photo',
					'photo_id' => (int) $report['photo_id'],
					'event_id' => (int) $report['event_id'],
				],
			]);
			Session::setFlash('success', 'Foto nascosta e segnalazione chiusa.');
		} catch (Throwable $exception) {
			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::PHOTO_REPORT_RESOLVED,
				'entity_type' => 'photo_report',
				'entity_id' => $reportId,
				'success' => 0,
				'error_message' => $exception->getMessage(),
				'payload' => ['action' => 'hide_photo'],
			]);
			Session::setFlash('error', $exception->getMessage());
		}

		$this->redirectToIndex();
	}

	private function handleStatus(int $reportId, string $status, string $successMessage): void
	{
		if (!$this->csrfIsValid()) {
			$this->redirectBackWithError('Token CSRF non valido.');
		}

		$adminId = (int) ($_SESSION['user_id'] ?? 0);
		try {
			$report = $this->photos->findReport($reportId);
			if (!$report || !$this->photos->updateReportStatus($reportId, $status)) {
				throw new \InvalidArgumentException('Segnalazione non trovata o già aggiornata.');
			}
			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::PHOTO_REPORT_RESOLVED,
				'entity_type' => 'photo_report',
				'entity_id' => $reportId,
				'payload' => [
					'action' => $status,
					'photo_id' => (int) $report['photo_id'],
					'event_id' => (int) $report['event_id'],
				],
			]);
			Session::setFlash('success', $successMessage);
		} catch (Throwable $exception) {
			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => AuditLogActionType::PHOTO_REPORT_RESOLVED,
				'entity_type' => 'photo_report',
				'entity_id' => $reportId,
				'success' => 0,
				'error_message' => $exception->getMessage(),
				'payload' => ['action' => $status],
			]);
			Session::setFlash('error', $exception->getMessage());
		}

		$this->redirectToIndex();
	}

	private function csrfIsValid(): bool
	{
		return !empty($_POST['csrf_token'])
			&& !empty($_SESSION['csrf_token'])
			&& hash_equals((string) $_SESSION['csrf_token'], (string) $_POST['csrf_token']);
	}

	private function redirectBackWithError(string $message): void
	{
		Session::setFlash('error', $message);
		$this->redirectToIndex();
	}

	private function redirectToIndex(): void
	{
		$status = (string) ($_GET['status'] ?? 'open');
		$suffix = $status !== '' ? '?status=' . rawurlencode($status) : '';
		header('Location: /admin/photos/reports' . $suffix);
		exit();
	}
}
