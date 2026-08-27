<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Guest;
use App\Services\AuditLogService;
use App\Services\ImageService;
use App\Support\AuditLogActionType;
use function date;
use function file_get_contents;
use function header;
use function json_decode;
use function json_encode;
use function trim;
use function var_dump;

class AdminGuestController extends Controller
{
	private Guest $guestModel;
	private ImageService $imageService;
	private AuditLogService $auditLogService;

	public function __construct()
	{
		$this->guestModel = new Guest();
		$this->imageService = new ImageService($this->guestModel->getDbConnection());
		$this->auditLogService = new AuditLogService();
	}

	/**
	 * Lista articoli Guest
	 */
	public function all()
	{
		$guests = $this->guestModel->getAll();

		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Guest', 'url' => URL_ROOT . '/admin/guests/all'],
		];

		$this->view('admin/guests/all', [
			'guests' => $guests,
			'csrf_token' => $_SESSION['csrf_token'],
			'breadcrumbs' => $breadcrumbs
		], 'admin');
	}

	public function data(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$guests = $this->guestModel->getAll();
		$page = max((int) ($_GET['page'] ?? 1), 1);
		$perPage = (int) ($_GET['perPage'] ?? 25);
		if (!in_array($perPage, [10, 25, 50, 100], true)) {
			$perPage = 25;
		}
		$search = trim($_GET['search'] ?? '');
		$sort = $_GET['sort'] ?? 'created_at';
		$direction = strtolower($_GET['direction'] ?? 'desc');
		$allowedSorts = ['id', 'name', 'slug', 'created_at'];
		if (!in_array($sort, $allowedSorts, true)) {
			$sort = 'created_at';
		}
		if (!in_array($direction, ['asc', 'desc'], true)) {
			$direction = 'desc';
		}

		$guests = array_values(array_filter($guests, static function (array $guest) use ($search): bool {
			if ($search === '') {
				return true;
			}
			return str_contains(
				strtolower(implode(' ', $guest)),
				strtolower($search)
			);
		}));
		usort($guests, static function (array $left, array $right) use ($sort, $direction): int {
			$leftValue = $left[$sort] ?? '';
			$rightValue = $right[$sort] ?? '';
			$result = strcmp((string) $leftValue, (string) $rightValue);
			return $direction === 'asc' ? $result : -$result;
		});

		$total = count($guests);
		$pages = max((int) ceil($total / $perPage), 1);
		$page = min($page, $pages);
		$slice = array_slice($guests, ($page - 1) * $perPage, $perPage);

		$data = array_map(static function (array $guest): array {
			return [
				'id' => $guest['id'],
				'name' => $guest['name'],
				'slug' => $guest['slug'],
				'created_at' => $guest['created_at'] ?? null,
				'_links' => [
					'edit' => '/admin/guests/edit/' . $guest['id'],
					'delete' => '/admin/guests/delete/' . $guest['id'],
				],
			];
		}, $slice);

		echo json_encode([
			'success' => true,
			'data' => $data,
			'meta' => ['page' => $page, 'pages' => $pages, 'total' => $total],
		]);
		exit();
	}

	public function detail($params): void
	{
		header('Content-Type: application/json; charset=utf-8');
		$id = (int) ($params[0] ?? 0);
		$guest = $this->guestModel->find($id);

		if (!$guest) {
			echo json_encode(['success' => false, 'message' => 'Guest non trovato']);
			exit();
		}

		echo json_encode([
			'success' => true,
			'data' => [
				'id' => $guest['id'],
				'name' => $guest['name'],
				'slug' => $guest['slug'],
				'bio' => $guest['bio'] ?? '',
				'website' => $guest['website'] ?? '',
				'instagram' => $guest['instagram'] ?? '',
				'tiktok' => $guest['tiktok'] ?? '',
				'youtube' => $guest['youtube'] ?? '',
				'created_at' => $guest['created_at'] ?? null,
				'updated_at' => $guest['updated_at'] ?? null,
				'_links' => [
					'edit' => '/admin/guests/edit/' . $guest['id'],
					'delete' => '/admin/guests/delete/' . $guest['id'],
				],
			],
		]);
		exit();
	}

	/**
	 * Form creazione articolo
	 */
	public function create()
	{
		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Guest', 'url' => URL_ROOT . '/admin/guests/all'],
			['label' => 'Crea Articolo', 'url' => URL_ROOT . '/admin/guests/create'],
		];

		$this->view('admin/guests/create', [
			'csrf_token' => $_SESSION['csrf_token'],
			'breadcrumbs' => $breadcrumbs
		], 'admin');
	}

	/**
	 * Salvataggio articolo
	 */
	public function store()
	{
		if (!$this->validateCsrfToken()) {
			header('Location: ' . URL_ROOT . '/admin/guests/create');
			exit();
		}

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Location: ' . URL_ROOT . '/admin/guests/create');
			exit();
		}

		$name = trim($_POST['name'] ?? '');

		$errors = [];

		if (empty($name)) {
			$errors[] = 'Il Nome e Cognome è obbligatorio.';
		}

		if (strlen($name) > 255) {
			$errors[] = 'Nome e Cognome lungo.';
		}

		if (empty($_POST['bio'])) {
			$errors[] = 'Il bio è obbligatorio.';
		}

		if (!empty($errors)) {
			Session::setFlash('error', implode('<br>', $errors));

			$breadcrumbs = [
				['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
				['label' => 'Guest', 'url' => URL_ROOT . '/admin/guests/all'],
				['label' => 'Crea Guest', 'url' => URL_ROOT . '/admin/guests/create'],
			];

			$this->view('admin/guest/create', [
				'csrf_token' => $_SESSION['csrf_token'],
				'error' => Session::getFlash('error'),
				'breadcrumbs' => $breadcrumbs
			], 'admin');

			return;
		}

		$slug = $this->generateSlug($_POST['slug'] ?? $name);

		$data = [
			'name' => $name,
			'slug' => $slug,
			'bio' => $_POST['bio'],
			'website' => trim($_POST['website'] ?? ''),
			'instagram' => trim($_POST['instagram'] ?? ''),
			'tiktok' => trim($_POST['tiktok'] ?? ''),
			'youtube' => trim($_POST['youtube'] ?? ''),
			'created_at' => date('Y-m-d H:i:s'),
		];

		$postId = $this->guestModel->adminCreate($data);
		if (!empty($_FILES['cover_image']['name'])) {
			//$coverImageId = $this->imageService->upload($_FILES['cover_image'], 'guest', $postId, $data['name'], true);
try{
	$coverImageId = $this->imageService->upload(
		$_FILES['cover_image'],
		'guest',
		$postId,
		$data['name'],
		true
	);
	$data['cover_image_id'] = $coverImageId;
}catch (\Exception $exception){
	var_dump($exception->getMessage());
			}

		}

		$this->auditLogService->logAudit([
			'user_id' => $_SESSION['user_id'] ?? null,
			'action_type' => AuditLogActionType::GUEST_CREATED,
			'entity_type' => 'guest',
			'entity_id' => $postId,
			'success' => 1,
			'payload' => [
				'name' => $data['name'],
				'slug' => $data['slug'],
				'has_cover_image' => !empty($_FILES['cover_image']['name']),
			],
		]);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'success' => true,
			'message' => 'Guest creato con successo!',
			'redirect' => '/admin/guests/edit/' . $postId,
			'id' => $postId,
		]);
		exit();
	}

	private function validateCsrfToken()
	{
		if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
			Session::setFlash('error', 'Errore di sicurezza: richiesta non valida (CSRF).');
			return false;
		}
		return true;
	}

	/**
	 * Form modifica
	 */
	public function edit($params)
	{
		$id = $params[0] ?? null;

		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID non valido.');
			header('Location: /admin/guests/all');
			exit();
		}
		$guests = $this->guestModel->find($id);
		$cover = $this->imageService->getPrimary('guest', $id, 'thumb');
		//var_dump($cover);
		if(!empty($cover)){
			$guests['immagine']= '/public_assets'.$cover['path'];
			$guests['cover_image_id'] = $cover['id'];
		}else{
			$guests['immagine']= '';
			$guests['cover_image_id'] = '';
		}


		if (!$guests) {
			Session::setFlash('error', 'Articolo non trovato.');
			header('Location: /admin/guests/all');
			exit();
		}

		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Guest', 'url' => URL_ROOT . '/admin/guests/all'],
			['label' => 'Modifica: ' . $guests['name'], 'url' => URL_ROOT . '/admin/guests/edit/' . $id],
		];

		$this->view('admin/guests/edit', [
			'guest' => $guests,
			'csrf_token' => $_SESSION['csrf_token'],
			'breadcrumbs' => $breadcrumbs,
		], 'admin');
	}

	/**
	 * Update articolo
	 */
	public function update($params)
	{
		if (!$this->validateCsrfToken()) {
			header('Content-Type: application/json; charset=utf-8');
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Token CSRF non valido.']);
			exit();
		}


		$id = $params[0] ?? null;
		if (!$id || !is_numeric($id)) {
			header('Content-Type: application/json; charset=utf-8');
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'ID non valido.']);
			exit();
		}


		$post = $this->guestModel->find($id);
		if (!$post) {
			header('Content-Type: application/json; charset=utf-8');
			http_response_code(404);
			echo json_encode(['success' => false, 'message' => 'Guest non trovato.']);
			exit();
		}

		$slug = $this->generateSlug($_POST['slug'] ?? $_POST['name']);
		$data = [
			'name' => $_POST['name'] ?? $post['name'],
			'slug' => $slug,
			'bio' => $_POST['bio'],
			'website' => trim($_POST['website'] ?? ''),
			'instagram' => trim($_POST['instagram'] ?? ''),
			'tiktok' => trim($_POST['tiktok'] ?? ''),
			'youtube' => trim($_POST['youtube'] ?? ''),
			'updated_at' => date('Y-m-d H:i:s'),
			'id'=>  $id
		];
		if (!empty($_FILES['cover_image']['name'])) {

			try{
				$coverImageId = $this->imageService->replacePrimary(
					$_FILES['cover_image'],
					'guest',
					$id,
					$data['name']
				);
				//$coverImageId = $this->imageService->upload($_FILES['cover_image'], 'Guest_post', $id, $data['name'], true);

				$data['cover_image_id'] = $coverImageId;
			}catch(\Exception $exception){
				var_dump($exception->getMessage());
			}

		}

		$this->guestModel->update($id, $data);
		$this->auditLogService->logAudit([
			'user_id' => $_SESSION['user_id'] ?? null,
			'action_type' => AuditLogActionType::GUEST_UPDATED,
			'entity_type' => 'guest',
			'entity_id' => (int) $id,
			'success' => 1,
			'payload' => [
				'name' => $data['name'],
				'slug' => $data['slug'],
				'has_cover_image' => !empty($_FILES['cover_image']['name']),
			],
		]);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'success' => true,
			'message' => 'Guest aggiornato con successo!',
			'id' => (int) $id,
		]);
		exit();
	}

	/**
	 * Delete (soft)
	 */
	public function delete($params)
	{
		header('Content-Type: application/json; charset=utf-8');

		$id = $params[0] ?? null;

		if (!$id || !is_numeric($id)) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'ID non valido.']);
			exit();
		}

		if (!$this->validateCsrfToken()) {
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Token CSRF non valido.']);
			exit();
		}

		if ($this->guestModel->delete($id)) {
			$this->auditLogService->logAudit([
				'user_id' => $_SESSION['user_id'] ?? null,
				'action_type' => AuditLogActionType::GUEST_DELETED,
				'entity_type' => 'guest',
				'entity_id' => (int) $id,
				'success' => 1,
				'payload' => [
					'id' => (int) $id,
				],
			]);
			echo json_encode(['success' => true, 'message' => 'Guest eliminato.']);
			exit();
		}

		http_response_code(500);
		echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione del guest.']);
		exit();
	}

	/**
	 * Slug generator
	 */
	private function generateSlug(string $text): string
	{
		$text = strtolower($text);
		$text = preg_replace('/[^a-z0-9]+/', '-', $text);
		return trim($text, '-');
	}
	public function search()
	{
		$q = $_GET['q'] ?? '';
		$data = $this->guestModel->search($q);

		echo json_encode($data);
	}

	public function createSoft()
	{
		header('Content-Type: application/json; charset=utf-8');

		$input = json_decode(file_get_contents("php://input"), true);

		if (!isset($input['name']) || trim($input['name']) === '') {
			echo json_encode(['error' => 'Nome richiesto']);
			return;
		}

		$name = trim($input['name']);

		$id = $this->guestModel->create($name);

		echo json_encode([
			'id' => $id,
			'name' => $name
		]);
		exit;
	}
}
