<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class GuestAnalyticsService
{
	protected $db;
	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function trackView(int $guestId): void
	{
		$sessionKey = 'guest_view_' . $guestId;
		$cookieKey = 'guest_view_' . $guestId;

		if (isset($_SESSION[$sessionKey]) || isset($_COOKIE[$cookieKey])) {
			return;
		}

		$_SESSION[$sessionKey] = true;

		// cookie 24h
		setcookie($cookieKey, '1', time() + 86400, '/');

		$today = date('Y-m-d');

		$stmt = $this->db->prepare("
        INSERT INTO guest_views (guest_id, view_date, views)
        VALUES (:guest_id, :view_date, 1)
        ON DUPLICATE KEY UPDATE views = views + 1
    ");

		$stmt->execute([
			'guest_id' => $guestId,
			'view_date' => $today
		]);
	}
	public function getTotalViews(int $guestId): int
	{
		$stmt = $this->db->prepare("
        SELECT SUM(views)
        FROM guest_views
        WHERE guest_id = :guest_id
    ");

		$stmt->execute(['guest_id' => $guestId]);

		return (int) $stmt->fetchColumn();
	}
	public function getLast30Days(int $guestId): int
	{
		$stmt = $this->db->prepare("
        SELECT COALESCE(SUM(views), 0)
        FROM guest_views
        WHERE guest_id = :guest_id
        AND view_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");

		$stmt->execute([
			'guest_id' => $guestId
		]);
		var_dump($stmt->fetch());

		return (int) $stmt->fetchColumn();
	}
	public function getTopguests(): array
	{
		$stmt = $this->db->query("
        SELECT view_date, SUM(views) AS views
		FROM guest_views
		GROUP BY view_date
		ORDER BY view_date ASC;
    ");

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function getTrendMultiguest(): array
	{
		$stmt = $this->db->query("
        SELECT 
            e.titolo,
            ev.view_date,
            SUM(ev.views) AS views
        FROM guest_views ev
        INNER JOIN guests e ON e.id = ev.guest_id
        GROUP BY ev.guest_id, ev.view_date
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
