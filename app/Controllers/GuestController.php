<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Guest;
use App\Services\GuestAnalyticsService;
use App\Services\ImageService;
use function header;
use function var_dump;
use const URL_ROOT;

class GuestController extends Controller{
	private Guest $guest;
	private ImageService $imageService;
	private GuestAnalyticsService  $guestAnalyticsService;

	public function __construct(){
		$this->guest = new Guest();
		$this->guestAnalyticsService = new GuestAnalyticsService();
		$this->imageService = new ImageService($this->guest->getDbConnection());
	}

	public function index()
	{
		$guests = $this->guest->getAll();

		foreach ($guests as &$guest) {

			$cover = $this->imageService->getPrimary('guest', $guest['id']);

			if (!empty($cover)) {
				$guest['immagine'] = $cover['path'];
				$guest['immagine_width'] = $cover['width'];
				$guest['immagine_height'] = $cover['height'];
			} else {
				$guest['immagine'] = '';
				$guest['immagine_width'] = '';
				$guest['immagine_height'] = '';
			}

			// futuro
			$guest['events_count'] = $this->guest->countEvents($guest['id']);
		}

		return $this->view('guests/index', [
			'guests' => $guests,
			'totalGuests' => count($guests),
		]);
	}

	public function show(array $params)
	{
		$slug = $params[0] ?? null;

		if (!$slug) {
			Session::setFlash('error', 'Slug ospite non valido.');
			header('Location: ' . URL_ROOT . 'ospiti');
			exit();
		}

		$guest = $this->guest->findBySlug($slug);

		if (!$guest) {
			http_response_code(404);
			echo "Ospite non trovato";
			return;
		}
		$this->guestAnalyticsService->trackView($guest['id']);

		// immagine principale
		$cover = $this->imageService->getPrimary('guest', $guest['id']);

		$guest['immagine'] = $cover['path'] ?? '';
		$guest['immagine_width'] = $cover['width'] ?? '';
		$guest['immagine_height'] = $cover['height'] ?? '';

		// EVENTI REALI
		$events = $this->guest->getEvents($guest['id']);

		return $this->view('guests/show', [
			'guest' => $guest,
			'events' => $events,
			'eventsCount' => count($events)
		]);
	}
}
