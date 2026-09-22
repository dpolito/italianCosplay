<?php
namespace App\Controllers;


use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\AdCampaignService;
use App\Services\AdPositionService;
use App\Services\AdStatsService;
use App\Services\ConsentService;
use App\Services\EventAgendaAnalyticsService;
use App\Services\AdminEngagementAnalyticsService;
use App\Services\FavoriteAnalyticsService;
use App\Services\SiteFeatureFlagService;
use App\Services\UserRegistrationAnalyticsService;
use App\Support\AuditLogActionType;
use App\ValueObjects\Money;

class AdminController extends Controller
{
	private User $userModel;
	private AdStatsService $adStatsService;
	private AdCampaignService $adCampaignService;
	private AdPositionService $adPositionService;
	private AdminEngagementAnalyticsService $adminEngagementAnalyticsService;
	private EventAgendaAnalyticsService $eventAgendaAnalyticsService;
	private FavoriteAnalyticsService $favoriteAnalyticsService;
	private SiteFeatureFlagService $siteFeatureFlagService;
	private UserRegistrationAnalyticsService $userRegistrationAnalyticsService;
	private AuditLogService $auditLogService;
	private ConsentService $consentService;

	public function __construct()
	{
		$this->userModel = new User();
		$this->adStatsService = new AdStatsService();
		$this->adCampaignService = new AdCampaignService();
		$this->adPositionService = new AdPositionService();
		$this->adminEngagementAnalyticsService = new AdminEngagementAnalyticsService();
		$this->eventAgendaAnalyticsService = new EventAgendaAnalyticsService();
		$this->favoriteAnalyticsService = new FavoriteAnalyticsService();
		$this->siteFeatureFlagService = new SiteFeatureFlagService();
		$this->userRegistrationAnalyticsService = new UserRegistrationAnalyticsService();
		$this->auditLogService = new AuditLogService();
		$this->consentService = new ConsentService();

		// CSRF token
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
	}

