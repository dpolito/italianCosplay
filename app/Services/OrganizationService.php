<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\OrganizationRepository;
use InvalidArgumentException;

class OrganizationService
{
	private OrganizationRepository $repository;

	public function __construct()
	{
		$this->repository = new OrganizationRepository();
	}

	public function getAll(): array
	{
		return $this->repository->findAll();
	}

	public function getForUser(int $userId): array
	{
		return $this->repository->findForUser($userId);
	}

	public function findForUser(int $organizationId, int $userId): ?array
	{
		$organization = $this->repository->findById($organizationId);
		if (!$organization) return null;
		foreach ($organization['members'] as $member) {
			if ((int) $member['user_id'] === $userId && $member['status'] === 'active') return $organization;
		}
		return null;
	}

	public function findById(int $id): ?array
	{
		return $this->repository->findById($id);
	}

	public function findPublicBySlug(string $slug): ?array
	{
		return $this->repository->findPublicBySlug($slug);
	}

	public function getPublicOrganizations(): array
	{
		return $this->repository->findAllPublic();
	}

	public function findMasterForUser(int $masterId, int $userId): ?array
	{
		return $this->repository->findMasterForUser($masterId, $userId);
	}

	public function findMasterForOrganization(int $masterId, int $organizationId): ?array
	{
		return $this->repository->findMasterForOrganization($masterId, $organizationId);
	}

	public function findEventForUser(int $eventId, int $userId): ?array
	{
		return $this->repository->findEventForUser($eventId, $userId);
	}

	public function findEventForOrganization(int $eventId, int $organizationId): ?array
	{
		return $this->repository->findEventForOrganization($eventId, $organizationId);
	}

	public function findEventsForMaster(int $masterId, int $userId): array
	{
		return $this->repository->findEventsForMaster($masterId, $userId);
	}

	public function searchUsers(int $organizationId, string $query): array
	{
		$query = trim($query);
		if ($organizationId < 1 || mb_strlen($query) < 2) return [];
		return $this->repository->searchUsers($organizationId, $query);
	}

	public function searchEventMasters(int $organizationId, string $query): array
	{
		$query = trim($query);
		if ($organizationId < 1 || mb_strlen($query) < 2) return [];
		return $this->repository->searchEventMasters($organizationId, $query);
	}

	public function addMaster(int $organizationId, int $eventMasterId, string $role): bool
	{
		$this->validateMasterRole($role);
		return $this->repository->addMaster($organizationId, $eventMasterId, $role);
	}

	public function updateMaster(int $organizationId, int $eventMasterId, string $role): bool
	{
		$this->validateMasterRole($role);
		return $this->repository->updateMaster($organizationId, $eventMasterId, $role);
	}

	public function removeMaster(int $organizationId, int $eventMasterId): bool
	{
		return $this->repository->removeMaster($organizationId, $eventMasterId);
	}

	public function withdrawOrganizationForUser(int $organizationId, int $userId): bool
	{
		$organization = $this->repository->findById($organizationId);
		if (!$organization || !$this->hasManagerRole($organization, $userId)) throw new \RuntimeException('Non hai i permessi per ritirare questa organizzazione.');
		return $this->repository->withdrawOrganization($organizationId);
	}

	public function withdrawMasterForUser(int $organizationId, int $masterId, int $userId): bool
	{
		$organization = $this->repository->findById($organizationId);
		if (!$organization || !$this->hasManagerRole($organization, $userId) || !$this->repository->findMasterForOrganization($masterId, $organizationId)) throw new \RuntimeException('Non hai i permessi per ritirare questo master.');
		return $this->repository->withdrawMaster($organizationId, $masterId);
	}

	public function withdrawEventForUser(int $organizationId, int $eventId, int $userId): bool
	{
		$organization = $this->repository->findById($organizationId);
		if (!$organization || !$this->hasManagerRole($organization, $userId) || !$this->repository->findEventForOrganization($eventId, $organizationId)) throw new \RuntimeException('Non hai i permessi per ritirare questa edizione.');
		return $this->repository->withdrawEvent($organizationId, $eventId);
	}

