<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Session;
use App\Models\BlogPost;
use App\Models\Comune;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Provincia;
use App\Models\Regione;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\ConsentService;
use App\Services\FavoriteService;
use App\Support\AuditLogActionType;
use App\Services\AdStatsService;
use App\Services\CosplayPortfolioService;
use App\Services\NotificationService;
use Exception;

class DashboardController extends Controller
{
	private $userModel;
	private $uploadDir = APP_ROOT . '/public_assets/uploads/avatar/';
	private AdStatsService $adStatsService;
	private AuditLogService $auditLogService;
	private ConsentService $consentService;
	private FavoriteService $favoriteService;
	private \App\Services\EventAgendaService $eventAgendaService;
	private NotificationService $notificationService;
	private CosplayPortfolioService $cosplayPortfolioService;
	private Event $eventModel;
	private BlogPost $blogPostModel;
	private Guest $guestModel;
	private Comune $comuneModel;
	private Provincia $provinciaModel;
	private Regione $regioneModel;
	private Event $eventSearchModel;

	public function __construct()
	{
		$this->userModel = new User();
		$this->adStatsService = new AdStatsService();
		$this->auditLogService = new AuditLogService();
		$this->consentService = new ConsentService();
		$this->favoriteService = new FavoriteService();
		$this->eventAgendaService = new \App\Services\EventAgendaService();
		$this->notificationService = new NotificationService();
		$this->cosplayPortfolioService = new CosplayPortfolioService();
		$this->eventModel = new Event();
		$this->blogPostModel = new BlogPost();
		$this->guestModel = new Guest();
		$this->comuneModel = new Comune();
		$this->provinciaModel = new Provincia();
		$this->regioneModel = new Regione();
		$this->eventSearchModel = new Event();
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
		// Middleware: deve essere loggato
		//$this->middleware('AuthMiddleware');
	}

	public function overview()
	{
		$userId = $_SESSION['user_id'];
		$user = $this->userModel->find($userId);
		$adStats = $this->adStatsService->getUserOverview((int)$userId);
		$favoritesSummary = [
			'total' => $this->favoriteService->countUserFavorites((int) $userId),
			'event' => $this->favoriteService->countUserFavoritesByType((int) $userId, 'event'),
			'guest' => $this->favoriteService->countUserFavoritesByType((int) $userId, 'guest'),
			'blog_post' => $this->favoriteService->countUserFavoritesByType((int) $userId, 'blog_post'),
			'regione' => $this->favoriteService->countUserFavoritesByType((int) $userId, 'regione'),
			'provincia' => $this->favoriteService->countUserFavoritesByType((int) $userId, 'provincia'),
			'comune' => $this->favoriteService->countUserFavoritesByType((int) $userId, 'comune'),
		];
		$agendaCounts = $this->eventAgendaService->getUserAgendaCount((int) $userId);
		$ciVadoEvents = $this->eventAgendaService->getUpcomingByStatus((int) $userId, 'ci_vado', 3);
		$cosplayPortfolio = $this->cosplayPortfolioService->getUserPortfolio((int) $userId);

		$this->view('dashboard/overview', [
			'user' => $user,
			'adStats' => $adStats,
			'favoritesSummary' => $favoritesSummary,
			'agendaCounts' => $agendaCounts,
			'ciVadoEvents' => $ciVadoEvents,
			'cosplayPortfolioCount' => count($cosplayPortfolio),
		], 'dashboard'); // 🔥 layout custom
	}

	public function favorites(): void
	{
		$this->requireFeature('enable_favorites', 'Preferiti temporaneamente disattivati.');
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$user = $this->userModel->find($userId);
		$favorites = $this->favoriteService->getUserFavorites($userId);
		$grouped = $this->buildFavoriteGroups($favorites);

		$this->view('dashboard/favorites', [
			'user' => $user,
			'groupedFavorites' => $grouped,
			'summary' => [
				'total' => count($favorites),
				'event' => count($grouped['event'] ?? []),
				'guest' => count($grouped['guest'] ?? []),
				'blog_post' => count($grouped['blog_post'] ?? []),
				'regione' => count($grouped['regione'] ?? []),
				'provincia' => count($grouped['provincia'] ?? []),
				'comune' => count($grouped['comune'] ?? []),
			],
		], 'dashboard');
	}

	public function events(): void
	{
		$this->requireFeature('enable_personal_agenda', 'Agenda personale temporaneamente disattivata.');
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$user = $this->userModel->find($userId);
		$agenda = $this->eventAgendaService->getUserAgenda($userId);
		$groupedAgenda = [
			'ci_vado' => $this->eventAgendaService->getUpcomingByStatus($userId, 'ci_vado', 12),
			'mi_interessa' => $this->eventAgendaService->getUpcomingByStatus($userId, 'mi_interessa', 12),
			'forse_vado' => $this->eventAgendaService->getUpcomingByStatus($userId, 'forse_vado', 12),
		];
		$counts = $this->eventAgendaService->getUserAgendaCount($userId);

		$this->view('dashboard/events', [
			'user' => $user,
			'agenda' => $agenda,
			'groupedAgenda' => $groupedAgenda,
			'counts' => $counts,
		], 'dashboard');
	}

