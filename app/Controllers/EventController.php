<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Mailer;
use App\Core\Session;
use App\Helpers\DateHelper;
use App\Helpers\MonthHelper;
use App\Helpers\WeekendHelper;
use App\Models\Comune;
use App\Models\Event;
use App\Models\EventMaster;
use App\Models\Guest;
use App\Models\Provincia;
use App\Models\Regione;
use App\Models\TipoEvento;
use App\Repositories\EventRepository;
use App\Repositories\OrganizationRepository;
use App\Services\EventAnalyticsService;
use App\Services\EventFeedService;
use App\Services\EventImageMigrationService;
use App\Services\EventRankingService;
use App\Services\EventScoringEngine;
use App\Services\EventScraperService;
use App\Services\EventSignalService;
use App\Services\ImageService;
use App\Services\EventAgendaService;
use App\Services\EventReportAnalyticsService;
use App\Services\CosplayPortfolioService;
use App\Services\EventReportConsentService;
use App\Services\ProvinceCorrelateService;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use App\Support\AuditLogActionType;
use App\Services\FavoriteService;
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
use const URL_ROOT_SITE;

class EventController extends Controller{
	private Event $eventModel;
	private Guest $guestModel;
	private TipoEvento $tipoEventoModel;
	private Regione $regioneModel;
	private Provincia $provinciaModel;
	private Comune $comuneModel;
	private EventMaster $eventMasterModel;
	private $uploadDir = APP_ROOT . '/public_assets/uploads/events/';
	private $eventsBasePath; // Proprietà per il percorso base degli eventi (URL assoluto)
	private $relativeEventsBasePath; // Proprietà per il percorso base degli eventi (URL relativo)
	private EventScraperService $eventScraperService;
	private EventAnalyticsService $eventAnalyticsService;
	private EventRankingService $eventRankingService;
	private EventFeedService $eventFeedService;
	private ImageService $imageService;
	private EventAgendaService $eventAgendaService;
	private EventReportAnalyticsService $eventReportAnalyticsService;
	private CosplayPortfolioService $cosplayPortfolioService;
	private ProvinceCorrelateService $provinceCorrelateService;
	private EventReportConsentService $eventReportConsentService;
	private EventRepository $eventRepository;
	private OrganizationRepository $organizationRepository;
	private AuditLogService $auditLogService;
	private NotificationService $notificationService;

	public function __construct(){
		$this->eventModel = new Event();
		$this->guestModel = new Guest();
		$this->tipoEventoModel = new TipoEvento();
		$this->regioneModel = new Regione();
		$this->provinciaModel = new Provincia();
		$this->comuneModel = new Comune();
		$this->eventMasterModel = new EventMaster();
		$this->eventScraperService = new EventScraperService();
		$this->eventAnalyticsService = new EventAnalyticsService();
		$this->eventRankingService = new EventRankingService();
		$this->eventFeedService = new EventFeedService();
		$this->eventAgendaService = new EventAgendaService();
		$this->eventReportAnalyticsService = new EventReportAnalyticsService();
		$this->cosplayPortfolioService = new CosplayPortfolioService();
		$this->provinceCorrelateService = new ProvinceCorrelateService();
		$this->eventReportConsentService = new EventReportConsentService();
		$this->eventRepository = new EventRepository();
		$this->organizationRepository = new OrganizationRepository();
		$this->auditLogService = new AuditLogService();
		$this->notificationService = new NotificationService();
		$this->imageService = new ImageService($this->eventModel->getDbConnection());
		// Definisci il percorso base per gli eventi qui per evitare duplicazioni
		// CORREZIONE QUI: Rimuovi lo slash finale da URL_ROOT prima di concatenare
		$this->eventsBasePath = rtrim(URL_ROOT, '/') . '/eventi-cosplay';
		$this->relativeEventsBasePath = '/eventi-cosplay'; // Percorso relativo per i controlli URI
		// Assicurati che la directory di upload esista
		if(!is_dir($this->uploadDir)){
			mkdir($this->uploadDir, 0775, true);
		}
	}

	public function scrape(){
		header('Content-Type: application/json; charset=utf-8');
		$url = $_POST['url'] ?? null;
		if(!$url){
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
	 *
	 * @param array $params Contiene lo slug della regione o della provincia se presente.
	 */
	public function index(array $params = []){
		$this->requireFeature('enable_events', 'Gli eventi pubblici sono temporaneamente disattivati.');
		$searchQuery = trim((string) ($_GET['q'] ?? ''));
		$regioneSlug = null;
		$provinciaSlug = null;
		$comuneSlug = null;
		if(!empty($params)){
			if(stripos($params[0], '/') !== false){
				$regioneSlug = $params[1] ?? null;
				$provinciaSlug = $params[2] ?? null;
				$comuneSlug = $params[3] ?? null;
			}else{
				$regioneSlug = $params[0] ?? null;
				$provinciaSlug = $params[1] ?? null;
				$comuneSlug = $params[2] ?? null;
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
		if($regioneSlug){
			$selectedRegione = $this->regioneModel->findBySlug($regioneSlug);
			if(!$selectedRegione){
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
		if($provinciaSlug && $selectedRegione){
			$selectedProvincia = $this->provinciaModel->findBySlug($provinciaSlug);
			if(
				!$selectedProvincia ||
				$selectedProvincia['regione_id'] != $selectedRegione['id']
			){
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
		if($comuneSlug && $selectedProvincia){
			$selectedComune = $this->comuneModel->findBySlug($comuneSlug);
			if(
				!$selectedComune ||
				$selectedComune['provincia_id'] != $selectedProvincia['id']
			){
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
		if(!empty($selectedRegione) && empty($selectedProvincia)){
			$provinceCorrelate = $this->provinceCorrelateService->getProvinceCorrelate($selectedRegione['slug']);
		}
		/* ---------------- EVENTI ---------------- */
		$events = $this->eventModel->getApprovedEvents(
			$regioneId,
			$provinciaId,
			$comuneId,
			0,
			$searchQuery
		);
		foreach($events as $index => &$event){
			$imageService = new ImageService($this->eventModel->getDbConnection());
			$images = $this->eventModel->getImages($event['id']);
			$cover = $this->imageService->getPrimary('event', $event['id']);
			if(!empty($cover)){
				$event['immagine'] = '/public_assets/' . $cover['path'];
				$event['immagine_width'] = $cover['width'];
				$event['immagine_height'] = $cover['height'];
			}else{
				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}
			if(!$event){
				Session::setFlash('error', 'Evento non trovato.');
				header('Location: ' . URL_ROOT . '/admin/events/pending');
				exit();
			}
			$event['lazy'] = ($index < 3) ? false : true;
		}
		unset($event);
		$agendaStates = [];
		if (!empty($_SESSION['user_id']) && !empty($events)) {
			$agendaStates = $this->eventAgendaService->getUserAgendaStates(
				(int) $_SESSION['user_id'],
				array_column($events, 'id')
			);
		}
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
		if(empty($events)){
			$noindex = true;
		}
		if ($searchQuery !== '') {
			$noindex = true;
		}
		if($selectedRegione){
			$canonicalUrl .= '/' . $selectedRegione['slug'];
		}
		if($selectedProvincia){
			$canonicalUrl .= '/' . $selectedProvincia['slug'];
		}
		if($selectedComune){
			$canonicalUrl .= '/' . $selectedComune['slug'];
		}
		/* ---------------- VIEW ---------------- */
		$this->view('events/index', [
			'events'              => $events,
			'selected_regione'    => $selectedRegione,
			'selected_provincia'  => $selectedProvincia,
			'selected_comune'     => $selectedComune,
			'breadcrumbs'         => $breadcrumbs,
			'all_regioni'         => $this->regioneModel->getAll(),
			'all_province'        => $this->provinciaModel->getByRegioneId($regioneId),
			'all_comuni'          => $this->comuneModel->getAll($provinciaId),
			'testo_descrittivo'   => $testo_descrittivo,
			'schema_org_generico' => $schema_org_generico,
			'SchemaListaEventi'   => $SchemaListaEventi,
			'noindex'             => $noindex,
			'canonicalUrl'        => $canonicalUrl,
			'provinceCorrelate'   => $provinceCorrelate,
			'agendaStates'        => $agendaStates,
			'searchQuery'         => $searchQuery,
			'availableYears'      => $this->eventModel->getAvailableYears(),
		]);
	}

	public function year(array $params): void
	{
		$this->requireFeature('enable_events', 'Gli eventi pubblici sono temporaneamente disattivati.');

		$yearValue = (string) ($params[0] ?? '');
		if (!preg_match('/^\d{4}$/', $yearValue)) {
			header('Location: ' . $this->eventsBasePath);
			exit();
		}

		$year = (int) $yearValue;
		$currentYear = (int) date('Y');
		if ($year < 2020 || $year > ($currentYear + 5)) {
			header('Location: ' . $this->eventsBasePath);
			exit();
		}

		$summary = $this->eventModel->getYearSummary($year);
		if ($summary === null) {
			header('Location: ' . $this->eventsBasePath);
			exit();
		}

		$events = $this->eventModel->getEventsByYear($year);
		foreach ($events as $index => &$event) {
			$cover = $this->imageService->getPrimary('event', $event['id']);
			if (!empty($cover)) {
				$event['immagine'] = '/public_assets/' . $cover['path'];
				$event['immagine_width'] = $cover['width'];
				$event['immagine_height'] = $cover['height'];
			} else {
				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}
			$event['lazy'] = ($index < 3) ? false : true;
		}
		unset($event);

		$eventsByMonth = [];
		foreach ($events as $event) {
			$monthNumber = (int) date('n', strtotime((string) $event['data_inizio']));
			$eventsByMonth[$monthNumber][] = $event;
		}

		$months = $this->eventModel->getAvailableMonthsByYear($year);
		$canonicalUrl = URL_ROOT_SITE . '/eventi-cosplay-' . $year;
		$breadcrumbs = [
			['label' => 'Home', 'url' => URL_ROOT_SITE . '/'],
			['label' => 'Eventi Cosplay Italia', 'url' => URL_ROOT_SITE . '/eventi-cosplay'],
			['label' => 'Eventi Cosplay ' . $year, 'url' => $canonicalUrl],
		];

		$this->view('events/year', [
			'year' => $year,
			'events' => $events,
			'eventsByMonth' => $eventsByMonth,
			'months' => $months,
			'summary' => $summary,
			'breadcrumbs' => $breadcrumbs,
			'canonicalUrl' => $canonicalUrl,
			'eventCount' => (int) $summary['total'],
		]);
	}

	public function updateAgendaStatus(): void
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Location: /eventi-cosplay');
			exit();
		}

		if (!isset($_SESSION['user_id'])) {
			header('Location: /login');
			exit();
		}

		if (!$this->isValidCsrfToken()) {
			Session::setFlash('error', 'Richiesta non valida.');
			header('Location: ' . ($_POST['redirect_to'] ?? '/eventi-cosplay'));
			exit();
		}

		$userId = (int) $_SESSION['user_id'];
		$eventId = (int) ($_POST['event_id'] ?? 0);
		$status = trim((string) ($_POST['status'] ?? ''));
		$redirectTo = $_POST['redirect_to'] ?? '/eventi-cosplay';
		$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

		if ($eventId <= 0 || $status === '') {
			$this->respondAgendaUpdate($isAjax, false, null, $redirectTo, 'Dati non validi.');
		}

		if (!$this->eventAgendaService->eventExists($eventId)) {
			$this->respondAgendaUpdate($isAjax, false, null, $redirectTo, 'Evento non trovato.');
		}

		try {
			$ok = $status === 'remove'
				? $this->eventAgendaService->removeStatus($userId, $eventId)
				: $this->eventAgendaService->setStatus($userId, $eventId, $status);
		} catch (\Throwable $exception) {
			$this->respondAgendaUpdate(
				$isAjax,
				false,
				null,
				$redirectTo,
				'Impossibile aggiornare l\'agenda.'
			);
		}

		$this->respondAgendaUpdate(
			$isAjax,
			$ok,
			$status === 'remove' ? null : $status,
			$redirectTo,
			$ok ? 'Agenda aggiornata.' : 'Impossibile aggiornare l\'agenda.'
		);
	}

	public function saveCosplaySelection(): void
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Location: /eventi-cosplay');
			exit();
		}

		if (!isset($_SESSION['user_id'])) {
			header('Location: /login');
			exit();
		}

		$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
		if (!$this->isValidCsrfToken()) {
			$this->respondCosplaySelection($isAjax, false, 'Richiesta non valida.', (string) ($_POST['redirect_to'] ?? '/eventi-cosplay'));
		}

		$userId = (int) $_SESSION['user_id'];
		$eventId = (int) ($_POST['event_id'] ?? 0);
		$portfolioId = (int) ($_POST['portfolio_id'] ?? 0);
		$status = (string) ($_POST['status'] ?? 'porterò');
		$redirectTo = (string) ($_POST['redirect_to'] ?? '/eventi-cosplay');

		if ($eventId <= 0 || $portfolioId <= 0) {
			$this->respondCosplaySelection($isAjax, false, 'Dati non validi.', $redirectTo);
		}

		$ok = $this->cosplayPortfolioService->saveEventSelection($userId, $eventId, $portfolioId, $status);
		$this->respondCosplaySelection(
			$isAjax,
			$ok,
			$ok ? 'Cosplay collegato all’evento.' : 'Impossibile collegare il cosplay all’evento.',
			$redirectTo,
			$ok ? [
				'event_id' => $eventId,
				'portfolio_id' => $portfolioId,
				'status' => $status,
				'selections' => $this->cosplayPortfolioService->getCharacterSelectionsForEvent($userId, $eventId),
			] : []
		);
	}

	public function removeCosplaySelection(): void
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Location: /eventi-cosplay');
			exit();
		}

		if (!isset($_SESSION['user_id'])) {
			header('Location: /login');
			exit();
		}

		$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
		if (!$this->isValidCsrfToken()) {
			$this->respondCosplaySelection($isAjax, false, 'Richiesta non valida.', (string) ($_POST['redirect_to'] ?? '/eventi-cosplay'));
		}

		$userId = (int) $_SESSION['user_id'];
		$eventId = (int) ($_POST['event_id'] ?? 0);
		$redirectTo = (string) ($_POST['redirect_to'] ?? '/eventi-cosplay');

		if ($eventId <= 0) {
			$this->respondCosplaySelection($isAjax, false, 'Dati non validi.', $redirectTo);
		}

		$portfolioId = (int) ($_POST['portfolio_id'] ?? 0);
		$ok = $this->cosplayPortfolioService->removeEventSelection($userId, $eventId, $portfolioId > 0 ? $portfolioId : null);
		$this->respondCosplaySelection(
			$isAjax,
			$ok,
			$ok ? 'Associazione cosplay rimossa.' : 'Impossibile rimuovere l’associazione.',
			$redirectTo,
			[
				'event_id' => $eventId,
				'selections' => $ok ? $this->cosplayPortfolioService->getCharacterSelectionsForEvent($userId, $eventId) : [],
			]
		);
	}

	private function respondAgendaUpdate(bool $isAjax, bool $success, ?string $status, string $redirectTo, string $message): void
	{
		if ($isAjax) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode([
				'success' => $success,
				'status' => $status,
				'message' => $message,
			], JSON_UNESCAPED_UNICODE);
			exit();
		}

		Session::setFlash($success ? 'success' : 'error', $message);
		header('Location: ' . $redirectTo);
		exit();
	}

	private function respondCosplaySelection(bool $isAjax, bool $success, string $message, string $redirectTo, array $extra = []): void
	{
		if ($isAjax) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array_merge([
				'success' => $success,
				'message' => $message,
			], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit();
		}

		Session::setFlash($success ? 'success' : 'error', $message);
		header('Location: ' . $redirectTo);
		exit();
	}

	private function isValidCsrfToken(): bool
	{
		return !empty($_POST['csrf_token'])
			&& !empty($_SESSION['csrf_token'])
			&& hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
	}

	/**
	 * Mostra il form per segnalare un nuovo evento (pubblico).
	 */
	public function segnalaEvento(){
		if(!isset($_SESSION['csrf_token'])){
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
			'csrf_token'   => $_SESSION['csrf_token'],
			'tipi_evento'  => $tipi_evento,
			'all_regioni'  => $allRegioni,
			'all_province' => $allProvince,
			'all_comuni'   => $allComuni,
			'breadcrumbs'  => $breadcrumbs,
		]);
	}

	public function trackReportForm(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(405);
			echo json_encode(['success' => false, 'message' => 'Metodo non consentito.']);
			exit();
		}

		if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Token CSRF non valido.']);
			exit();
		}

		$eventName = trim((string) ($_POST['event_name'] ?? ''));
		$allowedEvents = ['form_start', 'field_progress', 'privacy_accept', 'submit_success', 'submit_error'];
		if (!in_array($eventName, $allowedEvents, true)) {
			http_response_code(422);
			echo json_encode(['success' => false, 'message' => 'Evento di tracking non valido.']);
			exit();
		}

		$sessionKey = session_id() !== '' ? session_id() : hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
		$stepName = trim((string) ($_POST['step_name'] ?? '')) ?: null;
		$fieldsCompleted = isset($_POST['fields_completed']) ? max(0, min(100, (int) $_POST['fields_completed'])) : null;
		$eventId = isset($_POST['event_id']) && is_numeric($_POST['event_id']) ? (int) $_POST['event_id'] : null;

		$tracked = $this->eventReportAnalyticsService->track([
			'event_id' => $eventId,
			'session_key' => $sessionKey,
			'event_name' => $eventName,
			'step_name' => $stepName,
			'fields_completed' => $fieldsCompleted,
			'page_url' => $_POST['page_url'] ?? ($_SERVER['HTTP_REFERER'] ?? null),
		]);

		echo json_encode([
			'success' => $tracked,
		]);
		exit();
	}

