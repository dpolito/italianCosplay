<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (!defined('APP_ROOT')) {
	define('APP_ROOT', '/web/htdocs/www.italiancosplay.it/home');
}
require_once APP_ROOT . '/app/Core/Database.php';

use App\Core\Database;
use App\Models\BaseModel;


require_once APP_ROOT . '/app/Models/BaseModel.php'; // La classe BaseModel
$config = require APP_ROOT . '/app/config/database.php';

Database::getInstance($config);
$baseModel = new BaseModel();
$dbConnection = $baseModel->getDbConnection();

// Impostazioni dei cookie
$cookieName = 'user_cookie_consent';
$cookieExpiry = time() + (86400 * 365); // Scadenza tra 1 anno
$cookiePath = '/';

header('Content-Type: application/json');

// Ricevi i dati JSON inviati dal client
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
	http_response_code(400);
	echo json_encode(['status' => 'error', 'message' => 'Dati non validi.']);
	exit;
}

$consentLevel = $input['level'] ?? 'necessary';
$consentDetails = $input['details'] ?? ['analytics' => false, 'marketing' => false];
$userId = $_COOKIE['user_consent_id'] ?? uniqid('consent_', true);

// 1. Imposta i cookie per le decisioni immediate
// Cookie principale con le scelte dettagliate
setcookie($cookieName, json_encode($consentDetails), $cookieExpiry, $cookiePath);
// Cookie ID per riconoscere l'utente
setcookie('user_consent_id', $userId, $cookieExpiry, $cookiePath);

// 2. Salva le preferenze nel database
try {
	// Controlla se l'utente esiste già (UPSERT)
	$stmt = $dbConnection->prepare(
		"INSERT INTO cookie_consent (user_id, consent_level, consent_details)
         VALUES (:user_id, :consent_level, :consent_details)
         ON DUPLICATE KEY UPDATE
         consent_level = VALUES(consent_level),
         consent_details = VALUES(consent_details),
         updated_at = CURRENT_TIMESTAMP"
	);

	$stmt->execute([
		'user_id' => $userId,
		'consent_level' => $consentLevel,
		'consent_details' => json_encode($consentDetails)
	]);

	echo json_encode(['status' => 'success', 'message' => 'Consenso salvato.']);

} catch (PDOException $e) {
	http_response_code(500);
	// Per il debug: error_log($e->getMessage());
	echo json_encode(['status' => 'error', 'message' => 'Impossibile salvare il consenso.']);
}
