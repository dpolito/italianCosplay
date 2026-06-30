<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class EventAnalyticsService
{
	protected $db;
	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function trackView(int $eventId): void
	{
		$sessionKey = 'event_view_' . $eventId;
		$cookieKey = 'event_view_' . $eventId;

		if (isset($_SESSION[$sessionKey]) || isset($_COOKIE[$cookieKey])) {
			return;
		}

		$_SESSION[$sessionKey] = true;

		// cookie 24h
		setcookie($cookieKey, '1', time() + 86400, '/');

		$today = date('Y-m-d');

		$stmt = $this->db->prepare("
        INSERT INTO event_views (event_id, view_date, views)
        VALUES (:event_id, :view_date, 1)
        ON DUPLICATE KEY UPDATE views = views + 1
    ");

		$stmt->execute([
			'event_id' => $eventId,
			'view_date' => $today
		]);
	}
	public function getTotalViews(int $eventId): int
	{
		$stmt = $this->db->prepare("
        SELECT SUM(views)
        FROM event_views
        WHERE event_id = :event_id
    ");

		$stmt->execute(['event_id' => $eventId]);

		return (int) $stmt->fetchColumn();
	}
	public function getLast30Days(int $eventId): int
	{
		$stmt = $this->db->prepare("
        SELECT COALESCE(SUM(views), 0)
        FROM event_views
        WHERE event_id = :event_id
        AND view_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");

		$stmt->execute([
			'event_id' => $eventId
		]);
		var_dump($stmt->fetch());

		return (int) $stmt->fetchColumn();
	}
	public function getTopEvents(): array
	{
		$stmt = $this->db->query("
        SELECT view_date, SUM(views) AS views
		FROM event_views
		GROUP BY view_date
		ORDER BY view_date ASC;
    ");

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function getTrendMultiEvent(): array
	{
		$stmt = $this->db->query("
        SELECT 
            e.titolo,
            ev.view_date,
            SUM(ev.views) AS views
        FROM event_views ev
        INNER JOIN events e ON e.id = ev.event_id
        GROUP BY ev.event_id, ev.view_date
        ORDER BY ev.view_date ASC
    ");

		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$result = [];

		foreach ($rows as $row) {
			$title = $row['titolo'];

			$result[$title][] = [
				'date' => $row['view_date'],
				'views' => (int)$row['views']
			];
		}

		return $result;
	}
}
