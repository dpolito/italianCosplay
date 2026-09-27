<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use RuntimeException;

class UserInvitationRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function findUserByEmail(string $email): ?array
	{
		$stmt = $this->db->prepare('SELECT id, username, email FROM users WHERE LOWER(email) = LOWER(:email) AND anonymized_at IS NULL LIMIT 1');
		$stmt->execute([':email' => $email]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function findUserById(int $userId): ?array
	{
		$stmt = $this->db->prepare('SELECT id, username, email FROM users WHERE id = :id AND anonymized_at IS NULL LIMIT 1');
		$stmt->execute([':id' => $userId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function isBlocked(string $email): bool
	{
		$stmt = $this->db->prepare('SELECT id FROM user_invitation_blocks WHERE LOWER(email) = LOWER(:email) LIMIT 1');
		$stmt->execute([':email' => $email]);

		return (bool) $stmt->fetchColumn();
	}

	public function countSentSince(int $userId, string $since): int
	{
		$stmt = $this->db->prepare('SELECT COUNT(*) FROM user_invitations WHERE invited_by_user_id = :user_id AND created_at >= :since');
		$stmt->execute([':user_id' => $userId, ':since' => $since]);

		return (int) $stmt->fetchColumn();
	}

	public function findPendingByEmail(string $email): ?array
	{
		$stmt = $this->db->prepare("SELECT * FROM user_invitations WHERE LOWER(email) = LOWER(:email) AND status = 'pending' AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
		$stmt->execute([':email' => $email]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function create(array $data): int
	{
		$stmt = $this->db->prepare("INSERT INTO user_invitations (invited_by_user_id, invited_user_id, email, status, token_hash, expires_at, created_at) VALUES (:invited_by_user_id, :invited_user_id, :email, 'pending', :token_hash, :expires_at, NOW())");
		$stmt->execute([
			':invited_by_user_id' => $data['invited_by_user_id'],
			':invited_user_id' => $data['invited_user_id'] ?: null,
			':email' => $data['email'],
			':token_hash' => $data['token_hash'],
			':expires_at' => $data['expires_at'],
		]);

		return (int) $this->db->lastInsertId();
	}

	public function findRecentForUser(int $userId, int $limit = 20): array
	{
		$stmt = $this->db->prepare("SELECT ui.*, u.username AS invited_username FROM user_invitations ui LEFT JOIN users u ON u.id = ui.invited_user_id AND u.anonymized_at IS NULL WHERE ui.invited_by_user_id = :user_id ORDER BY ui.created_at DESC, ui.id DESC LIMIT {$limit}");
		$stmt->execute([':user_id' => $userId]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findPendingByTokenHash(string $tokenHash): ?array
	{
		$stmt = $this->db->prepare("SELECT ui.*, inviter.username AS inviter_username FROM user_invitations ui INNER JOIN users inviter ON inviter.id = ui.invited_by_user_id AND inviter.anonymized_at IS NULL WHERE ui.token_hash = :token_hash AND ui.status = 'pending' AND ui.expires_at > NOW() LIMIT 1");
		$stmt->execute([':token_hash' => $tokenHash]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function acceptForUser(int $invitationId, int $invitedUserId): bool
	{
		$this->db->beginTransaction();
		try {
			$find = $this->db->prepare("SELECT * FROM user_invitations WHERE id = :id AND status = 'pending' AND expires_at > NOW() FOR UPDATE");
			$find->execute([':id' => $invitationId]);
			$invitation = $find->fetch(PDO::FETCH_ASSOC);
			if (!$invitation) {
				throw new RuntimeException('Invito non valido o scaduto.');
			}
			if ((int) $invitation['invited_by_user_id'] === $invitedUserId) {
				throw new RuntimeException('Non puoi accettare un invito inviato da te.');
			}

			$update = $this->db->prepare("UPDATE user_invitations SET status = 'accepted', invited_user_id = :invited_user_id, accepted_at = NOW(), updated_at = NOW() WHERE id = :id AND status = 'pending'");
			$update->execute([':invited_user_id' => $invitedUserId, ':id' => $invitationId]);

			$this->createRelationship((int) $invitation['invited_by_user_id'], $invitedUserId, $invitationId);

			$this->db->commit();
			return true;
		} catch (\Throwable $exception) {
			if ($this->db->inTransaction()) {
				$this->db->rollBack();
			}
			throw $exception;
		}
	}

	public function acceptPendingForEmail(string $email, int $invitedUserId): array
	{
		$stmt = $this->db->prepare("SELECT id FROM user_invitations WHERE LOWER(email) = LOWER(:email) AND status = 'pending' AND expires_at > NOW() ORDER BY created_at ASC");
		$stmt->execute([':email' => $email]);
		$ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
		$accepted = [];

		foreach ($ids as $invitationId) {
			try {
				if ($this->acceptForUser($invitationId, $invitedUserId)) {
					$accepted[] = $invitationId;
				}
			} catch (\Throwable) {
				continue;
			}
		}

		return $accepted;
	}

	public function blockByTokenHash(string $tokenHash): ?array
	{
		$this->db->beginTransaction();
		try {
			$find = $this->db->prepare("SELECT * FROM user_invitations WHERE token_hash = :token_hash AND status = 'pending' FOR UPDATE");
			$find->execute([':token_hash' => $tokenHash]);
			$invitation = $find->fetch(PDO::FETCH_ASSOC);
			if (!$invitation) {
				$this->db->commit();
				return null;
			}

			$block = $this->db->prepare("INSERT INTO user_invitation_blocks (email, reason, created_at) VALUES (:email, 'recipient_opt_out', NOW()) ON DUPLICATE KEY UPDATE reason = VALUES(reason)");
			$block->execute([':email' => $invitation['email']]);

			$update = $this->db->prepare("UPDATE user_invitations SET status = 'blocked', blocked_at = NOW(), updated_at = NOW() WHERE LOWER(email) = LOWER(:email) AND status = 'pending'");
			$update->execute([':email' => $invitation['email']]);

			$this->db->commit();
			return $invitation;
		} catch (\Throwable $exception) {
			if ($this->db->inTransaction()) {
				$this->db->rollBack();
			}
			throw $exception;
		}
	}

	private function createRelationship(int $inviterUserId, int $invitedUserId, int $invitationId): void
	{
		$stmt = $this->db->prepare("INSERT INTO user_relationships (user_id, related_user_id, relationship_type, status, source, source_invitation_id, created_at) VALUES (:user_id, :related_user_id, 'referral', 'active', 'invitation', :source_invitation_id, NOW()) ON DUPLICATE KEY UPDATE status = 'active', source_invitation_id = VALUES(source_invitation_id), updated_at = NOW()");
		$stmt->execute([
			':user_id' => $inviterUserId,
			':related_user_id' => $invitedUserId,
			':source_invitation_id' => $invitationId,
		]);
	}
}
