<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Mailer;
use App\Core\Session;
use App\Models\User;
use App\Services\ConsentService;
use App\Services\AuditLogService;
use App\Services\PendingUserActionService;
use App\Services\UserInvitationService;
use App\Services\TelegramNotificationService;
use App\Support\AuditLogActionType;

class AuthController extends Controller
{
	private User $userModel;
	private ConsentService $consentService;
	private AuditLogService $auditLogService;
	private PendingUserActionService $pendingUserActionService;
	private UserInvitationService $userInvitationService;
	private ?TelegramNotificationService $telegramNotificationService;

	public function __construct() {
		$this->userModel = new User();
		$this->consentService = new ConsentService();
		$this->auditLogService = new AuditLogService();
		$this->pendingUserActionService = new PendingUserActionService();
		$this->userInvitationService = new UserInvitationService();
		$this->telegramNotificationService = $this->createTelegramNotificationService();
	}

	public function showLoginForm()
	{
		// Genera un token CSRF per il form di login, se non esiste già
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
		$this->view('home/auth/login', [
			'csrf_token' => $_SESSION['csrf_token'],
			'pendingActionContext' => $this->pendingUserActionService->getLoginContext(),
		]); // Passa il token alla vista
	}

	/*public function login()
	{
		// Protezione CSRF
		if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
			Session::setFlash('error', 'Errore di sicurezza: richiesta non valida (CSRF).');
			header('Location: /login'); // Reindirizza al form di login
			exit();
		}

		// Controlla se la richiesta è di tipo POST e se i campi sono stati inviati
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username_email']) && isset($_POST['password'])) {
			$identifier = trim($_POST['username_email']); // Può essere username o email
			$password = trim($_POST['password']);

			// 1. Cerca l'utente per username o email
			$user = $this->userModel->findByUsername($identifier);
			if (!$user) {
				$user = $this->userModel->findByEmail($identifier);
			}

			// 2. Verifica se l'utente esiste e la password è corretta
			if ($user && password_verify($password, $user['password'])) {
				// Password corretta! Salva i dati dell'utente nella sessione.
				// session_start() dovrebbe essere chiamato una sola volta all'inizio del tuo index.php o file di bootstrap
				// Se Session::start() gestisce già questo, puoi rimuovere session_start() da qui.
				if (session_status() == PHP_SESSION_NONE) {
					session_start();
				}


				$_SESSION['user_id'] = $user['id'];
				$_SESSION['username'] = $user['username'];
				$_SESSION['role'] = $user['role']; // Salva il ruolo dell'utente nella sessione

				// 3. Reindirizza in base al ruolo
				if ($user['role'] === 'admin') {
					header('Location: /admin/dashboard'); // Reindirizza all'area admin
					exit();
				} else {
					header('Location: /dashboard'); // Reindirizza all'area utente normale
					exit();
				}

			} else {
				// Credenziali non valide
				$data = [
					'error' => 'Username/Email o password non validi.',
					'old_identifier' => $identifier,
					'csrf_token' => $_SESSION['csrf_token'] // Passa il token anche in caso di errore
				];
				$this->view('home/auth/login', $data);
			}
		} else {
			// Se non è una richiesta POST o i campi non sono settati, reindirizza alla pagina di login
			header('Location: /login');
			exit();
		}
	}*/
	public function login()
	{
		// ✅ Solo POST
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Location: /login');
			exit();
		}

		// ✅ Protezione CSRF
		if (
			empty($_POST['csrf_token']) ||
			empty($_SESSION['csrf_token']) ||
			!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
		) {
			$this->auditLogService->logAuth([
				'user_id' => null,
				'identifier' => $_POST['username_email'] ?? null,
				'action_type' => AuditLogActionType::LOGIN_FAILED,
				'success' => 0,
				'failure_reason' => 'csrf_invalid',
				'payload' => [
					'method' => 'password'
				]
			]);

			$this->view('home/auth/login', [
				'error' => 'Errore di sicurezza: richiesta non valida (CSRF).',
				'csrf_token' => $_SESSION['csrf_token']
			]);
			exit();
		}

		// ✅ Validazione input
		$identifier = trim($_POST['username_email'] ?? '');
		$password   = trim($_POST['password'] ?? '');

		if ($identifier === '' || $password === '') {
			$this->auditLogService->logAuth([
				'user_id' => null,
				'identifier' => $identifier ?: null,
				'action_type' => AuditLogActionType::LOGIN_FAILED,
				'success' => 0,
				'failure_reason' => 'missing_fields',
				'payload' => [
					'method' => 'password'
				]
			]);

			$this->view('home/auth/login', [
				'error' => 'Compila tutti i campi.',
				'old_identifier' => $identifier,
				'csrf_token' => $_SESSION['csrf_token']
			]);
			return;
		}

