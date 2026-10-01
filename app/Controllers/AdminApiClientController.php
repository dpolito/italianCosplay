<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Comune;
use App\Models\Provincia;
use App\Models\Regione;
use App\Repositories\ApiClientRepository;
use App\Repositories\ApiRequestLogRepository;
use App\Services\ApiClientService;
use App\Services\ApiSimulatorService;
use InvalidArgumentException;
use Throwable;

final class AdminApiClientController extends Controller
{
	private ApiClientRepository $clients;
	private ApiClientService $service;
	private ApiRequestLogRepository $logs;
	private ApiSimulatorService $simulator;
	private array $config;

	public function __construct()
	{
		$this->clients = new ApiClientRepository();
		$this->service = new ApiClientService($this->clients);
		$this->logs = new ApiRequestLogRepository();
		$this->simulator = new ApiSimulatorService($this->clients);
		$this->config = require APP_ROOT . '/app/config/api_management.php';
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
	}

	public function index(): void
	{
		$this->view('admin/api-clients/index', [
			'clients' => $this->clients->allWithUsageToday(),
			'summary' => $this->logs->summary(),
			'topEndpoints' => $this->logs->topEndpoints(),
			'csrf_token' => $_SESSION['csrf_token'] ?? '',
		], 'admin');
	}

	public function create(): void
	{
		$this->form();
	}

	public function edit(array $params): void
	{
		$this->form((int) ($params[0] ?? 0));
	}

	public function store(): void
	{
		try {
			$this->assertCsrf();
			$result = $this->service->create($_POST, (int) ($_SESSION['user_id'] ?? 0));
			Session::setFlash('success', 'API client creato.');
			$_SESSION['new_api_key_' . $result['id']] = $result['plain_key'];
			header('Location: /admin/api-clients/' . $result['id']);
		} catch (Throwable $exception) {
			Session::setFlash('error', $exception->getMessage());
			header('Location: /admin/api-clients/create');
		}
		exit();
	}

	public function update(array $params): void
	{
		$id = (int) ($params[0] ?? 0);
		try {
			$this->assertCsrf();
			$this->service->update($id, $_POST, (int) ($_SESSION['user_id'] ?? 0));
			Session::setFlash('success', 'API client aggiornato.');
		} catch (Throwable $exception) {
			Session::setFlash('error', $exception->getMessage());
		}
		header('Location: /admin/api-clients/' . $id);
		exit();
	}

	public function show(array $params): void
	{
		$id = (int) ($params[0] ?? 0);
		$client = $this->clients->find($id);
		if (!$client) {
			$this->notFound();
		}
		$newKey = $_SESSION['new_api_key_' . $id] ?? null;
		unset($_SESSION['new_api_key_' . $id]);
		$this->view('admin/api-clients/show', [
			'client' => $client,
			'scopes' => $this->clients->scopes($id),
			'availableScopes' => $this->service->scopes(),
			'newApiKey' => $newKey,
			'usageToday' => $this->clients->usageCount($id, 'day', date('Y-m-d 00:00:00')),
			'csrf_token' => $_SESSION['csrf_token'] ?? '',
		], 'admin');
	}

	public function rotate(array $params): void
	{
		$id = (int) ($params[0] ?? 0);
		try {
			$this->assertCsrf();
			$_SESSION['new_api_key_' . $id] = $this->service->rotate($id, (int) ($_SESSION['user_id'] ?? 0));
			Session::setFlash('success', 'API key ruotata. Copiala ora.');
		} catch (Throwable $exception) {
			Session::setFlash('error', $exception->getMessage());
		}
		header('Location: /admin/api-clients/' . $id);
		exit();
	}

	public function status(array $params): void
	{
		$id = (int) ($params[0] ?? 0);
		try {
			$this->assertCsrf();
			$this->service->setStatus($id, (string) ($_POST['status'] ?? ''), (int) ($_SESSION['user_id'] ?? 0));
			Session::setFlash('success', 'Stato API client aggiornato.');
		} catch (Throwable $exception) {
			Session::setFlash('error', $exception->getMessage());
		}
		header('Location: /admin/api-clients/' . $id);
		exit();
	}

	public function logs(): void
	{
		$page = max(1, (int) ($_GET['page'] ?? 1));
		$result = $this->logs->search([
			'client_id' => $_GET['client_id'] ?? null,
			'endpoint' => $_GET['endpoint'] ?? null,
			'status_code' => $_GET['status_code'] ?? null,
			'from' => $_GET['from'] ?? null,
			'to' => $_GET['to'] ?? null,
		], $page, 50);
		$this->view('admin/api-clients/logs', [
			'logs' => $result,
			'clients' => $this->clients->allWithUsageToday(),
			'summary' => $this->logs->summary(),
		], 'admin');
	}

	public function simulator(): void
	{
		$result = null;
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			try {
				$this->assertCsrf();
				$result = $this->simulator->simulate(
					(int) ($_POST['client_id'] ?? 0),
					(string) ($_POST['endpoint'] ?? ''),
					$_POST
				);
			} catch (Throwable $exception) {
				$result = [
					'status' => 403,
					'json' => json_encode([
						'success' => false,
						'error' => [
							'code' => 'SIMULATOR_ERROR',
							'message' => $exception->getMessage(),
						],
					], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
				];
			}
		}

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode([
				'success' => true,
				'result' => $this->publicSimulatorResult($result),
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit();
		}

		$this->view('admin/api-clients/simulator', [
			'clients' => $this->clients->allWithUsageToday(),
			'endpoints' => $this->simulator->endpoints(),
			'selectedClientId' => (int) ($_POST['client_id'] ?? $_GET['client_id'] ?? 0),
			'selectedEndpoint' => (string) ($_POST['endpoint'] ?? $_GET['endpoint'] ?? 'events_search'),
			'regions' => (new Regione())->getAll(),
			'provinces' => (new Provincia())->getByRegioneId(null),
			'municipalities' => (new Comune())->getAll(null),
			'result' => $result,
			'csrf_token' => $_SESSION['csrf_token'] ?? '',
		], 'admin');
	}

	private function form(?int $id = null): void
	{
		$client = $id ? $this->clients->find($id) : null;
		if ($id && !$client) {
			$this->notFound();
		}
		$this->view('admin/api-clients/form', [
			'client' => $client,
			'selectedScopes' => $id ? $this->clients->scopes($id) : [],
			'availableScopes' => $this->service->scopes(),
			'config' => $this->config,
			'csrf_token' => $_SESSION['csrf_token'] ?? '',
		], 'admin');
	}

	private function assertCsrf(): void
	{
		if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) {
			throw new InvalidArgumentException('Token CSRF non valido.');
		}
	}

	private function publicSimulatorResult(?array $result): ?array
	{
		if ($result === null) {
			return null;
		}
		if (!empty($result['client']) && is_array($result['client'])) {
			$result['client'] = [
				'id' => (int) ($result['client']['id'] ?? 0),
				'name' => (string) ($result['client']['name'] ?? ''),
				'environment' => (string) ($result['client']['environment'] ?? ''),
				'status' => (string) ($result['client']['status'] ?? ''),
				'key_prefix' => (string) ($result['client']['key_prefix'] ?? ''),
			];
		}

		return $result;
	}

	private function notFound(): void
	{
		header('HTTP/1.0 404 Not Found');
		require APP_ROOT . '/app/views/errors/404.php';
		exit();
	}
}
