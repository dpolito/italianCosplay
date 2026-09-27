<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Mailer;
use App\Repositories\UserInvitationRepository;
use App\Support\AuditLogActionType;
use InvalidArgumentException;
use RuntimeException;

class UserInvitationService
{
	private const DAILY_LIMIT = 5;
	private const EXPIRATION_DAYS = 30;

	private UserInvitationRepository $repository;
	private AuditLogService $auditLogService;
	private NotificationService $notificationService;

	public function __construct()
	{
		$this->repository = new UserInvitationRepository();
		$this->auditLogService = new AuditLogService();
		$this->notificationService = new NotificationService();
	}

	public function invite(int $invitedByUserId, string $email): array
	{
		$email = strtolower(trim($email));
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			throw new InvalidArgumentException('Inserisci un indirizzo email valido.');
		}

		$inviter = $this->repository->findUserById($invitedByUserId);
		if (!$inviter) {
			throw new RuntimeException('Utente non valido.');
		}
		if ($this->repository->isBlocked($email)) {
			throw new RuntimeException('Questo indirizzo ha scelto di non ricevere altri inviti.');
		}
		if ($this->repository->countSentSince($invitedByUserId, date('Y-m-d H:i:s', time() - 86400)) >= self::DAILY_LIMIT) {
			throw new RuntimeException('Hai raggiunto il limite giornaliero di inviti.');
		}

		$existingUser = $this->repository->findUserByEmail($email);
		if ($existingUser && (int) $existingUser['id'] === $invitedByUserId) {
			throw new RuntimeException('Non puoi invitare te stesso.');
		}
		if ($this->repository->findPendingByEmail($email)) {
			throw new RuntimeException('Esiste già un invito pendente per questa email.');
		}

		$token = bin2hex(random_bytes(32));
		$invitationId = $this->repository->create([
			'invited_by_user_id' => $invitedByUserId,
			'invited_user_id' => $existingUser['id'] ?? null,
			'email' => $email,
			'token_hash' => hash('sha256', $token),
			'expires_at' => date('Y-m-d H:i:s', time() + (self::EXPIRATION_DAYS * 86400)),
		]);

		$this->auditLogService->logAudit([
			'user_id' => $invitedByUserId,
			'action_type' => AuditLogActionType::USER_INVITATION_CREATED,
			'entity_type' => 'user_invitation',
			'entity_id' => $invitationId,
			'payload' => [
				'email' => $email,
				'registered_user' => (bool) $existingUser,
			],
		]);

		if ($existingUser) {
			$this->sendInvitationNotification((int) $existingUser['id'], $invitationId, (string) $inviter['username'], $token);
		} else {
			$this->sendInvitationEmail($email, $email, (string) $inviter['username'], $token);
		}