		// ✅ Cerca utente (username o email)
		$user = $this->userModel->findByUsername($identifier) ?: $this->userModel->findByEmail($identifier);

		if (!$user || !password_verify($password, $user['password'])) {
			$this->auditLogService->logAuth([
				'user_id' => $user['id'] ?? null,
				'identifier' => $identifier,
				'action_type' => AuditLogActionType::LOGIN_FAILED,
				'success' => 0,
				'failure_reason' => 'invalid_credentials',
				'payload' => [
					'method' => 'password'
				]
			]);

			$this->view('home/auth/login', [
				'error' => 'Username/Email o password non validi.',
				'old_identifier' => $identifier,
				'csrf_token' => $_SESSION['csrf_token']
			]);
			return;
		}

		if (!empty($user['deactivated_at'])) {
			$this->auditLogService->logAuth([
				'user_id' => (int) $user['id'],
				'identifier' => $identifier,
				'action_type' => AuditLogActionType::LOGIN_FAILED,
				'success' => 0,
				'failure_reason' => 'account_deactivated',
				'payload' => [
					'method' => 'password'
				]
			]);

			$this->view('home/auth/login', [
				'error' => 'Account non disponibile. Contatta il supporto se pensi sia un errore.',
				'old_identifier' => $identifier,
				'csrf_token' => $_SESSION['csrf_token']
			]);
			return;
		}

		// ❌ Controlla se l'utente ha verificato l'email
		if ((int)$user['verified'] === 0) {
			$this->auditLogService->logAuth([
				'user_id' => (int) $user['id'],
				'identifier' => $identifier,
				'action_type' => AuditLogActionType::LOGIN_FAILED,
				'success' => 0,
				'failure_reason' => 'email_not_verified',
				'payload' => [
					'method' => 'password'
				]
			]);

			$this->view('home/auth/login', [
				'error' => 'Devi verificare la tua email prima di accedere. Controlla la tua casella di posta.',
				'old_identifier' => $identifier,
				'csrf_token' => $_SESSION['csrf_token']
			]);
			return;
		}

		// ✅ Login OK — rigenera sessione (ANTI session fixation)
		session_regenerate_id(true);
		$_SESSION['user_id'] = (int)$user['id'];
		$this->userModel->recordSuccessfulLogin((int) $user['id']);

		$pendingResult = $this->pendingUserActionService->consumeForUser((int) $user['id']);
		$defaultRedirect = $this->userModel->hasPermission($user['id'], 'view_admin_dashboard') ? '/admin/dashboard' : '/dashboard';
		$redirect = $pendingResult['return_url'] ?? $defaultRedirect;
		if ($pendingResult !== null) {
			Session::setFlash($pendingResult['success'] ? 'success' : 'error', (string) $pendingResult['message']);
			if ($pendingResult['success']) {
				Session::setFlash('pending_action_type', (string) $pendingResult['type']);
			}
		}

		$this->auditLogService->logAuth([
			'user_id' => (int) $user['id'],
			'identifier' => $identifier,
			'action_type' => AuditLogActionType::LOGIN_SUCCESS,
			'success' => 1,
			'payload' => [
				'method' => 'password',
				'redirect' => $redirect,
				'pending_action' => $pendingResult['type'] ?? null,
			]
		]);

		header('Location: ' . $redirect);

