<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Mailer;
use App\Repositories\EventMasterClaimRepository;
use App\Services\AuditLogService;
use App\Services\TelegramNotificationService;
use App\Support\AuditLogActionType;
use RuntimeException;

class EventMasterClaimService
{
	private EventMasterClaimRepository $repository;
	private AuditLogService $auditLogService;

	public function __construct()
	{
		$this->repository = new EventMasterClaimRepository();
		$this->auditLogService = new AuditLogService();
	}

	public function getPendingClaims(): array
	{
		return $this->repository->findAllPending();
	}

	public function findById(int $id): ?array
	{
		return $this->repository->findById($id);
	}

	public function getClaimsForUser(int $userId): array
	{
		return $this->repository->findForUser($userId);
	}

	public function getOrganizationsForUser(int $userId): array
	{
		return $this->repository->findOrganizationsForUser($userId);
	}

	public function searchOrganizationsForClaim(int $userId, int $eventMasterId, string $query): array
	{
		$query = trim($query);
		if (mb_strlen($query) < 2) return [];
		if ($this->repository->hasMasterAssociation($eventMasterId)) return [];
		return $this->repository->searchOrganizationsForClaim($userId, $eventMasterId, $query);
	}

	public function hasMasterAssociation(int $eventMasterId): bool
	{
		return $this->repository->hasMasterAssociation($eventMasterId);
	}

	public function createClaim(int $userId, int $eventMasterId, ?int $organizationId, string $organizationName, string $evidence, bool $privacyAccepted): array
	{
		if ($this->repository->hasMasterAssociation($eventMasterId)) {
			throw new RuntimeException('Questo master è già associato a un’organizzazione.');
		}

		if (!$privacyAccepted) {
			throw new RuntimeException('Devi confermare di aver letto l’informativa privacy.');
		}
		if ($organizationId === null && mb_strlen($organizationName) < 2) {
			throw new RuntimeException('Inserisci il nome dell’organizzazione.');
		}
		if (mb_strlen($evidence) > 5000) {
			throw new RuntimeException('La descrizione delle prove è troppo lunga.');
		}

		$result = $this->repository->createClaim($userId, $eventMasterId, $organizationId, trim($organizationName), trim($evidence));
		$claim = $this->repository->findById((int) $result['id']);
		if (!$claim) {
			throw new RuntimeException('Richiesta creata ma non recuperabile.');
		}
		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::EVENT_MASTER_CLAIM_REQUESTED,
			'entity_type' => 'event_master_claim',
			'entity_id' => (int) $claim['id'],
			'payload' => [
				'event_master_id' => $eventMasterId,
				'organization_id' => (int) $claim['organization_id'],
				'privacy_policy_acknowledged' => true,
			],
		]);

		$this->notify($claim);
		return $claim;
	}

	private function notify(array $claim): void
	{
		$name = htmlspecialchars((string) $claim['requester_username'], ENT_QUOTES, 'UTF-8');
		$master = htmlspecialchars((string) $claim['event_master_name'], ENT_QUOTES, 'UTF-8');
		$organization = htmlspecialchars((string) $claim['organization_name'], ENT_QUOTES, 'UTF-8');
		$html = "<p>Ciao {$name},</p><p>abbiamo ricevuto la tua richiesta di gestione di <strong>{$master}</strong> per l’organizzazione <strong>{$organization}</strong>.</p><p>La richiesta sarà verificata dallo staff di ItalianCosplay.</p>";
		(new Mailer())->send((string) $claim['requester_email'], (string) $claim['requester_username'], 'Richiesta di riscatto ricevuta', $html, null, [Mailer::TAG_EVENT_CLAIM]);

		$botToken = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
		$chatId = defined('TELEGRAM_CHAT_ID') ? TELEGRAM_CHAT_ID : '';
		if (is_string($botToken) && is_string($chatId) && trim($botToken) !== '' && trim($chatId) !== '') {
			$message = "<b>Nuova richiesta di riscatto</b>\nMaster: " . $master . "\nOrganizzazione: " . $organization . "\nUtente: " . $name . "\nEmail: " . htmlspecialchars((string) $claim['requester_email'], ENT_QUOTES, 'UTF-8');
			(new TelegramNotificationService($botToken, $chatId))->sendMessage($message);
		}
	}

	public function approve(int $id, int $reviewedBy): array
	{
		return $this->repository->approve($id, $reviewedBy);
	}

	public function reject(int $id, int $reviewedBy, ?string $reviewNotes = null): array
	{
		return $this->repository->reject($id, $reviewedBy, $reviewNotes);
	}
}