	public function notifications(): void
	{
		$this->requireFeature('enable_notifications', 'Notifiche temporaneamente disattivate.');
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$user = $this->userModel->find($userId);
		$notifications = $this->notificationService->getLatest($userId, 30);
		$unreadCount = $this->notificationService->getUnreadCount($userId);

		$this->view('dashboard/notifications', [
			'user' => $user,
			'notifications' => $notifications,
			'unreadCount' => $unreadCount,
		], 'dashboard');
	}

	public function cosplay(): void
	{
		$this->requireFeature('enable_cosplay_portfolio', 'Portfolio cosplay temporaneamente disattivato.');
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$user = $this->userModel->find($userId);
		$portfolio = $this->cosplayPortfolioService->getUserPortfolio($userId);
		$characterSearch = trim((string) ($_GET['q'] ?? ''));
		$characters = $this->cosplayPortfolioService->getCharacterSuggestions($characterSearch);
		$eventSelections = $this->cosplayPortfolioService->getUserEventSelections($userId);
		$groupedEventSelections = $this->groupCosplaySelectionsByEvent($eventSelections);

		$this->view('dashboard/cosplay', [
			'user' => $user,
			'portfolio' => $portfolio,
			'characters' => $characters,
			'eventSelections' => $eventSelections,
			'groupedEventSelections' => $groupedEventSelections,
			'characterSearch' => $characterSearch,
		], 'dashboard');
	}