	/**
	 * Valida il token CSRF per le richieste POST/modifiche.
	 * @return bool True se il token è valido, false altrimenti.
	 */
	private function validateCsrfToken()
	{
		if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
			Session::setFlash('error', 'Errore di sicurezza: richiesta non valida (CSRF).');
			return false;
		}
		return true;
	}

	/**
	 * Mostra la dashboard dell'amministratore (puoi personalizzarla in seguito)
	 */
	public function dashboard()
	{
		$registrationDays = $this->resolveDashboardIntervalDays($_GET['registration_days'] ?? null);
		$adCampaigns = $this->adCampaignService->getAllForAdmin();
		$campaignCount = count($adCampaigns);
		$activeCampaigns = 0;
		$pendingCampaigns = 0;
		$totalImpressions = 0;
		$totalClicks = 0;
		$totalRevenue = Money::zero('EUR');

		foreach ($adCampaigns as $campaign) {
			$approvalStatus = $campaign['approval_status'] ?? $campaign['status'] ?? 'pending';
			if ($approvalStatus === 'approved' || $approvalStatus === 'active') {
				$activeCampaigns++;
			}
			if ($approvalStatus === 'pending' || $approvalStatus === 'pending_review' || $approvalStatus === 'waiting_payment') {
				$pendingCampaigns++;
			}

			$stats = $this->adStatsService->getCampaignStats((int)$campaign['id']);
			$totalImpressions += (int)($stats['impressions'] ?? 0);
			$totalClicks += (int)($stats['clicks'] ?? 0);
			$totalRevenue = $totalRevenue->add(Money::fromDecimal($campaign['price'] ?? 0, $campaign['currency'] ?? 'EUR'));
		}

		$this->view('admin/dashboard', [
			'favoriteAnalytics' => [
				'counts' => $this->favoriteAnalyticsService->getActionCountsByType(),
				'trend' => $this->favoriteAnalyticsService->getDailyTrend(30),
			],
			'agendaAnalytics' => [
				'counts' => $this->eventAgendaAnalyticsService->getActionCounts(),
				'trend' => $this->eventAgendaAnalyticsService->getDailyTrend(30),
			],
			'engagementAnalytics' => [
				'topEvents' => $this->adminEngagementAnalyticsService->getTopEventEngagement(),
				'eventOpportunities' => $this->adminEngagementAnalyticsService->getEventOpportunities(),
				'topBlogPosts' => $this->adminEngagementAnalyticsService->getTopBlogEngagement(),
			],
			'userRegistrationAnalytics' => $this->userRegistrationAnalyticsService->getOverview($registrationDays),
			'dashboardIntervals' => [7, 30, 90, 180, 365],
			'adStats' => [
				'campaigns' => $campaignCount,
				'active_campaigns' => $activeCampaigns,
				'pending_campaigns' => $pendingCampaigns,
				'positions' => count($this->adPositionService->getAll()),
				'impressions' => $totalImpressions,
				'clicks' => $totalClicks,
				'ctr' => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0,
				'revenue' => $totalRevenue->toDecimal(),
			],
		], 'admin'); // Specificato il layout 'admin'
	}

	private function resolveDashboardIntervalDays($value): int
	{
		$days = (int) $value;
		$allowed = [7, 30, 90, 180, 365];

		return in_array($days, $allowed, true) ? $days : 30;
	}

	public function setup()
	{
		$flags = $this->siteFeatureFlagService->getAllFlags();

		$this->view('admin/setup/index', [
			'flags' => $flags,
			'csrf_token' => $_SESSION['csrf_token'],
		], 'admin');
	}

	public function updateSetup()
	{
		if (!$this->validateCsrfToken()) {
			header('Location: /admin/setup');
			exit();
		}

		$enabledFlags = $_POST['flags'] ?? [];
		if (!is_array($enabledFlags)) {
			$enabledFlags = [];
		}

		$currentFlags = $this->siteFeatureFlagService->getAllFlags();
		$payload = [];
		foreach ($currentFlags as $flag) {
			$flagKey = (string) ($flag['flag_key'] ?? '');
			if ($flagKey === '') {
				continue;
			}
			$payload[$flagKey] = isset($enabledFlags[$flagKey]) && (string) $enabledFlags[$flagKey] === '1';
		}

		$updated = $this->siteFeatureFlagService->updateFlags($payload);

		$this->auditLogService->logAudit([
			'user_id' => $_SESSION['user_id'] ?? null,
			'action_type' => AuditLogActionType::SITE_SETUP_UPDATED,
			'entity_type' => 'site_setup',
			'entity_id' => null,
			'success' => $updated ? 1 : 0,
			'payload' => [
				'flags' => $payload,
			],
		]);

		Session::setFlash($updated ? 'success' : 'error', $updated ? 'Impostazioni sito aggiornate.' : 'Impossibile aggiornare le impostazioni sito.');
		header('Location: /admin/setup');
		exit();
	}

	/**
	 * Mostra l'elenco di tutti gli utenti
	 */
	public function users()
	{
		$users = $this->userModel->getAllUsers();
		$roles = $this->userModel->getRoles();
		$this->view('admin/users/index',
			[
				'users' => $users,
				'csrf_token' => $_SESSION['csrf_token'],
				'roles' => $roles
			],
			'admin'); // Specificato il layout 'admin'
	}

	public function usersData(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$users = $this->userModel->getAllUsers();
		$roles = $this->userModel->getRoles();
		$roleMap = [];
		foreach ($roles as $role) {
			$roleMap[(int) $role['id']] = $role['name'];
		}

		$page = max((int) ($_GET['page'] ?? 1), 1);
		$perPage = (int) ($_GET['perPage'] ?? 25);
		if (!in_array($perPage, [10, 25, 50, 100], true)) {
			$perPage = 25;
		}
		$search = trim($_GET['search'] ?? '');
		$sort = $_GET['sort'] ?? 'id';
		$direction = strtolower($_GET['direction'] ?? 'desc');
		$allowedSorts = ['id', 'username', 'email', 'role', 'role_id', 'verified'];
		if (!in_array($sort, $allowedSorts, true)) {
			$sort = 'id';
		}
		if (!in_array($direction, ['asc', 'desc'], true)) {
			$direction = 'desc';
		}

		$users = array_values(array_filter($users, static function (array $user) use ($search): bool {
			if ($search === '') {
				return true;
			}
			return str_contains(
				strtolower(implode(' ', $user)),
				strtolower($search)
			);
		}));

		usort($users, static function (array $left, array $right) use ($sort, $direction): int {
			$leftValue = $left[$sort] ?? '';
			$rightValue = $right[$sort] ?? '';
			$result = is_numeric($leftValue) && is_numeric($rightValue)
				? ((float) $leftValue <=> (float) $rightValue)
				: strcmp((string) $leftValue, (string) $rightValue);
			return $direction === 'asc' ? $result : -$result;
		});

		$total = count($users);
		$pages = max((int) ceil($total / $perPage), 1);
		$page = min($page, $pages);
		$slice = array_slice($users, ($page - 1) * $perPage, $perPage);

		$data = array_map(static function (array $user) use ($roleMap): array {
			$roleId = (int) ($user['role_id'] ?? 0);
			return [
				'id' => $user['id'],
				'username' => $user['username'],
				'email' => $user['email'],
				'role_id' => $roleId,
				'role_name' => $roleMap[$roleId] ?? ($user['role'] ?? 'N/D'),
				'verified' => (int) ($user['verified'] ?? 0),
				'privacy_accepted_at' => $user['privacy_accepted_at'] ?? null,
				'privacy_policy_version_id' => isset($user['privacy_policy_version_id']) ? (int) $user['privacy_policy_version_id'] : null,
				'marketing_opt_in' => (int) ($user['marketing_opted_in'] ?? 0),
				'marketing_opt_in_at' => $user['marketing_opted_in_at'] ?? null,
				'age_declared_adult' => (int) ($user['age_declared_adult'] ?? 0),
				'age_declared_at' => $user['age_declared_at'] ?? null,
				'created_at' => $user['created_at'] ?? null,
				'updated_at' => $user['updated_at'] ?? null,
				'_links' => [
					'edit' => '/admin/users/edit/' . $user['id'],
					'delete' => '/admin/users/delete/' . $user['id'],
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

	public function userDetail($params): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$id = (int) ($params[0] ?? 0);
		$user = $this->userModel->find($id);

		if (!$user) {
			echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
			exit();
		}

		$roles = $this->userModel->getRoles();
		$roleName = 'N/D';
		foreach ($roles as $role) {
			if ((int) $role['id'] === (int) ($user['role_id'] ?? 0)) {
				$roleName = $role['name'];
				break;
			}
		}

		echo json_encode([
			'success' => true,
			'data' => [
				'id' => $user['id'],
				'username' => $user['username'],
				'email' => $user['email'],
				'role_id' => $user['role_id'] ?? null,
				'role_name' => $roleName,
				'verified' => (int) ($user['verified'] ?? 0),
				'privacy_accepted_at' => $user['privacy_accepted_at'] ?? null,
				'privacy_policy_version_id' => isset($user['privacy_policy_version_id']) ? (int) $user['privacy_policy_version_id'] : null,
				'marketing_opt_in' => (int) ($user['marketing_opted_in'] ?? 0),
				'marketing_opt_in_at' => $user['marketing_opted_in_at'] ?? null,
				'age_declared_adult' => (int) ($user['age_declared_adult'] ?? 0),
				'age_declared_at' => $user['age_declared_at'] ?? null,
				'comune_name' => $user['comune_name'] ?? '',
				'first_name' => $user['first_name'] ?? '',
				'last_name' => $user['last_name'] ?? '',
				'website' => $user['website'] ?? '',
				'bio' => $user['bio'] ?? '',
				'social' => $user['social'] ?? '',
				'created_at' => $user['created_at'] ?? null,
				'updated_at' => $user['updated_at'] ?? null,
				'_links' => [
					'edit' => '/admin/users/edit/' . $user['id'],
					'delete' => '/admin/users/delete/' . $user['id'],
				],
			],
		]);
		exit();
	}

	/**
	 * Mostra il modulo per creare un nuovo utente
	 */
	public function createUser()
	{
		$roles = $this->userModel->getRoles();
		$this->view('admin/users/create', [
			'roles' => $roles,
			'csrf_token' => $_SESSION['csrf_token']
		], 'admin'); // Specificato il layout 'admin'
	}

	/**
	 * Gestisce l'invio del modulo per la creazione di un utente
	 */
	public function storeUser()
	{
		// Protezione CSRF
		if (!$this->validateCsrfToken()) {
			header('Location: /admin/users/create'); // Reindirizza al form di creazione utente
			exit();
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$data = [
				'username' => trim($_POST['username']),
				'email' => trim($_POST['email']),
				'password' => trim($_POST['password']),
				'role' => trim($_POST['role'] ?? 'user'), // Default 'user' se non specificato
				'role_id'  => trim($_POST['role_id'] ?? 'user'),
				'verified'  => trim($_POST['verified'] ?? '0')
			];

			$errors = [];

			// Validazione Username
			if (empty($data['username'])) {
				$errors[] = 'L\'username è obbligatorio.';
			} elseif (strlen($data['username']) < 3 || strlen($data['username']) > 50) {
				$errors[] = 'L\'username deve essere tra 3 e 50 caratteri.';
			} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
				$errors[] = 'L\'username può contenere solo lettere, numeri e underscore.';
			}

			// Validazione Email
			if (empty($data['email'])) {
				$errors[] = 'L\'email è obbligatoria.';
			} elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
				$errors[] = 'Formato email non valido.';
			} elseif (strlen($data['email']) > 255) {
				$errors[] = 'L\'email è troppo lunga (max 255 caratteri).';
			}

			// Validazione Password
			if (empty($data['password'])) {
				$errors[] = 'La password è obbligatoria.';
			} elseif (strlen($data['password']) < 6) {
				$errors[] = 'La password deve essere di almeno 6 caratteri.';
			}

			// Controlla se username o email esistono già nel DB
			if ($this->userModel->findByUsername($data['username'])) {
				$errors[] = 'Username già in uso.';
			}
			if ($this->userModel->findByEmail($data['email'])) {
				$errors[] = 'Email già in uso.';
			}

			if (!empty($errors)) {
				Session::setFlash('error', implode('<br>', $errors));
				// Ricarica il form con i dati inseriti e l'errore
				$this->view('admin/users/create', array_merge($data, ['csrf_token' => $_SESSION['csrf_token']]), 'admin'); // Specificato il layout 'admin'
				return;
			}

			if ($this->userModel->create($data)) {
				$createdUser = $this->userModel->findByUsername($data['username']) ?: $this->userModel->findByEmail($data['email']);
				$this->auditLogService->logAudit([
					'user_id' => $_SESSION['user_id'] ?? null,
					'action_type' => AuditLogActionType::USER_CREATED,
					'entity_type' => 'user',
					'entity_id' => $createdUser['id'] ?? null,
					'success' => 1,
					'payload' => [
						'username' => $data['username'],
						'email' => $data['email'],
						'role_id' => $data['role_id'],
						'verified' => $data['verified'],
					],
				]);
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode([
					'success' => true,
					'message' => 'Utente creato con successo!',
				]);
				exit();
			} else {
				Session::setFlash('error', 'Errore durante la creazione dell\'utente.');
				$this->view('admin/users/create', array_merge($data, ['csrf_token' => $_SESSION['csrf_token']]), 'admin'); // Specificato il layout 'admin'
			}
		} else {
			header('Location: /admin/users/create');
			exit();
		}
	}

	/**
	 * Mostra il modulo per modificare un utente esistente
	 * @param array $params Contiene l'ID dell'utente
	 */
	public function editUser($params)
	{
		$id = $params[0] ?? null; // Assumendo che l'ID sia il primo parametro nella rotta

		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID utente non valido.');
			header('Location: /admin/users');
			exit();
		}

		$user = $this->userModel->find($id);

		if (!$user) {
			Session::setFlash('error', 'Utente non trovato.');
			header('Location: /admin/users');
			exit();
		}
		$roles = $this->userModel->getRoles();
		$this->view('admin/users/edit', [
			'user' => $user,
			'roles' => $roles,
			'csrf_token' => $_SESSION['csrf_token']
		], 'admin');

		//$this->view('admin/users/edit', ['user' => $user, 'csrf_token' => $_SESSION['csrf_token']], 'admin'); // Specificato il layout 'admin'
	}

	/**
	 * Gestisce l'invio del modulo per l'aggiornamento di un utente
	 * @param array $params Contiene l'ID dell'utente
	 */
	public function updateUser($params)
	{
		// Protezione CSRF
		if (!$this->validateCsrfToken()) {
			header('Content-Type: application/json; charset=utf-8');
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Token CSRF non valido.']);
			exit();
		}

		$id = $params[0] ?? null;

		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID utente non valido.');
			header('Location: /admin/users');
			exit();
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$user = $this->userModel->find($id);
			if (!$user) {
				Session::setFlash('error', 'Utente non trovato.');
				header('Location: /admin/users');
				exit();
			}

			$data = [
				'username' => trim($_POST['username'] ?? $user['username']),
				'email' => trim($_POST['email'] ?? $user['email']),
				'first_name' => trim($_POST['first_name'] ?? $user['first_name'] ?? ''),
				'last_name' => trim($_POST['last_name'] ?? $user['last_name'] ?? ''),
				'website' => trim($_POST['website'] ?? $user['website'] ?? ''),
				'bio' => trim($_POST['bio'] ?? $user['bio'] ?? ''),
				'comune_id' => trim($_POST['comune_id'] ?? ($user['comune_id'] ?? '')),
				'verified' => isset($_POST['verified']) ? 1 : (int) ($user['verified'] ?? 0),
				'role' => trim($_POST['role'] ?? 'user'),
				'role_id'  => trim($_POST['role_id'] ?? $user['role_id']),
				'social' => $this->normalizeSocial($_POST['social'] ?? []),
				'profile_settings' => $this->normalizeProfileSettings($_POST['settings'] ?? []),
				'age_declared_adult' => isset($_POST['age_declared_adult']) ? 1 : 0,
				'marketing_opted_in' => isset($_POST['marketing_opted_in']) ? 1 : 0,
				'avatar' => $user['avatar'] ?? '',
				'profile_cover' => $user['profile_cover'] ?? '',
			];

			// La password è opzionale nell'aggiornamento
			if (!empty($_POST['password'])) {
				$data['password'] = trim($_POST['password']);
			}

			$errors = [];

			// Validazione Username
			if (empty($data['username'])) {
				$errors[] = 'L\'username è obbligatorio.';
			} elseif (strlen($data['username']) < 3 || strlen($data['username']) > 50) {
				$errors[] = 'L\'username deve essere tra 3 e 50 caratteri.';
			} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
				$errors[] = 'L\'username può contenere solo lettere, numeri e underscore.';
			}

			// Validazione Email
			if (empty($data['email'])) {
				$errors[] = 'L\'email è obbligatoria.';
			} elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
				$errors[] = 'Formato email non valido.';
			} elseif (strlen($data['email']) > 255) {
				$errors[] = 'L\'email è troppo lunga (max 255 caratteri).';
			}

			if (!empty($data['website']) && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
				$errors[] = 'Formato URL non valido.';
			}

			if ($data['comune_id'] !== '' && !ctype_digit((string) $data['comune_id'])) {
				$errors[] = 'Comune non valido.';
			}

			if (!ctype_digit((string) $data['role_id'])) {
				$errors[] = 'Ruolo non valido.';
			}

			// Validazione Password (solo se fornita)
			if (isset($data['password']) && strlen($data['password']) < 6) {
				$errors[] = 'La nuova password deve essere di almeno 6 caratteri.';
			}

			// Controlla se username o email sono già in uso da un altro utente (escludendo l'utente corrente)
			$existingUserByUsername = $this->userModel->findByUsername($data['username']);
			if ($existingUserByUsername && $existingUserByUsername['id'] != $id) {
				$errors[] = 'Username già in uso da un altro utente.';
			}
			$existingUserByEmail = $this->userModel->findByEmail($data['email']);
			if ($existingUserByEmail && $existingUserByEmail['id'] != $id) {
				$errors[] = 'Email già in uso da un altro utente.';
			}

			if (!empty($errors)) {
				Session::setFlash('error', implode('<br>', $errors));
				$this->view('admin/users/edit', ['user' => array_merge($user, $_POST), 'roles' => $this->userModel->getRoles(), 'error' => Session::getFlash('error'), 'csrf_token' => $_SESSION['csrf_token']], 'admin');
				return;
			}

			if (!empty($_FILES['avatar']['name'])) {
				$avatarPath = $this->handleAdminImageUpload($_FILES['avatar'], 'avatar', $data['avatar']);
				if ($avatarPath === null) {
					$errors[] = 'Impossibile caricare l\'immagine profilo.';
				} else {
					$data['avatar'] = $avatarPath;
				}
			}

			if (!empty($_FILES['profile_cover']['name'])) {
				$coverPath = $this->handleAdminImageUpload($_FILES['profile_cover'], 'covers', $data['profile_cover']);
				if ($coverPath === null) {
					$errors[] = 'Impossibile caricare la cover.';
				} else {
					$data['profile_cover'] = $coverPath;
				}
			}

			if (!empty($errors)) {
				Session::setFlash('error', implode('<br>', $errors));
				$this->view('admin/users/edit', ['user' => array_merge($user, $data), 'roles' => $this->userModel->getRoles(), 'error' => Session::getFlash('error'), 'csrf_token' => $_SESSION['csrf_token']], 'admin');
				return;
			}

			if ($this->userModel->update($id, $data)) {
				$this->userModel->updateProfileSettings((int) $id, $data['profile_settings']);
				$this->consentService->updateMarketingConsent((int) $id, (bool) $data['marketing_opted_in']);
				$this->consentService->recordAgeDeclaration((int) $id, (bool) $data['age_declared_adult']);
				$this->auditLogService->logAudit([
					'user_id' => $_SESSION['user_id'] ?? null,
					'action_type' => AuditLogActionType::USER_UPDATED,
					'entity_type' => 'user',
					'entity_id' => (int) $id,
					'success' => 1,
					'payload' => [
						'username' => $data['username'],
						'email' => $data['email'],
						'first_name' => $data['first_name'],
						'last_name' => $data['last_name'],
						'website' => $data['website'],
						'comune_id' => $data['comune_id'],
						'verified' => $data['verified'],
						'role_id' => $data['role_id'],
						'age_declared_adult' => $data['age_declared_adult'],
						'marketing_opted_in' => $data['marketing_opted_in'],
						'profile_settings' => $data['profile_settings'],
						'avatar_changed' => $data['avatar'] !== ($user['avatar'] ?? ''),
						'cover_changed' => $data['profile_cover'] !== ($user['profile_cover'] ?? ''),
						'password_changed' => !empty($data['password']),
					],
				]);
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode([
					'success' => true,
					'message' => 'Utente aggiornato con successo!',
				]);
				exit();
			} else {
				Session::setFlash('error', 'Errore durante l\'aggiornamento dell\'utente.');
				$user = $this->userModel->find($id);
				$this->view('admin/users/edit', ['user' => array_merge($user, $data), 'roles' => $this->userModel->getRoles(), 'error' => Session::getFlash('error'), 'csrf_token' => $_SESSION['csrf_token']], 'admin');
			}
		} else {
			header('Location: /admin/users');
			exit();
		}
	}

	private function normalizeSocial($social): string
	{
		if (!is_array($social)) {
			$social = [];
		}

		$normalized = [];
		foreach (['instagram', 'facebook', 'tiktok', 'youtube'] as $network) {
			$value = trim((string) ($social[$network] ?? ''));
			$normalized[$network] = mb_substr($value, 0, 255);
		}

		return json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
	}

	private function normalizeProfileSettings($settings): array
	{
		if (!is_array($settings)) {
			$settings = [];
		}

		$normalized = [];
		foreach ([
			'show_email', 'show_bio', 'show_comune', 'show_instagram',
			'show_facebook', 'show_tiktok', 'show_youtube', 'show_nome',
		] as $key) {
			$normalized[$key] = !empty($settings[$key]);
		}

		return $normalized;
	}

	private function handleAdminImageUpload(array $file, string $directory, string $currentPath = ''): ?string
	{
		if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 5 * 1024 * 1024) {
			return null;
		}

		$mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']);
		$extensions = [
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/gif' => 'gif',
			'image/webp' => 'webp',
		];
		if (!isset($extensions[$mime])) {
			return null;
		}

		$imageInfo = @getimagesize($file['tmp_name']);
		if ($imageInfo === false) {
			return null;
		}

		$baseName = bin2hex(random_bytes(16));
		$baseDirectory = APP_ROOT . '/public_assets/uploads/' . $directory . '/';
		if (!is_dir($baseDirectory) && !mkdir($baseDirectory, 0755, true) && !is_dir($baseDirectory)) {
			return null;
		}

		$sourcePath = $baseDirectory . $baseName . '.' . $extensions[$mime];
		$webpPath = $baseDirectory . $baseName . '.webp';
		if (!move_uploaded_file($file['tmp_name'], $sourcePath) || !$this->convertAdminImageToWebP($sourcePath, $webpPath)) {
			if (file_exists($sourcePath)) {
				@unlink($sourcePath);
			}
			return null;
		}

		if ($currentPath && file_exists(APP_ROOT . $currentPath)) {
			@unlink(APP_ROOT . $currentPath);
		}
		@unlink($sourcePath);

		return '/public_assets/uploads/' . $directory . '/' . $baseName . '.webp';
	}

	private function convertAdminImageToWebP(string $sourcePath, string $destinationPath): bool
	{
		$imageInfo = @getimagesize($sourcePath);
		if ($imageInfo === false) {
			return false;
		}

		$image = match ($imageInfo['mime']) {
			'image/jpeg' => @imagecreatefromjpeg($sourcePath),
			'image/png' => @imagecreatefrompng($sourcePath),
			'image/gif' => @imagecreatefromgif($sourcePath),
			'image/webp' => @imagecreatefromwebp($sourcePath),
			default => false,
		};
		if (!$image) {
			return false;
		}

		if ($imageInfo['mime'] === 'image/png' || $imageInfo['mime'] === 'image/webp') {
			imagepalettetotruecolor($image);
			imagealphablending($image, true);
			imagesavealpha($image, true);
		}

		$result = imagewebp($image, $destinationPath, 85);
		imagedestroy($image);
		return $result;
	}

	/**
	 * Elimina un utente
	 * @param array $params Contiene l'ID dell'utente
	 */
	public function deleteUser($params)
	{
		header('Content-Type: application/json; charset=utf-8');

		$id = $params[0] ?? null;

		if (!$id || !is_numeric($id)) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'ID utente non valido.']);
			exit();
		}

		// Protezione CSRF
		if (!$this->validateCsrfToken()) {
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Token CSRF non valido.']);
			exit();
		}

		if ($this->userModel->delete((int) $id, isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null, 'Admin account deletion')) {
			$this->auditLogService->logAudit([
				'user_id' => $_SESSION['user_id'] ?? null,
				'action_type' => AuditLogActionType::USER_DELETED,
				'entity_type' => 'user',
				'entity_id' => (int) $id,
				'success' => 1,
				'payload' => [
					'id' => (int) $id,
				],
			]);
			echo json_encode(['success' => true, 'message' => 'Utente eliminato con successo!']);
			exit();
		}

		http_response_code(500);
		echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'utente.']);
		exit();
	}
}
