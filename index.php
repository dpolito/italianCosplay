<?php
use App\Core\Database;
use App\Core\ErrorHandler;
use App\Core\Router;
use App\Core\Session;


ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Definizione di costanti per la root del progetto e il nome dell'applicazione
define('APP_NAME', 'Italian Cosplay');
define('APP_ROOT', __DIR__);

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$script_dir = dirname($script_name);


// Rimuove eventuali 'public' o sottocartelle non desiderate dall'URL base
$base_url_path = str_replace('/public', '', $script_dir);

// Rimuovi eventuali slash finali da $base_url_path prima di definire URL_ROOT
// Questo è il punto cruciale se il tuo problema è qui.
define('URL_ROOT', rtrim($protocol . '://' . $host . $base_url_path, '/'));
define('URL_ROOT_SITE', rtrim($protocol . '://' . $host , '/'));

// Carica la configurazione applicativa condivisa.
require_once APP_ROOT . '/app/config/app.php';
// Carica il file dell'autoloader di Composer, se presente.
// Questo è il metodo preferito per gestire le dipendenze.


if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
	require_once APP_ROOT . '/vendor/autoload.php';
} else {
	// Se Composer non è usato o le dipendenze non sono installate,
	// carica manualmente le classi principali nell'ordine corretto.
	// L'ordine è importante per le dipendenze tra classi (es. Controller prima dei Controller specifici).
	require_once APP_ROOT . '/app/Core/Database.php';
	require_once APP_ROOT . '/app/Core/ErrorHandler.php';
	require_once APP_ROOT . '/app/Core/Session.php';
	require_once APP_ROOT . '/app/Core/Router.php';
	require_once APP_ROOT . '/app/Core/Controller.php'; // I controller estendono questa classe
	require_once APP_ROOT . '/app/Services/TelegramNotificationService.php';
	require_once APP_ROOT . '/app/Models/BaseModel.php'; // I modelli estendono questa classe
	require_once APP_ROOT . '/app/Models/User.php'; // Per AuthController e AdminController
	require_once APP_ROOT . '/app/Models/Event.php'; // Per EventController
	require_once APP_ROOT . '/app/Models/EventMaster.php'; // Per AdminEventMasterController
	require_once APP_ROOT . '/app/Models/TipoEvento.php'; // Per EventController
	require_once APP_ROOT . '/app/Models/Regione.php'; // Per EventController e ApiController
	require_once APP_ROOT . '/app/Models/Provincia.php'; // Per EventController e ApiController
	require_once APP_ROOT . '/app/Models/Comune.php'; // Per EventController e ApiController
	require_once APP_ROOT . '/app/Controllers/HomeController.php'; // Controller specifici
	require_once APP_ROOT . '/app/Controllers/AuthController.php';
	require_once APP_ROOT . '/app/Controllers/AdminController.php';
	require_once APP_ROOT . '/app/Controllers/AdminEventMasterController.php';
	require_once APP_ROOT . '/app/Controllers/EventController.php';
	require_once APP_ROOT . '/app/Controllers/ApiController.php';
}

ErrorHandler::register();

// Avvia la sessione.
// Si assume che Session::start() gestisca internamente session_start()
// e controlli se la sessione è già stata avviata.
Session::start();

// Carica le configurazioni del database e inizializza la connessione.
$db_config = require_once APP_ROOT . '/app/config/database.php';
Database::getInstance($db_config); // Passa la configurazione al singleton

// Inizializza il router.
$router = new Router();
$GLOBALS['router'] = $router;

// Includi tutte le definizioni delle rotte.
require_once APP_ROOT . '/app/routes.php';

// Ottieni l'URI della richiesta e il metodo HTTP.
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Gestione degli assets pubblici:
// Questo blocco evita che il router tenti di processare le richieste per file statici
// come immagini, CSS o JavaScript che si trovano nella directory 'public_assets'.
// In un ambiente di produzione, è preferibile configurare il server web (Apache/Nginx)
// per servire direttamente questi file, migliorando le prestazioni.
// Controlla se l'URL richiesto corrisponde a un file statico
if (preg_match('/^\/(public_assets)\//', $requestUri)) {
	$filePath = APP_ROOT . $requestUri;
	if (file_exists($filePath)) {
		$mimeType = mime_content_type($filePath);

		$extension = pathinfo($filePath, PATHINFO_EXTENSION);
		if ($extension === 'js') {
			$mimeType = 'application/javascript';
		} elseif ($extension === 'css') {
			$mimeType = 'text/css';
		}

		header('Content-Type: ' . $mimeType);
		readfile($filePath);
		exit();
	}
}
//---
// Nuova rotta: gestisci la richiesta a /save-consent.php
if ($requestUri === '/save-consent.php') {
	// Includi il file PHP dal percorso corretto e sicuro
	$filePath = APP_ROOT . '/app/Core/save-consent.php';
	if (file_exists($filePath)) {
		require $filePath;
		exit();
	}
}

// Dispatch della richiesta al controller e metodo appropriato.
$router->dispatch($requestUri, $requestMethod);
