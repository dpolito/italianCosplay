<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Definizione di una costante per la root del progetto
define('APP_NAME', 'Italian Cosplay');
define('APP_ROOT', __DIR__);

// Per debugging, puoi commentare questa riga in produzione
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Carica il file dell'autoloader di Composer, se presente.
// Questo è fondamentale se intendi usare Composer per le dipendenze.
if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
	require_once APP_ROOT . '/vendor/autoload.php';
} else {
	// Se non usi Composer o non hai ancora installato le dipendenze,
	// dovrai caricare manualmente tutte le classi necessarie.
	// Questo è un approccio meno robusto per progetti più grandi.
	require_once APP_ROOT . '/app/core/Router.php';
	require_once APP_ROOT . '/app/core/Database.php';
	require_once APP_ROOT . '/app/core/Session.php';
	require_once APP_ROOT . '/app/core/Controller.php';
	require_once APP_ROOT . '/app/models/BaseModel.php';
	require_once APP_ROOT . '/app/models/Event.php';
	require_once APP_ROOT . '/app/models/BaseModel.php'; // Se non è già incluso
	require_once APP_ROOT . '/app/models/Regione.php';
	require_once APP_ROOT . '/app/models/Provincia.php';
	require_once APP_ROOT . '/app/models/Comune.php';
	require_once APP_ROOT . '/app/controllers/ApiController.php'; // Assicurati che sia presente
	// ... e tutti gli altri modelli e classi core se non usi Composer
}


// Avvia la sessione
Session::start();

// Carica le configurazioni del database
$db_config = require_once APP_ROOT . '/app/config/database.php';

// Inizializza la connessione al database tramite la tua classe Database
// Ora $db_config è un array, come atteso da Database::getInstance()
$db = Database::getInstance($db_config);

$router = new Router();

// Definizione delle rotte
require_once APP_ROOT . '/app/routes.php';

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Gestione base degli assets pubblici per evitare che il router li processi
// Questo è un workaround se non hai configurato Alias nel server web
if (preg_match('/^\/(public_assets)\//', $requestUri)) {
	// Se la richiesta è per un asset pubblico, non processarla con il router
	// e lascia che il server web (o un semplice include) serva il file.
	// In un ambiente di produzione, la configurazione del server web è preferibile.
	$filePath = APP_ROOT . $requestUri;
	if (file_exists($filePath)) {
		$mimeType = mime_content_type($filePath);
		header('Content-Type: ' . $mimeType);
		readfile($filePath);
		exit();
	} else {
		http_response_code(404);
		echo "File non trovato.";
		exit();
	}
}

$router->dispatch($requestUri, $requestMethod);

?>
