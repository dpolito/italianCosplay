<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Services\ImageService;
use function var_dump;

class AdminBlogController extends Controller
{
	private BlogPost $blogModel;
	private BlogCategory $blogCategoryModel;
	private ImageService $imageService;

	public function __construct()
	{
		$this->blogModel = new BlogPost();
		$this->blogCategoryModel = new BlogCategory();
		$this->imageService = new ImageService($this->blogModel->getDbConnection());
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

		Session::setFlash('success', 'Articolo creato con successo!');
		header('Location: /admin/blog/edit/' . $postId);
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
			header('Location: /admin/blog/all');
			exit();
		}


		$id = $params[0] ?? null;

		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID non valido.');
			header('Location: /admin/blog/all');
			exit();
		}


		$post = $this->blogModel->find($id);
		if (!$post) {
			Session::setFlash('error', 'Articolo non trovato.');
			header('Location: /admin/blog/all');
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
		Session::setFlash('success', 'Articolo aggiornato con successo!');

		header('Location: /admin/blog/edit/' . $id);
		exit();
	}

	/**
	 * Delete (soft)
	 */
	public function delete($params)
	{
		$id = $params[0] ?? null;

		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID non valido.');
			header('Location: /admin/blog/all');
			exit();
		}

		if (!$this->validateCsrfToken()) {
			header('Location: /admin/blog/all');
			exit();
		}

		$this->blogModel->delete($id);

		Session::setFlash('success', 'Articolo eliminato.');

		header('Location: /admin/blog/all');
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
