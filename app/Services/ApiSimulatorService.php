<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Comune;
use App\Models\Event;
use App\Models\Provincia;
use App\Models\Regione;
use App\Repositories\ApiClientRepository;
use App\Repositories\PhotoRepository;
use DateTimeImmutable;
use PDO;

final class ApiSimulatorService
{
	private PDO $db;
	private array $config;

	private const ENDPOINTS = [
		'events_search' => [
			'label' => 'GET /api/v1/events/search',
			'scope' => 'events:search',
			'method' => 'simulateEventSearch',
		],
		'event_detail' => [
			'label' => 'GET /api/v1/events/{slug}',
			'scope' => 'events:read',
			'method' => 'simulateEventDetail',
		],
		'event_photos' => [
			'label' => 'GET /api/v1/events/{slug}/photos',
			'scope' => 'events:photos',
			'method' => 'simulateEventPhotos',
		],
		'locations' => [
			'label' => 'GET /api/v1/locations/{type}',
			'scope' => 'locations:read',
			'method' => 'simulateLocations',
		],
	];

	public function __construct(private ?ApiClientRepository $clients = null)
	{
		$this->clients = $clients ?? new ApiClientRepository();
		$this->db = Database::getInstance()->getConnection();
		$this->config = require APP_ROOT . '/app/config/api_management.php';
	}

	public function endpoints(): array
	{
		return self::ENDPOINTS;
	}

	public function simulate(int $clientId, string $endpointKey, array $input): array
	{
		$client = $this->clients->find($clientId);
		if (!$client) {
			return $this->response(404, ['success' => false, 'error' => ['code' => 'API_CLIENT_NOT_FOUND', 'message' => 'API client not found.']]);
		}
		if (!isset(self::ENDPOINTS[$endpointKey])) {
			return $this->response(404, ['success' => false, 'error' => ['code' => 'SIMULATOR_ENDPOINT_NOT_FOUND', 'message' => 'Simulator endpoint not found.']], $client);
		}
		$endpoint = self::ENDPOINTS[$endpointKey];
		if ((string) $client['status'] !== 'active') {
			return $this->response(403, ['success' => false, 'error' => ['code' => 'API_CLIENT_INACTIVE', 'message' => 'API client is not active.']], $client, $endpoint);
		}
		if (!empty($client['expires_at']) && strtotime((string) $client['expires_at']) < time()) {
			return $this->response(403, ['success' => false, 'error' => ['code' => 'API_KEY_EXPIRED', 'message' => 'API key expired.']], $client, $endpoint);
		}
		$scopes = $this->clients->scopes((int) $client['id']);
		if (!in_array($endpoint['scope'], $scopes, true)) {
			return $this->response(403, ['success' => false, 'error' => ['code' => 'SCOPE_MISSING', 'message' => 'Missing required scope.']], $client, $endpoint, $scopes);
		}

		$method = $endpoint['method'];
		$result = $this->{$method}($input);

		return $this->response($result['status'], $result['body'], $client, $endpoint, $scopes, $result['request'] ?? []);
	}

