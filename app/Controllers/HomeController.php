<?php
namespace App\Controllers;
// Non è necessario includere il modello Event qui a meno che tu non voglia
// recuperare eventi reali per la homepage. Per ora, useremo dati statici.
use App\Core\Controller;
use App\Models\Event;
use App\Models\TipoEvento;
use App\Services\EventFeedService;

class HomeController extends Controller
{
	private Event $eventModel;
	private TipoEvento $tipoEventoModel;
	private EventFeedService $eventFeedService;

	public function __construct()
	{
		$this->eventModel = new Event();
		$this->tipoEventoModel = new TipoEvento();
		$this->eventFeedService = new EventFeedService();
		// Definisci il percorso base per gli eventi qui per evitare duplicazioni
		// CORREZIONE QUI: Rimuovi lo slash finale da URL_ROOT prima di concatenare
	}

	/**
	 * Mostra la homepage del sito.
	 */
	public function index()
	{
		$feed = $this->eventFeedService->getHomeFeed();
		$this->view('home/index', [
			'top' => $feed['top'],
			'trending' => $feed['trending'],
			'new' => $feed['new'],
			'feed' => $feed['feed'],
		]);
	}

	/**
	 * Mostra la homepage del sito.
	 */
	public function privacy()
	{
		// Puoi recuperare dati dal database qui se vuoi popolare la sezione "Eventi in Evidenza"
		// $events = $this->eventModel->getFeaturedEvents(); // Esempio
		$ultimi_eventi = $this->eventModel->getApprovedEvents(null, null, null, 12);
		$tipi_eventi = $this->tipoEventoModel->getAll();
		// Passa un titolo alla vista per il layout
		$data = [];

		$this->view('home/privacy', $data);
	}

	/**
	 * Mostra la homepage del sito.
	 */
	public function cookies()
	{
		// Puoi recuperare dati dal database qui se vuoi popolare la sezione "Eventi in Evidenza"
		// $events = $this->eventModel->getFeaturedEvents(); // Esempio
		$ultimi_eventi = $this->eventModel->getApprovedEvents(null, null, null, 12);
		$tipi_eventi = $this->tipoEventoModel->getAll();
		// Passa un titolo alla vista per il layout
		$data = [];

		$this->view('home/cookie', $data);
	}
}
