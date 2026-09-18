<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Mailer;
use App\Core\Session;
use App\Models\User;
use App\Services\ConsentService;
use App\Services\AuditLogService;
use App\Services\TelegramNotificationService;
use App\Support\AuditLogActionType;

class AuthController extends Controller
{
	private User $userModel;
	private ConsentService $consentService;
	private AuditLogService $auditLogService;
	private ?TelegramNotificationService $telegramNotificationService;

	public function __construct() {
		$this->userModel = new User();
		$this->consentService = new ConsentService();
		$this->auditLogService = new AuditLogService();
		$this->telegramNotificationService = $this->createTelegramNotificationService();
	}

	public function showLoginForm()
	{
		// Genera un token CSRF per il form di login, se non esiste già
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
		$this->view('home/auth/login', ['csrf_token' => $_SESSION['csrf_token']]); // Passa il token alla vista
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

		$this->auditLogService->logAuth([
			'user_id' => (int) $user['id'],
			'identifier' => $identifier,
			'action_type' => AuditLogActionType::LOGIN_SUCCESS,
			'success' => 1,
			'payload' => [
				'method' => 'password',
				'redirect' => $this->userModel->hasPermission($user['id'], 'view_admin_dashboard') ? '/admin/dashboard' : '/dashboard'
			]
		]);

		// ✅ Redirect intelligente basato sui permessi
		if ($this->userModel->hasPermission($user['id'], 'view_admin_dashboard')) {
			header('Location: /admin/dashboard');
		} else {
			header('Location: /dashboard');
		}

		exit();
	}

	public function showRegisterForm()
	{
		$this->requireFeature('enable_user_registration', 'La registrazione utenti è temporaneamente disattivata.');
		// Genera un token CSRF per il form di registrazione, se non esiste già
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}

		$this->view('home/auth/register', [
			'csrf_token' => $_SESSION['csrf_token'],
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
				'old' => $_POST,
				'csrf_token' => $_SESSION['csrf_token'],
				'pageTitle' => 'Registrati su ItalianCosplay',
				'metaDescription' => 'Crea il tuo account su ItalianCosplay per salvare eventi, seguire i cosplay preferiti e accedere alla tua area personale.',
				'canonicalUrl' => URL_ROOT_SITE . '/register',
				'noindex' => true,
			]);
			return;
		}

		$username = trim($_POST['username']);
		$email = trim($_POST['email']);
		$password = trim($_POST['password']);
		$password_confirm = trim($_POST['password_confirm']);
		$ageDeclarationAccepted = !empty($_POST['age_declaration']);
		$newsletterOptIn = !empty($_POST['newsletter_opt_in']);
		$errors = [];

		// Validazioni
		if ($username === '' || $email === '' || $password === '' || $password_confirm === '') {
			$errors[] = 'Compila tutti i campi.';
		}
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$errors[] = 'Email non valida.';
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
				'old' => $_POST,
				'age_declaration' => $ageDeclarationAccepted,
				'newsletter_opt_in' => $newsletterOptIn,
				'csrf_token' => $_SESSION['csrf_token'],
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
}
