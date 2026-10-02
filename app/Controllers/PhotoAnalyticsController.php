<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\PhotoAnalyticsService;
use Throwable;

final class PhotoAnalyticsController extends Controller
{
	private PhotoAnalyticsService $analytics;

	public function __construct()
	{
		$this->analytics = new PhotoAnalyticsService();
	}

	public function track(): void
	{
		if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
			$this->json(false, 405);
		}
		$payload = $_POST;
		$contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
		if (str_contains($contentType, 'application/json')) {
			try {
				$decoded = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
				$payload = is_array($decoded) ? $decoded : [];
			} catch (Throwable) {
				$payload = [];
			}
		}
		$this->json($this->analytics->trackClick($payload), 200);
	}

	public function admin(): void
	{
		$days = (int) ($_GET['days'] ?? 30);
		if (!in_array($days, [1, 7, 30], true)) {
			$days = 30;
		}
		$sort = (string) ($_GET['sort'] ?? 'gallery_views');
		if (!in_array($sort, ['gallery_views', 'photo_views', 'photos'], true)) {
			$sort = 'gallery_views';
		}
		$this->view('admin/photos/analytics', [
			'days' => $days,
			'sort' => $sort,
			'report' => $this->analytics->dashboard($days, $sort),
			'pageTitle' => 'Statistiche foto',
		], 'admin');
	}

	private function json(bool $success, int $status): void
	{
		http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['success' => $success]);
		exit();
	}
}