		exit();
	}

	public function showRegisterForm()
	{
		$this->requireFeature('enable_user_registration', 'La registrazione utenti è temporaneamente disattivata.');
		// Genera un token CSRF per il form di registrazione, se non esiste già
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}

		$pendingInvitation = $this->getPendingInvitationRegistrationData();
		$old = $pendingInvitation ? ['email' => $pendingInvitation['email']] : [];

		$this->view('home/auth/register', [
			'csrf_token' => $_SESSION['csrf_token'],
			'old' => $old,
			'pendingInvitation' => $pendingInvitation,
			'pendingActionContext' => $this->pendingUserActionService->getLoginContext(),
			'pageTitle' => 'Registrati su ItalianCosplay',
			'metaDescription' => 'Crea il tuo account su ItalianCosplay per salvare eventi, seguire i cosplay preferiti e accedere alla tua area personale.',
			'canonicalUrl' => URL_ROOT_SITE . '/register',
			'noindex' => true,
		]); // Passa il token alla vista
	}

	public function agendaLanding(): void
	{
		$this->requireFeature('enable_user_registration', 'La registrazione utenti è temporaneamente disattivata.');

		$this->view('home/auth/agenda-landing', [
			'pageTitle' => 'Agenda cosplay personale: salva eventi vicini | ItalianCosplay',
			'metaDescription' => 'Crea la tua agenda cosplay gratuita: salva eventi, segui le zone che frequenti e ritrova gli appuntamenti che non vuoi perdere.',
			'canonicalUrl' => URL_ROOT_SITE . '/agenda-cosplay',
		]);
	}

	public function cosplanLanding(): void
	{
		$this->requireFeature('enable_user_registration', 'La registrazione utenti è temporaneamente disattivata.');

		$this->view('home/auth/cosplan-landing', [
			'pageTitle' => 'Cosplan gratis: organizza cosplay ed eventi | ItalianCosplay',
			'metaDescription' => 'Organizza gratis il tuo cosplan su ItalianCosplay: pianifica personaggio, costume, preparazione ed eventi cosplay dove portarlo.',
			'canonicalUrl' => URL_ROOT_SITE . '/cosplan',
		]);
	}

	public function register()
	{
		$this->requireFeature('enable_user_registration', 'La registrazione utenti è temporaneamente disattivata.');
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$csrf_token = bin2hex(random_bytes(32));
			$_SESSION['csrf_token'] = $csrf_token;
			$this->view('home/auth/register', [
				'csrf_token' => $csrf_token,
				'pendingActionContext' => $this->pendingUserActionService->getLoginContext(),
				'pageTitle' => 'Registrati su ItalianCosplay',
				'metaDescription' => 'Crea il tuo account su ItalianCosplay per salvare eventi, seguire i cosplay preferiti e accedere alla tua area personale.',
				'canonicalUrl' => URL_ROOT_SITE . '/register',
				'noindex' => true,
			]);
			return;
		}

		// Protezione CSRF
		if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {

			$this->view('home/auth/register', [
				'errors' => 'Errore di sicurezza: richiesta non valida (CSRF).',
				'old' => $this->safeRegistrationOldInput($_POST),
				'csrf_token' => $_SESSION['csrf_token'],
				'pendingActionContext' => $this->pendingUserActionService->getLoginContext(),
				'pageTitle' => 'Registrati su ItalianCosplay',
				'metaDescription' => 'Crea il tuo account su ItalianCosplay per salvare eventi, seguire i cosplay preferiti e accedere alla tua area personale.',
				'canonicalUrl' => URL_ROOT_SITE . '/register',
				'noindex' => true,
			]);
			return;
		}

		$username = trim($_POST['username']);
		$email = trim($_POST['email']);
		$pendingInvitation = $this->getPendingInvitationRegistrationData();
		if ($pendingInvitation) {
			$email = (string) $pendingInvitation['email'];
		}
		$password = trim($_POST['password']);
		$password_confirm = trim($_POST['password_confirm']);
		$registrationWebsite = trim((string) ($_POST['registration_website'] ?? ''));
		$ageDeclarationAccepted = !empty($_POST['age_declaration']);
		$newsletterOptIn = !empty($_POST['newsletter_opt_in']);
		$errors = [];

		if ($registrationWebsite !== '') {
			$this->auditLogService->logAudit([
				'user_id' => null,
				'action_type' => AuditLogActionType::USER_CREATED,
				'entity_type' => 'user',
				'entity_id' => null,
				'success' => 0,
				'payload' => [
					'email' => $email,
					'username' => $username,
					'reason' => 'registration_honeypot_filled',
				],
				'error_message' => 'registration_honeypot_filled',
			]);

			$this->view('home/auth/login', [
				'success' => 'Registrazione completata! Controlla la tua email per verificare il tuo account.',
				'csrf_token' => $_SESSION['csrf_token']
			]);
			return;
		}

		// Validazioni
		if ($username === '' || $email === '' || $password === '' || $password_confirm === '') {
			$errors[] = 'Compila tutti i campi.';
		}
		if (!$this->isValidPublicUsername($username)) {
			$errors[] = 'Scegli uno username leggibile: usa 3-30 caratteri tra lettere, numeri, punto, trattino o underscore.';
		}
		if ($this->looksLikeSensitiveUsername($username, $email, $password, $password_confirm)) {
			$errors[] = 'Lo username non può coincidere con email o password. Scegli un nome pubblico diverso.';
		}
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$errors[] = 'Email non valida.';
		}
		if ($pendingInvitation && strtolower(trim((string) ($_POST['email'] ?? ''))) !== strtolower((string) $pendingInvitation['email'])) {
			$errors[] = 'L’email deve corrispondere all’invito ricevuto.';
		}
		if (strlen($password) < 8) {
			$errors[] = 'La password deve essere di almeno 8 caratteri.';
		}
		if ($password !== $password_confirm) {
			$errors[] = 'Le password non coincidono.';
		}
		if (empty($_POST['privacy_accept'])) {
			$errors[] = 'Devi accettare l\'informativa privacy per completare la registrazione.';
		}
		if (!$ageDeclarationAccepted) {
			$errors[] = 'Devi dichiarare di avere almeno 18 anni per completare la registrazione.';
		}

		// Controlla username/email già esistenti
		$userModel = new User();
		if ($userModel->findByUsername($username)) $errors[] = 'Username già utilizzato.';
		if ($userModel->findByEmail($email)) $errors[] = 'Email già registrata.';

		if (!empty($errors)) {
			$this->view('home/auth/register', [
				'errors' => $errors,
				'old' => $this->safeRegistrationOldInput($_POST),
				'pendingInvitation' => $pendingInvitation,
				'age_declaration' => $ageDeclarationAccepted,
				'newsletter_opt_in' => $newsletterOptIn,
				'csrf_token' => $_SESSION['csrf_token'],
				'pendingActionContext' => $this->pendingUserActionService->getLoginContext(),
				'pageTitle' => 'Registrati su ItalianCosplay',
				'metaDescription' => 'Crea il tuo account su ItalianCosplay per salvare eventi, seguire i cosplay preferiti e accedere alla tua area personale.',
				'canonicalUrl' => URL_ROOT_SITE . '/register',
				'noindex' => true,
			]);
			return;
		}

		// Genera token verifica
		$verification_token = bin2hex(random_bytes(32));

		$created = $userModel->create([
			'username' => $username,
			'email' => $email,
			'password' => $password,
			'role_id' => 3, // default user
			'verified' => 0,
			'verification_token' => $verification_token
		]);

		if ($created && $this->telegramNotificationService !== null) {
			$this->telegramNotificationService->sendMessage(
				sprintf(
					'Nuovo utente creato: %s (%s)',
					$username,
					$email
				)
			);
		}

		if ($created) {
			$createdUser = $userModel->findByEmail($email);
			if (is_array($createdUser) && isset($createdUser['id'])) {
				$this->consentService->storeRegistrationConsents(
					(int) $createdUser['id'],
					$newsletterOptIn,
					$ageDeclarationAccepted,
					$_SERVER['REMOTE_ADDR'] ?? null,
					$_SERVER['HTTP_USER_AGENT'] ?? null
				);
				$this->userInvitationService->acceptPendingForRegisteredUser((int) $createdUser['id'], $email);
				unset($_SESSION['pending_user_invitation']);
			}
		}

		// Invia l'email di verifica usando il template dedicato alla registrazione.
		$verificationLink = 'https://www.italiancosplay.it/verify/' . $verification_token;
		$template = file_get_contents(__DIR__ . '/../views/email_template_registrazione.php');
		$template = str_replace(
			['{{nome}}', '{{link_conferma}}'],
			[
				htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($verificationLink, ENT_QUOTES, 'UTF-8'),
			],
			$template
		);
		$mailer = new Mailer();
		$mailer->send($email, $username, 'Conferma la tua email', $template, null, [Mailer::TAG_REGISTRATION]);

		$this->view('home/auth/login', [
			'success' => 'Registrazione completata! Controlla la tua email per verificare il tuo account.',
			'old' => $_POST,
			'age_declaration' => $ageDeclarationAccepted,
			'newsletter_opt_in' => $newsletterOptIn,
			'csrf_token' => $_SESSION['csrf_token']
		]);
	}
	public function verify($token)
	{
		if (empty($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}

		$verificationToken = is_array($token) ? trim((string) ($token[0] ?? '')) : trim((string) $token);
		if ($verificationToken === '') {
			Session::setFlash('error', 'Link di verifica non valido o incompleto.');
			$this->view('home/auth/login', [
				'error' => 'Link di verifica non valido o incompleto.',
				'csrf_token' => $_SESSION['csrf_token']
			]);
			return;
		}

		$userModel = new User();
		$user = $userModel->findByVerificationToken($verificationToken);

		if (!$user) {
			Session::setFlash('error', 'Token di verifica non valido.');
			$this->view('home/auth/login', [
				'error' => 'Token di verifica non valido.',
				'csrf_token' => $_SESSION['csrf_token']
			]);
			return;
		}

		$userModel->verifyUser($user['id']);
		if ($this->telegramNotificationService !== null) {
			$this->telegramNotificationService->sendMessage(
				sprintf(
					'Nuovo utente %s ha confermato l\'email',
					$user['username']
				)
			);
		}
		$this->view('home/auth/login', [
			'success' => 'Email verificata! Ora puoi accedere.',
			'csrf_token' => $_SESSION['csrf_token']
		]);
		Session::setFlash('success', 'Email verificata! Ora puoi accedere.');
	}

	public function logout()
	{
		// CSRF check
		if (
			!isset($_POST['csrf_token']) ||
			$_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')
		) {
			header('HTTP/1.1 403 Forbidden');
			die('CSRF token non valido');
		}

		$this->auditLogService->logAuth([
			'user_id' => $_SESSION['user_id'] ?? null,
			'identifier' => $_SESSION['username'] ?? null,
			'action_type' => AuditLogActionType::LOGOUT,
			'success' => 1,
			'payload' => [
				'method' => 'session_logout'
			]
		]);

		Session::destroy();
		header('Location: /');
		exit();
	}

	private function createTelegramNotificationService(): ?TelegramNotificationService
	{
		$botToken = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
		$chatId = defined('TELEGRAM_CHAT_ID') ? TELEGRAM_CHAT_ID : '';

		if (!is_string($botToken) || !is_string($chatId) || trim($botToken) === '' || trim($chatId) === '') {
			return null;
		}

		return new TelegramNotificationService($botToken, $chatId);
	}

	private function safeRegistrationOldInput(array $input): array
	{
		$pendingInvitation = $this->getPendingInvitationRegistrationData();
		return [
			'username' => (string) ($input['username'] ?? ''),
			'email' => (string) ($pendingInvitation['email'] ?? $input['email'] ?? ''),
			'privacy_accept' => !empty($input['privacy_accept']) ? '1' : '',
			'age_declaration' => !empty($input['age_declaration']) ? '1' : '',
			'newsletter_opt_in' => !empty($input['newsletter_opt_in']) ? '1' : '',
		];
	}

	private function getPendingInvitationRegistrationData(): ?array
	{
		$pendingInvitation = $_SESSION['pending_user_invitation'] ?? null;
		if (!is_array($pendingInvitation) || empty($pendingInvitation['token']) || empty($pendingInvitation['email'])) {
			return null;
		}

		$invitation = $this->userInvitationService->findPendingByToken((string) $pendingInvitation['token']);
		if (!$invitation || strtolower((string) $invitation['email']) !== strtolower((string) $pendingInvitation['email'])) {
			unset($_SESSION['pending_user_invitation']);
			return null;
		}

		return [
			'token' => (string) $pendingInvitation['token'],
			'email' => (string) $pendingInvitation['email'],
			'inviter_username' => (string) ($pendingInvitation['inviter_username'] ?? $invitation['inviter_username'] ?? ''),
		];
	}

	private function isValidPublicUsername(string $username): bool
	{
		if (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $username)) {
			return false;
		}

		if ($this->looksLikeGeneratedUsername($username)) {
			return false;
		}

		return true;
	}

	private function looksLikeSensitiveUsername(string $username, string $email, string $password, string $passwordConfirm): bool
	{
		$normalizedUsername = mb_strtolower($username);
		$emailLocalPart = mb_strtolower((string) strtok($email, '@'));

		return hash_equals($username, $password)
			|| hash_equals($username, $passwordConfirm)
			|| $normalizedUsername === mb_strtolower($email)
			|| ($emailLocalPart !== '' && $normalizedUsername === $emailLocalPart);
	}

	private function looksLikeGeneratedUsername(string $username): bool
	{
		if (!preg_match('/^[A-Za-z0-9]{16,}$/', $username)) {
			return false;
		}

		preg_match_all('/[aeiou]/i', $username, $vowels);
		preg_match_all('/[A-Z]/', $username, $uppercase);
		preg_match_all('/[a-z]/', $username, $lowercase);
		preg_match_all('/[0-9]/', $username, $digits);
		preg_match_all('/[A-Z][a-z]|[a-z][A-Z]/', $username, $caseSwitches);

		$length = strlen($username);
		$vowelRatio = count($vowels[0]) / $length;
		$hasMixedCase = count($uppercase[0]) > 0 && count($lowercase[0]) > 0;
		$hasManyCaseSwitches = count($caseSwitches[0]) >= 4;

		return $hasMixedCase
			&& $hasManyCaseSwitches
			&& $vowelRatio < 0.28
			&& count($digits[0]) <= 2;
	}
}
