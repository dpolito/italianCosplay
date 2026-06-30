<?php
namespace App\Services;
use App\Core\Database;
use PDO;

class EventSignalService
{
	protected $db;
	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function getViews7d(): array
	{
		$sql = "
			SELECT event_id, SUM(views) as views7d
			FROM event_views
			WHERE view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
			GROUP BY event_id
		";

		return $this->db->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
	}
}
