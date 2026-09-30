<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use function var_dump;

class EventRepository
{
	protected PDO $db;


	public function __construct()
	{
		$this->db = Database::getInstance()
			->getConnection();
	}


	public function getAll(): array
	{
		return $this->db
			->query("SELECT * FROM events WHERE deleted_at IS NULL")
			->fetchAll(PDO::FETCH_ASSOC);
	}


	public function getAdminList(
		int $page = 1,
		int $perPage = 25,
		string $search = '',
		array $filters = [],
		?string $sort = null,
		string $direction = 'desc'
	): array {

		$where = ['e.deleted_at IS NULL'];

		$params = [];


		/**
		 * Ricerca generale
		 */
		if ($search !== '') {

			$where[] = "
            (
                e.titolo LIKE :search_title
                OR e.luogo LIKE :search_place
            )
        ";

			$params['search_title'] =
				'%' . $search . '%';

			$params['search_place'] =
				'%' . $search . '%';

		}



		/**
		 * Filtro approvazione
		 */
		if (isset($filters['approvato']) &&
			$filters['approvato'] !== '') {

			$where[] =
				"e.approvato = :approvato";

			$params['approvato'] =
				(int)$filters['approvato'];

		}



		/**
		 * Filtro anno
		 */
		if (!empty($filters['year'])) {

			$where[] =
				"YEAR(e.data_inizio) = :year";

			$params['year'] =
				(int)$filters['year'];

		}



		/**
		 * Filtro regione
		 */
		if (!empty($filters['regione_id'])) {

			$where[] =
				"e.regione_id = :regione_id";

			$params['regione_id'] =
				(int)$filters['regione_id'];

		}



		$whereSql = '';

		if ($where) {

			$whereSql =
				'WHERE ' . implode(
					' AND ',
					$where
				);

		}



		/**
		 * Ordinamenti consentiti
		 */
		$allowedSort = [

			'titolo' =>
				'e.titolo',

			'data_inizio' =>
				'e.data_inizio',

			'created_at' =>
				'e.created_at',

			'final_score' =>
				'e.final_score',

			'event_size' =>
				'e.event_size'

		];



		$orderBy =
			$allowedSort[$sort]
			??
			'e.data_inizio';



		$direction =
			strtoupper($direction) === 'ASC'
				? 'ASC'
				: 'DESC';



		/**
		 * Totale risultati
		 */
		$countSql = "
        SELECT COUNT(*)
        FROM events e
        $whereSql
    ";


		$stmt =
			$this->db->prepare(
				$countSql
			);


		$stmt->execute(
			$params
		);


		$total =
			(int)$stmt->fetchColumn();



		/**
		 * Paginazione
		 */
		$offset =
			($page - 1) * $perPage;



		$sql = "

        SELECT
    e.id,
    e.titolo,
    e.luogo,
    e.data_inizio,
    e.year,
    e.immagine,
    e.approvato,
    e.final_score


        FROM events e


        $whereSql


        ORDER BY
            $orderBy $direction


        LIMIT :limit
        OFFSET :offset

    ";


		$stmt =
			$this->db->prepare(
				$sql
			);



		foreach ($params as $key => $value) {

			$stmt->bindValue(
				':' . $key,
				$value
			);

		}



		$stmt->bindValue(
			':limit',
			$perPage,
			PDO::PARAM_INT
		);


		$stmt->bindValue(
			':offset',
			$offset,
			PDO::PARAM_INT
		);



		$stmt->execute();



		return [

			'data' =>
				$stmt->fetchAll(PDO::FETCH_ASSOC),

			'total' =>
				$total

		];

	}


