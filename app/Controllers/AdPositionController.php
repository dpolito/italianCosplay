<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AdPositionService;
use Exception;

class AdPositionController extends AdminAdsController
{
	private AdPositionService $positionService;

	public function __construct()
	{
		$this->positionService = new AdPositionService();
		$this->ensureCsrfToken();
	}

	public function index(): void
	{
		$this->view('admin/ads/positions/index', [
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function data(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$positions = $this->positionService->getAll();
		$page = max((int)($_GET['page'] ?? 1), 1);
		$perPage = (int)($_GET['perPage'] ?? 25);
		if (!in_array($perPage, [10, 25, 50, 100], true)) {
			$perPage = 25;
		}
		$search = trim($_GET['search'] ?? '');
		$sort = $_GET['sort'] ?? 'name';
		$direction = strtolower($_GET['direction'] ?? 'asc');
		$allowedSorts = ['id', 'name', 'code', 'page', 'width', 'height', 'max_slots', 'base_price', 'sort_order'];
		if (!in_array($sort, $allowedSorts, true)) {
			$sort = 'name';
		}
		if (!in_array($direction, ['asc', 'desc'], true)) {
			$direction = 'asc';
		}

		$positions = array_values(array_filter($positions, static function (array $position) use ($search): bool {
			if ($search === '') {
				return true;
			}
			return str_contains(strtolower(implode(' ', $position)), strtolower($search));
		}));

		usort($positions, static function (array $left, array $right) use ($sort, $direction): int {
			$leftValue = $left[$sort] ?? '';
			$rightValue = $right[$sort] ?? '';
			$result = strcmp((string)$leftValue, (string)$rightValue);
			return $direction === 'asc' ? $result : -$result;
		});

		$total = count($positions);
		$pages = max((int)ceil($total / $perPage), 1);
		$page = min($page, $pages);
		$slice = array_slice($positions, ($page - 1) * $perPage, $perPage);

		$data = array_map(static function (array $position): array {
			return [
				'id' => $position['id'],
				'name' => $position['name'] ?? '',
				'code' => $position['code'] ?? '',
				'page' => $position['page'] ?? '',
				'width' => $position['width'] ?? 0,
				'height' => $position['height'] ?? 0,
				'mobile_width' => $position['mobile_width'] ?? 0,
				'mobile_height' => $position['mobile_height'] ?? 0,
				'max_slots' => $position['max_slots'] ?? 1,
				'base_price' => $position['base_price'] ?? 0,
				'rotation_type' => $position['rotation_type'] ?? 'random',
				'is_active' => $position['is_active'] ?? 0,
				'_links' => [
					'view' => '/admin/ads/positions/edit/' . $position['id'],
					'edit' => '/admin/ads/positions/edit/' . $position['id'],
					'delete' => '/admin/ads/positions/delete/' . $position['id'],
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

	public function detail(array $params): void
	{
		$position = $this->positionService->findById((int)($params[0] ?? 0));
		if (!$position) {
			$this->jsonResponse(false, 'Posizione non trovata.', [], 404);
		}

		$this->jsonResponse(true, '', [
			'id' => $position['id'],
			'name' => $position['name'] ?? '',
			'code' => $position['code'] ?? '',
			'page' => $position['page'] ?? '',
			'description' => $position['description'] ?? '',
			'width' => $position['width'] ?? 0,
			'height' => $position['height'] ?? 0,
			'mobile_width' => $position['mobile_width'] ?? 0,
			'mobile_height' => $position['mobile_height'] ?? 0,
			'max_slots' => $position['max_slots'] ?? 1,
			'base_price' => $position['base_price'] ?? 0,
			'rotation_type' => $position['rotation_type'] ?? 'random',
			'is_active' => $position['is_active'] ?? 0,
			'prices' => $this->positionService->getPrices((int)$position['id']),
			'_links' => [
				'edit' => '/admin/ads/positions/edit/' . $position['id'],
			],
		]);
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
		$this->guardCsrfOrJson('/admin/ads/positions');
		$this->positionService->delete((int)($params[0] ?? 0));

		if ($this->isJsonRequest()) {
			$this->jsonResponse(true, 'Posizione disattivata.');
		}

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

	private function guardCsrfOrJson(string $redirect): void
	{
		if ($this->csrfIsValid()) {
			return;
		}

		if ($this->isJsonRequest()) {
			$this->jsonResponse(false, 'Token CSRF non valido.', [], 403);
		}

		$this->flashAndRedirect('error', 'Token CSRF non valido.', $redirect);
	}
}
