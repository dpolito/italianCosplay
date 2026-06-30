<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class BlogAnalyticsService
{
	protected $db;
	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function trackView(int $blog_postId): void
	{
		$sessionKey = 'blog_post_view_' . $blog_postId;
		$cookieKey = 'blog_post_view_' . $blog_postId;

		if (isset($_SESSION[$sessionKey]) || isset($_COOKIE[$cookieKey])) {
			return;
		}

		$_SESSION[$sessionKey] = true;

		// cookie 24h
		setcookie($cookieKey, '1', time() + 86400, '/');

		$today = date('Y-m-d');

		$stmt = $this->db->prepare("
        INSERT INTO blog_post_views (blog_post_id, view_date, views)
        VALUES (:blog_post_id, :view_date, 1)
        ON DUPLICATE KEY UPDATE views = views + 1
    ");

		$stmt->execute([
			'blog_post_id' => $blog_postId,
			'view_date' => $today
		]);
	}
	public function getTotalViews(int $blog_postId): int
	{
		$stmt = $this->db->prepare("
        SELECT SUM(views)
        FROM blog_post_views
        WHERE blog_post_id = :blog_post_id
    ");

		$stmt->execute(['blog_post_id' => $blog_postId]);

		return (int) $stmt->fetchColumn();
	}
	public function getLast30Days(int $blog_postId): int
	{
		$stmt = $this->db->prepare("
        SELECT COALESCE(SUM(views), 0)
        FROM blog_post_views
        WHERE blog_post_id = :blog_post_id
        AND view_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");

		$stmt->execute([
			'blog_post_id' => $blog_postId
		]);
		var_dump($stmt->fetch());

		return (int) $stmt->fetchColumn();
	}
	public function getTopblog_posts(): array
	{
		$stmt = $this->db->query("
        SELECT view_date, SUM(views) AS views
		FROM blog_post_views
		GROUP BY view_date
		ORDER BY view_date ASC;
    ");

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function getTrendMultiblog_post(): array
	{
		$stmt = $this->db->query("
        SELECT 
            e.titolo,
            ev.view_date,
            SUM(ev.views) AS views
        FROM blog_post_views ev
        INNER JOIN blog_posts e ON e.id = ev.blog_post_id
        GROUP BY ev.blog_post_id, ev.view_date
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
