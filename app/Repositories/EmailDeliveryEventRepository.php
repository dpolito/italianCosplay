<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class EmailDeliveryEventRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function upsert(array $event): void
	{
		$stmt = $this->db->prepare("INSERT INTO email_delivery_events
			(provider, message_id, webhook_id, event_name, email, subject, tag, tags_json, template_id, reason,
				sending_ip, ts_event, ts_epoch, event_date, raw_payload_json, created_at, updated_at)
			VALUES
			(:provider, :message_id, :webhook_id, :event_name, :email, :subject, :tag, :tags_json, :template_id, :reason,
				:sending_ip, :ts_event, :ts_epoch, :event_date, :raw_payload_json, NOW(), NOW())
			ON DUPLICATE KEY UPDATE
				webhook_id = VALUES(webhook_id),
				email = VALUES(email),
				subject = VALUES(subject),
				tag = VALUES(tag),
				tags_json = VALUES(tags_json),
				template_id = VALUES(template_id),
				reason = VALUES(reason),
				sending_ip = VALUES(sending_ip),
				raw_payload_json = VALUES(raw_payload_json),
				updated_at = NOW()");

		$stmt->execute([
			':provider' => $event['provider'],
			':message_id' => $event['message_id'],
			':webhook_id' => $event['webhook_id'],
			':event_name' => $event['event_name'],
			':email' => $event['email'],
			':subject' => $event['subject'],
			':tag' => $event['tag'],
			':tags_json' => $event['tags_json'],
			':template_id' => $event['template_id'],
			':reason' => $event['reason'],
			':sending_ip' => $event['sending_ip'],
			':ts_event' => $event['ts_event'],
			':ts_epoch' => $event['ts_epoch'],
			':event_date' => $event['event_date'],
			':raw_payload_json' => $event['raw_payload_json'],
		]);
	}

	public function getOverview(array $filters = [], int $limit = 100): array
	{
		return [
			'totals' => $this->getTotals($filters),
			'byTag' => $this->getGroupedTotals('tag', $filters),
			'byEvent' => $this->getGroupedTotals('event_name', $filters),
			'events' => $this->findRecent($filters, $limit),
			'filters' => $filters,
		];
	}

	private function getTotals(array $filters): array
	{
		$where = $this->buildWhere($filters);
		$stmt = $this->db->prepare("SELECT
			COUNT(*) AS total,
			COUNT(DISTINCT message_id) AS messages,
			SUM(CASE WHEN event_name IN ('delivered') THEN 1 ELSE 0 END) AS delivered,
			SUM(CASE WHEN event_name IN ('opened', 'unique_opened', 'proxy_open', 'unique_proxy_open') THEN 1 ELSE 0 END) AS opened,
			SUM(CASE WHEN event_name IN ('click', 'clicked') THEN 1 ELSE 0 END) AS clicked,
			SUM(CASE WHEN event_name IN ('hard_bounce', 'hardBounce', 'soft_bounce', 'softBounce', 'blocked', 'invalid', 'spam') THEN 1 ELSE 0 END) AS problems
			FROM email_delivery_events
			{$where['sql']}");
		$stmt->execute($where['params']);

		return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
	}

	private function getGroupedTotals(string $field, array $filters): array
	{
		$where = $this->buildWhere($filters);
		$fieldSql = $field === 'tag' ? "COALESCE(NULLIF(tag, ''), 'senza_tag')" : 'event_name';
		$stmt = $this->db->prepare("SELECT {$fieldSql} AS label, COUNT(*) AS total, COUNT(DISTINCT message_id) AS messages
			FROM email_delivery_events
			{$where['sql']}
			GROUP BY label
			ORDER BY total DESC, label ASC
			LIMIT 50");
		$stmt->execute($where['params']);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	private function findRecent(array $filters, int $limit): array
	{
		$where = $this->buildWhere($filters);
		$limit = max(10, min($limit, 250));
		$stmt = $this->db->prepare("SELECT id, provider, message_id, event_name, email, subject, tag, tags_json,
				template_id, reason, sending_ip, event_date, created_at
			FROM email_delivery_events
			{$where['sql']}
			ORDER BY COALESCE(event_date, created_at) DESC, id DESC
			LIMIT {$limit}");
		$stmt->execute($where['params']);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findForAdminList(array $filters, string $search, string $sort, string $direction, int $page, int $perPage): array
	{
		$where = $this->buildWhere($filters, $search);
		$allowedSorts = [
			'id' => 'id',
			'event_date' => 'COALESCE(event_date, created_at)',
			'event_name' => 'event_name',
			'tag' => 'tag',
			'email' => 'email',
			'subject' => 'subject',
			'created_at' => 'created_at',
		];
		$orderBy = $allowedSorts[$sort] ?? 'COALESCE(event_date, created_at)';
		$direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';
		$page = max($page, 1);
		$perPage = max(10, min($perPage, 100));
		$offset = ($page - 1) * $perPage;

		$countStmt = $this->db->prepare("SELECT COUNT(*) FROM email_delivery_events {$where['sql']}");
		$countStmt->execute($where['params']);
		$total = (int) $countStmt->fetchColumn();

		$stmt = $this->db->prepare("SELECT id, provider, message_id, event_name, email, subject, tag, template_id,
				reason, sending_ip, event_date, created_at,
				DATE_FORMAT(COALESCE(event_date, created_at), '%d/%m/%Y %H:%i') AS event_date_label
			FROM email_delivery_events
			{$where['sql']}
			ORDER BY {$orderBy} {$direction}, id DESC
			LIMIT :limit OFFSET :offset");
		foreach ($where['params'] as $key => $value) {
			$stmt->bindValue($key, $value);
		}
		$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();

		return [
			'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
			'total' => $total,
			'page' => $page,
			'perPage' => $perPage,
			'pages' => max((int) ceil($total / $perPage), 1),
		];
	}

	public function findDetail(int $id): ?array
	{
		$stmt = $this->db->prepare("SELECT id, provider, message_id, webhook_id, event_name, email, subject, tag,
				tags_json, template_id, reason, sending_ip, ts_event, ts_epoch, event_date, raw_payload_json,
				created_at, updated_at
			FROM email_delivery_events
			WHERE id = :id
			LIMIT 1");
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return is_array($row) ? $row : null;
	}

	private function buildWhere(array $filters, string $search = ''): array
	{
		$conditions = [];
		$params = [];

		if (!empty($filters['tag'])) {
			$conditions[] = 'tag = :tag';
			$params[':tag'] = $filters['tag'];
		}

		if (!empty($filters['event_name'])) {
			$conditions[] = 'event_name = :event_name';
			$params[':event_name'] = $filters['event_name'];
		}

		if (!empty($filters['email'])) {
			$conditions[] = 'email LIKE :email';
			$params[':email'] = '%' . $filters['email'] . '%';
		}

		if (!empty($filters['from'])) {
			$conditions[] = 'COALESCE(event_date, created_at) >= :from_date';
			$params[':from_date'] = $filters['from'] . ' 00:00:00';
		}

		if (!empty($filters['to'])) {
			$conditions[] = 'COALESCE(event_date, created_at) <= :to_date';
			$params[':to_date'] = $filters['to'] . ' 23:59:59';
		}

		$search = trim($search);
		if ($search !== '') {
			$conditions[] = '(email LIKE :search_email OR subject LIKE :search_subject OR message_id LIKE :search_message_id OR reason LIKE :search_reason)';
			$params[':search_email'] = '%' . $search . '%';
			$params[':search_subject'] = '%' . $search . '%';
			$params[':search_message_id'] = '%' . $search . '%';
			$params[':search_reason'] = '%' . $search . '%';
		}

		return [
			'sql' => $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions),
			'params' => $params,
		];
	}
}
