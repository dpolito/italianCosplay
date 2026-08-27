<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Guest;
use App\Services\GuestAnalyticsService;
use App\Services\FavoriteService;
use App\Services\ImageService;
use function header;
use function var_dump;
use const URL_ROOT;

class GuestController extends Controller{
	private Guest $guest;
	private ImageService $imageService;
	private GuestAnalyticsService  $guestAnalyticsService;
	private FavoriteService $favoriteService;

	public function __construct(){
		$this->guest = new Guest();
		$this->guestAnalyticsService = new GuestAnalyticsService();
		$this->favoriteService = new FavoriteService();
		$this->imageService = new ImageService($this->guest->getDbConnection());
	}

	public function index()
	{
		$this->requireFeature('enable_guest_directory', 'La directory guest è temporaneamente disattivata.');
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
		$this->requireFeature('enable_guest_directory', 'La directory guest è temporaneamente disattivata.');
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
		$isFavorited = !empty($_SESSION['user_id'])
			? $this->favoriteService->isFavorited((int) $_SESSION['user_id'], 'guest', (int) $guest['id'])
			: false;

		return $this->view('guests/show', [
			'guest' => $guest,
			'events' => $events,
			'eventsCount' => count($events),
			'isFavorited' => $isFavorited,
			'favoriteEntityType' => 'guest',
		]);
	}
}
