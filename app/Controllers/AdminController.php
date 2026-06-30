<?php
namespace App\Controllers;


use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Services\EventAnalyticsService;

class AdminController extends Controller
{
	private User $userModel;
	private EventAnalyticsService $eventAnalyticsService;

	public function __construct()
	{
		$this->userModel = new User();
		$this->eventAnalyticsService = new EventAnalyticsService();

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
		$this->view('admin/dashboard', [
			'trend_visite' => $stats_eventi,
		], 'admin'); // Specificato il layout 'admin'
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
				Session::setFlash('success', 'Utente creato con successo!');
				header('Location: /admin/users');
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
			$id = $params[0] ?? null;
			header('Location: /admin/users/edit/' . $id);
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
				Session::setFlash('success', 'Utente aggiornato con successo!');
				header('Location: /admin/users');
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
		// Protezione CSRF
		if (!$this->validateCsrfToken()) {
			header('Location: /admin/users'); // Reindirizza alla lista utenti
			exit();
		}

		$id = $params[0] ?? null;

		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID utente non valido.');
			header('Location: /admin/users');
			exit();
		}

		if ($this->userModel->delete($id)) {
			Session::setFlash('success', 'Utente eliminato con successo!');
		} else {
			Session::setFlash('error', 'Errore durante l\'eliminazione dell\'utente.');
		}

		header('Location: /admin/users');
		exit();
	}
}
