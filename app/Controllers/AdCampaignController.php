<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AdCampaignService;
use App\Services\AdPaymentService;
use App\Services\AdPricingService;
use Exception;
use function var_dump;

class AdCampaignController extends Controller
{
	private AdCampaignService $campaignService;
	private AdPaymentService $paymentService;
	private AdPricingService $pricingService;

	public function __construct()
	{
		$this->campaignService = new AdCampaignService();
		$this->paymentService = new AdPaymentService();
		$this->pricingService = new AdPricingService();

		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
	}

	public function index(): void
	{
		$userId = (int)$_SESSION['user_id'];

		$this->view('dashboard/ads/campaigns/index', [
			'campaigns' => $this->campaignService->getByUser($userId),
			'csrf_token' => $_SESSION['csrf_token'],
		], 'dashboard');
	}

	public function create(): void
	{
		$this->view('dashboard/ads/campaigns/create', [
			'positions' => $this->campaignService->getAvailablePositions(),
			'csrf_token' => $_SESSION['csrf_token'],
		], 'dashboard');
	}

	public function store(): void
	{
		if (!$this->isValidCsrfToken()) {
			Session::setFlash('error', 'Sessione scaduta. Ricarica la pagina e riprova.');
			header('Location: /dashboard/ads/campaigns/create');
			exit();
		}

		try {
			$imagePath = $this->handleBannerUpload($_FILES['banner'] ?? null);

			$result = $this->campaignService->createFromCheckout([
				'user_id' => (int)$_SESSION['user_id'],
				'position_id' => (int)($_POST['position_id'] ?? 0),
				'title' => trim($_POST['title'] ?? ''),
				'description' => trim($_POST['description'] ?? ''),
				'image_path' => $imagePath,
				'target_url' => trim($_POST['target_url'] ?? ''),
				'alt_text' => trim($_POST['alt_text'] ?? ''),
				'sponsor_name' => trim($_POST['sponsor_name'] ?? ''),
				'facebook_url' => trim($_POST['facebook_url'] ?? ''),
				'instagram_url' => trim($_POST['instagram_url'] ?? ''),
				'tiktok_url' => trim($_POST['tiktok_url'] ?? ''),
				'creative_type' => $imagePath ? 'image' : 'sponsor_card',
				'start_date' => $_POST['start_date'] ?? '',
				'end_date' => $_POST['end_date'] ?? '',
				'duration_days' => (int)($_POST['duration_days'] ?? 0),
				'notes' => trim($_POST['notes'] ?? ''),
			]);

			header('Location: /dashboard/ads/campaigns/' . $result['campaign_id'] . '/review');
			exit();
		} catch (Exception $e) {
			Session::setFlash('error', $e->getMessage());
			header('Location: /dashboard/ads/campaigns/create');
			exit();
		}
	}

	public function review(array $params): void
	{
		$campaign = $this->requireOwnedCampaign((int)($params[0] ?? 0));

		$this->view('dashboard/ads/campaigns/review', [
			'campaign' => $campaign,
			'csrf_token' => $_SESSION['csrf_token'],
		], 'dashboard');
	}

	public function checkout(array $params): void
	{
		if (!$this->isValidCsrfToken()) {
			Session::setFlash('error', 'Sessione scaduta. Ricarica il riepilogo e riprova.');
			header('Location: /dashboard/ads/campaigns');
			exit();
		}

		$campaign = $this->requireOwnedCampaign((int)($params[0] ?? 0));

		try {
			$redirectUrl = $this->paymentService->createCheckoutSession($campaign);
			header('Location: ' . $redirectUrl);
			exit();
		} catch (Exception $e) {
			Session::setFlash('error', $e->getMessage());
			header('Location: /dashboard/ads/campaigns/' . $campaign['id'] . '/review');
			exit();
		}
	}

	public function cancel(array $params): void
	{
		if (!$this->isValidCsrfToken()) {
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: /dashboard/ads/campaigns/index');
			exit();
		}

		$campaign = $this->requireOwnedCampaign((int)($params[0] ?? 0));
		$this->campaignService->cancel((int)$campaign['id']);

		Session::setFlash('success', 'Campagna annullata.');
		header('Location: /dashboard/ads/campaigns/index');
		exit();
	}

	public function estimatePrice(): void
	{
		header('Content-Type: application/json');

		try {
			$positionId = (int)($_POST['position_id'] ?? 0);
			$start = $_POST['start_date'] ?? '';
			$duration = (int)($_POST['duration_days'] ?? 0);
			$end = $duration > 0 ? $this->pricingService->buildEndDate($start, $duration) : ($_POST['end_date'] ?? '');
			$price = $this->pricingService->calculate($positionId, $start, $end);

			echo json_encode(['success' => true, 'price' => $price, 'end_date' => $end]);
		} catch (Exception $e) {
			echo json_encode(['success' => false, 'message' => $e->getMessage()]);
		}

		exit();
	}

	private function requireOwnedCampaign(int $campaignId): array
	{
		$campaign = $this->campaignService->findById($campaignId);
		$userId = (int)$_SESSION['user_id'];

		if (!$campaign || (int)$campaign['user_id'] !== $userId) {
			Session::setFlash('error', 'Campagna non trovata.');
			header('Location: /dashboard/ads/campaigns');
			exit();
		}

		return $campaign;
	}

	private function handleBannerUpload(?array $file): ?string
	{
		if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
			return null;
		}

		if ($file['error'] !== UPLOAD_ERR_OK) {
			throw new Exception('Upload del banner non riuscito.');
		}

		$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
		$mime = mime_content_type($file['tmp_name']);
		if (!isset($allowed[$mime])) {
			throw new Exception('Formato banner non valido. Usa JPG, PNG o WebP.');
		}

		if ((int)$file['size'] > 5 * 1024 * 1024) {
			throw new Exception('Il banner non può superare 5MB.');
		}

		$dir = APP_ROOT . '/public_assets/uploads/banners/';
		if (!is_dir($dir)) {
			mkdir($dir, 0775, true);
		}

		$name = 'banner_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
		$path = $dir . $name;

		if (!move_uploaded_file($file['tmp_name'], $path)) {
			throw new Exception('Impossibile salvare il banner.');
		}

		return '/public_assets/uploads/banners/' . $name;
	}

	private function isValidCsrfToken(): bool
	{
		return !empty($_POST['csrf_token'])
			&& !empty($_SESSION['csrf_token'])
			&& hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
	}
}
