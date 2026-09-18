<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Mailer;
use App\Repositories\OrganizationInvitationRepository;
use App\Services\NotificationService;
use App\Support\AuditLogActionType;
use InvalidArgumentException;
use RuntimeException;

class OrganizationInvitationService
{
	private OrganizationInvitationRepository $repository;
	private AuditLogService $auditLogService;
	private NotificationService $notificationService;

	public function __construct()
	{
		$this->repository = new OrganizationInvitationRepository();
		$this->auditLogService = new AuditLogService();
		$this->notificationService = new NotificationService();
	}

	public function invite(int $organizationId, int $invitedBy, string $email, string $role, bool $staffOverride = false): array
	{
		$email = strtolower(trim($email));
		$this->validateRole($role);
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Inserisci un indirizzo email valido.');
		$inviterRole = $this->repository->getOrganizationMemberRole($organizationId, $invitedBy);
		if (!$staffOverride && (!$inviterRole || !in_array($inviterRole, ['owner', 'admin'], true))) throw new RuntimeException('Non hai i permessi per invitare membri.');
		if (!$staffOverride && $inviterRole === 'admin' && $role === 'admin') throw new RuntimeException('Solo l’owner può invitare un amministratore.');
		$user = $this->repository->findUserByEmail($email);
		if ($user && (int) $user['id'] === $invitedBy) throw new RuntimeException('Non puoi invitare te stesso.');
		if ($user) {
			$member = $this->repository->getMember($organizationId, (int) $user['id']);
			if ($member && $member['status'] === 'active') throw new RuntimeException('Questo utente è già membro dell’organizzazione.');
		}
		if ($this->repository->findPendingByOrganizationEmail($organizationId, $email)) throw new RuntimeException('Esiste già un invito pendente per questa email.');
		$token = bin2hex(random_bytes(32));
		$invitationId = $this->repository->create([
			'organization_id' => $organizationId,
			'email' => $email,
			'user_id' => $user['id'] ?? null,
			'role' => $role,
			'token_hash' => hash('sha256', $token),
			'invited_by' => $invitedBy,
			'expires_at' => date('Y-m-d H:i:s', time() + 604800),
		]);
		$this->auditLogService->logAudit(['user_id' => $invitedBy, 'action_type' => AuditLogActionType::ORGANIZATION_INVITATION_CREATED, 'entity_type' => 'organization_invitation', 'entity_id' => $invitationId, 'payload' => ['organization_id' => $organizationId, 'email' => $email, 'role' => $role, 'registered_user' => (bool) $user]]);
		$this->notify($email, (string) ($user['username'] ?? $email), $organizationId, $invitationId, $role, $token, $user ? (int) $user['id'] : null);
		return ['id' => $invitationId, 'email' => $email, 'role' => $role, 'status' => 'pending'];
	}

	public function getPendingForUser(int $userId): array
	{
		return $this->repository->findPendingForUser($userId);
	}

	public function getForOrganization(int $organizationId, int $userId): array
	{
		$this->assertManager($organizationId, $userId);
		return $this->repository->findForOrganization($organizationId);
	}

