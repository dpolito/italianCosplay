<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class PhotoAnalyticsRepository
{
	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Database::getInstance()->getConnection();
	}

	public function record(array $data): void
	{
		$stmt = $this->db->prepare(
			"INSERT INTO photo_analytics_events
				(event_type, visitor_key, user_id, event_id, photo_id, uploaded_by_user_id, source, filter_event_id, filter_year, filter_uploader_id, request_path, referrer, created_at)
			 VALUES
				(:event_type, :visitor_key, :user_id, :event_id, :photo_id, :uploaded_by_user_id, :source, :filter_event_id, :filter_year, :filter_uploader_id, :request_path, :referrer, NOW())"
		);
		$stmt->execute([
			':event_type' => $data['event_type'],
			':visitor_key' => $data['visitor_key'],
			':user_id' => $data['user_id'],
			':event_id' => $data['event_id'],
			':photo_id' => $data['photo_id'],
			':uploaded_by_user_id' => $data['uploaded_by_user_id'],
			':source' => $data['source'],
			':filter_event_id' => $data['filter_event_id'],
			':filter_year' => $data['filter_year'],
			':filter_uploader_id' => $data['filter_uploader_id'],
			':request_path' => $data['request_path'],
			':referrer' => $data['referrer'],
		]);
	}

	public function hasRecentEvent(string $visitorKey, string $eventType, ?int $eventId, ?int $photoId, ?string $source, int $minutes): bool
	{
		$stmt = $this->db->prepare(
			"SELECT 1
			 FROM photo_analytics_events
			 WHERE visitor_key = :visitor_key
			   AND event_type = :event_type
			   AND event_id <=> :event_id
			   AND photo_id <=> :photo_id
			   AND source <=> :source
			   AND created_at >= DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
			 LIMIT 1"
		);
		$stmt->bindValue(':visitor_key', $visitorKey);
		$stmt->bindValue(':event_type', $eventType);
		$stmt->bindValue(':event_id', $eventId, $eventId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
		$stmt->bindValue(':photo_id', $photoId, $photoId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
		$stmt->bindValue(':source', $source, $source === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
		$stmt->bindValue(':minutes', $minutes, PDO::PARAM_INT);
		$stmt->execute();
		return (bool) $stmt->fetchColumn();
	}

	public function overview(int $days): array
	{
		$events = $this->countsByType($days);
		return [
			'hub_views' => $events['photo_hub_view'] ?? 0,
			'event_gallery_views' => $events['photo_event_gallery_view'] ?? 0,
			'photo_views' => $events['photo_view'] ?? 0,
			'photo_opens' => $events['photo_open'] ?? 0,
			'uploader_profile_clicks' => $events['photo_uploader_profile_click'] ?? 0,
			'upload_cta_clicks' => $events['photo_upload_cta_click'] ?? 0,
			'uploads' => $events['photo_upload_success'] ?? 0,
			'self_claims' => $events['photo_self_claim'] ?? 0,
			'unique_visitors' => $this->scalar(
				"SELECT COUNT(DISTINCT visitor_key) FROM photo_analytics_events WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)",
				[':days' => $days]
			),
			'photos_published' => $this->scalar(
				"SELECT COUNT(*) FROM photos WHERE status = 'published' AND deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)",
				[':days' => $days]
			),
			'active_uploaders' => $this->scalar(
				"SELECT COUNT(DISTINCT uploaded_by_user_id) FROM photos WHERE status = 'published' AND deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)",
				[':days' => $days]
			),
		];
	}

	public function countsByType(int $days): array
	{
		$stmt = $this->db->prepare(
			"SELECT event_type, COUNT(*) AS total
			 FROM photo_analytics_events
			 WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
			 GROUP BY event_type"
		);
		$stmt->bindValue(':days', $days, PDO::PARAM_INT);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$counts = [];
		foreach ($rows as $row) {
			$counts[(string) $row['event_type']] = (int) $row['total'];
		}
		return $counts;
	}

	public function eventStats(int $days, string $sort): array
	{
		$sortSql = match ($sort) {
			'photos' => 'photo_count DESC',
			'photo_views' => 'photo_views DESC',
			default => 'gallery_views DESC',
		};
		$stmt = $this->db->prepare(
			"SELECT e.id, e.titolo, e.slug,
					COALESCE(ps.photo_count, 0) AS photo_count,
					COALESCE(ps.uploader_count, 0) AS uploader_count,
					COALESCE(pas.gallery_views, 0) AS gallery_views,
					COALESCE(pas.photo_views, 0) AS photo_views,
					COALESCE(pas.unique_visitors, 0) AS unique_visitors,
					COALESCE(pas.self_claims, 0) AS self_claims
			 FROM events e
			 LEFT JOIN (
				SELECT event_id, COUNT(*) AS photo_count, COUNT(DISTINCT uploaded_by_user_id) AS uploader_count
				FROM photos
				WHERE status = 'published' AND deleted_at IS NULL
				GROUP BY event_id
			 ) ps ON ps.event_id = e.id
			 LEFT JOIN (
				SELECT event_id,
					SUM(event_type = 'photo_event_gallery_view') AS gallery_views,
					SUM(event_type = 'photo_view') AS photo_views,
					COUNT(DISTINCT CASE WHEN event_type IN ('photo_event_gallery_view', 'photo_view') THEN visitor_key END) AS unique_visitors,
					SUM(event_type = 'photo_self_claim') AS self_claims
				FROM photo_analytics_events
				WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
				  AND event_id IS NOT NULL
				GROUP BY event_id
			 ) pas ON pas.event_id = e.id
			 WHERE e.deleted_at IS NULL
			   AND (ps.photo_count IS NOT NULL OR pas.event_id IS NOT NULL)
			 ORDER BY {$sortSql}, e.titolo ASC
			 LIMIT 30"
		);
		$stmt->bindValue(':days', $days, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function uploaderStats(int $days): array
	{
		$stmt = $this->db->prepare(
			"SELECT u.id, u.username,
					COALESCE(ps.photos_published, 0) AS photos_published,
					COALESCE(ps.event_count, 0) AS event_count,
					COALESCE(pas.photo_views, 0) AS photo_views,
					COALESCE(pas.profile_clicks, 0) AS profile_clicks
			 FROM users u
			 LEFT JOIN (
				SELECT uploaded_by_user_id, COUNT(*) AS photos_published, COUNT(DISTINCT event_id) AS event_count
				FROM photos
				WHERE status = 'published'
				  AND deleted_at IS NULL
				  AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
				GROUP BY uploaded_by_user_id
			 ) ps ON ps.uploaded_by_user_id = u.id
			 LEFT JOIN (
				SELECT uploaded_by_user_id,
					SUM(event_type = 'photo_view') AS photo_views,
					SUM(event_type = 'photo_uploader_profile_click') AS profile_clicks
				FROM photo_analytics_events
				WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days2 DAY)
				  AND uploaded_by_user_id IS NOT NULL
				GROUP BY uploaded_by_user_id
			 ) pas ON pas.uploaded_by_user_id = u.id
			 WHERE u.anonymized_at IS NULL
			   AND (ps.uploaded_by_user_id IS NOT NULL OR pas.uploaded_by_user_id IS NOT NULL)
			 ORDER BY photo_views DESC, photos_published DESC, u.username ASC
			 LIMIT 30"
		);
		$stmt->bindValue(':days', $days, PDO::PARAM_INT);
		$stmt->bindValue(':days2', $days, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function topPhotos(int $days): array
	{
		$stmt = $this->db->prepare(
			"SELECT p.id, p.thumbnail_storage_key, p.thumbnail_width, p.thumbnail_height,
					e.titolo AS event_title, e.slug AS event_slug,
					u.username AS uploader_username,
					SUM(pae.event_type = 'photo_view') AS photo_views,
					COUNT(DISTINCT pae.visitor_key) AS unique_visitors,
					SUM(CASE WHEN pae.event_type = 'photo_self_claim' THEN 1 ELSE 0 END) AS self_claims
			 FROM photo_analytics_events pae
			 INNER JOIN photos p ON p.id = pae.photo_id
			 INNER JOIN events e ON e.id = p.event_id
			 INNER JOIN users u ON u.id = p.uploaded_by_user_id
			 WHERE pae.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
			   AND pae.event_type IN ('photo_view', 'photo_self_claim')
			   AND p.status = 'published'
			   AND p.deleted_at IS NULL
			 GROUP BY p.id, p.thumbnail_storage_key, p.thumbnail_width, p.thumbnail_height, e.titolo, e.slug, u.username
			 ORDER BY photo_views DESC, unique_visitors DESC
			 LIMIT 20"
		);
		$stmt->bindValue(':days', $days, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function filterUsage(int $days): array
	{
		$stmt = $this->db->prepare(
			"SELECT
					SUM(filter_event_id IS NOT NULL) AS event_filter_count,
					SUM(filter_year IS NOT NULL) AS year_filter_count,
					SUM(filter_uploader_id IS NOT NULL) AS uploader_filter_count,
					COUNT(*) AS total
			 FROM photo_analytics_events
			 WHERE event_type = 'photo_filter_used'
			   AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)"
		);
		$stmt->bindValue(':days', $days, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['event_filter_count' => 0, 'year_filter_count' => 0, 'uploader_filter_count' => 0, 'total' => 0];
	}

	public function topFilterEvents(int $days): array
	{
		$stmt = $this->db->prepare(
			"SELECT e.id, e.titolo, e.slug, COUNT(*) AS total
			 FROM photo_analytics_events pae
			 INNER JOIN events e ON e.id = pae.filter_event_id
			 WHERE pae.event_type = 'photo_filter_used'
			   AND pae.filter_event_id IS NOT NULL
			   AND pae.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
			 GROUP BY e.id, e.titolo, e.slug
			 ORDER BY total DESC, e.titolo ASC
			 LIMIT 10"
		);
		$stmt->bindValue(':days', $days, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function dailyTrend(int $days = 30): array
	{
		$stmt = $this->db->prepare(
			"SELECT DATE(created_at) AS day,
					SUM(event_type = 'photo_hub_view') AS hub_views,
					SUM(event_type = 'photo_event_gallery_view') AS gallery_views,
					SUM(event_type = 'photo_view') AS photo_views
			 FROM photo_analytics_events
			 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
			 GROUP BY DATE(created_at)
			 ORDER BY day ASC"
		);
		$stmt->bindValue(':days', $days, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	private function scalar(string $sql, array $params): int
	{
		$stmt = $this->db->prepare($sql);
		foreach ($params as $name => $value) {
			$stmt->bindValue((string) $name, (int) $value, PDO::PARAM_INT);
		}
		$stmt->execute();
		return (int) $stmt->fetchColumn();
	}
}