	public function updateScore(
		int $id,
		int $score,
		int $views
	): void
	{
		$stmt = $this->db->prepare("
            UPDATE events
            SET final_score = :score,
                trending_score = :views,
                last_score_update = NOW()
            WHERE id = :id
        ");


		$stmt->execute([

			'score' => $score,

			'views' => $views,

			'id' => $id

		]);

	}

	public function getPaginated(array $params): array
	{
		$page = max(
			(int) ($params['page'] ?? 1),
			1
		);
		$perPage = (int) ($params['perPage'] ?? 25);
		$offset = ($page - 1) * $perPage;
		$search = trim(
			$params['search'] ?? ''
		);
		$sort = $params['sort'] ?? 'data_inizio';
		$direction = strtolower(
			$params['direction'] ?? 'desc'
		);
		$allowedSorts = [
			'id' => 'e.id',
			'titolo' => 'e.titolo',
			'data_inizio' => 'e.data_inizio',
			'data_fine' => 'e.data_fine',
			'luogo' => 'e.luogo',
			'event_size' => 'e.event_size',
			'approvato' => 'e.approvato'
		];
		if (!isset($allowedSorts[$sort])) {
			$sort = 'data_inizio';
		}
		$orderBy = $allowedSorts[$sort];
		if (!in_array($direction, ['asc', 'desc'], true)) {
			$direction = 'desc';
		}
		$where = ['e.deleted_at IS NULL'];
		$queryParams = [];


		if ($search !== '') {
			$where[] = "
			(
				e.titolo LIKE :search_title
				OR e.luogo LIKE :search_luogo
				OR e.descrizione LIKE :search_descrizione
			)
		";
			$value = '%' . $search . '%';
			$queryParams['search_title'] = $value;
			$queryParams['search_luogo'] = $value;
			$queryParams['search_descrizione'] = $value;
		}
		$filters = $params['filters'] ?? [];
		if (
			isset($filters['approvato'])
			&&
			$filters['approvato'] !== ''
		) {
			$where[] =
				'e.approvato = :approvato';
			$queryParams['approvato'] =
				(int) $filters['approvato'];
		}
		if (
			isset($filters['year'])
			&&
			$filters['year'] !== ''
		) {
			$where[] =
				'YEAR(e.data_inizio) = :year';
			$queryParams['year'] =
				(int) $filters['year'];
		}
		if (
			isset($filters['regione_id'])
			&&
			$filters['regione_id'] !== ''
		) {
			$where[] =
				'e.regione_id = :regione_id';

			$queryParams['regione_id'] =
				(int) $filters['regione_id'];
		}
		$whereSql = '';
		if (!empty($where)) {
			$whereSql =
				'WHERE ' . implode(
					' AND ',
					$where
				);

		}
		/**
		 * COUNT
		 */
		$countSql = "
		SELECT COUNT(*)
		FROM events e
		$whereSql
	";


		$countStmt =
			$this->db->prepare($countSql);


		$countStmt->execute(
			$queryParams
		);


		$total =
			(int) $countStmt->fetchColumn();



		/**
		 * DATA
		 */
		$sql = "
		SELECT

			e.id,

			e.titolo,

			e.slug,

			e.data_inizio,

			e.data_fine,

			e.luogo,

			e.event_size,

			e.approvato

		FROM events e

		$whereSql

		ORDER BY $orderBy $direction

		LIMIT :limit OFFSET :offset
	";



		$stmt =
			$this->db->prepare($sql);



		foreach ($queryParams as $key => $value) {

			$stmt->bindValue(
				':' . $key,
				$value
			);

		}



		$stmt->bindValue(
			':limit',
			$perPage,
			PDO::PARAM_INT
		);


		$stmt->bindValue(
			':offset',
			$offset,
			PDO::PARAM_INT
		);



		$stmt->execute();



		return [

			'data' =>
				$stmt->fetchAll(PDO::FETCH_ASSOC),


			'total' =>
				$total

		];

	}

	public function find(int $id): array
	{
		$stmt = $this->db->prepare(
			"
		SELECT *
		FROM events
		WHERE id = :id
		  AND deleted_at IS NULL
		"
		);


		$stmt->execute([
			'id'=>$id
		]);


		return $stmt->fetch(
			PDO::FETCH_ASSOC
		);
	}
}
