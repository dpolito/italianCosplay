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

	public function create()
	{
		$this->view('admin/blog-categories/create', [
			'csrf_token' => $_SESSION['csrf_token']
		], 'admin');
	}

	public function store()
	{
		if (!$this->validateCsrfToken()) {
			header('Location: /admin/blog-categories/create');
			exit();
		}

		$data = [
			'name' => trim($_POST['name']),
			'slug' => trim($_POST['slug']),
			'description' => $_POST['description'] ?? null,
			'seo_title' => $_POST['seo_title'] ?? null,
			'seo_description' => $_POST['seo_description'] ?? null,
		];

		$this->categoryModel->create($data);

		header('Location: /admin/blog-categories/all');
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
			header('Location: /admin/blog-categories/all');
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

		header('Location: /admin/blog-categories/edit/' . $id);
		exit();
	}

	public function delete($params)
	{
		$id = $params[0] ?? null;

		if ($id) {
			$this->categoryModel->delete($id);
		}

		header('Location: /admin/blog-categories/all');
		exit();
	}
}
