<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Comune;
use App\Models\Event;
use App\Models\Provincia;
use App\Models\Regione;
use App\Repositories\PhotoRepository;
use App\Services\ApiAccessService;
use App\Services\PhotoService;
use DateTimeImmutable;
use PDO;

final class ApiV1Controller extends Controller
{
	private array $config;
	private PDO $db;

	public function __construct()
	{
		$this->config = require APP_ROOT . '/app/config/api_management.php';
		$this->db = Database::getInstance()->getConnection();
	}

	public function searchEvents(): void
	{
		$validation = $this->validateSearch();
		if ($validation !== null) {
			$this->jsonError(422, $validation);
		}

		$limit = min((int) ($_GET['limit'] ?? 10), (int) $this->config['security']['max_results_per_request']);
		$page = min(max(1, (int) ($_GET['page'] ?? 1)), (int) $this->config['security']['max_page']);
		$offset = ($page - 1) * $limit;
		$where = ["e.approvato = 1", "e.deleted_at IS NULL"];
		$params = [];

		foreach (['regione' => 'regione_id', 'provincia' => 'provincia_id', 'comune' => 'comune_id'] as $queryKey => $column) {
			$value = filter_input(INPUT_GET, $queryKey, FILTER_VALIDATE_INT);
			if ($value) {
				$where[] = "e.{$column} = :{$column}";
				$params[':' . $column] = $value;
			}
		}
		$q = trim((string) ($_GET['q'] ?? ''));
		if ($q !== '') {
			$where[] = "(e.titolo LIKE :q_title OR e.slug LIKE :q_slug OR e.luogo LIKE :q_place)";
			$params[':q_title'] = '%' . $q . '%';
			$params[':q_slug'] = '%' . $q . '%';
			$params[':q_place'] = '%' . $q . '%';
		}
		if (!empty($_GET['from'])) {
			$where[] = "e.data_fine >= :from_date";
			$params[':from_date'] = (string) $_GET['from'];
		} else {
			$where[] = "e.data_fine >= CURDATE()";
		}
		if (!empty($_GET['to'])) {
			$where[] = "e.data_inizio <= :to_date";
			$params[':to_date'] = (string) $_GET['to'];
		}

		$sql = "
			SELECT e.id, e.titolo, e.slug, e.data_inizio, e.data_fine, e.luogo, e.immagine,
				r.nome AS regione, p.nome AS provincia, c.nome AS comune, te.nome AS tipo_evento
			FROM events e
			LEFT JOIN regioni r ON r.id = e.regione_id
			LEFT JOIN province p ON p.id = e.provincia_id
			LEFT JOIN comuni c ON c.id = e.comune_id
			LEFT JOIN tipo_evento te ON te.id = e.tipo_evento_id
			WHERE " . implode(' AND ', $where) . "
			ORDER BY e.data_inizio ASC, e.id ASC
			LIMIT :limit OFFSET :offset
		";
		$stmt = $this->db->prepare($sql);
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();

		$this->jsonSuccess([
			'items' => array_map([$this, 'eventRow'], $stmt->fetchAll(PDO::FETCH_ASSOC)),
			'meta' => [
				'limit' => $limit,
				'page' => $page,
				'max_page' => (int) $this->config['security']['max_page'],
			],
		]);
	}

	public function showEvent(array $params): void
	{
		$event = (new Event())->findBySlug((string) ($params[0] ?? ''));
		if (!$event || (int) ($event['approvato'] ?? 0) !== 1) {
			$this->jsonError(404, ['EVENT_NOT_FOUND', 'Event not found.']);
		}

		$this->jsonSuccess(['event' => $this->eventRow($event, true)]);
	}

	public function eventPhotos(array $params): void
	{
		$event = (new Event())->findBySlug((string) ($params[0] ?? ''));
		if (!$event || (int) ($event['approvato'] ?? 0) !== 1) {
			$this->jsonError(404, ['EVENT_NOT_FOUND', 'Event not found.']);
		}
		$limit = min((int) ($_GET['limit'] ?? 12), (int) $this->config['security']['max_results_per_request']);
		$page = min(max(1, (int) ($_GET['page'] ?? 1)), (int) $this->config['security']['max_page']);
		$repo = new PhotoRepository($this->db);
		$service = new PhotoService($repo);
		$photos = array_map(fn (array $photo): array => $service->decorate($photo), $repo->listPublishedByEvent((int) $event['id'], $limit, ($page - 1) * $limit));
		$items = array_map(static fn (array $photo): array => [
			'id' => (int) $photo['id'],
			'url' => '/eventi-cosplay/' . rawurlencode((string) $photo['event_slug']) . '/foto/' . (int) $photo['id'],
			'thumbnail_url' => $photo['thumbnail_url'] ?? null,
			'width' => (int) ($photo['width'] ?? 0),
			'height' => (int) ($photo['height'] ?? 0),
			'created_at' => $photo['created_at'] ?? null,
		], $photos);

		$this->jsonSuccess(['items' => $items, 'meta' => ['limit' => $limit, 'page' => $page]]);
	}

