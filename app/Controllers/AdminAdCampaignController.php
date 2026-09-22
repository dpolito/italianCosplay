<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AdCampaignService;
use App\Services\AdPaymentService;
use App\Services\AdStatsService;
use App\Services\AuditLogService;
use Exception;

class AdminAdCampaignController extends AdminAdsController
{
	private AdCampaignService $campaignService;
	private AdPaymentService $paymentService;
	private AdStatsService $statsService;
	private AuditLogService $auditLogService;

	public function __construct()
	{
		$this->campaignService = new AdCampaignService();
		$this->paymentService = new AdPaymentService();
		$this->statsService = new AdStatsService();
		$this->auditLogService = new AuditLogService();
		$this->ensureCsrfToken();
	}

	public function index(): void
	{
		$this->view('admin/ads/campaigns/index', [
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function data(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$campaigns = $this->campaignService->getAllForAdmin();
		foreach ($campaigns as &$campaign) {
			$impressions = (int)($campaign['impressions'] ?? 0);
			$clicks = (int)($campaign['clicks'] ?? 0);
			$campaign['ctr'] = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
		}
		unset($campaign);
		$page = max((int)($_GET['page'] ?? 1), 1);
		$perPage = (int)($_GET['perPage'] ?? 25);
		if (!in_array($perPage, [10, 25, 50, 100], true)) {
			$perPage = 25;
		}
		$search = trim($_GET['search'] ?? '');
		$sort = $_GET['sort'] ?? 'id';
		$direction = strtolower($_GET['direction'] ?? 'desc');
		$allowedSorts = ['id', 'banner_title', 'position_name', 'approval_status', 'status', 'start_date', 'end_date', 'impressions', 'clicks', 'ctr'];
		if (!in_array($sort, $allowedSorts, true)) {
			$sort = 'id';
		}
		if (!in_array($direction, ['asc', 'desc'], true)) {
			$direction = 'desc';
		}

		$campaigns = array_values(array_filter($campaigns, static function (array $campaign) use ($search): bool {
			if ($search === '') {
				return true;
			}

			return str_contains(strtolower(implode(' ', $campaign)), strtolower($search));
		}));

		usort($campaigns, static function (array $left, array $right) use ($sort, $direction): int {
			$leftValue = $left[$sort] ?? '';
			$rightValue = $right[$sort] ?? '';
			$result = strcmp((string)$leftValue, (string)$rightValue);

			return $direction === 'asc' ? $result : -$result;
		});

		$total = count($campaigns);
		$pages = max((int)ceil($total / $perPage), 1);
		$page = min($page, $pages);
		$slice = array_slice($campaigns, ($page - 1) * $perPage, $perPage);

		$data = array_map(static function (array $campaign): array {
			return [
				'id' => $campaign['id'],
				'user_id' => $campaign['user_id'] ?? null,
				'banner_title' => $campaign['banner_title'] ?? '',
				'username' => $campaign['username'] ?? '',
				'position_name' => $campaign['position_name'] ?? '',
				'start_date' => $campaign['start_date'] ?? null,
				'end_date' => $campaign['end_date'] ?? null,
				'impressions' => (int)($campaign['impressions'] ?? 0),
				'clicks' => (int)($campaign['clicks'] ?? 0),
				'ctr' => (int)($campaign['impressions'] ?? 0) > 0
					? round(((int)($campaign['clicks'] ?? 0) / (int)($campaign['impressions'] ?? 0)) * 100, 2)
					: 0,
				'approval_status' => $campaign['approval_status'] ?? $campaign['status'] ?? 'pending',
				'status' => $campaign['status'] ?? 'pending',
				'_links' => [
					'view' => '/admin/ads/campaigns/' . $campaign['id'],
					'edit' => '/admin/ads/campaigns/' . $campaign['id'],
					'delete' => '/admin/ads/campaigns/' . $campaign['id'] . '/reject',
					'approve' => '/admin/ads/campaigns/' . $campaign['id'] . '/approve',
					'reject' => '/admin/ads/campaigns/' . $campaign['id'] . '/reject',
					'request_changes' => '/admin/ads/campaigns/' . $campaign['id'] . '/request-changes',
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
		$campaign = $this->campaignService->findById((int)($params[0] ?? 0));
		if (!$campaign) {
			$this->jsonResponse(false, 'Campagna non trovata.', [], 404);
		}

		$stats = $this->statsService->getCampaignStats((int)$campaign['id']);

		$this->jsonResponse(true, '', [
			'id' => $campaign['id'],
			'user_id' => $campaign['user_id'] ?? null,
			'banner_id' => $campaign['banner_id'] ?? null,
			'banner_title' => $campaign['banner_title'] ?? '',
			'banner_description' => $campaign['banner_description'] ?? '',
			'impressions' => $stats['impressions'],
			'clicks' => $stats['clicks'],
			'ctr' => $stats['ctr'],
			'username' => $campaign['username'] ?? '',
			'position_name' => $campaign['position_name'] ?? '',
			'start_date' => $campaign['start_date'] ?? null,
			'end_date' => $campaign['end_date'] ?? null,
			'approval_status' => $campaign['approval_status'] ?? $campaign['status'] ?? 'pending',
			'status' => $campaign['status'] ?? 'pending',
			'price' => $campaign['price'] ?? 0,
			'currency' => $campaign['currency'] ?? 'EUR',
			'notes' => $campaign['notes'] ?? '',
			'image_path' => $campaign['image_path'] ?? '',
			'_links' => [
				'approve' => '/admin/ads/campaigns/' . $campaign['id'] . '/approve',
				'reject' => '/admin/ads/campaigns/' . $campaign['id'] . '/reject',
				'request_changes' => '/admin/ads/campaigns/' . $campaign['id'] . '/request-changes',
				'edit' => '/admin/ads/campaigns/' . $campaign['id'],
			],
		]);
	}

	public function show(array $params): void
	{
		$campaign = $this->campaignService->findById((int)($params[0] ?? 0));
		if (!$campaign) {
			Session::setFlash('error', 'Campagna non trovata.');
			header('Location: /admin/ads/campaigns');
			exit();
		}

		$this->view('admin/ads/campaigns/show', [
			'campaign' => $campaign,
			'stats' => $this->statsService->getCampaignStats((int)$campaign['id']),
			'payment' => $this->paymentService->getByCampaign((int) $campaign['id']),
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function paymentLogs(): void
	{
		$this->view('admin/ads/payment-logs/index', [
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function paymentLogsData(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$limit = max(10, min((int) ($_GET['perPage'] ?? 25), 200));
		$page = max((int) ($_GET['page'] ?? 1), 1);
		$search = trim((string) ($_GET['search'] ?? ''));
		$sort = (string) ($_GET['sort'] ?? 'created_at');
		$direction = strtolower((string) ($_GET['direction'] ?? 'desc'));
		$filters = $_GET['filters'] ?? [];
		$actionTypeFilter = trim((string) ($filters['action_type'] ?? ''));

		$rows = $this->auditLogService->getPaymentLogs(500);

		if ($actionTypeFilter !== '') {
			$rows = array_values(array_filter($rows, static fn (array $row): bool => (string) ($row['action_type'] ?? '') === $actionTypeFilter));
		}

		if ($search !== '') {
			$needle = mb_strtolower($search);
			$rows = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
				$haystack = mb_strtolower(implode(' ', [
					(string) ($row['action_type'] ?? ''),
					(string) ($row['entity_type'] ?? ''),
					(string) ($row['entity_id'] ?? ''),
					(string) ($row['username'] ?? ''),
					(string) ($row['ip_address'] ?? ''),
					(string) ($row['request_uri'] ?? ''),
					(string) ($row['http_method'] ?? ''),
					(string) ($row['error_message'] ?? ''),
					(string) ($row['payload_json'] ?? ''),
				]));

				return str_contains($haystack, $needle);
			}));
		}

		usort($rows, static function (array $left, array $right) use ($sort, $direction): int {
			$leftValue = $left[$sort] ?? '';
			$rightValue = $right[$sort] ?? '';
			$result = strcmp((string) $leftValue, (string) $rightValue);

			return $direction === 'asc' ? $result : -$result;
		});

		$total = count($rows);
		$pages = max((int) ceil($total / $limit), 1);
		$page = min($page, $pages);
		$slice = array_slice($rows, ($page - 1) * $limit, $limit);

		$data = array_map(static function (array $row): array {
			$payload = [];
			if (!empty($row['payload_json'])) {
				$decoded = json_decode((string) $row['payload_json'], true);
				if (is_array($decoded)) {
					$payload = $decoded;
				}
			}

			return [
				'id' => (int) ($row['id'] ?? 0),
				'created_at' => $row['created_at'] ?? null,
				'action_type' => $row['action_type'] ?? '',
				'entity_type' => $row['entity_type'] ?? '',
				'entity_id' => $row['entity_id'] ?? null,
				'username' => $row['username'] ?? 'Sistema',
				'ip_address' => $row['ip_address'] ?? '',
				'user_agent' => $row['user_agent'] ?? '',
				'request_uri' => $row['request_uri'] ?? '',
				'http_method' => $row['http_method'] ?? '',
				'success' => (int) ($row['success'] ?? 0),
				'error_message' => $row['error_message'] ?? '',
				'payload_summary' => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '',
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

	public function approve(array $params): void
	{
		$this->guardCsrfOrJson();
		$this->runAction(fn() => $this->campaignService->approve((int)($params[0] ?? 0), trim($_POST['admin_notes'] ?? '')), 'Campagna approvata.');
	}

	public function reject(array $params): void
	{
		$this->guardCsrfOrJson();
		$this->runAction(fn() => $this->campaignService->reject((int)($params[0] ?? 0), trim($_POST['admin_notes'] ?? '')), 'Campagna rifiutata.');
	}

	public function requestChanges(array $params): void
	{
		$this->guardCsrfOrJson();
		$this->runAction(fn() => $this->campaignService->requestChanges((int)($params[0] ?? 0), trim($_POST['admin_notes'] ?? '')), 'Modifiche richieste all’inserzionista.');
	}

	private function runAction(callable $action, string $message): void
	{
		$success = false;

		try {
			$action();
			Session::setFlash('success', $message);
			$success = true;
		} catch (Exception $e) {
			Session::setFlash('error', $e->getMessage());
		}

		if ($this->isJsonRequest()) {
			if ($success) {
				$this->jsonResponse(true, $message);
			}

			$this->jsonResponse(false, Session::getFlash('error') ?: 'Operazione non riuscita.', [], 400);
		}

		header('Location: /admin/ads/campaigns');
		exit();
	}

	private function guardCsrfOrJson(): void
	{
		if ($this->csrfIsValid()) {
			return;
		}

		if ($this->isJsonRequest()) {
			$this->jsonResponse(false, 'Token CSRF non valido.', [], 403);
		}

		$this->flashAndRedirect('error', 'Token CSRF non valido.', '/admin/ads/campaigns');
	}
}
