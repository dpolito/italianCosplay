<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/models/Event.php';
require_once APP_ROOT . '/app/core/Session.php'; // Assicurati che Session sia incluso

class EventController extends Controller
{
	private $eventModel;

	public function __construct()
	{
		$this->eventModel = new Event();
	}

	public function index()
	{
		$events = $this->eventModel->getApprovedEvents();
		$this->view('events/index', ['events' => $events]);
	}

	public function show($params)
	{
		$id = $params[0]; // L'ID viene passato come primo parametro dal router
		$event = $this->eventModel->find($id);
		if (!$event) {
			// Gestisci evento non trovato, ad esempio con una pagina 404
			$this->view('errors/404');
			return;
		}
		$this->view('events/show', ['event' => $event]);
	}

	public function create()
	{
		// Mostra il form per segnalare un nuovo evento
		$this->view('events/create');
	}

	public function store()
	{
		// Logica per salvare un nuovo evento
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$data = [
				'titolo'           => $_POST['titolo'] ?? '',
				'descrizione'      => $_POST['descrizione'] ?? '',
				'data_inizio'      => $_POST['data_inizio'] ?? '',
				'data_fine'        => $_POST['data_fine'] ?? null, // Può essere null se evento di un solo giorno
				'luogo'            => $_POST['luogo'] ?? '',
				'regione_id'       => $_POST['regione_id'] ?? null,
				'provincia_id'     => $_POST['provincia_id'] ?? null,
				'comune_id'        => $_POST['comune_id'] ?? null,
				'latitudine'       => $_POST['latitudine'] ?? null,
				'longitudine'      => $_POST['longitudine'] ?? null,
				'sito_web'         => $_POST['sito_web'] ?? null,
				'social_facebook'  => $_POST['social_facebook'] ?? null,
				'social_twitter'   => $_POST['social_twitter'] ?? null,
				'social_instagram' => $_POST['social_instagram'] ?? null,
				'social_tiktok'    => $_POST['social_tiktok'] ?? null,
				'social_youtube'   => $_POST['social_youtube'] ?? null,
				'tipo_evento_id'   => $_POST['tipo_evento_id'] ?? null,
				'approvato'        => 0 // Nuovo evento è sempre non approvato
			];

			// Validazione dei dati di base (da migliorare)
			if (empty($data['titolo']) || empty($data['data_inizio']) || empty($data['luogo'])) {
				Session::setFlash('error', 'Titolo, Data Inizio e Luogo sono campi obbligatori.');
				$this->redirect('/events/create');
			}

			// Gestione upload immagine
			$data['immagine'] = null;
			if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
				$uploadDir = APP_ROOT . '/public_assets/images/';
				if (!is_dir($uploadDir)) {
					mkdir($uploadDir, 0777, true); // Crea la directory se non esiste
				}

				$fileName = uniqid() . '_' . basename($_FILES['immagine']['name']); // Rinomina per evitare collisioni
				$targetFilePath = $uploadDir . $fileName;
				$fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

				$allowTypes = array('jpg', 'png', 'jpeg', 'gif');
				if (in_array($fileType, $allowTypes)) {
					if (move_uploaded_file($_FILES['immagine']['tmp_name'], $targetFilePath)) {
						$data['immagine'] = $fileName;
					} else {
						Session::setFlash('error', 'Errore durante il caricamento dell\'immagine.');
						$this->redirect('/events/create');
					}
				} else {
					Session::setFlash('error', 'Solo file JPG, JPEG, PNG, GIF sono permessi per l\'immagine.');
					$this->redirect('/events/create');
				}
			}


			if (Session::hasFlash('error')) { // Se c'è stato un errore nell'upload, fermati qui
				return;
			}

