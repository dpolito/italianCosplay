<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\EventMaster;
use App\Services\AuditLogService;
use App\Services\EventMasterClaimService;
use App\Support\AuditLogActionType;
use Throwable;

class EventMasterClaimController extends Controller
{
	private EventMaster $eventMasterModel;
	private EventMasterClaimService $claimService;
	private AuditLogService $auditLogService;

	public function __construct()
	{
		$this->eventMasterModel = new EventMaster();
		$this->claimService = new EventMasterClaimService();
		$this->auditLogService = new AuditLogService();
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
	}

	public function create(array $params): void
	{
		$this->requireAuth();
		$master = $this->eventMasterModel->findPublicBySlug((string) ($params[0] ?? ''));
		if (!$master) {
			http_response_code(404);
			$this->view('errors/404');
			return;
		}

		if ($this->claimService->hasMasterAssociation((int) $master['id'])) {
			Session::setFlash('error', 'Questo evento è già gestito da un’organizzazione.');
			header('Location: ' . URL_ROOT_SITE . '/eventi-master/' . rawurlencode((string) $master['slug']));
			exit();
		}

		$this->view('events/master-claim', [
			'eventMaster' => $master,
			'organizations' => $this->claimService->getOrganizationsForUser((int) $_SESSION['user_id']),
			'csrf_token' => $_SESSION['csrf_token'],
		]);
	}

	public function store(array $params): void
	{
		$this->requireAuth();
		$slug = (string) ($params[0] ?? '');
		$master = $this->eventMasterModel->findPublicBySlug($slug);
		if (!$master || empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
			Session::setFlash('error', 'Richiesta non valida.');
			header('Location: /eventi-master/' . rawurlencode($slug) . '/riscatta');
			exit();
		}

		if ($this->claimService->hasMasterAssociation((int) $master['id'])) {
			Session::setFlash('error', 'Questo evento è già gestito da un’organizzazione.');
			header('Location: ' . URL_ROOT_SITE . '/eventi-master/' . rawurlencode($slug));
			exit();
		}

		$organizationId = filter_var($_POST['organization_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		$organizationId = $organizationId === false ? null : $organizationId;
		try {
			$this->claimService->createClaim(
				(int) $_SESSION['user_id'],
				(int) $master['id'],
				$organizationId,
				trim((string) ($_POST['organization_name'] ?? '')),
				trim((string) ($_POST['evidence'] ?? '')),
				!empty($_POST['privacy_accept'])
			);
			Session::setFlash('success', 'Richiesta inviata. Ti contatteremo dopo la verifica.');
			header('Location: ' . URL_ROOT_SITE . '/eventi-master/' . rawurlencode($slug));
			exit();
		} catch (Throwable $exception) {
			$this->auditLogService->logAudit([
				'user_id' => (int) $_SESSION['user_id'],
				'action_type' => AuditLogActionType::EVENT_MASTER_CLAIM_REQUESTED,
				'entity_type' => 'event_master',
				'entity_id' => (int) $master['id'],
				'success' => 0,
				'error_message' => $exception->getMessage(),
			]);
			Session::setFlash('error', $exception->getMessage());
			header('Location: /eventi-master/' . rawurlencode($slug) . '/riscatta');
			exit();
		}
	}

	public function organizationSearch(array $params): void
	{
		$this->requireAuth();
		$master = $this->eventMasterModel->findPublicBySlug((string) ($params[0] ?? ''));
		if (!$master) { $this->jsonResponse(false, 'Evento master non trovato.', 404); }
		if ($this->claimService->hasMasterAssociation((int) $master['id'])) { $this->jsonResponse(false, 'Evento già gestito da un’organizzazione.', 409); }
		$query = trim((string) ($_GET['q'] ?? ''));
		$this->jsonResponse(true, '', 200, ['organizations' => $this->claimService->searchOrganizationsForClaim((int) $_SESSION['user_id'], (int) $master['id'], $query)]);
	}

	private function requireAuth(): void
	{
		if (empty($_SESSION['user_id'])) {
			header('Location: /register');
			exit();
		}
	}

	private function jsonResponse(bool $success, string $message, int $status = 200, array $data = []): void
	{
		http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
		exit();
	}
}
