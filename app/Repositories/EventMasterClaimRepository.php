<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use RuntimeException;

class EventMasterClaimRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function findAllPending(): array
	{
		$stmt = $this->db->query($this->baseSelect() . "
			WHERE c.status = 'pending'
			ORDER BY c.created_at ASC, c.id ASC
		");

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findById(int $id): ?array
	{
		$stmt = $this->db->prepare($this->baseSelect() . ' WHERE c.id = :id LIMIT 1');
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function findForUser(int $userId): array
	{
		$stmt = $this->db->prepare($this->baseSelect() . ' WHERE c.requested_by = :user_id ORDER BY c.created_at DESC, c.id DESC');
		$stmt->execute([':user_id' => $userId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findOrganizationsForUser(int $userId): array
	{
		$stmt = $this->db->prepare("SELECT o.id, o.name
			FROM organizations o
			INNER JOIN organization_users ou ON ou.organization_id = o.id
			WHERE ou.user_id = :user_id AND ou.status = 'active'
				AND ou.role IN ('owner', 'admin') AND o.status <> 'archived'
			ORDER BY o.name ASC");
		$stmt->execute([':user_id' => $userId]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function searchOrganizationsForClaim(int $userId, int $eventMasterId, string $query, int $limit = 10): array
	{
		$limit = max(1, min($limit, 50));
		$stmt = $this->db->prepare("SELECT o.id, o.name
			FROM organizations o
			WHERE (o.status = 'active' AND o.is_public = 1 OR EXISTS (
				SELECT 1 FROM organization_users ou
				WHERE ou.organization_id = o.id AND ou.user_id = :user_id
				AND ou.status = 'active' AND ou.role IN ('owner', 'admin')
			))
			AND o.name LIKE :name_query
			AND NOT EXISTS (
				SELECT 1 FROM organization_event_masters oem
				WHERE oem.organization_id = o.id AND oem.event_master_id = :event_master_id
			)
			ORDER BY o.name ASC
			LIMIT {$limit}");
		$stmt->execute([':user_id' => $userId, ':name_query' => '%' . $query . '%', ':event_master_id' => $eventMasterId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function hasMasterAssociation(int $eventMasterId): bool
	{
		$stmt = $this->db->prepare('SELECT 1 FROM organization_event_masters WHERE event_master_id = :event_master_id LIMIT 1');
		$stmt->execute([':event_master_id' => $eventMasterId]);
		return (bool) $stmt->fetchColumn();
	}

	public function createClaim(int $userId, int $eventMasterId, ?int $organizationId, string $organizationName, string $evidence): array
	{
		$this->db->beginTransaction();

		try {
			if ($this->hasMasterAssociation($eventMasterId)) {
				throw new RuntimeException('Questo master è già associato a un’organizzazione.');
			}

			if ($organizationId !== null) {
				$organization = $this->db->prepare("SELECT o.id, o.name
					FROM organizations o
					WHERE o.id = :organization_id
						AND (o.status = 'active' AND o.is_public = 1 OR EXISTS (
							SELECT 1 FROM organization_users ou
							WHERE ou.organization_id = o.id AND ou.user_id = :user_id
								AND ou.status = 'active' AND ou.role IN ('owner', 'admin')
						))
					LIMIT 1");
				$organization->execute([':organization_id' => $organizationId, ':user_id' => $userId]);
				$organizationRow = $organization->fetch(PDO::FETCH_ASSOC);
				if (!$organizationRow) {
					throw new RuntimeException('Organizzazione non valida.');
				}

				$linked = $this->db->prepare('SELECT 1 FROM organization_event_masters WHERE organization_id = :organization_id AND event_master_id = :event_master_id LIMIT 1');
				$linked->execute([':organization_id' => $organizationId, ':event_master_id' => $eventMasterId]);
				if ($linked->fetchColumn()) {
					throw new RuntimeException('Questo master è già associato all’organizzazione.');
				}

				$pending = $this->db->prepare("SELECT 1 FROM event_master_claims WHERE organization_id = :organization_id AND event_master_id = :event_master_id AND status = 'pending' LIMIT 1");
				$pending->execute([':organization_id' => $organizationId, ':event_master_id' => $eventMasterId]);
				if ($pending->fetchColumn()) {
					throw new RuntimeException('Esiste già una richiesta pendente per questo master e questa organizzazione.');
				}
			} else {
				$slug = $this->uniqueOrganizationSlug($organizationName);
				$organization = $this->db->prepare("INSERT INTO organizations
					(owner_user_id, name, slug, status, is_public, created_at)
					VALUES (:owner_user_id, :name, :slug, 'pending_review', 0, NOW())");
				$organization->execute([
					':owner_user_id' => $userId,
					':name' => $organizationName,
					':slug' => $slug,
				]);
				$organizationId = (int) $this->db->lastInsertId();

				$member = $this->db->prepare("INSERT INTO organization_users
					(organization_id, user_id, role, status, joined_at, created_at)
					VALUES (:organization_id, :user_id, 'owner', 'active', NOW(), NOW())");
				$member->execute([':organization_id' => $organizationId, ':user_id' => $userId]);
			}

			$claim = $this->db->prepare("INSERT INTO event_master_claims
				(event_master_id, organization_id, requested_by, role, status, evidence, created_at)
				VALUES (:event_master_id, :organization_id, :requested_by, 'organizer', 'pending', :evidence, NOW())");
			$claim->execute([
				':event_master_id' => $eventMasterId,
				':organization_id' => $organizationId,
				':requested_by' => $userId,
				':evidence' => $evidence !== '' ? $evidence : null,
			]);
			$claimId = (int) $this->db->lastInsertId();

			$this->db->commit();
			return ['id' => $claimId, 'organization_id' => $organizationId];
		} catch (\Throwable $exception) {
			$this->db->rollBack();
			throw $exception;
		}
	}

	private function uniqueOrganizationSlug(string $name): string
	{
		$base = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $name), '-')) ?: 'organizzazione';
		$slug = $base;
		$counter = 1;
		$stmt = $this->db->prepare('SELECT COUNT(*) FROM organizations WHERE slug = :slug');
		while (true) {
			$stmt->execute([':slug' => $slug]);
			if ((int) $stmt->fetchColumn() === 0) {
				return $slug;
			}
			$slug = $base . '-' . $counter++;
		}
	}

	public function approve(int $id, int $reviewedBy): array
	{
		$this->db->beginTransaction();

		try {
			$claim = $this->lockPendingClaim($id);
			if (!$claim) {
				throw new RuntimeException('La richiesta non è più pendente o non esiste.');
			}

			$insert = $this->db->prepare("INSERT INTO organization_event_masters
				(organization_id, event_master_id, role)
				VALUES (:organization_id, :event_master_id, :insert_role)
				ON DUPLICATE KEY UPDATE role = :update_role, updated_at = NOW()");
			$insert->execute([
				':organization_id' => $claim['organization_id'],
				':event_master_id' => $claim['event_master_id'],
				':insert_role' => $claim['role'],
				':update_role' => $claim['role'],
			]);

			$requestedBy = (int) $claim['requested_by'];
			$organizationId = (int) $claim['organization_id'];
			$ownerUserId = (int) ($claim['organization_owner_user_id'] ?? 0);
			$isStaffOwnedPlaceholder = (int) ($claim['organization_owner_can_manage_events'] ?? 0) === 1;
			$memberRole = $isStaffOwnedPlaceholder ? 'owner' : 'admin';

			if ($isStaffOwnedPlaceholder && $ownerUserId > 0 && $ownerUserId !== $requestedBy) {
				$transfer = $this->db->prepare('UPDATE organizations SET owner_user_id = :requested_by, updated_at = NOW() WHERE id = :organization_id');
				$transfer->execute([
					':requested_by' => $requestedBy,
					':organization_id' => $organizationId,
				]);

				$previousOwner = $this->db->prepare("UPDATE organization_users
					SET status = 'removed', updated_at = NOW()
					WHERE organization_id = :organization_id AND user_id = :owner_user_id AND role = 'owner'");
				$previousOwner->execute([
					':organization_id' => $organizationId,
					':owner_user_id' => $ownerUserId,
				]);
			}

			$member = $this->db->prepare("INSERT INTO organization_users
				(organization_id, user_id, role, status, joined_at, created_at)
				VALUES (:organization_id, :user_id, :role, 'active', NOW(), NOW())
				ON DUPLICATE KEY UPDATE role = IF(role = 'owner', 'owner', :role_update), status = 'active', joined_at = COALESCE(joined_at, NOW()), updated_at = NOW()");
			$member->execute([
				':organization_id' => $organizationId,
				':user_id' => $requestedBy,
				':role' => $memberRole,
				':role_update' => $memberRole,
			]);

			$update = $this->db->prepare("UPDATE event_master_claims
				SET status = 'approved', reviewed_by = :reviewed_by, reviewed_at = NOW(), updated_at = NOW()
				WHERE id = :id AND status = 'pending'");
			$update->execute([':reviewed_by' => $reviewedBy, ':id' => $id]);

			$this->db->commit();
			return $claim;
		} catch (\Throwable $exception) {
			$this->db->rollBack();
			throw $exception;
		}
	}

	public function reject(int $id, int $reviewedBy, ?string $reviewNotes = null): array
	{
		$this->db->beginTransaction();

		try {
			$claim = $this->lockPendingClaim($id);
			if (!$claim) {
				throw new RuntimeException('La richiesta non è più pendente o non esiste.');
			}

			$update = $this->db->prepare("UPDATE event_master_claims
				SET status = 'rejected', reviewed_by = :reviewed_by, reviewed_at = NOW(),
					review_notes = :review_notes, updated_at = NOW()
				WHERE id = :id AND status = 'pending'");
			$update->execute([
				':reviewed_by' => $reviewedBy,
				':review_notes' => $reviewNotes,
				':id' => $id,
			]);

			$this->db->commit();
			return $claim;
		} catch (\Throwable $exception) {
			$this->db->rollBack();
			throw $exception;
		}
	}

	private function lockPendingClaim(int $id): ?array
	{
		$stmt = $this->db->prepare($this->baseSelect() . ' WHERE c.id = :id AND c.status = \'pending\' FOR UPDATE');
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	private function baseSelect(): string
	{
		return "SELECT c.*, em.nome AS event_master_name, em.slug AS event_master_slug,
			o.name AS organization_name, o.owner_user_id AS organization_owner_user_id,
			EXISTS (
				SELECT 1
				FROM users owner_user
				INNER JOIN role_permissions owner_rp ON owner_rp.role_id = owner_user.role_id
				INNER JOIN permissions owner_permission ON owner_permission.id = owner_rp.permission_id
				WHERE owner_user.id = o.owner_user_id AND owner_permission.name = 'manage_events'
			) AS organization_owner_can_manage_events,
			u.username AS requester_username, u.email AS requester_email,
			r.username AS reviewer_username
		FROM event_master_claims c
		INNER JOIN events_master em ON em.id = c.event_master_id
		INNER JOIN organizations o ON o.id = c.organization_id
		INNER JOIN users u ON u.id = c.requested_by
		LEFT JOIN users r ON r.id = c.reviewed_by";
	}
}