	private function validateMasterRole(string $role): void
	{
		if (!in_array($role, ['organizer', 'co_organizer'], true)) throw new InvalidArgumentException('Ruolo master non valido.');
	}

	private function hasManagerRole(array $organization, int $userId): bool
	{
		foreach ($organization['members'] ?? [] as $member) {
			if ((int) ($member['user_id'] ?? 0) === $userId && in_array((string) ($member['role'] ?? ''), ['owner', 'admin'], true) && ($member['status'] ?? '') === 'active') return true;
		}
		return false;
	}

	public function updateStatus(int $id, string $status): bool
	{
		$allowed = ['draft', 'pending_review', 'active', 'suspended', 'archived'];
		if (!in_array($status, $allowed, true)) {
			throw new InvalidArgumentException('Stato organizzazione non valido.');
		}

		return $this->repository->updateStatus($id, $status);
	}

	public function save(array $data, ?int $id = null): int|bool
	{
		$name = trim((string) ($data['name'] ?? ''));
		$ownerId = (int) ($data['owner_user_id'] ?? 0);
		$current = $id !== null ? $this->repository->findById($id) : null;
		if ($id !== null && $ownerId < 1) {
			$ownerId = (int) ($current['owner_user_id'] ?? 0);
		}
		if (mb_strlen($name) < 2 || mb_strlen($name) > 180 || $ownerId < 1) throw new InvalidArgumentException('Nome e owner sono obbligatori.');
		if ($this->repository->existsByName($name, $id)) {
			throw new InvalidArgumentException("Esiste gia un'organizzazione con questo nome. Cerca quella esistente invece di crearne una nuova.");
		}
		$slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $name), '-')) ?: 'organizzazione';
		if ($this->repository->existsBySlug($slug, $id)) {
			throw new InvalidArgumentException("Esiste gia un'organizzazione con un nome molto simile. Cerca quella esistente invece di crearne una nuova.");
		}
		$data['owner_user_id'] = $ownerId;
		$data['name'] = $name;
		$data['slug'] = $slug;
		foreach (['legal_name', 'phone', 'logo_path', 'cover_path', 'facebook_url', 'instagram_url', 'tiktok_url', 'youtube_url'] as $field) {
			$data[$field] = $data[$field] ?? ($current[$field] ?? '');
		}
		$data['status'] = $data['status'] ?? ($id !== null ? (($current['status'] ?? 'draft')) : 'draft');
		$data['is_public'] = !empty($data['is_public']) ? 1 : 0;
		return $id === null ? $this->repository->create($data) : $this->repository->update($id, $data);
	}

	public function addMember(int $organizationId, int $userId, string $role, int $invitedBy = 0, bool $staffOverride = false): bool
	{
		if (!in_array($role, ['admin', 'editor', 'viewer'], true)) throw new InvalidArgumentException('Ruolo membro non valido.');
		if ($invitedBy < 1) throw new InvalidArgumentException('Autore dell’invito non valido.');
		$invitationRepository = new \App\Repositories\OrganizationInvitationRepository();
		$user = $invitationRepository->findUserById($userId);
		if (!$user) throw new InvalidArgumentException('Utente non trovato.');
		(new OrganizationInvitationService())->invite($organizationId, $invitedBy, (string) $user['email'], $role, $staffOverride);
		return true;
	}

	public function updateMember(int $organizationId, int $userId, string $role, string $status): bool
	{
		if (!in_array($role, ['admin', 'editor', 'viewer'], true) || !in_array($status, ['active', 'removed'], true)) throw new InvalidArgumentException('Dati membro non validi.');
		$organization = $this->repository->findById($organizationId);
		if (!$organization || (int) $organization['owner_user_id'] === $userId) throw new InvalidArgumentException('L’owner non può essere modificato da questa sezione.');
		return $this->repository->updateMember($organizationId, $userId, $role, $status);
	}
}
