<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class OrganizationEmailDeliveryRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function findOrganizationsWithEmailStatus(): array
	{
		$stmt = $this->db->query("SELECT o.id, o.name, o.email, o.status AS organization_status,
			d.status AS delivery_status, d.sent_at, d.last_attempted_at, d.error_message
			FROM organizations o
			LEFT JOIN organization_email_deliveries d
				ON d.organization_id = o.id AND d.email = o.email
			WHERE o.email IS NOT NULL AND o.email <> ''
			ORDER BY d.sent_at IS NULL DESC, o.name ASC");

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findPendingRecipients(): array
	{
		$stmt = $this->db->query("SELECT o.id, o.name, o.email
			FROM organizations o
			WHERE o.email IS NOT NULL AND o.email <> ''
			AND NOT EXISTS (
				SELECT 1
				FROM organization_email_deliveries d
				WHERE d.organization_id = o.id AND d.email = o.email AND d.status = 'sent'
			)
			ORDER BY o.name ASC");

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function recordSent(int $organizationId, string $email, string $subject, int $sentBy): void
	{
		$stmt = $this->db->prepare("INSERT INTO organization_email_deliveries
			(organization_id, email, subject, status, sent_by, sent_at, last_attempted_at, created_at)
			VALUES (:organization_id, :email, :subject, 'sent', :sent_by, NOW(), NOW(), NOW())
			ON DUPLICATE KEY UPDATE subject = :subject_update, status = 'sent', sent_by = :sent_by_update,
				sent_at = NOW(), last_attempted_at = NOW(), error_message = NULL, updated_at = NOW()");
		$stmt->execute([
			':organization_id' => $organizationId,
			':email' => $email,
			':subject' => $subject,
			':sent_by' => $sentBy,
			':subject_update' => $subject,
			':sent_by_update' => $sentBy,
		]);
	}

	public function recordFailed(int $organizationId, string $email, string $subject, int $sentBy, string $errorMessage): void
	{
		$stmt = $this->db->prepare("INSERT INTO organization_email_deliveries
			(organization_id, email, subject, status, sent_by, last_attempted_at, error_message, created_at)
			VALUES (:organization_id, :email, :subject, 'failed', :sent_by, NOW(), :error_message, NOW())
			ON DUPLICATE KEY UPDATE subject = IF(status = 'sent', subject, :subject_update),
				status = IF(status = 'sent', status, 'failed'),
				sent_by = IF(status = 'sent', sent_by, :sent_by_update),
				last_attempted_at = IF(status = 'sent', last_attempted_at, NOW()),
				error_message = IF(status = 'sent', error_message, :error_message_update),
				updated_at = NOW()");
		$stmt->execute([
			':organization_id' => $organizationId,
			':email' => $email,
			':subject' => $subject,
			':sent_by' => $sentBy,
			':error_message' => mb_substr($errorMessage, 0, 1000),
			':subject_update' => $subject,
			':sent_by_update' => $sentBy,
			':error_message_update' => mb_substr($errorMessage, 0, 1000),
		]);
	}
}
