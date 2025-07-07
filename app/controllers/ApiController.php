<?php

require_once APP_ROOT . '/app/core/Controller.php';
require_once APP_ROOT . '/app/models/Regione.php';
require_once APP_ROOT . '/app/models/Provincia.php';
require_once APP_ROOT . '/app/models/Comune.php';

class ApiController extends Controller
{
	private $regioneModel;
	private $provinciaModel;
	private $comuneModel;

	public function __construct()
	{
		$this->regioneModel = new Regione();
		$this->provinciaModel = new Provincia();
		$this->comuneModel = new Comune();
	}

	/**
	 * Recupera tutte le regioni.
	 * Endpoint: /api/regioni
	 */
	public function getRegioni()
	{
		$regioni = $this->regioneModel->getAll();
		header('Content-Type: application/json');
		echo json_encode($regioni);
		exit(); // Termina lo script dopo l'invio della risposta JSON
	}

	/**
	 * Recupera le province in base all'ID della regione.
	 * Endpoint: /api/province/{regioneId}
	 * @param array $params Contiene l'ID della regione.
	 */
	public function getProvince($params)
	{
		$regioneId = $params[0] ?? null;

		// Validazione dell'ID della regione
		$regioneId = filter_var($regioneId, FILTER_VALIDATE_INT);

		if ($regioneId === false || $regioneId === null) {
			header('Content-Type: application/json');
			echo json_encode(['error' => 'ID Regione non valido.']);
			exit(); // Termina lo script in caso di errore
		}

		$province = $this->provinciaModel->getByRegioneId($regioneId);
		header('Content-Type: application/json');
		echo json_encode($province);
		exit(); // Termina lo script dopo l'invio della risposta JSON
	}

	/**
	 * Recupera i comuni in base all'ID della provincia.
	 * Endpoint: /api/comuni/{provinciaId}
	 * @param array $params Contiene l'ID della provincia.
	 */
	public function getComuni($params)
	{
		$provinciaId = $params[0] ?? null;

		// Validazione dell'ID della provincia
		$provinciaId = filter_var($provinciaId, FILTER_VALIDATE_INT);

		if ($provinciaId === false || $provinciaId === null) {
			header('Content-Type: application/json');
			echo json_encode(['error' => 'ID Provincia non valido.']);
			exit(); // Termina lo script in caso di errore
		}

		$comuni = $this->comuneModel->getByProvinciaId($provinciaId);
		header('Content-Type: application/json');
		echo json_encode($comuni);
		exit(); // Termina lo script dopo l'invio della risposta JSON
	}
}