	public function revoke(int $organizationId, int $invitationId, int $userId): bool
	{
		$this->assertManager($organizationId, $userId);
		$invitation = $this->repository->findForManagement($invitationId, $organizationId);
		if (!$invitation || (string) $invitation['status'] !== 'pending' || strtotime((string) $invitation['expires_at']) <= time()) throw new RuntimeException('Questo invito non è più pendente.');
		if (!$this->repository->revoke($invitationId, $organizationId)) throw new RuntimeException('Invito non revocato.');
		$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::ORGANIZATION_INVITATION_REVOKED, 'entity_type' => 'organization_invitation', 'entity_id' => $invitationId, 'payload' => ['organization_id' => $organizationId, 'email' => $invitation['email'], 'source' => 'dashboard']]);
		return true;
	}

	public function resend(int $organizationId, int $invitationId, int $userId): array
	{
		$managerRole = $this->assertManager($organizationId, $userId);
		$invitation = $this->repository->findForManagement($invitationId, $organizationId);
		if (!$invitation || !in_array((string) $invitation['status'], ['pending', 'expired'], true)) throw new RuntimeException('Questo invito non può essere reinviato.');
		if ($managerRole === 'admin' && (string) $invitation['role'] === 'admin') throw new RuntimeException('Solo l’owner può reinviare un invito amministratore.');
		$token = bin2hex(random_bytes(32));
		$newInvitation = $this->repository->resend($invitationId, $organizationId, $userId, hash('sha256', $token), date('Y-m-d H:i:s', time() + 604800));
		$recipient = $this->repository->findUserByEmail((string) $newInvitation['email']);
		$this->notify((string) $newInvitation['email'], (string) ($recipient['username'] ?? $newInvitation['email']), $organizationId, (int) $newInvitation['id'], (string) $newInvitation['role'], $token, $recipient ? (int) $recipient['id'] : null);
		$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::ORGANIZATION_INVITATION_RESENT, 'entity_type' => 'organization_invitation', 'entity_id' => (int) $newInvitation['id'], 'payload' => ['organization_id' => $organizationId, 'previous_invitation_id' => $invitationId, 'email' => $newInvitation['email'], 'role' => $newInvitation['role'], 'source' => 'dashboard']]);
		return $newInvitation;
	}

	public function findForUserByToken(string $token, int $userId): ?array
	{
		$invitation = $this->repository->findPendingByTokenHash(hash('sha256', trim($token)));
		if (!$invitation) return null;
		$user = $this->repository->findUserByEmail((string) $invitation['email']);
		if (!$user || (int) $user['id'] !== $userId) return null;
		return $invitation;
	}

	public function accept(string $token, int $userId): bool
	{
		$invitation = $this->repository->findPendingByTokenHash(hash('sha256', trim($token)));
		$user = $invitation ? $this->repository->findUserByEmail((string) $invitation['email']) : null;
		if (!$invitation || !$user || (int) $user['id'] !== $userId) throw new RuntimeException('Invito non valido o non associato al tuo account.');
		$result = $this->repository->accept((int) $invitation['id'], $userId, (string) $invitation['role']);
		$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::ORGANIZATION_INVITATION_ACCEPTED, 'entity_type' => 'organization_invitation', 'entity_id' => (int) $invitation['id'], 'payload' => ['organization_id' => (int) $invitation['organization_id'], 'role' => $invitation['role']]]);
		return $result;
	}

	public function decline(string $token, int $userId): bool
	{
		$invitation = $this->repository->findPendingByTokenHash(hash('sha256', trim($token)));
		$user = $invitation ? $this->repository->findUserByEmail((string) $invitation['email']) : null;
		if (!$invitation || !$user || (int) $user['id'] !== $userId) throw new RuntimeException('Invito non valido o non associato al tuo account.');
		$result = $this->repository->decline((int) $invitation['id'], $userId);
		$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::ORGANIZATION_INVITATION_DECLINED, 'entity_type' => 'organization_invitation', 'entity_id' => (int) $invitation['id'], 'payload' => ['organization_id' => (int) $invitation['organization_id']]]);
		return $result;
	}

	public function acceptById(int $invitationId, int $userId): bool
	{
		$invitation = $this->repository->findPendingByIdForUser($invitationId, $userId);
		if (!$invitation) throw new RuntimeException('Invito non valido, scaduto o non associato al tuo account.');
		$result = $this->repository->accept($invitationId, $userId, (string) $invitation['role']);
		$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::ORGANIZATION_INVITATION_ACCEPTED, 'entity_type' => 'organization_invitation', 'entity_id' => $invitationId, 'payload' => ['organization_id' => (int) $invitation['organization_id'], 'role' => $invitation['role'], 'source' => 'dashboard']]);
		return $result;
	}

	public function declineById(int $invitationId, int $userId): bool
	{
		$invitation = $this->repository->findPendingByIdForUser($invitationId, $userId);
		if (!$invitation) throw new RuntimeException('Invito non valido, scaduto o non associato al tuo account.');
		$result = $this->repository->decline($invitationId, $userId);
		$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::ORGANIZATION_INVITATION_DECLINED, 'entity_type' => 'organization_invitation', 'entity_id' => $invitationId, 'payload' => ['organization_id' => (int) $invitation['organization_id'], 'source' => 'dashboard']]);
		return $result;
	}

	private function validateRole(string $role): void
	{
		if (!in_array($role, ['admin', 'editor', 'viewer'], true)) throw new InvalidArgumentException('Ruolo invito non valido.');
	}

	private function assertManager(int $organizationId, int $userId): string
	{
		$role = $this->repository->getOrganizationMemberRole($organizationId, $userId);
		if (!in_array($role, ['owner', 'admin'], true)) throw new RuntimeException('Non hai i permessi per gestire gli inviti.');
		return $role;
	}

	private function notify(string $email, string $name, int $organizationId, int $invitationId, string $role, string $token, ?int $userId): void
	{
		$baseUrl = defined('URL_ROOT_SITE') ? rtrim(URL_ROOT_SITE, '/') : '';
		$link = $baseUrl . '/dashboard/organization-invitations/accept?token=' . rawurlencode($token);
		$safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
		$safeRole = htmlspecialchars($role, ENT_QUOTES, 'UTF-8');
		$html = "<p>Ciao {$safeName},</p><p>Sei stato invitato a partecipare a un'organizzazione su ItalianCosplay con il ruolo di <strong>{$safeRole}</strong>.</p><p><a href=\"{$link}\">Visualizza e rispondi all'invito</a></p><p>L'invito scade tra 7 giorni.</p>";
		(new Mailer())->send($email, $name, 'Invito a partecipare a un’organizzazione', $html, null, [Mailer::TAG_ORGANIZATION_INVITATION]);
		if ($userId) $this->notificationService->createNotification(['user_id' => $userId, 'notification_type' => 'organization_invitation', 'title' => 'Nuovo invito organizzazione', 'message' => 'Hai ricevuto un invito a partecipare a un’organizzazione.', 'source_entity_type' => 'organization_invitation', 'source_entity_id' => $invitationId, 'payload' => ['organization_id' => $organizationId]]);
	}
}
