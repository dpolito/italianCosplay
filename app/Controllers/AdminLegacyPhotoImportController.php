<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\LegacyPhotoImportService;
use Throwable;

final class AdminLegacyPhotoImportController extends Controller
{
	private LegacyPhotoImportService $service;

	public function __construct()
	{
		$this->service = new LegacyPhotoImportService();
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
	}

	public function index(): void
	{
		$importId = (int) ($_GET['import_id'] ?? 0);
		$this->view('admin/photos/import-legacy', [
			'csrf_token' => $_SESSION['csrf_token'],
			'recentImports' => $this->service->recentImports(),
			'currentImport' => $importId > 0 ? $this->service->getImport($importId) : null,
		], 'admin');
	}

	public function analyze(): void
	{
		$this->assertCsrf();
		try {
			$result = $this->service->analyzeUpload($_FILES['json_file'] ?? [], (int) ($_POST['destination_user_id'] ?? 0), (int) ($_SESSION['user_id'] ?? 0));
			$this->json(true, 'File analizzato.', 200, $result);
		} catch (Throwable $exception) {
			$this->json(false, $exception->getMessage(), 422);
		}
	}

	public function mapping(array $params): void
	{
		$this->assertCsrf();
		try {
			$mapping = json_decode((string) ($_POST['mapping'] ?? '{}'), true, 512, JSON_THROW_ON_ERROR);
			if (!is_array($mapping)) {
				throw new \InvalidArgumentException('Mapping non valido.');
			}
			$this->service->saveMapping((int) ($params[0] ?? 0), $mapping, (int) ($_SESSION['user_id'] ?? 0));
			$this->json(true, 'Mapping salvato.');
		} catch (Throwable $exception) {
			$this->json(false, $exception->getMessage(), 422);
		}
	}

	public function process(array $params): void
	{
		$this->assertCsrf();
		try {
			$groupKey = trim((string) ($_POST['group_key'] ?? '')) ?: null;
			$result = $this->service->processBatch((int) ($params[0] ?? 0), (int) ($_SESSION['user_id'] ?? 0), !empty($_POST['retry_errors']), $groupKey);
			$this->json(true, 'Batch elaborato.', 200, $result);
		} catch (Throwable $exception) {
			$this->json(false, $exception->getMessage(), 422);
		}
	}

	public function searchEvents(): void
	{
		try {
			$query = trim((string) ($_GET['q'] ?? ''));
			$year = filter_var($_GET['year'] ?? null, FILTER_VALIDATE_INT) ?: null;
			$this->json(true, '', 200, ['events' => $this->service->searchEvents($query, $year)]);
		} catch (Throwable $exception) {
			$this->json(false, $exception->getMessage(), 422);
		}
	}

	public function status(array $params): void
	{
		try {
			$this->json(true, '', 200, $this->service->status((int) ($params[0] ?? 0)));
		} catch (Throwable $exception) {
			$this->json(false, $exception->getMessage(), 404);
		}
	}

	public function report(array $params): void
	{
		$csv = $this->service->reportCsv((int) ($params[0] ?? 0));
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="legacy-photo-import-' . (int) ($params[0] ?? 0) . '.csv"');
		echo $csv;
	}

	private function assertCsrf(): void
	{
		$token = (string) ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
		if ($token === '' || empty($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $token)) {
			$this->json(false, 'Token CSRF non valido.', 419);
		}
	}

	private function json(bool $success, string $message = '', int $status = 200, array $data = []): void
	{
		http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['success' => $success, 'message' => $message] + $data, JSON_UNESCAPED_UNICODE);
		exit();
	}
}
