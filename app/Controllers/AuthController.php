<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Mailer;
use App\Core\Session;
use App\Models\User;

class AuthController extends Controller
{
	private User $userModel;

	public function __construct() {
		$this->userModel = new User();
	}

	public function showLoginForm()
	{
		// Genera un token CSRF per il form di login, se non esiste già
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
		$this->view('auth/login', ['csrf_token' => $_SESSION['csrf_token']]); // Passa il token alla vista
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
				$this->view('auth/login', $data);
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
			$this->view('auth/login', [
				'error' => 'Errore di sicurezza: richiesta non valida (CSRF).',
				'csrf_token' => $_SESSION['csrf_token']
			]);
			exit();
		}

		// ✅ Validazione input
		$identifier = trim($_POST['username_email'] ?? '');
		$password   = trim($_POST['password'] ?? '');

		if ($identifier === '' || $password === '') {
			$this->view('auth/login', [
				'error' => 'Compila tutti i campi.',
				'old_identifier' => $identifier,
				'csrf_token' => $_SESSION['csrf_token']
			]);
			return;
		}

		// ✅ Cerca utente (username o email)
		$user = $this->userModel->findByUsername($identifier) ?: $this->userModel->findByEmail($identifier);

		if (!$user || !password_verify($password, $user['password'])) {
			$this->view('auth/login', [
				'error' => 'Username/Email o password non validi.',
				'old_identifier' => $identifier,
				'csrf_token' => $_SESSION['csrf_token']
			]);
			return;
		}

		// ❌ Controlla se l'utente ha verificato l'email
		if ((int)$user['verified'] === 0) {
			$this->view('auth/login', [
				'error' => 'Devi verificare la tua email prima di accedere. Controlla la tua casella di posta.',
				'old_identifier' => $identifier,
				'csrf_token' => $_SESSION['csrf_token']
			]);
			return;
		}

		// ✅ Login OK — rigenera sessione (ANTI session fixation)
		$_SESSION['user_id'] = (int)$user['id'];

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
		// Genera un token CSRF per il form di registrazione, se non esiste già
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
		$this->view('auth/register', ['csrf_token' => $_SESSION['csrf_token']]); // Passa il token alla vista
	}

	public function register()
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$csrf_token = bin2hex(random_bytes(32));
			$_SESSION['csrf_token'] = $csrf_token;
			$this->view('auth/register', ['csrf_token' => $csrf_token]);
			return;
		}

		// Protezione CSRF
		if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {

			$this->view('auth/register', [
				'errors' => 'Errore di sicurezza: richiesta non valida (CSRF).',
				'old' => $_POST,
				'csrf_token' => $_SESSION['csrf_token']
			]);
			return;
		}

		$username = trim($_POST['username']);
		$email = trim($_POST['email']);
		$password = trim($_POST['password']);
		$password_confirm = trim($_POST['password_confirm']);
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

		// Controlla username/email già esistenti
		$userModel = new User();
		if ($userModel->findByUsername($username)) $errors[] = 'Username già utilizzato.';
		if ($userModel->findByEmail($email)) $errors[] = 'Email già registrata.';

		if (!empty($errors)) {
			$this->view('auth/register', [
				'errors' => $errors,
				'old' => $_POST,
				'csrf_token' => $_SESSION['csrf_token']
			]);
			return;
		}

		// Genera token verifica
		$verification_token = bin2hex(random_bytes(32));

		$userModel->create([
			'username' => $username,
			'email' => $email,
			'password' => $password,
			'role_id' => 3, // default user
			'verified' => 0,
			'verification_token' => $verification_token
		]);

		// Invia email di verifica (funzione mail personalizzata)
		$verificationLink = 'https://www.italiancosplay.it/verify/' . $verification_token;
		$mailer = new Mailer();
		$mailer->send($email, $email, "Verifica la tua email", "Clicca qui per verificare il tuo account: $verificationLink");

		$this->view('auth/login', [
			'success' => 'Registrazione completata! Controlla la tua email per verificare il tuo account.',
			'old' => $_POST,
			'csrf_token' => $_SESSION['csrf_token']
		]);
	}
	public function verify($token)
	{
		$userModel = new User();
		$user = $userModel->findByVerificationToken($token[1]);

		if (!$user) {
			Session::setFlash('error', 'Token di verifica non valido.');
			$this->view('auth/login', [
				'error' => 'Token di verifica non valido.',
				'csrf_token' => $_SESSION['csrf_token']
			]);
			return;
		}

		$userModel->verifyUser($user['id']);
		$this->view('auth/login', [
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

		Session::destroy();
		header('Location: /');
		exit();
	}
}
