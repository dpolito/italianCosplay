<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\Event;
use App\Models\User;
use App\Repositories\PhotoRepository;
use App\Services\CosplayPortfolioService;
use App\Services\PendingUserActionService;
use App\Services\PhotoAnalyticsService;
use App\Services\PhotoService;
use PDO;
use Throwable;

final class PhotoController extends Controller
{
	private PDO $db;
	private Event $eventModel;
	private User $userModel;
	private PhotoRepository $photos;
	private PhotoService $photoService;
	private PhotoAnalyticsService $photoAnalyticsService;
	private CosplayPortfolioService $cosplayPortfolioService;
	private PendingUserActionService $pendingUserActionService;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
		$this->eventModel = new Event();
		$this->userModel = new User();
		$this->photos = new PhotoRepository($this->db);
		$this->photoService = new PhotoService($this->photos);
		$this->photoAnalyticsService = new PhotoAnalyticsService();
		$this->cosplayPortfolioService = new CosplayPortfolioService();
		$this->pendingUserActionService = new PendingUserActionService();
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
	}

	public function eventGallery(array $params): void
	{
		$event = $this->eventModel->findBySlug((string) ($params[0] ?? ''));
		if (!$event) {
			$this->notFound();
		}
		$page = max(1, (int) ($_GET['page'] ?? 1));
		$perPage = 48;
		$total = $this->photos->countPublishedByEvent((int) $event['id']);
		$items = array_map(fn (array $photo): array => $this->photoService->decorate($photo), $this->photos->listPublishedByEvent((int) $event['id'], $perPage, ($page - 1) * $perPage));
		$this->photoAnalyticsService->track('photo_event_gallery_view', [
			'event_id' => (int) $event['id'],
			'source' => 'event_gallery',
		]);
		$this->view('photos/event-gallery', [
			'event' => $event,
			'photos' => $items,
			'totalPhotos' => $total,
			'uploaderCount' => $this->photos->countUploadersByEvent((int) $event['id']),
			'page' => $page,
			'totalPages' => max(1, (int) ceil($total / $perPage)),
			'seoTitle' => 'Foto ' . ($event['titolo'] ?? 'evento cosplay') . ': gallery cosplay | ItalianCosplay',
			'seoDescription' => 'Guarda le foto cosplay di ' . ($event['titolo'] ?? 'questo evento') . ' pubblicate dalla community di ItalianCosplay.',
			'canonical' => '/eventi-cosplay/' . ($event['slug'] ?? '') . '/foto',
		]);
	}

	public function index(): void
	{
		$filters = $this->photoIndexFilters();
		$page = $this->photoIndexPage();
		if ($this->hasLegacyPhotoIndexQuery()) {
			header('Location: ' . $this->photoIndexPath($filters, $page), true, 301);
			exit();
		}
		$this->photoAnalyticsService->track('photo_hub_view', ['source' => 'photo_hub']);
		if (!empty($filters['event_id']) || !empty($filters['year']) || !empty($filters['uploader_id'])) {
			$this->photoAnalyticsService->track('photo_filter_used', [
				'filter_event_id' => (int) $filters['event_id'] ?: null,
				'filter_year' => (int) $filters['year'] ?: null,
				'filter_uploader_id' => (int) $filters['uploader_id'] ?: null,
				'source' => 'photo_hub',
			], false);
		}
		$perPage = 48;
		$total = $this->photos->countPublished($filters);
		$totalPages = max(1, (int) ceil($total / $perPage));
		if ($page > $totalPages) {
			$page = $totalPages;
		}

		$photos = array_map(
			fn (array $photo): array => $this->photoService->decorate($photo),
			$this->photos->listPublished($filters, $perPage, ($page - 1) * $perPage)
		);

		$hasFilters = !empty($filters['event_id']) || !empty($filters['year']) || !empty($filters['uploader_id']) || $page > 1;
		$this->view('photos/index', [
			'photos' => $photos,
			'totalPhotos' => $total,
			'page' => $page,
			'totalPages' => $totalPages,
			'filters' => $filters,
			'filterEvents' => $this->photos->listFilterEvents(),
			'filterYears' => $this->photos->listFilterYears(),
			'filterUploaders' => $this->photos->listFilterUploaders(),
			'photographedEvents' => $this->photos->listPhotographedEvents(6),
			'canonicalUrl' => URL_ROOT_SITE . '/foto-cosplay',
			'noindex' => $hasFilters,
		]);
	}

	public function show(array $params): void
	{
		$photoId = count($params) > 1 ? (int) ($params[1] ?? 0) : (int) ($params[0] ?? 0);
		$photo = $this->photos->findPublished($photoId);
		if (!$photo) {
			$this->notFound();
		}
		$photo = $this->photoService->decorate($photo);
		$this->photoAnalyticsService->track('photo_view', [
			'photo_id' => (int) $photo['id'],
			'event_id' => (int) $photo['event_id'],
			'uploaded_by_user_id' => (int) $photo['uploaded_by_user_id'],
			'source' => 'photo_detail',
		]);
		$this->view('photos/show', [
			'photo' => $photo,
			'cosplayers' => $this->photos->getCosplayers((int) $photo['id']),
			'userCosplays' => isset($_SESSION['user_id']) ? $this->cosplayPortfolioService->getUserPortfolio((int) $_SESSION['user_id']) : [],
			'csrf_token' => $_SESSION['csrf_token'] ?? '',
			'seoTitle' => 'Foto cosplay di ' . ($photo['event_title'] ?? 'evento') . ' | ItalianCosplay',
			'seoDescription' => 'Foto caricata da @' . ($photo['uploader_username'] ?? 'utente') . ' nella gallery di ' . ($photo['event_title'] ?? 'ItalianCosplay') . '.',
			'photoSeo' => $photo,
			'canonicalUrl' => URL_ROOT_SITE . '/eventi-cosplay/' . rawurlencode((string) $photo['event_slug']) . '/foto/' . (int) $photo['id'],
		]);
	}

	public function dashboardIndex(): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$this->view('dashboard/photos/index', [
			'user' => $this->userModel->find($userId),
			'groups' => $this->photos->groupedByUploader($userId),
		], 'dashboard');
	}

	public function uploadForm(): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$preselectedEvent = null;
		$eventId = (int) ($_GET['event_id'] ?? 0);
		if ($eventId > 0) {
			$stmt = $this->db->prepare("SELECT id, titolo, slug, data_inizio, data_fine FROM events WHERE id = :id AND approvato = 1 AND deleted_at IS NULL");
			$stmt->execute([':id' => $eventId]);
			$preselectedEvent = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
		}
		$this->view('dashboard/photos/upload', [
			'user' => $this->userModel->find($userId),
			'csrf_token' => $_SESSION['csrf_token'] ?? '',
			'photoConfig' => $this->photoService->getConfig(),
			'preselectedEvent' => $preselectedEvent,
		], 'dashboard');
	}

	public function manageEvent(array $params): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$eventId = (int) ($params[0] ?? 0);
		$stmt = $this->db->prepare("SELECT id, titolo, slug, data_inizio, data_fine FROM events WHERE id = :id LIMIT 1");
		$stmt->execute([':id' => $eventId]);
		$event = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$event) {
			$this->notFound();
		}
		$photos = array_map(fn (array $photo): array => $this->photoService->decorate($photo), $this->photos->listForUploaderEvent($userId, $eventId));
		$this->view('dashboard/photos/manage', [
			'user' => $this->userModel->find($userId),
			'event' => $event,
			'photos' => $photos,
			'photoCosplayers' => $this->photos->getCosplayersForPhotos(array_column($photos, 'id')),
			'userCosplays' => $this->cosplayPortfolioService->getUserPortfolio($userId),
			'csrf_token' => $_SESSION['csrf_token'] ?? '',
		], 'dashboard');
	}

	public function upload(): void
	{
		$this->assertCsrfJson();
		try {
			$result = $this->photoService->upload($_FILES['photo'] ?? [], (int) ($_POST['event_id'] ?? 0), (int) $_SESSION['user_id']);
			$this->json(true, 'Foto caricata.', 200, ['photo' => $result]);
		} catch (Throwable $exception) {
			$this->json(false, $exception->getMessage(), 422);
		}
	}

	public function delete(): void
	{
		$this->assertCsrfJson();
		try {
			$this->photoService->deleteOwned((int) ($_POST['photo_id'] ?? 0), (int) $_SESSION['user_id']);
			$this->json(true, 'Foto eliminata.');
		} catch (Throwable $exception) {
			$this->json(false, $exception->getMessage(), 422);
		}
	}

	public function associate(): void
	{
		$this->assertCsrfJson();
		$photoIds = json_decode((string) ($_POST['photo_ids'] ?? '[]'), true);
		$photoIds = is_array($photoIds) ? $photoIds : [];
		$count = $this->photoService->addAssociation(
			$photoIds,
			(int) $_SESSION['user_id'],
			(int) ($_POST['user_id'] ?? 0) ?: null,
			(int) ($_POST['cosplay_id'] ?? 0) ?: null,
			trim((string) ($_POST['display_name'] ?? '')) ?: null,
			trim((string) ($_POST['instagram_username'] ?? '')) ?: null
		);
		$this->json(true, $count > 0 ? $count . ' associazioni salvate.' : 'Associazione già presente.', 200, [
			'associations' => $this->photos->getCosplayersForPhotos($photoIds),
		]);
	}

	public function removeAssociation(): void
	{
		$this->assertCsrfJson();
		try {
			$associationId = (int) ($_POST['association_id'] ?? 0);
			$this->photoService->removeAssociation($associationId, (int) $_SESSION['user_id']);
			$this->json(true, 'Associazione rimossa.');
		} catch (Throwable $exception) {
			$this->json(false, $exception->getMessage(), 422);
		}
	}

	public function claimSelf(array $params): void
	{
		$photoId = count($params) > 1 ? (int) ($params[1] ?? 0) : (int) ($params[0] ?? 0);
		$slug = count($params) > 1 ? (string) ($params[0] ?? '') : '';
		$returnUrl = $slug !== '' ? '/eventi-cosplay/' . rawurlencode($slug) . '/foto/' . $photoId : '/foto/' . $photoId;
		if (empty($_SESSION['user_id'])) {
			$photo = $this->photos->findPublished($photoId);
			$eventId = (int) ($photo['event_id'] ?? 0);
			$this->pendingUserActionService->storePhotoSelfAssociation($eventId, $photoId, (int) ($_POST['cosplay_id'] ?? 0) ?: null, $returnUrl);
			header('Location: /login');
			exit();
		}
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$this->assertCsrfJson(false);
			$count = $this->photoService->addAssociation([$photoId], (int) $_SESSION['user_id'], (int) $_SESSION['user_id'], (int) ($_POST['cosplay_id'] ?? 0) ?: null, null, null, false);
			$photo = $this->photos->findPublished($photoId);
			if ($photo) {
				$this->photoAnalyticsService->track('photo_self_claim', [
					'photo_id' => (int) $photo['id'],
					'event_id' => (int) $photo['event_id'],
					'uploaded_by_user_id' => (int) $photo['uploaded_by_user_id'],
					'source' => 'photo_detail',
				], false);
			}
			Session::setFlash('success', $count > 0 ? 'Ti sei associato alla foto.' : 'Sei già associato a questa foto.');
		}
		header('Location: ' . $returnUrl);
		exit();
	}

	public function report(array $params): void
	{
		$this->assertCsrfJson(false);
		$photoId = count($params) > 1 ? (int) ($params[1] ?? 0) : (int) ($params[0] ?? 0);
		$slug = count($params) > 1 ? (string) ($params[0] ?? '') : '';
		$returnUrl = $slug !== '' ? '/eventi-cosplay/' . rawurlencode($slug) . '/foto/' . $photoId : '/foto/' . $photoId;
		try {
			$this->photoService->report($photoId, isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null, (string) ($_POST['reason'] ?? ''), (string) ($_POST['message'] ?? ''));
			Session::setFlash('success', 'Segnalazione inviata.');
		} catch (Throwable $exception) {
			Session::setFlash('error', $exception->getMessage());
		}
		header('Location: ' . $returnUrl);
		exit();
	}

	public function searchEvents(): void
	{
		$q = trim((string) ($_GET['q'] ?? ''));
		$rows = [];
		if (mb_strlen($q) >= 2) {
			$stmt = $this->db->prepare(
				"SELECT e.id, e.titolo, e.slug, e.data_inizio, e.data_fine, e.luogo, c.nome AS comune_nome, p.nome AS provincia_nome
				 FROM events e
				 LEFT JOIN comuni c ON c.id = e.comune_id
				 LEFT JOIN province p ON p.id = e.provincia_id
				 WHERE e.approvato = 1 AND e.deleted_at IS NULL
				   AND (e.titolo LIKE :q_title OR e.slug LIKE :q_slug OR e.luogo LIKE :q_place OR c.nome LIKE :q_city)
				 ORDER BY e.data_inizio DESC
				 LIMIT 12"
			);
			$like = '%' . $q . '%';
			$stmt->execute([
				':q_title' => $like,
				':q_slug' => $like,
				':q_place' => $like,
				':q_city' => $like,
			]);
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		$this->json(true, '', 200, ['events' => $rows]);
	}

	public function searchUsers(): void
	{
		$q = trim((string) ($_GET['q'] ?? ''));
		$rows = [];
		if (mb_strlen($q) >= 2) {
			$stmt = $this->db->prepare(
				"SELECT id, username, first_name, last_name, avatar
				 FROM users
				 WHERE anonymized_at IS NULL
				   AND (username LIKE :q_username OR first_name LIKE :q_first_name OR last_name LIKE :q_last_name)
				 ORDER BY username ASC
				 LIMIT 12"
			);
			$like = '%' . $q . '%';
			$stmt->execute([
				':q_username' => $like,
				':q_first_name' => $like,
				':q_last_name' => $like,
			]);
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		$this->json(true, '', 200, ['users' => $rows]);
	}

	public function userCosplays(array $params): void
	{
		$this->json(true, '', 200, ['cosplays' => $this->cosplayPortfolioService->getUserPortfolio((int) ($params[0] ?? 0))]);
	}

	private function photoIndexFilters(): array
	{
		$filters = $this->photoIndexPathFilters();
		if (!empty($_GET['event'])) {
			$filters['event_id'] = max(0, (int) $_GET['event']);
		}
		if (!empty($_GET['year'])) {
			$filters['year'] = $this->normalizeYear($_GET['year']);
		}
		if (!empty($_GET['uploader'])) {
			$filters['uploader_id'] = max(0, (int) $_GET['uploader']);
		}

		return $filters;
	}

	private function photoIndexPathFilters(): array
	{
		$filters = [
			'event_id' => 0,
			'year' => 0,
			'uploader_id' => 0,
		];
		foreach ($this->photoIndexSegments() as $segment) {
			if (preg_match('/^evento-(\d+)$/', $segment, $matches) === 1) {
				$filters['event_id'] = (int) $matches[1];
				continue;
			}
			if (preg_match('/^anno-(\d{4})$/', $segment, $matches) === 1) {
				$filters['year'] = $this->normalizeYear($matches[1]);
				continue;
			}
			if (preg_match('/^autore-(\d+)$/', $segment, $matches) === 1) {
				$filters['uploader_id'] = (int) $matches[1];
			}
		}

		return [
			'event_id' => max(0, $filters['event_id']),
			'year' => max(0, $filters['year']),
			'uploader_id' => max(0, $filters['uploader_id']),
		];
	}

	private function photoIndexPage(): int
	{
		$page = max(1, (int) ($_GET['page'] ?? 1));
		foreach ($this->photoIndexSegments() as $segment) {
			if (preg_match('/^pagina-(\d+)$/', $segment, $matches) === 1) {
				$page = max(1, (int) $matches[1]);
			}
		}
		return $page;
	}

	private function photoIndexSegments(): array
	{
		$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/foto-cosplay'), PHP_URL_PATH) ?: '/foto-cosplay';
		$relative = trim(preg_replace('#^/foto-cosplay/?#', '', $path) ?? '', '/');
		return $relative === '' ? [] : array_values(array_filter(explode('/', $relative)));
	}

	private function hasLegacyPhotoIndexQuery(): bool
	{
		return array_key_exists('event', $_GET)
			|| array_key_exists('year', $_GET)
			|| array_key_exists('uploader', $_GET)
			|| array_key_exists('page', $_GET);
	}

	private function photoIndexPath(array $filters, int $page = 1): string
	{
		$segments = [];
		if (!empty($filters['event_id'])) {
			$segments[] = 'evento-' . (int) $filters['event_id'];
		}
		if (!empty($filters['year'])) {
			$segments[] = 'anno-' . (int) $filters['year'];
		}
		if (!empty($filters['uploader_id'])) {
			$segments[] = 'autore-' . (int) $filters['uploader_id'];
		}
		if ($page > 1) {
			$segments[] = 'pagina-' . $page;
		}
		return '/foto-cosplay' . ($segments ? '/' . implode('/', $segments) : '');
	}

	private function normalizeYear(mixed $year): int
	{
		$year = (int) $year;
		$current = (int) date('Y');
		return ($year >= 2000 && $year <= $current + 3) ? $year : 0;
	}

	private function assertCsrfJson(bool $json = true): void
	{
		if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) {
			if ($json) {
				$this->json(false, 'Token CSRF non valido.', 403);
			}
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: /dashboard/photos');
			exit();
		}
	}

	private function json(bool $success, string $message, int $status = 200, array $data = []): void
	{
		http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
		exit();
	}

	private function notFound(): void
	{
		header('HTTP/1.0 404 Not Found');
		require APP_ROOT . '/app/views/errors/404.php';
		exit();
	}
}
