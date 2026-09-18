<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class LegacyInvitationEmailRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function findAllWithStatus(): array
	{
		$stmt = $this->db->query("SELECT id, email, recipient_name, source_label, legacy_last_visited_at, tracking_token, status,
			subject, sent_at, last_attempted_at, error_message, click_count, first_clicked_at, last_clicked_at
			FROM legacy_invitation_email_recipients
			ORDER BY status = 'pending' DESC, last_clicked_at DESC, sent_at DESC, email ASC");

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findForAdminList(string $search, string $sort, string $direction, int $page, int $perPage): array
	{
		$allowedSorts = [
			'id' => 'id',
			'email' => 'email',
			'source_label' => 'source_label',
			'legacy_last_visited_at' => 'legacy_last_visited_at',
			'status' => 'status',
			'sent_at' => 'sent_at',
			'click_count' => 'click_count',
			'last_clicked_at' => 'last_clicked_at',
			'error_message' => 'error_message',
		];
		$orderBy = $allowedSorts[$sort] ?? 'id';
		$direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';
		$page = max($page, 1);
		$perPage = max(10, min($perPage, 100));
		$offset = ($page - 1) * $perPage;

		$where = '';
		$params = [];
		$search = trim($search);
		if ($search !== '') {
			$where = "WHERE email LIKE :search_email
				OR source_label LIKE :search_source
				OR status LIKE :search_status
				OR error_message LIKE :search_error";
			$params = [
				':search_email' => '%' . $search . '%',
				':search_source' => '%' . $search . '%',
				':search_status' => '%' . $search . '%',
				':search_error' => '%' . $search . '%',
			];
		}

		$countStmt = $this->db->prepare("SELECT COUNT(*) FROM legacy_invitation_email_recipients {$where}");
		$countStmt->execute($params);
		$total = (int) $countStmt->fetchColumn();

		$stmt = $this->db->prepare("SELECT id, email, recipient_name, source_label, legacy_last_visited_at, tracking_token, status,
				subject, sent_at, last_attempted_at, error_message, click_count, first_clicked_at, last_clicked_at,
				DATE_FORMAT(legacy_last_visited_at, '%d/%m/%Y %H:%i') AS legacy_last_visited_at_label,
				DATE_FORMAT(sent_at, '%d/%m/%Y %H:%i') AS sent_at_label,
				DATE_FORMAT(last_clicked_at, '%d/%m/%Y %H:%i') AS last_clicked_at_label
			FROM legacy_invitation_email_recipients
			{$where}
			ORDER BY {$orderBy} {$direction}, id DESC
			LIMIT :limit OFFSET :offset");
		foreach ($params as $key => $value) {
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

	public function findPendingRecipients(int $limit): array
	{
		$limit = max(1, min(200, $limit));
		$stmt = $this->db->prepare("SELECT id, email, recipient_name, tracking_token
			FROM legacy_invitation_email_recipients
			WHERE status <> 'sent'
			ORDER BY status = 'failed' ASC, email ASC
			LIMIT :limit");
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function insertRecipient(string $email, ?string $recipientName, ?string $sourceLabel, ?string $legacyLastVisitedAt, string $trackingToken): bool
	{
		$stmt = $this->db->prepare("INSERT IGNORE INTO legacy_invitation_email_recipients
			(email, recipient_name, source_label, legacy_last_visited_at, tracking_token, status, created_at)
			VALUES (:email, :recipient_name, :source_label, :legacy_last_visited_at, :tracking_token, 'pending', NOW())");

		$stmt->execute([
			':email' => $email,
			':recipient_name' => $recipientName,
			':source_label' => $sourceLabel,
			':legacy_last_visited_at' => $legacyLastVisitedAt,
			':tracking_token' => $trackingToken,
		]);

		return $stmt->rowCount() > 0;
	}

	public function recordSent(int $recipientId, string $subject, int $sentBy): void
	{
		$stmt = $this->db->prepare("UPDATE legacy_invitation_email_recipients
			SET subject = :subject, status = 'sent', sent_by = :sent_by, sent_at = NOW(),
				last_attempted_at = NOW(), error_message = NULL, updated_at = NOW()
			WHERE id = :id");
		$stmt->execute([
			':id' => $recipientId,
			':subject' => $subject,
			':sent_by' => $sentBy,
		]);
	}

	public function recordFailed(int $recipientId, string $subject, int $sentBy, string $errorMessage): void
	{
		$stmt = $this->db->prepare("UPDATE legacy_invitation_email_recipients
			SET subject = :subject,
				status = IF(status = 'sent', status, 'failed'),
				sent_by = IF(status = 'sent', sent_by, :sent_by),
				last_attempted_at = IF(status = 'sent', last_attempted_at, NOW()),
				error_message = IF(status = 'sent', error_message, :error_message),
				updated_at = NOW()
			WHERE id = :id");
		$stmt->execute([
			':id' => $recipientId,
			':subject' => $subject,
			':sent_by' => $sentBy,
			':error_message' => mb_substr($errorMessage, 0, 1000),
		]);
	}

	public function recordClick(string $trackingToken, ?string $userAgent): ?array
	{
		$stmt = $this->db->prepare("SELECT id, email, click_count
			FROM legacy_invitation_email_recipients
			WHERE tracking_token = :tracking_token
			LIMIT 1");
		$stmt->execute([':tracking_token' => $trackingToken]);
		$recipient = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$recipient) {
			return null;
		}

		$update = $this->db->prepare("UPDATE legacy_invitation_email_recipients
			SET click_count = click_count + 1,
				first_clicked_at = IF(first_clicked_at IS NULL, NOW(), first_clicked_at),
				last_clicked_at = NOW(),
				last_click_user_agent = :user_agent,
				updated_at = NOW()
			WHERE id = :id");
		$update->execute([
			':id' => (int) $recipient['id'],
			':user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 500) : null,
		]);

		return $recipient;
	}
}
