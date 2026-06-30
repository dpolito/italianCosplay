<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AdPositionService;
use Exception;

class AdPositionController extends Controller
{
	private AdPositionService $positionService;

	public function __construct()
	{
		$this->positionService = new AdPositionService();

		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
	}

	public function index(): void
	{
		$this->view('admin/ads/positions/index', [
			'positions' => $this->positionService->getAll(),
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function create(): void
	{
		$this->view('admin/ads/positions/form', [
			'position' => null,
			'prices' => [],
			'action' => '/admin/ads/positions/store',
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function store(): void
	{
		$this->guardCsrf('/admin/ads/positions/create');

		try {
			$this->positionService->create($this->payload());
			Session::setFlash('success', 'Posizione pubblicitaria creata.');
			header('Location: /admin/ads/positions');
			exit();
		} catch (Exception $e) {
			Session::setFlash('error', $e->getMessage());
			header('Location: /admin/ads/positions/create');
			exit();
		}
	}

	public function edit(array $params): void
	{
		$id = (int)($params[0] ?? 0);
		$position = $this->positionService->findById($id);

		if (!$position) {
			Session::setFlash('error', 'Posizione non trovata.');
			header('Location: /admin/ads/positions');
			exit();
		}

		$this->view('admin/ads/positions/form', [
			'position' => $position,
			'prices' => $this->positionService->getPrices($id),
			'action' => '/admin/ads/positions/update/' . $id,
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function update(array $params): void
	{
		$id = (int)($params[0] ?? 0);
		$this->guardCsrf('/admin/ads/positions/edit/' . $id);

		try {
			$this->positionService->update($id, $this->payload());
			Session::setFlash('success', 'Posizione pubblicitaria aggiornata.');
			header('Location: /admin/ads/positions');
			exit();
		} catch (Exception $e) {
			Session::setFlash('error', $e->getMessage());
			header('Location: /admin/ads/positions/edit/' . $id);
			exit();
		}
	}

	public function delete(array $params): void
	{
		$this->guardCsrf('/admin/ads/positions');
		$this->positionService->delete((int)($params[0] ?? 0));

		Session::setFlash('success', 'Posizione disattivata.');
		header('Location: /admin/ads/positions');
		exit();
	}

	private function payload(): array
	{
		return [
			'name' => trim($_POST['name'] ?? ''),
			'code' => trim($_POST['code'] ?? ''),
			'page' => trim($_POST['page'] ?? ''),
			'description' => trim($_POST['description'] ?? ''),
			'width' => (int)($_POST['width'] ?? 0),
			'height' => (int)($_POST['height'] ?? 0),
			'mobile_width' => (int)($_POST['mobile_width'] ?? 0),
			'mobile_height' => (int)($_POST['mobile_height'] ?? 0),
			'max_slots' => (int)($_POST['max_slots'] ?? 1),
			'rotation_type' => $_POST['rotation_type'] ?? 'random',
			'base_price' => (float)($_POST['base_price'] ?? 0),
			'currency' => 'EUR',
			'min_days' => (int)($_POST['min_days'] ?? 7),
			'max_days' => (int)($_POST['max_days'] ?? 60),
			'estimated_monthly_impressions' => (int)($_POST['estimated_monthly_impressions'] ?? 0),
			'average_ctr' => (float)($_POST['average_ctr'] ?? 0),
			'is_sponsored_rel_required' => isset($_POST['is_sponsored_rel_required']) ? 1 : 0,
			'is_active' => isset($_POST['is_active']) ? 1 : 0,
			'sort_order' => (int)($_POST['sort_order'] ?? 0),
			'prices' => [
				7 => $_POST['price_7'] ?? null,
				15 => $_POST['price_15'] ?? null,
				30 => $_POST['price_30'] ?? null,
				60 => $_POST['price_60'] ?? null,
			],
		];
	}

	private function guardCsrf(string $redirect): void
	{
		if (!$this->isValidCsrfToken()) {
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: ' . $redirect);
			exit();
		}
	}

	private function isValidCsrfToken(): bool
	{
		return !empty($_POST['csrf_token'])
			&& !empty($_SESSION['csrf_token'])
			&& hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
	}
}
