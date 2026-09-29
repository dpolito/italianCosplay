<?php

namespace App\Services;

use App\Core\Database;
use App\Support\AuditLogActionType;
use PDO;

class EventAgendaService
{
	private PDO $db;
	private AuditLogService $auditLogService;
	private EventAgendaAnalyticsService $eventAgendaAnalyticsService;
	private NotificationService $notificationService;
	private ?bool $eventsSoftDeleteAvailable = null;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
		$this->auditLogService = new AuditLogService();
		$this->eventAgendaAnalyticsService = new EventAgendaAnalyticsService();
		$this->notificationService = new NotificationService();
	}

	public function getUserAgendaStates(int $userId, array $eventIds): array
	{
		$eventIds = array_values(array_unique(array_filter(array_map('intval', $eventIds))));
		if (empty($eventIds)) {
			return [];
		}

		$placeholders = implode(',', array_fill(0, count($eventIds), '?'));
		$stmt = $this->db->prepare(
			"SELECT event_id, status FROM user_event_agenda WHERE user_id = ? AND event_id IN ({$placeholders})"
		);
		$stmt->execute(array_merge([$userId], $eventIds));

		$states = [];
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$states[(int) $row['event_id']] = (string) $row['status'];
		}

		return $states;
	}

	public function getUserAgenda(int $userId): array
	{
		$stmt = $this->db->prepare(
			"SELECT id, user_id, event_id, status, created_at, updated_at FROM user_event_agenda WHERE user_id = :user_id ORDER BY updated_at DESC, created_at DESC"
		);
		$stmt->execute([':user_id' => $userId]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getUserAgendaYears(int $userId): array
	{
		$deletedAtCondition = $this->eventDeletedAtCondition('e');
		$stmt = $this->db->prepare(
			"SELECT DISTINCT YEAR(e.data_inizio) AS year
			FROM user_event_agenda a
			INNER JOIN events e ON e.id = a.event_id
			WHERE a.user_id = :user_id
			  AND e.data_inizio IS NOT NULL
			  {$deletedAtCondition}
			ORDER BY year DESC"
		);
		$stmt->execute([':user_id' => $userId]);

		return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
	}

	public function getUserAgendaForYear(int $userId, int $year): array
	{
		$deletedAtCondition = $this->eventDeletedAtCondition('e');
		$stmt = $this->db->prepare(
			"SELECT
				a.event_id,
				a.status,
				a.created_at AS agenda_created_at,
				a.updated_at AS agenda_updated_at,
				e.titolo,
				e.slug,
				e.data_inizio,
				e.data_fine,
				e.luogo,
				e.immagine,
				r.nome AS regione_nome,
				p.nome AS provincia_nome,
				c.nome AS comune_nome
			FROM user_event_agenda a
			INNER JOIN events e ON e.id = a.event_id
			LEFT JOIN regioni r ON e.regione_id = r.id
			LEFT JOIN province p ON e.provincia_id = p.id
			LEFT JOIN comuni c ON e.comune_id = c.id
			WHERE a.user_id = :user_id
			  AND YEAR(e.data_inizio) = :year
			  {$deletedAtCondition}
			ORDER BY e.data_inizio ASC, e.data_fine ASC, e.titolo ASC"
		);
		$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
		$stmt->bindValue(':year', $year, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getUpcomingAgendaEvents(int $userId): array
	{
		$deletedAtCondition = $this->eventDeletedAtCondition('e');
		$stmt = $this->db->prepare(
			"SELECT
				a.event_id,
				a.status,
				a.created_at AS agenda_created_at,
				a.updated_at AS agenda_updated_at,
				e.titolo,
				e.slug,
				e.data_inizio,
				e.data_fine,
				e.luogo,
				e.immagine,
				r.nome AS regione_nome,
				p.nome AS provincia_nome,
				c.nome AS comune_nome
			FROM user_event_agenda a
			INNER JOIN events e ON e.id = a.event_id
			LEFT JOIN regioni r ON e.regione_id = r.id
			LEFT JOIN province p ON e.provincia_id = p.id
			LEFT JOIN comuni c ON e.comune_id = c.id
			WHERE a.user_id = :user_id
			  AND COALESCE(e.data_fine, e.data_inizio) >= CURDATE()
			  {$deletedAtCondition}
			ORDER BY e.data_inizio ASC, e.data_fine ASC, e.titolo ASC"
		);
		$stmt->execute([':user_id' => $userId]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getUpcomingByStatus(int $userId, string $status, int $limit = 3): array
	{
		$allowed = ['mi_interessa', 'ci_vado', 'forse_vado'];
		if (!in_array($status, $allowed, true)) {
			return [];
		}

		$stmt = $this->db->prepare(
			"SELECT
				a.event_id,
				a.status,
				a.created_at,
				e.titolo,
				e.slug,
				e.data_inizio,
				e.data_fine,
				e.luogo,
				e.immagine,
				r.nome AS regione_nome,
				p.nome AS provincia_nome,
				c.nome AS comune_nome
			FROM user_event_agenda a
			INNER JOIN events e ON e.id = a.event_id
			LEFT JOIN regioni r ON e.regione_id = r.id
			LEFT JOIN province p ON e.provincia_id = p.id
			LEFT JOIN comuni c ON e.comune_id = c.id
			WHERE a.user_id = :user_id
			  AND a.status = :status
			  AND e.data_fine >= CURDATE()
			ORDER BY e.data_inizio ASC
			LIMIT :limit"
		);
		$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
		$stmt->bindValue(':status', $status, PDO::PARAM_STR);
		$stmt->bindValue(':limit', max(1, min($limit, 10)), PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function setStatus(int $userId, int $eventId, string $status): bool
	{
		$allowed = ['mi_interessa', 'ci_vado', 'forse_vado'];
		if (!in_array($status, $allowed, true)) {
			return false;
		}

		$previousStatus = $this->getUserAgendaStates($userId, [$eventId])[$eventId] ?? null;
		$stmt = $this->db->prepare(
			"INSERT INTO user_event_agenda (user_id, event_id, status, created_at, updated_at)
			 VALUES (:user_id, :event_id, :status, NOW(), NOW())
			 ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()"
		);
		$ok = $stmt->execute([
			':user_id' => $userId,
			':event_id' => $eventId,
			':status' => $status,
		]);

		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::EVENT_AGENDA_UPDATED,
			'entity_type' => 'event',
			'entity_id' => $eventId,
			'success' => $ok ? 1 : 0,
			'payload' => [
				'status' => $status,
			],
		]);

		if ($ok) {
			$this->eventAgendaAnalyticsService->track($userId, $eventId, 'set', $status, $previousStatus);
			if ($previousStatus === null) {
				$this->notifyFirstAgendaSave($userId, $eventId, $status);
			}
		}

		return $ok;
	}

	public function eventExists(int $eventId): bool
	{
		$deletedAtCondition = $this->eventDeletedAtCondition();
		$stmt = $this->db->prepare(
			"SELECT 1 FROM events WHERE id = :event_id {$deletedAtCondition} LIMIT 1"
		);
		$stmt->bindValue(':event_id', $eventId, PDO::PARAM_INT);
		$stmt->execute();

		return (bool) $stmt->fetchColumn();
	}

	public function removeStatus(int $userId, int $eventId): bool
	{
		$previousStatus = $this->getUserAgendaStates($userId, [$eventId])[$eventId] ?? null;
		$stmt = $this->db->prepare(
			"DELETE FROM user_event_agenda WHERE user_id = :user_id AND event_id = :event_id"
		);
		$ok = $stmt->execute([
			':user_id' => $userId,
			':event_id' => $eventId,
		]);

		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::EVENT_AGENDA_REMOVED,
			'entity_type' => 'event',
			'entity_id' => $eventId,
			'success' => $ok ? 1 : 0,
			'payload' => [],
		]);

		if ($ok) {
			$this->eventAgendaAnalyticsService->track($userId, $eventId, 'remove', null, $previousStatus);
		}

		return $ok;
	}

	public function getUserAgendaCount(int $userId): array
	{
		$stmt = $this->db->prepare(
			"SELECT status, COUNT(*) AS total FROM user_event_agenda WHERE user_id = :user_id GROUP BY status"
		);
		$stmt->execute([':user_id' => $userId]);

		$counts = [
			'mi_interessa' => 0,
			'ci_vado' => 0,
			'forse_vado' => 0,
		];

		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$counts[(string) $row['status']] = (int) $row['total'];
		}

		return $counts;
	}

	public function getUserAgendaCountForYear(int $userId, int $year): array
	{
		$deletedAtCondition = $this->eventDeletedAtCondition('e');
		$stmt = $this->db->prepare(
			"SELECT a.status, COUNT(*) AS total
			FROM user_event_agenda a
			INNER JOIN events e ON e.id = a.event_id
			WHERE a.user_id = :user_id
			  AND YEAR(e.data_inizio) = :year
			  {$deletedAtCondition}
			GROUP BY a.status"
		);
		$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
		$stmt->bindValue(':year', $year, PDO::PARAM_INT);
		$stmt->execute();

		$counts = [
			'mi_interessa' => 0,
			'ci_vado' => 0,
			'forse_vado' => 0,
		];

		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$counts[(string) $row['status']] = (int) $row['total'];
		}

		return $counts;
	}

	private function eventDeletedAtCondition(string $alias = ''): string
	{
		if (!$this->hasEventsSoftDeleteColumn()) {
			return '';
		}

		$prefix = $alias !== '' ? $alias . '.' : '';

		return 'AND ' . $prefix . 'deleted_at IS NULL';
	}

	private function notifyFirstAgendaSave(int $userId, int $eventId, string $status): void
	{
		$event = $this->findEventNotificationData($eventId);
		if ($event === null) {
			return;
		}

		$title = (string) ($event['titolo'] ?? 'Evento');
		$slug = (string) ($event['slug'] ?? '');
		$statusLabels = [
			'mi_interessa' => 'Mi interessa',
			'ci_vado' => 'Ci vado',
			'forse_vado' => 'Forse vado',
		];

		$this->notificationService->createNotification([
			'user_id' => $userId,
			'notification_type' => 'agenda_created',
			'title' => 'Evento salvato in Agenda',
			'message' => $title . ' è stato aggiunto alla tua Agenda come "' . ($statusLabels[$status] ?? $status) . '".',
			'source_entity_type' => 'event',
			'source_entity_id' => $eventId,
			'payload' => [
				'agenda_status' => $status,
				'return_url' => $slug !== '' ? '/eventi-cosplay/' . $slug : null,
			],
		]);
	}

	private function findEventNotificationData(int $eventId): ?array
	{
		$stmt = $this->db->prepare("SELECT titolo, slug FROM events WHERE id = :id LIMIT 1");
		$stmt->execute([':id' => $eventId]);
		$event = $stmt->fetch(PDO::FETCH_ASSOC);

		return is_array($event) ? $event : null;
	}

	private function hasEventsSoftDeleteColumn(): bool
	{
		if ($this->eventsSoftDeleteAvailable !== null) {
			return $this->eventsSoftDeleteAvailable;
		}

		$stmt = $this->db->prepare(
			"SELECT COUNT(*)
			FROM information_schema.COLUMNS
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = 'events'
			  AND COLUMN_NAME = 'deleted_at'"
		);
		$stmt->execute();
		$this->eventsSoftDeleteAvailable = (int) $stmt->fetchColumn() > 0;

		return $this->eventsSoftDeleteAvailable;
	}
}
