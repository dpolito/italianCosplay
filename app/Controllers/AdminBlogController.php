<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Services\AuditLogService;
use App\Services\ImageService;
use App\Support\AuditLogActionType;
use function var_dump;

class AdminBlogController extends Controller
{
	private BlogPost $blogModel;
	private BlogCategory $blogCategoryModel;
	private ImageService $imageService;
	private AuditLogService $auditLogService;

	public function __construct()
	{
		$this->blogModel = new BlogPost();
		$this->blogCategoryModel = new BlogCategory();
		$this->imageService = new ImageService($this->blogModel->getDbConnection());
		$this->auditLogService = new AuditLogService();
	}

	/**
	 * Lista articoli blog
	 */
	public function all()
	{
		$posts = $this->blogModel->getAll();

		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Blog', 'url' => URL_ROOT . '/admin/blog/all'],
		];

		$this->view('admin/blog/all', [
			'posts' => $posts,
			'csrf_token' => $_SESSION['csrf_token'],
			'breadcrumbs' => $breadcrumbs
		], 'admin');
	}

	public function data(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$posts = $this->blogModel->getAll();

		$page = max((int) ($_GET['page'] ?? 1), 1);
		$perPage = (int) ($_GET['perPage'] ?? 25);
		if (!in_array($perPage, [10, 25, 50, 100], true)) {
			$perPage = 25;
		}

		$search = trim($_GET['search'] ?? '');
		$sort = $_GET['sort'] ?? 'created_at';
		$direction = strtolower($_GET['direction'] ?? 'desc');
		$allowedSorts = ['id', 'titolo', 'slug', 'status', 'views', 'created_at'];
		if (!in_array($sort, $allowedSorts, true)) {
			$sort = 'created_at';
		}
		if (!in_array($direction, ['asc', 'desc'], true)) {
			$direction = 'desc';
		}

		$posts = array_values(array_filter($posts, static function (array $post) use ($search): bool {
			if ($search === '') {
				return true;
			}

			$haystack = strtolower(
				implode(' ', [
					$post['id'] ?? '',
					$post['titolo'] ?? '',
					$post['slug'] ?? '',
					$post['status'] ?? '',
				])
			);

			return str_contains($haystack, strtolower($search));
		}));

		usort($posts, static function (array $left, array $right) use ($sort, $direction): int {
			$leftValue = $left[$sort] ?? '';
			$rightValue = $right[$sort] ?? '';
			$result = is_numeric($leftValue) && is_numeric($rightValue)
				? ((float) $leftValue <=> (float) $rightValue)
				: strcmp((string) $leftValue, (string) $rightValue);

			return $direction === 'asc' ? $result : -$result;
		});

		$total = count($posts);
		$pages = max((int) ceil($total / $perPage), 1);
		$page = min($page, $pages);
		$offset = ($page - 1) * $perPage;
		$slice = array_slice($posts, $offset, $perPage);

		$data = array_map(static function (array $post): array {
			return [
				'id' => $post['id'],
				'titolo' => $post['titolo'],
				'slug' => $post['slug'],
				'status' => $post['status'] ?? 'draft',
				'views' => (int) ($post['views'] ?? 0),
				'created_at' => $post['created_at'] ?? null,
				'_links' => [
					'view' => '/blog/' . $post['slug'],
					'edit' => '/admin/blog/edit/' . $post['id'],
					'delete' => '/admin/blog/delete/' . $post['id'],
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

	public function detail($params): void
	{
		header('Content-Type: application/json; charset=utf-8');
		$id = (int) ($params[0] ?? 0);
		$post = $this->blogModel->find($id);

		if (!$post) {
			echo json_encode(['success' => false, 'message' => 'Articolo non trovato']);
			exit();
		}

		echo json_encode([
			'success' => true,
			'data' => [
				'id' => $post['id'],
				'titolo' => $post['titolo'],
				'slug' => $post['slug'],
				'excerpt' => $post['excerpt'] ?? '',
				'status' => $post['status'] ?? 'draft',
				'views' => (int) ($post['views'] ?? 0),
				'created_at' => $post['created_at'] ?? null,
				'updated_at' => $post['updated_at'] ?? null,
				'categoria_id' => $post['categoria_id'] ?? null,
				'meta_title' => $post['meta_title'] ?? '',
				'meta_description' => $post['meta_description'] ?? '',
				'_links' => [
					'edit' => '/admin/blog/edit/' . $post['id'],
					'view' => '/blog/' . $post['slug'],
					'delete' => '/admin/blog/delete/' . $post['id'],
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
			['label' => 'Blog', 'url' => URL_ROOT . '/admin/blog/all'],
			['label' => 'Crea Articolo', 'url' => URL_ROOT . '/admin/blog/create'],
		];
		$categorie = $this->blogCategoryModel->all();

		$this->view('admin/blog/create', [
			'csrf_token' => $_SESSION['csrf_token'],
			'breadcrumbs' => $breadcrumbs,
			'categorie' => $categorie
		], 'admin');
	}

	/**
	 * Salvataggio articolo
	 */
	public function store()
	{
		if (!$this->validateCsrfToken()) {
			header('Location: ' . URL_ROOT . '/admin/blog/create');
			exit();
		}

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Location: ' . URL_ROOT . '/admin/blog/create');
			exit();
		}

		$titolo = trim($_POST['titolo'] ?? '');

		$errors = [];

		if (empty($titolo)) {
			$errors[] = 'Il titolo è obbligatorio.';
		}

		if (strlen($titolo) > 255) {
			$errors[] = 'Titolo troppo lungo.';
		}

		if (empty($_POST['contenuto'])) {
			$errors[] = 'Il contenuto è obbligatorio.';
		}

		if (!empty($errors)) {
			Session::setFlash('error', implode('<br>', $errors));

			$breadcrumbs = [
				['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
				['label' => 'Blog', 'url' => URL_ROOT . '/admin/blog/all'],
				['label' => 'Crea Articolo', 'url' => URL_ROOT . '/admin/blog/create'],
			];

			$this->view('admin/blog/create', [
				'csrf_token' => $_SESSION['csrf_token'],
				'error' => Session::getFlash('error'),
				'breadcrumbs' => $breadcrumbs
			], 'admin');

			return;
		}

		$slug = $this->generateSlug($_POST['slug'] ?? $titolo);

		$data = [
			'titolo' => $titolo,
			'slug' => $slug,
			'excerpt' => trim($_POST['excerpt'] ?? ''),
			'contenuto' => $_POST['contenuto'],
			'meta_title' => trim($_POST['meta_title'] ?? ''),
			'meta_description' => trim($_POST['meta_description'] ?? ''),
			'categoria_id' => (int)($_POST['categoria_id'] ?? null),
			'related_event_id' => (int)($_POST['related_event_id'] ?? null),
			'published_at' => ($_POST['published_at'] ?? null),
			'status' => $_POST['status'] ?? 'draft',
			'user_id' => $_SESSION['user_id']
		];

		$postId = $this->blogModel->create($data);
		if (!empty($_FILES['cover_image']['name'])) {
			$coverImageId = $this->imageService->upload($_FILES['cover_image'], 'blog_post', $postId, $data['titolo'], true);
			$data['cover_image_id'] = $coverImageId;
		}
		$this->auditLogService->logAudit([
			'user_id' => $_SESSION['user_id'] ?? null,
			'action_type' => AuditLogActionType::BLOG_POST_CREATED,
			'entity_type' => 'blog_post',
			'entity_id' => $postId,
			'success' => 1,
			'payload' => [
				'title' => $data['titolo'],
				'slug' => $data['slug'],
				'status' => $data['status'],
				'has_cover_image' => !empty($_FILES['cover_image']['name']),
			],
		]);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'success' => true,
			'message' => 'Articolo creato con successo!',
			'redirect' => '/admin/blog/edit/' . $postId,
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
			header('Location: /admin/blog/all');
			exit();
		}
		$categorie = $this->blogCategoryModel->all();
		$post = $this->blogModel->find($id);
		$post['published_at_date'] = !empty($post['published_at'])
			? date('Y-m-d', strtotime($post['published_at']))
			: null;
		$cover = $this->imageService->getPrimary('blog_post', $id);
		//var_dump($cover);
		if(!empty($cover)){
			$post['immagine']= '/public_assets'.$cover['path'];
			$post['cover_image_id'] = $cover['id'];
		}else{
			$post['immagine']= '';
			$post['cover_image_id'] = '';
		}


		if (!$post) {
			Session::setFlash('error', 'Articolo non trovato.');
			header('Location: /admin/blog/all');
			exit();
		}

		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Blog', 'url' => URL_ROOT . '/admin/blog/all'],
			['label' => 'Modifica: ' . $post['titolo'], 'url' => URL_ROOT . '/admin/blog/edit/' . $id],
		];

		$this->view('admin/blog/edit', [
			'post' => $post,
			'csrf_token' => $_SESSION['csrf_token'],
			'breadcrumbs' => $breadcrumbs,
			'categorie' => $categorie,
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


		$post = $this->blogModel->find($id);
		if (!$post) {
			header('Content-Type: application/json; charset=utf-8');
			http_response_code(404);
			echo json_encode(['success' => false, 'message' => 'Articolo non trovato.']);
			exit();
		}
		$slug = $this->generateSlug($_POST['slug'] ?? $_POST['titolo']);
		$data = [
			'titolo' => trim($_POST['titolo']),
			'slug' => $slug,
			'excerpt' => trim($_POST['excerpt'] ?? ''),
			'contenuto' => $_POST['contenuto'],
			'meta_title' => trim($_POST['meta_title'] ?? ''),
			'meta_description' => trim($_POST['meta_description'] ?? ''),
			'categoria_id' => (int)($_POST['categoria_id'] ?? null),
			'related_event_id' => (int)($_POST['related_event_id'] ?? null),
			'published_at' => ($_POST['published_at'] ?? null),
			'status' => $_POST['status'] ?? 'draft',
		];
		if (!empty($_FILES['cover_image']['name'])) {

			$coverImageId = $this->imageService->replacePrimary(
				$_FILES['cover_image'],
				'blog_post',
				$id,
				$data['titolo']
			);

			//$coverImageId = $this->imageService->upload($_FILES['cover_image'], 'blog_post', $id, $data['titolo'], true);

			$data['cover_image_id'] = $coverImageId;
		}

		$this->blogModel->update($id, $data);
		$this->auditLogService->logAudit([
			'user_id' => $_SESSION['user_id'] ?? null,
			'action_type' => AuditLogActionType::BLOG_POST_UPDATED,
			'entity_type' => 'blog_post',
			'entity_id' => (int) $id,
			'success' => 1,
			'payload' => [
				'title' => $data['titolo'],
				'slug' => $data['slug'],
				'status' => $data['status'],
				'has_cover_image' => !empty($_FILES['cover_image']['name']),
			],
		]);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'success' => true,
			'message' => 'Articolo aggiornato con successo!',
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

		$post = $this->blogModel->find((int) $id);
		if (!$post) {
			http_response_code(404);
			echo json_encode(['success' => false, 'message' => 'Articolo non trovato.']);
			exit();
		}

		if ($this->blogModel->delete((int) $id, isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null)) {
			$this->auditLogService->logAudit([
				'user_id' => $_SESSION['user_id'] ?? null,
				'action_type' => AuditLogActionType::BLOG_POST_DELETED,
				'entity_type' => 'blog_post',
				'entity_id' => (int) $id,
				'success' => 1,
				'payload' => [
					'title' => $post['titolo'] ?? null,
					'slug' => $post['slug'] ?? null,
				],
			]);
			echo json_encode(['success' => true, 'message' => 'Articolo eliminato.']);
			exit();
		}

		http_response_code(500);
		echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'articolo.']);
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
}
