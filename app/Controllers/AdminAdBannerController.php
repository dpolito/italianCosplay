<?php

namespace App\Controllers;

use App\Core\Session;
use App\Models\User;
use App\Services\AdBannerService;
use Exception;

class AdminAdBannerController extends AdminAdsController
{
	private AdBannerService $bannerService;
	private User $userModel;

	public function __construct()
	{
		$this->bannerService = new AdBannerService();
		$this->userModel = new User();
		$this->ensureCsrfToken();
	}

	public function edit(array $params): void
	{
		$banner = $this->bannerService->findById((int)($params[0] ?? 0));
		if (!$banner) {
			Session::setFlash('error', 'Banner non trovato.');
			header('Location: /admin/ads/campaigns');
			exit();
		}

		$this->view('admin/ads/banners/edit', [
			'banner' => $banner,
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function update(array $params): void
	{
		$bannerId = (int)($params[0] ?? 0);

		if (!$this->csrfIsValid()) {
			$this->flashAndRedirect('error', 'Token CSRF non valido.', '/admin/ads/banners/edit/' . $bannerId);
		}

		try {
			$data = [
				'title' => trim($_POST['title'] ?? ''),
				'target_url' => trim($_POST['target_url'] ?? ''),
				'type' => 'sponsor',
			];

			$image = $_FILES['image'] ?? null;
			$mobileImage = $_FILES['mobile_image'] ?? null;
			$imageError = $image['error'] ?? UPLOAD_ERR_NO_FILE;
			if ($image && $imageError === UPLOAD_ERR_OK && !is_uploaded_file($image['tmp_name'])) {
				throw new Exception('Il file immagine non risulta caricato correttamente dal server.');
			}
			if ($image && $imageError !== UPLOAD_ERR_NO_FILE && $imageError !== UPLOAD_ERR_OK) {
				throw new Exception(sprintf(
					'Upload immagine non riuscito (codice %d). Usa JPG, PNG o WebP entro 5MB.',
					(int)$imageError
				));
			}

			if ($image && $imageError === UPLOAD_ERR_OK) {
				$data['image'] = $image;
			}
			if ($mobileImage && ($mobileImage['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
				$data['mobile_image'] = $mobileImage;
			}

			$this->bannerService->update($bannerId, $data);
			Session::setFlash('success', 'Banner aggiornato.');
		} catch (Exception $e) {
			Session::setFlash('error', $e->getMessage());
			header('Location: /admin/ads/banners/' . $bannerId . '/edit');
			exit();
		}

		header('Location: /admin/ads/banners/' . $bannerId . '/edit');
		exit();
	}
}
