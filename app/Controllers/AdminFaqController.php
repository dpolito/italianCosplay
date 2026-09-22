<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AuditLogService;
use App\Services\FaqService;
use App\Services\SiteFeatureFlagService;
use App\Support\AuditLogActionType;
use InvalidArgumentException;
use Throwable;

class AdminFaqController extends Controller
{
	private FaqService $faqService;
	private AuditLogService $auditLogService;

	public function __construct()
	{
		$this->faqService = new FaqService();
		$this->auditLogService = new AuditLogService();
		if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}

	public function index(): void
	{
		header('Location: /admin/faq/categories');
		exit();
	}

	public function categories(): void
	{
		$this->view('admin/faq/categories', [
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function items(): void
	{
		$this->view('admin/faq/items', [
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function categoriesData(): void
	{
		$rows = array_map(static fn (array $category): array => [
			'id' => (int) $category['id'],
			'name' => $category['name'],
			'slug' => $category['slug'],
			'feature' => $category['feature_flag_key'] ?: 'Sempre visibile',
			'item_count' => (int) $category['item_count'],
			'status' => (int) $category['is_active'] === 1 ? 'Pubblicata' : 'Nascosta',
			'sort_order' => (int) $category['sort_order'],
			'created_at' => $category['created_at'],
			'_links' => [
				'edit' => '/admin/faq/categories/' . (int) $category['id'] . '/edit',
				'delete' => '/admin/faq/categories/' . (int) $category['id'] . '/delete',
			],
		], $this->faqService->getAdminCategories());

		$this->listResponse($rows, ['id', 'name', 'slug', 'feature', 'item_count', 'status', 'sort_order', 'created_at'], 'sort_order');
	}

	public function itemsData(): void
	{
		$rows = array_map(static fn (array $item): array => [
			'id' => (int) $item['id'],
			'question' => $item['question'],
			'category_name' => $item['category_name'],
			'feature' => $item['feature_flag_key'] ?: 'Eredita categoria',
			'status' => (int) $item['is_active'] === 1 ? 'Pubblicata' : 'Nascosta',
			'sort_order' => (int) $item['sort_order'],
			'created_at' => $item['created_at'],
			'_links' => [
				'edit' => '/admin/faq/items/' . (int) $item['id'] . '/edit',
				'delete' => '/admin/faq/items/' . (int) $item['id'] . '/delete',
			],
		], $this->faqService->getAdminItems());

		$this->listResponse($rows, ['id', 'question', 'category_name', 'feature', 'status', 'sort_order', 'created_at'], 'sort_order');
	}

	public function categoryForm(array $params = []): void
	{
		$id = (int) ($params[0] ?? 0);
		$category = $id > 0 ? $this->faqService->findCategory($id) : null;
		if ($id > 0 && !$category) { $this->notFound('Categoria FAQ non trovata.'); }
		$this->view('admin/faq/category-form', [
			'category' => $category, 'flags' => (new SiteFeatureFlagService())->getAllFlags(),
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function saveCategory(array $params = []): void
	{
		$id = (int) ($params[0] ?? 0);
		$this->save('category', $id ?: null);
	}

	public function itemForm(array $params = []): void
	{
		$id = (int) ($params[0] ?? 0);
		$item = $id > 0 ? $this->faqService->findItem($id) : null;
		if ($id > 0 && !$item) { $this->notFound('FAQ non trovata.'); }
		$this->view('admin/faq/item-form', [
			'item' => $item, 'categories' => $this->faqService->getAdminCategories(),
			'flags' => (new SiteFeatureFlagService())->getAllFlags(), 'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function saveItem(array $params = []): void
	{
		$id = (int) ($params[0] ?? 0);
		$this->save('item', $id ?: null);
	}

	public function deleteCategory(array $params): void { $this->delete('category', (int) ($params[0] ?? 0)); }
	public function deleteItem(array $params): void { $this->delete('item', (int) ($params[0] ?? 0)); }

	private function save(string $type, ?int $id): void
	{
		$this->requireValidCsrf();
		try {
			$entityId = $type === 'category'
				? $this->faqService->saveCategory($id, $_POST)
				: $this->faqService->saveItem($id, $_POST);
			$action = $type === 'category'
				? ($id ? AuditLogActionType::FAQ_CATEGORY_UPDATED : AuditLogActionType::FAQ_CATEGORY_CREATED)
				: ($id ? AuditLogActionType::FAQ_ITEM_UPDATED : AuditLogActionType::FAQ_ITEM_CREATED);
			$this->log($action, $type === 'category' ? 'faq_category' : 'faq_item', $entityId, true);
			Session::setFlash('success', $type === 'category' ? 'Categoria FAQ salvata.' : 'Domanda e risposta salvate.');
			header('Location: ' . ($type === 'category' ? '/admin/faq/categories' : '/admin/faq/items')); exit();
		} catch (Throwable $exception) {
			$failedAction = $type === 'category'
				? ($id ? AuditLogActionType::FAQ_CATEGORY_UPDATED : AuditLogActionType::FAQ_CATEGORY_CREATED)
				: ($id ? AuditLogActionType::FAQ_ITEM_UPDATED : AuditLogActionType::FAQ_ITEM_CREATED);
			$this->log($failedAction, $type === 'category' ? 'faq_category' : 'faq_item', $id, false, $exception->getMessage());
			$message = $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'Non è stato possibile salvare la FAQ. Controlla i dati e riprova.';
			Session::setFlash('error', $message);
			header('Location: ' . ($type === 'category' ? '/admin/faq/categories/' : '/admin/faq/items/') . ($id ? $id . '/edit' : 'create')); exit();
		}
	}

	private function delete(string $type, int $id): void
	{
		$this->requireValidCsrf();
		if ($id < 1) { $this->notFound('Elemento FAQ non valido.'); }
		$success = $type === 'category' ? $this->faqService->deleteCategory($id) : $this->faqService->deleteItem($id);
		$action = $type === 'category' ? AuditLogActionType::FAQ_CATEGORY_DELETED : AuditLogActionType::FAQ_ITEM_DELETED;
		$this->log($action, $type === 'category' ? 'faq_category' : 'faq_item', $id, $success);
		$message = $success ? 'Elemento FAQ eliminato.' : 'Impossibile eliminare l’elemento FAQ.';
		if ($this->wantsJson()) {
			header('Content-Type: application/json; charset=utf-8');
			http_response_code($success ? 200 : 500);
			echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
			exit();
		}
		Session::setFlash($success ? 'success' : 'error', $message);
		header('Location: ' . ($type === 'category' ? '/admin/faq/categories' : '/admin/faq/items')); exit();
	}

	private function listResponse(array $rows, array $allowedSorts, string $defaultSort): never
	{
		$search = mb_strtolower(mb_substr(trim((string) ($_GET['search'] ?? '')), 0, 100));
		if ($search !== '') {
			$rows = array_values(array_filter($rows, static fn (array $row): bool => str_contains(mb_strtolower(implode(' ', array_filter($row, static fn (mixed $value): bool => is_scalar($value)))), $search)));
		}

		$sort = (string) ($_GET['sort'] ?? $defaultSort);
		$direction = strtolower((string) ($_GET['direction'] ?? 'asc'));
		if (!in_array($sort, $allowedSorts, true)) $sort = $defaultSort;
		if (!in_array($direction, ['asc', 'desc'], true)) $direction = 'asc';
		usort($rows, static function (array $left, array $right) use ($sort, $direction): int {
			$result = ($left[$sort] ?? '') <=> ($right[$sort] ?? '');
			return $direction === 'asc' ? $result : -$result;
		});

		$page = max(1, (int) ($_GET['page'] ?? 1));
		$perPage = (int) ($_GET['perPage'] ?? 25);
		if (!in_array($perPage, [10, 25, 50, 100], true)) $perPage = 25;
		$total = count($rows);
		$pages = max(1, (int) ceil($total / $perPage));
		$page = min($page, $pages);
		$rows = array_slice($rows, ($page - 1) * $perPage, $perPage);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['success' => true, 'data' => $rows, 'meta' => ['page' => $page, 'pages' => $pages, 'total' => $total]], JSON_UNESCAPED_UNICODE);
		exit();
	}

	private function wantsJson(): bool
	{
		return str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
	}

	private function requireValidCsrf(): void
	{
		if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', (string) $_POST['csrf_token'])) {
			http_response_code(403); exit('Richiesta non valida.');
		}
	}

	private function log(string $action, string $entityType, ?int $entityId, bool $success, ?string $error = null): void
	{
		$this->auditLogService->logAudit(['user_id' => $_SESSION['user_id'] ?? null, 'action_type' => $action, 'entity_type' => $entityType, 'entity_id' => $entityId, 'success' => $success ? 1 : 0, 'error_message' => $error]);
	}

	private function notFound(string $message): never
	{
		Session::setFlash('error', $message); header('Location: /admin/faq'); exit();
	}
}
