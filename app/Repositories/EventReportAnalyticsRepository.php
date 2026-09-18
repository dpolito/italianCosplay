<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class EventReportAnalyticsRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function recordEvent(array $data): bool
	{
		$stmt = $this->db->prepare(
			'INSERT INTO event_report_analytics
				(event_id, session_key, event_name, step_name, fields_completed, page_url, referrer, ip_hash, user_agent_hash, created_at)
			 VALUES
				(:event_id, :session_key, :event_name, :step_name, :fields_completed, :page_url, :referrer, :ip_hash, :user_agent_hash, NOW())'
		);

		return $stmt->execute([
			'event_id' => $data['event_id'] ?? null,
			'session_key' => $data['session_key'],
			'event_name' => $data['event_name'],
			'step_name' => $data['step_name'] ?? null,
			'fields_completed' => $data['fields_completed'] ?? null,
			'page_url' => $data['page_url'] ?? null,
			'referrer' => $data['referrer'] ?? null,
			'ip_hash' => $data['ip_hash'] ?? null,
			'user_agent_hash' => $data['user_agent_hash'] ?? null,
		]);
	}

	public function hasSessionEvent(string $sessionKey, string $eventName): bool
	{
		$stmt = $this->db->prepare(
			'SELECT COUNT(*) FROM event_report_analytics WHERE session_key = :session_key AND event_name = :event_name LIMIT 1'
		);
		$stmt->execute([
			'session_key' => $sessionKey,
			'event_name' => $eventName,
		]);

		return (int) $stmt->fetchColumn() > 0;
	}
}
