<?php
namespace App\Repositories;
use App\Core\Database;
use PDO;


class EventRepository
{
	protected $db;
	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function getAll(): array
	{
		return $this->db
			->query("SELECT * FROM events")
			->fetchAll(PDO::FETCH_ASSOC);
	}

	public function updateScore(int $id, int $score, int $views): void
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
}
