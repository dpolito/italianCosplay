<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class OrganizationRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function findAll(): array
	{
		$stmt = $this->db->query("SELECT o.*, u.username AS owner_username,
			(SELECT COUNT(*) FROM organization_users ou WHERE ou.organization_id = o.id AND ou.status = 'active' AND ou.deleted_at IS NULL) AS member_count,
			(SELECT COUNT(*) FROM organization_event_masters oem WHERE oem.organization_id = o.id) AS master_count
			FROM organizations o
			LEFT JOIN users u ON u.id = o.owner_user_id
			WHERE o.deleted_at IS NULL
			ORDER BY o.created_at DESC, o.id DESC");

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findById(int $id): ?array
	{
		$stmt = $this->db->prepare("SELECT o.*, u.username AS owner_username, u.email AS owner_email
			FROM organizations o LEFT JOIN users u ON u.id = o.owner_user_id
			WHERE o.id = :id AND o.deleted_at IS NULL LIMIT 1");
		$stmt->execute([':id' => $id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) return null;
		$row['members'] = $this->findMembers($id);
		$row['masters'] = $this->findMasters($id);
		return $row;
	}

	public function findPublicBySlug(string $slug): ?array
	{
		$stmt = $this->db->prepare("SELECT o.* FROM organizations o WHERE o.slug = :slug AND o.status = 'active' AND o.is_public = 1 AND o.deleted_at IS NULL LIMIT 1");
		$stmt->execute([':slug' => $slug]);
		$organization = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$organization) return null;

		$masters = $this->db->prepare("SELECT em.id, em.nome, em.slug, em.descrizione, em.sito_web, em.social_facebook, em.social_twitter, em.social_instagram, em.social_tiktok, em.social_youtube FROM organization_event_masters oem INNER JOIN events_master em ON em.id = oem.event_master_id WHERE oem.organization_id = :organization_id AND em.status = 'active' AND em.is_public = 1 AND em.deleted_at IS NULL ORDER BY em.nome ASC");
		$masters->execute([':organization_id' => $organization['id']]);
		$organization['masters'] = $masters->fetchAll(PDO::FETCH_ASSOC);

		$events = $this->db->prepare("SELECT e.id, e.titolo, e.slug, e.data_inizio, e.data_fine, e.luogo, e.year, r.nome AS regione_nome, p.nome AS provincia_nome, c.nome AS comune_nome FROM events e LEFT JOIN regioni r ON r.id = e.regione_id LEFT JOIN province p ON p.id = e.provincia_id LEFT JOIN comuni c ON c.id = e.comune_id WHERE e.event_master_id = :event_master_id AND e.approvato = 1 AND e.deleted_at IS NULL ORDER BY e.data_inizio DESC, e.id DESC");
		foreach ($organization['masters'] as &$master) {
			$events->execute([':event_master_id' => $master['id']]);
			$master['events'] = $events->fetchAll(PDO::FETCH_ASSOC);
		}
		unset($master);
		return $organization;
	}

	public function findAllPublic(): array
	{
		$stmt = $this->db->query("SELECT id, name, slug, description, logo_path, cover_path, updated_at FROM organizations WHERE status = 'active' AND is_public = 1 AND deleted_at IS NULL ORDER BY name ASC");
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findPublicForMaster(int $masterId): array
	{
		$stmt = $this->db->prepare("SELECT DISTINCT o.id, o.name, o.slug, o.logo_path FROM organizations o INNER JOIN organization_event_masters oem ON oem.organization_id = o.id WHERE oem.event_master_id = :master_id AND o.status = 'active' AND o.is_public = 1 AND o.deleted_at IS NULL ORDER BY o.name ASC");
		$stmt->execute([':master_id' => $masterId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function hasMasterAssociation(int $masterId): bool
	{
		$stmt = $this->db->prepare('SELECT 1 FROM organization_event_masters WHERE event_master_id = :master_id LIMIT 1');
		$stmt->execute([':master_id' => $masterId]);
		return (bool) $stmt->fetchColumn();
	}

	public function findPublicForEvent(int $eventId): array
	{
		$stmt = $this->db->prepare("SELECT DISTINCT o.id, o.name, o.slug, o.logo_path FROM organizations o INNER JOIN organization_event_masters oem ON oem.organization_id = o.id INNER JOIN events e ON e.event_master_id = oem.event_master_id WHERE e.id = :event_id AND e.approvato = 1 AND e.deleted_at IS NULL AND o.status = 'active' AND o.is_public = 1 AND o.deleted_at IS NULL ORDER BY o.name ASC");
		$stmt->execute([':event_id' => $eventId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findForUser(int $userId): array
	{
		$stmt = $this->db->prepare("SELECT o.id, o.name, o.slug, o.status, o.is_public, ou.role,
			(SELECT COUNT(*) FROM organization_users members WHERE members.organization_id = o.id AND members.status = 'active' AND members.deleted_at IS NULL) AS member_count,
			(SELECT COUNT(*) FROM organization_event_masters masters WHERE masters.organization_id = o.id) AS master_count
			FROM organizations o
			INNER JOIN organization_users ou ON ou.organization_id = o.id
			WHERE ou.user_id = :user_id AND ou.status = 'active' AND ou.deleted_at IS NULL AND o.status <> 'archived' AND o.deleted_at IS NULL
			ORDER BY o.name ASC");
		$stmt->execute([':user_id' => $userId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findMasterForUser(int $masterId, int $userId): ?array
	{
		$stmt = $this->db->prepare("SELECT em.*, oem.organization_id, oem.role AS organization_master_role,
			o.name AS organization_name, ou.role AS organization_user_role
			FROM events_master em
			INNER JOIN organization_event_masters oem ON oem.event_master_id = em.id
			INNER JOIN organizations o ON o.id = oem.organization_id
			INNER JOIN organization_users ou ON ou.organization_id = o.id
			WHERE em.id = :master_id AND ou.user_id = :user_id AND ou.status = 'active' AND ou.deleted_at IS NULL AND em.deleted_at IS NULL AND o.deleted_at IS NULL
			LIMIT 1");
		$stmt->execute([':master_id' => $masterId, ':user_id' => $userId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function findMasterForOrganization(int $masterId, int $organizationId): ?array
	{
		$stmt = $this->db->prepare("SELECT em.*, oem.organization_id, oem.role AS organization_master_role, o.name AS organization_name
			FROM events_master em
			INNER JOIN organization_event_masters oem ON oem.event_master_id = em.id
			INNER JOIN organizations o ON o.id = oem.organization_id
			WHERE em.id = :master_id AND oem.organization_id = :organization_id AND em.deleted_at IS NULL AND o.deleted_at IS NULL
			LIMIT 1");
		$stmt->execute([':master_id' => $masterId, ':organization_id' => $organizationId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function findEventForUser(int $eventId, int $userId): ?array
	{
		$stmt = $this->db->prepare("SELECT e.*, em.nome AS master_name, oem.organization_id,
			o.name AS organization_name, ou.role AS organization_user_role
			FROM events e
			INNER JOIN events_master em ON em.id = e.event_master_id
			INNER JOIN organization_event_masters oem ON oem.event_master_id = em.id
			INNER JOIN organizations o ON o.id = oem.organization_id
			INNER JOIN organization_users ou ON ou.organization_id = o.id
			WHERE e.id = :event_id AND ou.user_id = :user_id AND ou.status = 'active' AND ou.deleted_at IS NULL AND e.deleted_at IS NULL AND em.deleted_at IS NULL AND o.deleted_at IS NULL
			LIMIT 1");
		$stmt->execute([':event_id' => $eventId, ':user_id' => $userId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function findEventForOrganization(int $eventId, int $organizationId): ?array
	{
		$stmt = $this->db->prepare("SELECT e.*, em.nome AS master_name, oem.organization_id,
			o.name AS organization_name
			FROM events e
			INNER JOIN events_master em ON em.id = e.event_master_id
			INNER JOIN organization_event_masters oem ON oem.event_master_id = em.id
			INNER JOIN organizations o ON o.id = oem.organization_id
			WHERE e.id = :event_id AND oem.organization_id = :organization_id AND e.deleted_at IS NULL AND em.deleted_at IS NULL AND o.deleted_at IS NULL
			LIMIT 1");
		$stmt->execute([':event_id' => $eventId, ':organization_id' => $organizationId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function findEventsForMaster(int $masterId, int $userId): array
	{
		$stmt = $this->db->prepare("SELECT DISTINCT e.*, em.nome AS master_name, oem.organization_id,
			o.name AS organization_name, ou.role AS organization_user_role
			FROM events e
			INNER JOIN events_master em ON em.id = e.event_master_id
			INNER JOIN organization_event_masters oem ON oem.event_master_id = em.id
			INNER JOIN organizations o ON o.id = oem.organization_id
			INNER JOIN organization_users ou ON ou.organization_id = o.id
			WHERE e.event_master_id = :master_id AND ou.user_id = :user_id AND ou.status = 'active' AND ou.deleted_at IS NULL AND e.deleted_at IS NULL AND em.deleted_at IS NULL AND o.deleted_at IS NULL
			ORDER BY e.data_inizio DESC, e.id DESC");
		$stmt->execute([':master_id' => $masterId, ':user_id' => $userId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function updateStatus(int $id, string $status): bool
	{
		$stmt = $this->db->prepare('UPDATE organizations SET status = :status, updated_at = NOW() WHERE id = :id AND deleted_at IS NULL');
		return $stmt->execute([':status' => $status, ':id' => $id]);
	}

	public function withdrawOrganization(int $organizationId): bool
	{
		$stmt = $this->db->prepare("UPDATE organizations SET status = 'pending_review', is_public = 0, updated_at = NOW() WHERE id = :id AND deleted_at IS NULL");
		return $stmt->execute([':id' => $organizationId]);
	}

	public function withdrawMaster(int $organizationId, int $masterId): bool
	{
		$stmt = $this->db->prepare("UPDATE events_master em INNER JOIN organization_event_masters oem ON oem.event_master_id = em.id INNER JOIN organizations o ON o.id = oem.organization_id SET em.status = 'pending_review', em.is_public = 0, em.updated_at = NOW() WHERE em.id = :master_id AND oem.organization_id = :organization_id AND em.deleted_at IS NULL AND o.deleted_at IS NULL");
		return $stmt->execute([':master_id' => $masterId, ':organization_id' => $organizationId]);
	}

	public function withdrawEvent(int $organizationId, int $eventId): bool
	{
		$stmt = $this->db->prepare("UPDATE events e INNER JOIN events_master em ON em.id = e.event_master_id INNER JOIN organization_event_masters oem ON oem.event_master_id = em.id INNER JOIN organizations o ON o.id = oem.organization_id SET e.approvato = 0, e.updated_at = NOW() WHERE e.id = :event_id AND oem.organization_id = :organization_id AND e.deleted_at IS NULL AND em.deleted_at IS NULL AND o.deleted_at IS NULL");
		return $stmt->execute([':event_id' => $eventId, ':organization_id' => $organizationId]);
	}

	public function existsByName(string $name, ?int $excludeId = null): bool
	{
		$sql = 'SELECT 1 FROM organizations WHERE name = :name AND deleted_at IS NULL';
		$params = [':name' => trim($name)];
		if ($excludeId !== null) {
			$sql .= ' AND id <> :exclude_id';
			$params[':exclude_id'] = $excludeId;
		}
		$sql .= ' LIMIT 1';
		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
		return (bool) $stmt->fetchColumn();
	}

	public function existsBySlug(string $slug, ?int $excludeId = null): bool
	{
		$sql = 'SELECT 1 FROM organizations WHERE slug = :slug AND deleted_at IS NULL';
		$params = [':slug' => $slug];
		if ($excludeId !== null) {
			$sql .= ' AND id <> :exclude_id';
			$params[':exclude_id'] = $excludeId;
		}
		$sql .= ' LIMIT 1';
		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
		return (bool) $stmt->fetchColumn();
	}

	public function create(array $data): int
	{
		$this->db->beginTransaction();
		try {
			$stmt = $this->db->prepare("INSERT INTO organizations (owner_user_id, name, slug, legal_name, description, website_url, email, phone, facebook_url, instagram_url, tiktok_url, youtube_url, status, is_public, created_at) VALUES (:owner_user_id, :name, :slug, :legal_name, :description, :website_url, :email, :phone, :facebook_url, :instagram_url, :tiktok_url, :youtube_url, :status, :is_public, NOW())");
			$stmt->execute([
				':owner_user_id' => $data['owner_user_id'],
				':name' => $data['name'],
				':slug' => $data['slug'],
				':legal_name' => $data['legal_name'] ?: null,
				':description' => $data['description'] ?: null,
				':website_url' => $data['website_url'] ?: null,
				':email' => $data['email'] ?: null,
				':phone' => $data['phone'] ?: null,
				':facebook_url' => $data['facebook_url'] ?: null,
				':instagram_url' => $data['instagram_url'] ?: null,
				':tiktok_url' => $data['tiktok_url'] ?: null,
				':youtube_url' => $data['youtube_url'] ?: null,
				':status' => $data['status'],
				':is_public' => $data['is_public'],
			]);
			$id = (int) $this->db->lastInsertId();
			$member = $this->db->prepare("INSERT INTO organization_users (organization_id, user_id, role, status, joined_at, created_at) VALUES (:organization_id, :user_id, 'owner', 'active', NOW(), NOW())");
			$member->execute([':organization_id' => $id, ':user_id' => $data['owner_user_id']]);
			$this->db->commit();
			return $id;
		} catch (\Throwable $exception) {
			if ($this->db->inTransaction()) $this->db->rollBack();
			throw $exception;
		}
	}

	public function update(int $id, array $data): bool
	{
		$stmt = $this->db->prepare("UPDATE organizations SET owner_user_id = :owner_user_id, name = :name, slug = :slug, legal_name = :legal_name, description = :description, website_url = :website_url, email = :email, phone = :phone, logo_path = :logo_path, cover_path = :cover_path, facebook_url = :facebook_url, instagram_url = :instagram_url, tiktok_url = :tiktok_url, youtube_url = :youtube_url, status = :status, is_public = :is_public, updated_at = NOW() WHERE id = :id AND deleted_at IS NULL");
		$result = $stmt->execute([':owner_user_id' => $data['owner_user_id'], ':name' => $data['name'], ':slug' => $data['slug'], ':legal_name' => $data['legal_name'] ?: null, ':description' => $data['description'] ?: null, ':website_url' => $data['website_url'] ?: null, ':email' => $data['email'] ?: null, ':phone' => $data['phone'] ?: null, ':logo_path' => $data['logo_path'] ?: null, ':cover_path' => $data['cover_path'] ?: null, ':facebook_url' => $data['facebook_url'] ?: null, ':instagram_url' => $data['instagram_url'] ?: null, ':tiktok_url' => $data['tiktok_url'] ?: null, ':youtube_url' => $data['youtube_url'] ?: null, ':status' => $data['status'], ':is_public' => $data['is_public'], ':id' => $id]);
		$member = $this->db->prepare("INSERT INTO organization_users (organization_id, user_id, role, status, joined_at, created_at) VALUES (:organization_id, :user_id, 'owner', 'active', NOW(), NOW()) ON DUPLICATE KEY UPDATE role = 'owner', status = 'active', joined_at = COALESCE(joined_at, NOW()), updated_at = NOW()");
		$member->execute([':organization_id' => $id, ':user_id' => $data['owner_user_id']]);
		return $result;
	}

	public function addMember(int $organizationId, int $userId, string $role): bool
	{
		$stmt = $this->db->prepare("INSERT INTO organization_users (organization_id, user_id, role, status, joined_at, created_at)
			VALUES (:organization_id, :user_id, :role, 'active', NOW(), NOW())
			ON DUPLICATE KEY UPDATE role = :role_update, status = 'active', joined_at = COALESCE(joined_at, NOW()), deleted_at = NULL, deleted_by = NULL, deletion_reason = NULL, updated_at = NOW()");
		return $stmt->execute([':organization_id' => $organizationId, ':user_id' => $userId, ':role' => $role, ':role_update' => $role]);
	}

	public function updateMember(int $organizationId, int $userId, string $role, string $status = 'active'): bool
	{
		$stmt = $this->db->prepare("UPDATE organization_users SET role = :role, status = :status, updated_at = NOW() WHERE organization_id = :organization_id AND user_id = :user_id AND deleted_at IS NULL");
		return $stmt->execute([':role' => $role, ':status' => $status, ':organization_id' => $organizationId, ':user_id' => $userId]);
	}

	public function delete(int $id, ?int $deletedBy = null, ?string $reason = null): bool
	{
		$stmt = $this->db->prepare("
			UPDATE organizations
			SET deleted_at = NOW(),
			    deleted_by = :deleted_by,
			    deletion_reason = :deletion_reason,
			    is_public = 0,
			    updated_at = NOW()
			WHERE id = :id
			  AND deleted_at IS NULL
		");

		return $stmt->execute([
			':id' => $id,
			':deleted_by' => $deletedBy,
			':deletion_reason' => $reason,
		]);
	}

	public function searchUsers(int $organizationId, string $query, int $limit = 10): array
	{
		$limit = max(1, min($limit, 50));
		$stmt = $this->db->prepare("SELECT id, username, email
			FROM users
			WHERE (username LIKE :username_query OR email LIKE :email_query)
			AND anonymized_at IS NULL
			AND NOT EXISTS (
				SELECT 1 FROM organization_users ou
				WHERE ou.organization_id = :organization_id AND ou.user_id = users.id AND ou.status <> 'removed' AND ou.deleted_at IS NULL
			)
			ORDER BY username ASC
			LIMIT {$limit}");
		$search = '%' . $query . '%';
		$stmt->execute([':username_query' => $search, ':email_query' => $search, ':organization_id' => $organizationId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function searchEventMasters(int $organizationId, string $query, int $limit = 10): array
	{
		$limit = max(1, min($limit, 50));
		$stmt = $this->db->prepare("SELECT em.id, em.nome, em.slug
			FROM events_master em
			WHERE em.nome LIKE :name_query
			AND em.deleted_at IS NULL
			AND NOT EXISTS (
				SELECT 1 FROM organization_event_masters oem
				WHERE oem.organization_id = :organization_id AND oem.event_master_id = em.id
			)
			ORDER BY em.nome ASC
			LIMIT {$limit}");
		$stmt->execute([':name_query' => '%' . $query . '%', ':organization_id' => $organizationId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function addMaster(int $organizationId, int $eventMasterId, string $role): bool
	{
		$stmt = $this->db->prepare("INSERT INTO organization_event_masters (organization_id, event_master_id, role, created_at)
			VALUES (:organization_id, :event_master_id, :insert_role, NOW())
			ON DUPLICATE KEY UPDATE role = :update_role, updated_at = NOW()");
		return $stmt->execute([':organization_id' => $organizationId, ':event_master_id' => $eventMasterId, ':insert_role' => $role, ':update_role' => $role]);
	}

	public function updateMaster(int $organizationId, int $eventMasterId, string $role): bool
	{
		$stmt = $this->db->prepare('UPDATE organization_event_masters SET role = :role, updated_at = NOW() WHERE organization_id = :organization_id AND event_master_id = :event_master_id');
		return $stmt->execute([':role' => $role, ':organization_id' => $organizationId, ':event_master_id' => $eventMasterId]);
	}

	public function removeMaster(int $organizationId, int $eventMasterId): bool
	{
		$stmt = $this->db->prepare('DELETE FROM organization_event_masters WHERE organization_id = :organization_id AND event_master_id = :event_master_id');
		return $stmt->execute([':organization_id' => $organizationId, ':event_master_id' => $eventMasterId]);
	}

	private function findMembers(int $id): array
	{
		$stmt = $this->db->prepare("SELECT ou.*, u.username, u.email
			FROM organization_users ou INNER JOIN users u ON u.id = ou.user_id
			WHERE ou.organization_id = :id AND ou.deleted_at IS NULL ORDER BY FIELD(ou.status, 'active', 'invited', 'removed'), ou.role, u.username");
		$stmt->execute([':id' => $id]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	private function findMasters(int $id): array
	{
		$stmt = $this->db->prepare("SELECT oem.*, em.nome, em.slug
			FROM organization_event_masters oem INNER JOIN events_master em ON em.id = oem.event_master_id
			WHERE oem.organization_id = :id AND em.deleted_at IS NULL ORDER BY em.nome ASC");
		$stmt->execute([':id' => $id]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}
