<?php

require_once APP_ROOT . '/app/core/Controller.php';
// Potrebbe essere necessario includere Event.php se la home mostra eventi in evidenza
// require_once APP_ROOT . '/app/models/Event.php';

class HomeController extends Controller
{
	public function index()
	{
		// Esempio: Mostrare gli ultimi 3 eventi approvati
		// $eventModel = new Event();
		// $latestEvents = $eventModel->getApprovedEvents(3); // Dovresti implementare un metodo getLatestApprovedEvents(limit)
		$this->view('home/index', /* ['latestEvents' => $latestEvents] */);
	}
}