		return ['id' => $invitationId, 'email' => $email, 'status' => 'pending'];
	}

	public function getRecentForUser(int $userId): array
	{
		return $this->repository->findRecentForUser($userId);
	}

	public function getDailyUsage(int $userId): array
	{
		$sent = $this->repository->countSentSince($userId, date('Y-m-d H:i:s', time() - 86400));

		return [
			'sent' => $sent,
			'limit' => self::DAILY_LIMIT,
			'remaining' => max(0, self::DAILY_LIMIT - $sent),
		];
	}

	public function findPendingByToken(string $token): ?array
	{
		$token = trim($token);
		if ($token === '') {
			return null;
		}

		return $this->repository->findPendingByTokenHash(hash('sha256', $token));
	}

	public function acceptByToken(string $token, int $userId): bool
	{
		$invitation = $this->repository->findPendingByTokenHash(hash('sha256', trim($token)));
		if (!$invitation) {
			throw new RuntimeException('Invito non valido o scaduto.');
		}
		$user = $this->repository->findUserById($userId);
		if (!$user || strtolower((string) $user['email']) !== strtolower((string) $invitation['email'])) {
			throw new RuntimeException('Questo invito è associato a un altro indirizzo email.');
		}

		$result = $this->repository->acceptForUser((int) $invitation['id'], $userId);
		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::USER_INVITATION_ACCEPTED,
			'entity_type' => 'user_invitation',
			'entity_id' => (int) $invitation['id'],
			'payload' => [
				'invited_by_user_id' => (int) $invitation['invited_by_user_id'],
				'source' => 'dashboard',
			],
		]);

		return $result;
	}

	public function acceptPendingForRegisteredUser(int $userId, string $email): array
	{
		$acceptedIds = $this->repository->acceptPendingForEmail($email, $userId);
		foreach ($acceptedIds as $invitationId) {
			$this->auditLogService->logAudit([
				'user_id' => $userId,
				'action_type' => AuditLogActionType::USER_INVITATION_ACCEPTED,
				'entity_type' => 'user_invitation',
				'entity_id' => $invitationId,
				'payload' => ['source' => 'registration'],
			]);
		}

		return $acceptedIds;
	}

	public function blockByToken(string $token): bool
	{
		$invitation = $this->repository->blockByTokenHash(hash('sha256', trim($token)));
		if (!$invitation) {
			return false;
		}
		$this->auditLogService->logAudit([
			'user_id' => null,
			'action_type' => AuditLogActionType::USER_INVITATION_BLOCKED,
			'entity_type' => 'user_invitation',
			'entity_id' => (int) $invitation['id'],
			'payload' => ['email' => $invitation['email']],
		]);

		return true;
	}

	private function sendInvitationEmail(string $email, string $name, string $inviterName, string $token): void
	{
		$baseUrl = defined('URL_ROOT_SITE') ? rtrim(URL_ROOT_SITE, '/') : '';
		$acceptLink = $baseUrl . '/inviti/accetta?token=' . rawurlencode($token);
		$blockLink = $baseUrl . '/inviti/blocca?token=' . rawurlencode($token);
		$html = $this->buildInvitationEmailHtml($inviterName, $acceptLink, $blockLink);

		(new Mailer())->send($email, $name, $inviterName . ' ti invita su ItalianCosplay.it', $html, null, [Mailer::TAG_USER_INVITATION]);
	}

	private function buildInvitationEmailHtml(string $inviterName, string $acceptLink, string $blockLink): string
	{
		$templatePath = APP_ROOT . '/app/views/email_template_registrazione.php';
		$template = is_file($templatePath) ? (string) file_get_contents($templatePath) : '';
		if ($template === '') {
			throw new RuntimeException('Template email registrazione non disponibile.');
		}

		$safeInviterName = htmlspecialchars($inviterName, ENT_QUOTES, 'UTF-8');
		$safeAcceptLink = htmlspecialchars($acceptLink, ENT_QUOTES, 'UTF-8');
		$safeBlockLink = htmlspecialchars($blockLink, ENT_QUOTES, 'UTF-8');

		$replacements = [
			'<title>Conferma il tuo account | ItalianCosplay.it</title>' => '<title>Invito su ItalianCosplay.it</title>',
			'Conferma la tua email e completa la registrazione su ItalianCosplay.it.' => $safeInviterName . ' ti invita su ItalianCosplay.it.',
			'Benvenuto nella community' => 'Invito community',
			'Conferma il tuo account' => 'Unisciti a ItalianCosplay.it',
			'Scopri eventi cosplay in Italia, salva quelli che ti interessano e gestisci il tuo profilo.' => 'Scopri eventi cosplay in Italia, salva quelli che ti interessano e partecipa alla community.',
			'Ciao <strong style="color: #14532d;">{{nome}}</strong>,' => 'Ciao,',
			'grazie per esserti registrato su <strong>ItalianCosplay.it</strong>.' => $safeInviterName . ' pensa che <strong>ItalianCosplay.it</strong> possa interessarti.',
			'Per completare la registrazione e attivare il tuo account, conferma il tuo indirizzo email cliccando sul pulsante qui sotto.' => 'ItalianCosplay.it è un portale dedicato agli eventi cosplay in Italia: puoi scoprire eventi, salvare quelli che ti interessano e gestire il tuo profilo.',
			'{{link_conferma}}' => $safeAcceptLink,
			'Conferma la tua email' => 'Accetta invito',
			'Se non hai richiesto questa registrazione, puoi ignorare questa email.' => 'Hai ricevuto questa email perché un utente di ItalianCosplay.it ha indicato il tuo indirizzo per invitarti. Non sei stato iscritto a newsletter o comunicazioni marketing.',
			'A presto,<br><strong>ItalianCosplay.it</strong>' => 'Se non vuoi ricevere altri inviti, puoi bloccarli da qui: <a href="' . $safeBlockLink . '">non ricevere altri inviti</a>.<br><br>A presto,<br><strong>ItalianCosplay.it</strong>',
			'Cosa puoi fare dopo la conferma' => 'Cosa puoi fare dopo l’iscrizione',
			'Esplora il calendario, salva eventi nella tua agenda e aggiorna il tuo profilo personale.' => 'Esplora il calendario, salva eventi nella tua agenda e ritrova più facilmente gli appuntamenti cosplay che ti interessano.',
		];

		return str_replace(array_keys($replacements), array_values($replacements), $template);
	}

	private function sendInvitationNotification(int $userId, int $invitationId, string $inviterName, string $token): void
	{
		$this->notificationService->createNotification([
			'user_id' => $userId,
			'notification_type' => 'user_invitation',
			'title' => 'Nuovo invito community',
			'message' => $inviterName . ' ti ha invitato a collegarti su ItalianCosplay.',
			'source_entity_type' => 'user_invitation',
			'source_entity_id' => $invitationId,
			'payload' => [
				'accept_url' => '/dashboard/inviti/accetta?token=' . rawurlencode($token),
				'inviter_name' => $inviterName,
			],
		]);
	}
}
