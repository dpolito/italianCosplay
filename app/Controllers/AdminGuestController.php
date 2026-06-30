<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Guest;
use App\Services\ImageService;
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

	public function __construct()
	{
		$this->guestModel = new Guest();
		$this->imageService = new ImageService($this->guestModel->getDbConnection());
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

		Session::setFlash('success', 'Guest creato con successo!');
		header('Location: /admin/guests/edit/' . $postId);
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
			header('Location: /admin/guests/all');
			exit();
		}


		$id = $params[0] ?? null;
		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID non valido.');
			header('Location: /admin/guests/all');
			exit();
		}


		$post = $this->guestModel->find($id);
		if (!$post) {
			Session::setFlash('error', 'Guest non trovato.');
			header('Location: /admin/guests/all');
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
		Session::setFlash('success', 'Articolo aggiornato con successo!');

		header('Location: /admin/guests/edit/' . $id);
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
			header('Location: /admin/guests/all');
			exit();
		}

		if (!$this->validateCsrfToken()) {
			header('Location: /admin/guests/all');
			exit();
		}

		$this->guestModel->delete($id);

		Session::setFlash('success', 'Articolo eliminato.');

		header('Location: /admin/guests/all');
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
