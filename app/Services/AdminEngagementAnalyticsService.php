<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use Throwable;

class AdminEngagementAnalyticsService
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function getTopEventEngagement(int $limit = 8, int $days = 30): array
	{
		try {
			$stmt = $this->db->prepare(
				"SELECT
					e.id,
					e.titolo,
					e.slug,
					COALESCE(v.views, 0) AS views,
					COALESCE(f.favorites, 0) AS favorites,
					COALESCE(a.agenda_actions, 0) AS agenda_actions,
					(COALESCE(f.favorites, 0) + COALESCE(a.agenda_actions, 0)) AS engagement_actions
				FROM events e
				LEFT JOIN (
					SELECT event_id, SUM(views) AS views
					FROM event_views
					WHERE view_date >= DATE_SUB(CURDATE(), INTERVAL :view_days DAY)
					GROUP BY event_id
				) v ON v.event_id = e.id
				LEFT JOIN (
					SELECT entity_id, COUNT(*) AS favorites
					FROM user_favorite_events
					WHERE entity_type = 'event'
					  AND action = 'add'
					  AND created_at >= DATE_SUB(CURDATE(), INTERVAL :favorite_days DAY)
					GROUP BY entity_id
				) f ON f.entity_id = e.id
				LEFT JOIN (
					SELECT event_id, COUNT(*) AS agenda_actions
					FROM user_event_agenda_events
					WHERE action = 'set'
					  AND created_at >= DATE_SUB(CURDATE(), INTERVAL :agenda_days DAY)
					GROUP BY event_id
				) a ON a.event_id = e.id
				WHERE e.approvato = 1
				ORDER BY engagement_actions DESC, views DESC, e.data_inizio ASC
				LIMIT :limit"
			);
			$this->bindWindow($stmt, $days, $limit);
			$stmt->execute();

			return $this->withConversion($stmt->fetchAll(PDO::FETCH_ASSOC));
		} catch (Throwable $exception) {
			error_log('Admin event engagement report failed: ' . $exception->getMessage());
			return [];
		}
	}

	public function getEventOpportunities(int $limit = 8, int $days = 30): array
	{
		try {
			$stmt = $this->db->prepare(
				"SELECT
					e.id,
					e.titolo,
					e.slug,
					COALESCE(v.views, 0) AS views,
					COALESCE(f.favorites, 0) AS favorites,
					COALESCE(a.agenda_actions, 0) AS agenda_actions,
					(COALESCE(f.favorites, 0) + COALESCE(a.agenda_actions, 0)) AS engagement_actions
				FROM events e
				INNER JOIN (
					SELECT event_id, SUM(views) AS views
					FROM event_views
					WHERE view_date >= DATE_SUB(CURDATE(), INTERVAL :view_days DAY)
					GROUP BY event_id
				) v ON v.event_id = e.id
				LEFT JOIN (
					SELECT entity_id, COUNT(*) AS favorites
					FROM user_favorite_events
					WHERE entity_type = 'event'
					  AND action = 'add'
					  AND created_at >= DATE_SUB(CURDATE(), INTERVAL :favorite_days DAY)
					GROUP BY entity_id
				) f ON f.entity_id = e.id
				LEFT JOIN (
					SELECT event_id, COUNT(*) AS agenda_actions
					FROM user_event_agenda_events
					WHERE action = 'set'
					  AND created_at >= DATE_SUB(CURDATE(), INTERVAL :agenda_days DAY)
					GROUP BY event_id
				) a ON a.event_id = e.id
				WHERE e.approvato = 1
				  AND v.views >= 10
				ORDER BY (COALESCE(f.favorites, 0) + COALESCE(a.agenda_actions, 0)) / NULLIF(v.views, 0) ASC, v.views DESC
				LIMIT :limit"
			);
			$this->bindWindow($stmt, $days, $limit);
			$stmt->execute();

			return $this->withConversion($stmt->fetchAll(PDO::FETCH_ASSOC));
		} catch (Throwable $exception) {
			error_log('Admin event opportunity report failed: ' . $exception->getMessage());
			return [];
		}
	}

	public function getTopBlogEngagement(int $limit = 8, int $days = 30): array
	{
		try {
			$stmt = $this->db->prepare(
				"SELECT
					p.id,
					p.titolo,
					p.slug,
					COALESCE(v.views, 0) AS views,
					COALESCE(f.favorites, 0) AS favorites,
					COALESCE(f.favorites, 0) AS engagement_actions
				FROM blog_posts p
				LEFT JOIN (
					SELECT blog_post_id, SUM(views) AS views
					FROM blog_post_views
					WHERE view_date >= DATE_SUB(CURDATE(), INTERVAL :view_days DAY)
					GROUP BY blog_post_id
				) v ON v.blog_post_id = p.id
				LEFT JOIN (
					SELECT entity_id, COUNT(*) AS favorites
					FROM user_favorite_events
					WHERE entity_type = 'blog_post'
					  AND action = 'add'
					  AND created_at >= DATE_SUB(CURDATE(), INTERVAL :favorite_days DAY)
					GROUP BY entity_id
				) f ON f.entity_id = p.id
				WHERE p.status = 'published'
				  AND p.deleted_at IS NULL
				ORDER BY engagement_actions DESC, views DESC, p.created_at DESC
				LIMIT :limit"
			);
			$stmt->bindValue(':view_days', max(1, min($days, 365)), PDO::PARAM_INT);
			$stmt->bindValue(':favorite_days', max(1, min($days, 365)), PDO::PARAM_INT);
			$stmt->bindValue(':limit', max(1, min($limit, 20)), PDO::PARAM_INT);
			$stmt->execute();

			return $this->withConversion($stmt->fetchAll(PDO::FETCH_ASSOC));
		} catch (Throwable $exception) {
			error_log('Admin blog engagement report failed: ' . $exception->getMessage());
			return [];
		}
	}

	private function bindWindow(\PDOStatement $stmt, int $days, int $limit): void
	{
		$days = max(1, min($days, 365));
		$stmt->bindValue(':view_days', $days, PDO::PARAM_INT);
		$stmt->bindValue(':favorite_days', $days, PDO::PARAM_INT);
		$stmt->bindValue(':agenda_days', $days, PDO::PARAM_INT);
		$stmt->bindValue(':limit', max(1, min($limit, 20)), PDO::PARAM_INT);
	}

	private function withConversion(array $rows): array
	{
		foreach ($rows as &$row) {
			$views = (int) ($row['views'] ?? 0);
			$actions = (int) ($row['engagement_actions'] ?? 0);
			$row['conversion_rate'] = $views > 0 ? round(($actions / $views) * 100, 2) : 0.0;
		}

		return $rows;
	}
}
