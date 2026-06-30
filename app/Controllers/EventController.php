<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Mailer;
use App\Core\Session;
use App\Helpers\DateHelper;
use App\Models\Comune;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Provincia;
use App\Models\Regione;
use App\Models\TipoEvento;
use App\Repositories\EventRepository;
use App\Services\EventAnalyticsService;
use App\Services\EventFeedService;
use App\Services\EventImageMigrationService;
use App\Services\EventRankingService;
use App\Services\EventScoringEngine;
use App\Services\EventScraperService;
use App\Services\EventSignalService;
use App\Services\ImageService;
use App\Services\ProvinceCorrelateService;
use function array_map;
use function bin2hex;
use function count;
use function date;
use function filter_var;
use function header;
use function json_decode;
use function json_encode;
use function random_bytes;
use function stripos;
use function strtotime;
use function var_dump;
use const FILTER_VALIDATE_INT;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_UNICODE;
use const URL_ROOT;

class EventController extends Controller
{
	private Event $eventModel;
	private Guest $guestModel;
	private TipoEvento $tipoEventoModel;
	private Regione $regioneModel;
	private Provincia $provinciaModel;
	private Comune $comuneModel;
	private $uploadDir = APP_ROOT . '/public_assets/uploads/events/';
	private $eventsBasePath; // Proprietà per il percorso base degli eventi (URL assoluto)
	private $relativeEventsBasePath; // Proprietà per il percorso base degli eventi (URL relativo)
	private EventScraperService $eventScraperService;
	private EventAnalyticsService $eventAnalyticsService;
	private EventRankingService $eventRankingService;
	private EventFeedService $eventFeedService;
	private ImageService $imageService;
	private ProvinceCorrelateService  $provinceCorrelateService;

	public function __construct()
	{
		$this->eventModel = new Event();
		$this->guestModel = new Guest();
		$this->tipoEventoModel = new TipoEvento();
		$this->regioneModel = new Regione();
		$this->provinciaModel = new Provincia();
		$this->comuneModel = new Comune();
		$this->eventScraperService = new EventScraperService();
		$this->eventAnalyticsService = new EventAnalyticsService();
		$this->eventRankingService = new EventRankingService();
		$this->eventFeedService = new EventFeedService();
		$this->provinceCorrelateService = new ProvinceCorrelateService();
		$this->imageService = new ImageService($this->eventModel->getDbConnection());

		// Definisci il percorso base per gli eventi qui per evitare duplicazioni
		// CORREZIONE QUI: Rimuovi lo slash finale da URL_ROOT prima di concatenare
		$this->eventsBasePath = rtrim(URL_ROOT, '/') . '/eventi-cosplay';
		$this->relativeEventsBasePath = '/eventi-cosplay'; // Percorso relativo per i controlli URI

		// Assicurati che la directory di upload esista
		if (!is_dir($this->uploadDir)) {
			mkdir($this->uploadDir, 0775, true);
		}
	}



	public function scrape()
	{
		header('Content-Type: application/json; charset=utf-8');

		$url = $_POST['url'] ?? null;

		if (!$url) {
			echo json_encode(['error' => 'URL mancante']);
			return;
		}

		$data = $this->eventScraperService->scrapeCosplayersItalia($url);

		echo json_encode($data);
		exit;
	}



	/**
	 * Mostra la lista degli eventi approvati al pubblico.
	 * Ora può ricevere un parametro slug della regione o della provincia dall'URL.
	 * @param array $params Contiene lo slug della regione o della provincia se presente.
	 */
	public function index(array $params = [])
	{
		$regioneSlug = null;
		$provinciaSlug = null;
		$comuneSlug = null;
		if(!empty($params)){
			if(stripos($params[0], '/') !== false ) {
				$regioneSlug   = $params[1] ?? null;
				$provinciaSlug = $params[2] ?? null;
				$comuneSlug    = $params[3] ?? null;
			}else{
				$regioneSlug   = $params[0] ?? null;
				$provinciaSlug = $params[1] ?? null;
				$comuneSlug    = $params[2] ?? null;
			}
		}


		$selectedRegione = null;
		$selectedProvincia = null;
		$selectedComune = null;

		$regioneId = null;
		$provinciaId = null;
		$comuneId = null;

		$noindex = false;
		$canonicalUrl = URL_ROOT_SITE . '/eventi-cosplay';

		// Breadcrumb base
		$breadcrumbs = [
			['label' => 'Home', 'url' => URL_ROOT_SITE . '/'],
			['label' => 'Eventi Cosplay Italia', 'url' => URL_ROOT_SITE . '/eventi-cosplay'],
		];
		$provinceCorrelate = null;

		/* ---------------- REGIONE ---------------- */
		if ($regioneSlug) {
			$selectedRegione = $this->regioneModel->findBySlug($regioneSlug);
			if (!$selectedRegione) {
				Session::setFlash('error', 'Regione non trovata.');
				header('Location: ' . $this->eventsBasePath);
				exit;
			}

			$regioneId = $selectedRegione['id'];
			$breadcrumbs[] = [
				'label' => 'Eventi Cosplay ' . $selectedRegione['nome'],
				'url'   => URL_ROOT_SITE . '/eventi-cosplay/' . $selectedRegione['slug'],
			];
		}

		/* ---------------- PROVINCIA ---------------- */
		if ($provinciaSlug && $selectedRegione) {
			$selectedProvincia = $this->provinciaModel->findBySlug($provinciaSlug);

			if (
				!$selectedProvincia ||
				$selectedProvincia['regione_id'] != $selectedRegione['id']
			) {
				Session::setFlash('error', 'Provincia non valida.');
				header('Location: ' . $this->eventsBasePath);
				exit;
			}

			$provinciaId = $selectedProvincia['id'];
			$breadcrumbs[] = [
				'label' => 'Eventi Cosplay ' . $selectedProvincia['nome'],
				'url'   => URL_ROOT_SITE . '/eventi-cosplay/' .
					$selectedRegione['slug'] . '/' .
					$selectedProvincia['slug'],
			];
		}

		/* ---------------- COMUNE ---------------- */
		if ($comuneSlug && $selectedProvincia) {
			$selectedComune = $this->comuneModel->findBySlug($comuneSlug);

			if (
				!$selectedComune ||
				$selectedComune['provincia_id'] != $selectedProvincia['id']
			) {
				Session::setFlash('error', 'Comune non valido.');
				header('Location: ' . $this->eventsBasePath);
				exit;
			}

			$comuneId = $selectedComune['id'];
			$breadcrumbs[] = [
				'label' => 'Eventi Cosplay ' . $selectedComune['nome'],
				'url'   => URL_ROOT_SITE . '/eventi-cosplay/' .
					$selectedRegione['slug'] . '/' .
					$selectedProvincia['slug'] . '/' .
					$selectedComune['slug'],
			];
		}
		if (!empty($selectedRegione) && empty($selectedProvincia)) {
			$provinceCorrelate = $this->provinceCorrelateService->getProvinceCorrelate($selectedRegione['slug']);
		}

		/* ---------------- EVENTI ---------------- */
		$events = $this->eventModel->getApprovedEvents(
			$regioneId,
			$provinciaId,
			$comuneId
		);

		foreach ($events as $index => &$event) {
			$imageService = new ImageService($this->eventModel->getDbConnection());
			$images = $this->eventModel->getImages($event['id']);

			$cover = $this->imageService->getPrimary('event', $event['id']);

			if(!empty($cover)){
				$event['immagine'] = '/public_assets/'. $cover['path'];
				$event['immagine_width'] = $cover['width'];
				$event['immagine_height'] = $cover['height'];
			}else{
				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}

			if (!$event) {
				Session::setFlash('error', 'Evento non trovato.');
				header('Location: ' . URL_ROOT . '/admin/events/pending');
				exit();
			}


			$event['lazy'] = ($index < 3) ? false : true;
		}
		unset($event);

		/* ---------------- SEO ---------------- */
		$testo_descrittivo = $this->generaTestoSEO(
			$selectedRegione,
			$selectedProvincia,
			$selectedComune
		);


		$schema_org_generico = $this->generaSchemaSEO(
			$selectedRegione,
			$selectedProvincia,
			$selectedComune
		);

		$SchemaListaEventi = $this->generaSchemaListaEventi($events);

		if (empty($events)) {
			$noindex = true;
		}
		if ($selectedRegione) {
			$canonicalUrl .= '/' . $selectedRegione['slug'];
		}
		if ($selectedProvincia) {
			$canonicalUrl .= '/' . $selectedProvincia['slug'];
		}
		if ($selectedComune) {
			$canonicalUrl .= '/' . $selectedComune['slug'];
		}

		/* ---------------- VIEW ---------------- */
		$this->view('events/index', [
			'events' => $events,
			'selected_regione' => $selectedRegione,
			'selected_provincia' => $selectedProvincia,
			'selected_comune' => $selectedComune,
			'breadcrumbs' => $breadcrumbs,
			'all_regioni' => $this->regioneModel->getAll(),
			'all_province' => $this->provinciaModel->getByRegioneId($regioneId),
			'all_comuni' => $this->comuneModel->getAll($provinciaId),
			'testo_descrittivo' => $testo_descrittivo,
			'schema_org_generico' => $schema_org_generico,
			'SchemaListaEventi' => $SchemaListaEventi,
			'noindex' => $noindex,
			'canonicalUrl' => $canonicalUrl,
			'provinceCorrelate' => $provinceCorrelate
		]);
	}


