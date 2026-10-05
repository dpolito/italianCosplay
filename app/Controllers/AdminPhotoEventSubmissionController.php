<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Repositories\PhotoEventSubmissionRepository;
use App\Services\PhotoEventSubmissionService;
use Throwable;

final class AdminPhotoEventSubmissionController extends Controller
{
	private PhotoEventSubmissionRepository $submissions;
	private PhotoEventSubmissionService $service;

	public function __construct()
	{
		$db = Database::getInstance()->getConnection();
		$this->submissions = new PhotoEventSubmissionRepository($db);
		$this->service = new PhotoEventSubmissionService($this->submissions);
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
	}

	public function index(): void
	{
		$status = (string) ($_GET['status'] ?? 'pending');
		if (!in_array($status, ['pending', 'approved', 'merged', 'rejected', ''], true)) {
			$status = 'pending';
		}
		$page = max(1, (int) ($_GET['page'] ?? 1));
		$perPage = 25;
		$total = $this->submissions->count($status);
		$this->view('admin/photos/event-submissions', [
			'submissions' => $this->submissions->list($status, $perPage, ($page - 1) * $perPage),
			'status' => $status,
			'page' => $page,
			'totalPages' => max(1, (int) ceil($total / $perPage)),
			'total' => $total,
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function merge(array $params): void
	{
		if (!$this->csrfIsValid()) {
			$this->redirectWithError('Token CSRF non valido.');
		}
		try {
			$this->service->mergeIntoEvent((int) ($params[0] ?? 0), (int) ($_POST['resolved_event_id'] ?? 0), (int) ($_SESSION['user_id'] ?? 0));
			Session::setFlash('success', 'Foto collegate all\'evento selezionato e rese pubbliche.');
		} catch (Throwable $exception) {
			Session::setFlash('error', $exception->getMessage());
		}
		$this->redirectToIndex();
	}

	public function reject(array $params): void
	{
		if (!$this->csrfIsValid()) {
			$this->redirectWithError('Token CSRF non valido.');
		}
		try {
			$this->service->reject((int) ($params[0] ?? 0), (int) ($_SESSION['user_id'] ?? 0));
			Session::setFlash('success', 'Segnalazione rifiutata. Le foto restano non pubbliche.');
		} catch (Throwable $exception) {
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

	private function redirectWithError(string $message): void
	{
		Session::setFlash('error', $message);
		$this->redirectToIndex();
	}

	private function redirectToIndex(): void
	{
		$status = (string) ($_GET['status'] ?? 'pending');
		$suffix = $status !== '' ? '?status=' . rawurlencode($status) : '';
		header('Location: /admin/photos/event-submissions' . $suffix);
		exit();
	}
}
