<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AdBannerService;

class AdBannerController extends Controller
{
	private AdBannerService $bannerService;

	public function __construct()
	{
		$this->bannerService = new AdBannerService();

		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
	}

	/**
	 * LISTA BANNER UTENTE
	 */
	public function index()
	{


		$userId = $_SESSION['user_id'];

		$banners = $this->bannerService->getByUser($userId);

		$this->view('dashboard/ads/banners/index', [
			'banners' => $banners
		], 'dashboard');
	}

	/**
	 * FORM CREAZIONE BANNER
	 */
	public function create()
	{


		$this->view('dashboard/ads/banners/create', [
			'csrf_token' => $_SESSION['csrf_token']
		], 'dashboard');
	}

	/**
	 * SALVATAGGIO BANNER (UPLOAD + DB)
	 */
	public function store()
	{


		if (!$this->isValidCsrfToken()) {
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: /dashboard/ads/banners/create');
			exit();
		}

		$userId = $_SESSION['user_id'];

		$title      = trim($_POST['title'] ?? '');
		$targetUrl  = trim($_POST['target_url'] ?? '');
		$type       = $_POST['type'] ?? 'sponsor';
		$image      = $_FILES['image'] ?? null;

		$errors = [];

		if (!$title) {
			$errors[] = 'Titolo obbligatorio.';
		}

		if (!$targetUrl || !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
			$errors[] = 'URL non valido.';
		}

		if (!$image || $image['error'] !== UPLOAD_ERR_OK) {
			$errors[] = 'Immagine obbligatoria.';
		}

		if (!empty($errors)) {
			Session::setFlash('error', implode(' ', $errors));
			header('Location: /dashboard/ads/banners/create');
			exit();
		}

		try {
			$bannerId = $this->bannerService->create([
				'user_id'    => $userId,
				'title'      => $title,
				'target_url' => $targetUrl,
				'type'       => $type,
				'image'      => $image
			]);

			Session::setFlash('success', 'Banner creato con successo.');

			header("Location: /dashboard/ads/banners");
			exit();

		} catch (\Exception $e) {
			Session::setFlash('error', $e->getMessage());

			header('Location: /dashboard/ads/banners/create');
			exit();
		}
	}

	/**
	 * MODIFICA BANNER
	 */
	public function edit($id)
	{


		$userId = $_SESSION['user_id'];

		$banner = $this->bannerService->findById((int)$id);

		if (!$banner || $banner['user_id'] !== $userId) {
			Session::setFlash('error', 'Banner non trovato.');
			header('Location: /dashboard/ads/banners');
			exit();
		}

		$this->view('dashboard/ads/banners/edit', [
			'banner' => $banner,
			'csrf_token' => $_SESSION['csrf_token']
		], 'dashboard');
	}

	/**
	 * UPDATE BANNER
	 */
	public function update($id)
	{


		if (!$this->isValidCsrfToken()) {
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: /dashboard/ads/banners');
			exit();
		}

		$userId = $_SESSION['user_id'];

		$banner = $this->bannerService->findById((int)$id);

		if (!$banner || $banner['user_id'] !== $userId) {
			Session::setFlash('error', 'Non autorizzato.');
			header('Location: /dashboard/ads/banners');
			exit();
		}

		$data = [
			'title'      => trim($_POST['title'] ?? ''),
			'target_url' => trim($_POST['target_url'] ?? ''),
			'type'       => $_POST['type'] ?? 'sponsor'
		];

		$image = $_FILES['image'] ?? null;

		if ($image && $image['error'] === UPLOAD_ERR_OK) {
			$data['image'] = $image;
		}

		$this->bannerService->update((int)$id, $data);

		Session::setFlash('success', 'Banner aggiornato.');

		header('Location: /dashboard/ads/banners');
		exit();
	}

	/**
	 * DELETE LOGICO BANNER
	 */
	public function delete($id)
	{


		$userId = $_SESSION['user_id'];

		$banner = $this->bannerService->findById((int)$id);

		if (!$banner || $banner['user_id'] !== $userId) {
			Session::setFlash('error', 'Non autorizzato.');
			header('Location: /dashboard/ads/banners');
			exit();
		}

		$this->bannerService->delete((int)$id);

		Session::setFlash('success', 'Banner eliminato.');

		header('Location: /dashboard/ads/banners');
		exit();
	}

	/**
	 * CSRF VALIDATION
	 */
	private function isValidCsrfToken(): bool
	{
		return !empty($_POST['csrf_token'])
			&& !empty($_SESSION['csrf_token'])
			&& hash_equals($_SESSION['csrf_token'], $_SESSION['csrf_token']);
	}
}
