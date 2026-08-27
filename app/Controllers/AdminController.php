<?php
namespace App\Controllers;


use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\AdCampaignService;
use App\Services\AdPositionService;
use App\Services\AdStatsService;
use App\Services\EventAnalyticsService;
use App\Services\SiteFeatureFlagService;
use App\Support\AuditLogActionType;
use App\ValueObjects\Money;

class AdminController extends Controller
{
	private User $userModel;
	private EventAnalyticsService $eventAnalyticsService;
	private AdStatsService $adStatsService;
	private AdCampaignService $adCampaignService;
	private AdPositionService $adPositionService;
	private SiteFeatureFlagService $siteFeatureFlagService;
	private AuditLogService $auditLogService;

	public function __construct()
	{
		$this->userModel = new User();
		$this->eventAnalyticsService = new EventAnalyticsService();
		$this->adStatsService = new AdStatsService();
		$this->adCampaignService = new AdCampaignService();
		$this->adPositionService = new AdPositionService();
		$this->siteFeatureFlagService = new SiteFeatureFlagService();
		$this->auditLogService = new AuditLogService();

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
		$stats_eventi = $this->eventAnalyticsService->getTrendMultiEvent();
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
			'trend_visite' => $stats_eventi,
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
			$data = [
				'username' => trim($_POST['username']),
				'email' => trim($_POST['email']),
				'role' => trim($_POST['role'] ?? 'user'),
				'role_id'  => trim($_POST['role_id'])
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
				// Recupera l'utente originale per ripopolare il form con i dati esistenti
				$user = $this->userModel->find($id);
				// Unisci i dati dell'utente originale con i dati POST per ripopolare i campi
				$this->view('admin/users/edit', ['user' => array_merge($user, $_POST), 'error' => Session::getFlash('error'), 'csrf_token' => $_SESSION['csrf_token']], 'admin'); // Specificato il layout 'admin'
				return;
			}

			if ($this->userModel->update($id, $data)) {
				$this->auditLogService->logAudit([
					'user_id' => $_SESSION['user_id'] ?? null,
					'action_type' => AuditLogActionType::USER_UPDATED,
					'entity_type' => 'user',
					'entity_id' => (int) $id,
					'success' => 1,
					'payload' => [
						'username' => $data['username'],
						'email' => $data['email'],
						'role_id' => $data['role_id'],
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
				$this->view('admin/users/edit', ['user' => array_merge($user, $data), 'error' => Session::getFlash('error'), 'csrf_token' => $_SESSION['csrf_token']], 'admin'); // Specificato il layout 'admin'
			}
		} else {
			header('Location: /admin/users');
			exit();
		}
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

		if ($this->userModel->delete($id)) {
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
