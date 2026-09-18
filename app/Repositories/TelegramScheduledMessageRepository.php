<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class TelegramScheduledMessageRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function create(array $data): int
	{
		$stmt = $this->db->prepare("INSERT INTO telegram_scheduled_messages
			(message, parse_mode, disable_web_page_preview, disable_notification, status, scheduled_at, created_by, created_at)
			VALUES
			(:message, :parse_mode, :disable_web_page_preview, :disable_notification, 'scheduled', :scheduled_at, :created_by, NOW())");
		$stmt->execute([
			':message' => $data['message'],
			':parse_mode' => $data['parse_mode'] ?: null,
			':disable_web_page_preview' => (int) $data['disable_web_page_preview'],
			':disable_notification' => (int) $data['disable_notification'],
			':scheduled_at' => $data['scheduled_at'],
			':created_by' => $data['created_by'] ?: null,
		]);

		return (int) $this->db->lastInsertId();
	}

	public function findById(int $id): ?array
	{
		$stmt = $this->db->prepare("SELECT m.*, u.username AS created_by_username
			FROM telegram_scheduled_messages m
			LEFT JOIN users u ON u.id = m.created_by
			WHERE m.id = :id
			LIMIT 1");
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return is_array($row) ? $row : null;
	}

	public function findForAdminList(int $limit = 100): array
	{
		$limit = max(10, min($limit, 250));
		$stmt = $this->db->prepare("SELECT m.*, u.username AS created_by_username,
				DATE_FORMAT(m.scheduled_at, '%d/%m/%Y %H:%i') AS scheduled_at_label,
				DATE_FORMAT(m.sent_at, '%d/%m/%Y %H:%i') AS sent_at_label
			FROM telegram_scheduled_messages m
			LEFT JOIN users u ON u.id = m.created_by
			ORDER BY
				CASE m.status WHEN 'scheduled' THEN 0 WHEN 'failed' THEN 1 WHEN 'sent' THEN 2 ELSE 3 END,
				m.scheduled_at ASC,
				m.id DESC
			LIMIT {$limit}");
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findDueScheduled(int $limit = 10): array
	{
		$limit = max(1, min($limit, 50));
		$stmt = $this->db->prepare("SELECT *
			FROM telegram_scheduled_messages
			WHERE status = 'scheduled'
				AND scheduled_at <= NOW()
			ORDER BY scheduled_at ASC, id ASC
			LIMIT {$limit}");
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function markSent(int $id, ?int $telegramMessageId): bool
	{
		$stmt = $this->db->prepare("UPDATE telegram_scheduled_messages
			SET status = 'sent', sent_at = NOW(), telegram_message_id = :telegram_message_id, error_message = NULL, updated_at = NOW()
			WHERE id = :id AND status = 'scheduled'");

		return $stmt->execute([
			':id' => $id,
			':telegram_message_id' => $telegramMessageId,
		]);
	}

	public function markFailed(int $id, string $errorMessage): bool
	{
		$stmt = $this->db->prepare("UPDATE telegram_scheduled_messages
			SET status = 'failed', error_message = :error_message, updated_at = NOW()
			WHERE id = :id");

		return $stmt->execute([
			':id' => $id,
			':error_message' => mb_substr($errorMessage, 0, 255),
		]);
	}

	public function cancel(int $id): bool
	{
		$stmt = $this->db->prepare("UPDATE telegram_scheduled_messages
			SET status = 'cancelled', updated_at = NOW()
			WHERE id = :id AND status = 'scheduled'");
		$stmt->execute([':id' => $id]);

		return $stmt->rowCount() > 0;
	}
}
