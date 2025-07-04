<?php

require_once APP_ROOT . '/app/core/Controller.php';
// require_once APP_ROOT . '/app/models/User.php'; // Se hai un modello User
require_once APP_ROOT . '/app/core/Session.php';

class AuthController extends Controller
{
	// private $userModel;

	// public function __construct() {
	//     $this->userModel = new User();
	// }

	public function showLoginForm()
	{
		$this->view('auth/login');
	}

	public function login()
	{
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$username = $_POST['username'] ?? '';
			$password = $_POST['password'] ?? '';

			// Qui dovresti verificare le credenziali con il tuo modello User
			// Esempio fittizio:
			if ($username === 'admin' && $password === 'password123') { // NON USARE MAI QUESTA LOGICA IN PRODUZIONE
				Session::set('user_id', 1);
				Session::set('user_role', 'admin');
				Session::setFlash('success', 'Login effettuato con successo!');
				$this->redirect('/'); // Reindirizza alla home o alla dashboard admin
			} else {
				Session::setFlash('error', 'Credenziali non valide.');
				$this->redirect('/login');
			}
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
		Session::destroy();
		Session::setFlash('success', 'Logout effettuato con successo.');
		$this->redirect('/');
	}
}
