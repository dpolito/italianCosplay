<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Services\AdBannerService;

class AdBannerController extends Controller
{
	private AdBannerService $bannerService;
	private User $userModel;

	public function __construct()
	{
		$this->bannerService = new AdBannerService();
		$this->userModel = new User();

		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
	}

	/**
	 * LISTA BANNER UTENTE
	 */
	public function index()
	{
		$this->requireFeature('enable_advertising', 'Advertising temporaneamente disattivato.');
		$userId = $_SESSION['user_id'];

		$banners = $this->bannerService->getByUser($userId);

		$this->view('dashboard/ads/banners/index', [
			'user' => $this->userModel->find((int)$userId),
			'banners' => $banners
		], 'dashboard');
	}

	/**
	 * FORM CREAZIONE BANNER
	 */
	public function create()
	{
		$this->requireFeature('enable_advertising', 'Advertising temporaneamente disattivato.');
		$this->view('dashboard/ads/banners/create', [
			'user' => $this->userModel->find((int)$_SESSION['user_id']),
			'csrf_token' => $_SESSION['csrf_token']
		], 'dashboard');
	}

	/**
	 * SALVATAGGIO BANNER (UPLOAD + DB)
	 */
	public function store()
	{
		$this->requireFeature('enable_advertising', 'Advertising temporaneamente disattivato.');


		if (!$this->isValidCsrfToken()) {
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: /dashboard/ads/banners/create');
			exit();
		}

		$userId = $_SESSION['user_id'];

		$title      = trim($_POST['title'] ?? '');
		$targetUrl  = trim($_POST['target_url'] ?? '');
		$type       = 'sponsor';
		$image      = $_FILES['image'] ?? null;
		$mobileImage = $_FILES['mobile_image'] ?? null;

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
			$bannerData = [
				'user_id'    => $userId,
				'title'      => $title,
				'target_url' => $targetUrl,
				'type'       => $type,
				'image'      => $image,
			];
			if ($mobileImage && ($mobileImage['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
				$bannerData['mobile_image'] = $mobileImage;
			}

			$bannerId = $this->bannerService->create([
				...$bannerData,
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
	public function edit(array $params): void
	{
		$this->requireFeature('enable_advertising', 'Advertising temporaneamente disattivato.');
		$id = (int)($params[0] ?? 0);
		$userId = $_SESSION['user_id'];

		$banner = $this->bannerService->findById($id);

		if (!$banner || (int)$banner['user_id'] !== (int)$userId) {
			Session::setFlash('error', 'Banner non trovato.');
			header('Location: /dashboard/ads/banners');
			exit();
		}

		$this->view('dashboard/ads/banners/edit', [
			'user' => $this->userModel->find((int)$userId),
			'banner' => $banner,
			'csrf_token' => $_SESSION['csrf_token']
		], 'dashboard');
	}

	/**
	 * UPDATE BANNER
	 */
	public function update(array $params): void
	{
		$this->requireFeature('enable_advertising', 'Advertising temporaneamente disattivato.');
		$id = (int)($params[0] ?? 0);


		if (!$this->isValidCsrfToken()) {
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: /dashboard/ads/banners');
			exit();
		}

		$userId = $_SESSION['user_id'];

		$banner = $this->bannerService->findById($id);

		if (!$banner || (int)$banner['user_id'] !== (int)$userId) {
			Session::setFlash('error', 'Non autorizzato.');
			header('Location: /dashboard/ads/banners');
			exit();
		}

		$data = [
			'title'      => trim($_POST['title'] ?? ''),
			'target_url' => trim($_POST['target_url'] ?? ''),
			'type'       => 'sponsor'
		];

		$image = $_FILES['image'] ?? null;
		$mobileImage = $_FILES['mobile_image'] ?? null;

		if ($image && $image['error'] === UPLOAD_ERR_OK) {
			$data['image'] = $image;
		}
		if ($mobileImage && ($mobileImage['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
			$data['mobile_image'] = $mobileImage;
		}

		$this->bannerService->update($id, $data);

		Session::setFlash('success', 'Banner aggiornato.');

		header('Location: /dashboard/ads/banners');
		exit();
	}

	/**
	 * DELETE LOGICO BANNER
	 */
	public function delete(array $params): void
	{
		$this->requireFeature('enable_advertising', 'Advertising temporaneamente disattivato.');
		$id = (int)($params[0] ?? 0);
		$userId = $_SESSION['user_id'];

		$banner = $this->bannerService->findById($id);

		if (!$banner || (int)$banner['user_id'] !== (int)$userId) {
			Session::setFlash('error', 'Non autorizzato.');
			header('Location: /dashboard/ads/banners');
			exit();
		}

		try {
			$this->bannerService->delete($id);
			Session::setFlash('success', 'Banner eliminato.');
		} catch (\Exception $e) {
			Session::setFlash('error', $e->getMessage());
		}

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
			&& hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
	}
}
