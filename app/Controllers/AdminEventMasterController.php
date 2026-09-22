<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\EventMaster;
use App\Services\AuditLogService;
use App\Services\ImageService;
use App\Support\AuditLogActionType;

class AdminEventMasterController extends Controller
{
	private EventMaster $eventMasterModel;
	private ImageService $imageService;
	private AuditLogService $auditLogService;

	public function __construct()
	{
		$this->eventMasterModel = new EventMaster();
		$this->imageService = new ImageService($this->eventMasterModel->getDbConnection());
		$this->auditLogService = new AuditLogService();
	}

	private function validateCsrfToken(): bool
	{
		if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
			Session::setFlash('error', 'Errore di sicurezza: richiesta non valida (CSRF).');
			return false;
		}

		return true;
	}

	private function isAjaxRequest(): bool
	{
		return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
	}

	private function getEventMasterCover(int $id): array
	{
		$cover = $this->imageService->getPrimary('event_master', $id, 'medium');
		if (empty($cover)) {
			return [
				'url' => '',
				'width' => null,
				'height' => null,
			];
		}

		return [
			'url' => '/public_assets' . $cover['path'],
			'width' => $cover['width'] ?? null,
			'height' => $cover['height'] ?? null,
		];
	}

	public function index(): void
	{
		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Eventi Master', 'url' => URL_ROOT . '/admin/events-master'],
		];

		$this->view('admin/events-master/all', [
			'csrf_token' => $_SESSION['csrf_token'],
			'breadcrumbs' => $breadcrumbs,
		], 'admin');
	}

	public function data(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$eventMasters = $this->eventMasterModel->getAll();
		$page = max((int) ($_GET['page'] ?? 1), 1);
		$perPage = (int) ($_GET['perPage'] ?? 25);
		if (!in_array($perPage, [10, 25, 50, 100], true)) {
			$perPage = 25;
		}
		$search = trim($_GET['search'] ?? '');
		$sort = $_GET['sort'] ?? 'nome';
		$direction = strtolower($_GET['direction'] ?? 'asc');
		$allowedSorts = ['id', 'nome', 'slug', 'created_at'];
		if (!in_array($sort, $allowedSorts, true)) {
			$sort = 'nome';
		}
		if (!in_array($direction, ['asc', 'desc'], true)) {
			$direction = 'asc';
		}

		$eventMasters = array_values(array_filter($eventMasters, static function (array $eventMaster) use ($search): bool {
			if ($search === '') {
				return true;
			}

			return str_contains(
				strtolower(implode(' ', $eventMaster)),
				strtolower($search)
			);
		}));

		usort($eventMasters, static function (array $left, array $right) use ($sort, $direction): int {
			$leftValue = $left[$sort] ?? '';
			$rightValue = $right[$sort] ?? '';
			$result = strcmp((string) $leftValue, (string) $rightValue);
			return $direction === 'asc' ? $result : -$result;
		});

		$total = count($eventMasters);
		$pages = max((int) ceil($total / $perPage), 1);
		$page = min($page, $pages);
		$slice = array_slice($eventMasters, ($page - 1) * $perPage, $perPage);

		$data = array_map(function (array $eventMaster): array {
			$cover = $this->getEventMasterCover((int) $eventMaster['id']);

			return [
				'id' => $eventMaster['id'],
				'nome' => $eventMaster['nome'],
				'slug' => $eventMaster['slug'] ?? '',
				'sito_web' => $eventMaster['sito_web'] ?? '',
				'created_at' => $eventMaster['created_at'] ?? null,
				'status' => $eventMaster['status'] ?? 'active',
				'is_public' => (int) ($eventMaster['is_public'] ?? 1),
				'cover' => $cover['url'] ?? '',
				'_links' => [
					'edit' => '/admin/events-master/edit/' . $eventMaster['id'],
					'delete' => '/admin/events-master/delete/' . $eventMaster['id'],
				],
			];
		}, $slice);

		echo json_encode([
			'success' => true,
			'data' => $data,
			'meta' => [
				'page' => $page,
				'pages' => $pages,
				'total' => $total,
			],
		]);
		exit();
	}

	public function detail(array $params): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$id = (int) ($params[0] ?? 0);
		$eventMaster = $this->eventMasterModel->find($id);

		if (!$eventMaster) {
			echo json_encode([
				'success' => false,
				'message' => 'Evento master non trovato',
			]);
			exit();
		}

		$cover = $this->getEventMasterCover($id);

		echo json_encode([
			'success' => true,
			'data' => [
				'id' => $eventMaster['id'],
				'nome' => $eventMaster['nome'],
				'slug' => $eventMaster['slug'] ?? '',
				'descrizione' => $eventMaster['descrizione'] ?? '',
				'sito_web' => $eventMaster['sito_web'] ?? '',
				'social_facebook' => $eventMaster['social_facebook'] ?? '',
				'social_twitter' => $eventMaster['social_twitter'] ?? '',
				'social_instagram' => $eventMaster['social_instagram'] ?? '',
				'social_tiktok' => $eventMaster['social_tiktok'] ?? '',
				'social_youtube' => $eventMaster['social_youtube'] ?? '',
				'created_at' => $eventMaster['created_at'] ?? null,
				'updated_at' => $eventMaster['updated_at'] ?? null,
				'status' => $eventMaster['status'] ?? 'active',
				'is_public' => (int) ($eventMaster['is_public'] ?? 1),
				'cover' => $cover['url'] ?? '',
				'_links' => [
					'edit' => '/admin/events-master/edit/' . $eventMaster['id'],
					'delete' => '/admin/events-master/delete/' . $eventMaster['id'],
				],
			],
		]);
		exit();
	}

	public function create(): void
	{
		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Eventi Master', 'url' => URL_ROOT . '/admin/events-master'],
			['label' => 'Crea Evento Master', 'url' => URL_ROOT . '/admin/events-master/create'],
		];

		$this->view('admin/events-master/create', [
			'csrf_token' => $_SESSION['csrf_token'],
			'breadcrumbs' => $breadcrumbs,
		], 'admin');
	}

	public function store(): void
	{
		if (!$this->validateCsrfToken()) {
			header('Location: ' . URL_ROOT . '/admin/events-master/create');
			exit();
		}

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Location: ' . URL_ROOT . '/admin/events-master/create');
			exit();
		}

		$title = trim($_POST['nome'] ?? $_POST['titolo'] ?? $_POST['name'] ?? '');
		$data = [
			'nome' => $title,
			'slug' => trim($_POST['slug'] ?? ''),
			'descrizione' => trim($_POST['descrizione'] ?? $_POST['description'] ?? ''),
			'sito_web' => trim($_POST['sito_web'] ?? $_POST['website'] ?? ''),
			'social_facebook' => trim($_POST['social_facebook'] ?? ''),
			'social_twitter' => trim($_POST['social_twitter'] ?? ''),
			'social_instagram' => trim($_POST['social_instagram'] ?? ''),
			'social_tiktok' => trim($_POST['social_tiktok'] ?? ''),
			'social_youtube' => trim($_POST['social_youtube'] ?? ''),
		];

		$errors = [];
		if ($title === '') {
			$errors[] = 'Il nome è obbligatorio.';
		}
		if (strlen($title) > 255) {
			$errors[] = 'Il nome è troppo lungo.';
		}
			foreach (['sito_web', 'social_facebook', 'social_twitter', 'social_instagram', 'social_tiktok', 'social_youtube'] as $field) {
			if ($data[$field] !== '' && !filter_var($data[$field], FILTER_VALIDATE_URL)) {
				$errors[] = 'L\'URL del campo ' . $field . ' non è valido.';
			}
		}

		if ($errors) {
			Session::setFlash('error', implode('<br>', $errors));
			if ($this->isAjaxRequest()) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode([
					'success' => false,
					'message' => implode(' ', $errors),
				]);
				exit();
			}
			$breadcrumbs = [
				['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
				['label' => 'Eventi Master', 'url' => URL_ROOT . '/admin/events-master'],
				['label' => 'Crea Evento Master', 'url' => URL_ROOT . '/admin/events-master/create'],
			];
			$this->view('admin/events-master/create', array_merge($_POST, [
				'csrf_token' => $_SESSION['csrf_token'],
				'error' => Session::getFlash('error'),
				'breadcrumbs' => $breadcrumbs,
			]), 'admin');
			return;
		}

		$id = $this->eventMasterModel->create($data);
		if (!empty($_FILES['immagine']['name'])) {
			$this->imageService->upload(
				$_FILES['immagine'],
				'event_master',
				$id,
				$title,
				true
			);
		}
		$this->auditLogService->logAudit([
			'user_id' => $_SESSION['user_id'] ?? null,
			'action_type' => AuditLogActionType::EVENT_MASTER_CREATED,
			'entity_type' => 'event_master',
			'entity_id' => $id,
			'success' => 1,
			'payload' => [
				'nome' => $title,
				'slug' => $data['slug'] ?? null,
				'has_image' => !empty($_FILES['immagine']['name']),
			],
		]);
		Session::setFlash('success', 'Evento master creato con successo.');
		if ($this->isAjaxRequest()) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode([
				'success' => true,
				'message' => 'Evento master creato con successo.',
				'redirect' => URL_ROOT . '/admin/events-master/edit/' . $id,
			]);
			exit();
		}
		header('Location: ' . URL_ROOT . '/admin/events-master/edit/' . $id);
		exit();
	}

	public function edit(array $params): void
	{
		$id = (int) ($params[0] ?? 0);
		$eventMaster = $this->eventMasterModel->find($id);
		if (!$eventMaster) {
			Session::setFlash('error', 'Evento master non trovato.');
			header('Location: ' . URL_ROOT . '/admin/events-master');
			exit();
		}

		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Eventi Master', 'url' => URL_ROOT . '/admin/events-master'],
			['label' => 'Modifica Evento Master', 'url' => URL_ROOT . '/admin/events-master/edit/' . $id],
		];

		$this->view('admin/events-master/edit', [
			'eventMaster' => $eventMaster,
			'cover' => $this->getEventMasterCover($id),
			'csrf_token' => $_SESSION['csrf_token'],
			'breadcrumbs' => $breadcrumbs,
		], 'admin');
	}

	public function update(array $params): void
	{
		if (!$this->validateCsrfToken()) {
			header('Location: ' . URL_ROOT . '/admin/events-master');
			exit();
		}

		$id = (int) ($params[0] ?? 0);
		if ($id <= 0) {
			Session::setFlash('error', 'ID evento master non valido.');
			header('Location: ' . URL_ROOT . '/admin/events-master');
			exit();
		}

		$data = [
			'nome' => trim($_POST['nome'] ?? $_POST['titolo'] ?? $_POST['name'] ?? ''),
			'slug' => trim($_POST['slug'] ?? ''),
			'descrizione' => trim($_POST['descrizione'] ?? $_POST['description'] ?? ''),
			'sito_web' => trim($_POST['sito_web'] ?? $_POST['website'] ?? ''),
			'social_facebook' => trim($_POST['social_facebook'] ?? ''),
			'social_twitter' => trim($_POST['social_twitter'] ?? ''),
			'social_instagram' => trim($_POST['social_instagram'] ?? ''),
			'social_tiktok' => trim($_POST['social_tiktok'] ?? ''),
			'social_youtube' => trim($_POST['social_youtube'] ?? ''),
			'status' => in_array($_POST['status'] ?? '', ['draft', 'pending_review', 'active', 'suspended', 'archived'], true) ? $_POST['status'] : 'pending_review',
			'is_public' => !empty($_POST['is_public']) ? 1 : 0,
		];

		$errors = [];
		if ($data['nome'] === '') {
			$errors[] = 'Il nome è obbligatorio.';
		}
		if (strlen($data['nome']) > 255) {
			$errors[] = 'Il nome è troppo lungo.';
		}
		foreach (['sito_web', 'social_facebook', 'social_twitter', 'social_instagram', 'social_tiktok', 'social_youtube'] as $field) {
			if ($data[$field] !== '' && !filter_var($data[$field], FILTER_VALIDATE_URL)) {
				$errors[] = 'L\'URL del campo ' . $field . ' non è valido.';
			}
		}

		if ($errors) {
			Session::setFlash('error', implode('<br>', $errors));
			if ($this->isAjaxRequest()) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode([
					'success' => false,
					'message' => implode(' ', $errors),
				]);
				exit();
			}
			$eventMaster = $this->eventMasterModel->find($id);
			$breadcrumbs = [
				['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
				['label' => 'Eventi Master', 'url' => URL_ROOT . '/admin/events-master'],
				['label' => 'Modifica Evento Master', 'url' => URL_ROOT . '/admin/events-master/edit/' . $id],
			];
			$this->view('admin/events-master/edit', [
				'eventMaster' => array_merge($eventMaster ?? [], $_POST),
				'cover' => $this->getEventMasterCover($id),
				'csrf_token' => $_SESSION['csrf_token'],
				'breadcrumbs' => $breadcrumbs,
			], 'admin');
			return;
		}

		if (!empty($_FILES['immagine']['name'])) {
			$this->imageService->replacePrimary(
				$_FILES['immagine'],
				'event_master',
				$id,
				$data['nome']
			);
		}

		$this->eventMasterModel->update($id, $data);
		$this->auditLogService->logAudit([
			'user_id' => $_SESSION['user_id'] ?? null,
			'action_type' => AuditLogActionType::EVENT_MASTER_UPDATED,
			'entity_type' => 'event_master',
			'entity_id' => $id,
			'success' => 1,
			'payload' => [
				'nome' => $data['nome'],
				'slug' => $data['slug'] ?? null,
				'has_image' => !empty($_FILES['immagine']['name']),
			],
		]);
		Session::setFlash('success', 'Evento master aggiornato con successo.');
		if ($this->isAjaxRequest()) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode([
				'success' => true,
				'message' => 'Evento master aggiornato con successo.',
				'redirect' => URL_ROOT . '/admin/events-master/edit/' . $id,
			]);
			exit();
		}
		header('Location: ' . URL_ROOT . '/admin/events-master/edit/' . $id);
		exit();
	}

	public function delete(array $params): void
	{
		if (!$this->validateCsrfToken()) {
			header('Location: ' . URL_ROOT . '/admin/events-master');
			exit();
		}

		$id = (int) ($params[0] ?? 0);
		if ($id > 0) {
			$this->eventMasterModel->delete($id, isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null);
			$this->auditLogService->logAudit([
				'user_id' => $_SESSION['user_id'] ?? null,
				'action_type' => AuditLogActionType::EVENT_MASTER_DELETED,
				'entity_type' => 'event_master',
				'entity_id' => $id,
				'success' => 1,
				'payload' => [
					'id' => $id,
				],
			]);
			Session::setFlash('success', 'Evento master eliminato.');
		}

		header('Location: ' . URL_ROOT . '/admin/events-master');
		exit();
	}
}
