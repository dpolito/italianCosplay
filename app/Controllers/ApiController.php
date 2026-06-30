<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Models\Comune;
use App\Models\Provincia;
use App\Models\Regione;

class ApiController extends Controller
{
	private Regione $regioneModel;
	private Provincia $provinciaModel;
	private Comune $comuneModel;

	public function __construct()
	{
		$this->regioneModel = new Regione();
		$this->provinciaModel = new Provincia();
		$this->comuneModel = new Comune();
	}

	/**
	 * Restituisce tutte le regioni come JSON.
	 */
	public function getRegioni()
	{
		// Inizia l'output buffering per catturare eventuali output indesiderati
		ob_start();

		header('Content-Type: application/json');
		$regioni = $this->regioneModel->getAll();
		echo json_encode($regioni);

		// Termina l'output buffering e invia solo il contenuto desiderato
		ob_end_flush();
	}

	/**
	 * Restituisce le province di una specifica regione come JSON.
	 * @param array $params Contiene l'ID della regione.
	 */
	public function getProvinceByRegione($params) // Ho mantenuto il nome del metodo del router
	{
		// Inizia l'output buffering
		ob_start();

		header('Content-Type: application/json');
		$regioneId = $params[0] ?? null;

		if (!$regioneId || !is_numeric($regioneId)) {
			http_response_code(400); // Bad Request
			echo json_encode(['error' => 'ID regione non valido.']);
			ob_end_flush(); // Invia il JSON di errore e termina il buffering
			return;
		}

		// CORREZIONE QUI: Usa getByRegioneId() come definito in Provincia.php
		$province = $this->provinciaModel->getByRegioneId($regioneId);
		echo json_encode($province);

		// Termina l'output buffering
		ob_end_flush();
	}

	/**
	 * Restituisce i comuni di una specifica provincia come JSON.
	 * @param array $params Contiene l'ID della provincia.
	 */
	public function getComuniByProvincia($params) // Ho mantenuto il nome del metodo del router
	{
		// Inizia l'output buffering
		ob_start();

		header('Content-Type: application/json');
		$provinciaId = $params[0] ?? null;

		if (!$provinciaId || !is_numeric($provinciaId)) {
			http_response_code(400); // Bad Request
			echo json_encode(['error' => 'ID provincia non valido.']);
			ob_end_flush(); // Invia il JSON di errore e termina il buffering
			return;
		}

		// Questo è corretto: Comune::getAll() accetta provincia_id
		$comuni = $this->comuneModel->getAll($provinciaId);
		echo json_encode($comuni);

		// Termina l'output buffering
		ob_end_flush();
	}
	public function searchComuni($params) // Ho mantenuto il nome del metodo del router
	{
		// Inizia l'output buffering
		ob_start();

		header('Content-Type: application/json');
		$search = $params[0] ?? null;

		// Questo è corretto: Comune::getAll() accetta provincia_id
		$comuni = $this->comuneModel->search($search);
		echo json_encode($comuni);

		// Termina l'output buffering
		ob_end_flush();
	}
}
