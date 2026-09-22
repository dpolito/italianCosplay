<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AuditLogService;
use App\Services\EventMasterClaimService;
use App\Support\AuditLogActionType;
use Throwable;

class AdminEventMasterClaimController extends Controller
{
	private EventMasterClaimService $claimService;
	private AuditLogService $auditLogService;

	public function __construct()
	{
		$this->claimService = new EventMasterClaimService();
		$this->auditLogService = new AuditLogService();
		$this->ensureCsrfToken();
	}

	private function ensureCsrfToken(): void
	{
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
	}

	private function csrfIsValid(): bool
	{
		return !empty($_POST['csrf_token'])
			&& !empty($_SESSION['csrf_token'])
			&& hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token']);
	}

	public function index(): void
	{
		$this->view('admin/event-master-claims/index', [
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function data(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$claims = $this->claimService->getPendingClaims();
		$page = max((int) ($_GET['page'] ?? 1), 1);
		$perPage = (int) ($_GET['perPage'] ?? 25);
		if (!in_array($perPage, [10, 25, 50, 100], true)) {
			$perPage = 25;
		}

		$search = trim((string) ($_GET['search'] ?? ''));
		$sort = (string) ($_GET['sort'] ?? 'created_at');
		$direction = strtolower((string) ($_GET['direction'] ?? 'asc'));
		$allowedSorts = ['id', 'event_master_name', 'organization_name', 'requester_username', 'role', 'created_at'];
		if (!in_array($sort, $allowedSorts, true)) {
			$sort = 'created_at';
		}
		if (!in_array($direction, ['asc', 'desc'], true)) {
			$direction = 'asc';
		}

		if ($search !== '') {
			$needle = mb_strtolower($search);
			$claims = array_values(array_filter($claims, static function (array $claim) use ($needle): bool {
				$haystack = mb_strtolower(implode(' ', [
					(string) ($claim['event_master_name'] ?? ''),
					(string) ($claim['organization_name'] ?? ''),
					(string) ($claim['requester_username'] ?? ''),
					(string) ($claim['requester_email'] ?? ''),
				]));

				return str_contains($haystack, $needle);
			}));
		}

		usort($claims, static function (array $left, array $right) use ($sort, $direction): int {
			$result = strcmp((string) ($left[$sort] ?? ''), (string) ($right[$sort] ?? ''));

			return $direction === 'asc' ? $result : -$result;
		});

		$total = count($claims);
		$pages = max((int) ceil($total / $perPage), 1);
		$page = min($page, $pages);
		$slice = array_slice($claims, ($page - 1) * $perPage, $perPage);

		$data = array_map(static function (array $claim): array {
			return [
				'id' => (int) $claim['id'],
				'event_master_name' => $claim['event_master_name'],
				'organization_name' => $claim['organization_name'],
				'requester_username' => $claim['requester_username'],
				'requester_email' => $claim['requester_email'],
				'role' => $claim['role'],
				'created_at' => $claim['created_at'],
				'_links' => ['view' => '/admin/event-master-claims/' . (int) $claim['id']],
			];
		}, $slice);

		echo json_encode([
			'success' => true,
			'data' => $data,
			'meta' => ['page' => $page, 'pages' => $pages, 'total' => $total],
		]);
		exit();
	}

	public function show(array $params): void
	{
		$claim = $this->claimService->findById((int) ($params[0] ?? 0));
		if (!$claim) {
			Session::setFlash('error', 'Richiesta non trovata.');
			header('Location: /admin/event-master-claims');
			exit();
		}

		$this->view('admin/event-master-claims/show', [
			'claim' => $claim,
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function approve(array $params): void
	{
		$this->handleDecision((int) ($params[0] ?? 0), true);
	}

	public function reject(array $params): void
	{
		$this->handleDecision((int) ($params[0] ?? 0), false, trim((string) ($_POST['review_notes'] ?? '')));
	}

	private function handleDecision(int $id, bool $approve, ?string $reviewNotes = null): void
	{
		if (!$this->csrfIsValid()) {
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: /admin/event-master-claims/' . $id);
			exit();
		}

		$adminId = (int) ($_SESSION['user_id'] ?? 0);
		$claim = null;

		try {
			$claim = $approve
				? $this->claimService->approve($id, $adminId)
				: $this->claimService->reject($id, $adminId, $reviewNotes ?: null);

			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => $approve
					? AuditLogActionType::EVENT_MASTER_CLAIM_APPROVED
					: AuditLogActionType::EVENT_MASTER_CLAIM_REJECTED,
				'entity_type' => 'event_master_claim',
				'entity_id' => $id,
				'payload' => [
					'event_master_id' => (int) $claim['event_master_id'],
					'organization_id' => (int) $claim['organization_id'],
					'requested_by' => (int) $claim['requested_by'],
					'review_notes' => $reviewNotes,
				],
			]);

			Session::setFlash('success', $approve
				? 'Richiesta approvata e master collegato all’organizzazione.'
				: 'Richiesta rifiutata.');
		} catch (Throwable $exception) {
			$this->auditLogService->logAudit([
				'user_id' => $adminId,
				'action_type' => $approve
					? AuditLogActionType::EVENT_MASTER_CLAIM_APPROVED
					: AuditLogActionType::EVENT_MASTER_CLAIM_REJECTED,
				'entity_type' => 'event_master_claim',
				'entity_id' => $id,
				'success' => 0,
				'error_message' => $exception->getMessage(),
			]);
			Session::setFlash('error', $exception->getMessage());
		}

		header('Location: /admin/event-master-claims');
		exit();
	}
}