	public function locations(array $params): void
	{
		$type = (string) ($params[0] ?? 'regioni');
		if ($type === 'regioni') {
			$this->jsonSuccess(['items' => (new Regione())->getAll()]);
		}
		if ($type === 'province') {
			$regionId = filter_input(INPUT_GET, 'region_id', FILTER_VALIDATE_INT);
			if (!$regionId) {
				$this->jsonError(422, ['REGION_REQUIRED', 'region_id is required.']);
			}
			$this->jsonSuccess(['items' => (new Provincia())->getByRegioneId($regionId)]);
		}
		if ($type === 'comuni') {
			$provinceId = filter_input(INPUT_GET, 'province_id', FILTER_VALIDATE_INT);
			if (!$provinceId) {
				$this->jsonError(422, ['PROVINCE_REQUIRED', 'province_id is required.']);
			}
			$this->jsonSuccess(['items' => (new Comune())->getAll($provinceId)]);
		}

		$this->jsonError(404, ['LOCATION_ENDPOINT_NOT_FOUND', 'Location endpoint not found.']);
	}

	private function validateSearch(): ?array
	{
		$q = trim((string) ($_GET['q'] ?? ''));
		$hasLocation = filter_input(INPUT_GET, 'regione', FILTER_VALIDATE_INT) || filter_input(INPUT_GET, 'provincia', FILTER_VALIDATE_INT) || filter_input(INPUT_GET, 'comune', FILTER_VALIDATE_INT);
		$from = trim((string) ($_GET['from'] ?? ''));
		$to = trim((string) ($_GET['to'] ?? ''));
		if ($q === '' && !$hasLocation && ($from === '' || $to === '')) {
			return ['SEARCH_FILTER_REQUIRED', 'Provide q, a location filter, or a bounded date range.'];
		}
		if ($q !== '' && mb_strlen($q) < 2) {
			return ['SEARCH_TOO_SHORT', 'Search query must be at least 2 characters.'];
		}
		if ($from !== '' || $to !== '') {
			$fromDate = DateTimeImmutable::createFromFormat('Y-m-d', $from);
			$toDate = DateTimeImmutable::createFromFormat('Y-m-d', $to);
			if (!$fromDate || !$toDate) {
				return ['INVALID_DATE_RANGE', 'Use from and to dates in YYYY-MM-DD format.'];
			}
			if ($fromDate > $toDate || $fromDate->diff($toDate)->days > (int) $this->config['security']['max_search_date_range_days']) {
				return ['DATE_RANGE_TOO_WIDE', 'Date range is invalid or too wide.'];
			}
		}
		if ((int) ($_GET['page'] ?? 1) > (int) $this->config['security']['max_page']) {
			return ['PAGE_TOO_DEEP', 'Requested page is too deep.'];
		}

		return null;
	}

	private function eventRow(array $event, bool $detailed = false): array
	{
		$row = [
			'id' => (int) $event['id'],
			'title' => $event['titolo'] ?? $event['title'] ?? '',
			'slug' => $event['slug'] ?? '',
			'url' => '/eventi-cosplay/' . rawurlencode((string) ($event['slug'] ?? '')),
			'starts_at' => $event['data_inizio'] ?? null,
			'ends_at' => $event['data_fine'] ?? null,
			'place' => $event['luogo'] ?? null,
			'region' => $event['regione'] ?? $event['regione_nome'] ?? null,
			'province' => $event['provincia'] ?? $event['provincia_nome'] ?? null,
			'city' => $event['comune'] ?? $event['comune_nome'] ?? null,
			'type' => $event['tipo_evento'] ?? $event['tipo_evento_nome'] ?? null,
			'image' => $event['immagine'] ?? null,
		];
		if ($detailed) {
			$row['description'] = $event['descrizione'] ?? null;
			$row['official_site'] = $event['sito_web'] ?? null;
		}

		return $row;
	}

	private function jsonSuccess(array $data): void
	{
		(new ApiAccessService())->logSuccess(200);
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-store');
		echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit();
	}

	private function jsonError(int $status, array $error): void
	{
		(new ApiAccessService())->logSuccess($status);
		http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-store');
		echo json_encode(['success' => false, 'error' => ['code' => $error[0], 'message' => $error[1]]], JSON_UNESCAPED_UNICODE);
		exit();
	}
}