	/**
	 * Mostra il form per segnalare un nuovo evento (pubblico).
	 */
	public function segnalaEvento()
	{
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
		$tipi_evento = $this->tipoEventoModel->getAll();
		$allRegioni = $this->regioneModel->getAll();
		$allProvince = $this->provinciaModel->getByRegioneId();
		$allComuni = $this->comuneModel->getAll();

		$breadcrumbs = [
			['label' => 'Home', 'url' => URL_ROOT . '/'],
			['label' => 'Eventi Cosplay', 'url' => $this->eventsBasePath],
			['label' => 'Segnala Evento', 'url' => URL_ROOT . '/segnala-evento-cosplay'],
		];
		$this->view('events/segnala-evento', [
			'csrf_token' => $_SESSION['csrf_token'],
			'tipi_evento' => $tipi_evento,
			'all_regioni' => $allRegioni,
			'all_province' => $allProvince,
			'all_comuni' => $allComuni,
			'breadcrumbs' => $breadcrumbs,
		]);
	}


	public function salvaEventoSegnalato(){

	}

	/**
	 * Salva un nuovo evento segnalato (pubblico).
	 */
	public function store()
	{
		if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
			Session::setFlash('error', 'Errore di sicurezza: richiesta non valida (CSRF).');
			header('Location: /segnala-evento-cosplay');
			exit();
		}

