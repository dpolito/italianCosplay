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
		$this->view('auth/login');
	}

	public function login()
	{
		error_log("DEBUG: Inizio metodo login."); // DEBUG POINT 1

		if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username_email']) && isset($_POST['password'])) {
			$identifier = trim($_POST['username_email']);
			$password = trim($_POST['password']);

			error_log("DEBUG: Dati POST ricevuti. Identifier: " . $identifier); // DEBUG POINT 2

			$user = $this->userModel->findByUsername($identifier);
			if (!$user) {
				$user = $this->userModel->findByEmail($identifier);
			}

			error_log("DEBUG: Utente trovato: " . ($user ? $user['username'] : 'Nessuno')); // DEBUG POINT 3

			if ($user && password_verify($password, $user['password'])) {
				error_log("DEBUG: Password verificata con successo."); // DEBUG POINT 4

				if (session_status() == PHP_SESSION_NONE) { // Questo controllo è buono, ma idealmente session_start() è già fatto
					session_start();
				}

				$_SESSION['user_id'] = $user['id'];
				$_SESSION['username'] = $user['username'];
				$_SESSION['role'] = $user['role'];

				error_log("DEBUG: Sessione impostata. Ruolo: " . $_SESSION['role']); // DEBUG POINT 5

				if ($user['role'] === 'admin') {
					error_log("DEBUG: Reindirizzo ad admin dashboard."); // DEBUG POINT 6
					header('Location: /admin/dashboard');
					exit();
				} else {
					error_log("DEBUG: Reindirizzo a dashboard utente."); // DEBUG POINT 7
					header('Location: /dashboard');
					exit();
				}

			} else {
				error_log("DEBUG: Credenziali non valide o password non corrispondente."); // DEBUG POINT 8
				$data = [
					'error' => 'Username/Email o password non validi.',
					'old_identifier' => $identifier
				];
				$this->view('auth/login', $data);
			}
		} else {
			error_log("DEBUG: Richiesta non POST o campi mancanti."); // DEBUG POINT 9
			header('Location: /login');
			exit();
		}
	}

	public function showRegisterForm()
	{
		$this->view('auth/register');
	}

	public function register()
	{
		// Logica di registrazione utente
		Session::setFlash('info', 'La registrazione è attualmente disabilitata.');
		$this->redirect('/register');
	}

	public function logout()
	{
		// session_start() dovrebbe essere chiamato una sola volta all'inizio del tuo index.php o file di bootstrap.
		// Rimosso il controllo e la chiamata a session_start() qui.
		session_unset(); // Rimuove tutte le variabili di sessione
		session_destroy(); // Distrugge la sessione

		header('Location: /'); // Reindirizza alla homepage o alla pagina di login
		exit();
	}
}
