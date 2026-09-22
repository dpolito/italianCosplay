<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Event;
use DateTime;
use DateTimeInterface;
use IntlDateFormatter;
use function array_map;
use function array_slice;
use function usort;

class EventFeedService
{
	protected $db;
	private Event $eventModel;
	private EventSignalService $eventSignalService;
	private EventScoringEngine $eventScoreService;
	private ImageService $imageService;
	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
		$this->eventModel = new Event();
		$this->eventSignalService = new EventSignalService();
		$this->eventScoreService = new EventScoringEngine();
		$this->imageService = new ImageService($this->eventModel->getDbConnection());
	}

	public function getHome(): array
	{
		return $this->db->query("
			SELECT *
			FROM events
			WHERE approvato = 1
			  AND deleted_at IS NULL
			ORDER BY final_score DESC
			LIMIT 30
		")->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getWeekend(): array
	{
		$today = new DateTime();
		$day = (int)$today->format('N');

		if ($day >= 6) {
			$saturday = (clone $today)->modify('last saturday');
		} else {
			$saturday = (clone $today)->modify('next saturday');
		}

		$sunday = (clone $saturday)->modify('+1 day');

		$events = $this->eventModel->getEventsByDateRange($saturday, $sunday);

		// 👉 views tutte in una botta sola
		$viewsMap = $this->eventSignalService->getViews7d();

		$enhanced = [];

		foreach ($events as $event) {
			$views7d = $viewsMap[$event['id']] ?? 0;

			$event['final_score'] = $this->eventScoreService->calculate($event, $views7d);

			$enhanced[] = $event;
		}

		// 🔥 ordinamento globale
		usort($enhanced, fn($a, $b) => $b['final_score'] <=> $a['final_score']);

		// =========================
		// 🧠 DEDUP ENGINE
		// =========================
		$usedIds = [];

		// 🔥 TOP 3
		$top3 = array_slice($enhanced, 0, 3);
		foreach ($top3 as $e) {
			$usedIds[$e['id']] = true;
		}

		// 🏆 BIG (escludo già usati)
		$big = [];
		foreach ($enhanced as $e) {
			if (!isset($usedIds[$e['id']]) && ($e['event_size'] ?? 0) >= 4) {
				$big[] = $e;
				$usedIds[$e['id']] = true;
			}
		}

		// 🆕 NUOVI (escludo già usati)
		$new = [];
		foreach ($enhanced as $e) {
			if (!isset($usedIds[$e['id']]) && $e['final_score'] > 120) {
				$new[] = $e;
				$usedIds[$e['id']] = true;
			}
		}

		// 📋 TUTTI (solo quelli NON usati)
		$all = [];
		foreach ($enhanced as $e) {
			if (!isset($usedIds[$e['id']])) {
				$all[] = $e;
			}
		}

		return [
			'all' => $all,
			'new' => $new,
			'big' => $big,
			'top3' => $top3,
			'label' => $this->formatWeekendLabel($saturday, $sunday)
		];
	}
	public function getSpecificWeekend(DateTimeInterface $from, DateTimeInterface $to): array
	{

		$events = $this->eventModel->getEventsByDateRange($from, $to);

		// 👉 views tutte in una botta sola
		$viewsMap = $this->eventSignalService->getViews7d();

		$enhanced = [];

		foreach ($events as $event) {
			$views7d = $viewsMap[$event['id']] ?? 0;

			$event['final_score'] = $this->eventScoreService->calculate($event, $views7d);

			$enhanced[] = $event;
		}

		// 🔥 ordinamento globale
		usort($enhanced, fn($a, $b) => $b['final_score'] <=> $a['final_score']);

		// =========================
		// 🧠 DEDUP ENGINE
		// =========================
		$usedIds = [];

		// 🔥 TOP 3
		$top3 = array_slice($enhanced, 0, 3);
		foreach ($top3 as $e) {
			$usedIds[$e['id']] = true;
		}

		// 🏆 BIG (escludo già usati)
		$big = [];
		foreach ($enhanced as $e) {
			if (!isset($usedIds[$e['id']]) && ($e['event_size'] ?? 0) >= 4) {
				$big[] = $e;
				$usedIds[$e['id']] = true;
			}
		}

		// 🆕 NUOVI (escludo già usati)
		$new = [];
		foreach ($enhanced as $e) {
			if (!isset($usedIds[$e['id']]) && $e['final_score'] > 120) {
				$new[] = $e;
				$usedIds[$e['id']] = true;
			}
		}

		// 📋 TUTTI (solo quelli NON usati)
		$all = [];
		foreach ($enhanced as $e) {
			if (!isset($usedIds[$e['id']])) {
				$all[] = $e;
			}
		}

		return [
			'all' => $all,
			'new' => $new,
			'big' => $big,
			'top3' => $top3,
			'label' => $this->formatWeekendLabel($from, $to)
		];
	}

	public function getMonthSpecific (DateTimeInterface $start, DateTimeInterface $end): array
	{

		$events = $this->eventModel->getEventsByMonth(
			(int) $start->format('Y'),
			(int) $start->format('n')
		);
		$eventsById = [];
		foreach ($events as $event) {
			$eventId = (int) ($event['id'] ?? 0);
			if ($eventId > 0 && !isset($eventsById[$eventId])) {
				$eventsById[$eventId] = $event;
			}
		}
		$events = array_values($eventsById);

		// 👉 views tutte in una botta sola
		$viewsMap = $this->eventSignalService->getViews7d();

		$enhanced = [];

		foreach ($events as $event) {
			$views7d = $viewsMap[$event['id']] ?? 0;

			$event['final_score'] = $this->eventScoreService->calculate($event, $views7d);

			$enhanced[] = $event;
		}

		// 🔥 ordinamento globale
		usort($enhanced, fn($a, $b) => $b['final_score'] <=> $a['final_score']);

		// =========================
		// 🧠 DEDUP ENGINE
		// =========================
		$usedIds = [];

		// 🔥 TOP 3
		$top3 = array_slice($enhanced, 0, 3);
		foreach ($top3 as $e) {
			$usedIds[$e['id']] = true;
		}

		// 🏆 BIG (escludo già usati)
		$big = [];
		foreach ($enhanced as $e) {
			if (!isset($usedIds[$e['id']]) && ($e['event_size'] ?? 0) >= 4) {
				$big[] = $e;
				$usedIds[$e['id']] = true;
			}
		}

		// 🆕 NUOVI (escludo già usati)
		$new = [];
		foreach ($enhanced as $e) {
			if (!isset($usedIds[$e['id']]) && $e['final_score'] > 120) {
				$new[] = $e;
				$usedIds[$e['id']] = true;
			}
		}

		// 📋 TUTTI (solo quelli NON usati)
		$all = [];
		foreach ($enhanced as $e) {
			if (!isset($usedIds[$e['id']])) {
				$all[] = $e;
			}
		}

		return [
			'all' => $all,
			'new' => $new,
			'big' => $big,
			'top3' => $top3,
			'label' => $this->formatMonthLabel($start)
		];
	}
	public function getMonth(): array
	{
		$today = new DateTime();

		$start = (clone $today)->modify('first day of this month')->setTime(0,0,0);
		$end = (clone $today)->modify('last day of this month')->setTime(23,59,59);

		$events = $this->eventModel->getEventsByDateRange($start, $end);

		// 👉 views tutte in una botta sola
		$viewsMap = $this->eventSignalService->getViews7d();

		$enhanced = [];

		foreach ($events as $event) {
			$views7d = $viewsMap[$event['id']] ?? 0;

			$event['final_score'] = $this->eventScoreService->calculate($event, $views7d);

			$enhanced[] = $event;
		}

		// 🔥 ordinamento globale
		usort($enhanced, fn($a, $b) => $b['final_score'] <=> $a['final_score']);

		// =========================
		// 🧠 DEDUP ENGINE
		// =========================
		$usedIds = [];

		// 🔥 TOP 3
		$top3 = array_slice($enhanced, 0, 3);
		foreach ($top3 as $e) {
			$usedIds[$e['id']] = true;
		}

		// 🏆 BIG (escludo già usati)
		$big = [];
		foreach ($enhanced as $e) {
			if (!isset($usedIds[$e['id']]) && ($e['event_size'] ?? 0) >= 4) {
				$big[] = $e;
				$usedIds[$e['id']] = true;
			}
		}

		// 🆕 NUOVI (escludo già usati)
		$new = [];
		foreach ($enhanced as $e) {
			if (!isset($usedIds[$e['id']]) && $e['final_score'] > 120) {
				$new[] = $e;
				$usedIds[$e['id']] = true;
			}
		}

		// 📋 TUTTI (solo quelli NON usati)
		$all = [];
		foreach ($enhanced as $e) {
			if (!isset($usedIds[$e['id']])) {
				$all[] = $e;
			}
		}

		return [
			'all' => $all,
			'new' => $new,
			'big' => $big,
			'top3' => $top3,
			'label' => $this->formatMonthLabel($start)
		];
	}
	private function formatWeekendLabel(DateTimeInterface $saturday, DateTimeInterface $sunday): string
	{
		$formatter = new IntlDateFormatter(
			'it_IT',
			IntlDateFormatter::LONG,
			IntlDateFormatter::NONE
		);

		return $saturday->format('d') . ' - ' . $formatter->format($sunday);
	}
	public function formatMonthLabel(DateTimeInterface $date): string
	{
		$formatter = new IntlDateFormatter(
			'it_IT',
			IntlDateFormatter::LONG,
			IntlDateFormatter::NONE
		);

		$formatter->setPattern('LLLL yyyy'); // 👈 SOLO mese + anno

		return $formatter->format($date);
	}

	private function diversifyFeed(array $events): array
	{
		$result = [];

		$usedLocations = [];
		$maxPerLocation = 2; // max eventi per città/provincia

		foreach ($events as $event) {

			$locationKey = $event['comune_id'] ?? $event['provincia_id'] ?? 'unknown';

			if (!isset($usedLocations[$locationKey])) {
				$usedLocations[$locationKey] = 0;
			}

			// 🚫 limita eventi troppo simili
			if ($usedLocations[$locationKey] >= $maxPerLocation) {
				continue;
			}

			$result[] = $event;
			$usedLocations[$locationKey]++;

			// opzionale: limite globale feed
			if (count($result) >= 50) {
				break;
			}
		}

		return $result;
	}
	public function getHomeFeed(): array
	{
		// 📅 intervallo eventi
		$today = new DateTime();
		$end = new DateTime(date('Y') . '-12-31 23:59:59');

		$imageService = new ImageService(
			$this->eventModel->getDbConnection()
		);

		// 📦 eventi
		$events = $this->eventModel->getEventsByDateRange($today, $end);

		// 👀 views ultimi 7 giorni
		$viewsMap = $this->eventSignalService->getViews7d();

		$enhanced = [];

		// =========================================================
		// ENRICH EVENTS
		// =========================================================
		foreach ($events as $event) {

			// 🖼️ immagini
			/*$images = $this->eventModel->getImages($event['id']);

			$cover = array_map(
				fn($img) => $imageService->resolvePreset($img, 'medium'),
				$images
			);

			if (!empty($cover)) {

				$event['immagine'] =
					'/public_assets' . $cover[0]['url'];

				$event['immagine_width'] =
					$cover[0]['width'];

				$event['immagine_height'] =
					$cover[0]['height'];

			} else {

				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}*/
			$cover = $this->imageService->getPrimary('event', $event['id']);
			if(!empty($cover)){
				$event['immagine'] = 'public_assets/'. $cover['path'];
				$event['immagine_width'] = $cover['width'];
				$event['immagine_height'] = $cover['height'];
			}else{
				$event['immagine'] = '';
				$event['immagine_width'] = '';
				$event['immagine_height'] = '';
			}


			// 🔥 score
			$views7d = $viewsMap[$event['id']] ?? 0;

			$event['views7d'] = $views7d;

			$event['final_score'] =
				$this->eventScoreService->calculate(
					$event,
					$views7d
				);

			$enhanced[] = $event;
		}

		// =========================================================
		// RANKING PURO
		// =========================================================
		usort(
			$enhanced,
			fn($a, $b) =>
				$b['final_score'] <=> $a['final_score']
		);

		$ranked = $enhanced;

		// =========================================================
		// FEED DIVERSIFICATO
		// =========================================================
		$diversified = $this->diversifyFeed($ranked);

		// =========================================================
		// TOP HOME
		// =========================================================
		$top = array_slice($diversified, 0, 4);

		// =========================================================
		// TRENDING
		// usa ranking puro NON diversificato
		// =========================================================
		$trending = array_filter(
			$ranked,
			fn($e) =>
				$e['views7d'] >= 20 &&
				$e['final_score'] >= 90
		);

		$trending = array_slice(
			array_values($trending),
			0,
			12
		);

		// =========================================================
		// NUOVI EVENTI
		// =========================================================
		$new = array_filter(
			$ranked,
			fn($e) =>
				!empty($e['created_at']) &&
				strtotime($e['created_at']) >
				strtotime('-7 days')
		);

		$new = array_slice(
			array_values($new),
			0,
			12
		);

		// =========================================================
		// FEED PRINCIPALE
		// =========================================================
		$feed = array_slice($diversified, 4);

		// =========================================================
		// RETURN
		// =========================================================
		return [
			'top' => $top,
			'trending' => $trending,
			'new' => $new,
			'feed' => $feed
		];
	}
}
