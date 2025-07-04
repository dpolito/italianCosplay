<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/models/Event.php';
require_once APP_ROOT . '/app/core/Session.php';

class EventController extends Controller
{
	private Event $eventModel;

	public function __construct()
	{
		$this->eventModel = new Event();
	}

	/**
	 * Mostra la lista degli eventi approvati al pubblico.
	 */
	public function index()
	{
		$events = $this->eventModel->getApprovedEvents();
		$this->view('events/index', ['events' => $events]); // Vista pubblica
	}

	/**
	 * Mostra il form per segnalare un nuovo evento (pubblico).
	 */
	public function create()
	{
		$this->view('events/create'); // Vista pubblica
	}

	/**
	 * Salva un nuovo evento segnalato (pubblico).
	 */
	public function store()
	{
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$data = [
				'titolo' => trim($_POST['titolo']),
				'descrizione' => trim($_POST['descrizione']),
				'data_inizio' => trim($_POST['data_inizio']),
				'data_fine' => trim($_POST['data_fine'] ?? ''),
				'luogo' => trim($_POST['luogo']),
				'immagine' => trim($_POST['immagine'] ?? '')
			];

			// Validazione di base
			if (empty($data['titolo']) || empty($data['descrizione']) || empty($data['data_inizio']) || empty($data['luogo'])) {
				Session::setFlash('error', 'Si prega di compilare tutti i campi obbligatori.');
				$this->view('events/create', $data);
				return;
			}

			if ($this->eventModel->create($data)) {
				Session::setFlash('success', 'Evento segnalato con successo! Sarà visibile dopo l\'approvazione.');
				header('Location: /events');
				exit();
			} else {
				Session::setFlash('error', 'Errore durante la segnalazione dell\'evento.');
				$this->view('events/create', $data);
			}
		} else {
			header('Location: /events/create');
			exit();
		}
	}

	/**
	 * Mostra i dettagli di un singolo evento (pubblico).
	 * @param array $params Contiene l'ID dell'evento.
	 */
	public function show($params)
	{
		$id = $params[0] ?? null;
		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID evento non valido.');
			header('Location: /events');
			exit();
		}

		$event = $this->eventModel->find($id);

		// Per la vista pubblica, mostra solo eventi approvati
		if (!$event || $event['approvato'] == 0) {
			Session::setFlash('error', 'Evento non trovato o non ancora approvato.');
			header('Location: /events');
			exit();
		}

		$this->view('events/show', ['event' => $event]);
	}

	// --- Metodi per l'Area Amministrativa ---

	/**
	 * Protegge i metodi admin.
	 */
	private function requireAdmin()
	{
		if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
			Session::setFlash('error', 'Accesso non autorizzato all\'area amministrativa degli eventi.');
			header('Location: /login');
			exit();
		}
	}

	/**
	 * Mostra la lista degli eventi in attesa di approvazione (ADMIN).
	 */
	public function pending()
	{
		$this->requireAdmin(); // Proteggi il metodo

		$events = $this->eventModel->getPendingEvents();
		$this->view('admin/events/pending', ['events' => $events]);
	}

	/**
	 * Mostra tutti gli eventi (approvati e non) per l'amministratore.
	 */
	public function allEvents()
	{
		$this->requireAdmin(); // Proteggi il metodo

		$events = $this->eventModel->getAllEvents();
		$this->view('admin/events/all', ['events' => $events]); // Nuova vista per tutti gli eventi
	}

	/**
	 * Mostra il form per modificare un evento (ADMIN).
	 * Questo metodo è destinato ad essere chiamato tramite una rotta admin (es. /admin/events/edit/{id}).
	 * @param array $params Contiene l'ID dell'evento.
	 */
	public function edit($params)
	{
		$this->requireAdmin(); // Proteggi il metodo

		$id = $params[0] ?? null;
		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID evento non valido.');
			header('Location: /admin/events/pending');
			exit();
		}

		$event = $this->eventModel->find($id);

		if (!$event) {
			Session::setFlash('error', 'Evento non trovato.');
			header('Location: /admin/events/pending');
			exit();
		}

		$this->view('admin/events/edit', ['event' => $event]);
	}

	/**
	 * Gestisce l'invio del modulo per l'aggiornamento di un evento (ADMIN).
	 * Questo metodo è destinato ad essere chiamato tramite una rotta admin (es. /admin/events/update/{id}).
	 * @param array $params Contiene l'ID dell'evento.
	 */
	public function update($params)
	{
		$this->requireAdmin(); // Proteggi il metodo

		$id = $params[0] ?? null;
		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID evento non valido.');
			header('Location: /admin/events/pending');
			exit();
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$data = [
				'titolo' => trim($_POST['titolo']),
				'descrizione' => trim($_POST['descrizione']),
				'data_inizio' => trim($_POST['data_inizio']),
				'data_fine' => trim($_POST['data_fine'] ?? ''),
				'luogo' => trim($_POST['luogo']),
				'immagine' => trim($_POST['immagine'] ?? ''),
				'approvato' => (int)($_POST['approvato'] ?? 0)
			];

			// Validazione di base
			if (empty($data['titolo']) || empty($data['descrizione']) || empty($data['data_inizio']) || empty($data['luogo'])) {
				Session::setFlash('error', 'Si prega di compilare tutti i campi obbligatori.');
				$event = $this->eventModel->find($id);
				$this->view('admin/events/edit', ['event' => array_merge($event, $data), 'error' => Session::getFlash('error')]);
				return;
			}

			if ($this->eventModel->update($id, $data)) {
				Session::setFlash('success', 'Evento aggiornato con successo!');
				// Reindirizza alla lista pending o a tutti gli eventi, a seconda della preferenza
				header('Location: /admin/events/pending');
				exit();
			} else {
				Session::setFlash('error', 'Errore durante l\'aggiornamento dell\'evento.');
				$event = $this->eventModel->find($id);
				$this->view('admin/events/edit', ['event' => array_merge($event, $data), 'error' => Session::getFlash('error')]);
			}
		} else {
			header('Location: /admin/events/pending');
			exit();
		}
	}

	/**
	 * Elimina un evento (ADMIN).
	 * @param array $params Contiene l'ID dell'evento.
	 */
	public function delete($params)
	{
		$this->requireAdmin(); // Proteggi il metodo

		$id = $params[0] ?? null;
		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID evento non valido.');
			header('Location: /admin/events/pending');
			exit();
		}

		if ($this->eventModel->delete($id)) {
			Session::setFlash('success', 'Evento eliminato con successo!');
		} else {
			Session::setFlash('error', 'Errore durante l\'eliminazione dell\'evento.');
		}
		header('Location: /admin/events/pending');
		exit();
	}

	/**
	 * Approva un evento (ADMIN).
	 * @param array $params Contiene l'ID dell'evento.
	 */
	public function approve($params)
	{
		$this->requireAdmin(); // Proteggi il metodo

		$id = $params[0] ?? null;
		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID evento non valido.');
			header('Location: /admin/events/pending');
			exit();
		}

		if ($this->eventModel->approveEvent($id)) {
			Session::setFlash('success', 'Evento approvato con successo!');
		} else {
			Session::setFlash('error', 'Errore durante l\'approvazione dell\'evento.');
		}
		header('Location: /admin/events/pending');
		exit();
	}

	/**
	 * Mostra i dettagli di un singolo evento per l'amministratore.
	 * Non controlla lo stato di approvazione, l'admin può vedere tutti i dettagli.
	 * @param array $params Contiene l'ID dell'evento.
	 */
	public function adminShow($params)
	{
		$this->requireAdmin(); // Proteggi il metodo

		$id = $params[0] ?? null;
		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID evento non valido.');
			header('Location: /admin/events/pending'); // Reindirizza alla lista pending
			exit();
		}

		$event = $this->eventModel->find($id);

		if (!$event) {
			Session::setFlash('error', 'Evento non trovato.');
			header('Location: /admin/events/pending');
			exit();
		}

		// Usa la stessa vista admin/events/show.php che abbiamo già migliorato
		$this->view('admin/events/show', ['event' => $event]);
	}
}
