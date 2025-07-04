<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/models/User.php';
require_once APP_ROOT . '/app/core/Session.php'; // Per i messaggi flash

class AdminController extends Controller
{
	private User $userModel;

	public function __construct()
	{
		$this->userModel = new User();
		// Protezione dell'area admin:
		// Assicurati che l'utente sia loggato e abbia il ruolo 'admin'.
		// Questo è un controllo di base, puoi renderlo più robusto.
		if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
			Session::setFlash('error', 'Accesso non autorizzato all\'area amministrativa.');
			header('Location: /login'); // Reindirizza alla pagina di login
			exit();
		}
	}

	/**
	 * Mostra la dashboard dell'amministratore (puoi personalizzarla in seguito)
	 */
	public function dashboard()
	{
		$this->view('admin/dashboard'); // Crea questa vista in seguito
	}

	/**
	 * Mostra l'elenco di tutti gli utenti
	 */
	public function users()
	{
		$users = $this->userModel->getAllUsers();
		$this->view('admin/users/index', ['users' => $users]);
	}

	/**
	 * Mostra il modulo per creare un nuovo utente
	 */
	public function createUser()
	{
		$this->view('admin/users/create');
	}

	/**
	 * Gestisce l'invio del modulo per la creazione di un utente
	 */
	public function storeUser()
	{
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$data = [
				'username' => trim($_POST['username']),
				'email' => trim($_POST['email']),
				'password' => trim($_POST['password']),
				'role' => trim($_POST['role'] ?? 'user') // Default 'user' se non specificato
			];

			// Validazione di base (puoi espanderla)
			if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
				Session::setFlash('error', 'Si prega di compilare tutti i campi obbligatori.');
				$this->view('admin/users/create', $data); // Ricarica il form con i dati inseriti e l'errore
				return;
			}

			// Controlla se username o email esistono già
			if ($this->userModel->findByUsername($data['username'])) {
				Session::setFlash('error', 'Username già in uso.');
				$this->view('admin/users/create', $data);
				return;
			}
			if ($this->userModel->findByEmail($data['email'])) {
				Session::setFlash('error', 'Email già in uso.');
				$this->view('admin/users/create', $data);
				return;
			}

			if ($this->userModel->create($data)) {
				Session::setFlash('success', 'Utente creato con successo!');
				header('Location: /admin/users');
				exit();
			} else {
				Session::setFlash('error', 'Errore durante la creazione dell\'utente.');
				$this->view('admin/users/create', $data);
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

		$this->view('admin/users/edit', ['user' => $user]);
	}

	/**
	 * Gestisce l'invio del modulo per l'aggiornamento di un utente
	 * @param array $params Contiene l'ID dell'utente
	 */
	public function updateUser($params)
	{
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
				'role' => trim($_POST['role'] ?? 'user')
			];

			// La password è opzionale nell'aggiornamento
			if (!empty($_POST['password'])) {
				$data['password'] = trim($_POST['password']);
			}

			// Validazione di base
			if (empty($data['username']) || empty($data['email'])) {
				Session::setFlash('error', 'Si prega di compilare tutti i campi obbligatori.');
				// Ricarica il form con i dati attuali dell'utente e l'errore
				$user = $this->userModel->find($id); // Recupera l'utente per ripopolare il form
				$this->view('admin/users/edit', ['user' => array_merge($user, $data), 'error' => Session::getFlash('error')]);
				return;
			}

			// Controlla se username o email sono già in uso da un altro utente
			$existingUserByUsername = $this->userModel->findByUsername($data['username']);
			if ($existingUserByUsername && $existingUserByUsername['id'] != $id) {
				Session::setFlash('error', 'Username già in uso da un altro utente.');
				$user = $this->userModel->find($id);
				$this->view('admin/users/edit', ['user' => array_merge($user, $data), 'error' => Session::getFlash('error')]);
				return;
			}
			$existingUserByEmail = $this->userModel->findByEmail($data['email']);
			if ($existingUserByEmail && $existingUserByEmail['id'] != $id) {
				Session::setFlash('error', 'Email già in uso da un altro utente.');
				$user = $this->userModel->find($id);
				$this->view('admin/users/edit', ['user' => array_merge($user, $data), 'error' => Session::getFlash('error')]);
				return;
			}

			if ($this->userModel->update($id, $data)) {
				Session::setFlash('success', 'Utente aggiornato con successo!');
				header('Location: /admin/users');
				exit();
			} else {
				Session::setFlash('error', 'Errore durante l\'aggiornamento dell\'utente.');
				$user = $this->userModel->find($id);
				$this->view('admin/users/edit', ['user' => array_merge($user, $data), 'error' => Session::getFlash('error')]);
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
		$id = $params[0] ?? null;

		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID utente non valido.');
			header('Location: /admin/users');
			exit();
		}

		// Puoi aggiungere una conferma qui, ad es. tramite un form POST per la sicurezza
		if ($this->userModel->delete($id)) {
			Session::setFlash('success', 'Utente eliminato con successo!');
		} else {
			Session::setFlash('error', 'Errore durante l\'eliminazione dell\'utente.');
		}

		header('Location: /admin/users');
		exit();
	}
}
