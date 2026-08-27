<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\BlogCategory;

class AdminBlogCategoryController extends Controller
{
	private BlogCategory $categoryModel;

	public function __construct()
	{
		$this->categoryModel = new BlogCategory();
	}

	public function all()
	{
		$categories = $this->categoryModel->all();

		$this->view('admin/blog-categories/all', [
			'categories' => $categories,
			'csrf_token' => $_SESSION['csrf_token']
		], 'admin');
	}

	public function data(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$categories = $this->categoryModel->all();
		$page = max((int) ($_GET['page'] ?? 1), 1);
		$perPage = (int) ($_GET['perPage'] ?? 25);
		if (!in_array($perPage, [10, 25, 50, 100], true)) {
			$perPage = 25;
		}
		$search = trim($_GET['search'] ?? '');
		$sort = $_GET['sort'] ?? 'created_at';
		$direction = strtolower($_GET['direction'] ?? 'desc');
		$allowedSorts = ['id', 'name', 'slug', 'seo_title', 'seo_description', 'created_at'];
		if (!in_array($sort, $allowedSorts, true)) {
			$sort = 'created_at';
		}
		if (!in_array($direction, ['asc', 'desc'], true)) {
			$direction = 'desc';
		}

		$categories = array_values(array_filter($categories, static function (array $category) use ($search): bool {
			if ($search === '') {
				return true;
			}
			return str_contains(
				strtolower(implode(' ', $category)),
				strtolower($search)
			);
		}));
		usort($categories, static function (array $left, array $right) use ($sort, $direction): int {
			$leftValue = $left[$sort] ?? '';
			$rightValue = $right[$sort] ?? '';
			$result = strcmp((string) $leftValue, (string) $rightValue);
			return $direction === 'asc' ? $result : -$result;
		});

		$total = count($categories);
		$pages = max((int) ceil($total / $perPage), 1);
		$page = min($page, $pages);
		$slice = array_slice($categories, ($page - 1) * $perPage, $perPage);

		$data = array_map(static function (array $category): array {
			return [
				'id' => $category['id'],
				'name' => $category['name'],
				'slug' => $category['slug'],
				'seo_title' => $category['seo_title'] ?? '',
				'seo_description' => $category['seo_description'] ?? '',
				'created_at' => $category['created_at'] ?? null,
				'_links' => [
					'edit' => '/admin/blog-categories/edit/' . $category['id'],
					'delete' => '/admin/blog-categories/delete/' . $category['id'],
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
		$category = $this->categoryModel->find($id);

		if (!$category) {
			echo json_encode(['success' => false, 'message' => 'Categoria non trovata']);
			exit();
		}

		echo json_encode([
			'success' => true,
			'data' => $category,
		]);
		exit();
	}

	public function create()
	{
		$this->view('admin/blog-categories/create', [
			'csrf_token' => $_SESSION['csrf_token']
		], 'admin');
	}

	public function store()
	{
		if (!$this->validateCsrfToken()) {
			header('Content-Type: application/json; charset=utf-8');
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Token CSRF non valido.']);
			exit();
		}

		$data = [
			'name' => trim($_POST['name']),
			'slug' => trim($_POST['slug']),
			'description' => $_POST['description'] ?? null,
			'seo_title' => $_POST['seo_title'] ?? null,
			'seo_description' => $_POST['seo_description'] ?? null,
		];

		$categoryId = $this->categoryModel->create($data);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'success' => true,
			'message' => 'Categoria creata con successo!',
			'id' => $categoryId,
		]);
		exit();
	}

	public function edit($params)
	{
		$id = $params[0] ?? null;

		$category = $this->categoryModel->find($id);

		if (!$category) {
			header('Location: /admin/blog-categories/all');
			exit();
		}

		$this->view('admin/blog-categories/edit', [
			'category' => $category,
			'csrf_token' => $_SESSION['csrf_token']
		], 'admin');
	}
	private function validateCsrfToken()
	{
		if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
			Session::setFlash('error', 'Errore di sicurezza: richiesta non valida (CSRF).');
			return false;
		}
		return true;
	}

	public function update($params)
	{
		if (!$this->validateCsrfToken()) {
			header('Content-Type: application/json; charset=utf-8');
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Token CSRF non valido.']);
			exit();
		}

		$id = $params[0] ?? null;

		$data = [
			'name' => trim($_POST['name']),
			'slug' => trim($_POST['slug']),
			'description' => $_POST['description'] ?? null,
			'seo_title' => $_POST['seo_title'] ?? null,
			'seo_description' => $_POST['seo_description'] ?? null,
		];

		$this->categoryModel->update($id, $data);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'success' => true,
			'message' => 'Categoria aggiornata con successo!',
			'id' => (int) $id,
		]);
		exit();
	}

	public function delete($params)
	{
		header('Content-Type: application/json; charset=utf-8');

		$id = $params[0] ?? null;

		if (!$id || !is_numeric($id)) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'ID non valido.']);
			exit();
		}

		if ($this->categoryModel->delete((int) $id)) {
			echo json_encode(['success' => true, 'message' => 'Categoria eliminata.']);
			exit();
		}

		http_response_code(500);
		echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione della categoria.']);
		exit();
	}
}
