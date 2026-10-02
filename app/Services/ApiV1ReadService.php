<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Comune;
use App\Models\Event;
use App\Models\Provincia;
use App\Models\Regione;
use App\Repositories\PhotoRepository;
use DateTimeImmutable;
use PDO;

final class ApiV1ReadService
{
	private array $config;
	private PDO $db;

	public function __construct()
	{
		$this->config = require APP_ROOT . '/app/config/api_management.php';
		$this->db = Database::getInstance()->getConnection();
	}

	public function searchEvents(array $input): array
	{
		$validation = $this->validateSearch($input);
		if ($validation !== null) {
			return $this->error(422, $validation[0], $validation[1]);
		}

		$limit = min(max(1, (int) ($input['limit'] ?? 10)), (int) $this->config['security']['max_results_per_request']);
		$page = min(max(1, (int) ($input['page'] ?? 1)), (int) $this->config['security']['max_page']);
		$offset = ($page - 1) * $limit;
		$where = ["e.approvato = 1", "e.deleted_at IS NULL"];
		$params = [];

		foreach (['regione' => 'regione_id', 'provincia' => 'provincia_id', 'comune' => 'comune_id'] as $inputKey => $column) {
			$value = filter_var($input[$inputKey] ?? null, FILTER_VALIDATE_INT);
			if ($value) {
				$where[] = "e.{$column} = :{$column}";
				$params[':' . $column] = (int) $value;
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

		return $this->success([
			'items' => array_map([$this, 'eventRow'], $stmt->fetchAll(PDO::FETCH_ASSOC)),
			'meta' => [
				'limit' => $limit,
				'page' => $page,
				'max_page' => (int) $this->config['security']['max_page'],
			],
		]);
	}

	public function getEvent(string $slug): array
	{
		$event = (new Event())->findBySlug($slug);
		if (!$event || (int) ($event['approvato'] ?? 0) !== 1) {
			return $this->error(404, 'EVENT_NOT_FOUND', 'Event not found.');
		}

		return $this->success(['event' => $this->eventRow($event, true)]);
	}

	public function getEventPhotos(string $slug, array $input = []): array
	{
		$event = (new Event())->findBySlug($slug);
		if (!$event || (int) ($event['approvato'] ?? 0) !== 1) {
			return $this->error(404, 'EVENT_NOT_FOUND', 'Event not found.');
		}

		$limit = min(max(1, (int) ($input['limit'] ?? 12)), (int) $this->config['security']['max_results_per_request']);
		$page = min(max(1, (int) ($input['page'] ?? 1)), (int) $this->config['security']['max_page']);
		$repo = new PhotoRepository($this->db);
		$service = new PhotoService($repo);
		$photos = array_map(fn (array $photo): array => $service->decorate($photo), $repo->listPublishedByEvent((int) $event['id'], $limit, ($page - 1) * $limit));
		$items = array_map(static fn (array $photo): array => [
			'id' => (int) $photo['id'],
			'url' => self::absoluteUrl('/eventi-cosplay/' . rawurlencode((string) $photo['event_slug']) . '/foto/' . (int) $photo['id']),
			'thumbnail_url' => isset($photo['thumbnail_url']) ? self::absoluteUrl((string) $photo['thumbnail_url']) : null,
			'width' => (int) ($photo['width'] ?? 0),
			'height' => (int) ($photo['height'] ?? 0),
			'created_at' => $photo['created_at'] ?? null,
		], $photos);

		return $this->success(['items' => $items, 'meta' => ['limit' => $limit, 'page' => $page]]);
	}

	public function getLocations(string $type, array $input = []): array
	{
		if ($type === 'regioni') {
			return $this->success(['items' => (new Regione())->getAll()]);
		}
		if ($type === 'province') {
			$regionId = filter_var($input['region_id'] ?? null, FILTER_VALIDATE_INT);
			if (!$regionId) {
				return $this->error(422, 'REGION_REQUIRED', 'region_id is required.');
			}
			return $this->success(['items' => (new Provincia())->getByRegioneId((int) $regionId)]);
		}
		if ($type === 'comuni') {
			$provinceId = filter_var($input['province_id'] ?? null, FILTER_VALIDATE_INT);
			if (!$provinceId) {
				return $this->error(422, 'PROVINCE_REQUIRED', 'province_id is required.');
			}
			return $this->success(['items' => (new Comune())->getAll((int) $provinceId)]);
		}

		return $this->error(404, 'LOCATION_ENDPOINT_NOT_FOUND', 'Location endpoint not found.');
	}

	private function validateSearch(array $input): ?array
	{
		$q = trim((string) ($input['q'] ?? ''));
		$hasLocation = filter_var($input['regione'] ?? null, FILTER_VALIDATE_INT)
			|| filter_var($input['provincia'] ?? null, FILTER_VALIDATE_INT)
			|| filter_var($input['comune'] ?? null, FILTER_VALIDATE_INT);
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
			'title' => $event['titolo'] ?? $event['title'] ?? '',
			'slug' => $event['slug'] ?? '',
			'url' => self::absoluteUrl('/eventi-cosplay/' . rawurlencode((string) ($event['slug'] ?? ''))),
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
			$row['latitude'] = $event['latitudine'] ?? null;
			$row['longitude'] = $event['longitudine'] ?? null;
			$row['social'] = [
				'facebook' => $event['social_facebook'] ?? null,
				'twitter' => $event['social_twitter'] ?? null,
				'instagram' => $event['social_instagram'] ?? null,
				'tiktok' => $event['social_tiktok'] ?? null,
				'youtube' => $event['social_youtube'] ?? null,
			];
		}

		return $row;
	}

	private function success(array $data): array
	{
		return ['success' => true, 'data' => $data];
	}

	private function error(int $status, string $code, string $message): array
	{
		return ['success' => false, 'status' => $status, 'error' => ['code' => $code, 'message' => $message]];
	}

	private static function absoluteUrl(string $path): string
	{
		if (preg_match('#^https?://#i', $path) === 1) {
			return $path;
		}

		return rtrim(defined('URL_ROOT_SITE') ? URL_ROOT_SITE : 'https://www.italiancosplay.it', '/') . '/' . ltrim($path, '/');
	}
}