	private function simulateEventSearch(array $input): array
	{
		$validation = $this->validateSearch($input);
		if ($validation !== null) {
			return ['status' => 422, 'body' => ['success' => false, 'error' => ['code' => $validation[0], 'message' => $validation[1]]]];
		}
		$limit = min(max(1, (int) ($input['limit'] ?? 10)), (int) $this->config['security']['max_results_per_request']);
		$page = min(max(1, (int) ($input['page'] ?? 1)), (int) $this->config['security']['max_page']);
		$offset = ($page - 1) * $limit;
		$where = ["e.approvato = 1", "e.deleted_at IS NULL"];
		$params = [];
		foreach (['regione' => 'regione_id', 'provincia' => 'provincia_id', 'comune' => 'comune_id'] as $queryKey => $column) {
			$value = filter_var($input[$queryKey] ?? null, FILTER_VALIDATE_INT);
			if ($value) {
				$where[] = "e.{$column} = :{$column}";
				$params[':' . $column] = $value;
			}
		}
		$q = trim((string) ($input['q'] ?? ''));
		if ($q !== '') {
			$where[] = "(e.titolo LIKE :q_title OR e.slug LIKE :q_slug OR e.luogo LIKE :q_place)";
			$params[':q_title'] = '%' . $q . '%';
			$params[':q_slug'] = '%' . $q . '%';
			$params[':q_place'] = '%' . $q . '%';
		}
		if (!empty($input['from'])) {
			$where[] = "e.data_fine >= :from_date";
			$params[':from_date'] = (string) $input['from'];
		} else {
			$where[] = "e.data_fine >= CURDATE()";
		}
		if (!empty($input['to'])) {
			$where[] = "e.data_inizio <= :to_date";
			$params[':to_date'] = (string) $input['to'];
		}
		$stmt = $this->db->prepare("
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
		");
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();

		return [
			'status' => 200,
			'request' => ['limit' => $limit, 'page' => $page],
			'body' => ['success' => true, 'data' => [
				'items' => array_map([$this, 'eventRow'], $stmt->fetchAll(PDO::FETCH_ASSOC)),
				'meta' => ['limit' => $limit, 'page' => $page, 'max_page' => (int) $this->config['security']['max_page']],
			]],
		];
	}

	private function simulateEventDetail(array $input): array
	{
		$event = (new Event())->findBySlug(trim((string) ($input['slug'] ?? '')));
		if (!$event || (int) ($event['approvato'] ?? 0) !== 1) {
			return ['status' => 404, 'body' => ['success' => false, 'error' => ['code' => 'EVENT_NOT_FOUND', 'message' => 'Event not found.']]];
		}

		return ['status' => 200, 'body' => ['success' => true, 'data' => ['event' => $this->eventRow($event, true)]]];
	}

	private function simulateEventPhotos(array $input): array
	{
		$event = (new Event())->findBySlug(trim((string) ($input['slug'] ?? '')));
		if (!$event || (int) ($event['approvato'] ?? 0) !== 1) {
			return ['status' => 404, 'body' => ['success' => false, 'error' => ['code' => 'EVENT_NOT_FOUND', 'message' => 'Event not found.']]];
		}
		$limit = min(max(1, (int) ($input['limit'] ?? 12)), (int) $this->config['security']['max_results_per_request']);
		$page = min(max(1, (int) ($input['page'] ?? 1)), (int) $this->config['security']['max_page']);
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

		return ['status' => 200, 'request' => ['limit' => $limit, 'page' => $page], 'body' => ['success' => true, 'data' => ['items' => $items, 'meta' => ['limit' => $limit, 'page' => $page]]]];
	}

	private function simulateLocations(array $input): array
	{
		$type = (string) ($input['location_type'] ?? 'regioni');
		if ($type === 'regioni') {
			return ['status' => 200, 'body' => ['success' => true, 'data' => ['items' => (new Regione())->getAll()]]];
		}
		if ($type === 'province') {
			$regionId = filter_var($input['region_id'] ?? null, FILTER_VALIDATE_INT);
			if (!$regionId) {
				return ['status' => 422, 'body' => ['success' => false, 'error' => ['code' => 'REGION_REQUIRED', 'message' => 'region_id is required.']]];
			}
			return ['status' => 200, 'body' => ['success' => true, 'data' => ['items' => (new Provincia())->getByRegioneId($regionId)]]];
		}
		if ($type === 'comuni') {
			$provinceId = filter_var($input['province_id'] ?? null, FILTER_VALIDATE_INT);
			if (!$provinceId) {
				return ['status' => 422, 'body' => ['success' => false, 'error' => ['code' => 'PROVINCE_REQUIRED', 'message' => 'province_id is required.']]];
			}
			return ['status' => 200, 'body' => ['success' => true, 'data' => ['items' => (new Comune())->getAll($provinceId)]]];
		}

		return ['status' => 404, 'body' => ['success' => false, 'error' => ['code' => 'LOCATION_ENDPOINT_NOT_FOUND', 'message' => 'Location endpoint not found.']]];
	}

	private function validateSearch(array $input): ?array
	{
		$q = trim((string) ($input['q'] ?? ''));
		$hasLocation = filter_var($input['regione'] ?? null, FILTER_VALIDATE_INT) || filter_var($input['provincia'] ?? null, FILTER_VALIDATE_INT) || filter_var($input['comune'] ?? null, FILTER_VALIDATE_INT);
		$from = trim((string) ($input['from'] ?? ''));
		$to = trim((string) ($input['to'] ?? ''));
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
		if ((int) ($input['page'] ?? 1) > (int) $this->config['security']['max_page']) {
			return ['PAGE_TOO_DEEP', 'Requested page is too deep.'];
		}

		return null;
	}

	private function eventRow(array $event, bool $detailed = false): array
	{
		$row = [
			'id' => (int) $event['id'],
			'title' => $event['titolo'] ?? '',
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

	private function response(int $status, array $body, ?array $client = null, ?array $endpoint = null, array $scopes = [], array $request = []): array
	{
		return [
			'status' => $status,
			'client' => $client,
			'endpoint' => $endpoint,
			'scopes' => $scopes,
			'request' => $request,
			'body' => $body,
			'json' => json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		];
	}
}