	public function salvaEventoSegnalato(){
	}

	/**
	 * Salva un nuovo evento segnalato (pubblico).
	 */
	public function store(){
		$this->requireFeature('enable_events', 'La segnalazione eventi è temporaneamente disattivata.');
		if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
			Session::setFlash('error', 'Errore di sicurezza: richiesta non valida (CSRF).');
			header('Location: /segnala-evento-cosplay');
			exit();
		}
		error_log(__LINE__);
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			$data = [
				'titolo'              => trim($_POST['titolo']),
				'slug'                => trim($_POST['slug'] ?? ''),
				'anno'                => (int) ($_POST['anno'] ?? date('Y')),
				'descrizione'         => $_POST['descrizione'],
				'seo_title'           => trim($_POST['seo_title'] ?? ''),
				'seo_description'     => trim($_POST['seo_description'] ?? ''),
				'data_inizio'         => trim($_POST['data_inizio']),
				'data_fine'           => trim($_POST['data_fine'] ?? ''),
				'luogo'               => trim($_POST['luogo']),
				'regione_id'          => filter_var($_POST['regione_id'] ?? null, FILTER_VALIDATE_INT),
				'provincia_id'        => filter_var($_POST['provincia_id'] ?? null, FILTER_VALIDATE_INT),
				'comune_id'           => filter_var($_POST['comune_id'] ?? null, FILTER_VALIDATE_INT),
				'latitudine'          => filter_var($_POST['latitudine'] ?? null, FILTER_VALIDATE_FLOAT),
				'longitudine'         => filter_var($_POST['longitudine'] ?? null, FILTER_VALIDATE_FLOAT),
				'sito_web'            => filter_var(trim($_POST['sito_web'] ?? ''), FILTER_VALIDATE_URL),
				'social_facebook'     => filter_var(trim($_POST['social_facebook'] ?? ''), FILTER_VALIDATE_URL),
				'social_twitter'      => filter_var(trim($_POST['social_twitter'] ?? ''), FILTER_VALIDATE_URL),
				'social_instagram'    => filter_var(trim($_POST['social_instagram'] ?? ''), FILTER_VALIDATE_URL),
				'social_tiktok'       => filter_var(trim($_POST['social_tiktok'] ?? ''), FILTER_VALIDATE_URL),
				'social_youtube'      => filter_var(trim($_POST['social_youtube'] ?? ''), FILTER_VALIDATE_URL),
				'tipo_evento_id'      => filter_var($_POST['tipo_evento_id'] ?? null, FILTER_VALIDATE_INT),
				'immagine'            => null,
				'approvato'           => 0,
				'event_size'          => filter_var($_POST['event_size'] ?? null, FILTER_VALIDATE_INT),
				'is_paid'             => isset($_POST['is_paid']) ? 1 : 0,
				'has_cosplay_contest' => isset($_POST['has_cosplay_contest']) ? 1 : 0,
			];
			error_log(__LINE__);
			$errors = [];
			$imageId = null;
			if(empty($data['titolo'])){
				$errors[] = 'Il titolo è obbligatorio.';
			}
			if(strlen($data['titolo']) > 255){
				$errors[] = 'Il titolo è troppo lungo (max 255 caratteri).';
			}
			if(strlen($data['seo_title']) > 255){
				$errors[] = 'Il SEO Title è troppo lungo (max 255 caratteri).';
			}
			if(strlen($data['seo_description']) > 500){
				$errors[] = 'La SEO Description è troppo lunga (max 500 caratteri).';
			}
			if(empty($data['descrizione'])){
				$errors[] = 'La descrizione è obbligatoria.';
			}
			if(empty($data['data_inizio'])){
				$errors[] = 'La data di inizio è obbligatoria.';
			}
			if(!empty($data['data_inizio']) && !strtotime($data['data_inizio'])){
				$errors[] = 'Formato data di inizio non valido.';
			}
			if(!empty($data['data_fine']) && !strtotime($data['data_fine'])){
				$errors[] = 'Formato data di fine non valido.';
			}
			if(!empty($data['data_fine']) && !empty($data['data_inizio']) && strtotime($data['data_fine']) < strtotime($data['data_inizio'])){
				$errors[] = 'La data di fine non può essere precedente alla data di inizio.';
			}
			if(empty($data['luogo'])){
				$errors[] = 'Il luogo è obbligatorio.';
			}
			if(strlen($data['luogo']) > 255){
				$errors[] = 'Il luogo è troppo lungo (max 255 caratteri).';
			}
			if($data['regione_id'] === false || $data['regione_id'] === null || $data['regione_id'] <= 0){
				$errors[] = 'ID Regione non valido.';
			}
			if($data['provincia_id'] === false || $data['provincia_id'] === null || $data['provincia_id'] <= 0){
				$errors[] = 'ID Provincia non valido.';
			}
			if($data['comune_id'] === false || $data['comune_id'] === null || $data['comune_id'] <= 0){
				$errors[] = 'ID Comune non valido.';
			}
			if($data['tipo_evento_id'] === false || $data['tipo_evento_id'] === null || $data['tipo_evento_id'] <= 0){
				$errors[] = 'ID Tipo Evento non valido.';
			}
			if (empty($_POST['privacy_accept'])) {
				$errors[] = 'Devi accettare l\'informativa privacy per inviare la segnalazione.';
			}
			if($data['sito_web'] === false && !empty(trim($_POST['sito_web'] ?? ''))){
				$errors[] = 'URL Sito Web non valido.';
			}
			if($data['social_facebook'] === false && !empty(trim($_POST['social_facebook'] ?? ''))){
				$errors[] = 'URL Social Facebook non valido.';
			}
			if($data['social_twitter'] === false && !empty(trim($_POST['social_twitter'] ?? ''))){
				$errors[] = 'URL Social Twitter non valido.';
			}
			if($data['social_instagram'] === false && !empty(trim($_POST['social_instagram'] ?? ''))){
				$errors[] = 'URL Social Instagram non valido.';
			}
			if($data['social_tiktok'] === false && !empty(trim($_POST['social_tiktok'] ?? ''))){
				$errors[] = 'URL Social TikTok non valido.';
			}
			if($data['social_youtube'] === false && !empty(trim($_POST['social_youtube'] ?? ''))){
				$errors[] = 'URL Social YouTube non valido.';
			}
			if(!empty($errors)){
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
			if($eventId > 0){
				try {
					$this->eventReportConsentService->storeAcceptance(
						$eventId,
						$_SERVER['REMOTE_ADDR'] ?? null,
						$_SERVER['HTTP_USER_AGENT'] ?? null
					);
				} catch (\Throwable $exception) {
					error_log('Event privacy acceptance save failed: ' . $exception->getMessage());
				}
				$this->eventReportAnalyticsService->track([
					'event_id' => $eventId,
					'session_key' => session_id() !== '' ? session_id() : hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '')),
					'event_name' => 'submit_success',
					'fields_completed' => 100,
					'page_url' => '/segnala-evento-cosplay',
				]);
				if (isset($_FILES['immagine']) && is_array($_FILES['immagine'])) {
					$this->imageService->upload(
						$_FILES['immagine'],
						'event',
						(int) $eventId,
						$data['titolo'],
						true
					);
				}
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
					$template,
					null,
					[Mailer::TAG_EVENT_REPORT]
				);
				header('Location: /segnala-evento-cosplay');
				exit();
			}else{
				error_log(__LINE__);
				$this->eventReportAnalyticsService->track([
					'session_key' => session_id() !== '' ? session_id() : hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '')),
					'event_name' => 'submit_error',
					'step_name' => 'save_event',
					'page_url' => '/segnala-evento-cosplay',
				]);
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
		}else{
			error_log(__LINE__);
			header('Location: /segnala-evento-cosplay');
			exit();
		}
	}

	/**
	 * Mostra i dettagli di un singolo evento (pubblico).
	 * Ora accetta lo slug invece dell'ID.
	 *
	 * @param array $params Contiene lo slug dell'evento.
	 */
	public function show($params){
		$this->requireFeature('enable_events', 'Gli eventi pubblici sono temporaneamente disattivati.');
		$slug = $params[0] ?? null;
		if(!$slug){
			Session::setFlash('error', 'Slug evento non valido.');
			header('Location: ' . $this->eventsBasePath);
			exit();
		}
		$event = $this->eventModel->findBySlug($slug);
		if(!$event){
			Session::setFlash('error', 'Evento non trovato o non ancora approvato.');
			header('Location: ' . $this->eventsBasePath);
			exit();
		}
		$cover = $this->imageService->getPrimary('event', $event['id']);
		if(!empty($cover)){
			$event['immagine'] = '/public_assets/' . $cover['path'];
			$event['immagine_width'] = $cover['width'];
			$event['immagine_height'] = $cover['height'];
		}else{
			$event['immagine'] = '';
			$event['immagine_width'] = '';
			$event['immagine_height'] = '';
		}
		$this->eventAnalyticsService->trackView($event['id']);
		if($event['approvato'] == 0){
			Session::setFlash('error', 'Evento non trovato o non ancora approvato.');
			header('Location: ' . $this->eventsBasePath);
			exit();
		}
		$this->eventsBasePath = URL_ROOT_SITE . '/eventi-cosplay';
		$eventMaster = null;
		$organizations = [];
		$isFavorited = false;
		$eventMasterCount = 0;
		$eventMasterEvents = [];
		if (!empty($event['event_master_id'])) {
			$eventMaster = $this->eventMasterModel->find((int) $event['event_master_id']);
			if ($eventMaster) {
				$organizations = $this->organizationRepository->findPublicForEvent((int) $event['id']);
				$eventMasterCount = $this->eventMasterModel->countEvents((int) $eventMaster['id']);
				$eventMasterEvents = $this->eventModel->getEventsByMasterId((int) $eventMaster['id'], (int) $event['id']);
			}
		}
		// Breadcrumbs
		$breadcrumbs = [
			['label' => 'Home', 'url' => URL_ROOT_SITE . '/'],
			['label' => 'Eventi Cosplay', 'url' => $this->eventsBasePath],
		];
		$regione = null;
		$provincia = null;
		if(isset($event['regione_id']) && $event['regione_id']){
			$regione = $this->regioneModel->find($event['regione_id']);
			if($regione){
				$breadcrumbs[] = ['label' => $regione['nome'], 'url' => $this->eventsBasePath . '/' . $regione['slug']];
			}
		}
		if(isset($event['provincia_id']) && $event['provincia_id'] && $regione){
			$provincia = $this->provinciaModel->find($event['provincia_id']);
			if($provincia){
				if(isset($event['comune_id']) && $event['comune_id']){
					$comune = $this->comuneModel->find($event['comune_id']);
					if($comune){
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
		if ($this->hasVisibleMaster($eventMaster, $eventMasterCount)) {
			$breadcrumbs[] = [
				'label' => $eventMaster['nome'],
				'url' => URL_ROOT_SITE . '/eventi-master/' . $eventMaster['slug'],
			];
		}
		$agendaStates = [];
		$cosplayPortfolio = [];
		$cosplaySelections = [];
		$publicCosplaySelections = $this->cosplayPortfolioService->getPublicEventCosplaySelections((int) $event['id']);
		if (!empty($_SESSION['user_id'])) {
			$userId = (int) $_SESSION['user_id'];
			$isFavorited = (new FavoriteService())->isFavorited($userId, 'event', (int) $event['id']);
			$agendaStates = $this->eventAgendaService->getUserAgendaStates($userId, [(int) $event['id']]);
			$cosplayPortfolio = $this->cosplayPortfolioService->getUserPortfolio($userId);
			$cosplaySelections = $this->cosplayPortfolioService->getCharacterSelectionsForEvent($userId, (int) $event['id']);
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
		foreach($similarEvents as &$similarEvent){
			$cover = $this->imageService->getPrimary('event', $similarEvent['id']);
			if(!empty($cover)){
				$similarEvent['immagine'] = '/public_assets/' . $cover['path'];
				$similarEvent['immagine_width'] = $cover['width'];
				$similarEvent['immagine_height'] = $cover['height'];
			}else{
				$similarEvent['immagine'] = '';
				$similarEvent['immagine_width'] = '';
				$similarEvent['immagine_height'] = '';
			}
		}
		$data = [
			'event'             => $event,
			'breadcrumbs'       => $breadcrumbs,
			'SchemaListaEventi' => $SchemaListaEventi,
			'canonicalUrl'      => $canonicalUrl,
			'similarEvents'     => $similarEvents,
			'eventMaster'       => $eventMaster,
			'organizations'     => $organizations,
			'eventMasterEvents' => $eventMasterEvents,
			'eventMasterCount'  => $eventMasterCount,
			'hasVisibleMaster'  => $this->hasVisibleMaster($eventMaster, $eventMasterCount),
			'isFavorited'       => $isFavorited,
			'favoriteEntityType'=> 'event',
			'agendaStatus'      => $agendaStates[(int) $event['id']] ?? null,
			'cosplayPortfolio'   => $cosplayPortfolio,
			'cosplaySelections'   => $cosplaySelections,
			'publicCosplaySelections' => $publicCosplaySelections,
		];
		$this->view('events/show', $data);
	}

	public function masterIndex(): void
	{
		$this->requireFeature('enable_events', 'Gli eventi pubblici sono temporaneamente disattivati.');
		$eventMasters = $this->eventMasterModel->getPublic();
		$masters = [];
		foreach ($eventMasters as $eventMaster) {
			$eventCount = $this->eventMasterModel->countEvents((int) $eventMaster['id']);
			if (!$this->hasVisibleMaster($eventMaster, $eventCount)) {
				continue;
			}

			$cover = $this->imageService->getPrimary('event_master', (int) $eventMaster['id'], 'medium');
			$masters[] = [
				'id' => (int) $eventMaster['id'],
				'nome' => $eventMaster['nome'] ?? '',
				'slug' => $eventMaster['slug'] ?? '',
				'descrizione' => $eventMaster['descrizione'] ?? '',
				'event_count' => $eventCount,
				'cover' => !empty($cover['path']) ? '/public_assets' . $cover['path'] : '',
			];
		}

		usort($masters, static function (array $left, array $right): int {
			$result = $right['event_count'] <=> $left['event_count'];
			if ($result !== 0) {
				return $result;
			}
			return strcmp($left['nome'], $right['nome']);
		});

		$breadcrumbs = [
			['label' => 'Home', 'url' => URL_ROOT_SITE . '/'],
			['label' => 'Eventi Cosplay', 'url' => URL_ROOT_SITE . '/eventi-cosplay'],
			['label' => 'Eventi Master', 'url' => URL_ROOT_SITE . '/eventi-master'],
		];

		$canonicalUrl = URL_ROOT_SITE . '/eventi-master';

		$this->view('events/master-index', [
			'masters' => $masters,
			'breadcrumbs' => $breadcrumbs,
			'canonicalUrl' => $canonicalUrl,
			'pageTitle' => 'Eventi Master',
		]);
	}

	public function masterShow($params)
	{
		$this->requireFeature('enable_events', 'Gli eventi pubblici sono temporaneamente disattivati.');
		$slug = $params[0] ?? null;
		if(!$slug){
			Session::setFlash('error', 'Slug evento master non valido.');
			header('Location: ' . URL_ROOT_SITE . '/eventi-cosplay');
			exit();
		}

		$eventMaster = $this->eventMasterModel->findPublicBySlug($slug);
		if(!$eventMaster){
			http_response_code(404);
			$this->view('errors/404');
			return;
		}

		$events = $this->eventModel->getEventsByMasterId((int) $eventMaster['id']);
		$organizations = [];
		$hasOrganizationAssociation = false;

		try {
			$organizations = $this->organizationRepository->findPublicForMaster((int) $eventMaster['id']);
			$hasOrganizationAssociation = $this->organizationRepository->hasMasterAssociation((int) $eventMaster['id']);
		} catch (\Throwable $exception) {
			error_log('EventController::masterShow organization lookup failed: ' . $exception->getMessage());
		}

		$cover = $this->imageService->getPrimary('event_master', (int) $eventMaster['id'], 'large');
		$coverUrl = !empty($cover['path']) ? '/public_assets' . $cover['path'] : '';
		$canonicalUrl = URL_ROOT_SITE . '/eventi-master/' . $eventMaster['slug'];
		$breadcrumbs = [
			['label' => 'Home', 'url' => URL_ROOT_SITE . '/'],
			['label' => 'Eventi Cosplay', 'url' => URL_ROOT_SITE . '/eventi-cosplay'],
			['label' => $eventMaster['nome'], 'url' => $canonicalUrl],
		];

		$this->view('events/master', [
			'eventMaster' => $eventMaster,
			'events' => $events,
			'coverUrl' => $coverUrl,
			'breadcrumbs' => $breadcrumbs,
			'canonicalUrl' => $canonicalUrl,
			'eventCount' => count($events),
			'organizations' => $organizations,
			'hasOrganizationAssociation' => $hasOrganizationAssociation,
			'claimUrl' => $hasOrganizationAssociation ? '' : URL_ROOT_SITE . '/eventi-master/' . rawurlencode($eventMaster['slug']) . '/riscatta',
		]);
	}


	// --- Metodi per l'Area Amministrativa ---
	/**
	 * Protegge i metodi admin e assicura la presenza di un token CSRF.
	 */
	/**
	 * Valida il token CSRF per le richieste POST/modifiche.
	 *
	 * @return bool True se il token è valido, false altrimenti.
	 */
	private function validateCsrfToken(){
		if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
			Session::setFlash('error', 'Errore di sicurezza: richiesta non valida (CSRF).');

			return false;
		}

		return true;
	}

	/**
	 * Un master è visibile nel frontend solo quando ha almeno due edizioni.
	 */
	private function hasVisibleMaster(?array $eventMaster = null, ?int $eventCount = null): bool
	{
		if (empty($eventMaster)) {
			return false;
		}

		if ($eventCount === null) {
			$eventCount = $this->eventMasterModel->countEvents((int) $eventMaster['id']);
		}

		return $eventCount >= 2;
	}

	/**
	 * Mostra la lista degli eventi in attesa di approvazione (ADMIN).
	 */
	public function pending(){
		$events = $this->eventModel->getPendingEvents();
		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Eventi in Attesa', 'url' => URL_ROOT . '/admin/events/pending'],
		];
		$this->view('admin/events/pending', ['events' => $events, 'csrf_token' => $_SESSION['csrf_token'], 'breadcrumbs' => $breadcrumbs], 'admin');
	}

	public function pendingData(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$page = max((int) ($_GET['page'] ?? 1), 1);
		$perPage = (int) ($_GET['perPage'] ?? 25);
		if (!in_array($perPage, [10, 25, 50, 100], true)) {
			$perPage = 25;
		}

		$search = trim($_GET['search'] ?? '');
		$sort = $_GET['sort'] ?? 'data_inizio';
		$direction = strtolower($_GET['direction'] ?? 'desc');
		$allowedSorts = ['id', 'titolo', 'data_inizio', 'data_fine', 'luogo', 'event_size', 'approvato'];
		if (!in_array($sort, $allowedSorts, true)) {
			$sort = 'data_inizio';
		}
		if (!in_array($direction, ['asc', 'desc'], true)) {
			$direction = 'desc';
		}

		$result = $this->eventRepository->getPaginated([
			'page' => $page,
			'perPage' => $perPage,
			'search' => $search,
			'sort' => $sort,
			'direction' => $direction,
			'filters' => [
				'approvato' => 0,
			],
		]);

		$total = $result['total'];
		$pages = (int) ceil($total / $perPage);

		$data = [];

		foreach ($result['data'] as $event) {
			$data[] = [
				'id' => $event['id'],
				'titolo' => $event['titolo'],
				'data_inizio' => $event['data_inizio'],
				'data_fine' => $event['data_fine'],
				'luogo' => $event['luogo'],
				'event_size' => $event['event_size'],
				'approvato' => 'pending',
				'_links' => [
					'view' => '/eventi/' . $event['slug'],
					'edit' => '/admin/events/edit/' . $event['id'],
					'delete' => '/admin/events/delete/' . $event['id'],
				],
			];
		}

		echo json_encode([
			'success' => true,
			'data' => $data,
			'meta' => [
				'page' => $page,
				'pages' => $pages,
				'total' => $total,
			],
		]);
		exit();
	}

	/**
	 * Mostra tutti gli eventi (approvati e non) per l'amministratore.
	 */
	public function allEvents(){
		$events = $this->eventModel->getAllEvents();
		$regions = $this->regioneModel->getAll();
		$years = $this->eventModel->getAdminAvailableYears();
		$data['regions'] = array_map(
			static function(array $region): array {

				return [
					'value' => $region['id'],
					'label' => $region['nome']
				];

			},
			$regions
		);
		$data['years'] = array_map(
			static function(int $year): array {

				return [
					'value' => (string) $year,
					'label' => (string) $year
				];

			},
			$years
		);

		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Tutti gli Eventi', 'url' => URL_ROOT . '/admin/events/all'],
		];
		$this->view('admin/events/all', [
			'events' => $events,
			'csrf_token' => $_SESSION['csrf_token'],
			'breadcrumbs' => $breadcrumbs,
			'regions' => $data['regions'],
			'years' => $data['years']
		],
			'admin');
	}

	/**
	 * Mostra il form per creare un nuovo evento (ADMIN).
	 */
	public function adminCreate(){
		$tipi_evento = $this->tipoEventoModel->getAll();
		$eventMasters = $this->eventMasterModel->getAll();
		$allRegioni = $this->regioneModel->getAll();
		$allProvince = $this->provinciaModel->getAll();
		$allComuni = $this->comuneModel->getAll();
		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Tutti gli Eventi', 'url' => URL_ROOT . '/admin/events/all'],
			['label' => 'Crea Nuovo Evento', 'url' => URL_ROOT . '/admin/events/create'],
		];
		$this->view('admin/events/create', ['csrf_token' => $_SESSION['csrf_token'], 'tipi_evento' => $tipi_evento, 'event_masters' => $eventMasters, 'all_regioni' => $allRegioni, 'all_province' => $allProvince, 'all_comuni' => $allComuni, 'breadcrumbs' => $breadcrumbs], 'admin');
	}

	/**
	 * Salva un nuovo evento creato dall'amministratore.
	 */
	public function adminStore(){
		if(!$this->validateCsrfToken()){
			header('Location: ' . URL_ROOT . '/admin/events/create');
			exit();
		}
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			$is_paid = isset($_POST['is_paid']) ? 1 : 0;
			$has_cosplay_contest = isset($_POST['has_cosplay_contest']) ? 1 : 0;
			$data = [
				'titolo'              => trim($_POST['titolo']),
				'slug'                => trim($_POST['slug'] ?? ''),
				'anno'                => (int) ($_POST['anno'] ?? date('Y')),
				'descrizione'         => $_POST['descrizione'],
				'seo_title'           => trim($_POST['seo_title'] ?? ''),
				'seo_description'     => trim($_POST['seo_description'] ?? ''),
				'data_inizio'         => trim($_POST['data_inizio']),
				'data_fine'           => trim($_POST['data_fine'] ?? ''),
				'luogo'               => trim($_POST['luogo']),
				'regione_id'          => filter_var($_POST['regione_id'] ?? null, FILTER_VALIDATE_INT),
				'provincia_id'        => filter_var($_POST['provincia_id'] ?? null, FILTER_VALIDATE_INT),
				'comune_id'           => filter_var($_POST['comune_id'] ?? null, FILTER_VALIDATE_INT),
				'latitudine'          => filter_var($_POST['latitudine'] ?? null, FILTER_VALIDATE_FLOAT),
				'longitudine'         => filter_var($_POST['longitudine'] ?? null, FILTER_VALIDATE_FLOAT),
				'sito_web'            => filter_var(trim($_POST['sito_web'] ?? ''), FILTER_VALIDATE_URL),
				'social_facebook'     => filter_var(trim($_POST['social_facebook'] ?? ''), FILTER_VALIDATE_URL),
				'social_twitter'      => filter_var(trim($_POST['social_twitter'] ?? ''), FILTER_VALIDATE_URL),
				'social_instagram'    => filter_var(trim($_POST['social_instagram'] ?? ''), FILTER_VALIDATE_URL),
				'social_tiktok'       => filter_var(trim($_POST['social_tiktok'] ?? ''), FILTER_VALIDATE_URL),
				'social_youtube'      => filter_var(trim($_POST['social_youtube'] ?? ''), FILTER_VALIDATE_URL),
				'tipo_evento_id'      => filter_var($_POST['tipo_evento_id'] ?? null, FILTER_VALIDATE_INT),
				'approvato'           => (int) ($_POST['approvato'] ?? 0),
				'event_size'          => (int) ($_POST['event_size'] ?? 0),
				'event_master_id'     => filter_var($_POST['event_master_id'] ?? null, FILTER_VALIDATE_INT),
				'is_paid '            => $is_paid,
				'has_cosplay_contest' => $has_cosplay_contest,
				'immagine'            => null,
			];
			$errors = [];
			if(empty($data['titolo'])){
				$errors[] = 'Il titolo è obbligatorio.';
			}
			if(strlen($data['titolo']) > 255){
				$errors[] = 'Il titolo è troppo lungo (max 255 caratteri).';
			}
			if(strlen($data['seo_title']) > 255){
				$errors[] = 'Il SEO Title è troppo lungo (max 255 caratteri).';
			}
			if(strlen($data['seo_description']) > 500){
				$errors[] = 'La SEO Description è troppo lunga (max 500 caratteri).';
			}
			if(empty($data['descrizione'])){
				$errors[] = 'La descrizione è obbligatoria.';
			}
			if(empty($data['data_inizio'])){
				$errors[] = 'La data di inizio è obbligatoria.';
			}
			if(!empty($data['data_inizio']) && !strtotime($data['data_inizio'])){
				$errors[] = 'Formato data di inizio non valido.';
			}
			if(!empty($data['data_fine']) && !strtotime($data['data_fine'])){
				$errors[] = 'Formato data di fine non valido.';
			}
			if(!empty($data['data_fine']) && !empty($data['data_inizio']) && strtotime($data['data_fine']) < strtotime($data['data_inizio'])){
				$errors[] = 'La data di fine non può essere precedente alla data di inizio.';
			}
			if(empty($data['luogo'])){
				$errors[] = 'Il luogo è obbligatorio.';
			}
			if(strlen($data['luogo']) > 255){
				$errors[] = 'Il luogo è troppo lungo (max 255 caratteri).';
			}
			if($data['regione_id'] === false || $data['regione_id'] === null || $data['regione_id'] <= 0){
				$errors[] = 'ID Regione non valido.';
			}
			if($data['provincia_id'] === false || $data['provincia_id'] === null || $data['provincia_id'] <= 0){
				$errors[] = 'ID Provincia non valido.';
			}
			if($data['comune_id'] === false || $data['comune_id'] === null || $data['comune_id'] <= 0){
				$errors[] = 'ID Comune non valido.';
			}
			if($data['tipo_evento_id'] === false || $data['tipo_evento_id'] === null || $data['tipo_evento_id'] <= 0){
				$errors[] = 'ID Tipo Evento non valido.';
			}
			if($data['latitudine'] === false || ($data['latitudine'] !== null && ($data['latitudine'] < -90 || $data['latitudine'] > 90))){
				if(!empty($_POST['latitudine'])){
					$errors[] = 'Latitudine non valida (deve essere tra -90 e 90).';
				}
			}
			if($data['longitudine'] === false || ($data['longitudine'] !== null && ($data['longitudine'] < -180 || $data['longitudine'] > 180))){
				if(!empty($_POST['longitudine'])){
					$errors[] = 'Longitudine non valida (deve essere tra -180 e 180).';
				}
			}
			if($data['sito_web'] === false && !empty(trim($_POST['sito_web'] ?? ''))){
				$errors[] = 'URL Sito Web non valido.';
			}
			if($data['social_facebook'] === false && !empty(trim($_POST['social_facebook'] ?? ''))){
				$errors[] = 'URL Social Facebook non valido.';
			}
			if($data['social_twitter'] === false && !empty(trim($_POST['social_twitter'] ?? ''))){
				$errors[] = 'URL Social Twitter non valido.';
			}
			if($data['social_instagram'] === false && !empty(trim($_POST['social_instagram'] ?? ''))){
				$errors[] = 'URL Social Instagram non valido.';
			}
			if($data['social_tiktok'] === false && !empty(trim($_POST['social_tiktok'] ?? ''))){
				$errors[] = 'URL Social TikTok non valido.';
			}
			if($data['social_youtube'] === false && !empty(trim($_POST['social_youtube'] ?? ''))){
				$errors[] = 'URL Social YouTube non valido.';
			}
			$eventMasterError = $this->normalizeEventMasterId($data);
			if ($eventMasterError !== null) {
				$errors[] = $eventMasterError;
			}
			if(!empty($errors)){
				Session::setFlash('error', implode('<br>', $errors));
				$tipi_evento = $this->tipoEventoModel->getAll();
				$eventMasters = $this->eventMasterModel->getAll();
				$allRegioni = $this->regioneModel->getAll();
				$allProvince = $this->provinciaModel->getAll();
				$allComuni = $this->comuneModel->getAll();
				$breadcrumbs = [
					['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
					['label' => 'Tutti gli Eventi', 'url' => URL_ROOT . '/admin/events/all'],
					['label' => 'Crea Nuovo Evento', 'url' => URL_ROOT . '/admin/events/create'],
				];
				$this->view('admin/events/create', array_merge($_POST, ['csrf_token' => $_SESSION['csrf_token'], 'error' => Session::getFlash('error'), 'tipi_evento' => $tipi_evento, 'event_masters' => $eventMasters, 'all_regioni' => $allRegioni, 'all_province' => $allProvince, 'all_comuni' => $allComuni, 'breadcrumbs' => $breadcrumbs]), 'admin');

				return;
			}
			$eventId = $this->eventModel->create($data);
			if($eventId > 0){
				if(!empty($_FILES['immagine']['name'])){
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
				if(!empty($_POST['guest_ids'])){
					$guestIds = json_decode($_POST['guest_ids'], true) ?? [];
				}
				$this->guestModel->getGuests($eventId, $guestIds);
				$this->auditLogService->logAudit([
					'user_id' => $_SESSION['user_id'] ?? null,
					'action_type' => AuditLogActionType::EVENT_CREATED,
					'entity_type' => 'event',
					'entity_id' => $eventId,
					'success' => 1,
					'payload' => [
						'title' => $data['titolo'],
						'slug' => $data['slug'],
						'has_image' => !empty($_FILES['immagine']['name']),
						'guest_ids' => $guestIds,
					],
				]);
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode([
					'success' => true,
					'message' => 'Evento creato con successo!',
					'id' => $eventId,
				]);
				exit();
			}else{
				Session::setFlash('error', 'Errore durante la creazione dell\'evento.');
				$tipi_evento = $this->tipoEventoModel->getAll();
				$eventMasters = $this->eventMasterModel->getAll();
				$allRegioni = $this->regioneModel->getAll();
				$allProvince = $this->provinciaModel->getAll();
				$allComuni = $this->comuneModel->getAll();
				$breadcrumbs = [
					['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
					['label' => 'Tutti gli Eventi', 'url' => URL_ROOT . '/admin/events/all'],
					['label' => 'Crea Nuovo Evento', 'url' => URL_ROOT . '/admin/events/create'],
				];
				$this->view('admin/events/create', array_merge($data, ['csrf_token' => $_SESSION['csrf_token'], 'tipi_evento' => $tipi_evento, 'event_masters' => $eventMasters, 'all_regioni' => $allRegioni, 'all_province' => $allProvince, 'all_comuni' => $allComuni, 'breadcrumbs' => $breadcrumbs]), 'admin');
			}
		}else{
			header('Location: ' . URL_ROOT . '/admin/events/create');
			exit();
		}
	}

	/**
	 * Mostra il form per modificare un evento (ADMIN).
	 * Questo metodo è destinato ad essere chiamato tramite una rotta admin (es. /admin/events/edit/{id}).
	 *
	 * @param array $params Contiene l'ID dell'evento.
	 */
	public function edit($params){
		$id = $params[0] ?? null;
		if(!$id || !is_numeric($id)){
			Session::setFlash('error', 'ID evento non valido.');
			header('Location: ' . URL_ROOT . '/admin/events/pending');
			exit();
		}
		$event = $this->eventModel->find($id);
		$cover = $this->imageService->getPrimary('event', $event['id'], 'large');
		$event['guests'] = $this->guestModel->getGuests($id);
		if(!empty($cover)){
			$event['immagine'] = '/public_assets' . $cover['path'];
			$event['id_immagine'] = $cover['id'];
		}else{
			$event['immagine'] = '';
			$event['id_immagine'] = '';
		}
		if(!$event){
			Session::setFlash('error', 'Evento non trovato.');
			header('Location: ' . URL_ROOT . '/admin/events/pending');
			exit();
		}
		$tipi_evento = $this->tipoEventoModel->getAll();
		$eventMasters = $this->eventMasterModel->getAll();
		$allRegioni = $this->regioneModel->getAll();
		$allProvince = $this->provinciaModel->getAll();
		$allComuni = $this->comuneModel->getAll();
		$breadcrumbs = [
			['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
			['label' => 'Tutti gli Eventi', 'url' => URL_ROOT . '/admin/events/all'],
			['label' => 'Modifica Evento: ' . $event['titolo'], 'url' => URL_ROOT . '/admin/events/edit/' . $id],
		];
		$this->view('admin/events/edit', ['event' => $event, 'csrf_token' => $_SESSION['csrf_token'], 'tipi_evento' => $tipi_evento, 'event_masters' => $eventMasters, 'all_regioni' => $allRegioni, 'all_province' => $allProvince, 'all_comuni' => $allComuni, 'breadcrumbs' => $breadcrumbs], 'admin');
	}

	/**
	 * Gestisce l'invio del modulo per l'aggiornamento di un evento (ADMIN).
	 * Questo metodo è destinato ad essere chiamato tramite una rotta admin (es. /admin/events/update/{id}).
	 *
	 * @param array $params Contiene l'ID dell'evento.
	 */
	public function update($params){
		if(!$this->validateCsrfToken()){
			$id = $params[0] ?? null;
			header('Location: ' . URL_ROOT . '/admin/events/edit/' . $id);
			exit();
		}
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			$id = $params[0] ?? null;
			if(!$id || !is_numeric($id)){
				Session::setFlash('error', 'ID evento non valido.');
				header('Location: ' . URL_ROOT . '/admin/events/pending');
				exit();
			}
			$event = $this->eventModel->find($id);
			if(!$event){
				Session::setFlash('error', 'Evento non trovato.');
				header('Location: ' . URL_ROOT . '/admin/events/pending');
				exit();
			}
			$is_paid = isset($_POST['is_paid']) ? 1 : 0;
			$has_cosplay_contest = isset($_POST['has_cosplay_contest']) ? 1 : 0;
			$guestIds = [];
			if(!empty($_POST['guest_ids'])){
				$guestIds = json_decode($_POST['guest_ids'], true) ?? [];
			}
			$data = [
				'titolo'              => trim($_POST['titolo']),
				'slug'                => trim($_POST['slug'] ?? ''),
				'anno'                => filter_var($_POST['anno'] ?? null, FILTER_VALIDATE_INT),
				'descrizione'         => $_POST['descrizione'],
				'seo_title'           => trim($_POST['seo_title'] ?? ''),
				'seo_description'     => trim($_POST['seo_description'] ?? ''),
				'data_inizio'         => trim($_POST['data_inizio']),
				'data_fine'           => trim($_POST['data_fine'] ?? ''),
				'luogo'               => trim($_POST['luogo']),
				'regione_id'          => filter_var($_POST['regione_id'] ?? null, FILTER_VALIDATE_INT),
				'provincia_id'        => filter_var($_POST['provincia_id'] ?? null, FILTER_VALIDATE_INT),
				'comune_id'           => filter_var($_POST['comune_id'] ?? null, FILTER_VALIDATE_INT),
				'latitudine'          => filter_var($_POST['latitudine'] ?? null, FILTER_VALIDATE_FLOAT),
				'longitudine'         => filter_var($_POST['longitudine'] ?? null, FILTER_VALIDATE_FLOAT),
				'sito_web'            => filter_var(trim($_POST['sito_web'] ?? ''), FILTER_VALIDATE_URL),
				'social_facebook'     => filter_var(trim($_POST['social_facebook'] ?? ''), FILTER_VALIDATE_URL),
				'social_twitter'      => filter_var(trim($_POST['social_twitter'] ?? ''), FILTER_VALIDATE_URL),
				'social_instagram'    => filter_var(trim($_POST['social_instagram'] ?? ''), FILTER_VALIDATE_URL),
				'social_tiktok'       => filter_var(trim($_POST['social_tiktok'] ?? ''), FILTER_VALIDATE_URL),
				'social_youtube'      => filter_var(trim($_POST['social_youtube'] ?? ''), FILTER_VALIDATE_URL),
				'tipo_evento_id'      => filter_var($_POST['tipo_evento_id'] ?? null, FILTER_VALIDATE_INT),
				'approvato'           => (int) ($_POST['approvato'] ?? 0),
				'event_size'          => (int) ($_POST['event_size'] ?? 0),
				'event_master_id'     => filter_var($_POST['event_master_id'] ?? null, FILTER_VALIDATE_INT),
				'immagine'            => '',
				'id_immagine'         => (int) ($_POST['id_immagine'] ?? 0),
				'is_paid'             => filter_var($is_paid, FILTER_VALIDATE_INT),
				'has_cosplay_contest' => filter_var($has_cosplay_contest, FILTER_VALIDATE_INT),
				'guest_ids'           => json_encode($guestIds),
			];
			$errors = [];
			if(!empty($_FILES['immagine']['name'])){
				$coverImageId = $this->imageService->replacePrimary(
					$_FILES['immagine'],
					'event',
					$id,
					$data['titolo']
				);
				//$coverImageId = $this->imageService->upload($_FILES['cover_image'], 'blog_post', $id, $data['titolo'], true);
				$data['cover_image_id'] = $coverImageId;
			}
			if(empty($data['titolo'])){
				$errors[] = 'Il titolo è obbligatorio.';
			}
			if(strlen($data['titolo']) > 255){
				$errors[] = 'Il titolo è troppo lungo (max 255 caratteri).';
			}
			if(strlen($data['seo_title']) > 255){
				$errors[] = 'Il SEO Title è troppo lungo (max 255 caratteri).';
			}
			if(strlen($data['seo_description']) > 500){
				$errors[] = 'La SEO Description è troppo lunga (max 500 caratteri).';
			}
			if($data['anno'] === false || $data['anno'] === null || $data['anno'] < 2000 || $data['anno'] > 2100){
				$errors[] = 'Anno non valido (deve essere compreso tra 2000 e 2100).';
			}
			if(empty($data['descrizione'])){
				$errors[] = 'La descrizione è obbligatoria.';
			}
			if(empty($data['data_inizio'])){
				$errors[] = 'La data di inizio è obbligatoria.';
			}
			if(!empty($data['data_inizio']) && !strtotime($data['data_inizio'])){
				$errors[] = 'Formato data di inizio non valido.';
			}
			if(!empty($data['data_fine']) && !strtotime($data['data_fine'])){
				$errors[] = 'Formato data di fine non valido.';
			}
			if(!empty($data['data_fine']) && !empty($data['data_inizio']) && strtotime($data['data_fine']) < strtotime($data['data_inizio'])){
				$errors[] = 'La data di fine non può essere precedente alla data di inizio.';
			}
			if(empty($data['luogo'])){
				$errors[] = 'Il luogo è obbligatorio.';
			}
			if(strlen($data['luogo']) > 255){
				$errors[] = 'Il luogo è troppo lungo (max 255 caratteri).';
			}
			if($data['regione_id'] === false || $data['regione_id'] === null || $data['regione_id'] <= 0){
				$errors[] = 'ID Regione non valido.';
			}
			if($data['provincia_id'] === false || $data['provincia_id'] === null || $data['provincia_id'] <= 0){
				$errors[] = 'ID Provincia non valido.';
			}
			if($data['comune_id'] === false || $data['comune_id'] === null || $data['comune_id'] <= 0){
				$errors[] = 'ID Comune non valido.';
			}
			if($data['tipo_evento_id'] === false || $data['tipo_evento_id'] === null || $data['tipo_evento_id'] <= 0){
				$errors[] = 'ID Tipo Evento non valido.';
			}
			if($data['latitudine'] === false || ($data['latitudine'] !== null && ($data['latitudine'] < -90 || $data['latitudine'] > 90))){
				if(!empty($_POST['latitudine'])){
					$errors[] = 'Latitudine non valida (deve essere tra -90 e 90).';
				}
			}
			if($data['longitudine'] === false || ($data['longitudine'] !== null && ($data['longitudine'] < -180 || $data['longitudine'] > 180))){
				if(!empty($_POST['longitudine'])){
					$errors[] = 'Longitudine non valida (deve essere tra -180 e 180).';
				}
			}
			if($data['sito_web'] === false && !empty(trim($_POST['sito_web'] ?? ''))){
				$errors[] = 'URL Sito Web non valido.';
			}
			if($data['social_facebook'] === false && !empty(trim($_POST['social_facebook'] ?? ''))){
				$errors[] = 'URL Social Facebook non valido.';
			}
			if($data['social_twitter'] === false && !empty(trim($_POST['social_twitter'] ?? ''))){
				$errors[] = 'URL Social Twitter non valido.';
			}
			if($data['social_instagram'] === false && !empty(trim($_POST['social_instagram'] ?? ''))){
				$errors[] = 'URL Social Instagram non valido.';
			}
			if($data['social_tiktok'] === false && !empty(trim($_POST['social_tiktok'] ?? ''))){
				$errors[] = 'URL Social TikTok non valido.';
			}
			if($data['social_youtube'] === false && !empty(trim($_POST['social_youtube'] ?? ''))){
				$errors[] = 'URL Social YouTube non valido.';
			}
			$eventMasterError = $this->normalizeEventMasterId($data);
			if ($eventMasterError !== null) {
				$errors[] = $eventMasterError;
			}
			if(!empty($errors)){
				Session::setFlash('error', implode('<br>', $errors));
				$tipi_evento = $this->tipoEventoModel->getAll();
				$eventMasters = $this->eventMasterModel->getAll();
				$allRegioni = $this->regioneModel->getAll();
				$allProvince = $this->provinciaModel->getByRegioneId();
				$allComuni = $this->comuneModel->getAll();
				$breadcrumbs = [
					['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
					['label' => 'Tutti gli Eventi', 'url' => URL_ROOT . '/admin/events/all'],
					['label' => 'Modifica Evento: ' . $event['titolo'], 'url' => URL_ROOT . '/admin/events/edit/' . $id],
				];
				$this->view('admin/events/edit', array_merge($data, ['event' => $event, 'csrf_token' => $_SESSION['csrf_token'], 'tipi_evento' => $tipi_evento, 'event_masters' => $eventMasters, 'all_regioni' => $allRegioni, 'all_province' => $allProvince, 'all_comuni' => $allComuni, 'breadcrumbs' => $breadcrumbs]), 'admin');

				return;
			}
			if($this->eventModel->update($id, $data)){
				$this->guestModel->saveGuests($id, $guestIds);
				$updatedEvent = $this->eventModel->find($id);
				$this->notifySavedEventUsers((int) $id, $updatedEvent ?: $data);
				$this->auditLogService->logAudit([
					'user_id' => $_SESSION['user_id'] ?? null,
					'action_type' => AuditLogActionType::EVENT_UPDATED,
					'entity_type' => 'event',
					'entity_id' => (int) $id,
					'success' => 1,
					'payload' => [
						'title' => $data['titolo'],
						'slug' => $data['slug'],
						'has_image' => !empty($_FILES['immagine']['name']),
						'guest_ids' => $guestIds,
					],
				]);
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode([
					'success' => true,
					'message' => 'Evento aggiornato con successo!',
					'id' => (int) $id,
				]);
				exit();
			}else{
				Session::setFlash('error', 'Errore durante l\'aggiornamento dell\'evento.');
				$tipi_evento = $this->tipoEventoModel->getAll();
				$eventMasters = $this->eventMasterModel->getAll();
				$allRegioni = $this->regioneModel->getAll();
				$allProvince = $this->provinciaModel->getByRegioneId();
				$allComuni = $this->comuneModel->getAll();
				$breadcrumbs = [
					['label' => 'Dashboard Admin', 'url' => URL_ROOT . '/admin/dashboard'],
					['label' => 'Tutti gli Eventi', 'url' => URL_ROOT . '/admin/events/all'],
					['label' => 'Modifica Evento: ' . $event['titolo'], 'url' => URL_ROOT . '/admin/events/edit/' . $id],
				];
				$this->view('admin/events/edit', array_merge($data, ['event' => $event, 'csrf_token' => $_SESSION['csrf_token'], 'tipi_evento' => $tipi_evento, 'event_masters' => $eventMasters, 'all_regioni' => $allRegioni, 'all_province' => $allProvince, 'all_comuni' => $allComuni, 'breadcrumbs' => $breadcrumbs]), 'admin');
			}
		}else{
			header('Location: ' . URL_ROOT . '/admin/events/all');
			exit();
		}
	}

	private function notifySavedEventUsers(int $eventId, array $eventData, int $excludeUserId = 0): void
	{
		$recipients = $this->notificationService->getRecipientsForEntity('event', $eventId, $excludeUserId);
		if (empty($recipients)) {
			return;
		}

		$eventTitle = (string) ($eventData['titolo'] ?? 'un evento salvato');
		$eventSlug = (string) ($eventData['slug'] ?? '');

		$this->notificationService->createBulkNotifications($recipients, [
			'notification_type' => 'event_updated',
			'title' => 'Evento aggiornato',
			'message' => sprintf('L’evento "%s" è stato aggiornato.', $eventTitle),
			'source_entity_type' => 'event',
			'source_entity_id' => $eventId,
			'payload' => [
				'event_id' => $eventId,
				'event_title' => $eventTitle,
				'event_slug' => $eventSlug,
			],
		]);
	}

	/**
	 * Crea automaticamente un evento master minimale se non è stato scelto in form.
	 */
	private function ensureEventMasterForEvent(string $eventTitle): int
	{
		$eventTitle = trim($eventTitle);
		if ($eventTitle === '') {
			return 0;
		}

		$eventMasterId = $this->eventMasterModel->create([
			'nome' => $eventTitle,
			'slug' => $eventTitle,
			'descrizione' => null,
			'sito_web' => null,
			'social_facebook' => null,
			'social_twitter' => null,
			'social_instagram' => null,
			'social_tiktok' => null,
			'social_youtube' => null,
		]);

		return $eventMasterId > 0 ? $eventMasterId : 0;
	}

	private function normalizeEventMasterId(array &$data): ?string
	{
		$eventMasterId = (int) ($data['event_master_id'] ?? 0);
		if ($eventMasterId > 0 && $this->eventMasterModel->find($eventMasterId) !== null) {
			$data['event_master_id'] = $eventMasterId;
			return null;
		}

		if ($eventMasterId > 0) {
			return 'Evento Master selezionato non valido.';
		}

		$createdEventMasterId = $this->ensureEventMasterForEvent((string) ($data['titolo'] ?? ''));
		$data['event_master_id'] = $createdEventMasterId > 0 ? $createdEventMasterId : null;

		return null;
	}

	/**
	 * Approva un evento (ADMIN).
	 *
	 * @param array $params Contiene l'ID dell'evento.
	 */
	public function approve($params){
		$id = $params[0] ?? null;
		if(!$id || !is_numeric($id)){
			Session::setFlash('error', 'ID evento non valido.');
			header('Location: ' . URL_ROOT . '/admin/events/pending');
			exit();
		}
		if($_SERVER['REQUEST_METHOD'] == 'POST' && !$this->validateCsrfToken()){
			header('Location: ' . URL_ROOT . '/admin/events/pending');
			exit();
		}
		if($this->eventModel->approve($id)){
			$this->auditLogService->logAudit([
				'user_id' => $_SESSION['user_id'] ?? null,
				'action_type' => AuditLogActionType::EVENT_APPROVED,
				'entity_type' => 'event',
				'entity_id' => (int) $id,
				'success' => 1,
				'payload' => [
					'id' => (int) $id,
				],
			]);
			Session::setFlash('success', 'Evento approvato con successo!');
		}else{
			Session::setFlash('error', 'Errore durante l\'approvazione dell\'evento.');
		}
		header('Location: ' . URL_ROOT . '/admin/events/pending');
		exit();
	}

	/**
	 * Duplica un evento per creare una nuova edizione.
	 *
	 * @param array $params Contiene l'ID dell'evento.
	 */
	public function copy(array $params): void
	{
		if (!$this->validateCsrfToken()) {
			header('Location: ' . URL_ROOT . '/admin/events/all');
			exit();
		}

		$id = (int) ($params[0] ?? 0);
		if ($id <= 0) {
			Session::setFlash('error', 'ID evento non valido.');
			header('Location: ' . URL_ROOT . '/admin/events/all');
			exit();
		}

		$event = $this->eventModel->find($id);
		if (!$event) {
			Session::setFlash('error', 'Evento non trovato.');
			header('Location: ' . URL_ROOT . '/admin/events/all');
			exit();
		}

		$newEventId = $this->eventModel->create([
			'titolo' => $event['titolo'] ?? '',
			'slug' => '',
			'descrizione' => $event['descrizione'] ?? '',
			'data_inizio' => $event['data_inizio'] ?? '',
			'data_fine' => $event['data_fine'] ?? '',
			'luogo' => $event['luogo'] ?? '',
			'regione_id' => $event['regione_id'] ?? null,
			'provincia_id' => $event['provincia_id'] ?? null,
			'comune_id' => $event['comune_id'] ?? null,
			'latitudine' => $event['latitudine'] ?? null,
			'longitudine' => $event['longitudine'] ?? null,
			'sito_web' => $event['sito_web'] ?? null,
			'social_facebook' => $event['social_facebook'] ?? null,
			'social_twitter' => $event['social_twitter'] ?? null,
			'social_instagram' => $event['social_instagram'] ?? null,
			'social_tiktok' => $event['social_tiktok'] ?? null,
			'social_youtube' => $event['social_youtube'] ?? null,
			'tipo_evento_id' => $event['tipo_evento_id'] ?? null,
			'approvato' => 0,
			'year' => $event['year'] ?? date('Y'),
			'event_size' => $event['event_size'] ?? 0,
			'is_paid' => $event['is_paid'] ?? 0,
			'has_cosplay_contest' => $event['has_cosplay_contest'] ?? 0,
			'event_master_id' => $event['event_master_id'] ?? null,
		]);

		if ($newEventId > 0 && !empty($event['immagine'] ?? '')) {
			$this->imageService->duplicateEntityImages('event', $id, $newEventId);
		}
		$this->auditLogService->logAudit([
			'user_id' => $_SESSION['user_id'] ?? null,
			'action_type' => AuditLogActionType::EVENT_COPIED,
			'entity_type' => 'event',
			'entity_id' => $newEventId,
			'success' => 1,
			'payload' => [
				'source_event_id' => (int) $id,
				'source_title' => $event['titolo'] ?? null,
			],
		]);

		Session::setFlash('success', 'Evento copiato con successo. La nuova edizione è in attesa di approvazione.');
		header('Location: ' . URL_ROOT_SITE . '/admin/events/edit/' . $newEventId);
		exit();
	}

	/**
	 * Elimina un evento (ADMIN).
	 *
	 * @param array $params Contiene l'ID dell'evento.
	 */
	public function delete($params): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$id = $params[0] ?? null;


		if(
			!$id ||
			!is_numeric($id)
		){
			http_response_code(400);
			echo json_encode([
				'success' => false,
				'message' => 'ID evento non valido.'
			]);
			exit();

		}



		if(
			$_SERVER['REQUEST_METHOD'] !== 'POST'
		){
			http_response_code(405);
			echo json_encode([
				'success' => false,
				'message' => 'Metodo non consentito.'
			]);
			exit();

		}



		if(
			!$this->validateCsrfToken()
		){
			http_response_code(403);
			echo json_encode([
				'success' => false,
				'message' => 'Token CSRF non valido.'
			]);
			exit();

		}



		$event =
			$this->eventModel->find($id);



		if(!$event){
			http_response_code(404);
			echo json_encode([
				'success' => false,
				'message' => 'Evento non trovato.'
			]);
			exit();

		}



		if(
			$this->eventModel->delete((int) $id, isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null)
		){
			$this->auditLogService->logAudit([
				'user_id' => $_SESSION['user_id'] ?? null,
				'action_type' => AuditLogActionType::EVENT_DELETED,
				'entity_type' => 'event',
				'entity_id' => (int) $id,
				'success' => 1,
				'payload' => [
					'title' => $event['titolo'] ?? null,
					'slug' => $event['slug'] ?? null,
				],
			]);
			echo json_encode([
				'success' => true,
				'message' => 'Evento eliminato con successo!'
			]);
			exit();

		}

		http_response_code(500);
		echo json_encode([
			'success' => false,
			'message' => 'Errore durante eliminazione evento.'
		]);
		exit();

	}

	function generaTestoSEO($selected_regione = null, $selected_provincia = null, $selected_comune = null){
		if($selected_comune){
			// Caso: COMUNE + PROVINCIA + REGIONE
			$localita = $selected_comune['nome'] . " (" . $selected_provincia['nome'] . ", " . $selected_regione['nome'] . ")";
			$testo = "Scopri tutti gli eventi cosplay a {$localita}. 
        Fiere, raduni e manifestazioni per appassionati di manga, anime e cultura pop 
        ti aspettano nel cuore di {$selected_comune['nome']}. 
        Resta aggiornato sulle date e partecipa agli eventi vicino a te.";
		}elseif($selected_provincia){
			// Caso: PROVINCIA + REGIONE
			$localita = $selected_provincia['nome'] . " in " . $selected_regione['nome'];
			$testo = "Scopri gli eventi cosplay nella provincia di {$localita}. 
        Convention, fiere del fumetto e raduni cosplay organizzati durante l’anno, 
        con informazioni sempre aggiornate. 
        Vivi la tua passione per anime, manga e videogiochi in {$selected_provincia['nome']}.";
		}elseif($selected_regione){
			// Caso: REGIONE
			$localita = $selected_regione['nome'];
			if($selected_regione['intro_html'] != ''){
				$testo = "<div class='mb-4 text-lg leading-relaxed'>" . $selected_regione['intro_html'] . "</div>" ?? '';
			}else{
				$testo = "Eventi cosplay in {$localita}: fiere, raduni e manifestazioni dedicate al mondo nerd e otaku. 
        Scopri le prossime date, le città coinvolte e le novità in programma. 
        La regione {$localita} ospita ogni anno eventi imperdibili per i fan del cosplay.";
			}
		}else{
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

	function generaSchemaSEO($selected_regione = null, $selected_provincia = null, $selected_comune = null){
		// Nome area geografica
		if($selected_comune){
			$locationName = $selected_comune['nome'] . " (" . $selected_provincia['nome'] . ", " . $selected_regione['nome'] . ")";
		}elseif($selected_provincia){
			$locationName = "Provincia di " . $selected_provincia['nome'] . " (" . $selected_regione['nome'] . ")";
		}elseif($selected_regione){
			$locationName = "Regione " . $selected_regione['nome'];
		}else{
			$locationName = "Italia";
		}
		// Schema base (puoi arricchirlo evento per evento se hai i dettagli)
		$schema = [
			"@context"            => "https://schema.org",
			"@type"               => "Event",
			"name"                => "Eventi Cosplay in $locationName",
			"description"         => "Scopri fiere, raduni e manifestazioni cosplay in $locationName: date, luoghi e novità.",
			"eventAttendanceMode" => "https://schema.org/OfflineEventAttendanceMode",
			"eventStatus"         => "https://schema.org/EventScheduled",
			"location"            => [
				"@type"   => "Place",
				"name"    => $locationName,
				"address" => [
					"@type"           => "PostalAddress",
					"addressLocality" => $locationName,
					"addressCountry"  => "IT",
				],
			],
			"organizer"           => [
				"@type" => "Organization",
				"name"  => "ItalianCosplay",
				"url"   => "https://www.italiancosplay.it",
			],
		];

		return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
	}

	function generaSchemaListaEventi($eventi){
		$schema = [
			"@context" => "https://schema.org",
			"@graph"   => [],
		];
		foreach($eventi as $evento){
			// Solo eventi approvati
			$dataTesto = DateHelper::formatEventoPeriodo($evento['data_inizio'], $evento['data_fine']);
			$descrizione_pulita = $evento['titolo'] . " " . date('Y', strtotime($evento['data_inizio'])) . " si svolge " . $dataTesto . " " . date('Y', strtotime($evento['data_inizio'])) . " a " . $evento['comune_nome'] . " con cosplay, gaming, fumetti, ospiti e stand.";
			//strip_tags(html_entity_decode($evento['descrizione'] ?? ''));
			$eventoSchema = [
				"@type"               => "Event",
				"name"                => $evento['titolo'] ?? '',
				"description"         => $descrizione_pulita,
				"startDate"           => $evento['data_inizio'] ?? '',
				"endDate"             => $evento['data_fine'] ?? '',
				"eventAttendanceMode" => "https://schema.org/OfflineEventAttendanceMode",
				"eventStatus"         => "https://schema.org/EventScheduled",
				"location"            => [
					"@type"   => "Place",
					"name"    => $evento['luogo'] ?? '',
					"address" => [
						"@type"           => "PostalAddress",
						"addressLocality" => $evento['comune_nome'] ?? '',
						"addressRegion"   => $evento['regione_nome'] ?? '',
						"addressCountry"  => "IT",
					],
					"geo"     => [
						"@type"     => "GeoCoordinates",
						"latitude"  => $evento['latitudine'] ?? '',
						"longitude" => $evento['longitudine'] ?? '',
					],
				],
				"organizer"           => [
					"@type" => "Organization",
					"name"  => "ItalianCosplay",
					"url"   => "https://www.italiancosplay.it",
				],
				"image"               => [
					"https://www.italiancosplay.it" . $evento['immagine'],
				],
				"offers"              => [
					"@type"         => "Offer",
					"url"           => "https://www.italiancosplay.it/eventi-cosplay/" . $evento['slug'],
					"price"         => "0",
					"priceCurrency" => "EUR",
					"availability"  => "https://schema.org/InStock", // significa: evento ancora aperto
					"validFrom"     => date('c'), // ISO 8601
				],
				// 🎭 Performer — può essere un gruppo, cosplay guest, o un generico performer
				/*"performer" => [
					"@type" => "Person",
					"name" => "Cosplayer italiani",
				],*/
			];
			// Aggiungi eventuali social o sito web se esistono
			if(!empty($evento['sito_web'])){
				$eventoSchema['url'] = $evento['sito_web'];
			}
			if(!empty($evento['social_facebook'])){
				$eventoSchema['sameAs'][] = $evento['social_facebook'];
			}
			if(!empty($evento['social_twitter'])){
				$eventoSchema['sameAs'][] = $evento['social_twitter'];
			}
			if(!empty($evento['social_instagram'])){
				$eventoSchema['sameAs'][] = $evento['social_instagram'];
			}
			if(!empty($evento['social_youtube'])){
				$eventoSchema['sameAs'][] = $evento['social_youtube'];
			}
			if(!empty($evento['social_tiktok'])){
				$eventoSchema['sameAs'][] = $evento['social_tiktok'];
			}
			$schema['@graph'][] = $eventoSchema;
		}

		return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
	}

	function generaSchemaListaEventiSemplice($eventi){
		$schema = [
			"@context" => "https://schema.org",
			"@graph"   => [],
		];
		foreach($eventi as $evento){
			// Solo eventi approvati
			$dataTesto = DateHelper::formatEventoPeriodo($evento['data_inizio'], $evento['data_fine']);
			$descrizione_pulita = $evento['titolo'] . " " . date('Y', strtotime($evento['data_inizio'])) . " si svolge " . $dataTesto . " " . date('Y', strtotime($evento['data_inizio'])) . " a " . $evento['comune_nome'] . " con cosplay, gaming, fumetti, ospiti e stand.";
			//strip_tags(html_entity_decode($evento['descrizione'] ?? ''));
			$eventoSchema = [
				"@context"    => "https://schema.org",
				"@type"       => "Event",
				"name"        => $evento['titolo'] ?? '',
				"description" => $descrizione_pulita ?? '',
				"startDate" => !empty($evento['data_inizio']) ? date('c', strtotime($evento['data_inizio'])) : null,
				"endDate"   => !empty($evento['data_fine']) ? date('c', strtotime($evento['data_fine'])) : null,
				"eventAttendanceMode" => "https://schema.org/OfflineEventAttendanceMode",
				"eventStatus"         => "https://schema.org/EventScheduled",
				"location" => [
					"@type"   => "Place",
					"name"    => $evento['luogo'] ?? '',
					"address" => [
						"@type"           => "PostalAddress",
						"addressLocality" => $evento['comune_nome'] ?? '',
						"addressRegion"   => $evento['regione_nome'] ?? '',
						"addressCountry"  => "IT",
					],
					"geo"     => [
						"@type"     => "GeoCoordinates",
						"latitude"  => $evento['latitudine'] ?? null,
						"longitude" => $evento['longitudine'] ?? null,
					],
				],
				"image" => [
					"https://www.italiancosplay.it" . ltrim($evento['immagine'] ?? '', '/'),
				],
				"organizer" => [
					"@type" => "Organization",
					"name"  => "ItalianCosplay",
					"url"   => "https://www.italiancosplay.it",
				],
				"offers" => [
					"@type"         => "Offer",
					"url"           => "https://www.italiancosplay.it/eventi-cosplay/" . $evento['slug'],
					"price"         => "0",
					"priceCurrency" => "EUR",
					"availability"  => "https://schema.org/InStock",
					"validFrom"     => date('c'),
				],
			];
			// Aggiungi eventuali social o sito web se esistono
			if(!empty($evento['sito_web'])){
				$eventoSchema['url'] = $evento['sito_web'];
			}
			if(!empty($evento['social_facebook'])){
				$eventoSchema['sameAs'][] = $evento['social_facebook'];
			}
			if(!empty($evento['social_twitter'])){
				$eventoSchema['sameAs'][] = $evento['social_twitter'];
			}
			if(!empty($evento['social_instagram'])){
				$eventoSchema['sameAs'][] = $evento['social_instagram'];
			}
			if(!empty($evento['social_youtube'])){
				$eventoSchema['sameAs'][] = $evento['social_youtube'];
			}
			if(!empty($evento['social_tiktok'])){
				$eventoSchema['sameAs'][] = $evento['social_tiktok'];
			}
			$schema['@graph'][] = $eventoSchema;
		}

		return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
	}

	public function shortUrl($params){
		$this->requireFeature('enable_events', 'Gli eventi pubblici sono temporaneamente disattivati.');
		$slug = $params[0] ?? null;
		if(!$slug){
			header('Location: ' . $this->eventsBasePath);
			exit;
		}
		// 1. Controlla se lo slug è una regione
		$regione = $this->regioneModel->findBySlug($slug);
		if($regione){
			$this->index([$slug]);
			return;
		}
		// 2. Controlla se lo slug è un evento
		$event = $this->eventModel->findBySlug($slug);
		if($event && $event['approvato'] == 1){
			$this->show([$slug]);
			return;
		}
		// 3. Slug non trovato → 404
		http_response_code(404);
		$this->view('errors/404');
	}

	/*public function weekend()
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
	}*/
	public function weekend() : void{
		$noindex = false;
		/**
		 * Recupero weekend corrente
		 */
		$currentWeekend = \App\Helpers\WeekendHelper::getCurrentWeekend();
		/**
		 * Eventi del prossimo weekend
		 * (solo anteprima per la landing)
		 */
		$events = $this->eventModel->getEventsByDateRange(
			$currentWeekend['start'],
			$currentWeekend['end']
		);
		/**
		 * Aggiunta immagini
		 */
		foreach($events as &$event){
			$cover = $this->imageService->getPrimary(
				'event',
				$event['id']
			);
			if(!empty($cover)){
				$event['immagine'] = '/public_assets/' . $cover['path'];
				$event['immagine_width'] = $cover['width'];
				$event['immagine_height'] = $cover['height'];
			}else{
				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}
		}
		/**
		 * Generazione prossimi weekend
		 */
		$prossimiWeekend = [];
		$weekend = $currentWeekend;
		for($i = 0; $i < 12; $i++){
			$slug = WeekendHelper::generateSlug(
				$weekend['start'],
				$weekend['end']
			);
			$prossimiWeekend[] = [
				'slug'  => $slug,
				'start' => $weekend['start'],
				'end'   => $weekend['end'],
				'label' => sprintf(
					'%s - %s',
					$weekend['start']->format('d/m/Y'),
					$weekend['end']->format('d/m/Y')
				),
			];
			$weekend = \App\Helpers\WeekendHelper::getNextWeekend(
				$weekend['start']
			);
		}
		$canonicalUrl = URL_ROOT_SITE . '/eventi-cosplay-weekend';
		$breadcrumbs = [
			[
				'label' => 'Home',
				'url'   => URL_ROOT_SITE . '/',
			],
			[
				'label' => 'Eventi cosplay nel weekend',
				'url'   => $canonicalUrl,
			],
		];
		$currentWeekendSlug = WeekendHelper::generateSlug(
			$currentWeekend['start'],
			$currentWeekend['end']
		);
		$testo = "Scopri gli eventi cosplay organizzati nei weekend in Italia.
Trova fiere del fumetto, festival anime, raduni cosplay e appuntamenti nerd
nei prossimi fine settimana.";
		$this->view('events/weekend', [
			/**
			 * Anteprima prossimo weekend
			 */
			'events'          => $events,
			/**
			 * Weekend corrente
			 */
			'weekendCorrente' => [
				'slug'  => $currentWeekendSlug,
				'start' => $currentWeekend['start'],
				'end'   => $currentWeekend['end'],
			],
			/**
			 * Archivio/prossimi weekend
			 */
			'prossimiWeekend' => $prossimiWeekend,
			'noindex' => $noindex,
			'canonicalUrl' => $canonicalUrl,
			'breadcrumbs' => $breadcrumbs,
			'testo_descrittivo' => $testo,
		]);
	}

	public function updateAllScores(){
		//var_dump($this->eventRankingService->updateAllScores());
		$signal = new EventSignalService();
		$scoring = new EventScoringEngine();
		$repo = new EventRepository();
		$views7d = $signal->getViews7d();
		$events = $repo->getAll();
		foreach($events as $event){
			$views = $views7d[$event['id']] ?? 0;
			$score = $scoring->calculate($event, $views);
			$repo->updateScore($event['id'], $score, $views);
		}
		echo "OK - scores aggiornati\n";
	}

	public function meseSpecifico(array $params){
		$slug = $params[0] ?? null;
		if(!$slug){
			Session::setFlash('error', 'Slug evento non valido.');
			header('Location: ' . $this->eventsBasePath);
			exit();
		}
		$noindex = false;
		$mese = MonthHelper::parseSlug($slug);
		if(!$mese){
			header('Location: ' . $this->eventsBasePath);
			exit();
		}
		$from = MonthHelper::getStartDate(
			$mese['month'],
			$mese['year']
		);

		$to = MonthHelper::getEndDate(
			$mese['month'],
			$mese['year']
		);
		$weekendDelMese = WeekendHelper::getWeekendsOfMonth(
			$mese['year'],
			$mese['month']
		);
		$monthlyEvents = array_values(array_filter(
			$this->eventModel->getEventsByYear($mese['year']),
			static function (array $event) use ($mese): bool {
				if (empty($event['data_inizio'])) {
					return false;
				}

				return (int) date('n', strtotime((string) $event['data_inizio'])) === (int) $mese['month'];
			}
		));
		foreach($monthlyEvents as $index => &$event){
			$cover = $this->imageService->getPrimary('event', $event['id']);
			if(!empty($cover)){
				$event['immagine'] = '/public_assets/' . $cover['path'];
				$event['immagine_width'] = $cover['width'];
				$event['immagine_height'] = $cover['height'];
			}else{
				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}
		}
		unset($event);

		$topEvents = count($monthlyEvents) > 3 ? array_slice($monthlyEvents, 0, 3) : [];
		$importantEvents = [];
		$newEvents = [];

		// 📅 nome mese SEO
		$mese = $mese['label'];
		$frasi = [
			"un mese imperdibile per gli appassionati",
			"uno dei momenti migliori dell’anno per il cosplay",
			"un periodo ricco di eventi in tutta Italia",
		];
		$eventiMese = $monthlyEvents;
		$eventiMeseUnici = [];
		foreach ($eventiMese as $event) {
			$eventId = (int) ($event['id'] ?? 0);
			if ($eventId > 0) {
				$eventiMeseUnici[$eventId] = true;
			}
		}
		$totEventi = count($eventiMeseUnici);
		$listaTop = implode(', ', array_filter(array_map(
			static fn(array $event): string => trim((string) ($event['titolo'] ?? '')),
			$topEvents
		)));
		if ($listaTop === '') {
			$listaTop = 'i principali appuntamenti del mese';
		}
		$randomFrase = $frasi[array_rand($frasi)];
		$testo = "<p>
{$mese} è {$randomFrase} 🇮🇹 è uno dei mesi più ricchi di eventi cosplay in Italia 🇮🇹  
Con oltre {$totEventi} eventi tra fiere del fumetto, festival e raduni, questo mese offre appuntamenti imperdibili per tutti gli appassionati.

Dai grandi eventi come {$listaTop}, fino alle fiere locali in crescita, ecco i migliori eventi cosplay del mese selezionati per te.
</p>";
		// 🍞 breadcrumb
		$breadcrumbs = [
			['label' => 'Home', 'url' => URL_ROOT_SITE . '/'],
			['label' => "Eventi Cosplay Mese", 'url' => URL_ROOT_SITE . '/eventi-cosplay-mese'],
			['label' => "Eventi Cosplay {$mese}", 'url' => URL_ROOT_SITE . '/eventi-cosplay-mese/'. $slug],
		];



		$this->view('events/mese-specifico', [
			'events'            => $monthlyEvents,
			'nuovi'             => $newEvents,
			'piuImportanti'     => $importantEvents,
			'top3'              => $topEvents,
			'mese'              => $mese,
			'breadcrumbs'       => $breadcrumbs,
			'testo_descrittivo' => $testo,
			'weekendDelMese' => $weekendDelMese,
			'eventCount'        => $totEventi,
		]);
	}
	public function mese()
	{
		$breadcrumbs = [
			[
				'label' => 'Home',
				'url' => URL_ROOT_SITE . '/'
			],
			[
				'label' => 'Eventi Cosplay Mese',
				'url' => URL_ROOT_SITE . '/eventi-cosplay-mese'
			],
		];


		// Prossimi mesi disponibili
		$mesiDisponibili = $this->eventModel->getAvailableMonths();


		// Raggruppamento per anno
		$mesiPerAnno = [];

		foreach ($mesiDisponibili as $mese) {

			$mesiPerAnno[$mese['year']][] = $mese;

		}



		$pageTitle = 'Eventi cosplay mese per mese in Italia';


		$testo = '
    <p>
ItalianCosplay raccoglie gli eventi cosplay organizzati in Italia e li suddivide
per periodo, così puoi trovare facilmente fiere del fumetto, festival anime,
raduni cosplay, contest e manifestazioni dedicate alla cultura nerd.
</p>

<p>
Ogni mese può ospitare grandi appuntamenti nazionali e numerosi eventi locali:
consulta il calendario mensile per scoprire quali fiere visitare, dove incontrare
altri appassionati e quali manifestazioni stanno arrivando.
</p>

<p>
Le pagine mensili vengono aggiornate con nuovi eventi approvati dalla community,
con informazioni su date, location, organizzatori e collegamenti ufficiali.
</p>
    ';


		$this->view('events/mese', [
			'breadcrumbs' => $breadcrumbs,
			'mesiPerAnno' => $mesiPerAnno,
			'pageTitle' => $pageTitle,
			'testo_descrittivo' => $testo,
		]);
	}



	function formattaLista($items){
		$count = count($items);
		if($count === 0){
			return '';
		}
		if($count === 1){
			return $items[0];
		}
		if($count === 2){
			return $items[0] . ' e ' . $items[1];
		}
		$last = array_pop($items);

		return implode(', ', $items) . ' e ' . $last;
	}

	public function eventImageMigrationService(){
		$migration = new EventImageMigrationService($this->eventModel->getDbConnection());
		$migration->migrate();
	}

	public function weekendSpecifico(array $params) : void{
		$slug = $params[0] ?? null;
		if(!$slug){
			Session::setFlash('error', 'Slug evento non valido.');
			header('Location: ' . $this->eventsBasePath);
			exit();
		}
		$noindex = false;
		$weekend = WeekendHelper::parseSlug($slug);
		if(!$weekend){
			header('Location: ' . $this->eventsBasePath);
			exit();
		}
		$eventsData = $this->eventFeedService->getSpecificWeekend(
			$weekend['start'],
			$weekend['end']
		);
		$events = $eventsData['all'];
		foreach($eventsData['all'] as $index => &$event){
			$cover = $this->imageService->getPrimary('event', $event['id']);
			if(!empty($cover)){
				$event['immagine'] = '/public_assets/' . $cover['path'];
				$event['immagine_width'] = $cover['width'];
				$event['immagine_height'] = $cover['height'];
			}else{
				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}
		}
		foreach($eventsData['new'] as $index => &$event){
			$cover = $this->imageService->getPrimary('event', $event['id']);
			if(!empty($cover)){
				$event['immagine'] = '/public_assets/' . $cover['path'];
				$event['immagine_width'] = $cover['width'];
				$event['immagine_height'] = $cover['height'];
			}else{
				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}
		}
		foreach($eventsData['big'] as $index => &$event){
			$cover = $this->imageService->getPrimary('event', $event['id']);
			if(!empty($cover)){
				$event['immagine'] = '/public_assets/' . $cover['path'];
				$event['immagine_width'] = $cover['width'];
				$event['immagine_height'] = $cover['height'];
			}else{
				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}
		}
		foreach($eventsData['top3'] as $index => &$event){
			$cover = $this->imageService->getPrimary('event', $event['id']);
			if(!empty($cover)){
				$event['immagine'] = '/public_assets/' . $cover['path'];
				$event['immagine_width'] = $cover['width'];
				$event['immagine_height'] = $cover['height'];
			}else{
				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}
		}
		if(empty($events)){
			//$noindex = true;
		}
		$canonicalUrl = URL_ROOT_SITE . '/eventi-cosplay-weekend/' . $slug;
		$weekend = $eventsData['label'];
		$eventiTop_titoli = '';
		if(!empty($eventsData['top3'])){
			$eventiTop_titoli = implode(',', array_column($eventsData['top3'], 'titolo'));
		}
		$breadcrumbs = [
			['label' => 'Home', 'url' => URL_ROOT_SITE . '/'],
			['label' => 'Eventi Cosplay Weekend', 'url' => URL_ROOT_SITE . '/eventi-cosplay-weekend'],
			['label' => 'Eventi Cosplay Nel Weekend ' . $weekend, 'url' => URL_ROOT_SITE . '/eventi-cosplay-weekend/' . $slug],
		];
		$SchemaListaEventi = $this->generaSchemaListaEventiSemplice($events);
		$testo = "Questo weekend, $weekend, in tutta Italia si svolgono numerosi eventi dedicati al mondo cosplay, fumetti e cultura nerd.
	Dai grandi festival alle fiere locali, gli appassionati avranno diverse occasioni per incontrare cosplayer, partecipare a contest e vivere la community dal vivo.
	Di seguito trovi la lista completa degli eventi cosplay in programma questo fine settimana.";
		$this->view('events/weekend-specifico', [
			'events'            => $eventsData['all'],
			'nuovi'             => $eventsData['new'],
			'piuImportanti'     => $eventsData['big'],
			'top3'              => $eventsData['top3'],
			'weekend'           => $weekend,
			'noindex'           => $noindex,
			'canonicalUrl'      => $canonicalUrl,
			'breadcrumbs'       => $breadcrumbs,
			'SchemaListaEventi' => $SchemaListaEventi,
			'testo_descrittivo' => $testo,
			'eventiTop_titoli'  => $eventiTop_titoli,
		]);
	}

	/**
	 * Endpoint AJAX per AdminList eventi
	 *
	 * GET /admin/events/data
	 */
	/**
	 * Endpoint AJAX AdminList eventi
	 *
	 * GET /admin/events/data
	 */
	/**
	 * Endpoint AJAX AdminList eventi
	 *
	 * GET /admin/events/data
	 */
	/**
	 * AJAX data endpoint per AdminList eventi
	 */
	public function data(): void
	{
		header('Content-Type: application/json; charset=utf-8');
		try {
			$page = max(
				(int) ($_GET['page'] ?? 1),
				1
			);
			$perPage = (int) ($_GET['perPage'] ?? 25);
			if (!in_array($perPage, [10, 25, 50, 100], true)) {
				$perPage = 25;
			}
			$search = trim(
				$_GET['search'] ?? ''
			);
			$sort = $_GET['sort'] ?? 'data_inizio';
			$direction =
				strtolower(
					$_GET['direction'] ?? 'desc'
				);
			$allowedSorts = [
				'id',
				'titolo',
				'data_inizio',
				'data_fine',
				'luogo',
				'event_size',
				'approvato'
			];
			if (!in_array($sort, $allowedSorts, true)) {
				$sort = 'data_inizio';
			}
			if (!in_array($direction, ['asc','desc'], true)) {
				$direction = 'desc';
			}
			$filters = $_GET['filters'] ?? [];
			$result =
				$this->eventRepository->getPaginated([
					'page' => $page,
					'perPage' => $perPage,
					'search' => $search,
					'sort' => $sort,
					'direction' => $direction,
					'filters' => $filters
				]);





			$total =
				$result['total'];



			$pages =
				(int) ceil(
					$total / $perPage
				);




			$data = [];



			foreach ($result['data'] as $event) {


				$data[] = [

					'id' =>
						$event['id'],


					'titolo' =>
						$event['titolo'],


					'data_inizio' =>
						$event['data_inizio'],


					'luogo' =>
						$event['luogo'],


					'event_size' =>
						$event['event_size'],


					'approvato' =>
						$event['approvato']
							? 'approved'
							: 'pending',



				'_links' => [

					'view' =>
						'/eventi/' . $event['slug'],


					'edit' =>
						'/admin/events/edit/' . $event['id'],


					'copy' =>
						'/admin/events/copy/' . $event['id'],


					'delete' =>
						'/admin/events/delete/' . $event['id']

					]

				];


			}




			echo json_encode([

				'success' => true,


				'data' => $data,


				'meta' => [

					'page' => $page,

					'pages' => $pages,

					'total' => $total

				]

			]);


		} catch (\Throwable $e) {
			http_response_code(500);

			echo json_encode([

				'success' => false,

				'message' =>
					$e->getMessage(),

				'file' =>
					$e->getFile(),

				'line' =>
					$e->getLine()

			]);
		}

	}

	public function detail(array $params) : void{
		$id = $params[0] ?? null;
		if(!$id){
			Session::setFlash('error', 'Slug evento non valido.');
			header('Location: ' . $this->eventsBasePath);
			exit();
		}
		$event =
			$this->eventRepository->find($id);


		echo json_encode([
			'success' => true,
			'data' => $event
		]);
	}

	private function getEventStatus(int $approved): string
	{
		return $approved === 1
			? 'approved'
			: 'pending';
	}
	private function getImageUrl(?string $image): ?string
	{
		if (!$image) {
			return null;
		}


		return '/uploads/events/' . $image;
	}
}