			if ($this->eventModel->create($data)) {
				Session::setFlash('success', 'Evento segnalato con successo! Sarà visibile dopo l\'approvazione.');
				$this->redirect('/events');
			} else {
				Session::setFlash('error', 'Si è verificato un errore durante la segnalazione dell\'evento. Riprova.');
				$this->redirect('/events/create');
			}
		} else {
			$this->redirect('/events/create'); // Reindirizza se non è una POST request
		}
	}

	public function edit($params)
	{
		// Solo per amministratori o utenti che hanno segnalato l'evento
		// Qui si dovrebbe aggiungere la logica di autorizzazione
		$id = $params[0];
		$event = $this->eventModel->find($id);
		if (!$event) {
			$this->view('errors/404');
			return;
		}
		$this->view('events/edit', ['event' => $event]);
	}

	public function update($params)
	{
		// Solo per amministratori o utenti che hanno segnalato l'evento
		// Qui si dovrebbe aggiungere la logica di autorizzazione
		$id = $params[0];
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$data = [
				'titolo'           => $_POST['titolo'] ?? '',
				'descrizione'      => $_POST['descrizione'] ?? '',
				'data_inizio'      => $_POST['data_inizio'] ?? '',
				'data_fine'        => $_POST['data_fine'] ?? null,
				'luogo'            => $_POST['luogo'] ?? '',
				'regione_id'       => $_POST['regione_id'] ?? null,
				'provincia_id'     => $_POST['provincia_id'] ?? null,
				'comune_id'        => $_POST['comune_id'] ?? null,
				'latitudine'       => $_POST['latitudine'] ?? null,
				'longitudine'      => $_POST['longitudine'] ?? null,
				'sito_web'         => $_POST['sito_web'] ?? null,
				'social_facebook'  => $_POST['social_facebook'] ?? null,
				'social_twitter'   => $_POST['social_twitter'] ?? null,
				'social_instagram' => $_POST['social_instagram'] ?? null,
				'social_tiktok'    => $_POST['social_tiktok'] ?? null,
				'social_youtube'   => $_POST['social_youtube'] ?? null,
				'tipo_evento_id'   => $_POST['tipo_evento_id'] ?? null,
				'approvato'        => $_POST['approvato'] ?? 0 // Solo admin dovrebbe poter modificare questo
			];

			// Recupera il nome dell'immagine esistente
			$existingEvent = $this->eventModel->find($id);
			$data['immagine'] = $existingEvent['immagine'] ?? null;

			// Gestione nuovo upload immagine
			if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
				$uploadDir = APP_ROOT . '/public_assets/images/';
				if (!is_dir($uploadDir)) {
					mkdir($uploadDir, 0777, true);
				}

				$fileName = uniqid() . '_' . basename($_FILES['immagine']['name']);
				$targetFilePath = $uploadDir . $fileName;
				$fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

				$allowTypes = array('jpg', 'png', 'jpeg', 'gif');
				if (in_array($fileType, $allowTypes)) {
					// Elimina la vecchia immagine se ne esiste una
					if ($existingEvent['immagine'] && file_exists($uploadDir . $existingEvent['immagine'])) {
						unlink($uploadDir . $existingEvent['immagine']);
					}
					if (move_uploaded_file($_FILES['immagine']['tmp_name'], $targetFilePath)) {
						$data['immagine'] = $fileName;
					} else {
						Session::setFlash('error', 'Errore durante il caricamento della nuova immagine.');
						$this->redirect('/events/' . $id . '/edit');
						return;
					}
				} else {
					Session::setFlash('error', 'Solo file JPG, JPEG, PNG, GIF sono permessi per l\'immagine.');
					$this->redirect('/events/' . $id . '/edit');
					return;
				}
			} elseif (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
				// Se l'utente ha richiesto di rimuovere l'immagine
				$uploadDir = APP_ROOT . '/public_assets/images/';
				if ($existingEvent['immagine'] && file_exists($uploadDir . $existingEvent['immagine'])) {
					unlink($uploadDir . $existingEvent['immagine']);
				}
				$data['immagine'] = null;
			}


			if (Session::hasFlash('error')) {
				return;
			}

			if ($this->eventModel->update($id, $data)) {
				Session::setFlash('success', 'Evento aggiornato con successo!');
				$this->redirect('/events/' . $id);
			} else {
				Session::setFlash('error', 'Si è verificato un errore durante l\'aggiornamento dell\'evento.');
				$this->redirect('/events/' . $id . '/edit');
			}
		} else {
			$this->redirect('/events/' . $id . '/edit');
		}
	}

	public function delete($params)
	{
		// Solo per amministratori
		// Qui si dovrebbe aggiungere la logica di autorizzazione
		$id = $params[0];
		$event = $this->eventModel->find($id);
		if (!$event) {
			Session::setFlash('error', 'Evento non trovato per l\'eliminazione.');
			$this->redirect('/events');
			return;
		}

		if ($this->eventModel->delete($id)) {
			// Elimina anche il file immagine se esiste
			if ($event['immagine']) {
				$imagePath = APP_ROOT . '/public_assets/images/' . $event['immagine'];
				if (file_exists($imagePath)) {
					unlink($imagePath);
				}
			}
			Session::setFlash('success', 'Evento eliminato con successo!');
		} else {
			Session::setFlash('error', 'Si è verificato un errore durante l\'eliminazione dell\'evento.');
		}
		$this->redirect('/admin/events/pending'); // O dove è più appropriato reindirizzare dopo l'eliminazione
	}

	public function pending()
	{
		// Solo per amministratori
		// Qui si dovrebbe aggiungere la logica di autorizzazione
		$events = $this->eventModel->getPendingEvents();
		$this->view('events/pending', ['events' => $events]);
	}

	public function approve($params)
	{
		// Solo per amministratori
		// Qui si dovrebbe aggiungere la logica di autorizzazione
		$id = $params[0];
		if ($this->eventModel->approveEvent($id)) {
			Session::setFlash('success', 'Evento approvato con successo!');
		} else {
			Session::setFlash('error', 'Si è verificato un errore durante l\'approvazione dell\'evento.');
		}
		$this->redirect('/admin/events/pending');
	}

	// Aggiungi un AuthController per gestire login/register
	// public function showLoginForm() {}
	// public function login() {}
	// public function register() {}
	// public function logout() {}
}