	public function searchCosplayEvents(): void
	{
		header('Content-Type: application/json; charset=utf-8');
		$query = trim((string) ($_GET['q'] ?? ''));
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$excludedEventIds = [];
		if ($userId > 0) {
			$eventSelections = $this->cosplayPortfolioService->getUserEventSelections($userId);
			$excludedEventIds = array_values(array_unique(array_map(static fn (array $selection): int => (int) ($selection['event_id'] ?? 0), $eventSelections)));
		}
		$events = $this->eventSearchModel->searchApprovedEvents($query, 12, $excludedEventIds);

		echo json_encode([
			'success' => true,
			'items' => array_map(static function (array $event): array {
				$dateLabel = '';
				if (!empty($event['data_inizio'])) {
					$dateLabel = date('d/m/Y', strtotime((string) $event['data_inizio']));
				}
				if (!empty($event['data_fine']) && $event['data_fine'] !== ($event['data_inizio'] ?? null)) {
					$dateLabel .= ' - ' . date('d/m/Y', strtotime((string) $event['data_fine']));
				}

				return [
					'id' => (int) ($event['id'] ?? 0),
					'titolo' => $event['titolo'] ?? '',
					'slug' => $event['slug'] ?? '',
					'data_label' => $dateLabel,
					'location' => trim(implode(' · ', array_filter([
						$event['luogo'] ?? '',
						$event['comune_nome'] ?? '',
						$event['provincia_nome'] ?? '',
						$event['regione_nome'] ?? '',
					]))),
				];
			}, $events),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public function searchCosplayCharacters(): void
	{
		header('Content-Type: application/json; charset=utf-8');
		$query = trim((string) ($_GET['q'] ?? ''));
		$characters = $this->cosplayPortfolioService->getCharacterSuggestions($query, 25);

		echo json_encode([
			'success' => true,
			'items' => array_map(static function (array $character): array {
				return [
					'id' => (int) ($character['id'] ?? 0),
					'name_full' => $character['name_full'] ?? '',
					'name_native' => $character['name_native'] ?? '',
					'image_large' => $character['image_large'] ?? '',
					'anime_titles' => $character['anime_titles'] ?? '',
				];
			}, $characters),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public function saveCosplayPortfolio(): void
	{
		$this->requireFeature('enable_cosplay_portfolio', 'Portfolio cosplay temporaneamente disattivato.');
		$isAjax = $this->isAjaxRequest();
		if (!$this->isValidCsrfToken()) {
			$this->respondCosplayAction($isAjax, false, 'Token CSRF non valido.', '/dashboard/cosplay');
		}

		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$portfolioId = (int) ($_POST['id'] ?? 0);
		$ok = $this->cosplayPortfolioService->savePortfolioItem($userId, [
			'id' => $portfolioId,
			'anilist_character_id' => (int) ($_POST['anilist_character_id'] ?? 0),
			'custom_name' => trim((string) ($_POST['custom_name'] ?? '')),
			'notes' => trim((string) ($_POST['notes'] ?? '')),
			'reference_image_file' => $_FILES['reference_image_file'] ?? null,
			'is_public' => !empty($_POST['is_public']) ? 1 : 0,
		]);

		$portfolioItem = null;
		if ($ok) {
			$portfolioItem = $portfolioId > 0
				? $this->cosplayPortfolioService->getPortfolioItem($userId, $portfolioId)
				: $this->cosplayPortfolioService->getLatestPortfolioItemByCharacter($userId, (int) ($_POST['anilist_character_id'] ?? 0));
		}
		$this->respondCosplayAction(
			$isAjax,
			$ok,
			$ok ? 'Cosplay salvato nel portfolio.' : 'Impossibile salvare il cosplay.',
			'/dashboard/cosplay',
			[
				'portfolioItem' => $portfolioItem,
			]
		);
	}

	public function deleteCosplayPortfolio(): void
	{
		$this->requireFeature('enable_cosplay_portfolio', 'Portfolio cosplay temporaneamente disattivato.');
		$isAjax = $this->isAjaxRequest();
		if (!$this->isValidCsrfToken()) {
			$this->respondCosplayAction($isAjax, false, 'Token CSRF non valido.', '/dashboard/cosplay');
		}

		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$portfolioId = (int) ($_POST['portfolio_id'] ?? 0);
		$ok = $this->cosplayPortfolioService->deletePortfolioItem($userId, $portfolioId);

		$this->respondCosplayAction(
			$isAjax,
			$ok,
			$ok ? 'Cosplay rimosso dal portfolio.' : 'Impossibile rimuovere il cosplay.',
			'/dashboard/cosplay',
			[
				'portfolio_id' => $portfolioId,
			]
		);
	}

	public function toggleCosplayPortfolioVisibility(): void
	{
		$this->requireFeature('enable_cosplay_portfolio', 'Portfolio cosplay temporaneamente disattivato.');
		$isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
		if (!$this->isValidCsrfToken()) {
			if ($isAjax) {
				header('Content-Type: application/json; charset=utf-8');
				http_response_code(403);
				echo json_encode(['success' => false, 'message' => 'Token CSRF non valido.']);
				return;
			}
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: /dashboard/cosplay');
			exit();
		}

		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$portfolioId = (int) ($_POST['portfolio_id'] ?? 0);
		$isPublic = !empty($_POST['is_public']);
		$ok = $this->cosplayPortfolioService->setPortfolioVisibility($userId, $portfolioId, $isPublic);

		if ($isAjax) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode([
				'success' => $ok,
				'message' => $ok ? 'Visibilità aggiornata.' : 'Impossibile aggiornare la visibilità.',
				'is_public' => $isPublic ? 1 : 0,
				'portfolio_id' => $portfolioId,
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			return;
		}

		Session::setFlash($ok ? 'success' : 'error', $ok ? 'Visibilità aggiornata.' : 'Impossibile aggiornare la visibilità.');
		header('Location: /dashboard/cosplay');
		exit();
	}

	public function saveEventCosplaySelection(): void
	{
		$this->requireFeature('enable_cosplay_portfolio', 'Portfolio cosplay temporaneamente disattivato.');
		$isAjax = $this->isAjaxRequest();
		if (!$this->isValidCsrfToken()) {
			$this->respondCosplayAction($isAjax, false, 'Token CSRF non valido.', (string) ($_POST['redirect_to'] ?? '/dashboard/cosplay'));
		}

		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$eventId = (int) ($_POST['event_id'] ?? 0);
		$portfolioId = (int) ($_POST['portfolio_id'] ?? 0);
		$status = (string) ($_POST['status'] ?? 'porterò');
		$ok = $this->cosplayPortfolioService->saveEventSelection($userId, $eventId, $portfolioId, $status);

		$this->respondCosplayAction(
			$isAjax,
			$ok,
			$ok ? 'Cosplay collegato all’evento.' : 'Impossibile collegare il cosplay all’evento.',
			(string) ($_POST['redirect_to'] ?? '/dashboard/cosplay'),
			[
				'event_id' => $eventId,
				'portfolio_id' => $portfolioId,
				'status' => $status,
				'selection' => $ok ? $this->cosplayPortfolioService->getCharacterSelectionsForEvent($userId, $eventId) : [],
				'selections' => $ok ? $this->cosplayPortfolioService->getUserEventSelections($userId) : [],
			]
		);
	}

	public function removeEventCosplaySelection(): void
	{
		$this->requireFeature('enable_cosplay_portfolio', 'Portfolio cosplay temporaneamente disattivato.');
		$isAjax = $this->isAjaxRequest();
		if (!$this->isValidCsrfToken()) {
			$this->respondCosplayAction($isAjax, false, 'Token CSRF non valido.', (string) ($_POST['redirect_to'] ?? '/dashboard/cosplay'));
		}

		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$eventId = (int) ($_POST['event_id'] ?? 0);
		$portfolioId = (int) ($_POST['portfolio_id'] ?? 0);
		$ok = $this->cosplayPortfolioService->removeEventSelection($userId, $eventId, $portfolioId > 0 ? $portfolioId : null);

		$this->respondCosplayAction(
			$isAjax,
			$ok,
			$ok ? 'Associazione cosplay rimossa.' : 'Impossibile rimuovere l’associazione.',
			(string) ($_POST['redirect_to'] ?? '/dashboard/cosplay'),
			[
				'event_id' => $eventId,
				'selection' => $ok ? $this->cosplayPortfolioService->getCharacterSelectionsForEvent($userId, $eventId) : [],
				'selections' => $ok ? $this->cosplayPortfolioService->getUserEventSelections($userId) : [],
			]
		);
	}

	public function markNotificationRead(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->isValidCsrfToken()) {
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Richiesta non valida.']);
			return;
		}

		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$notificationId = (int) ($_POST['notification_id'] ?? 0);
		if ($userId <= 0 || $notificationId <= 0) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Notifica non valida.']);
			return;
		}

		$ok = $this->notificationService->markAsRead($userId, $notificationId);
		echo json_encode([
			'success' => $ok,
			'message' => $ok ? 'Notifica segnata come letta.' : 'Impossibile aggiornare la notifica.',
		]);
	}

	public function deleteNotification(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->isValidCsrfToken()) {
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Richiesta non valida.']);
			return;
		}

		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$notificationId = (int) ($_POST['notification_id'] ?? 0);
		if ($userId <= 0 || $notificationId <= 0) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Notifica non valida.']);
			return;
		}

		$ok = $this->notificationService->deleteNotification($userId, $notificationId);
		echo json_encode([
			'success' => $ok,
			'message' => $ok ? 'Notifica eliminata.' : 'Impossibile eliminare la notifica.',
		]);
	}

	public function markAllNotificationsRead(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->isValidCsrfToken()) {
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Richiesta non valida.']);
			return;
		}

		$userId = (int) ($_SESSION['user_id'] ?? 0);
		if ($userId <= 0) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Utente non valido.']);
			return;
		}

		$ok = $this->notificationService->markAllAsRead($userId);
		echo json_encode([
			'success' => $ok,
			'message' => $ok ? 'Tutte le notifiche sono state segnate come lette.' : 'Impossibile aggiornare le notifiche.',
		]);
	}

	public function profile()
	{
		$userId = $_SESSION['user_id'] ?? null;
		$user = $this->userModel->find($userId);

		$this->view('dashboard/profile', [
			'user' => $user
		], 'dashboard');
	}

	public function updateProfile()
	{
		if (!$this->isValidCsrfToken()) {
			$this->view('dashboard/profile', [
				'error' => 'Token CSRF non valido.',
			], 'dashboard');
			return;
		}

		$userId = $_SESSION['user_id'] ?? null;
		if (!$userId) {
			header('Location: /login');
		}

		$errors = [];

		$firstName  = trim($_POST['first_name'] ?? '');
		$lastName   = trim($_POST['last_name'] ?? '');
		$website    = trim($_POST['website'] ?? '');
		$bio        = trim($_POST['bio'] ?? '');
		$social = $_POST['social'] ?? [];
		$socialJson = json_encode($social, JSON_UNESCAPED_UNICODE);
		$comune_id = trim($_POST['comune_id'] ?? '');

		// VALIDAZIONI

		if ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
			$errors[] = 'URL sito non valido.';
		}

		if (!empty($errors)) {
			$this->view('dashboard/profile', [
				'error' => implode(' ', $errors),
			], 'dashboard');
		}

		// UPDATE
		$userModel = new User();

		$userModel->update_dashboard($userId, [
			'first_name' => $firstName,
			'last_name'  => $lastName,
			'website'    => $website,
			'bio'        => $bio,
			'social'     => $socialJson,
			'comune_id'  => $comune_id
		]);
		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::PROFILE_UPDATED,
			'entity_type' => 'user',
			'entity_id' => (int) $userId,
			'success' => 1,
			'payload' => [
				'first_name' => $firstName,
				'last_name' => $lastName,
				'website' => $website,
				'comune_id' => $comune_id,
			],
		]);
		$user = $this->userModel->find($userId);
		$this->view('dashboard/profile', [
			'success' => 'Profilo aggiornato con successo.',
			'user' => $user
		], 'dashboard');
	}

	public function changePassword()
	{
		$userId = $_SESSION['user_id'] ?? null;
		$user = $this->userModel->find($userId);

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			if (!$this->isValidCsrfToken()) {
				$this->view('dashboard/change_password', [
					'user' => $user,
					'errors' => ['Sessione scaduta o richiesta non valida. Ricarica la pagina e riprova.'],
					'csrf_token' => $_SESSION['csrf_token']
				], 'dashboard');
				return;
			}

			$current = $_POST['current_password'] ?? '';
			$new     = $_POST['new_password'] ?? '';
			$confirm = $_POST['confirm_password'] ?? '';

			$errors = [];

			if (!$user || !password_verify($current, $user['password'])) {
				$errors[] = "Password attuale non corretta.";
			}
			if (strlen($new) < 8) {
				$errors[] = "La nuova password deve essere di almeno 8 caratteri.";
			}
			if ($new !== $confirm) {
				$errors[] = "La conferma della password non corrisponde.";
			}

			if (empty($errors)) {
				$this->userModel->update_dashboard_password($userId, [
					'password' => password_hash($new, PASSWORD_DEFAULT)
				]);
				$this->view('dashboard/change_password', [
					'success' => 'Password aggiornata con successo.',
					'user' => $user,
					'csrf_token' => $_SESSION['csrf_token']
				], 'dashboard');
				return;
			} else {
				$this->view('dashboard/change_password', [
					'user' => $user,
					'errors' => $errors,
					'csrf_token' => $_SESSION['csrf_token']
				], 'dashboard');
				return;
			}
		}

		// GET → mostra il form
		$this->view('dashboard/change_password', [
			'user' => $user,
			'csrf_token' => $_SESSION['csrf_token']
		], 'dashboard');
	}

	public function avatar()
	{
		$userId = $_SESSION['user_id'] ?? null;
		$user = $this->userModel->find($userId);
		$this->view('dashboard/avatar', [
			'user' => $user,
		], 'dashboard');
	}
	public function updateAvatar(){
		$userId = $_SESSION['user_id'] ?? null;
		$user = $this->userModel->find($userId);
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			if (!$this->isValidCsrfToken()) {
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Richiesta non valida. Ricarica la pagina e riprova.']);
				exit();
			}

			if (!empty($_FILES['avatar'])) {
				$newAvatarPath = $this->handleImageUpload($_FILES['avatar'], $user['avatar']);

				if ($newAvatarPath) {
					$this->userModel->update_dashboard_avatar($userId, [
						'avatar' => $newAvatarPath
					]);
					$user = $this->userModel->find($userId);
					$this->auditLogService->logAudit([
						'user_id' => $userId,
						'action_type' => AuditLogActionType::AVATAR_UPDATED,
						'entity_type' => 'user',
						'entity_id' => (int) $userId,
						'success' => 1,
						'payload' => [
							'avatar' => $newAvatarPath,
						],
					]);
					echo json_encode(['success' => true, 'avatarUrl' => $user['avatar']]);
					exit();
				}
				// Se handleImageUpload ha dato errore, viene già settato il flash
			} else {
				echo json_encode(['success' => false, 'message' => 'errore']);
				exit();
			}
		}
	}

	public function toggleFavorite(): void
	{
		$this->requireFeature('enable_favorites', 'Preferiti temporaneamente disattivati.');
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Location: /dashboard/favorites');
			exit();
		}

		if (!$this->isValidCsrfToken()) {
			Session::setFlash('error', 'Richiesta non valida.');
			header('Location: ' . ($_POST['redirect_to'] ?? '/dashboard/favorites'));
			exit();
		}

		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$entityType = trim((string) ($_POST['entity_type'] ?? ''));
		$entityId = (int) ($_POST['entity_id'] ?? 0);
		$redirectTo = $_POST['redirect_to'] ?? '/dashboard/favorites';
		$isAjax = $this->isAjaxRequest();

		if ($userId <= 0 || $entityType === '' || $entityId <= 0) {
			$this->respondFavoriteToggle($isAjax, false, false, $entityType, $entityId, $redirectTo, 'Dati non validi.');
			return;
		}

		$isFavorited = $this->favoriteService->isFavorited($userId, $entityType, $entityId);
		$ok = $isFavorited
			? $this->favoriteService->removeFavorite($userId, $entityType, $entityId)
			: $this->favoriteService->addFavorite($userId, $entityType, $entityId);
		$nowFavorited = $ok ? !$isFavorited : $isFavorited;

		$this->respondFavoriteToggle(
			$isAjax,
			$ok,
			$nowFavorited,
			$entityType,
			$entityId,
			$redirectTo,
			$ok ? ($nowFavorited ? 'Aggiunto ai preferiti.' : 'Rimosso dai preferiti.') : 'Impossibile aggiornare il preferito.'
		);
	}

	private function respondFavoriteToggle(bool $isAjax, bool $success, bool $isFavorited, string $entityType, int $entityId, string $redirectTo, string $message): void
	{
		if ($isAjax) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode([
				'success' => $success,
				'isFavorited' => $isFavorited,
				'message' => $message,
				'entity_type' => $entityType,
				'entity_id' => $entityId,
			], JSON_UNESCAPED_UNICODE);
			exit();
		}

		Session::setFlash($success ? 'success' : 'error', $message);
		header('Location: ' . $redirectTo);
		exit();
	}

	private function isAjaxRequest(): bool
	{
		return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
			|| str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
	}

	private function respondCosplayAction(bool $isAjax, bool $success, string $message, string $redirectTo, array $extra = []): void
	{
		if ($isAjax) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array_merge([
				'success' => $success,
				'message' => $message,
			], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit();
		}

		Session::setFlash($success ? 'success' : 'error', $message);
		header('Location: ' . $redirectTo);
		exit();
	}

	private function groupCosplaySelectionsByEvent(array $eventSelections): array
	{
		$grouped = [];

		foreach ($eventSelections as $selection) {
			$eventId = (int) ($selection['event_id'] ?? 0);
			if ($eventId <= 0) {
				continue;
			}

			if (!isset($grouped[$eventId])) {
				$grouped[$eventId] = [
					'event_id' => $eventId,
					'titolo' => $selection['titolo'] ?? 'Evento',
					'slug' => $selection['slug'] ?? '',
					'data_inizio' => $selection['data_inizio'] ?? null,
					'data_fine' => $selection['data_fine'] ?? null,
					'items' => [],
				];
			}

			$grouped[$eventId]['items'][] = $selection;
		}

		uasort($grouped, static function (array $left, array $right): int {
			$leftDate = (string) ($left['data_inizio'] ?? '');
			$rightDate = (string) ($right['data_inizio'] ?? '');
			return strcmp($leftDate, $rightDate);
		});

		return array_values($grouped);
	}

	/**
	 * Gestisce l'upload di un'immagine.
	 * @param array $file Il file caricato da $_FILES.
	 * @param string|null $currentImagePath Il percorso dell'immagine corrente (per la cancellazione).
	 * @return string|null Il percorso relativo dell'immagine caricata o null in caso di errore/nessun upload.
	 */
	private function handleImageUpload($file, $currentImagePath = null)
	{
		if ($file['error'] === UPLOAD_ERR_NO_FILE) {
			// Nessun file caricato, mantieni l'immagine esistente se presente
			return $currentImagePath;
		}

		if ($file['error'] !== UPLOAD_ERR_OK) {
			Session::setFlash('error', 'Errore durante l\'upload del file: ' . $file['error']);
			return null;
		}

		$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
		$maxSize = 5 * 1024 * 1024; // 5 MB

		if (!in_array($file['type'], $allowedTypes)) {
			Session::setFlash('error', 'Tipo di file non consentito. Sono ammessi solo JPEG, PNG, GIF, WEBP.');
			return null;
		}

		if ($file['size'] > $maxSize) {
			Session::setFlash('error', 'Il file è troppo grande. Dimensione massima consentita: 5MB.');
			return null;
		}

		// Crea il nome del file
		$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$baseFileName = uniqid('avatar_');
		$originalFileName = $baseFileName . '.' . $fileExtension;
		$webpFileName = $baseFileName . '.webp';

		$destinationPath = $this->uploadDir . $originalFileName;
		$webpPath = $this->uploadDir . $webpFileName;

		// Percorsi relativi per il database
		$relativeWebpPath = '/public_assets/uploads/avatar/' . $webpFileName;

		// Sposta l'immagine originale
		if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
			Session::setFlash('error', 'Impossibile spostare il file caricato.');
			return null;
		}
		$this->processAvatar($destinationPath, $destinationPath);
		// Converti in WebP
		if (!$this->convertToWebP($destinationPath, $webpPath)) {
			Session::setFlash('error', 'Errore nella conversione in WebP. L\'immagine originale è stata salvata.');
			return '/public_assets/uploads/events/' . $originalFileName;
		}

		// Elimina immagine precedente, se presente
		if ($currentImagePath && file_exists(APP_ROOT . $currentImagePath)) {
			unlink(APP_ROOT . $currentImagePath);
		}

		// (Opzionale) elimina anche l'originale se vuoi mantenere solo il WebP
		// unlink($destinationPath);

		return $relativeWebpPath;
	}
	function processAvatar($tmpFile, $destPath, $maxSize = 100) {
		list($width, $height, $type) = getimagesize($tmpFile);

		switch ($type) {
			case IMAGETYPE_JPEG:
				$src = imagecreatefromjpeg($tmpFile);
				break;
			case IMAGETYPE_PNG:
				$src = imagecreatefrompng($tmpFile);
				break;
			default:
				throw new Exception("Formato immagine non supportato");
		}

		// Crop quadrato centrato
		$side = min($width, $height);
		$srcX = (int) floor(($width - $side) / 2);
		$srcY = (int) floor(($height - $side) / 2);

		$dst = imagecreatetruecolor($maxSize, $maxSize);
		imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $maxSize, $maxSize, $side, $side);

		// Salvataggio
		switch ($type) {
			case IMAGETYPE_JPEG:
				imagejpeg($dst, $destPath, 90);
				break;
			case IMAGETYPE_PNG:
				imagepng($dst, $destPath);
				break;
		}

		imagedestroy($src);
		imagedestroy($dst);
		return true;
	}
	/**
	 * Converte un’immagine in formato WebP usando GD
	 */
	private function convertToWebP(string $source, string $destination, int $quality = 80): bool
	{
		if (!file_exists($source)) {
			return false;
		}

		$info = getimagesize($source);
		if ($info === false) {
			return false;
		}

		$mime = $info['mime'];
		switch ($mime) {
			case 'image/jpeg':
				$image = imagecreatefromjpeg($source);
				break;
			case 'image/png':
				$image = imagecreatefrompng($source);
				imagepalettetotruecolor($image);
				imagealphablending($image, true);
				imagesavealpha($image, true);
				break;
			case 'image/gif':
				$image = imagecreatefromgif($source);
				break;
			case 'image/webp':
				// Già in formato webp
				copy($source, $destination);
				return true;
			default:
				return false;
		}

		$result = imagewebp($image, $destination, $quality);
		imagedestroy($image);
		return $result;
	}

	public function cover()
	{

		$userId = $_SESSION['user_id'];
		$user = $this->userModel->find($userId);

		$this->view('dashboard/cover', [
			'user' => $user,
		], 'dashboard');
	}
	public function updateCover()
	{
		error_log(print_r($_FILES, true));
		error_log(print_r($_POST, true));

		header('Content-Type: application/json');

		if (!$this->isValidCsrfToken()) {
			echo json_encode([
				'success' => false,
				'message' => 'Richiesta non valida. Ricarica la pagina e riprova.'
			]);
			exit;
		}

		$userId = $_SESSION['user_id'] ?? null;

		if (!$userId) {
			echo json_encode([
				'success' => false,
				'message' => 'Utente non autenticato'
			]);
			exit;
		}

		$user = $this->userModel->find($userId);

		$positionX = (int)($_POST['cover_position_x'] ?? 50);
		$positionY = (int)($_POST['cover_position_y'] ?? 50);

		$uploaded = false;
		$newCoverPath = null;

		// 1. UPLOAD FILE
		if (
			isset($_FILES['cover']) &&
			$_FILES['cover']['error'] === UPLOAD_ERR_OK
		) {
			$newCoverPath = $this->handleCoverUpload(
				$_FILES['cover'],
				$user['profile_cover'] ?? null
			);

			if ($newCoverPath) {
				$uploaded = true;
			} else {
				echo json_encode([
					'success' => false,
					'message' => 'Errore upload cover'
				]);
				exit;
			}
		}

		// 2. UPDATE DB
		$this->userModel->update_dashboard_cover($userId, [
			'profile_cover' => $newCoverPath ?? $user['profile_cover'],
			'cover_position_x' => $positionX,
			'cover_position_y' => $positionY
		]);
		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::COVER_UPDATED,
			'entity_type' => 'user',
			'entity_id' => (int) $userId,
			'success' => 1,
			'payload' => [
				'profile_cover' => $newCoverPath ?? $user['profile_cover'],
				'cover_position_x' => $positionX,
				'cover_position_y' => $positionY,
				'uploaded' => $uploaded,
			],
		]);

		// 3. RESPONSE SEMPRE
		echo json_encode([
			'success' => true,
			'message' => $uploaded ? 'Cover aggiornata' : 'Posizione aggiornata',
			'coverUrl' => $newCoverPath ?? $user['profile_cover'],
			'x' => $positionX,
			'y' => $positionY
		]);

		exit;
	}
	private function handleCoverUpload($file, $currentImagePath = null)
	{
		error_log('COVER FILE: ' . print_r($file, true));
		if ($file['error'] !== UPLOAD_ERR_OK) {
			return null;
		}

		$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
		$maxSize = 5 * 1024 * 1024;

		if (!in_array($file['type'], $allowedTypes)) return null;
		if ($file['size'] > $maxSize) return null;

		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$base = uniqid('cover_');

		$original = $base . '.' . $ext;
		$webp = $base . '.webp';

		$dest = APP_ROOT . '/public_assets/uploads/covers/' . $original;
		$destWebp = APP_ROOT . '/public_assets/uploads/covers/' . $webp;

		$relative = '/public_assets/uploads/covers/' . $webp;

		if (!move_uploaded_file($file['tmp_name'], $dest)) {
			return null;
		}

		$this->convertToWebP($dest, $destWebp);

		if ($currentImagePath && file_exists(APP_ROOT . $currentImagePath)) {
			@unlink(APP_ROOT . $currentImagePath);
		}

		return $relative;
	}
	public function updateSettings()
	{
		$userId = $_SESSION['user_id'];

		if (!$this->isValidCsrfToken()) {
			$user = $this->userModel->find($userId);
			$this->view('dashboard/settings', [
				'user' => $user,
				'error' => 'Richiesta non valida. Ricarica la pagina e riprova.'
			], 'dashboard');
			return;
		}

		$settings = $_POST['settings'] ?? [];

		// normalizza checkbox (non selezionati non arrivano)
		$all = [
			'show_email' => !empty($settings['show_email']),
			'show_bio' => !empty($settings['show_bio']),
			'show_comune' => !empty($settings['show_comune']),
			'show_instagram' => !empty($settings['show_instagram']),
			'show_facebook' => !empty($settings['show_facebook']),
			'show_tiktok' => !empty($settings['show_tiktok']),
			'show_youtube' => !empty($settings['show_youtube']),
			'show_nome' => !empty($settings['show_nome']),
			'newsletter_opt_in' => !empty($settings['newsletter_opt_in']),
		];

		$this->userModel->updateProfileSettings($userId, $all);
		$this->consentService->updateMarketingConsent($userId, !empty($settings['newsletter_opt_in']));
		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::PROFILE_SETTINGS_UPDATED,
			'entity_type' => 'user',
			'entity_id' => (int) $userId,
			'success' => 1,
			'payload' => $all,
		]);

		$user = $this->userModel->find($userId);

		$this->view('dashboard/settings', [
			'user' => $user,
			'success' => 'Impostazioni aggiornate con successo'
		], 'dashboard');
	}
	public function settings()
	{

		$userId = $_SESSION['user_id'] ?? null;
		$user = $this->userModel->find($userId);

		$this->view('dashboard/settings', [
			'user' => $user
		], 'dashboard');
	}

	public function deleteAccount()
	{
		$userId = $_SESSION['user_id'] ?? null;

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Location: /dashboard/settings');
			exit();
		}

		if (!$userId) {
			header('Location: /login');
			exit();
		}

		if (!$this->isValidCsrfToken()) {
			$user = $this->userModel->find($userId);
			$this->view('dashboard/settings', [
				'user' => $user,
				'error' => 'Richiesta non valida. Ricarica la pagina e riprova.'
			], 'dashboard');
			return;
		}

		$anonymized = $this->userModel->anonymizeAccount((int) $userId);

		if (!$anonymized) {
			$user = $this->userModel->find($userId);
			$this->view('dashboard/settings', [
				'user' => $user,
				'error' => 'Non è stato possibile completare la cancellazione dell\'account.'
			], 'dashboard');
			return;
		}

		Session::setFlash('success', 'Il tuo account è stato anonimizzato con successo.');
		unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role']);
		session_regenerate_id(true);
		header('Location: /');
		exit();
	}

	private function buildFavoriteGroups(array $favorites): array
	{
		$groups = [
			'event' => [],
			'guest' => [],
			'blog_post' => [],
			'regione' => [],
			'provincia' => [],
			'comune' => [],
		];

		foreach ($favorites as $favorite) {
			$type = $favorite['entity_type'] ?? '';
			$id = (int) ($favorite['entity_id'] ?? 0);
			if ($id <= 0 || !array_key_exists($type, $groups)) {
				continue;
			}

			$item = match ($type) {
				'event' => $this->buildFavoriteEvent((int) $id, $favorite),
				'guest' => $this->buildFavoriteGuest((int) $id, $favorite),
				'blog_post' => $this->buildFavoriteBlogPost((int) $id, $favorite),
				'regione' => $this->buildFavoriteRegione((int) $id, $favorite),
				'provincia' => $this->buildFavoriteProvincia((int) $id, $favorite),
				'comune' => $this->buildFavoriteComune((int) $id, $favorite),
				default => null,
			};

			if ($item !== null) {
				$groups[$type][] = $item;
			}
		}

		return $groups;
	}

	private function buildFavoriteEvent(int $id, array $favorite): ?array
	{
		$event = $this->eventModel->find($id);
		if (!$event) {
			return null;
		}

		return [
			'type' => 'event',
			'id' => $id,
			'title' => $event['titolo'] ?? 'Evento',
			'slug' => $event['slug'] ?? '',
			'url' => '/eventi-cosplay/' . ($event['slug'] ?? ''),
			'image' => $this->favoriteImageForEvent($id),
			'subtitle' => trim(($event['data_inizio'] ?? '') . ' ' . ($event['luogo'] ?? '')),
			'created_at' => $favorite['created_at'] ?? null,
		];
	}

	private function buildFavoriteGuest(int $id, array $favorite): ?array
	{
		$guest = $this->guestModel->find($id);
		if (!$guest) {
			return null;
		}

		return [
			'type' => 'guest',
			'id' => $id,
			'title' => $guest['name'] ?? 'Guest',
			'slug' => $guest['slug'] ?? '',
			'url' => '/ospiti/' . ($guest['slug'] ?? ''),
			'image' => !empty($guest['immagine']) ? '/public_assets/' . ltrim($guest['immagine'], '/') : '',
			'subtitle' => mb_strimwidth(strip_tags((string) ($guest['bio'] ?? '')), 0, 120, '...'),
			'created_at' => $favorite['created_at'] ?? null,
		];
	}

	private function buildFavoriteBlogPost(int $id, array $favorite): ?array
	{
		$post = $this->blogPostModel->find($id);
		if (!$post) {
			return null;
		}

		return [
			'type' => 'blog_post',
			'id' => $id,
			'title' => $post['titolo'] ?? 'Articolo',
			'slug' => $post['slug'] ?? '',
			'url' => '/blog/' . ($post['slug'] ?? ''),
			'image' => '',
			'subtitle' => mb_strimwidth(strip_tags((string) ($post['excerpt'] ?? $post['contenuto'] ?? '')), 0, 120, '...'),
			'created_at' => $favorite['created_at'] ?? null,
		];
	}

	private function buildFavoriteComune(int $id, array $favorite): ?array
	{
		$comune = $this->comuneModel->find($id);
		if (!$comune) {
			return null;
		}
		$provincia = !empty($comune['provincia_id']) ? $this->provinciaModel->find((int) $comune['provincia_id']) : null;
		$regione = !empty($provincia['regione_id'] ?? null) ? $this->regioneModel->find((int) $provincia['regione_id']) : null;

		return [
			'type' => 'comune',
			'id' => $id,
			'title' => $comune['nome'] ?? 'Comune',
			'slug' => $comune['slug'] ?? '',
			'url' => !empty($regione['slug'] ?? null) && !empty($provincia['slug'] ?? null) && !empty($comune['slug'] ?? null)
				? '/eventi-cosplay/' . $regione['slug'] . '/' . $provincia['slug'] . '/' . $comune['slug']
				: '/eventi-cosplay',
			'image' => '',
			'subtitle' => trim(($provincia['nome'] ?? '') . (!empty($regione['nome']) ? ', ' . $regione['nome'] : '')),
			'created_at' => $favorite['created_at'] ?? null,
		];
	}

	private function buildFavoriteRegione(int $id, array $favorite): ?array
	{
		$regione = $this->regioneModel->find($id);
		if (!$regione) {
			return null;
		}

		return [
			'type' => 'regione',
			'id' => $id,
			'title' => $regione['nome'] ?? 'Regione',
			'slug' => $regione['slug'] ?? '',
			'url' => !empty($regione['slug']) ? '/eventi-cosplay/' . $regione['slug'] : '/eventi-cosplay',
			'image' => '',
			'subtitle' => 'Regione',
			'created_at' => $favorite['created_at'] ?? null,
		];
	}

	private function buildFavoriteProvincia(int $id, array $favorite): ?array
	{
		$provincia = $this->provinciaModel->find($id);
		if (!$provincia) {
			return null;
		}

		$regione = !empty($provincia['regione_id']) ? $this->regioneModel->find((int) $provincia['regione_id']) : null;

		return [
			'type' => 'provincia',
			'id' => $id,
			'title' => $provincia['nome'] ?? 'Provincia',
			'slug' => $provincia['slug'] ?? '',
			'url' => !empty($regione['slug'] ?? null) && !empty($provincia['slug'] ?? null)
				? '/eventi-cosplay/' . $regione['slug'] . '/' . $provincia['slug']
				: '/eventi-cosplay',
			'image' => '',
			'subtitle' => trim(($regione['nome'] ?? '') . ' · Provincia'),
			'created_at' => $favorite['created_at'] ?? null,
		];
	}

	private function favoriteImageForEvent(int $eventId): string
	{
		$event = $this->eventModel->find($eventId);
		if (empty($event)) {
			return '';
		}

		return !empty($event['immagine']) ? '/public_assets/' . ltrim($event['immagine'], '/') : '';
	}

	private function isValidCsrfToken(): bool
	{
		return !empty($_POST['csrf_token'])
			&& !empty($_SESSION['csrf_token'])
			&& hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
	}
}
