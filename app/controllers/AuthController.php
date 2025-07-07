<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/models/User.php';
require_once APP_ROOT . '/app/core/Session.php'; // Assumiamo che questa classe gestisca session_start() globalmente

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

	public function login()
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
		// Protezione CSRF
		if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
			Session::setFlash('error', 'Errore di sicurezza: richiesta non valida (CSRF).');
			header('Location: /register'); // Reindirizza al form di registrazione
			exit();
		}

		// Logica di registrazione utente
		Session::setFlash('info', 'La registrazione è attualmente disabilitata.');
		$this->redirect('/register');
	}

	public function logout()
	{
		session_unset(); // Rimuove tutte le variabili di sessione
		session_destroy(); // Distrugge la sessione

		header('Location: /'); // Reindirizza alla homepage o alla pagina di login
		exit();
	}
}
