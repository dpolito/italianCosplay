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
		// Genera un token CSRF per il form pubblico, se non esiste già
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
		$this->view('events/create', ['csrf_token' => $_SESSION['csrf_token']]); // Passa il token alla vista
	}

	/**
	 * Salva un nuovo evento segnalato (pubblico).
	 */
	public function store()
	{
		// Protezione CSRF
		if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
			Session::setFlash('error', 'Errore di sicurezza: richiesta non valida (CSRF).');
			header('Location: /events/create'); // Reindirizza al form di creazione
			exit();
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$data = [
				'titolo' => trim($_POST['titolo']),
				'descrizione' => $_POST['descrizione'], // Contenuto HTML da Quill, non trim o htmlspecialchars qui
				'data_inizio' => trim($_POST['data_inizio']),
				'data_fine' => trim($_POST['data_fine'] ?? ''),
				'luogo' => trim($_POST['luogo']),
				'regione_id' => filter_var($_POST['regione_id'] ?? null, FILTER_VALIDATE_INT),
				'provincia_id' => filter_var($_POST['provincia_id'] ?? null, FILTER_VALIDATE_INT),
				'comune_id' => filter_var($_POST['comune_id'] ?? null, FILTER_VALIDATE_INT),
				'latitudine' => filter_var($_POST['latitudine'] ?? null, FILTER_VALIDATE_FLOAT),
				'longitudine' => filter_var($_POST['longitudine'] ?? null, FILTER_VALIDATE_FLOAT),
				'sito_web' => filter_var(trim($_POST['sito_web'] ?? ''), FILTER_VALIDATE_URL),
				'social_facebook' => filter_var(trim($_POST['social_facebook'] ?? ''), FILTER_VALIDATE_URL),
				'social_twitter' => filter_var(trim($_POST['social_twitter'] ?? ''), FILTER_VALIDATE_URL),
				'social_instagram' => filter_var(trim($_POST['social_instagram'] ?? ''), FILTER_VALIDATE_URL),
				'social_tiktok' => filter_var(trim($_POST['social_tiktok'] ?? ''), FILTER_VALIDATE_URL),
				'social_youtube' => filter_var(trim($_POST['social_youtube'] ?? ''), FILTER_VALIDATE_URL),
				'tipo_evento_id' => filter_var($_POST['tipo_evento_id'] ?? null, FILTER_VALIDATE_INT),
				'immagine' => filter_var(trim($_POST['immagine'] ?? ''), FILTER_VALIDATE_URL)
			];

			$errors = [];

			// Validazione campi obbligatori e formati
			if (empty($data['titolo'])) $errors[] = 'Il titolo è obbligatorio.';
			if (strlen($data['titolo']) > 255) $errors[] = 'Il titolo è troppo lungo (max 255 caratteri).';
			if (empty($data['descrizione'])) $errors[] = 'La descrizione è obbligatoria.';
			if (empty($data['data_inizio'])) $errors[] = 'La data di inizio è obbligatoria.';

			// Validazione formati data e logica
			if (!empty($data['data_inizio']) && !strtotime($data['data_inizio'])) $errors[] = 'Formato data di inizio non valido.';
			if (!empty($data['data_fine']) && !strtotime($data['data_fine'])) $errors[] = 'Formato data di fine non valido.';
			if (!empty($data['data_fine']) && !empty($data['data_inizio']) && strtotime($data['data_fine']) < strtotime($data['data_inizio'])) {
				$errors[] = 'La data di fine non può essere precedente alla data di inizio.';
			}

			if (empty($data['luogo'])) $errors[] = 'Il luogo è obbligatorio.';
			if (strlen($data['luogo']) > 255) $errors[] = 'Il luogo è troppo lungo (max 255 caratteri).';

			// Validazione ID numerici
			if ($data['regione_id'] === false || $data['regione_id'] === null) $errors[] = 'ID Regione non valido.';
			if ($data['provincia_id'] === false || $data['provincia_id'] === null) $errors[] = 'ID Provincia non valido.';
			if ($data['comune_id'] === false || $data['comune_id'] === null) $errors[] = 'ID Comune non valido.';
			if ($data['tipo_evento_id'] === false || $data['tipo_evento_id'] === null) $errors[] = 'ID Tipo Evento non valido.';

			// Validazione Latitudine/Longitudine
			if ($data['latitudine'] === false || $data['latitudine'] === null || $data['latitudine'] < -90 || $data['latitudine'] > 90) {
				if (!empty($_POST['latitudine'])) { // Solo se l'utente ha provato a inserire un valore
					$errors[] = 'Latitudine non valida (deve essere tra -90 e 90).';
				}
			}
			if ($data['longitudine'] === false || $data['longitudine'] === null || $data['longitudine'] < -180 || $data['longitudine'] > 180) {
				if (!empty($_POST['longitudine'])) { // Solo se l'utente ha provato a inserire un valore
					$errors[] = 'Longitudine non valida (deve essere tra -180 e 180).';
				}
			}

			// Validazione URL (filter_var restituisce false se non è un URL valido o se è null/empty e non lo valida)
			if ($data['sito_web'] === false && !empty(trim($_POST['sito_web'] ?? ''))) $errors[] = 'URL Sito Web non valido.';
			if ($data['social_facebook'] === false && !empty(trim($_POST['social_facebook'] ?? ''))) $errors[] = 'URL Social Facebook non valido.';
			if ($data['social_twitter'] === false && !empty(trim($_POST['social_twitter'] ?? ''))) $errors[] = 'URL Social Twitter non valido.';
			if ($data['social_instagram'] === false && !empty(trim($_POST['social_instagram'] ?? ''))) $errors[] = 'URL Social Instagram non valido.';
			if ($data['social_tiktok'] === false && !empty(trim($_POST['social_tiktok'] ?? ''))) $errors[] = 'URL Social TikTok non valido.';
			if ($data['social_youtube'] === false && !empty(trim($_POST['social_youtube'] ?? ''))) $errors[] = 'URL Social YouTube non valido.';
			if ($data['immagine'] === false && !empty(trim($_POST['immagine'] ?? ''))) $errors[] = 'URL Immagine non valido.';


			if (!empty($errors)) {
				Session::setFlash('error', implode('<br>', $errors));
				// Ricarica la vista con i dati inviati e il token CSRF per ripopolare il form
				$this->view('events/create', array_merge($_POST, ['csrf_token' => $_SESSION['csrf_token'], 'error' => Session::getFlash('error')]));
				return;
			}

			// Se la validazione passa, procedi con la creazione
			if ($this->eventModel->create($data)) {
				Session::setFlash('success', 'Evento segnalato con successo! Sarà visibile dopo l\'approvazione.');
				header('Location: /events');
				exit();
			} else {
				Session::setFlash('error', 'Errore durante la segnalazione dell\'evento.');
				$this->view('events/create', array_merge($data, ['csrf_token' => $_SESSION['csrf_token']]));
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
	 * Protegge i metodi admin e assicura la presenza di un token CSRF.
	 */
	private function requireAdmin()
	{
		if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
			Session::setFlash('error', 'Accesso non autorizzato all\'area amministrativa degli eventi.');
			header('Location: /login');
			exit();
		}
		// Genera un token CSRF se non esiste già per la sessione admin
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
	 * Mostra la lista degli eventi in attesa di approvazione (ADMIN).
	 */
	public function pending()
	{
		$this->requireAdmin(); // Proteggi il metodo

		$events = $this->eventModel->getPendingEvents();
		$this->view('admin/events/pending', ['events' => $events, 'csrf_token' => $_SESSION['csrf_token']]);
	}

	/**
	 * Mostra tutti gli eventi (approvati e non) per l'amministratore.
	 */
	public function allEvents()
	{
		$this->requireAdmin(); // Proteggi il metodo

		$events = $this->eventModel->getAllEvents();
		$this->view('admin/events/all', ['events' => $events, 'csrf_token' => $_SESSION['csrf_token']]);
	}

	/**
	 * Mostra il form per creare un nuovo evento (ADMIN).
	 */
	public function adminCreate()
	{
		$this->requireAdmin(); // Proteggi il metodo
		$this->view('admin/events/create', ['csrf_token' => $_SESSION['csrf_token']]); // Passa il token alla vista
	}

	/**
	 * Salva un nuovo evento creato dall'amministratore.
	 */
	public function adminStore()
	{
		$this->requireAdmin(); // Proteggi il metodo

		// Protezione CSRF
		if (!$this->validateCsrfToken()) {
			header('Location: /admin/events/create'); // Reindirizza al form di creazione admin
			exit();
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$data = [
				'titolo' => trim($_POST['titolo']),
				'descrizione' => $_POST['descrizione'], // Contenuto HTML da Quill, non trim o htmlspecialchars qui
				'data_inizio' => trim($_POST['data_inizio']),
				'data_fine' => trim($_POST['data_fine'] ?? ''),
				'luogo' => trim($_POST['luogo']),
				'regione_id' => filter_var($_POST['regione_id'] ?? null, FILTER_VALIDATE_INT),
				'provincia_id' => filter_var($_POST['provincia_id'] ?? null, FILTER_VALIDATE_INT),
				'comune_id' => filter_var($_POST['comune_id'] ?? null, FILTER_VALIDATE_INT),
				'latitudine' => filter_var($_POST['latitudine'] ?? null, FILTER_VALIDATE_FLOAT),
				'longitudine' => filter_var($_POST['longitudine'] ?? null, FILTER_VALIDATE_FLOAT),
				'sito_web' => filter_var(trim($_POST['sito_web'] ?? ''), FILTER_VALIDATE_URL),
				'social_facebook' => filter_var(trim($_POST['social_facebook'] ?? ''), FILTER_VALIDATE_URL),
				'social_twitter' => filter_var(trim($_POST['social_twitter'] ?? ''), FILTER_VALIDATE_URL),
				'social_instagram' => filter_var(trim($_POST['social_instagram'] ?? ''), FILTER_VALIDATE_URL),
				'social_tiktok' => filter_var(trim($_POST['social_tiktok'] ?? ''), FILTER_VALIDATE_URL),
				'social_youtube' => filter_var(trim($_POST['social_youtube'] ?? ''), FILTER_VALIDATE_URL),
				'tipo_evento_id' => filter_var($_POST['tipo_evento_id'] ?? null, FILTER_VALIDATE_INT),
				'immagine' => filter_var(trim($_POST['immagine'] ?? ''), FILTER_VALIDATE_URL),
				'approvato' => (int)($_POST['approvato'] ?? 0)
			];

			$errors = [];

			// Validazione campi obbligatori e formati
			if (empty($data['titolo'])) $errors[] = 'Il titolo è obbligatorio.';
			if (strlen($data['titolo']) > 255) $errors[] = 'Il titolo è troppo lungo (max 255 caratteri).';
			if (empty($data['descrizione'])) $errors[] = 'La descrizione è obbligatoria.';
			if (empty($data['data_inizio'])) $errors[] = 'La data di inizio è obbligatoria.';

			// Validazione formati data e logica
			if (!empty($data['data_inizio']) && !strtotime($data['data_inizio'])) $errors[] = 'Formato data di inizio non valido.';
			if (!empty($data['data_fine']) && !strtotime($data['data_fine'])) $errors[] = 'Formato data di fine non valido.';
			if (!empty($data['data_fine']) && !empty($data['data_inizio']) && strtotime($data['data_fine']) < strtotime($data['data_inizio'])) {
				$errors[] = 'La data di fine non può essere precedente alla data di inizio.';
			}

			if (empty($data['luogo'])) $errors[] = 'Il luogo è obbligatorio.';
			if (strlen($data['luogo']) > 255) $errors[] = 'Il luogo è troppo lungo (max 255 caratteri).';

			// Validazione ID numerici
			if ($data['regione_id'] === false || $data['regione_id'] === null) $errors[] = 'ID Regione non valido.';
			if ($data['provincia_id'] === false || $data['provincia_id'] === null) $errors[] = 'ID Provincia non valido.';
			if ($data['comune_id'] === false || $data['comune_id'] === null) $errors[] = 'ID Comune non valido.';
			if ($data['tipo_evento_id'] === false || $data['tipo_evento_id'] === null) $errors[] = 'ID Tipo Evento non valido.';

			// Validazione Latitudine/Longitudine
			if ($data['latitudine'] === false || $data['latitudine'] === null || $data['latitudine'] < -90 || $data['latitudine'] > 90) {
				if (!empty($_POST['latitudine'])) {
					$errors[] = 'Latitudine non valida (deve essere tra -90 e 90).';
				}
			}
			if ($data['longitudine'] === false || $data['longitudine'] === null || $data['longitudine'] < -180 || $data['longitudine'] > 180) {
				if (!empty($_POST['longitudine'])) {
					$errors[] = 'Longitudine non valida (deve essere tra -180 e 180).';
				}
			}

			// Validazione URL
			if ($data['sito_web'] === false && !empty(trim($_POST['sito_web'] ?? ''))) $errors[] = 'URL Sito Web non valido.';
			if ($data['social_facebook'] === false && !empty(trim($_POST['social_facebook'] ?? ''))) $errors[] = 'URL Social Facebook non valido.';
			if ($data['social_twitter'] === false && !empty(trim($_POST['social_twitter'] ?? ''))) $errors[] = 'URL Social Twitter non valido.';
			if ($data['social_instagram'] === false && !empty(trim($_POST['social_instagram'] ?? ''))) $errors[] = 'URL Social Instagram non valido.';
			if ($data['social_tiktok'] === false && !empty(trim($_POST['social_tiktok'] ?? ''))) $errors[] = 'URL Social TikTok non valido.';
			if ($data['social_youtube'] === false && !empty(trim($_POST['social_youtube'] ?? ''))) $errors[] = 'URL Social YouTube non valido.';
			if ($data['immagine'] === false && !empty(trim($_POST['immagine'] ?? ''))) $errors[] = 'URL Immagine non valido.';


			if (!empty($errors)) {
				Session::setFlash('error', implode('<br>', $errors));
				$this->view('admin/events/create', array_merge($_POST, ['csrf_token' => $_SESSION['csrf_token'], 'error' => Session::getFlash('error')]));
				return;
			}

			// Se la validazione passa, procedi con la creazione
			if ($this->eventModel->create($data)) {
				Session::setFlash('success', 'Evento creato con successo!');
				header('Location: /admin/events/all');
				exit();
			} else {
				Session::setFlash('error', 'Errore durante la creazione dell\'evento.');
				$this->view('admin/events/create', array_merge($data, ['csrf_token' => $_SESSION['csrf_token']]));
			}
		} else {
			header('Location: /admin/events/create');
			exit();
		}
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

		$this->view('admin/events/edit', ['event' => $event, 'csrf_token' => $_SESSION['csrf_token']]); // Passa il token alla vista
	}

	/**
	 * Gestisce l'invio del modulo per l'aggiornamento di un evento (ADMIN).
	 * Questo metodo è destinato ad essere chiamato tramite una rotta admin (es. /admin/events/update/{id}).
	 * @param array $params Contiene l'ID dell'evento.
	 */
	public function update($params)
	{
		$this->requireAdmin(); // Proteggi il metodo

		// Protezione CSRF
		if (!$this->validateCsrfToken()) {
			$id = $params[0] ?? null; // Recupera l'ID per il reindirizzamento corretto
			header('Location: /admin/events/edit/' . $id);
			exit();
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$id = $params[0] ?? null;
			if (!$id || !is_numeric($id)) {
				Session::setFlash('error', 'ID evento non valido.');
				header('Location: /admin/events/pending');
				exit();
			}

			$data = [
				'titolo' => trim($_POST['titolo']),
				'descrizione' => $_POST['descrizione'], // Contenuto HTML da Quill, non trim o htmlspecialchars qui
				'data_inizio' => trim($_POST['data_inizio']),
				'data_fine' => trim($_POST['data_fine'] ?? ''),
				'luogo' => trim($_POST['luogo']),
				'regione_id' => filter_var($_POST['regione_id'] ?? null, FILTER_VALIDATE_INT),
				'provincia_id' => filter_var($_POST['provincia_id'] ?? null, FILTER_VALIDATE_INT),
				'comune_id' => filter_var($_POST['comune_id'] ?? null, FILTER_VALIDATE_INT),
				'latitudine' => filter_var($_POST['latitudine'] ?? null, FILTER_VALIDATE_FLOAT),
				'longitudine' => filter_var($_POST['longitudine'] ?? null, FILTER_VALIDATE_FLOAT),
				'sito_web' => filter_var(trim($_POST['sito_web'] ?? ''), FILTER_VALIDATE_URL),
				'social_facebook' => filter_var(trim($_POST['social_facebook'] ?? ''), FILTER_VALIDATE_URL),
				'social_twitter' => filter_var(trim($_POST['social_twitter'] ?? ''), FILTER_VALIDATE_URL),
				'social_instagram' => filter_var(trim($_POST['social_instagram'] ?? ''), FILTER_VALIDATE_URL),
				'social_tiktok' => filter_var(trim($_POST['social_tiktok'] ?? ''), FILTER_VALIDATE_URL),
				'social_youtube' => filter_var(trim($_POST['social_youtube'] ?? ''), FILTER_VALIDATE_URL),
				'tipo_evento_id' => filter_var($_POST['tipo_evento_id'] ?? null, FILTER_VALIDATE_INT),
				'immagine' => filter_var(trim($_POST['immagine'] ?? ''), FILTER_VALIDATE_URL),
				'approvato' => (int)($_POST['approvato'] ?? 0)
			];

			$errors = [];

			// Validazione campi obbligatori e formati
			if (empty($data['titolo'])) $errors[] = 'Il titolo è obbligatorio.';
			if (strlen($data['titolo']) > 255) $errors[] = 'Il titolo è troppo lungo (max 255 caratteri).';
			if (empty($data['descrizione'])) $errors[] = 'La descrizione è obbligatoria.';
			if (empty($data['data_inizio'])) $errors[] = 'La data di inizio è obbligatoria.';

			// Validazione formati data e logica
			if (!empty($data['data_inizio']) && !strtotime($data['data_inizio'])) $errors[] = 'Formato data di inizio non valido.';
			if (!empty($data['data_fine']) && !strtotime($data['data_fine'])) $errors[] = 'Formato data di fine non valido.';
			if (!empty($data['data_fine']) && !empty($data['data_inizio']) && strtotime($data['data_fine']) < strtotime($data['data_inizio'])) {
				$errors[] = 'La data di fine non può essere precedente alla data di inizio.';
			}

			if (empty($data['luogo'])) $errors[] = 'Il luogo è obbligatorio.';
			if (strlen($data['luogo']) > 255) $errors[] = 'Il luogo è troppo lungo (max 255 caratteri).';

			// Validazione ID numerici
			if ($data['regione_id'] === false || $data['regione_id'] === null) $errors[] = 'ID Regione non valido.';
			if ($data['provincia_id'] === false || $data['provincia_id'] === null) $errors[] = 'ID Provincia non valido.';
			if ($data['comune_id'] === false || $data['comune_id'] === null) $errors[] = 'ID Comune non valido.';
			if ($data['tipo_evento_id'] === false || $data['tipo_evento_id'] === null) $errors[] = 'ID Tipo Evento non valido.';

			// Validazione Latitudine/Longitudine
			if ($data['latitudine'] === false || $data['latitudine'] === null || $data['latitudine'] < -90 || $data['latitudine'] > 90) {
				if (!empty($_POST['latitudine'])) {
					$errors[] = 'Latitudine non valida (deve essere tra -90 e 90).';
				}
			}
			if ($data['longitudine'] === false || $data['longitudine'] === null || $data['longitudine'] < -180 || $data['longitudine'] > 180) {
				if (!empty($_POST['longitudine'])) {
					$errors[] = 'Longitudine non valida (deve essere tra -180 e 180).';
				}
			}

			// Validazione URL
			if ($data['sito_web'] === false && !empty(trim($_POST['sito_web'] ?? ''))) $errors[] = 'URL Sito Web non valido.';
			if ($data['social_facebook'] === false && !empty(trim($_POST['social_facebook'] ?? ''))) $errors[] = 'URL Social Facebook non valido.';
			if ($data['social_twitter'] === false && !empty(trim($_POST['social_twitter'] ?? ''))) $errors[] = 'URL Social Twitter non valido.';
			if ($data['social_instagram'] === false && !empty(trim($_POST['social_instagram'] ?? ''))) $errors[] = 'URL Social Instagram non valido.';
			if ($data['social_tiktok'] === false && !empty(trim($_POST['social_tiktok'] ?? ''))) $errors[] = 'URL Social TikTok non valido.';
			if ($data['social_youtube'] === false && !empty(trim($_POST['social_youtube'] ?? ''))) $errors[] = 'URL Social YouTube non valido.';
			if ($data['immagine'] === false && !empty(trim($_POST['immagine'] ?? ''))) $errors[] = 'URL Immagine non valido.';


			if (!empty($errors)) {
				Session::setFlash('error', implode('<br>', $errors));
				// Ricarica la vista con i dati inviati, l'evento originale e il token CSRF
				$event = $this->eventModel->find($id); // Recupera l'evento originale per i dati non modificati
				$this->view('admin/events/edit', array_merge(['event' => $event], $_POST, ['csrf_token' => $_SESSION['csrf_token'], 'error' => Session::getFlash('error')]));
				return;
			}

			if ($this->eventModel->update($id, $data)) {
				Session::setFlash('success', 'Evento aggiornato con successo!');
				header('Location: /admin/events/all');
				exit();
			} else {
				Session::setFlash('error', 'Errore durante l\'aggiornamento dell\'evento.');
				$event = $this->eventModel->find($id);
				$this->view('admin/events/edit', array_merge(['event' => $event], $data, ['csrf_token' => $_SESSION['csrf_token']]));
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

		// Protezione CSRF
		if (!$this->validateCsrfToken()) {
			header('Location: /admin/events/pending'); // Reindirizza alla lista pending
			exit();
		}

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

		// Protezione CSRF
		if (!$this->validateCsrfToken()) {
			header('Location: /admin/events/pending'); // Reindirizza alla lista pending
			exit();
		}

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

		$this->view('admin/events/show', ['event' => $event, 'csrf_token' => $_SESSION['csrf_token']]); // Passa il token alla vista
	}
}
