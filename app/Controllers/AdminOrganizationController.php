<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\AuditLogService;
use App\Services\ImageService;
use App\Services\OrganizationService;
use App\Models\User;
use App\Support\AuditLogActionType;
use Throwable;

class AdminOrganizationController extends Controller
{
	private OrganizationService $organizationService;
	private AuditLogService $auditLogService;
	private ImageService $imageService;
	private User $userModel;

	public function __construct()
	{
		$this->organizationService = new OrganizationService();
		$this->auditLogService = new AuditLogService();
		$this->userModel = new User();
		$this->imageService = new ImageService(Database::getInstance()->getConnection());
		if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}

	public function index(): void
	{
		$this->view('admin/organizations/index', ['csrf_token' => $_SESSION['csrf_token']], 'admin');
	}

	public function data(): void
	{
		header('Content-Type: application/json; charset=utf-8');
		$rows = $this->organizationService->getAll();
		$search = trim((string) ($_GET['search'] ?? ''));
		$sort = (string) ($_GET['sort'] ?? 'created_at');
		$direction = strtolower((string) ($_GET['direction'] ?? 'desc'));
		$allowedSorts = ['id', 'name', 'slug', 'status', 'owner_username', 'member_count', 'master_count', 'created_at'];
		if (!in_array($sort, $allowedSorts, true)) $sort = 'created_at';
		if (!in_array($direction, ['asc', 'desc'], true)) $direction = 'desc';
		if ($search !== '') {
			$needle = mb_strtolower($search);
			$rows = array_values(array_filter($rows, static fn (array $row): bool => str_contains(mb_strtolower(implode(' ', [(string) ($row['name'] ?? ''), (string) ($row['slug'] ?? ''), (string) ($row['status'] ?? ''), (string) ($row['owner_username'] ?? '')])), $needle)));
		}
		usort($rows, static function (array $left, array $right) use ($sort, $direction): int {
			$result = strcmp((string) ($left[$sort] ?? ''), (string) ($right[$sort] ?? ''));
			return $direction === 'asc' ? $result : -$result;
		});
		$page = max((int) ($_GET['page'] ?? 1), 1);
		$perPage = (int) ($_GET['perPage'] ?? 25);
		if (!in_array($perPage, [10, 25, 50, 100], true)) $perPage = 25;
		$total = count($rows);
		$pages = max((int) ceil($total / $perPage), 1);
		$page = min($page, $pages);
		$rows = array_slice($rows, ($page - 1) * $perPage, $perPage);
		$data = array_map(static fn (array $row): array => [
			'id' => (int) $row['id'], 'name' => $row['name'], 'slug' => $row['slug'], 'status' => $row['status'],
			'owner_username' => $row['owner_username'] ?? '', 'member_count' => (int) $row['member_count'],
			'master_count' => (int) $row['master_count'], 'created_at' => $row['created_at'],
			'_links' => ['view' => '/admin/organizations/' . (int) $row['id']],
		], $rows);
		echo json_encode(['success' => true, 'data' => $data, 'meta' => ['page' => $page, 'pages' => $pages, 'total' => $total]]);
		exit();
	}

	public function show(array $params): void
	{
		$organization = $this->organizationService->findById((int) ($params[0] ?? 0));
		if (!$organization) { Session::setFlash('error', 'Organizzazione non trovata.'); header('Location: /admin/organizations'); exit(); }
		$this->view('admin/organizations/show', ['organization' => $organization, 'users' => $this->userModel->getAllUsers(), 'csrf_token' => $_SESSION['csrf_token']], 'admin');
	}

	public function userSearch(array $params): void
	{
		$query = trim((string) ($_GET['q'] ?? ''));
		$organizationId = (int) ($params[0] ?? 0);
		$this->jsonResponse(true, '', 200, ['users' => $this->organizationService->searchUsers($organizationId, $query)]);
	}

	public function masterSearch(array $params): void
	{
		$organizationId = (int) ($params[0] ?? 0);
		$query = trim((string) ($_GET['q'] ?? ''));
		$this->jsonResponse(true, '', 200, ['masters' => $this->organizationService->searchEventMasters($organizationId, $query)]);
	}

	public function addMaster(array $params): void { $this->masterAction((int) ($params[0] ?? 0), 'add'); }

	public function updateMaster(array $params): void { $this->masterAction((int) ($params[0] ?? 0), 'update'); }

	public function removeMaster(array $params): void { $this->masterAction((int) ($params[0] ?? 0), 'remove'); }