		error_log(__LINE__);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {

			$data = [
				'titolo' => trim($_POST['titolo']),
				'descrizione' => $_POST['descrizione'],
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
				'immagine' => null,
				'approvato' => 0,
				'event_size' => filter_var($_POST['event_size'] ?? null, FILTER_VALIDATE_INT),
				'is_paid' => filter_var($_POST['is_paid'] ? 1: 0, FILTER_VALIDATE_INT),
				'has_cosplay_contest' => filter_var($_POST['has_cosplay_contest'] ? 1 : 0, FILTER_VALIDATE_INT),
			];
			error_log(__LINE__);

			$errors = [];
			$imageId = null;
			if (empty($data['titolo'])) $errors[] = 'Il titolo è obbligatorio.';
			if (strlen($data['titolo']) > 255) $errors[] = 'Il titolo è troppo lungo (max 255 caratteri).';
			if (empty($data['descrizione'])) $errors[] = 'La descrizione è obbligatoria.';
			if (empty($data['data_inizio'])) $errors[] = 'La data di inizio è obbligatoria.';

			if (!empty($data['data_inizio']) && !strtotime($data['data_inizio'])) $errors[] = 'Formato data di inizio non valido.';
			if (!empty($data['data_fine']) && !strtotime($data['data_fine'])) $errors[] = 'Formato data di fine non valido.';
			if (!empty($data['data_fine']) && !empty($data['data_inizio']) && strtotime($data['data_fine']) < strtotime($data['data_inizio'])) {
				$errors[] = 'La data di fine non può essere precedente alla data di inizio.';
			}

			if (empty($data['luogo'])) $errors[] = 'Il luogo è obbligatorio.';
			if (strlen($data['luogo']) > 255) $errors[] = 'Il luogo è troppo lungo (max 255 caratteri).';

			if ($data['regione_id'] === false || $data['regione_id'] === null || $data['regione_id'] <= 0) $errors[] = 'ID Regione non valido.';
			if ($data['provincia_id'] === false || $data['provincia_id'] === null || $data['provincia_id'] <= 0) $errors[] = 'ID Provincia non valido.';
			if ($data['comune_id'] === false || $data['comune_id'] === null || $data['comune_id'] <= 0) $errors[] = 'ID Comune non valido.';
			if ($data['tipo_evento_id'] === false || $data['tipo_evento_id'] === null || $data['tipo_evento_id'] <= 0) $errors[] = 'ID Tipo Evento non valido.';
			if ($data['sito_web'] === false && !empty(trim($_POST['sito_web'] ?? ''))) $errors[] = 'URL Sito Web non valido.';
			if ($data['social_facebook'] === false && !empty(trim($_POST['social_facebook'] ?? ''))) $errors[] = 'URL Social Facebook non valido.';
			if ($data['social_twitter'] === false && !empty(trim($_POST['social_twitter'] ?? ''))) $errors[] = 'URL Social Twitter non valido.';
			if ($data['social_instagram'] === false && !empty(trim($_POST['social_instagram'] ?? ''))) $errors[] = 'URL Social Instagram non valido.';
			if ($data['social_tiktok'] === false && !empty(trim($_POST['social_tiktok'] ?? ''))) $errors[] = 'URL Social TikTok non valido.';
			if ($data['social_youtube'] === false && !empty(trim($_POST['social_youtube'] ?? ''))) $errors[] = 'URL Social YouTube non valido.';


			if (!empty($errors)) {
				error_log(__LINE__);
				Session::setFlash('error', implode('<br>', $errors));
				$tipi_evento = $this->tipoEventoModel->getAll();
				$allRegioni = $this->regioneModel->getAll();
				$allProvince = $this->provinciaModel->getByRegioneId();
				$allComuni = $this->comuneModel->getAll();
				$breadcrumbs = [
					['label' => 'Home', 'url' => URL_ROOT . '/'],
					['label' => 'Eventi Cosplay', 'url' => $this->eventsBasePath],
					['label' => 'Segnala Evento', 'url' => $this->eventsBasePath . '/segnala-evento-cosplay'],
				];
				$this->view('events/segnala-evento', array_merge($_POST, ['csrf_token' => $_SESSION['csrf_token'], 'error' => Session::getFlash('error'), 'tipi_evento' => $tipi_evento, 'all_regioni' => $allRegioni, 'all_province' => $allProvince, 'all_comuni' => $allComuni, 'breadcrumbs' => $breadcrumbs]));
				return;
			}
			error_log(__LINE__);
			$eventId = $this->eventModel->create($data);
			if ($eventId > 0) {
				$this->imageService->upload(
					$_FILES['immagine'],
					$eventId,
					$data['titolo']
				);
				error_log(__LINE__);
				Session::setFlash('success', 'Evento segnalato con successo! Sarà visibile dopo l\'approvazione.');

				$mailer = new Mailer();
				$template = file_get_contents(__DIR__ . '/../views/email_template_segnalazione_evento.php');
				$template = str_replace(
					['{{nome}}', '{{link_conferma}}'],
					['Mario Rossi', 'https://italiancosplay.com/conferma?token=abc123'],
					$template
				);
				$esito = $mailer->send(
					'd.polito81@gmail.com',
					'd.polito81@gmail.com',
					'Segnalazione evento ' . $data['titolo'],
					$template
				);

				header('Location: /segnala-evento-cosplay');
				exit();
			} else {
				error_log(__LINE__);
				Session::setFlash('error', 'Errore durante la segnalazione dell\'evento.');
				$tipi_evento = $this->tipoEventoModel->getAll();
				$allRegioni = $this->regioneModel->getAll();
				$allProvince = $this->provinciaModel->getAll();
				$allComuni = $this->comuneModel->getAll();
				$breadcrumbs = [
					['label' => 'Home', 'url' => URL_ROOT . '/'],
					['label' => 'Eventi Cosplay', 'url' => $this->eventsBasePath],
					['label' => 'Segnala Evento', 'url' => $this->eventsBasePath . '/segnala-evento-cosplay'],
				];
				$this->view('events/segnala-evento', array_merge($data, ['csrf_token' => $_SESSION['csrf_token'], 'tipi_evento' => $tipi_evento, 'all_regioni' => $allRegioni, 'all_province' => $allProvince, 'all_comuni' => $allComuni, 'breadcrumbs' => $breadcrumbs]));
			}
		} else {
			error_log(__LINE__);
			header('Location: /segnala-evento-cosplay');
			exit();
		}
	}

	/**
	 * Mostra i dettagli di un singolo evento (pubblico).
	 * Ora accetta lo slug invece dell'ID.
	 * @param array $params Contiene lo slug dell'evento.
	 */
	public function show($params)
	{
		$slug = $params[0] ?? null;
		if (!$slug) {
			Session::setFlash('error', 'Slug evento non valido.');
			header('Location: ' . $this->eventsBasePath);
			exit();
		}

		$event = $this->eventModel->findBySlug($slug);
		$cover = $this->imageService->getPrimary('event', $event['id']);
		if(!empty($cover)){
			$event['immagine'] = '/public_assets/'.$cover['path'];
			$event['immagine_width'] = $cover['width'];
			$event['immagine_height'] = $cover['height'];
		}else{
			$event['immagine'] = '';
			$event['immagine_width'] = '';
			$event['immagine_height'] = '';
		}
		$this->eventAnalyticsService->trackView($event['id']);


		if (!$event || $event['approvato'] == 0) {
			Session::setFlash('error', 'Evento non trovato o non ancora approvato.');
			header('Location: ' . $this->eventsBasePath);
			exit();
		}
		$this->eventsBasePath = URL_ROOT_SITE . '/eventi-cosplay';
		// Breadcrumbs
		$breadcrumbs = [
			['label' => 'Home', 'url' => URL_ROOT_SITE . '/'],
			['label' => 'Eventi Cosplay', 'url' => $this->eventsBasePath],
		];
		$regione = '';
		$provincia = '';

		if (isset($event['regione_id']) && $event['regione_id']) {
			$regione = $this->regioneModel->find($event['regione_id']);
			if ($regione) {
				$breadcrumbs[] = ['label' => $regione['nome'], 'url' => $this->eventsBasePath . '/' . $regione['slug']];
			}
		}

		if(isset($event['provincia_id']) && $event['provincia_id']){
			$provincia = $this->provinciaModel->find($event['provincia_id']);
			if($provincia){
				if (isset($event['comune_id']) && $event['comune_id']) {
					$comune = $this->comuneModel->find($event['comune_id']);
					if ($comune) {
						if($provincia['nome'] != $comune['nome']){
							$breadcrumbs[] = ['label' => $provincia['nome'], 'url' => $this->eventsBasePath . '/' . $regione['slug'] . '/' . $provincia['slug']];
							$breadcrumbs[] = ['label' => $comune['nome'], 'url' => $this->eventsBasePath . '/' . $regione['slug'] . '/' . $provincia['slug'] . '/' . $comune['slug']];
						}else{
							$breadcrumbs[] = ['label' => $comune['nome'], 'url' => $this->eventsBasePath . '/' . $regione['slug'] . '/' . $provincia['slug'] . '/' . $comune['slug']];
						}
					}
				}else{
					$breadcrumbs[] = ['label' => $provincia['nome'], 'url' => $this->eventsBasePath . '/' . $regione['slug'] . '/' . $provincia['slug']];
				}

			}
		}



		$breadcrumbs[] = ['label' => $event['titolo']];

		// Schema Eventi
		$SchemaListaEventi = $this->generaSchemaListaEventi([$event]);

		// **Canonical URL corretto per il dettaglio evento**
		$canonicalUrl = $this->eventsBasePath . '/' . $event['slug'];

		$similarEvents = $this->eventModel->getSimilarEvents(
			$event['id'],
			$event['regione_id'],
			$event['tipo_evento_id'],
			6
		);

		foreach ($similarEvents as &$similarEvent) {
			$cover = $this->imageService->getPrimary('event', $similarEvent['id']);
			if(!empty($cover)){
				$similarEvent['immagine'] = '/public_assets/'.$cover['path'];
				$similarEvent['immagine_width'] = $cover['width'];
				$similarEvent['immagine_height'] = $cover['height'];
			}else{
				$similarEvent['immagine'] = '';
				$similarEvent['immagine_width'] = '';
				$similarEvent['immagine_height'] = '';
			}
		}
		$data = [
			'event' => $event,
			'breadcrumbs' => $breadcrumbs,
			'SchemaListaEventi' => $SchemaListaEventi,
			'canonicalUrl' => $canonicalUrl,
			'similarEvents' => $similarEvents,
		];

		$this->view('events/show', $data);
	}


	// --- Metodi per l'Area Amministrativa ---

	/**
	 * Protegge i metodi admin e assicura la presenza di un token CSRF.
	 */


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


		$events = $this->eventModel->getPendingEvents();

		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Eventi in Attesa', 'url' => URL_ROOT . '/admin/events/pending'],
		];

		$this->view('admin/events/pending', ['events' => $events, 'csrf_token' => $_SESSION['csrf_token'], 'breadcrumbs' => $breadcrumbs], 'admin');
	}

	/**
	 * Mostra tutti gli eventi (approvati e non) per l'amministratore.
	 */
	public function allEvents()
	{


		$events = $this->eventModel->getAllEvents();

		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Tutti gli Eventi', 'url' => URL_ROOT . '/admin/events/all'],
		];

		$this->view('admin/events/all', ['events' => $events, 'csrf_token' => $_SESSION['csrf_token'], 'breadcrumbs' => $breadcrumbs], 'admin');
	}

	/**
	 * Mostra il form per creare un nuovo evento (ADMIN).
	 */
	public function adminCreate()
	{

		$tipi_evento = $this->tipoEventoModel->getAll();
		$allRegioni = $this->regioneModel->getAll();
		$allProvince = $this->provinciaModel->getAll();
		$allComuni = $this->comuneModel->getAll();


		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Tutti gli Eventi', 'url' => URL_ROOT . '/admin/events/all'],
			['label' => 'Crea Nuovo Evento', 'url' => URL_ROOT . '/admin/events/create'],
		];

		$this->view('admin/events/create', ['csrf_token' => $_SESSION['csrf_token'], 'tipi_evento' => $tipi_evento, 'all_regioni' => $allRegioni, 'all_province' => $allProvince, 'all_comuni' => $allComuni, 'breadcrumbs' => $breadcrumbs], 'admin');
	}

	/**
	 * Salva un nuovo evento creato dall'amministratore.
	 */
	public function adminStore()
	{
		if (!$this->validateCsrfToken()) {
			header('Location: ' . URL_ROOT . '/admin/events/create');
			exit();
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$is_paid = isset($_POST['is_paid']) ? 1 : 0;
			$has_cosplay_contest = isset($_POST['has_cosplay_contest']) ? 1 : 0;
			$data = [
				'titolo' => trim($_POST['titolo']),
				'descrizione' => $_POST['descrizione'],
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
				'approvato' => (int)($_POST['approvato'] ?? 0),
				'event_size' => (int)($_POST['event_size'] ?? 0),
				'is_paid ' => $is_paid,
				'has_cosplay_contest' => $has_cosplay_contest,
				'immagine' => null,
			];

			$errors = [];
			if (empty($data['titolo'])) $errors[] = 'Il titolo è obbligatorio.';
			if (strlen($data['titolo']) > 255) $errors[] = 'Il titolo è troppo lungo (max 255 caratteri).';
			if (empty($data['descrizione'])) $errors[] = 'La descrizione è obbligatoria.';
			if (empty($data['data_inizio'])) $errors[] = 'La data di inizio è obbligatoria.';

			if (!empty($data['data_inizio']) && !strtotime($data['data_inizio'])) $errors[] = 'Formato data di inizio non valido.';
			if (!empty($data['data_fine']) && !strtotime($data['data_fine'])) $errors[] = 'Formato data di fine non valido.';
			if (!empty($data['data_fine']) && !empty($data['data_inizio']) && strtotime($data['data_fine']) < strtotime($data['data_inizio'])) {
				$errors[] = 'La data di fine non può essere precedente alla data di inizio.';
			}

			if (empty($data['luogo'])) $errors[] = 'Il luogo è obbligatorio.';
			if (strlen($data['luogo']) > 255) $errors[] = 'Il luogo è troppo lungo (max 255 caratteri).';

			if ($data['regione_id'] === false || $data['regione_id'] === null || $data['regione_id'] <= 0) $errors[] = 'ID Regione non valido.';
			if ($data['provincia_id'] === false || $data['provincia_id'] === null || $data['provincia_id'] <= 0) $errors[] = 'ID Provincia non valido.';
			if ($data['comune_id'] === false || $data['comune_id'] === null || $data['comune_id'] <= 0) $errors[] = 'ID Comune non valido.';
			if ($data['tipo_evento_id'] === false || $data['tipo_evento_id'] === null || $data['tipo_evento_id'] <= 0) $errors[] = 'ID Tipo Evento non valido.';

			if ($data['latitudine'] === false || ($data['latitudine'] !== null && ($data['latitudine'] < -90 || $data['latitudine'] > 90))) {
				if (!empty($_POST['latitudine'])) {
					$errors[] = 'Latitudine non valida (deve essere tra -90 e 90).';
				}
			}
			if ($data['longitudine'] === false || ($data['longitudine'] !== null && ($data['longitudine'] < -180 || $data['longitudine'] > 180))) {
				if (!empty($_POST['longitudine'])) {
					$errors[] = 'Longitudine non valida (deve essere tra -180 e 180).';
				}
			}

			if ($data['sito_web'] === false && !empty(trim($_POST['sito_web'] ?? ''))) $errors[] = 'URL Sito Web non valido.';
			if ($data['social_facebook'] === false && !empty(trim($_POST['social_facebook'] ?? ''))) $errors[] = 'URL Social Facebook non valido.';
			if ($data['social_twitter'] === false && !empty(trim($_POST['social_twitter'] ?? ''))) $errors[] = 'URL Social Twitter non valido.';
			if ($data['social_instagram'] === false && !empty(trim($_POST['social_instagram'] ?? ''))) $errors[] = 'URL Social Instagram non valido.';
			if ($data['social_tiktok'] === false && !empty(trim($_POST['social_tiktok'] ?? ''))) $errors[] = 'URL Social TikTok non valido.';
			if ($data['social_youtube'] === false && !empty(trim($_POST['social_youtube'] ?? ''))) $errors[] = 'URL Social YouTube non valido.';


			if (!empty($errors)) {
				Session::setFlash('error', implode('<br>', $errors));
				$tipi_evento = $this->tipoEventoModel->getAll();
				$allRegioni = $this->regioneModel->getAll();
				$allProvince = $this->provinciaModel->getAll();
				$allComuni = $this->comuneModel->getAll();
				$breadcrumbs = [
					['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
					['label' => 'Tutti gli Eventi', 'url' => URL_ROOT . '/admin/events/all'],
					['label' => 'Crea Nuovo Evento', 'url' => URL_ROOT . '/admin/events/create'],
				];
				$this->view('admin/events/create', array_merge($_POST, ['csrf_token' => $_SESSION['csrf_token'], 'error' => Session::getFlash('error'), 'tipi_evento' => $tipi_evento, 'all_regioni' => $allRegioni, 'all_province' => $allProvince, 'all_comuni' => $allComuni, 'breadcrumbs' => $breadcrumbs]), 'admin');
				return;
			}
			$eventId = $this->eventModel->create($data);
			if ($eventId > 0) {
				if (!empty($_FILES['immagine']['name'])) {

					$coverImageId = $this->imageService->upload(
						$_FILES['immagine'],
						'event',
						$eventId,
						$data['titolo']
					);

					//$coverImageId = $this->imageService->upload($_FILES['cover_image'], 'blog_post', $id, $data['titolo'], true);

					$data['cover_image_id'] = $coverImageId;
				}
				$guestIds = [];

				if (!empty($_POST['guest_ids'])) {
					$guestIds = json_decode($_POST['guest_ids'], true) ?? [];
				}
				$this->guestModel->getGuests($eventId, $guestIds);
				Session::setFlash('success', 'Evento creato con successo!');
				header('Location: /admin/events/all');
				exit();
			} else {
				Session::setFlash('error', 'Errore durante la creazione dell\'evento.');
				$tipi_evento = $this->tipoEventoModel->getAll();
				$allRegioni = $this->regioneModel->getAll();
				$allProvince = $this->provinciaModel->getAll();
				$allComuni = $this->comuneModel->getAll();
				$breadcrumbs = [
					['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
					['label' => 'Tutti gli Eventi', 'url' => URL_ROOT . '/admin/events/all'],
					['label' => 'Crea Nuovo Evento', 'url' => URL_ROOT . '/admin/events/create'],
				];
				$this->view('admin/events/create', array_merge($data, ['csrf_token' => $_SESSION['csrf_token'], 'tipi_evento' => $tipi_evento, 'all_regioni' => $allRegioni, 'all_province' => $allProvince, 'all_comuni' => $allComuni, 'breadcrumbs' => $breadcrumbs]), 'admin');
			}
		} else {
			header('Location: ' . URL_ROOT . '/admin/events/create');
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
		$id = $params[0] ?? null;
		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID evento non valido.');
			header('Location: ' . URL_ROOT . '/admin/events/pending');
			exit();
		}

		$event = $this->eventModel->find($id);
		$cover = $this->imageService->getPrimary('event', $event['id'], 'large');
		$event['guests'] = $this->guestModel->getGuests($id);
		if(!empty($cover)){
			$event['immagine']= '/public_assets'.$cover['path'];
			$event['id_immagine'] = $cover['id'];
		}else{
			$event['immagine']= '';
			$event['id_immagine'] = '';
		}

		if (!$event) {
			Session::setFlash('error', 'Evento non trovato.');
			header('Location: ' . URL_ROOT . '/admin/events/pending');
			exit();
		}


		$tipi_evento = $this->tipoEventoModel->getAll();
		$allRegioni = $this->regioneModel->getAll();
		$allProvince = $this->provinciaModel->getAll();
		$allComuni = $this->comuneModel->getAll();

		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Tutti gli Eventi', 'url' => URL_ROOT . '/admin/events/all'],
			['label' => 'Modifica Evento: ' . $event['titolo'], 'url' => URL_ROOT . '/admin/events/edit/' . $id],
		];

		$this->view('admin/events/edit', ['event' => $event, 'csrf_token' => $_SESSION['csrf_token'], 'tipi_evento' => $tipi_evento, 'all_regioni' => $allRegioni, 'all_province' => $allProvince, 'all_comuni' => $allComuni, 'breadcrumbs' => $breadcrumbs], 'admin');
	}

	/**
	 * Gestisce l'invio del modulo per l'aggiornamento di un evento (ADMIN).
	 * Questo metodo è destinato ad essere chiamato tramite una rotta admin (es. /admin/events/update/{id}).
	 * @param array $params Contiene l'ID dell'evento.
	 */
	public function update($params)
	{
		if (!$this->validateCsrfToken()) {
			$id = $params[0] ?? null;
			header('Location: ' . URL_ROOT . '/admin/events/edit/' . $id);
			exit();
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$id = $params[0] ?? null;
			if (!$id || !is_numeric($id)) {
				Session::setFlash('error', 'ID evento non valido.');
				header('Location: ' . URL_ROOT . '/admin/events/pending');
				exit();
			}

			$event = $this->eventModel->find($id);
			if (!$event) {
				Session::setFlash('error', 'Evento non trovato.');
				header('Location: ' . URL_ROOT . '/admin/events/pending');
				exit();
			}
			$is_paid = isset($_POST['is_paid']) ? 1 : 0;
			$has_cosplay_contest = isset($_POST['has_cosplay_contest']) ? 1 : 0;

			$guestIds = [];
			if (!empty($_POST['guest_ids'])) {
				$guestIds = json_decode($_POST['guest_ids'], true) ?? [];
			}

			$data = [
				'titolo' => trim($_POST['titolo']),
				'descrizione' => $_POST['descrizione'],
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
				'approvato' => (int)($_POST['approvato'] ?? 0),
				'event_size' => (int)($_POST['event_size'] ?? 0),
				'immagine' => '',
				'id_immagine' => (int)($_POST['id_immagine'] ?? 0),
				'is_paid' => filter_var($is_paid, FILTER_VALIDATE_INT),
				'has_cosplay_contest' => filter_var($has_cosplay_contest, FILTER_VALIDATE_INT),
				'guest_ids' => json_encode($guestIds),
			];
			$errors = [];

			if (!empty($_FILES['immagine']['name'])) {

				$coverImageId = $this->imageService->replacePrimary(
					$_FILES['immagine'],
					'event',
					$id,
					$data['titolo']
				);

				//$coverImageId = $this->imageService->upload($_FILES['cover_image'], 'blog_post', $id, $data['titolo'], true);

				$data['cover_image_id'] = $coverImageId;
			}

			if (empty($data['titolo'])) $errors[] = 'Il titolo è obbligatorio.';
			if (strlen($data['titolo']) > 255) $errors[] = 'Il titolo è troppo lungo (max 255 caratteri).';
			if (empty($data['descrizione'])) $errors[] = 'La descrizione è obbligatoria.';
			if (empty($data['data_inizio'])) $errors[] = 'La data di inizio è obbligatoria.';

			if (!empty($data['data_inizio']) && !strtotime($data['data_inizio'])) $errors[] = 'Formato data di inizio non valido.';
			if (!empty($data['data_fine']) && !strtotime($data['data_fine'])) $errors[] = 'Formato data di fine non valido.';
			if (!empty($data['data_fine']) && !empty($data['data_inizio']) && strtotime($data['data_fine']) < strtotime($data['data_inizio'])) {
				$errors[] = 'La data di fine non può essere precedente alla data di inizio.';
			}

			if (empty($data['luogo'])) $errors[] = 'Il luogo è obbligatorio.';
			if (strlen($data['luogo']) > 255) $errors[] = 'Il luogo è troppo lungo (max 255 caratteri).';

			if ($data['regione_id'] === false || $data['regione_id'] === null || $data['regione_id'] <= 0) $errors[] = 'ID Regione non valido.';
			if ($data['provincia_id'] === false || $data['provincia_id'] === null || $data['provincia_id'] <= 0) $errors[] = 'ID Provincia non valido.';
			if ($data['comune_id'] === false || $data['comune_id'] === null || $data['comune_id'] <= 0) $errors[] = 'ID Comune non valido.';
			if ($data['tipo_evento_id'] === false || $data['tipo_evento_id'] === null || $data['tipo_evento_id'] <= 0) $errors[] = 'ID Tipo Evento non valido.';

			if ($data['latitudine'] === false || ($data['latitudine'] !== null && ($data['latitudine'] < -90 || $data['latitudine'] > 90))) {
				if (!empty($_POST['latitudine'])) {
					$errors[] = 'Latitudine non valida (deve essere tra -90 e 90).';
				}
			}
			if ($data['longitudine'] === false || ($data['longitudine'] !== null && ($data['longitudine'] < -180 || $data['longitudine'] > 180))) {
				if (!empty($_POST['longitudine'])) {
					$errors[] = 'Longitudine non valida (deve essere tra -180 e 180).';
				}
			}

			if ($data['sito_web'] === false && !empty(trim($_POST['sito_web'] ?? ''))) $errors[] = 'URL Sito Web non valido.';
			if ($data['social_facebook'] === false && !empty(trim($_POST['social_facebook'] ?? ''))) $errors[] = 'URL Social Facebook non valido.';
			if ($data['social_twitter'] === false && !empty(trim($_POST['social_twitter'] ?? ''))) $errors[] = 'URL Social Twitter non valido.';
			if ($data['social_instagram'] === false && !empty(trim($_POST['social_instagram'] ?? ''))) $errors[] = 'URL Social Instagram non valido.';
			if ($data['social_tiktok'] === false && !empty(trim($_POST['social_tiktok'] ?? ''))) $errors[] = 'URL Social TikTok non valido.';
			if ($data['social_youtube'] === false && !empty(trim($_POST['social_youtube'] ?? ''))) $errors[] = 'URL Social YouTube non valido.';


			if (!empty($errors)) {
				Session::setFlash('error', implode('<br>', $errors));
				$tipi_evento = $this->tipoEventoModel->getAll();
				$allRegioni = $this->regioneModel->getAll();
				$allProvince = $this->provinciaModel->getByRegioneId();
				$allComuni = $this->comuneModel->getAll();
				$breadcrumbs = [
					['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
					['label' => 'Tutti gli Eventi', 'url' => URL_ROOT . '/admin/events/all'],
					['label' => 'Modifica Evento: ' . $event['titolo'], 'url' => URL_ROOT . '/admin/events/edit/' . $id],
				];
				$this->view('admin/events/edit', array_merge($data, ['event' => $event, 'csrf_token' => $_SESSION['csrf_token'], 'tipi_evento' => $tipi_evento, 'all_regioni' => $allRegioni, 'all_province' => $allProvince, 'all_comuni' => $allComuni, 'breadcrumbs' => $breadcrumbs]), 'admin');
				return;
			}

			if ($this->eventModel->update($id, $data)) {
				$this->guestModel->saveGuests($id, $guestIds);
				Session::setFlash('success', 'Evento aggiornato con successo!');
				header("Location: /admin/events/edit/$id");


			} else {
				Session::setFlash('error', 'Errore durante l\'aggiornamento dell\'evento.');
				$tipi_evento = $this->tipoEventoModel->getAll();
				$allRegioni = $this->regioneModel->getAll();
				$allProvince = $this->provinciaModel->getByRegioneId();
				$allComuni = $this->comuneModel->getAll();
				$breadcrumbs = [
					['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
					['label' => 'Tutti gli Eventi', 'url' => URL_ROOT . '/admin/events/all'],
					['label' => 'Modifica Evento: ' . $event['titolo'], 'url' => URL_ROOT . '/admin/events/edit/' . $id],
				];
				$this->view('admin/events/edit', array_merge($data, ['event' => $event, 'csrf_token' => $_SESSION['csrf_token'], 'tipi_evento' => $tipi_evento, 'all_regioni' => $allRegioni, 'all_province' => $allProvince, 'all_comuni' => $allComuni, 'breadcrumbs' => $breadcrumbs]), 'admin');
			}
		} else {
			header('Location: ' . URL_ROOT . '/admin/events/all');
			exit();
		}
	}

	/**
	 * Approva un evento (ADMIN).
	 * @param array $params Contiene l'ID dell'evento.
	 */
	public function approve($params)
	{


		$id = $params[0] ?? null;
		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID evento non valido.');
			header('Location: ' . URL_ROOT . '/admin/events/pending');
			exit();
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$this->validateCsrfToken()) {
			header('Location: ' . URL_ROOT . '/admin/events/pending');
			exit();
		}

		if ($this->eventModel->approve($id)) {
			Session::setFlash('success', 'Evento approvato con successo!');
		} else {
			Session::setFlash('error', 'Errore durante l\'approvazione dell\'evento.');
		}
		header('Location: ' . URL_ROOT . '/admin/events/pending');
		exit();
	}

	/**
	 * Elimina un evento (ADMIN).
	 * @param array $params Contiene l'ID dell'evento.
	 */
	public function delete($params)
	{


		$id = $params[0] ?? null;
		if (!$id || !is_numeric($id)) {
			Session::setFlash('error', 'ID evento non valido.');
			header('Location: ' . URL_ROOT . '/admin/events/all');
			exit();
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$this->validateCsrfToken()) {
			header('Location: ' . URL_ROOT . '/admin/events/all');
			exit();
		}

		$event = $this->eventModel->find($id);
		if (!$event) {
			Session::setFlash('error', 'Evento non trovato.');
			header('Location: /admin/events/all');
			exit();
		}

		if ($event['immagine'] && file_exists(APP_ROOT . $event['immagine'])) {
			unlink(APP_ROOT . $event['immagine']);
		}

		if ($this->eventModel->delete($id)) {
			Session::setFlash('success', 'Evento eliminato con successo!');
		} else {
			Session::setFlash('error', 'Errore durante l\'eliminazione dell\'evento.');
		}
		header('Location: /admin/events/all');
		exit();
	}

	function generaTestoSEO($selected_regione = null, $selected_provincia = null, $selected_comune = null) {
		if ($selected_comune) {
			// Caso: COMUNE + PROVINCIA + REGIONE
			$localita = $selected_comune['nome'] . " (" . $selected_provincia['nome'] . ", " . $selected_regione['nome'] . ")";
			$testo = "Scopri tutti gli eventi cosplay a {$localita}. 
        Fiere, raduni e manifestazioni per appassionati di manga, anime e cultura pop 
        ti aspettano nel cuore di {$selected_comune['nome']}. 
        Resta aggiornato sulle date e partecipa agli eventi vicino a te.";
		} elseif ($selected_provincia) {
			// Caso: PROVINCIA + REGIONE
			$localita = $selected_provincia['nome'] . " in " . $selected_regione['nome'];
			$testo = "Scopri gli eventi cosplay nella provincia di {$localita}. 
        Convention, fiere del fumetto e raduni cosplay organizzati durante l’anno, 
        con informazioni sempre aggiornate. 
        Vivi la tua passione per anime, manga e videogiochi in {$selected_provincia['nome']}.";
		} elseif ($selected_regione) {
			// Caso: REGIONE
			$localita = $selected_regione['nome'];
			if($selected_regione['intro_html'] != ''){
				$testo = "<div class='mb-4 text-lg leading-relaxed'>" . $selected_regione['intro_html'] . "</div>" ?? '';
			}else{
				$testo = "Eventi cosplay in {$localita}: fiere, raduni e manifestazioni dedicate al mondo nerd e otaku. 
        Scopri le prossime date, le città coinvolte e le novità in programma. 
        La regione {$localita} ospita ogni anno eventi imperdibili per i fan del cosplay.";
			}


		} else {
			// Caso: ITALIA
			$testo = "<p class=\"mb-4 text-lg leading-relaxed\">Scopri i migliori eventi cosplay in tutta Italia. 
        Fiere del fumetto, raduni e manifestazioni cosplay da Nord a Sud, 
        con tutte le informazioni utili per partecipare e vivere la tua passione.</p>";
			$testo .= "<h2 class=\"text-2xl font-bold mb-4\">
    Calendario Eventi Cosplay in Italia
</h2>

<p class=\"mb-4 text-lg leading-relaxed\">
Benvenuto nel calendario degli eventi cosplay in Italia di ItalianCosplay, il punto di riferimento per trovare fiere del fumetto, comics, raduni cosplay, festival nerd e manifestazioni dedicate alla cultura pop in tutta Italia.
</p>

<p class=\"mb-4 text-lg leading-relaxed\">
In questa pagina puoi scoprire i prossimi eventi cosplay in programma, filtrare gli appuntamenti per regione, provincia o comune e rimanere aggiornato sulle principali fiere italiane dedicate ad anime, manga, videogiochi, fantasy e intrattenimento geek. Ogni evento contiene informazioni utili come date, location, programma, ospiti, biglietti e collegamenti ai canali ufficiali.
</p>

<p class=\"mb-4 text-lg leading-relaxed\">
Il calendario viene aggiornato costantemente con nuovi eventi cosplay da Nord a Sud Italia, includendo sia le grandi fiere del fumetto sia i piccoli raduni locali organizzati da associazioni, community e gruppi cosplay. Tra gli eventi presenti puoi trovare convention comics, festival giapponesi, eventi gaming, contest cosplay, mercatini nerd e appuntamenti dedicati ai fan di anime, manga, cinema, serie TV e videogiochi.
</p>

<p class=\"mb-4 text-lg leading-relaxed\">
Grazie ai filtri disponibili puoi trovare rapidamente gli eventi cosplay più vicini a te oppure scoprire le manifestazioni più importanti in programma nei prossimi weekend. La pagina include eventi in continuo aggiornamento per aiutare cosplayer, fotografi, visitatori e appassionati a organizzare la propria partecipazione agli eventi più attesi dell’anno.
</p>

<p class=\"mb-4 text-lg leading-relaxed\">
ItalianCosplay raccoglie eventi cosplay italiani in un unico calendario semplice da consultare, pensato per offrire una panoramica completa del panorama cosplay italiano. Che tu stia cercando una grande fiera comics oppure un piccolo evento locale, qui puoi trovare ogni settimana nuovi appuntamenti dedicati al mondo cosplay e nerd.
</p>";
		}

		return $testo;
	}
	function generaSchemaSEO($selected_regione = null, $selected_provincia = null, $selected_comune = null) {
		// Nome area geografica
		if ($selected_comune) {
			$locationName = $selected_comune['nome'] . " (" . $selected_provincia['nome'] . ", " . $selected_regione['nome'] . ")";
		} elseif ($selected_provincia) {
			$locationName = "Provincia di " . $selected_provincia['nome'] . " (" . $selected_regione['nome'] . ")";
		} elseif ($selected_regione) {
			$locationName = "Regione " . $selected_regione['nome'];
		} else {
			$locationName = "Italia";
		}

		// Schema base (puoi arricchirlo evento per evento se hai i dettagli)
		$schema = [
			"@context" => "https://schema.org",
			"@type" => "Event",
			"name" => "Eventi Cosplay in $locationName",
			"description" => "Scopri fiere, raduni e manifestazioni cosplay in $locationName: date, luoghi e novità.",
			"eventAttendanceMode" => "https://schema.org/OfflineEventAttendanceMode",
			"eventStatus" => "https://schema.org/EventScheduled",
			"location" => [
				"@type" => "Place",
				"name" => $locationName,
				"address" => [
					"@type" => "PostalAddress",
					"addressLocality" => $locationName,
					"addressCountry" => "IT",
				],
			],
			"organizer" => [
				"@type" => "Organization",
				"name" => "ItalianCosplay",
				"url" => "https://www.italiancosplay.it",
			],
		];

		return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . '</script>';
	}
	function generaSchemaListaEventi($eventi) {
		$schema = [
			"@context" => "https://schema.org",
			"@graph" => [],
		];
		foreach ($eventi as $evento) {
			// Solo eventi approvati
			$dataTesto = DateHelper::formatEventoPeriodo($evento['data_inizio'], $evento['data_fine']);
			$descrizione_pulita = $evento['titolo']." ".date('Y', strtotime($evento['data_inizio']))." si svolge ".$dataTesto." ".date('Y', strtotime($evento['data_inizio']))." a ".$evento['comune_nome']." con cosplay, gaming, fumetti, ospiti e stand.";
			//strip_tags(html_entity_decode($evento['descrizione'] ?? ''));

			$eventoSchema = [
				"@type" => "Event",
				"name" => $evento['titolo'] ?? '',
				"description" => $descrizione_pulita,
				"startDate" => $evento['data_inizio'] ?? '',
				"endDate" => $evento['data_fine'] ?? '',
				"eventAttendanceMode" => "https://schema.org/OfflineEventAttendanceMode",
				"eventStatus" => "https://schema.org/EventScheduled",
				"location" => [
					"@type" => "Place",
					"name" => $evento['luogo'] ?? '',
					"address" => [
						"@type" => "PostalAddress",
						"addressLocality" => $evento['comune_nome'] ?? '',
						"addressRegion" => $evento['regione_nome'] ?? '',
						"addressCountry" => "IT",
					],
					"geo" => [
						"@type" => "GeoCoordinates",
						"latitude" => $evento['latitudine'] ?? '',
						"longitude" => $evento['longitudine'] ?? '',
					],
				],
				"organizer" => [
					"@type" => "Organization",
					"name" => "ItalianCosplay",
					"url" => "https://www.italiancosplay.it",
				],
			"image" => [
				"https://www.italiancosplay.it".$evento['immagine'],
			],
			"offers" => [
				"@type" => "Offer",
				"url" => "https://www.italiancosplay.it/eventi-cosplay/".$evento['slug'],
				"price" => "0",
				"priceCurrency" => "EUR",
				"availability" => "https://schema.org/InStock", // significa: evento ancora aperto
				"validFrom" => date('c'), // ISO 8601
			],

			// 🎭 Performer — può essere un gruppo, cosplay guest, o un generico performer
			/*"performer" => [
				"@type" => "Person",
				"name" => "Cosplayer italiani",
			],*/
			];

			// Aggiungi eventuali social o sito web se esistono
			if (!empty($evento['sito_web'])) $eventoSchema['url'] = $evento['sito_web'];
			if (!empty($evento['social_facebook'])) $eventoSchema['sameAs'][] = $evento['social_facebook'];
			if (!empty($evento['social_twitter'])) $eventoSchema['sameAs'][] = $evento['social_twitter'];
			if (!empty($evento['social_instagram'])) $eventoSchema['sameAs'][] = $evento['social_instagram'];
			if (!empty($evento['social_youtube'])) $eventoSchema['sameAs'][] = $evento['social_youtube'];
			if (!empty($evento['social_tiktok'])) $eventoSchema['sameAs'][] = $evento['social_tiktok'];

			$schema['@graph'][] = $eventoSchema;
		}

		return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . '</script>';
	}
	function generaSchemaListaEventiSemplice($eventi) {
		$schema = [
			"@context" => "https://schema.org",
			"@graph" => [],
		];
		foreach ($eventi as $evento) {
			// Solo eventi approvati
			$dataTesto = DateHelper::formatEventoPeriodo($evento['data_inizio'], $evento['data_fine']);
			$descrizione_pulita = $evento['titolo']." ".date('Y', strtotime($evento['data_inizio']))." si svolge ".$dataTesto." ".date('Y', strtotime($evento['data_inizio']))." a ".$evento['comune_nome']." con cosplay, gaming, fumetti, ospiti e stand.";
			//strip_tags(html_entity_decode($evento['descrizione'] ?? ''));

			$eventoSchema = [
				"@context" => "https://schema.org",
				"@type" => "Event",
				"name" => $evento['titolo'] ?? '',
				"description" => $descrizione_pulita ?? '',

				"startDate" => !empty($evento['data_inizio']) ? date('c', strtotime($evento['data_inizio'])) : null,
				"endDate" => !empty($evento['data_fine']) ? date('c', strtotime($evento['data_fine'])) : null,

				"eventAttendanceMode" => "https://schema.org/OfflineEventAttendanceMode",
				"eventStatus" => "https://schema.org/EventScheduled",

				"location" => [
					"@type" => "Place",
					"name" => $evento['luogo'] ?? '',
					"address" => [
						"@type" => "PostalAddress",
						"addressLocality" => $evento['comune_nome'] ?? '',
						"addressRegion" => $evento['regione_nome'] ?? '',
						"addressCountry" => "IT",
					],
					"geo" => [
						"@type" => "GeoCoordinates",
						"latitude" => $evento['latitudine'] ?? null,
						"longitude" => $evento['longitudine'] ?? null,
					],
				],

				"image" => [
					"https://www.italiancosplay.it" . ltrim($evento['immagine'] ??'', '/')
				],

				"organizer" => [
					"@type" => "Organization",
					"name" => "ItalianCosplay",
					"url" => "https://www.italiancosplay.it",
				],

				"offers" => [
					"@type" => "Offer",
					"url" => "https://www.italiancosplay.it/eventi-cosplay/" . $evento['slug'],
					"price" => "0",
					"priceCurrency" => "EUR",
					"availability" => "https://schema.org/InStock",
					"validFrom" => date('c'),
				],
			];

			// Aggiungi eventuali social o sito web se esistono
			if (!empty($evento['sito_web'])) $eventoSchema['url'] = $evento['sito_web'];
			if (!empty($evento['social_facebook'])) $eventoSchema['sameAs'][] = $evento['social_facebook'];
			if (!empty($evento['social_twitter'])) $eventoSchema['sameAs'][] = $evento['social_twitter'];
			if (!empty($evento['social_instagram'])) $eventoSchema['sameAs'][] = $evento['social_instagram'];
			if (!empty($evento['social_youtube'])) $eventoSchema['sameAs'][] = $evento['social_youtube'];
			if (!empty($evento['social_tiktok'])) $eventoSchema['sameAs'][] = $evento['social_tiktok'];

			$schema['@graph'][] = $eventoSchema;
		}

		return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . '</script>';
	}

	public function shortUrl($params)
	{
		$slug = $params[0] ?? null;
		if (!$slug) {
			header('Location: ' . $this->eventsBasePath);
			exit;
		}
		// 1. Controlla se lo slug è una regione
		$regione = $this->regioneModel->findBySlug($slug);
		if ($regione) {
			$this->index([$slug]);
		}
		// 2. Controlla se lo slug è un evento
		$event = $this->eventModel->findBySlug($slug);
		if ($event && $event['approvato'] == 1) {
			$this->show([$slug]);
		}
		// 3. Slug non trovato → 404
		/*Session::setFlash('error', 'Pagina non trovata.');
		header('Location: ' . $this->eventsBasePath);
		exit;*/
	}

	public function weekend()
	{
		$noindex = false;

		$eventsData = $this->eventFeedService->getWeekend();

		$events = $eventsData['all'];

		foreach ($eventsData['all'] as $index => &$event) {
			$cover = $this->imageService->getPrimary('event', $event['id']);
			if(!empty($cover)){
				$event['immagine'] = '/public_assets/'.$cover['path'];
				$event['immagine_width'] = $cover['width'];
				$event['immagine_height'] = $cover['height'];
			}else{
				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}
		}
		foreach ($eventsData['new'] as $index => &$event) {
			$cover = $this->imageService->getPrimary('event', $event['id']);
			if(!empty($cover)){
				$event['immagine'] = '/public_assets/'.$cover['path'];
				$event['immagine_width'] = $cover['width'];
				$event['immagine_height'] = $cover['height'];
			}else{
				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}
		}
		foreach ($eventsData['big'] as $index => &$event) {
			$cover = $this->imageService->getPrimary('event', $event['id']);
			if(!empty($cover)){
				$event['immagine'] = '/public_assets/'.$cover['path'];
				$event['immagine_width'] = $cover['width'];
				$event['immagine_height'] = $cover['height'];
			}else{
				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}
		}
		foreach ($eventsData['top3'] as $index => &$event) {
			$cover = $this->imageService->getPrimary('event', $event['id']);
			if(!empty($cover)){
				$event['immagine'] = '/public_assets/'.$cover['path'];
				$event['immagine_width'] = $cover['width'];
				$event['immagine_height'] = $cover['height'];
			}else{
				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}
		}


		if (empty($events)) {
			//$noindex = true;
		}

		$canonicalUrl = URL_ROOT_SITE . '/eventi-cosplay-weekend';

		$weekend = $eventsData['label'];
		$eventiTop_titoli = '';

		if (!empty($eventsData['top3'])) {
			$eventiTop_titoli = implode(',', array_column($eventsData['top3'], 'titolo'));
		}

		$breadcrumbs = [
			['label' => 'Home', 'url' => URL_ROOT_SITE . '/'],
			['label' => 'Eventi Cosplay Nel Weekend ' . $weekend, 'url' => URL_ROOT_SITE . '/eventi-cosplay-weekend'],
		];

		$SchemaListaEventi = $this->generaSchemaListaEventiSemplice($events);

		$testo = "Questo weekend, $weekend, in tutta Italia si svolgono numerosi eventi dedicati al mondo cosplay, fumetti e cultura nerd.
	Dai grandi festival alle fiere locali, gli appassionati avranno diverse occasioni per incontrare cosplayer, partecipare a contest e vivere la community dal vivo.
	Di seguito trovi la lista completa degli eventi cosplay in programma questo fine settimana.";

		$this->view('events/weekend', [
			'events' => $eventsData['all'],
			'nuovi' => $eventsData['new'],
			'piuImportanti' => $eventsData['big'],
			'top3' => $eventsData['top3'],
			'weekend' => $weekend,
			'noindex' => $noindex,
			'canonicalUrl' => $canonicalUrl,
			'breadcrumbs' => $breadcrumbs,
			'SchemaListaEventi' => $SchemaListaEventi,
			'testo_descrittivo' => $testo,
			'eventiTop_titoli' => $eventiTop_titoli
		]);
	}
	public function updateAllScores(){
		//var_dump($this->eventRankingService->updateAllScores());
		$signal = new EventSignalService();
		$scoring = new EventScoringEngine();
		$repo = new EventRepository();

		$views7d = $signal->getViews7d();
		$events = $repo->getAll();

		foreach ($events as $event) {

			$views = $views7d[$event['id']] ?? 0;

			$score = $scoring->calculate($event, $views);

			$repo->updateScore($event['id'], $score, $views);
		}

		echo "OK - scores aggiornati\n";
	}
	public function mese()
	{

		$feed = $this->eventFeedService->getMonth();


		foreach ($feed['all'] as $index => &$event) {
			$cover = $this->imageService->getPrimary('event', $event['id']);
			if(!empty($cover)){
				$event['immagine'] = '/public_assets/'.$cover['path'];
				$event['immagine_width'] = $cover['width'];
				$event['immagine_height'] = $cover['height'];
			}else{
				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}
		}
		foreach ($feed['new'] as $index => &$event) {
			$cover = $this->imageService->getPrimary('event', $event['id']);
			if(!empty($cover)){
				$event['immagine'] = '/public_assets/'.$cover['path'];
				$event['immagine_width'] = $cover['width'];
				$event['immagine_height'] = $cover['height'];
			}else{
				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}
		}
		foreach ($feed['big'] as $index => &$event) {
			$cover = $this->imageService->getPrimary('event', $event['id']);
			if(!empty($cover)){
				$event['immagine'] = '/public_assets/'.$cover['path'];
				$event['immagine_width'] = $cover['width'];
				$event['immagine_height'] = $cover['height'];
			}else{
				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}
		}
		foreach ($feed['top3'] as $index => &$event) {
			$cover = $this->imageService->getPrimary('event', $event['id']);
			if(!empty($cover)){
				$event['immagine'] = '/public_assets/'.$cover['path'];
				$event['immagine_width'] = $cover['width'];
				$event['immagine_height'] = $cover['height'];
			}else{
				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}
		}

		// 📅 nome mese SEO

		$mese = $feed['label'];
		$frasi = [
			"un mese imperdibile per gli appassionati",
			"uno dei momenti migliori dell’anno per il cosplay",
			"un periodo ricco di eventi in tutta Italia"
		];
		$totEventi = 0;
		$listaTop = '';
		$randomFrase = $frasi[array_rand($frasi)];
		$testo = "<p>
{$mese} è {$randomFrase} 🇮🇹 è uno dei mesi più ricchi di eventi cosplay in Italia 🇮🇹  
Con oltre {$totEventi} eventi tra fiere del fumetto, festival e raduni, questo mese offre appuntamenti imperdibili per tutti gli appassionati.

Dai grandi eventi come {$listaTop}, fino alle fiere locali in crescita, ecco i migliori eventi cosplay del mese selezionati per te.
</p>";

		// 🍞 breadcrumb
		$breadcrumbs = [
			['label' => 'Home', 'url' => URL_ROOT_SITE . '/'],
			['label' => "Eventi Cosplay {$mese}", 'url' => URL_ROOT_SITE . '/eventi-cosplay-mese'],
		];

		$this->view('events/mese', [
			'events' => $feed['all'],
			'nuovi' => $feed['new'],
			'piuImportanti' => $feed['big'],
			'top3' => $feed['top3'],
			'mese' => $mese,
			'breadcrumbs' => $breadcrumbs,
			'testo_descrittivo' => $testo
		]);
	}



	function formattaLista($items) {
		$count = count($items);

		if ($count === 0) return '';
		if ($count === 1) return $items[0];
		if ($count === 2) return $items[0] . ' e ' . $items[1];

		$last = array_pop($items);
		return implode(', ', $items) . ' e ' . $last;
	}

	public function eventImageMigrationService(){
		$migration = new EventImageMigrationService($this->eventModel->getDbConnection());

		$migration->migrate();
	}





}
