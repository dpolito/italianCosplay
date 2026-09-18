<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use RuntimeException;

class OrganizationInvitationRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function findUserByEmail(string $email): ?array
	{
		$stmt = $this->db->prepare('SELECT id, username, email FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
		$stmt->execute([':email' => $email]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function findUserById(int $userId): ?array
	{
		$stmt = $this->db->prepare('SELECT id, username, email FROM users WHERE id = :id LIMIT 1');
		$stmt->execute([':id' => $userId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function getOrganizationMemberRole(int $organizationId, int $userId): ?string
	{
		$stmt = $this->db->prepare("SELECT role FROM organization_users WHERE organization_id = :organization_id AND user_id = :user_id AND status = 'active' LIMIT 1");
		$stmt->execute([':organization_id' => $organizationId, ':user_id' => $userId]);
		$role = $stmt->fetchColumn();
		return $role === false ? null : (string) $role;
	}

	public function getMember(int $organizationId, int $userId): ?array
	{
		$stmt = $this->db->prepare('SELECT id, role, status FROM organization_users WHERE organization_id = :organization_id AND user_id = :user_id LIMIT 1');
		$stmt->execute([':organization_id' => $organizationId, ':user_id' => $userId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function findPendingByOrganizationEmail(int $organizationId, string $email): ?array
	{
		$stmt = $this->db->prepare("SELECT * FROM organization_invitations WHERE organization_id = :organization_id AND LOWER(email) = LOWER(:email) AND status = 'pending' AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
		$stmt->execute([':organization_id' => $organizationId, ':email' => $email]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function create(array $data): int
	{
		$this->db->beginTransaction();
		try {
			$stmt = $this->db->prepare("INSERT INTO organization_invitations (organization_id, email, user_id, role, status, token_hash, invited_by, expires_at, created_at) VALUES (:organization_id, :email, :user_id, :role, 'pending', :token_hash, :invited_by, :expires_at, NOW())");
			$stmt->execute([
				':organization_id' => $data['organization_id'],
				':email' => $data['email'],
				':user_id' => $data['user_id'] ?: null,
				':role' => $data['role'],
				':token_hash' => $data['token_hash'],
				':invited_by' => $data['invited_by'],
				':expires_at' => $data['expires_at'],
			]);
			$id = (int) $this->db->lastInsertId();
			if (!empty($data['user_id'])) {
				$member = $this->db->prepare("INSERT INTO organization_users (organization_id, user_id, role, status, invited_by, invited_at, created_at) VALUES (:organization_id, :user_id, :role, 'invited', :invited_by, NOW(), NOW()) ON DUPLICATE KEY UPDATE role = :role_update, status = 'invited', invited_by = :invited_by_update, invited_at = NOW(), joined_at = NULL, updated_at = NOW()");
				$member->execute([
					':organization_id' => $data['organization_id'],
					':user_id' => $data['user_id'],
					':role' => $data['role'],
					':invited_by' => $data['invited_by'],
					':role_update' => $data['role'],
					':invited_by_update' => $data['invited_by'],
				]);
			}
			$this->db->commit();
			return $id;
		} catch (\Throwable $exception) {
			if ($this->db->inTransaction()) $this->db->rollBack();
			throw $exception;
		}
	}

	public function findPendingByTokenHash(string $tokenHash): ?array
	{
		$stmt = $this->db->prepare("SELECT oi.*, o.name AS organization_name, u.username AS invited_username, u.email AS invited_user_email FROM organization_invitations oi INNER JOIN organizations o ON o.id = oi.organization_id LEFT JOIN users u ON u.id = oi.user_id WHERE oi.token_hash = :token_hash AND oi.status = 'pending' AND oi.expires_at > NOW() LIMIT 1");
		$stmt->execute([':token_hash' => $tokenHash]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function accept(int $invitationId, int $userId, string $role): bool
	{
		$this->db->beginTransaction();
		try {
			$invitation = $this->db->prepare("SELECT organization_id FROM organization_invitations WHERE id = :id AND status = 'pending' AND expires_at > NOW() FOR UPDATE");
			$invitation->execute([':id' => $invitationId]);
			$row = $invitation->fetch(PDO::FETCH_ASSOC);
			if (!$row) throw new RuntimeException('Invito non valido o scaduto.');
			$member = $this->db->prepare("INSERT INTO organization_users (organization_id, user_id, role, status, joined_at, created_at) VALUES (:organization_id, :user_id, :role, 'active', NOW(), NOW()) ON DUPLICATE KEY UPDATE role = :role_update, status = 'active', joined_at = NOW(), updated_at = NOW()");
			$member->execute([':role' => $role, ':role_update' => $role, ':organization_id' => $row['organization_id'], ':user_id' => $userId]);
			$update = $this->db->prepare("UPDATE organization_invitations SET status = 'accepted', user_id = :user_id, accepted_by_user_id = :user_id_accepted, accepted_at = NOW(), updated_at = NOW() WHERE id = :id AND status = 'pending'");
			$update->execute([':user_id' => $userId, ':user_id_accepted' => $userId, ':id' => $invitationId]);
			$this->db->commit();
			return true;
		} catch (\Throwable $exception) {
			if ($this->db->inTransaction()) $this->db->rollBack();
			throw $exception;
		}
	}

	public function decline(int $invitationId, int $userId): bool
	{
		$this->db->beginTransaction();
		try {
			$update = $this->db->prepare("UPDATE organization_invitations oi INNER JOIN users u ON LOWER(u.email) = LOWER(oi.email) SET oi.status = 'declined', oi.declined_at = NOW(), oi.updated_at = NOW() WHERE oi.id = :id AND oi.status = 'pending' AND oi.expires_at > NOW() AND u.id = :user_id");
			$update->execute([':id' => $invitationId, ':user_id' => $userId]);
			if ($update->rowCount() < 1) throw new RuntimeException('Invito non valido o scaduto.');
			$member = $this->db->prepare("UPDATE organization_users ou INNER JOIN organization_invitations oi ON oi.organization_id = ou.organization_id SET ou.status = 'declined', ou.updated_at = NOW() WHERE oi.id = :id AND ou.user_id = :user_id AND ou.status = 'invited'");
			$member->execute([':id' => $invitationId, ':user_id' => $userId]);
			$this->db->commit();
			return true;
		} catch (\Throwable $exception) {
			if ($this->db->inTransaction()) $this->db->rollBack();
			throw $exception;
		}
	}

	public function findPendingForUser(int $userId): array
	{
		$stmt = $this->db->prepare("SELECT oi.*, o.name AS organization_name FROM organization_invitations oi INNER JOIN organizations o ON o.id = oi.organization_id WHERE oi.user_id = :user_id AND oi.status = 'pending' AND oi.expires_at > NOW() ORDER BY oi.created_at DESC");
		$stmt->execute([':user_id' => $userId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findPendingByIdForUser(int $invitationId, int $userId): ?array
	{
		$stmt = $this->db->prepare("SELECT oi.*, o.name AS organization_name FROM organization_invitations oi INNER JOIN organizations o ON o.id = oi.organization_id INNER JOIN users u ON LOWER(u.email) = LOWER(oi.email) WHERE oi.id = :id AND oi.status = 'pending' AND oi.expires_at > NOW() AND u.id = :user_id LIMIT 1");
		$stmt->execute([':id' => $invitationId, ':user_id' => $userId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function findForOrganization(int $organizationId): array
	{
		$stmt = $this->db->prepare("SELECT oi.*, CASE WHEN oi.status = 'pending' AND oi.expires_at <= NOW() THEN 'expired' ELSE oi.status END AS display_status, o.name AS organization_name, u.username AS invited_username, ib.username AS invited_by_username FROM organization_invitations oi INNER JOIN organizations o ON o.id = oi.organization_id LEFT JOIN users u ON u.id = oi.user_id LEFT JOIN users ib ON ib.id = oi.invited_by WHERE oi.organization_id = :organization_id ORDER BY CASE WHEN oi.status = 'pending' AND oi.expires_at > NOW() THEN 0 ELSE 1 END, oi.created_at DESC, oi.id DESC");
		$stmt->execute([':organization_id' => $organizationId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findForManagement(int $invitationId, int $organizationId): ?array
	{
		$stmt = $this->db->prepare('SELECT oi.*, o.name AS organization_name, u.username AS invited_username, u.email AS invited_user_email FROM organization_invitations oi INNER JOIN organizations o ON o.id = oi.organization_id LEFT JOIN users u ON u.id = oi.user_id WHERE oi.id = :id AND oi.organization_id = :organization_id LIMIT 1');
		$stmt->execute([':id' => $invitationId, ':organization_id' => $organizationId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function revoke(int $invitationId, int $organizationId): bool
	{
		$stmt = $this->db->prepare("UPDATE organization_invitations SET status = 'revoked', revoked_at = NOW(), updated_at = NOW() WHERE id = :id AND organization_id = :organization_id AND status = 'pending' AND expires_at > NOW()");
		$stmt->execute([':id' => $invitationId, ':organization_id' => $organizationId]);
		return $stmt->rowCount() > 0;
	}

	public function resend(int $invitationId, int $organizationId, int $invitedBy, string $tokenHash, string $expiresAt): array
	{
		$this->db->beginTransaction();
		try {
			$find = $this->db->prepare('SELECT organization_id, email, user_id, role, status, expires_at FROM organization_invitations WHERE id = :id AND organization_id = :organization_id FOR UPDATE');
			$find->execute([':id' => $invitationId, ':organization_id' => $organizationId]);
			$old = $find->fetch(PDO::FETCH_ASSOC);
			if (!$old || !in_array((string) $old['status'], ['pending', 'expired'], true)) throw new RuntimeException('Questo invito non può essere reinviato.');

			$closeStatus = ((string) $old['status'] === 'expired' || strtotime((string) $old['expires_at']) <= time()) ? 'expired' : 'revoked';
			$close = $this->db->prepare("UPDATE organization_invitations SET status = :status, revoked_at = CASE WHEN :is_revoked = 1 THEN NOW() ELSE revoked_at END, updated_at = NOW() WHERE id = :id AND status IN ('pending', 'expired')");
			$close->execute([':status' => $closeStatus, ':is_revoked' => $closeStatus === 'revoked' ? 1 : 0, ':id' => $invitationId]);

			$insert = $this->db->prepare("INSERT INTO organization_invitations (organization_id, email, user_id, role, status, token_hash, invited_by, expires_at, created_at) VALUES (:organization_id, :email, :user_id, :role, 'pending', :token_hash, :invited_by, :expires_at, NOW())");
			$insert->execute([
				':organization_id' => $old['organization_id'],
				':email' => $old['email'],
				':user_id' => $old['user_id'] ?: null,
				':role' => $old['role'],
				':token_hash' => $tokenHash,
				':invited_by' => $invitedBy,
				':expires_at' => $expiresAt,
			]);
			$newId = (int) $this->db->lastInsertId();

			$member = $this->db->prepare("UPDATE organization_users SET role = :role, status = 'invited', invited_by = :invited_by, invited_at = NOW(), joined_at = NULL, updated_at = NOW() WHERE organization_id = :organization_id AND user_id = :user_id");
			if (!empty($old['user_id'])) {
				$member->execute([':role' => $old['role'], ':invited_by' => $invitedBy, ':organization_id' => $old['organization_id'], ':user_id' => $old['user_id']]);
			}
			$this->db->commit();
			return ['id' => $newId, 'organization_id' => (int) $old['organization_id'], 'email' => (string) $old['email'], 'user_id' => $old['user_id'] ? (int) $old['user_id'] : null, 'role' => (string) $old['role']];
		} catch (\Throwable $exception) {
			if ($this->db->inTransaction()) $this->db->rollBack();
			throw $exception;
		}
	}
}
