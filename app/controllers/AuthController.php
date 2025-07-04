<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/models/User.php';
require_once APP_ROOT . '/app/core/Session.php';

class AuthController extends Controller{
	private User $userModel;

	public function __construct(){
		$this->userModel = new User();
	}

	public function showLoginForm(){
		// Questa vista dovrebbe mostrare il modulo di login
		$this->view('auth/login'); // Assicurati che questo sia il percorso corretto per la tua vista di login
	}

	public function login(){
		if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username_email']) && isset($_POST['password'])){
			$identifier = trim($_POST['username_email']);
			$password = trim($_POST['password']);
			$user = $this->userModel->findByUsername($identifier);
			if(!$user){
				$user = $this->userModel->findByEmail($identifier);
			}
			if($user && password_verify($password, $user['password'])){
				if(session_status() == PHP_SESSION_NONE){
					session_start();
				}
				$_SESSION['user_id'] = $user['id'];
				$_SESSION['username'] = $user['username'];
				$_SESSION['role'] = $user['role'];
				if($user['role'] === 'admin'){
					header('Location: /admin/dashboard');
					exit();
				}else{
					header('Location: /dashboard');
					exit();
				}
			}else{
				// Credenziali non valide
				$data = [
					'error'          => 'Username/Email o password non validi.',
					'old_identifier' => $identifier
				];
				// Ricarica la vista di login con un messaggio di errore
				// *** MODIFICA QUI: Assicurati che il percorso della vista sia lo stesso del showLoginForm() ***
				$this->view('auth/login', $data);
			}
		}else{
			header('Location: /login');
			exit();
		}
	}

	public function showRegisterForm(){
		$this->view('auth/register');
	}

	public function register(){
		Session::setFlash('info', 'La registrazione è attualmente disabilitata.');
		$this->redirect('/register');
	}

	public function logout(){
		if(session_status() == PHP_SESSION_NONE){
			session_start();
		}
		session_unset();
		session_destroy();
		header('Location: /');
		exit();
	}
}