	private function masterAction(int $organizationId, string $action): void
	{
		$json = $this->isJsonRequest();
		if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) { if ($json) $this->jsonResponse(false, 'Token CSRF non valido.', 403); Session::setFlash('error', 'Token CSRF non valido.'); header('Location: /admin/organizations/' . $organizationId); exit(); }
		$eventMasterId = (int) ($_POST['event_master_id'] ?? 0);
		$role = (string) ($_POST['role'] ?? 'organizer');
		try {
			$ok = $action === 'add' ? $this->organizationService->addMaster($organizationId, $eventMasterId, $role) : ($action === 'remove' ? $this->organizationService->removeMaster($organizationId, $eventMasterId) : $this->organizationService->updateMaster($organizationId, $eventMasterId, $role));
			if (!$ok) throw new \RuntimeException('Master non aggiornato.');
			$this->auditLogService->logAudit(['user_id' => (int) $_SESSION['user_id'], 'action_type' => AuditLogActionType::ORGANIZATION_MASTER_UPDATED, 'entity_type' => 'organization', 'entity_id' => $organizationId, 'payload' => ['event_master_id' => $eventMasterId, 'role' => $role, 'operation' => $action]]);
			$message = $action === 'add' ? 'Master associato correttamente.' : ($action === 'remove' ? 'Master rimosso dall’organizzazione.' : 'Ruolo del master aggiornato.');
			Session::setFlash('success', $message);
			if ($json) $this->jsonResponse(true, $message);
		} catch (Throwable $exception) {
			$this->auditLogService->logAudit(['user_id' => (int) ($_SESSION['user_id'] ?? 0), 'action_type' => AuditLogActionType::ORGANIZATION_MASTER_UPDATED, 'entity_type' => 'organization', 'entity_id' => $organizationId, 'success' => 0, 'error_message' => $exception->getMessage(), 'payload' => ['event_master_id' => $eventMasterId, 'role' => $role, 'operation' => $action]]);
			Session::setFlash('error', $exception->getMessage());
			if ($json) $this->jsonResponse(false, $exception->getMessage(), 400);
		}
		header('Location: /admin/organizations/' . $organizationId); exit();
	}

	public function addMember(array $params): void
	{
		$this->memberAction((int) ($params[0] ?? 0), 'add');
	}

	public function updateMember(array $params): void
	{
		$this->memberAction((int) ($params[0] ?? 0), 'update');
	}

	private function memberAction(int $organizationId, string $action): void
	{
		$json = $this->isJsonRequest();
		if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) { if ($json) $this->jsonResponse(false, 'Token CSRF non valido.', 403); Session::setFlash('error', 'Token CSRF non valido.'); header('Location: /admin/organizations/' . $organizationId); exit(); }
		try {
			$userId = (int) ($_POST['user_id'] ?? 0);
			$role = (string) ($_POST['role'] ?? 'viewer');
			$status = $action === 'add' ? 'active' : (string) ($_POST['status'] ?? 'active');
			$ok = $action === 'add' ? $this->organizationService->addMember($organizationId, $userId, $role, (int) $_SESSION['user_id'], true) : $this->organizationService->updateMember($organizationId, $userId, $role, $status);
			if (!$ok) throw new \RuntimeException('Membro non aggiornato.');
			$this->auditLogService->logAudit(['user_id' => (int) $_SESSION['user_id'], 'action_type' => AuditLogActionType::ORGANIZATION_MEMBER_UPDATED, 'entity_type' => 'organization', 'entity_id' => $organizationId, 'payload' => ['member_user_id' => $userId, 'role' => $role, 'status' => $status, 'operation' => $action]]);
			$message = $action === 'add' ? 'Invito inviato correttamente.' : ($status === 'removed' ? 'Membro rimosso dall’organizzazione.' : 'Membro aggiornato correttamente.');
			Session::setFlash('success', $message);
			if ($json) $this->jsonResponse(true, $message);
		} catch (Throwable $exception) {
			$this->auditLogService->logAudit(['user_id' => (int) ($_SESSION['user_id'] ?? 0), 'action_type' => AuditLogActionType::ORGANIZATION_MEMBER_UPDATED, 'entity_type' => 'organization', 'entity_id' => $organizationId, 'success' => 0, 'error_message' => $exception->getMessage(), 'payload' => ['member_user_id' => (int) ($_POST['user_id'] ?? 0), 'role' => (string) ($_POST['role'] ?? ''), 'operation' => $action]]);
			Session::setFlash('error', $exception->getMessage());
			if ($json) $this->jsonResponse(false, $exception->getMessage(), 400);
		}
		header('Location: /admin/organizations/' . $organizationId); exit();
	}

	public function create(): void
	{
		$this->form(null);
	}

	public function edit(array $params): void
	{
		$organization = $this->organizationService->findById((int) ($params[0] ?? 0));
		if (!$organization) { Session::setFlash('error', 'Organizzazione non trovata.'); header('Location: /admin/organizations'); exit(); }
		header('Location: /admin/organizations/' . (int) $organization['id'] . '#data'); exit();
	}

	public function store(): void { $this->save(null); }

	public function update(array $params): void { $this->save((int) ($params[0] ?? 0)); }

	private function form(?array $organization): void
	{
		$this->view('admin/organizations/form', ['organization' => $organization, 'users' => $this->userModel->getAllUsers(), 'csrf_token' => $_SESSION['csrf_token']], 'admin');
	}

	private function save(?int $id): void
	{
		$json = $this->isJsonRequest();
		if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) { if ($json) $this->jsonResponse(false, 'Token CSRF non valido.', 403); Session::setFlash('error', 'Token CSRF non valido.'); header('Location: /admin/organizations'); exit(); }
		try {
			$data = $_POST;
			$uploadedImages = [];

			if ($id !== null) {
				$uploadedImages = $this->uploadOrganizationImages($data, $id);
			}

			$result = $this->organizationService->save($data, $id);
			$organizationId = $id ?? (int) $result;

			if ($id === null) {
				$uploadedImages = $this->uploadOrganizationImages($data, $organizationId);
				if ($uploadedImages !== []) {
					$this->organizationService->save($data, $organizationId);
				}
			}

			$this->auditLogService->logAudit(['user_id' => (int) $_SESSION['user_id'], 'action_type' => $id ? AuditLogActionType::ORGANIZATION_UPDATED : AuditLogActionType::ORGANIZATION_CREATED, 'entity_type' => 'organization', 'entity_id' => $organizationId, 'payload' => ['name' => trim((string) ($data['name'] ?? '')), 'uploaded_images' => $uploadedImages]]);
			$message = $id ? 'Organizzazione aggiornata.' : 'Organizzazione creata.';
			Session::setFlash('success', $message);
			if ($json) $this->jsonResponse(true, $message);
			header('Location: /admin/organizations/' . $organizationId); exit();
		} catch (Throwable $exception) {
			$this->auditLogService->logAudit(['user_id' => (int) ($_SESSION['user_id'] ?? 0), 'action_type' => $id ? AuditLogActionType::ORGANIZATION_UPDATED : AuditLogActionType::ORGANIZATION_CREATED, 'entity_type' => 'organization', 'entity_id' => $id, 'success' => 0, 'error_message' => $exception->getMessage(), 'payload' => ['name' => trim((string) ($_POST['name'] ?? ''))]]);
			if ($json) $this->jsonResponse(false, $exception->getMessage(), 400);
			Session::setFlash('error', $exception->getMessage());
			header('Location: ' . ($id ? '/admin/organizations/' . $id : '/admin/organizations/create')); exit();
		}
	}

	private function uploadOrganizationImages(array &$data, int $organizationId): array
	{
		$uploaded = [];

		foreach (['logo' => 'organization_logo', 'cover' => 'organization_cover'] as $field => $entityType) {
			if (empty($_FILES[$field]['name'])) {
				continue;
			}

			$imageId = $this->imageService->replacePrimary(
				$_FILES[$field],
				$entityType,
				$organizationId,
				trim((string) ($data['name'] ?? 'Organizzazione'))
			);

			if (!$imageId) {
				throw new \RuntimeException('Impossibile caricare l’immagine ' . $field . '.');
			}

			$image = $this->imageService->getPrimary($entityType, $organizationId, 'large');
			$data[$field . '_path'] = $image['path'] ?? null;
			$uploaded[] = $field;
		}

		return $uploaded;
	}

	public function updateStatus(array $params): void
	{
		$id = (int) ($params[0] ?? 0);
		$json = $this->isJsonRequest();
		if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) { if ($json) $this->jsonResponse(false, 'Token CSRF non valido.', 403); Session::setFlash('error', 'Token CSRF non valido.'); header('Location: /admin/organizations/' . $id); exit(); }
		try {
			$status = (string) ($_POST['status'] ?? '');
			if (!$this->organizationService->updateStatus($id, $status)) throw new \RuntimeException('Organizzazione non trovata.');
			$this->auditLogService->logAudit(['user_id' => (int) $_SESSION['user_id'], 'action_type' => AuditLogActionType::ORGANIZATION_STATUS_UPDATED, 'entity_type' => 'organization', 'entity_id' => $id, 'payload' => ['status' => $status]]);
			Session::setFlash('success', 'Stato organizzazione aggiornato.');
			if ($json) $this->jsonResponse(true, 'Stato organizzazione aggiornato.');
		} catch (Throwable $exception) {
			$this->auditLogService->logAudit(['user_id' => (int) $_SESSION['user_id'], 'action_type' => AuditLogActionType::ORGANIZATION_STATUS_UPDATED, 'entity_type' => 'organization', 'entity_id' => $id, 'success' => 0, 'error_message' => $exception->getMessage()]);
			Session::setFlash('error', $exception->getMessage());
			if ($json) $this->jsonResponse(false, $exception->getMessage(), 400);
		}
		header('Location: /admin/organizations/' . $id); exit();
	}

	private function isJsonRequest(): bool { return str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json'); }

	private function jsonResponse(bool $success, string $message, int $status = 200, array $data = []): void
	{
		http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode(array_merge(['success' => $success, 'message' => $message], $data)); exit();
	}
}
