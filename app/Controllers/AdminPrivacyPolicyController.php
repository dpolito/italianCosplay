<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\PrivacyPolicyService;
use Exception;

class AdminPrivacyPolicyController extends Controller
{
	private PrivacyPolicyService $privacyPolicyService;

	public function __construct()
	{
		$this->privacyPolicyService = new PrivacyPolicyService();
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
	}

	public function index(): void
	{
		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Privacy', 'url' => URL_ROOT . '/admin/privacy'],
		];

		$this->view('admin/privacy/index', [
			'breadcrumbs' => $breadcrumbs,
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function data(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$versions = $this->privacyPolicyService->getAllVersions();
		$page = max((int) ($_GET['page'] ?? 1), 1);
		$perPage = (int) ($_GET['perPage'] ?? 25);
		if (!in_array($perPage, [10, 25, 50, 100], true)) {
			$perPage = 25;
		}
		$search = trim((string) ($_GET['search'] ?? ''));
		$sort = (string) ($_GET['sort'] ?? 'version_number');
		$direction = strtolower((string) ($_GET['direction'] ?? 'desc'));
		$allowedSorts = ['id', 'version_number', 'title', 'published_at', 'is_active', 'created_at'];
		if (!in_array($sort, $allowedSorts, true)) {
			$sort = 'version_number';
		}
		if (!in_array($direction, ['asc', 'desc'], true)) {
			$direction = 'desc';
		}

		$versions = array_values(array_filter($versions, static function (array $version) use ($search): bool {
			if ($search === '') {
				return true;
			}

			return str_contains(
				strtolower(implode(' ', $version)),
				strtolower($search)
			);
		}));

		usort($versions, static function (array $left, array $right) use ($sort, $direction): int {
			$leftValue = $left[$sort] ?? '';
			$rightValue = $right[$sort] ?? '';
			if ($sort === 'is_active' || $sort === 'version_number' || $sort === 'id') {
				$result = (int) $leftValue <=> (int) $rightValue;
			} else {
				$result = strcmp((string) $leftValue, (string) $rightValue);
			}

			return $direction === 'asc' ? $result : -$result;
		});

		$total = count($versions);
		$pages = max((int) ceil($total / $perPage), 1);
		$page = min($page, $pages);
		$slice = array_slice($versions, ($page - 1) * $perPage, $perPage);

		$data = array_map(static function (array $version): array {
			return [
				'id' => $version['id'],
				'version_number' => (int) $version['version_number'],
				'title' => $version['title'],
				'published_at' => $version['published_at'] ?? null,
				'is_active' => (int) ($version['is_active'] ?? 0),
				'created_at' => $version['created_at'] ?? null,
				'_links' => [
					'edit' => '/admin/privacy/edit/' . $version['id'],
				],
			];
		}, $slice);

		echo json_encode([
			'success' => true,
			'data' => $data,
			'meta' => [
				'page' => $page,
				'pages' => $pages,
				'total' => $total,
			],
		]);
		exit();
	}

	public function create(): void
	{
		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Privacy', 'url' => URL_ROOT . '/admin/privacy'],
			['label' => 'Nuova versione', 'url' => URL_ROOT . '/admin/privacy/create'],
		];

		$this->view('admin/privacy/create', [
			'breadcrumbs' => $breadcrumbs,
			'csrf_token' => $_SESSION['csrf_token'],
			'nextVersionNumber' => $this->getNextVersionNumber(),
		], 'admin');
	}

	public function store(): void
	{
		$this->guardCsrf('/admin/privacy/create');

		try {
			$data = $this->buildPayload();
			$this->privacyPolicyService->createVersion($data);
			Session::setFlash('success', 'Versione privacy creata.');
			header('Location: /admin/privacy');
			exit();
		} catch (Exception $e) {
			Session::setFlash('error', $e->getMessage());
			header('Location: /admin/privacy/create');
			exit();
		}
	}

	public function edit(array $params): void
	{
		$id = (int) ($params[0] ?? 0);
		$version = $this->privacyPolicyService->findById($id);
		if (!$version) {
			Session::setFlash('error', 'Versione privacy non trovata.');
			header('Location: /admin/privacy');
			exit();
		}

		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Privacy', 'url' => URL_ROOT . '/admin/privacy'],
			['label' => 'Modifica versione', 'url' => URL_ROOT . '/admin/privacy/edit/' . $id],
		];

		$this->view('admin/privacy/edit', [
			'breadcrumbs' => $breadcrumbs,
			'version' => $version,
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function update(array $params): void
	{
		$id = (int) ($params[0] ?? 0);
		$this->guardCsrf('/admin/privacy/edit/' . $id);

		try {
			$data = $this->buildPayload();
			$this->privacyPolicyService->updateVersion($id, $data);
			if (!empty($data['is_active'])) {
				$this->privacyPolicyService->activateVersion($id);
			}
			Session::setFlash('success', 'Versione privacy aggiornata.');
			header('Location: /admin/privacy/edit/' . $id);
			exit();
		} catch (Exception $e) {
			Session::setFlash('error', $e->getMessage());
			header('Location: /admin/privacy/edit/' . $id);
			exit();
		}
	}

	public function activate(array $params): void
	{
		$id = (int) ($params[0] ?? 0);
		$this->guardCsrf('/admin/privacy');

		try {
			$this->privacyPolicyService->activateVersion($id);
			Session::setFlash('success', 'Versione privacy attivata.');
		} catch (Exception $e) {
			Session::setFlash('error', $e->getMessage());
		}

		header('Location: /admin/privacy');
		exit();
	}

	private function guardCsrf(string $redirect): void
	{
		if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', (string) $_POST['csrf_token'])) {
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: ' . $redirect);
			exit();
		}
	}

	private function buildPayload(): array
	{
		$versionNumber = (int) ($_POST['version_number'] ?? 0);
		$title = trim((string) ($_POST['title'] ?? ''));
		$content = trim((string) ($_POST['content'] ?? ''));
		$isActive = !empty($_POST['is_active']);
		$publishedAt = trim((string) ($_POST['published_at'] ?? ''));

		if ($versionNumber < 1) {
			throw new Exception('Inserisci un numero versione valido.');
		}
		if ($title === '') {
			throw new Exception('Inserisci un titolo valido.');
		}
		if ($content === '') {
			throw new Exception('Inserisci il testo della privacy.');
		}

		return [
			'version_number' => $versionNumber,
			'title' => $title,
			'content' => $content,
			'is_active' => $isActive,
			'published_at' => $publishedAt !== '' ? $publishedAt : null,
			'updated_by' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
			'created_by' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
		];
	}

	private function getNextVersionNumber(): int
	{
		$versions = $this->privacyPolicyService->getAllVersions();
		if (!$versions) {
			return 1;
		}

		$max = 0;
		foreach ($versions as $version) {
			$max = max($max, (int) ($version['version_number'] ?? 0));
		}

		return $max + 1;
	}
}
