<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Models\Event;


class ApiEventController extends Controller
{
	private Event $eventModel;
	private array $apiKeys = [];

	public function __construct()
	{
		$this->eventModel = new Event();
		$config = require APP_ROOT . '/app/config/api.php';
		$this->apiKeys = $config['api_keys'];

		$this->checkApiKey();
	}
	private function checkApiKey(): void
	{
		$providedKey = $_SERVER['HTTP_X_API_KEY']
			?? $_GET['apikey']
			?? null;

		if (!$providedKey) {
			http_response_code(401);
			echo json_encode([
				'status' => 'error',
				'message' => 'API key mancante'
			]);
			exit;
		}
		if (
			!isset($this->apiKeys[$providedKey]) ||
			!$this->apiKeys[$providedKey]['active']
		) {
			http_response_code(401);
			echo json_encode([
				'status' => 'error',
				'message' => 'API key non valida'
			]);
			exit;
		}
	}

	public function index()
	{
		header('Content-Type: application/json; charset=utf-8');
		header('X-Robots-Tag: noindex, nofollow');
		header('Cache-Control: public, max-age=300');

		// Filtri opzionali
		$regione   = $_GET['regione']   ?? null;
		$provincia = $_GET['provincia'] ?? null;
		$comune    = $_GET['comune']    ?? null;
		$dal       = $_GET['dal']       ?? date('Y-m-d');
		$limit = min(max((int)($_GET['limit'] ?? 50), 1), 100);

		$eventi = $this->eventModel->getApprovedEventsApi(
			$regione,
			$provincia,
			$comune,
			$limit
		);

		echo json_encode([
			'status'   => 'ok',
			'count'    => count($eventi),
			'generated'=> date('c'),
			'eventi'   => $eventi
		], JSON_UNESCAPED_UNICODE);

		exit;
	}

	public function show($slug)
	{
		header('Content-Type: application/json; charset=utf-8');
		header('X-Robots-Tag: noindex, nofollow');
		header('Cache-Control: public, max-age=300');

		$evento = $this->eventModel->findBySlugApi($slug);

		if (!$evento) {
			http_response_code(404);
			echo json_encode([
				'status'  => 'error',
				'message' => 'Evento non trovato'
			]);
			exit;
		}

		echo json_encode([
			'status' => 'ok',
			'evento' => $evento
		], JSON_UNESCAPED_UNICODE);

		exit;
	}
}
