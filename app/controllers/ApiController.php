<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/core/Database.php'; // Assicurati che sia incluso

// Includi i nuovi modelli
require_once APP_ROOT . '/app/models/Regione.php';
require_once APP_ROOT . '/app/models/Provincia.php';
require_once APP_ROOT . '/app/models/Comune.php';

class ApiController extends Controller
{
	private Regione $regioneModel;
	private Provincia $provinciaModel;
	private Comune $comuneModel;

	public function __construct()
	{
		// Inizializza le istanze dei modelli
		$this->regioneModel = new Regione();
		$this->provinciaModel = new Provincia();
		$this->comuneModel = new Comune();
	}

	/**
	 * Recupera tutte le regioni.
	 * Restituisce un JSON con {id, nome}.
	 */
	public function getRegioni()
	{
		header('Content-Type: application/json');
		try {
			$regioni = $this->regioneModel->getAll();
			echo json_encode($regioni);
		} catch (Exception $e) { // Cattura eccezioni generiche dal modello
			http_response_code(500);
			echo json_encode(['error' => 'Errore nel recupero delle regioni: ' . $e->getMessage()]);
		}
		exit();
	}

	/**
	 * Recupera le province in base all'ID della regione.
	 * Restituisce un JSON con {id, nome}.
	 * @param array $params Contiene l'ID della regione.
	 */
	public function getProvinceByRegione($params)
	{
		header('Content-Type: application/json');
		$regioneId = $params[0] ?? null;

		if (!$regioneId || !is_numeric($regioneId)) {
			http_response_code(400);
			echo json_encode(['error' => 'ID regione non valido.']);
			exit();
		}

		try {
			$province = $this->provinciaModel->getByRegioneId($regioneId);
			echo json_encode($province);
		} catch (Exception $e) {
			http_response_code(500);
			echo json_encode(['error' => 'Errore nel recupero delle province: ' . $e->getMessage()]);
		}
		exit();
	}

	/**
	 * Recupera i comuni in base all'ID della provincia.
	 * Restituisce un JSON con {id, nome}.
	 * @param array $params Contiene l'ID della provincia.
	 */
	public function getComuniByProvincia($params)
	{
		header('Content-Type: application/json');
		$provinciaId = $params[0] ?? null;

		if (!$provinciaId || !is_numeric($provinciaId)) {
			http_response_code(400);
			echo json_encode(['error' => 'ID provincia non valido.']);
			exit();
		}

		try {
			$comuni = $this->comuneModel->getByProvinciaId($provinciaId);
			echo json_encode($comuni);
		} catch (Exception $e) {
			http_response_code(500);
			echo json_encode(['error' => 'Errore nel recupero dei comuni: ' . $e->getMessage()]);
		}
		exit();
	}
}
