<?php
namespace App\Models;
use App\Services\ImageService;
use PDO;
use DateTime;
use function var_dump;

class Event extends BaseModel
{
	protected $table = 'events';
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Genera uno slug unico da un titolo.
	 * @param string $title Il titolo da cui generare lo slug.
	 * @param int|null $excludeId ID dell'evento da escludere nella ricerca di duplicati (per gli aggiornamenti).
	 * @return string Lo slug generato.
	 */
	public function generateUniqueSlug(string $title, ?int $excludeId = null): string
	{
		$baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
		$slug = $baseSlug;
		$counter = 1;

		while (true) {
			$query = "SELECT COUNT(*) FROM " . $this->table . " WHERE slug = :slug";
			if ($excludeId !== null) {
				$query .= " AND id != :exclude_id";
			}
			$stmt = $this->db->prepare($query);
			$stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
			if ($excludeId !== null) {
				$stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
			}
			$stmt->execute();
			$count = $stmt->fetchColumn();

			if ($count == 0) {
				break; // Lo slug è unico
			}

			$slug = $baseSlug . '-' . $counter++;
		}
		return $slug;
	}

	/**
	 * Recupera gli eventi approvati, ordinati per data di inizio.
	 * Ora accetta parametri di filtro per regione, provincia e comune.
	 * @param int|null $regioneId ID della regione per filtrare.
	 * @param int|null $provinciaId ID della provincia per filtrare.
	 * @param int|null $comuneId ID del comune per filtrare.
	 * @return array Array di eventi.
	 */
	public function getApprovedEvents(?int $regioneId = null, ?int $provinciaId = null, ?int $comuneId = null, $limit = 0): array
	{
		$query = "SELECT e.*, r.nome as regione_nome, p.nome as provincia_nome, c.nome as comune_nome, te.nome as tipo_evento_nome, r.slug as regione_slug
                  FROM " . $this->table . " e
                  LEFT JOIN regioni r ON e.regione_id = r.id
                  LEFT JOIN province p ON e.provincia_id = p.id
                  LEFT JOIN comuni c ON e.comune_id = c.id
                  LEFT JOIN tipo_evento te ON e.tipo_evento_id = te.id
                  WHERE e.approvato = 1 AND e.data_fine >= CURDATE()"; // Solo eventi approvati e non scaduti

		$params = [];

		if ($regioneId !== null) {
			$query .= " AND e.regione_id = :regione_id";
			$params[':regione_id'] = $regioneId;
		}
		if ($provinciaId !== null) {
			$query .= " AND e.provincia_id = :provincia_id";
			$params[':provincia_id'] = $provinciaId;
		}
		if ($comuneId !== null) {
			$query .= " AND e.comune_id = :comune_id";
			$params[':comune_id'] = $comuneId;
		}

		$query .= " ORDER BY e.data_inizio ASC, e.data_fine ASC";
		if($limit > 0){
			$query .= " LIMIT " . $limit;
		}
		$stmt = $this->db->prepare($query);
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value);
		}
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function getApprovedEventsApi(
		?int $regioneId = null,
		?int $provinciaId = null,
		?int $comuneId = null,
		?int $limit = 50
	): array {
		$query = "
        SELECT 
            e.id,
            e.titolo,
            e.slug,
            e.data_inizio,
            e.data_fine,
            e.luogo,
            r.nome AS regione,
            p.nome AS provincia,
            c.nome AS comune,
            te.nome AS tipo_evento,
            e.immagine
        FROM {$this->table} e
        LEFT JOIN regioni r ON e.regione_id = r.id
        LEFT JOIN province p ON e.provincia_id = p.id
        LEFT JOIN comuni c ON e.comune_id = c.id
        LEFT JOIN tipo_evento te ON e.tipo_evento_id = te.id
        WHERE e.approvato = 1
          AND e.data_fine >= CURDATE()
    ";

		$params = [];

		if ($regioneId !== null) {
			$query .= " AND e.regione_id = :regione_id";
			$params['regione_id'] = $regioneId;
		}
		if ($provinciaId !== null) {
			$query .= " AND e.provincia_id = :provincia_id";
			$params['provincia_id'] = $provinciaId;
		}
		if ($comuneId !== null) {
			$query .= " AND e.comune_id = :comune_id";
			$params['comune_id'] = $comuneId;
		}

		$query .= " ORDER BY e.data_inizio ASC, e.data_fine ASC";
		$query .= " LIMIT :limit";

		$stmt = $this->db->prepare($query);

		foreach ($params as $key => $value) {
			$stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
		}

		$stmt->bindValue(':limit', max(1, min($limit, 100)), PDO::PARAM_INT);

		$stmt->execute();

		return array_map(function ($row) {
			return [
				'id'          => (int)$row['id'],
				'nome'        => $row['titolo'],
				'slug'        => $row['slug'],
				'inizio'      => $row['data_inizio'],
				'fine'        => $row['data_fine'],
				'indirizzo'   => $row['luogo'],
				'regione'     => $row['regione'],
				'provincia'   => $row['provincia'],
				'comune'      => $row['comune'],
				'tipo_evento' => $row['tipo_evento'],
				'url'         => '/eventi-cosplay/' . $row['slug'],
				'immagine'         => $row['immagine']
			];
		}, $stmt->fetchAll(PDO::FETCH_ASSOC));
	}


	/**
	 * Recupera tutti gli eventi, inclusi quelli non approvati (per admin).
	 * @return array Array di eventi.
	 */
	public function getAllEvents(): array
	{
		$stmt = $this->db->query("SELECT e.*, r.nome as regione_nome, p.nome as provincia_nome, c.nome as comune_nome, te.nome as tipo_evento_nome
                                  FROM " . $this->table . " e
                                  LEFT JOIN regioni r ON e.regione_id = r.id
                                  LEFT JOIN province p ON e.provincia_id = p.id
                                  LEFT JOIN comuni c ON e.comune_id = c.id
                                  LEFT JOIN tipo_evento te ON e.tipo_evento_id = te.id
                                  ORDER BY e.created_at DESC");
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Recupera gli eventi in attesa di approvazione (per admin).
	 * @return array Array di eventi.
	 */
	public function getPendingEvents(): array
	{
		$stmt = $this->db->query("SELECT e.*, r.nome as regione_nome, p.nome as provincia_nome, c.nome as comune_nome, te.nome as tipo_evento_nome
                                  FROM " . $this->table . " e
                                  LEFT JOIN regioni r ON e.regione_id = r.id
                                  LEFT JOIN province p ON e.provincia_id = p.id
                                  LEFT JOIN comuni c ON e.comune_id = c.id
                                  LEFT JOIN tipo_evento te ON e.tipo_evento_id = te.id
                                  WHERE e.approvato = 0 ORDER BY e.created_at DESC");
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Trova un evento per ID.
	 * @param int $id L'ID dell'evento.
	 * @return array|null L'evento come array associativo o null se non trovato.
	 */
	public function find($id)
	{
		$stmt = $this->db->prepare("SELECT e.*, r.nome as regione_nome, p.nome as provincia_nome, c.nome as comune_nome, te.nome as tipo_evento_nome
                                  FROM " . $this->table . " e
                                  LEFT JOIN regioni r ON e.regione_id = r.id
                                  LEFT JOIN province p ON e.provincia_id = p.id
                                  LEFT JOIN comuni c ON e.comune_id = c.id
                                  LEFT JOIN tipo_evento te ON e.tipo_evento_id = te.id
                                  WHERE e.id = :id");

		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Trova un evento per slug.
	 * @param string $slug Lo slug dell'evento.
	 * @return array|null L'evento come array associativo o null se non trovato.
	 */
	public function findBySlug(string $slug)
	{
		$stmt = $this->db->prepare("SELECT e.*, r.nome as regione_nome, p.nome as provincia_nome, c.nome as comune_nome, te.nome as tipo_evento_nome
                                  FROM " . $this->table . " e
                                  LEFT JOIN regioni r ON e.regione_id = r.id
                                  LEFT JOIN province p ON e.provincia_id = p.id
                                  LEFT JOIN comuni c ON e.comune_id = c.id
                                  LEFT JOIN tipo_evento te ON e.tipo_evento_id = te.id
                                  WHERE e.slug = :slug");
		$stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
	public function findBySlugApi(string $slug): ?array
	{
		$sql = "
        SELECT 
            e.id,
            e.nome,
            e.slug,
            e.descrizione,
            e.data_inizio,
            e.data_fine,
            e.indirizzo,
            e.sito_web,
            r.nome AS regione,
            p.nome AS provincia,
            c.nome AS comune,
            te.nome AS tipo_evento
        FROM {$this->table} e
        LEFT JOIN regioni r ON e.regione_id = r.id
        LEFT JOIN province p ON e.provincia_id = p.id
        LEFT JOIN comuni c ON e.comune_id = c.id
        LEFT JOIN tipo_evento te ON e.tipo_evento_id = te.id
        WHERE e.slug = :slug
          AND e.approvato = 1
        LIMIT 1
    ";

		$stmt = $this->db->prepare($sql);
		$stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
		$stmt->execute();

		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) {
			return null;
		}

		return [
			'id'          => (int)$row['id'],
			'nome'        => $row['nome'],
			'slug'        => $row['slug'],
			'descrizione' => $row['descrizione'],
			'inizio'      => $row['data_inizio'],
			'fine'        => $row['data_fine'],
			'indirizzo'   => $row['indirizzo'],
			'sito_web'    => $row['sito_web'],
			'regione'     => $row['regione'],
			'provincia'   => $row['provincia'],
			'comune'      => $row['comune'],
			'tipo_evento' => $row['tipo_evento'],
			'url'         => '/eventi-cosplay/' . $row['slug']
		];
	}


	/**
	 * Crea un nuovo evento.
	 * @param array $data Dati dell'evento.
	 * @return bool True se l'evento è stato creato con successo, false altrimenti.
	 */
	public function create(array $data): int
	{
		$slug = $this->generateUniqueSlug($data['titolo']);

		$query = "INSERT INTO " . $this->table . " (
		titolo, descrizione, data_inizio, data_fine, luogo,
		regione_id, provincia_id, comune_id, latitudine, longitudine,
		sito_web, social_facebook, social_twitter, social_instagram,
		social_tiktok, social_youtube, tipo_evento_id, immagine, approvato, slug, event_size, is_paid, has_cosplay_contest
	) VALUES (
		:titolo, :descrizione, :data_inizio, :data_fine, :luogo,
		:regione_id, :provincia_id, :comune_id, :latitudine, :longitudine,
		:sito_web, :social_facebook, :social_twitter, :social_instagram,
		:social_tiktok, :social_youtube, :tipo_evento_id, :immagine, :approvato, :slug, :event_size, :is_paid, :has_cosplay_contest
	)";

		$stmt = $this->db->prepare($query);

		$stmt->bindValue(':titolo', $data['titolo']);
		$stmt->bindValue(':descrizione', $data['descrizione']);
		$stmt->bindValue(':data_inizio', $data['data_inizio']);
		$stmt->bindValue(':data_fine', $data['data_fine'] ?: null);
		$stmt->bindValue(':luogo', $data['luogo']);
		$stmt->bindValue(':regione_id', $data['regione_id'], \PDO::PARAM_INT);
		$stmt->bindValue(':provincia_id', $data['provincia_id'], \PDO::PARAM_INT);
		$stmt->bindValue(':comune_id', $data['comune_id'], \PDO::PARAM_INT);
		$stmt->bindValue(':latitudine', $data['latitudine'] ?: null);
		$stmt->bindValue(':longitudine', $data['longitudine'] ?: null);
		$stmt->bindValue(':sito_web', $data['sito_web'] ?: null);
		$stmt->bindValue(':social_facebook', $data['social_facebook'] ?: null);
		$stmt->bindValue(':social_twitter', $data['social_twitter'] ?: null);
		$stmt->bindValue(':social_instagram', $data['social_instagram'] ?: null);
		$stmt->bindValue(':social_tiktok', $data['social_tiktok'] ?: null);
		$stmt->bindValue(':social_youtube', $data['social_youtube'] ?: null);
		$stmt->bindValue(':tipo_evento_id', $data['tipo_evento_id'], \PDO::PARAM_INT);
		$stmt->bindValue(':immagine', null); // 🔥 IMPORTANTISSIMO: non più usata
		$stmt->bindValue(':approvato', $data['approvato'], \PDO::PARAM_INT);
		$stmt->bindValue(':slug', $slug);
		$stmt->bindValue(':event_size', $data['event_size'] , \PDO::PARAM_INT);
		$stmt->bindValue(':is_paid', $data['is_paid'], \PDO::PARAM_INT);
		$stmt->bindValue(':has_cosplay_contest', $data['has_cosplay_contest'], \PDO::PARAM_INT);

		$stmt->execute();


		return (int) $this->db->lastInsertId();
	}

	/**
	 * Aggiorna un evento esistente.
	 * @param int $id L'ID dell'evento da aggiornare.
	 * @param array $data Dati dell'evento da aggiornare.
	 * @return bool True se l'evento è stato aggiornato con successo, false altrimenti.
	 */
	public function update(int $id, array $data): bool
	{
		// Recupera lo slug esistente o ne genera uno nuovo se il titolo è cambiato
		$currentEvent = $this->find($id);
		$slug = $currentEvent['slug']; // Mantiene lo slug esistente di default
		if ($currentEvent['titolo'] !== $data['titolo']) {
			$slug = $this->generateUniqueSlug($data['titolo'], $id);
		}

		$query = "UPDATE " . $this->table . " SET
                      titolo = :titolo,
                      descrizione = :descrizione,
                      data_inizio = :data_inizio,
                      data_fine = :data_fine,
                      luogo = :luogo,
                      regione_id = :regione_id,
                      provincia_id = :provincia_id,
                      comune_id = :comune_id,
                      latitudine = :latitudine,
                      longitudine = :longitudine,
                      sito_web = :sito_web,
                      social_facebook = :social_facebook,
                      social_twitter = :social_twitter,
                      social_instagram = :social_instagram,
                      social_tiktok = :social_tiktok,
                      social_youtube = :social_youtube,
                      tipo_evento_id = :tipo_evento_id,
                      immagine = :immagine,
                      approvato = :approvato,
                      slug = :slug,
                      event_size = :event_size,
                      is_paid = :is_paid,
                      has_cosplay_contest = :has_cosplay_contest,
                      updated_at = NOW()
                  WHERE id = :id";

		$stmt = $this->db->prepare($query);

		$stmt->bindValue(':titolo', $data['titolo']);
		$stmt->bindValue(':descrizione', $data['descrizione']);
		$stmt->bindValue(':data_inizio', $data['data_inizio']);
		$stmt->bindValue(':data_fine', $data['data_fine'] ?: null);
		$stmt->bindValue(':luogo', $data['luogo']);
		$stmt->bindValue(':regione_id', $data['regione_id'], PDO::PARAM_INT);
		$stmt->bindValue(':provincia_id', $data['provincia_id'], PDO::PARAM_INT);
		$stmt->bindValue(':comune_id', $data['comune_id'], PDO::PARAM_INT);
		$stmt->bindValue(':latitudine', $data['latitudine'] ?: null);
		$stmt->bindValue(':longitudine', $data['longitudine'] ?: null);
		$stmt->bindValue(':sito_web', $data['sito_web'] ?: null);
		$stmt->bindValue(':social_facebook', $data['social_facebook'] ?: null);
		$stmt->bindValue(':social_twitter', $data['social_twitter'] ?: null);
		$stmt->bindValue(':social_instagram', $data['social_instagram'] ?: null);
		$stmt->bindValue(':social_tiktok', $data['social_tiktok'] ?: null);
		$stmt->bindValue(':social_youtube', $data['social_youtube'] ?: null);
		$stmt->bindValue(':tipo_evento_id', $data['tipo_evento_id'], PDO::PARAM_INT);
		$stmt->bindValue(':immagine', $data['immagine'] ?: null);
		$stmt->bindValue(':approvato', $data['approvato'], PDO::PARAM_INT);
		$stmt->bindValue(':slug', $slug); // Associa lo slug (nuovo o esistente)
		$stmt->bindValue(':event_size',$data['event_size']); // Associa lo slug (nuovo o esistente)
		$stmt->bindValue(':is_paid',$data['is_paid']); // Associa lo slug (nuovo o esistente)
		$stmt->bindValue(':has_cosplay_contest',$data['has_cosplay_contest']); // Associa lo slug (nuovo o esistente)
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);



		return $stmt->execute();
	}

	/**
	 * Elimina un evento per ID.
	 * @param int $id L'ID dell'evento da eliminare.
	 * @return bool True se l'evento è stato eliminato con successo, false altrimenti.
	 */
	public function delete(int $id): bool
	{
		$stmt = $this->db->prepare("DELETE FROM " . $this->table . " WHERE id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		return $stmt->execute();
	}

	/**
	 * Approva un evento.
	 * @param int $id L'ID dell'evento da approvare.
	 * @return bool True se l'evento è stato approvato con successo, false altrimenti.
	 */
	public function approveEvent(int $id): bool
	{
		$stmt = $this->db->prepare("UPDATE " . $this->table . " SET approvato = 1, updated_at = NOW() WHERE id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		return $stmt->execute();
	}

	public function getEventsByDateRange(DateTime $from, DateTime $to, ?int $regioneId = null): array
	{
		$query = "SELECT e.*, r.nome as regione_nome, p.nome as provincia_nome, c.nome as comune_nome
              FROM " . $this->table . " e
              LEFT JOIN regioni r ON e.regione_id = r.id
              LEFT JOIN province p ON e.provincia_id = p.id
              LEFT JOIN comuni c ON e.comune_id = c.id
              WHERE e.approvato = 1
              AND e.data_fine >= :from
              AND e.data_inizio <= :to";

		$params = [
			':from' => $from->format('Y-m-d'),
			':to' => $to->format('Y-m-d'),
		];

		if ($regioneId !== null) {
			$query .= " AND e.regione_id = :regione_id";
			$params[':regione_id'] = $regioneId;
		}

		$query .= " ORDER BY e.data_inizio ASC";

		$stmt = $this->db->prepare($query);
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value);
		}

		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function buildWeekendSections(array $events): array
	{
		$used = [];

		/* 🔥 TOP 3 (già ordinati per score) */
		$top3 = array_slice($events, 0, 3);
		foreach ($top3 as $e) {
			$used[$e['id']] = true;
		}

		/* 🏆 BIG */
		$big = [];
		foreach ($events as $e) {
			if (isset($used[$e['id']])) continue;

			if ($e['event_size'] >= 3) {
				$big[] = $e;
				$used[$e['id']] = true;
			}
		}

		/* 🆕 NEW */
		$new = [];
		foreach ($events as $e) {
			if (isset($used[$e['id']])) continue;

			if (strtotime($e['created_at']) > strtotime('-30 days')) {
				$new[] = $e;
				$used[$e['id']] = true;
			}
		}

		/* 📋 FULL */
		$full = [];
		foreach ($events as $e) {
			if (!isset($used[$e['id']])) {
				$full[] = $e;
			}
		}

		return [
			'top3' => $top3,
			'big' => $big,
			'new' => $new,
			'all' => $full
		];
	}
	public function getEventsByDateRangeWithScore($start, $end): array
	{
		$query = "
        SELECT 
            e.*,

            COALESCE(
                SUM(CASE WHEN ev.view_date >= CURDATE() - INTERVAL 3 DAY THEN ev.views ELSE 0 END) * 3 +
                SUM(CASE WHEN ev.view_date >= CURDATE() - INTERVAL 7 DAY THEN ev.views ELSE 0 END) * 2
            , 0) AS score

        FROM {$this->table} e

        LEFT JOIN event_views ev ON ev.event_id = e.id

        WHERE 
            e.approvato = 1
            AND e.data_inizio <= :end
            AND e.data_fine >= :start

        GROUP BY e.id

        ORDER BY score DESC, e.data_inizio ASC
    ";

		$stmt = $this->db->prepare($query);
		$stmt->bindValue(':start', $start->format('Y-m-d'));
		$stmt->bindValue(':end', $end->format('Y-m-d'));
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function getSimilarEvents(int $eventId, int $regioneId, int $tipoEventoId, int $limit = 6): array
	{
		$query = "
        SELECT 
            e.*,

            COALESCE(
                SUM(CASE WHEN ev.view_date >= CURDATE() - INTERVAL 3 DAY THEN ev.views ELSE 0 END) * 3 +
                SUM(CASE WHEN ev.view_date >= CURDATE() - INTERVAL 7 DAY THEN ev.views ELSE 0 END) * 2
            , 0) AS score

        FROM {$this->table} e

        LEFT JOIN event_views ev ON ev.event_id = e.id

        WHERE 
            e.approvato = 1
            AND e.id != :event_id
            AND e.data_fine >= CURDATE()

            AND (
                e.regione_id = :regione_id
                OR e.tipo_evento_id = :tipo_evento_id
            )

        GROUP BY e.id

        ORDER BY score DESC, e.data_inizio ASC

        LIMIT :limit
    ";

		$stmt = $this->db->prepare($query);
		$stmt->bindValue(':event_id', $eventId, PDO::PARAM_INT);
		$stmt->bindValue(':regione_id', $regioneId, PDO::PARAM_INT);
		$stmt->bindValue(':tipo_evento_id', $tipoEventoId, PDO::PARAM_INT);
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getEventsWithTrending(DateTime $start, DateTime $end): array
	{
		$query = "
        SELECT 
            e.*,
            r.nome as regione_nome,
            p.nome as provincia_nome,
            c.nome as comune_nome,
            te.nome as tipo_evento_nome,

            COALESCE(tv.views, 0) as today_views,
            COALESCE(yv.views, 0) as yesterday_views

        FROM {$this->table} e

        LEFT JOIN regioni r ON e.regione_id = r.id
        LEFT JOIN province p ON e.provincia_id = p.id
        LEFT JOIN comuni c ON e.comune_id = c.id
        LEFT JOIN tipo_evento te ON e.tipo_evento_id = te.id

        /* views oggi */
        LEFT JOIN event_views tv 
            ON e.id = tv.event_id 
            AND tv.view_date = CURDATE()

        /* views ieri */
        LEFT JOIN event_views yv 
            ON e.id = yv.event_id 
            AND yv.view_date = CURDATE() - INTERVAL 1 DAY

        WHERE e.approvato = 1
        AND e.data_fine >= :start
        AND e.data_inizio <= :end

        ORDER BY e.data_inizio ASC
    ";

		$stmt = $this->db->prepare($query);
		$stmt->bindValue(':start', $start->format('Y-m-d'));
		$stmt->bindValue(':end', $end->format('Y-m-d'));
		$stmt->execute();

		$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// 🔥 CALCOLO TRENDING
		foreach ($events as &$e) {

			$today = (int)($e['today_views'] ?? 0);
			$yesterday = (int)($e['yesterday_views'] ?? 0);

			// score semplice e robusto
			$e['trending_score'] = max(0, $today - $yesterday);

			// fallback: se non cresce ma ha views oggi
			if ($e['trending_score'] === 0 && $today > 0) {
				$e['trending_score'] = $today;
			}

			// crescita percentuale (più precisa)
			if ($yesterday > 0) {
				$growth = ($today - $yesterday) / $yesterday;
			} else {
				$growth = $today > 0 ? 1 : 0;
			}

			// puoi scegliere quale usare 👇

			// 👉 VERSIONE 1 (consigliata)
			$e['trending_score'] = round($growth * 100);

			// 👉 VERSIONE 2 (alternativa più stabile)
			// $e['trending_score'] = $diff;

			// sicurezza
			if ($e['trending_score'] < 0) {
				$e['trending_score'] = 0;
			}
		}

		return $events;
	}
	public function getCoverImage(int $eventId, ImageService $imageService,  string $preset = 'medium'): ?array
	{
		$images = $this->getImages($eventId, $preset, [
			'primary' => true,
			'type' => 'cover'
		]);

		return isset($images[0])
			? $imageService->resolvePreset($images[0], $preset)
			: null;
	}

	public function getImages(int $eventId, ?string $preset = 'medium', array $filters = []): array
	{
		$sql = "
        SELECT 
            i.id AS image_id,
            i.alt_text,
            ei.type,
            ei.is_primary,
            ei.sort_order,
            iv.preset,
            iv.path,
            iv.width,
            iv.height
        FROM event_images ei
        JOIN images i ON i.id = ei.image_id
        JOIN image_variants iv ON iv.image_id = i.id
        WHERE ei.event_id = :event_id
    ";

		$params = [
			'event_id' => $eventId
		];

		// 🔥 filtri dinamici
		if (isset($filters['primary'])) {
			$sql .= " AND ei.is_primary = :primary";
			$params['primary'] = (int)$filters['primary'];
		}

		if (isset($filters['type'])) {
			$sql .= " AND ei.type = :type";
			$params['type'] = $filters['type'];
		}

		$sql .= " ORDER BY ei.sort_order ASC, ei.id ASC";

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);

		$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

		// 🔥 normalizzazione + filtro preset
		$images = [];

		foreach ($rows as $row) {
			$id = $row['image_id'];

			if (!isset($images[$id])) {
				$images[$id] = [
					'id' => $id,
					'alt_text' => $row['alt_text'],
					'type' => $row['type'],
					'is_primary' => (bool)$row['is_primary'],
					'variants' => []
				];
			}

			$images[$id]['variants'][$row['preset']] = [
				'path' => $row['path'],
				'width' => $row['width'],
				'height' => $row['height']
			];
		}

		return array_values($images);
	}
}
