<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Session;
use App\Models\BlogPost;
use App\Models\Comune;
use App\Models\Event;
use App\Models\EventMaster;
use App\Models\Guest;
use App\Models\Provincia;
use App\Models\Regione;
use App\Models\TipoEvento;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\ConsentService;
use App\Services\FavoriteService;
use App\Services\ImageService;
use App\Services\PendingUserActionService;
use App\Support\AuditLogActionType;
use App\Services\AdStatsService;
use App\Services\CosplayPortfolioService;
use App\Services\NotificationService;
use Exception;

class DashboardController extends Controller
{
	private $userModel;
	private $uploadDir = APP_ROOT . '/public_assets/uploads/avatar/';
	private AdStatsService $adStatsService;
	private AuditLogService $auditLogService;
	private ConsentService $consentService;
	private FavoriteService $favoriteService;
	private PendingUserActionService $pendingUserActionService;
	private \App\Services\EventAgendaService $eventAgendaService;
	private NotificationService $notificationService;
	private CosplayPortfolioService $cosplayPortfolioService;
	private Event $eventModel;
	private EventMaster $eventMasterModel;
	private BlogPost $blogPostModel;
	private Guest $guestModel;
	private Comune $comuneModel;
	private Provincia $provinciaModel;
	private Regione $regioneModel;
	private TipoEvento $tipoEventoModel;
	private ImageService $imageService;
	private Event $eventSearchModel;
	private \App\Services\OrganizationService $organizationService;
	private \App\Services\EventMasterClaimService $eventMasterClaimService;
	private \App\Services\OrganizationInvitationService $organizationInvitationService;
	private \App\Services\UserInvitationService $userInvitationService;

	public function __construct()
	{
		$this->userModel = new User();
		$this->adStatsService = new AdStatsService();
		$this->auditLogService = new AuditLogService();
		$this->consentService = new ConsentService();
		$this->favoriteService = new FavoriteService();
		$this->pendingUserActionService = new PendingUserActionService();
		$this->eventAgendaService = new \App\Services\EventAgendaService();
		$this->notificationService = new NotificationService();
		$this->cosplayPortfolioService = new CosplayPortfolioService();
		$this->eventModel = new Event();
		$this->eventMasterModel = new EventMaster();
		$this->blogPostModel = new BlogPost();
		$this->guestModel = new Guest();
		$this->comuneModel = new Comune();
		$this->provinciaModel = new Provincia();
		$this->regioneModel = new Regione();
		$this->tipoEventoModel = new TipoEvento();
		$this->imageService = new ImageService($this->eventModel->getDbConnection());
		$this->eventSearchModel = new Event();
		$this->organizationService = new \App\Services\OrganizationService();
		$this->eventMasterClaimService = new \App\Services\EventMasterClaimService();
		$this->organizationInvitationService = new \App\Services\OrganizationInvitationService();
		$this->userInvitationService = new \App\Services\UserInvitationService();
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
		// Middleware: deve essere loggato
		//$this->middleware('AuthMiddleware');
	}

	public function overview()
	{
		$userId = $_SESSION['user_id'];
		$user = $this->userModel->find($userId);
		$adStats = $this->adStatsService->getUserOverview((int)$userId);
		$favoritesSummary = [
			'total' => $this->favoriteService->countUserFavorites((int) $userId),
			'event' => $this->favoriteService->countUserFavoritesByType((int) $userId, 'event'),
			'guest' => $this->favoriteService->countUserFavoritesByType((int) $userId, 'guest'),
			'blog_post' => $this->favoriteService->countUserFavoritesByType((int) $userId, 'blog_post'),
			'regione' => $this->favoriteService->countUserFavoritesByType((int) $userId, 'regione'),
			'provincia' => $this->favoriteService->countUserFavoritesByType((int) $userId, 'provincia'),
			'comune' => $this->favoriteService->countUserFavoritesByType((int) $userId, 'comune'),
		];
		$agendaCounts = $this->eventAgendaService->getUserAgendaCount((int) $userId);
		$cosplayPortfolio = $this->cosplayPortfolioService->getUserPortfolio((int) $userId);
		$organizationData = $this->organizationService->getForUser((int) $userId);
		$claimData = $this->eventMasterClaimService->getClaimsForUser((int) $userId);
		$organizationInvitations = $this->organizationInvitationService->getPendingForUser((int) $userId);

		$this->view('dashboard/overview', [
			'user' => $user,
			'adStats' => $adStats,
			'favoritesSummary' => $favoritesSummary,
			'agendaCounts' => $agendaCounts,
			'cosplayPortfolioCount' => count($cosplayPortfolio),
			'organizations' => $organizationData,
			'eventMasterClaims' => $claimData,
			'organizationInvitations' => $organizationInvitations,
		], 'dashboard'); // 🔥 layout custom
	}

	public function organizations(): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$this->view('dashboard/organizations', [
			'user' => $this->userModel->find($userId),
			'organizations' => $this->organizationService->getForUser($userId),
			'claims' => $this->eventMasterClaimService->getClaimsForUser($userId),
		], 'dashboard');
	}

	public function invitations(): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$this->view('dashboard/invitations', [
			'user' => $this->userModel->find($userId),
			'invitations' => $this->userInvitationService->getRecentForUser($userId),
			'dailyUsage' => $this->userInvitationService->getDailyUsage($userId),
		], 'dashboard');
	}

	public function sendInvitation(): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) {
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: /dashboard/inviti'); exit();
		}

		try {
			$result = $this->userInvitationService->invite($userId, (string) ($_POST['email'] ?? ''));
			Session::setFlash('success', 'Invito inviato a ' . $result['email'] . '.');
		} catch (\Throwable $exception) {
			$this->auditLogService->logAudit([
				'user_id' => $userId,
				'action_type' => AuditLogActionType::USER_INVITATION_CREATED,
				'entity_type' => 'user_invitation',
				'entity_id' => null,
				'success' => 0,
				'error_message' => mb_substr($exception->getMessage(), 0, 250),
				'payload' => ['source' => 'dashboard'],
			]);
			Session::setFlash('error', $exception->getMessage());
		}

		header('Location: /dashboard/inviti'); exit();
	}

	public function acceptUserInvitation(): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$token = trim((string) ($_GET['token'] ?? ''));
		try {
			$this->userInvitationService->acceptByToken($token, $userId);
			Session::setFlash('success', 'Invito accettato. Il collegamento è stato salvato nel tuo account.');
		} catch (\Throwable $exception) {
			Session::setFlash('error', $exception->getMessage());
		}

		header('Location: /dashboard/inviti'); exit();
	}

	public function organizationInvitations(): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$this->view('dashboard/organization-invitations', ['user' => $this->userModel->find($userId), 'invitations' => $this->organizationInvitationService->getPendingForUser($userId)], 'dashboard');
	}

	public function organizationInvitationPreview(): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$token = trim((string) ($_GET['token'] ?? ''));
		$invitation = $this->organizationInvitationService->findForUserByToken($token, $userId);
		if (!$invitation) {
			Session::setFlash('error', 'Invito non valido, scaduto o non associato al tuo account.');
			header('Location: /dashboard/organization-invitations'); exit();
		}
		$this->view('dashboard/organization-invitation', ['user' => $this->userModel->find($userId), 'invitation' => $invitation, 'token' => $token], 'dashboard');
	}

	public function acceptOrganizationInvitation(): void
	{
		$this->respondToOrganizationInvitation('accept');
	}

	public function declineOrganizationInvitation(): void
	{
		$this->respondToOrganizationInvitation('decline');
	}

	private function respondToOrganizationInvitation(string $action): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$token = trim((string) ($_POST['token'] ?? ''));
		$invitationId = (int) ($_POST['invitation_id'] ?? 0);
		$json = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
		if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) {
			if ($json) $this->dashboardJson(false, 'Token CSRF non valido.', 403);
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: /dashboard/organization-invitations'); exit();
		}
		try {
			$ok = $action === 'accept'
				? ($invitationId > 0 ? $this->organizationInvitationService->acceptById($invitationId, $userId) : $this->organizationInvitationService->accept($token, $userId))
				: ($invitationId > 0 ? $this->organizationInvitationService->declineById($invitationId, $userId) : $this->organizationInvitationService->decline($token, $userId));
			if (!$ok) throw new \RuntimeException('Invito non aggiornato.');
			$message = $action === 'accept' ? 'Invito accettato. Ora fai parte dell’organizzazione.' : 'Invito rifiutato.';
			if ($json) $this->dashboardJson(true, $message);
			Session::setFlash('success', $message);
		} catch (\Throwable $exception) {
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => $action === 'accept' ? AuditLogActionType::ORGANIZATION_INVITATION_ACCEPTED : AuditLogActionType::ORGANIZATION_INVITATION_DECLINED, 'entity_type' => 'organization_invitation', 'entity_id' => $invitationId > 0 ? $invitationId : null, 'success' => 0, 'error_message' => mb_substr($exception->getMessage(), 0, 250), 'payload' => ['source' => 'dashboard']]);
			if ($json) $this->dashboardJson(false, $exception->getMessage(), 400);
			Session::setFlash('error', $exception->getMessage());
		}
		header('Location: /dashboard/organization-invitations'); exit();
	}

	public function createOrganization(): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$this->view('dashboard/organization-create', ['user' => $this->userModel->find($userId)], 'dashboard');
	}

	public function storeOrganization(): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$json = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
		if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) {
			if ($json) $this->dashboardJson(false, 'Token CSRF non valido.', 403);
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: /dashboard/organizations/create'); exit();
		}
		try {
			$data = $_POST;
			$data['owner_user_id'] = $userId;
			$data['status'] = 'pending_review';
			$data['is_public'] = 0;
			$organizationId = (int) $this->organizationService->save($data);
			foreach (['logo' => 'organization_logo', 'cover' => 'organization_cover'] as $field => $entityType) {
				if (empty($_FILES[$field]['name'])) continue;
				$imageId = $this->imageService->replacePrimary($_FILES[$field], $entityType, $organizationId, trim((string) ($data['name'] ?? 'Organizzazione')));
				if (!$imageId) throw new \RuntimeException('Impossibile caricare l’immagine ' . $field . '.');
				$image = $this->imageService->getPrimary($entityType, $organizationId, 'large');
				$data[$field . '_path'] = $image['path'] ?? null;
			}
			if (!empty($data['logo_path']) || !empty($data['cover_path'])) {
				$this->organizationService->save($data, $organizationId);
			}
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::ORGANIZATION_CREATED, 'entity_type' => 'organization', 'entity_id' => $organizationId, 'payload' => ['source' => 'dashboard', 'status' => 'pending_review']]);
			if ($json) $this->dashboardJson(true, 'Organizzazione inviata per la verifica.', 200, ['organization_id' => $organizationId]);
			Session::setFlash('success', 'Organizzazione inviata per la verifica.');
		} catch (\Throwable $exception) {
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::ORGANIZATION_CREATED, 'entity_type' => 'organization', 'entity_id' => null, 'success' => 0, 'error_message' => $exception->getMessage(), 'payload' => ['source' => 'dashboard', 'status' => 'pending_review']]);
			if ($json) $this->dashboardJson(false, $exception->getMessage(), 400);
			Session::setFlash('error', $exception->getMessage());
		}
		header('Location: /dashboard/organizations'); exit();
	}

	public function organizationDetail(array $params): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$organizationId = (int) ($params[0] ?? 0);
		$organization = $this->organizationService->findForUser($organizationId, $userId);
		if (!$organization) {
			Session::setFlash('error', 'Organizzazione non trovata o accesso non autorizzato.');
			header('Location: /dashboard/organizations');
			exit();
		}
		$currentUserRole = $this->getOrganizationMemberRole($organization, $userId);
		$this->view('dashboard/organization-detail', [
			'user' => $this->userModel->find($userId),
			'organization' => $organization,
			'currentUserRole' => $currentUserRole,
			'invitations' => in_array($currentUserRole, ['owner', 'admin'], true) ? $this->organizationInvitationService->getForOrganization($organizationId, $userId) : [],
		], 'dashboard');
	}

	public function revokeOrganizationInvitation(array $params): void
	{
		$this->manageOrganizationInvitation((int) ($params[0] ?? 0), (int) ($params[1] ?? 0), 'revoke');
	}

	public function resendOrganizationInvitation(array $params): void
	{
		$this->manageOrganizationInvitation((int) ($params[0] ?? 0), (int) ($params[1] ?? 0), 'resend');
	}

	private function manageOrganizationInvitation(int $organizationId, int $invitationId, string $action): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$json = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
		if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) {
			if ($json) $this->dashboardJson(false, 'Token CSRF non valido.', 403);
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: /dashboard/organizations/' . $organizationId); exit();
		}
		try {
			$responseData = [];
			if ($action === 'revoke') {
				$this->organizationInvitationService->revoke($organizationId, $invitationId, $userId);
				$message = 'Invito revocato.';
			} else {
				$resentInvitation = $this->organizationInvitationService->resend($organizationId, $invitationId, $userId);
				$responseData = ['invitation_id' => (int) $resentInvitation['id']];
				$message = 'Invito reinviato.';
			}
			if ($json) $this->dashboardJson(true, $message, 200, $responseData);
			Session::setFlash('success', $message);
		} catch (\Throwable $exception) {
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => $action === 'revoke' ? AuditLogActionType::ORGANIZATION_INVITATION_REVOKED : AuditLogActionType::ORGANIZATION_INVITATION_RESENT, 'entity_type' => 'organization_invitation', 'entity_id' => $invitationId, 'success' => 0, 'error_message' => mb_substr($exception->getMessage(), 0, 250), 'payload' => ['organization_id' => $organizationId, 'source' => 'dashboard']]);
			if ($json) $this->dashboardJson(false, $exception->getMessage(), 400);
			Session::setFlash('error', $exception->getMessage());
		}
		header('Location: /dashboard/organizations/' . $organizationId); exit();
	}

	public function withdrawOrganization(array $params): void
	{
		$this->withdrawOrganizationResource((int) ($params[0] ?? 0), 0, 'organization');
	}

	public function withdrawOrganizationMaster(array $params): void
	{
		$this->withdrawOrganizationResource((int) ($params[0] ?? 0), (int) ($params[1] ?? 0), 'master');
	}

	public function withdrawOrganizationEvent(array $params): void
	{
		$this->withdrawOrganizationResource((int) ($params[0] ?? 0), (int) ($params[1] ?? 0), 'event');
	}

	private function withdrawOrganizationResource(int $organizationId, int $resourceId, string $resourceType): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$json = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
		if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) {
			if ($json) $this->dashboardJson(false, 'Token CSRF non valido.', 403);
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: /dashboard/organizations/' . $organizationId); exit();
		}
		$actionType = $resourceType === 'organization' ? AuditLogActionType::ORGANIZATION_STATUS_UPDATED : ($resourceType === 'master' ? AuditLogActionType::EVENT_MASTER_UPDATED : AuditLogActionType::EVENT_UPDATED);
		try {
			if ($resourceType === 'organization') {
				$this->organizationService->withdrawOrganizationForUser($organizationId, $userId);
				$message = 'Organizzazione ritirata e inviata nuovamente in revisione.';
			} elseif ($resourceType === 'master') {
				$this->organizationService->withdrawMasterForUser($organizationId, $resourceId, $userId);
				$message = 'Master ritirato dalla pubblicazione e inviato in revisione.';
			} else {
				$this->organizationService->withdrawEventForUser($organizationId, $resourceId, $userId);
				$message = 'Edizione ritirata dalla pubblicazione e inviata in revisione.';
			}
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => $actionType, 'entity_type' => $resourceType === 'organization' ? 'organization' : ($resourceType === 'master' ? 'event_master' : 'event'), 'entity_id' => $resourceType === 'organization' ? $organizationId : $resourceId, 'payload' => ['source' => 'dashboard', 'operation' => 'withdraw', 'status' => 'pending_review']]);
			if ($json) $this->dashboardJson(true, $message);
			Session::setFlash('success', $message);
		} catch (\Throwable $exception) {
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => $actionType, 'entity_type' => $resourceType === 'organization' ? 'organization' : ($resourceType === 'master' ? 'event_master' : 'event'), 'entity_id' => $resourceType === 'organization' ? $organizationId : $resourceId, 'success' => 0, 'error_message' => mb_substr($exception->getMessage(), 0, 250), 'payload' => ['source' => 'dashboard', 'operation' => 'withdraw']]);
			if ($json) $this->dashboardJson(false, $exception->getMessage(), 400);
			Session::setFlash('error', $exception->getMessage());
		}
		$redirect = $resourceType === 'master' ? '/dashboard/organizations/' . $organizationId . '/masters/' . $resourceId : ($resourceType === 'event' ? '/dashboard/organizations/' . $organizationId . '/events/' . $resourceId . '/edit' : '/dashboard/organizations/' . $organizationId);
		header('Location: ' . $redirect); exit();
	}

	public function organizationMemberSearch(array $params): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$organizationId = (int) ($params[0] ?? 0);
		$organization = $this->organizationService->findForUser($organizationId, $userId);
		if (!$organization || !in_array($this->getOrganizationMemberRole($organization, $userId), ['owner', 'admin'], true)) { $this->dashboardJson(false, 'Accesso non autorizzato.', 403); }
		$query = trim((string) ($_GET['q'] ?? ''));
		$this->dashboardJson(true, '', 200, ['users' => $this->organizationService->searchUsers($organizationId, $query)]);
	}

	public function organizationMasterSearch(array $params): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$organizationId = (int) ($params[0] ?? 0);
		$organization = $this->organizationService->findForUser($organizationId, $userId);
		if (!$organization || !in_array($this->getOrganizationMemberRole($organization, $userId), ['owner', 'admin'], true)) $this->dashboardJson(false, 'Accesso non autorizzato.', 403);
		$this->dashboardJson(true, '', 200, ['masters' => $this->organizationService->searchEventMasters($organizationId, (string) ($_GET['q'] ?? ''))]);
	}

	public function organizationMaster(array $params): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0); $organizationId = (int) ($params[0] ?? 0); $organization = $this->organizationService->findForUser($organizationId, $userId); $json = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
		if (!$organization || !in_array($this->getOrganizationMemberRole($organization, $userId), ['owner', 'admin'], true)) { if ($json) $this->dashboardJson(false, 'Non hai i permessi per gestire i master.', 403); header('Location: /dashboard/organizations/' . $organizationId); exit(); }
		if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) { if ($json) $this->dashboardJson(false, 'Token CSRF non valido.', 403); header('Location: /dashboard/organizations/' . $organizationId); exit(); }
		$masterId = (int) ($_POST['event_master_id'] ?? 0); $masterRole = (string) ($_POST['role'] ?? 'organizer'); $operation = (string) ($_POST['operation'] ?? 'update');
		try { $ok = $operation === 'remove' ? $this->organizationService->removeMaster($organizationId, $masterId) : ($operation === 'add' ? $this->organizationService->addMaster($organizationId, $masterId, $masterRole) : $this->organizationService->updateMaster($organizationId, $masterId, $masterRole)); if (!$ok) throw new \RuntimeException('Master non aggiornato.'); $this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::ORGANIZATION_MASTER_UPDATED, 'entity_type' => 'organization', 'entity_id' => $organizationId, 'payload' => ['source' => 'dashboard', 'event_master_id' => $masterId, 'role' => $masterRole, 'operation' => $operation]]); $message = $operation === 'add' ? 'Master associato.' : ($operation === 'remove' ? 'Master rimosso.' : 'Ruolo del master aggiornato.'); if ($json) $this->dashboardJson(true, $message); Session::setFlash('success', $message); } catch (\Throwable $exception) { $this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::ORGANIZATION_MASTER_UPDATED, 'entity_type' => 'organization', 'entity_id' => $organizationId, 'success' => 0, 'error_message' => $exception->getMessage(), 'payload' => ['source' => 'dashboard', 'event_master_id' => $masterId, 'operation' => $operation]]); if ($json) $this->dashboardJson(false, $exception->getMessage(), 400); Session::setFlash('error', $exception->getMessage()); }
		header('Location: /dashboard/organizations/' . $organizationId); exit();
	}

	public function organizationMember(array $params): void
	{
		$this->organizationRelationAction((int) ($params[0] ?? 0), 'member');
	}

	private function organizationRelationAction(int $organizationId, string $relation): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$organization = $this->organizationService->findForUser($organizationId, $userId);
		$role = $organization ? $this->getOrganizationMemberRole($organization, $userId) : 'viewer';
		$json = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
		if (!$organization || !in_array($role, ['owner', 'admin'], true)) { if ($json) $this->dashboardJson(false, 'Non hai i permessi per gestire i membri.', 403); header('Location: /dashboard/organizations/' . $organizationId); exit(); }
		if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) { if ($json) $this->dashboardJson(false, 'Token CSRF non valido.', 403); header('Location: /dashboard/organizations/' . $organizationId); exit(); }
		$memberId = (int) ($_POST['user_id'] ?? 0);
		$memberRole = (string) ($_POST['role'] ?? 'viewer');
		$status = (string) ($_POST['status'] ?? 'active');
		try {
			$ok = $status === 'removed' ? $this->organizationService->updateMember($organizationId, $memberId, $memberRole, 'removed') : $this->organizationService->addMember($organizationId, $memberId, $memberRole, $userId);
			if (!$ok) throw new \RuntimeException('Membro non aggiornato.');
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::ORGANIZATION_MEMBER_UPDATED, 'entity_type' => 'organization', 'entity_id' => $organizationId, 'payload' => ['source' => 'dashboard', 'member_user_id' => $memberId, 'role' => $memberRole, 'status' => $status]]);
			$message = $status === 'removed' ? 'Membro rimosso.' : 'Invito inviato al membro.';
			if ($json) $this->dashboardJson(true, $message);
			Session::setFlash('success', $message);
		} catch (\Throwable $exception) {
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::ORGANIZATION_MEMBER_UPDATED, 'entity_type' => 'organization', 'entity_id' => $organizationId, 'success' => 0, 'error_message' => $exception->getMessage(), 'payload' => ['source' => 'dashboard', 'member_user_id' => $memberId]]);
			if ($json) $this->dashboardJson(false, $exception->getMessage(), 400);
			Session::setFlash('error', $exception->getMessage());
		}
		header('Location: /dashboard/organizations/' . $organizationId); exit();
	}

	public function updateOrganization(array $params): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$organizationId = (int) ($params[0] ?? 0);
		$organization = $this->organizationService->findForUser($organizationId, $userId);
		$role = $organization ? $this->getOrganizationMemberRole($organization, $userId) : 'viewer';
		$json = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
		if (!$organization || !in_array($role, ['owner', 'admin'], true)) {
			if ($json) $this->dashboardJson(false, 'Non hai i permessi per modificare questa organizzazione.', 403);
			Session::setFlash('error', 'Non hai i permessi per modificare questa organizzazione.');
			header('Location: /dashboard/organizations/' . $organizationId); exit();
		}
		if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
			if ($json) $this->dashboardJson(false, 'Token CSRF non valido.', 403);
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: /dashboard/organizations/' . $organizationId); exit();
		}
		try {
			foreach (['logo' => 'organization_logo', 'cover' => 'organization_cover'] as $field => $entityType) {
				if (!empty($_FILES[$field]['name'])) {
					$imageId = $this->imageService->replacePrimary($_FILES[$field], $entityType, $organizationId, trim((string) ($_POST['name'] ?? 'Organizzazione')));
					if (!$imageId) throw new \RuntimeException('Impossibile caricare l’immagine ' . $field . '.');
					$cover = $this->imageService->getPrimary($entityType, $organizationId, 'large');
					$_POST[$field . '_path'] = $cover['path'] ?? null;
				}
			}
			$this->organizationService->save($_POST, $organizationId);
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::ORGANIZATION_UPDATED, 'entity_type' => 'organization', 'entity_id' => $organizationId, 'payload' => ['source' => 'dashboard', 'name' => trim((string) ($_POST['name'] ?? ''))]]);
			if ($json) $this->dashboardJson(true, 'Organizzazione aggiornata correttamente.');
			Session::setFlash('success', 'Organizzazione aggiornata correttamente.');
		} catch (\Throwable $exception) {
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::ORGANIZATION_UPDATED, 'entity_type' => 'organization', 'entity_id' => $organizationId, 'success' => 0, 'error_message' => $exception->getMessage(), 'payload' => ['source' => 'dashboard']]);
			if ($json) $this->dashboardJson(false, $exception->getMessage(), 400);
			Session::setFlash('error', $exception->getMessage());
		}
		header('Location: /dashboard/organizations/' . $organizationId); exit();
	}

	public function organizationMasterDetail(array $params): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$organizationId = (int) ($params[0] ?? 0);
		$masterId = (int) ($params[1] ?? 0);
		$organization = $this->organizationService->findForUser($organizationId, $userId);
		$master = $this->organizationService->findMasterForOrganization($masterId, $organizationId);
		if (!$organization || !$master) {
			Session::setFlash('error', 'Master non trovato o accesso non autorizzato.');
			header('Location: /dashboard/organizations/' . $organizationId);
			exit();
		}
		$master['organization_user_role'] = $this->getOrganizationMemberRole($organization, $userId);
		$this->view('dashboard/organization-master-detail', [
			'user' => $this->userModel->find($userId),
			'master' => $master,
			'events' => $this->organizationService->findEventsForMaster($masterId, $userId),
			'organizationId' => $organizationId,
			'canManage' => in_array($master['organization_user_role'], ['owner', 'admin'], true),
			'canEdit' => in_array($master['organization_user_role'], ['owner', 'admin', 'editor'], true),
		], 'dashboard');
	}

	public function organizationMasterCreate(array $params): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$organizationId = (int) ($params[0] ?? 0);
		$organization = $this->organizationService->findForUser($organizationId, $userId);
		if (!$organization || !in_array($this->getOrganizationMemberRole($organization, $userId), ['owner', 'admin'], true)) {
			Session::setFlash('error', 'Non hai i permessi per proporre un master.');
			header('Location: /dashboard/organizations/' . $organizationId); exit();
		}
		$this->view('dashboard/organization-master-create', ['user' => $this->userModel->find($userId), 'organization' => $organization], 'dashboard');
	}

	public function storeOrganizationMaster(array $params): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$organizationId = (int) ($params[0] ?? 0);
		$json = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
		$organization = $this->organizationService->findForUser($organizationId, $userId);
		if (!$organization || !in_array($this->getOrganizationMemberRole($organization, $userId), ['owner', 'admin'], true)) {
			if ($json) $this->dashboardJson(false, 'Non hai i permessi per proporre un master.', 403);
			header('Location: /dashboard/organizations/' . $organizationId); exit();
		}
		if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) {
			if ($json) $this->dashboardJson(false, 'Token CSRF non valido.', 403);
			header('Location: /dashboard/organizations/' . $organizationId . '/masters/create'); exit();
		}
		$masterId = null;
		try {
			$name = trim((string) ($_POST['nome'] ?? ''));
			if (mb_strlen($name) < 2 || mb_strlen($name) > 255) throw new \InvalidArgumentException('Il nome del master deve avere tra 2 e 255 caratteri.');
			$data = ['nome' => $name, 'descrizione' => trim((string) ($_POST['descrizione'] ?? '')), 'sito_web' => trim((string) ($_POST['sito_web'] ?? '')), 'social_facebook' => trim((string) ($_POST['social_facebook'] ?? '')), 'social_twitter' => trim((string) ($_POST['social_twitter'] ?? '')), 'social_instagram' => trim((string) ($_POST['social_instagram'] ?? '')), 'social_tiktok' => trim((string) ($_POST['social_tiktok'] ?? '')), 'social_youtube' => trim((string) ($_POST['social_youtube'] ?? '')), 'status' => 'pending_review', 'is_public' => 0];
			foreach (['sito_web', 'social_facebook', 'social_twitter', 'social_instagram', 'social_tiktok', 'social_youtube'] as $field) {
				if ($data[$field] !== '' && !filter_var($data[$field], FILTER_VALIDATE_URL)) throw new \InvalidArgumentException('L’URL del campo ' . $field . ' non è valido.');
			}
			$masterId = $this->eventMasterModel->create($data);
			if (!$this->organizationService->addMaster($organizationId, $masterId, 'organizer')) throw new \RuntimeException('Impossibile associare il master all’organizzazione.');
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::EVENT_MASTER_CREATED, 'entity_type' => 'event_master', 'entity_id' => $masterId, 'payload' => ['source' => 'dashboard', 'organization_id' => $organizationId, 'status' => 'pending_review']]);
			if ($json) $this->dashboardJson(true, 'Master inviato per la verifica.', 200, ['event_master_id' => $masterId]);
			Session::setFlash('success', 'Master inviato per la verifica.');
		} catch (\Throwable $exception) {
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::EVENT_MASTER_CREATED, 'entity_type' => 'event_master', 'entity_id' => $masterId, 'success' => 0, 'error_message' => $exception->getMessage(), 'payload' => ['source' => 'dashboard', 'organization_id' => $organizationId, 'status' => 'pending_review']]);
			if ($json) $this->dashboardJson(false, $exception->getMessage(), 400);
			Session::setFlash('error', $exception->getMessage());
		}
		header('Location: /dashboard/organizations/' . $organizationId); exit();
	}

	public function organizationEventCreate(array $params): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$organizationId = (int) ($params[0] ?? 0);
		$masterId = (int) ($params[1] ?? 0);
		$organization = $this->organizationService->findForUser($organizationId, $userId);
		$master = $this->organizationService->findMasterForOrganization($masterId, $organizationId);
		if ($master && $organization) $master['organization_user_role'] = $this->getOrganizationMemberRole($organization, $userId);
		if (!$organization || !$master || !in_array($master['organization_user_role'], ['owner', 'admin', 'editor'], true)) {
			Session::setFlash('error', 'Non hai i permessi per creare un’edizione.');
			header('Location: /dashboard/organizations/' . $organizationId); exit();
		}
		$this->view('dashboard/organization-event-create', [
			'user' => $this->userModel->find($userId), 'master' => $master,
			'organizationId' => $organizationId, 'regions' => $this->regioneModel->getAll(),
			'types' => $this->tipoEventoModel->getAll(),
		], 'dashboard');
	}

	public function storeOrganizationEvent(array $params): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$organizationId = (int) ($params[0] ?? 0);
		$masterId = (int) ($params[1] ?? 0);
		$organization = $this->organizationService->findForUser($organizationId, $userId);
		$master = $this->organizationService->findMasterForOrganization($masterId, $organizationId);
		if ($master && $organization) $master['organization_user_role'] = $this->getOrganizationMemberRole($organization, $userId);
		$json = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
		if (!$master || (int) $master['organization_id'] !== $organizationId || !in_array($master['organization_user_role'], ['owner', 'admin', 'editor'], true)) { if ($json) $this->dashboardJson(false, 'Non hai i permessi per creare un’edizione.', 403); header('Location: /dashboard/organizations/' . $organizationId); exit(); }
		if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) { if ($json) $this->dashboardJson(false, 'Token CSRF non valido.', 403); header('Location: /dashboard/organizations/' . $organizationId . '/masters/' . $masterId); exit(); }
		try {
			$data = [
				'titolo' => trim((string) ($_POST['titolo'] ?? '')), 'descrizione' => trim((string) ($_POST['descrizione'] ?? '')),
				'data_inizio' => trim((string) ($_POST['data_inizio'] ?? '')), 'data_fine' => trim((string) ($_POST['data_fine'] ?? '')),
				'luogo' => trim((string) ($_POST['luogo'] ?? '')), 'regione_id' => (int) ($_POST['regione_id'] ?? 0),
				'provincia_id' => (int) ($_POST['provincia_id'] ?? 0), 'comune_id' => (int) ($_POST['comune_id'] ?? 0),
				'tipo_evento_id' => (int) ($_POST['tipo_evento_id'] ?? 0), 'sito_web' => trim((string) ($_POST['sito_web'] ?? '')),
				'social_facebook' => trim((string) ($_POST['social_facebook'] ?? '')), 'social_twitter' => trim((string) ($_POST['social_twitter'] ?? '')),
				'social_instagram' => trim((string) ($_POST['social_instagram'] ?? '')), 'social_tiktok' => trim((string) ($_POST['social_tiktok'] ?? '')),
				'social_youtube' => trim((string) ($_POST['social_youtube'] ?? '')), 'social_twitter' => trim((string) ($_POST['social_twitter'] ?? '')), 'anno' => (int) ($_POST['anno'] ?? date('Y')),
				'event_size' => (int) ($_POST['event_size'] ?? 0), 'is_paid' => !empty($_POST['is_paid']) ? 1 : 0,
				'has_cosplay_contest' => !empty($_POST['has_cosplay_contest']) ? 1 : 0, 'approvato' => 0,
				'event_master_id' => $masterId, 'immagine' => null,
			];
			if ($data['titolo'] === '' || $data['descrizione'] === '' || $data['data_inizio'] === '' || $data['luogo'] === '') throw new \InvalidArgumentException('Titolo, descrizione, data di inizio e luogo sono obbligatori.');
			if ($data['data_fine'] !== '' && strtotime($data['data_fine']) < strtotime($data['data_inizio'])) throw new \InvalidArgumentException('La data di fine non può precedere quella di inizio.');
			foreach (['regione_id', 'provincia_id', 'comune_id', 'tipo_evento_id'] as $field) if ($data[$field] < 1) throw new \InvalidArgumentException('Completa regione, provincia, comune e tipo evento.');
			$eventId = $this->eventModel->create($data);
			$guestIds = json_decode((string) ($_POST['guest_ids'] ?? '[]'), true);
			$guestIds = is_array($guestIds) ? array_values(array_filter(array_map('intval', $guestIds), static fn (int $id): bool => $id > 0)) : [];
			$this->guestModel->saveGuests($eventId, $guestIds);
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::EVENT_CREATED, 'entity_type' => 'event', 'entity_id' => $eventId, 'payload' => ['source' => 'dashboard', 'organization_id' => $organizationId, 'event_master_id' => $masterId]]);
			if ($json) $this->dashboardJson(true, 'Edizione creata correttamente.', 200, ['event_id' => $eventId]);
			Session::setFlash('success', 'Edizione creata correttamente.');
		} catch (\Throwable $exception) {
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::EVENT_CREATED, 'entity_type' => 'event', 'entity_id' => $eventId ?? null, 'success' => 0, 'error_message' => $exception->getMessage(), 'payload' => ['source' => 'dashboard', 'organization_id' => $organizationId, 'event_master_id' => $masterId]]);
			if ($json) $this->dashboardJson(false, $exception->getMessage(), 400);
			Session::setFlash('error', $exception->getMessage());
		}
		header('Location: /dashboard/organizations/' . $organizationId . '/masters/' . $masterId); exit();
	}

	public function updateOrganizationMaster(array $params): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$organizationId = (int) ($params[0] ?? 0);
		$masterId = (int) ($params[1] ?? 0);
		$organization = $this->organizationService->findForUser($organizationId, $userId);
		$master = $this->organizationService->findMasterForOrganization($masterId, $organizationId);
		if ($master && $organization) $master['organization_user_role'] = $this->getOrganizationMemberRole($organization, $userId);
		$json = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
		if (!$master || (int) $master['organization_id'] !== $organizationId || !in_array($master['organization_user_role'], ['owner', 'admin', 'editor'], true)) {
			if ($json) $this->dashboardJson(false, 'Non hai i permessi per modificare questo master.', 403);
			header('Location: /dashboard/organizations/' . $organizationId); exit();
		}
		if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) {
			if ($json) $this->dashboardJson(false, 'Token CSRF non valido.', 403);
			header('Location: /dashboard/organizations/' . $organizationId . '/masters/' . $masterId); exit();
		}
		try {
			$name = trim((string) ($_POST['nome'] ?? ''));
			if (mb_strlen($name) < 2 || mb_strlen($name) > 255) throw new \InvalidArgumentException('Il nome del master deve avere tra 2 e 255 caratteri.');
			$data = [];
			foreach (['nome', 'descrizione', 'sito_web', 'social_facebook', 'social_twitter', 'social_instagram', 'social_tiktok', 'social_youtube'] as $field) {
				$data[$field] = trim((string) ($_POST[$field] ?? ''));
			}
			if (!$this->eventMasterModel->update($masterId, $data)) throw new \RuntimeException('Master non aggiornato.');
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::EVENT_MASTER_UPDATED, 'entity_type' => 'event_master', 'entity_id' => $masterId, 'payload' => ['source' => 'dashboard', 'organization_id' => $organizationId]]);
			if ($json) $this->dashboardJson(true, 'Master aggiornato correttamente.');
			Session::setFlash('success', 'Master aggiornato correttamente.');
		} catch (\Throwable $exception) {
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::EVENT_MASTER_UPDATED, 'entity_type' => 'event_master', 'entity_id' => $masterId, 'success' => 0, 'error_message' => $exception->getMessage(), 'payload' => ['source' => 'dashboard', 'organization_id' => $organizationId]]);
			if ($json) $this->dashboardJson(false, $exception->getMessage(), 400);
			Session::setFlash('error', $exception->getMessage());
		}
		header('Location: /dashboard/organizations/' . $organizationId . '/masters/' . $masterId); exit();
	}

	public function organizationMasterEdit(array $params): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$organizationId = (int) ($params[0] ?? 0);
		$masterId = (int) ($params[1] ?? 0);
		$organization = $this->organizationService->findForUser($organizationId, $userId);
		$master = $this->organizationService->findMasterForOrganization($masterId, $organizationId);
		if ($master && $organization) $master['organization_user_role'] = $this->getOrganizationMemberRole($organization, $userId);
		if (!$master || (int) $master['organization_id'] !== $organizationId || !in_array($master['organization_user_role'], ['owner', 'admin', 'editor'], true)) {
			Session::setFlash('error', 'Non hai i permessi per modificare questo master.');
			header('Location: /dashboard/organizations/' . $organizationId); exit();
		}
		$this->view('dashboard/organization-master-edit', ['user' => $this->userModel->find($userId), 'master' => $master, 'organizationId' => $organizationId], 'dashboard');
	}

	public function organizationEventEdit(array $params): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$organizationId = (int) ($params[0] ?? 0);
		$eventId = (int) ($params[1] ?? 0);
		$organization = $this->organizationService->findForUser($organizationId, $userId);
		$event = $this->organizationService->findEventForOrganization($eventId, $organizationId);
		if (!$organization || !$event) {
			Session::setFlash('error', 'Edizione non trovata o accesso non autorizzato.');
			header('Location: /dashboard/organizations/' . $organizationId); exit();
		}
		$event['organization_user_role'] = $this->getOrganizationMemberRole($organization, $userId);
		$event['guests'] = $this->guestModel->getGuests($eventId);
		$this->view('dashboard/organization-event-edit', [
			'user' => $this->userModel->find($userId), 'event' => $event,
			'organizationId' => $organizationId,
			'canEdit' => in_array($event['organization_user_role'], ['owner', 'admin', 'editor'], true),
			'canManage' => in_array($event['organization_user_role'], ['owner', 'admin'], true),
			'regions' => $this->regioneModel->getAll(),
			'provinces' => $this->provinciaModel->getByRegioneId((int) ($event['regione_id'] ?? 0)),
			'municipalities' => $this->comuneModel->getAll((int) ($event['provincia_id'] ?? 0)),
			'types' => $this->tipoEventoModel->getAll(),
		], 'dashboard');
	}

	public function organizationGuestSearch(array $params): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$eventId = (int) ($params[0] ?? 0);
		$event = $this->organizationService->findEventForUser($eventId, $userId);
		if (!$event || !in_array($event['organization_user_role'], ['owner', 'admin', 'editor'], true)) {
			$this->dashboardJson(false, 'Accesso non autorizzato.', 403);
		}
		$query = trim((string) ($_GET['q'] ?? ''));
		$this->dashboardJson(true, '', 200, ['guests' => mb_strlen($query) >= 2 ? $this->guestModel->search($query) : []]);
	}

	public function dashboardGuestSearch(): void
	{
		$query = trim((string) ($_GET['q'] ?? ''));
		$this->dashboardJson(true, '', 200, ['guests' => mb_strlen($query) >= 2 ? $this->guestModel->search($query) : []]);
	}

	public function organizationGuestRemove(array $params): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$eventId = (int) ($params[0] ?? 0);
		$guestId = (int) ($params[1] ?? 0);
		if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) {
			$this->dashboardJson(false, 'Token CSRF non valido.', 403);
		}
		$event = $this->organizationService->findEventForUser($eventId, $userId);
		if (!$event || !in_array((string) ($event['organization_user_role'] ?? ''), ['owner', 'admin', 'editor'], true)) {
			$this->dashboardJson(false, 'Accesso non autorizzato.', 403);
		}
		try {
			if (!$this->guestModel->removeFromEvent($eventId, $guestId)) throw new \RuntimeException('Il guest non è associato a questa edizione.');
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::GUEST_REMOVED_FROM_EVENT, 'entity_type' => 'event', 'entity_id' => $eventId, 'payload' => ['source' => 'dashboard', 'guest_id' => $guestId]]);
			$this->dashboardJson(true, 'Guest rimosso dall’edizione.');
		} catch (\Throwable $exception) {
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::GUEST_REMOVED_FROM_EVENT, 'entity_type' => 'event', 'entity_id' => $eventId, 'success' => 0, 'error_message' => mb_substr($exception->getMessage(), 0, 250), 'payload' => ['source' => 'dashboard', 'guest_id' => $guestId]]);
			$this->dashboardJson(false, $exception->getMessage(), 400);
		}
	}

	public function dashboardGuestCreate(): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) $this->dashboardJson(false, 'Token CSRF non valido.', 403);
		$name = trim((string) ($_POST['name'] ?? ''));
		if (mb_strlen($name) < 2 || mb_strlen($name) > 255) $this->dashboardJson(false, 'Nome guest non valido.', 422);
		try {
			$id = (int) $this->guestModel->create($name);
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::GUEST_CREATED, 'entity_type' => 'guest', 'entity_id' => $id, 'payload' => ['source' => 'dashboard']]);
			$this->dashboardJson(true, 'Guest creato.', 200, ['guest' => ['id' => $id, 'name' => $name]]);
		} catch (\Throwable $exception) {
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::GUEST_CREATED, 'entity_type' => 'guest', 'entity_id' => null, 'success' => 0, 'error_message' => $exception->getMessage(), 'payload' => ['source' => 'dashboard']]);
			$this->dashboardJson(false, 'Impossibile creare il guest.', 400);
		}
	}

	public function organizationGuestCreate(array $params): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$eventId = (int) ($params[0] ?? 0);
		$event = $this->organizationService->findEventForUser($eventId, $userId);
		if (!$event || !in_array($event['organization_user_role'], ['owner', 'admin', 'editor'], true)) $this->dashboardJson(false, 'Accesso non autorizzato.', 403);
		if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) $this->dashboardJson(false, 'Token CSRF non valido.', 403);
		$name = trim((string) ($_POST['name'] ?? ''));
		if (mb_strlen($name) < 2 || mb_strlen($name) > 255) $this->dashboardJson(false, 'Nome guest non valido.', 422);
		try {
			$id = (int) $this->guestModel->create($name);
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::GUEST_CREATED, 'entity_type' => 'guest', 'entity_id' => $id, 'payload' => ['source' => 'dashboard', 'event_id' => $eventId]]);
			$this->dashboardJson(true, 'Guest creato.', 200, ['guest' => ['id' => $id, 'name' => $name]]);
		} catch (\Throwable $exception) {
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::GUEST_CREATED, 'entity_type' => 'guest', 'success' => 0, 'error_message' => $exception->getMessage(), 'payload' => ['source' => 'dashboard', 'event_id' => $eventId]]);
			$this->dashboardJson(false, 'Impossibile creare il guest.', 400);
		}
	}

	public function updateOrganizationEvent(array $params): void
	{
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$organizationId = (int) ($params[0] ?? 0);
		$eventId = (int) ($params[1] ?? 0);
		$event = $this->organizationService->findEventForUser($eventId, $userId);
		$json = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
		if (!$event || (int) $event['organization_id'] !== $organizationId || !in_array($event['organization_user_role'], ['owner', 'admin', 'editor'], true)) {
			if ($json) $this->dashboardJson(false, 'Non hai i permessi per modificare questa edizione.', 403);
			header('Location: /dashboard/organizations/' . $organizationId); exit();
		}
		if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) {
			if ($json) $this->dashboardJson(false, 'Token CSRF non valido.', 403);
			header('Location: /dashboard/organizations/' . $organizationId . '/events/' . $eventId . '/edit'); exit();
		}
		try {
			$data = $event;
			foreach (['titolo', 'descrizione', 'data_inizio', 'data_fine', 'luogo', 'sito_web', 'social_facebook', 'social_twitter', 'social_instagram', 'social_tiktok', 'social_youtube'] as $field) {
				if (array_key_exists($field, $_POST)) $data[$field] = trim((string) $_POST[$field]);
			}
			if ($data['titolo'] === '' || $data['data_inizio'] === '' || $data['luogo'] === '') throw new \InvalidArgumentException('Titolo, data di inizio e luogo sono obbligatori.');
			if ($data['data_fine'] !== '' && strtotime($data['data_fine']) < strtotime($data['data_inizio'])) throw new \InvalidArgumentException('La data di fine non può precedere quella di inizio.');
			$data['anno'] = (int) ($_POST['anno'] ?? ($event['year'] ?? date('Y')));
			$data['regione_id'] = (int) ($_POST['regione_id'] ?? $event['regione_id']);
			$data['provincia_id'] = (int) ($_POST['provincia_id'] ?? $event['provincia_id']);
			$data['comune_id'] = (int) ($_POST['comune_id'] ?? $event['comune_id']);
			$data['tipo_evento_id'] = (int) ($_POST['tipo_evento_id'] ?? $event['tipo_evento_id']);
			$data['event_size'] = (int) ($_POST['event_size'] ?? ($event['event_size'] ?? 0));
			$data['is_paid'] = !empty($_POST['is_paid']) ? 1 : 0;
			$data['has_cosplay_contest'] = !empty($_POST['has_cosplay_contest']) ? 1 : 0;
			$data['approvato'] = (int) ($event['approvato'] ?? 0);
			$data['immagine'] = $event['immagine'] ?? null;
			$data['event_master_id'] = (int) $event['event_master_id'];
			$guestIds = json_decode((string) ($_POST['guest_ids'] ?? '[]'), true);
			$guestIds = is_array($guestIds) ? array_values(array_filter(array_map('intval', $guestIds), static fn (int $id): bool => $id > 0)) : [];
			if (!$this->eventModel->update($eventId, $data)) throw new \RuntimeException('Edizione non aggiornata.');
			$this->guestModel->saveGuests($eventId, $guestIds);
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::EVENT_UPDATED, 'entity_type' => 'event', 'entity_id' => $eventId, 'payload' => ['source' => 'dashboard', 'organization_id' => $organizationId]]);
			if ($json) $this->dashboardJson(true, 'Edizione aggiornata correttamente.');
			Session::setFlash('success', 'Edizione aggiornata correttamente.');
		} catch (\Throwable $exception) {
			$this->auditLogService->logAudit(['user_id' => $userId, 'action_type' => AuditLogActionType::EVENT_UPDATED, 'entity_type' => 'event', 'entity_id' => $eventId, 'success' => 0, 'error_message' => $exception->getMessage(), 'payload' => ['source' => 'dashboard', 'organization_id' => $organizationId]]);
			if ($json) $this->dashboardJson(false, $exception->getMessage(), 400);
			Session::setFlash('error', $exception->getMessage());
		}
		header('Location: /dashboard/organizations/' . $organizationId . '/events/' . $eventId . '/edit'); exit();
	}

	private function getOrganizationMemberRole(array $organization, int $userId): string
	{
		foreach ($organization['members'] as $member) {
			if ((int) $member['user_id'] === $userId) return (string) $member['role'];
		}
		return 'viewer';
	}

	private function dashboardJson(bool $success, string $message, int $status = 200, array $data = []): void
	{
		http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
		exit();
	}

	public function favorites(): void
	{
		$this->requireFeature('enable_favorites', 'Preferiti temporaneamente disattivati.');
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$user = $this->userModel->find($userId);
		$favorites = $this->favoriteService->getUserFavorites($userId);
		$grouped = $this->buildFavoriteGroups($favorites);

		$this->view('dashboard/favorites', [
			'user' => $user,
			'groupedFavorites' => $grouped,
			'summary' => [
				'total' => count($favorites),
				'event' => count($grouped['event'] ?? []),
				'guest' => count($grouped['guest'] ?? []),
				'blog_post' => count($grouped['blog_post'] ?? []),
				'regione' => count($grouped['regione'] ?? []),
				'provincia' => count($grouped['provincia'] ?? []),
				'comune' => count($grouped['comune'] ?? []),
			],
		], 'dashboard');
	}

	public function events(): void
	{
		$this->requireFeature('enable_personal_agenda', 'Agenda personale temporaneamente disattivata.');
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$user = $this->userModel->find($userId);
		$currentYear = (int) date('Y');
		$availableYears = $this->eventAgendaService->getUserAgendaYears($userId);
		$requestedYear = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: $currentYear;
		$selectedYear = max(2000, min(2100, (int) $requestedYear));
		$yearEvents = $this->eventAgendaService->getUserAgendaForYear($userId, $selectedYear);
		$upcomingEvents = $this->eventAgendaService->getUpcomingAgendaEvents($userId);
		$counts = $this->eventAgendaService->getUserAgendaCountForYear($userId, $selectedYear);
		$totalAgendaCount = array_sum($this->eventAgendaService->getUserAgendaCount($userId));
		$years = array_values(array_unique(array_merge($availableYears, [$currentYear, $selectedYear])));
		rsort($years);

		$this->view('dashboard/events', [
			'user' => $user,
			'yearEvents' => $yearEvents,
			'upcomingEvents' => $upcomingEvents,
			'counts' => $counts,
			'selectedYear' => $selectedYear,
			'availableYears' => $years,
			'totalAgendaCount' => $totalAgendaCount,
		], 'dashboard');
	}

	public function notifications(): void
	{
		$this->requireFeature('enable_notifications', 'Notifiche temporaneamente disattivate.');
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$user = $this->userModel->find($userId);
		$notifications = $this->notificationService->getLatest($userId, 30);
		$unreadCount = $this->notificationService->getUnreadCount($userId);

		$this->view('dashboard/notifications', [
			'user' => $user,
			'notifications' => $notifications,
			'unreadCount' => $unreadCount,
		], 'dashboard');
	}

	public function cosplay(): void
	{
		$this->requireFeature('enable_cosplay_portfolio', 'Portfolio cosplay temporaneamente disattivato.');
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$user = $this->userModel->find($userId);
		$portfolio = $this->cosplayPortfolioService->getUserPortfolio($userId);
		$characterSearch = trim((string) ($_GET['q'] ?? ''));
		$characters = $this->cosplayPortfolioService->getCharacterSuggestions($characterSearch);
		$eventSelections = $this->cosplayPortfolioService->getUserEventSelections($userId);
		$groupedEventSelections = $this->groupCosplaySelectionsByEvent($eventSelections);

		$this->view('dashboard/cosplay', [
			'user' => $user,
			'portfolio' => $portfolio,
			'characters' => $characters,
			'eventSelections' => $eventSelections,
			'groupedEventSelections' => $groupedEventSelections,
			'characterSearch' => $characterSearch,
		], 'dashboard');
	}

	public function searchCosplayEvents(): void
	{
		header('Content-Type: application/json; charset=utf-8');
		$query = trim((string) ($_GET['q'] ?? ''));
		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$excludedEventIds = [];
		if ($userId > 0) {
			$eventSelections = $this->cosplayPortfolioService->getUserEventSelections($userId);
			$excludedEventIds = array_values(array_unique(array_map(static fn (array $selection): int => (int) ($selection['event_id'] ?? 0), $eventSelections)));
		}
		$events = $this->eventSearchModel->searchApprovedEvents($query, 12, $excludedEventIds);

		echo json_encode([
			'success' => true,
			'items' => array_map(static function (array $event): array {
				$dateLabel = '';
				if (!empty($event['data_inizio'])) {
					$dateLabel = date('d/m/Y', strtotime((string) $event['data_inizio']));
				}
				if (!empty($event['data_fine']) && $event['data_fine'] !== ($event['data_inizio'] ?? null)) {
					$dateLabel .= ' - ' . date('d/m/Y', strtotime((string) $event['data_fine']));
				}

				return [
					'id' => (int) ($event['id'] ?? 0),
					'titolo' => $event['titolo'] ?? '',
					'slug' => $event['slug'] ?? '',
					'data_label' => $dateLabel,
					'location' => trim(implode(' · ', array_filter([
						$event['luogo'] ?? '',
						$event['comune_nome'] ?? '',
						$event['provincia_nome'] ?? '',
						$event['regione_nome'] ?? '',
					]))),
				];
			}, $events),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public function searchCosplayCharacters(): void
	{
		header('Content-Type: application/json; charset=utf-8');
		$query = trim((string) ($_GET['q'] ?? ''));
		$characters = $this->cosplayPortfolioService->getCharacterSuggestions($query, 25);

		echo json_encode([
			'success' => true,
			'items' => array_map(static function (array $character): array {
				return [
					'id' => (int) ($character['id'] ?? 0),
					'name_full' => $character['name_full'] ?? '',
					'name_native' => $character['name_native'] ?? '',
					'image_large' => $character['image_large'] ?? '',
					'anime_titles' => $character['anime_titles'] ?? '',
				];
			}, $characters),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public function saveCosplayPortfolio(): void
	{
		$this->requireFeature('enable_cosplay_portfolio', 'Portfolio cosplay temporaneamente disattivato.');
		$isAjax = $this->isAjaxRequest();
		if (!$this->isValidCsrfToken()) {
			$this->respondCosplayAction($isAjax, false, 'Token CSRF non valido.', '/dashboard/cosplay');
		}

		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$portfolioId = (int) ($_POST['id'] ?? 0);
		$ok = $this->cosplayPortfolioService->savePortfolioItem($userId, [
			'id' => $portfolioId,
			'anilist_character_id' => (int) ($_POST['anilist_character_id'] ?? 0),
			'custom_name' => trim((string) ($_POST['custom_name'] ?? '')),
			'notes' => trim((string) ($_POST['notes'] ?? '')),
			'reference_image_file' => $_FILES['reference_image_file'] ?? null,
			'is_public' => !empty($_POST['is_public']) ? 1 : 0,
		]);

		$portfolioItem = null;
		if ($ok) {
			$portfolioItem = $portfolioId > 0
				? $this->cosplayPortfolioService->getPortfolioItem($userId, $portfolioId)
				: $this->cosplayPortfolioService->getLatestPortfolioItemByCharacter($userId, (int) ($_POST['anilist_character_id'] ?? 0));
		}
		$this->respondCosplayAction(
			$isAjax,
			$ok,
			$ok ? 'Cosplay salvato nel portfolio.' : 'Impossibile salvare il cosplay.',
			'/dashboard/cosplay',
			[
				'portfolioItem' => $portfolioItem,
			]
		);
	}

	public function deleteCosplayPortfolio(): void
	{
		$this->requireFeature('enable_cosplay_portfolio', 'Portfolio cosplay temporaneamente disattivato.');
		$isAjax = $this->isAjaxRequest();
		if (!$this->isValidCsrfToken()) {
			$this->respondCosplayAction($isAjax, false, 'Token CSRF non valido.', '/dashboard/cosplay');
		}

		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$portfolioId = (int) ($_POST['portfolio_id'] ?? 0);
		$ok = $this->cosplayPortfolioService->deletePortfolioItem($userId, $portfolioId);

		$this->respondCosplayAction(
			$isAjax,
			$ok,
			$ok ? 'Cosplay rimosso dal portfolio.' : 'Impossibile rimuovere il cosplay.',
			'/dashboard/cosplay',
			[
				'portfolio_id' => $portfolioId,
			]
		);
	}

	public function toggleCosplayPortfolioVisibility(): void
	{
		$this->requireFeature('enable_cosplay_portfolio', 'Portfolio cosplay temporaneamente disattivato.');
		$isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
		if (!$this->isValidCsrfToken()) {
			if ($isAjax) {
				header('Content-Type: application/json; charset=utf-8');
				http_response_code(403);
				echo json_encode(['success' => false, 'message' => 'Token CSRF non valido.']);
				return;
			}
			Session::setFlash('error', 'Token CSRF non valido.');
			header('Location: /dashboard/cosplay');
			exit();
		}

		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$portfolioId = (int) ($_POST['portfolio_id'] ?? 0);
		$isPublic = !empty($_POST['is_public']);
		$ok = $this->cosplayPortfolioService->setPortfolioVisibility($userId, $portfolioId, $isPublic);

		if ($isAjax) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode([
				'success' => $ok,
				'message' => $ok ? 'Visibilità aggiornata.' : 'Impossibile aggiornare la visibilità.',
				'is_public' => $isPublic ? 1 : 0,
				'portfolio_id' => $portfolioId,
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			return;
		}

		Session::setFlash($ok ? 'success' : 'error', $ok ? 'Visibilità aggiornata.' : 'Impossibile aggiornare la visibilità.');
		header('Location: /dashboard/cosplay');
		exit();
	}

	public function saveEventCosplaySelection(): void
	{
		$this->requireFeature('enable_cosplay_portfolio', 'Portfolio cosplay temporaneamente disattivato.');
		$isAjax = $this->isAjaxRequest();
		if (!$this->isValidCsrfToken()) {
			$this->respondCosplayAction($isAjax, false, 'Token CSRF non valido.', (string) ($_POST['redirect_to'] ?? '/dashboard/cosplay'));
		}

		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$eventId = (int) ($_POST['event_id'] ?? 0);
		$portfolioId = (int) ($_POST['portfolio_id'] ?? 0);
		$status = (string) ($_POST['status'] ?? 'porterò');
		$ok = $this->cosplayPortfolioService->saveEventSelection($userId, $eventId, $portfolioId, $status);

		$this->respondCosplayAction(
			$isAjax,
			$ok,
			$ok ? 'Cosplay collegato all’evento.' : 'Impossibile collegare il cosplay all’evento.',
			(string) ($_POST['redirect_to'] ?? '/dashboard/cosplay'),
			[
				'event_id' => $eventId,
				'portfolio_id' => $portfolioId,
				'status' => $status,
				'selection' => $ok ? $this->cosplayPortfolioService->getCharacterSelectionsForEvent($userId, $eventId) : [],
				'selections' => $ok ? $this->cosplayPortfolioService->getUserEventSelections($userId) : [],
			]
		);
	}

	public function removeEventCosplaySelection(): void
	{
		$this->requireFeature('enable_cosplay_portfolio', 'Portfolio cosplay temporaneamente disattivato.');
		$isAjax = $this->isAjaxRequest();
		if (!$this->isValidCsrfToken()) {
			$this->respondCosplayAction($isAjax, false, 'Token CSRF non valido.', (string) ($_POST['redirect_to'] ?? '/dashboard/cosplay'));
		}

		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$eventId = (int) ($_POST['event_id'] ?? 0);
		$portfolioId = (int) ($_POST['portfolio_id'] ?? 0);
		$ok = $this->cosplayPortfolioService->removeEventSelection($userId, $eventId, $portfolioId > 0 ? $portfolioId : null);

		$this->respondCosplayAction(
			$isAjax,
			$ok,
			$ok ? 'Associazione cosplay rimossa.' : 'Impossibile rimuovere l’associazione.',
			(string) ($_POST['redirect_to'] ?? '/dashboard/cosplay'),
			[
				'event_id' => $eventId,
				'selection' => $ok ? $this->cosplayPortfolioService->getCharacterSelectionsForEvent($userId, $eventId) : [],
				'selections' => $ok ? $this->cosplayPortfolioService->getUserEventSelections($userId) : [],
			]
		);
	}

	public function markNotificationRead(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->isValidCsrfToken()) {
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Richiesta non valida.']);
			return;
		}

		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$notificationId = (int) ($_POST['notification_id'] ?? 0);
		if ($userId <= 0 || $notificationId <= 0) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Notifica non valida.']);
			return;
		}

		$ok = $this->notificationService->markAsRead($userId, $notificationId);
		echo json_encode([
			'success' => $ok,
			'message' => $ok ? 'Notifica segnata come letta.' : 'Impossibile aggiornare la notifica.',
		]);
	}

	public function deleteNotification(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->isValidCsrfToken()) {
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Richiesta non valida.']);
			return;
		}

		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$notificationId = (int) ($_POST['notification_id'] ?? 0);
		if ($userId <= 0 || $notificationId <= 0) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Notifica non valida.']);
			return;
		}

		$ok = $this->notificationService->deleteNotification($userId, $notificationId);
		echo json_encode([
			'success' => $ok,
			'message' => $ok ? 'Notifica eliminata.' : 'Impossibile eliminare la notifica.',
		]);
	}

	public function markAllNotificationsRead(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->isValidCsrfToken()) {
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Richiesta non valida.']);
			return;
		}

		$userId = (int) ($_SESSION['user_id'] ?? 0);
		if ($userId <= 0) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Utente non valido.']);
			return;
		}

		$ok = $this->notificationService->markAllAsRead($userId);
		echo json_encode([
			'success' => $ok,
			'message' => $ok ? 'Tutte le notifiche sono state segnate come lette.' : 'Impossibile aggiornare le notifiche.',
		]);
	}

	public function profile()
	{
		$userId = $_SESSION['user_id'] ?? null;
		$user = $this->userModel->find($userId);

		$this->view('dashboard/profile', [
			'user' => $user
		], 'dashboard');
	}

	public function updateProfile()
	{
		if (!$this->isValidCsrfToken()) {
			$this->view('dashboard/profile', [
				'error' => 'Token CSRF non valido.',
			], 'dashboard');
			return;
		}

		$userId = $_SESSION['user_id'] ?? null;
		if (!$userId) {
			header('Location: /login');
			return;
		}

		$errors = [];
		$user = $this->userModel->find($userId);

		$firstName  = trim($_POST['first_name'] ?? '');
		$lastName   = trim($_POST['last_name'] ?? '');
		$website    = trim($_POST['website'] ?? '');
		$bio        = trim($_POST['bio'] ?? '');
		$social = $_POST['social'] ?? [];
		$socialJson = json_encode($social, JSON_UNESCAPED_UNICODE);
		$comuneIdInput = trim((string) ($_POST['comune_id'] ?? ''));
		$comuneId = null;

		// VALIDAZIONI

		if ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
			$errors[] = 'URL sito non valido.';
		}

		if ($comuneIdInput !== '') {
			if (!ctype_digit($comuneIdInput)) {
				$errors[] = 'Comune non valido.';
			} else {
				$comuneId = (int) $comuneIdInput;
			}
		}

		if (!empty($errors)) {
			$this->view('dashboard/profile', [
				'error' => implode(' ', $errors),
				'user' => $user,
			], 'dashboard');
			return;
		}

		// UPDATE
		$userModel = new User();

		$userModel->update_dashboard($userId, [
			'first_name' => $firstName,
			'last_name'  => $lastName,
			'website'    => $website,
			'bio'        => $bio,
			'social'     => $socialJson,
			'comune_id'  => $comuneId
		]);
		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::PROFILE_UPDATED,
			'entity_type' => 'user',
			'entity_id' => (int) $userId,
			'success' => 1,
			'payload' => [
				'first_name' => $firstName,
				'last_name' => $lastName,
				'website' => $website,
				'comune_id' => $comuneId,
			],
		]);
		$user = $this->userModel->find($userId);
		$this->view('dashboard/profile', [
			'success' => 'Profilo aggiornato con successo.',
			'user' => $user
		], 'dashboard');
	}

	public function changePassword()
	{
		$userId = $_SESSION['user_id'] ?? null;
		$user = $this->userModel->find($userId);

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			if (!$this->isValidCsrfToken()) {
				$this->view('dashboard/change_password', [
					'user' => $user,
					'errors' => ['Sessione scaduta o richiesta non valida. Ricarica la pagina e riprova.'],
					'csrf_token' => $_SESSION['csrf_token']
				], 'dashboard');
				return;
			}

			$current = $_POST['current_password'] ?? '';
			$new     = $_POST['new_password'] ?? '';
			$confirm = $_POST['confirm_password'] ?? '';

			$errors = [];

			if (!$user || !password_verify($current, $user['password'])) {
				$errors[] = "Password attuale non corretta.";
			}
			if (strlen($new) < 8) {
				$errors[] = "La nuova password deve essere di almeno 8 caratteri.";
			}
			if ($new !== $confirm) {
				$errors[] = "La conferma della password non corrisponde.";
			}

			if (empty($errors)) {
				$this->userModel->update_dashboard_password($userId, [
					'password' => password_hash($new, PASSWORD_DEFAULT)
				]);
				$this->view('dashboard/change_password', [
					'success' => 'Password aggiornata con successo.',
					'user' => $user,
					'csrf_token' => $_SESSION['csrf_token']
				], 'dashboard');
				return;
			} else {
				$this->view('dashboard/change_password', [
					'user' => $user,
					'errors' => $errors,
					'csrf_token' => $_SESSION['csrf_token']
				], 'dashboard');
				return;
			}
		}

		// GET → mostra il form
		$this->view('dashboard/change_password', [
			'user' => $user,
			'csrf_token' => $_SESSION['csrf_token']
		], 'dashboard');
	}

	public function avatar()
	{
		$userId = $_SESSION['user_id'] ?? null;
		$user = $this->userModel->find($userId);
		$this->view('dashboard/avatar', [
			'user' => $user,
		], 'dashboard');
	}
	public function updateAvatar(){
		$userId = $_SESSION['user_id'] ?? null;
		$user = $this->userModel->find($userId);
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			if (!$this->isValidCsrfToken()) {
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Richiesta non valida. Ricarica la pagina e riprova.']);
				exit();
			}

			if (!empty($_FILES['avatar'])) {
				$newAvatarPath = $this->handleImageUpload($_FILES['avatar'], $user['avatar']);

				if ($newAvatarPath) {
					$this->userModel->update_dashboard_avatar($userId, [
						'avatar' => $newAvatarPath
					]);
					$user = $this->userModel->find($userId);
					$this->auditLogService->logAudit([
						'user_id' => $userId,
						'action_type' => AuditLogActionType::AVATAR_UPDATED,
						'entity_type' => 'user',
						'entity_id' => (int) $userId,
						'success' => 1,
						'payload' => [
							'avatar' => $newAvatarPath,
						],
					]);
					echo json_encode(['success' => true, 'avatarUrl' => $user['avatar']]);
					exit();
				}
				// Se handleImageUpload ha dato errore, viene già settato il flash
			} else {
				echo json_encode(['success' => false, 'message' => 'errore']);
				exit();
			}
		}
	}

	public function toggleFavorite(): void
	{
		$this->requireFeature('enable_favorites', 'Preferiti temporaneamente disattivati.');
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Location: /dashboard/favorites');
			exit();
		}

		if (!$this->isValidCsrfToken()) {
			Session::setFlash('error', 'Richiesta non valida.');
			header('Location: ' . ($_POST['redirect_to'] ?? '/dashboard/favorites'));
			exit();
		}

		$userId = (int) ($_SESSION['user_id'] ?? 0);
		$entityType = trim((string) ($_POST['entity_type'] ?? ''));
		$entityId = (int) ($_POST['entity_id'] ?? 0);
		$redirectTo = $_POST['redirect_to'] ?? '/dashboard/favorites';
		$isAjax = $this->isAjaxRequest();

		if ($userId <= 0 && $entityType === 'event' && $this->pendingUserActionService->storeFavorite($entityId, (string) $redirectTo)) {
			header('Location: /login');
			exit();
		}

		if ($userId <= 0 || $entityType === '' || $entityId <= 0) {
			$this->respondFavoriteToggle($isAjax, false, false, $entityType, $entityId, $redirectTo, 'Dati non validi.');
			return;
		}

		$isFavorited = $this->favoriteService->isFavorited($userId, $entityType, $entityId);
		$ok = $isFavorited
			? $this->favoriteService->removeFavorite($userId, $entityType, $entityId)
			: $this->favoriteService->addFavorite($userId, $entityType, $entityId);
		$nowFavorited = $ok ? !$isFavorited : $isFavorited;

		$this->respondFavoriteToggle(
			$isAjax,
			$ok,
			$nowFavorited,
			$entityType,
			$entityId,
			$redirectTo,
			$ok ? ($nowFavorited ? 'Aggiunto ai preferiti.' : 'Rimosso dai preferiti.') : 'Impossibile aggiornare il preferito.'
		);
	}

	private function respondFavoriteToggle(bool $isAjax, bool $success, bool $isFavorited, string $entityType, int $entityId, string $redirectTo, string $message): void
	{
		if ($isAjax) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode([
				'success' => $success,
				'isFavorited' => $isFavorited,
				'message' => $message,
				'entity_type' => $entityType,
				'entity_id' => $entityId,
			], JSON_UNESCAPED_UNICODE);
			exit();
		}

		Session::setFlash($success ? 'success' : 'error', $message);
		header('Location: ' . $redirectTo);
		exit();
	}

	private function isAjaxRequest(): bool
	{
		return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
			|| str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
	}

	private function respondCosplayAction(bool $isAjax, bool $success, string $message, string $redirectTo, array $extra = []): void
	{
		if ($isAjax) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array_merge([
				'success' => $success,
				'message' => $message,
			], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit();
		}

		Session::setFlash($success ? 'success' : 'error', $message);
		header('Location: ' . $redirectTo);
		exit();
	}

	private function groupCosplaySelectionsByEvent(array $eventSelections): array
	{
		$grouped = [];

		foreach ($eventSelections as $selection) {
			$eventId = (int) ($selection['event_id'] ?? 0);
			if ($eventId <= 0) {
				continue;
			}

			if (!isset($grouped[$eventId])) {
				$grouped[$eventId] = [
					'event_id' => $eventId,
					'titolo' => $selection['titolo'] ?? 'Evento',
					'slug' => $selection['slug'] ?? '',
					'data_inizio' => $selection['data_inizio'] ?? null,
					'data_fine' => $selection['data_fine'] ?? null,
					'items' => [],
				];
			}

			$grouped[$eventId]['items'][] = $selection;
		}

		uasort($grouped, static function (array $left, array $right): int {
			$leftDate = (string) ($left['data_inizio'] ?? '');
			$rightDate = (string) ($right['data_inizio'] ?? '');
			return strcmp($leftDate, $rightDate);
		});

		return array_values($grouped);
	}

	/**
	 * Gestisce l'upload di un'immagine.
	 * @param array $file Il file caricato da $_FILES.
	 * @param string|null $currentImagePath Il percorso dell'immagine corrente (per la cancellazione).
	 * @return string|null Il percorso relativo dell'immagine caricata o null in caso di errore/nessun upload.
	 */
	private function handleImageUpload($file, $currentImagePath = null)
	{
		if ($file['error'] === UPLOAD_ERR_NO_FILE) {
			// Nessun file caricato, mantieni l'immagine esistente se presente
			return $currentImagePath;
		}

		if ($file['error'] !== UPLOAD_ERR_OK) {
			Session::setFlash('error', 'Errore durante l\'upload del file: ' . $file['error']);
			return null;
		}

		$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
		$maxSize = 5 * 1024 * 1024; // 5 MB

		if (!in_array($file['type'], $allowedTypes)) {
			Session::setFlash('error', 'Tipo di file non consentito. Sono ammessi solo JPEG, PNG, GIF, WEBP.');
			return null;
		}

		if ($file['size'] > $maxSize) {
			Session::setFlash('error', 'Il file è troppo grande. Dimensione massima consentita: 5MB.');
			return null;
		}

		// Crea il nome del file
		$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$baseFileName = uniqid('avatar_');
		$originalFileName = $baseFileName . '.' . $fileExtension;
		$webpFileName = $baseFileName . '.webp';

		$destinationPath = $this->uploadDir . $originalFileName;
		$webpPath = $this->uploadDir . $webpFileName;

		// Percorsi relativi per il database
		$relativeWebpPath = '/public_assets/uploads/avatar/' . $webpFileName;

		// Sposta l'immagine originale
		if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
			Session::setFlash('error', 'Impossibile spostare il file caricato.');
			return null;
		}
		$this->processAvatar($destinationPath, $destinationPath);
		// Converti in WebP
		if (!$this->convertToWebP($destinationPath, $webpPath)) {
			Session::setFlash('error', 'Errore nella conversione in WebP. L\'immagine originale è stata salvata.');
			return '/public_assets/uploads/events/' . $originalFileName;
		}

		// Elimina immagine precedente, se presente
		if ($currentImagePath && file_exists(APP_ROOT . $currentImagePath)) {
			unlink(APP_ROOT . $currentImagePath);
		}

		// (Opzionale) elimina anche l'originale se vuoi mantenere solo il WebP
		// unlink($destinationPath);

		return $relativeWebpPath;
	}
	function processAvatar($tmpFile, $destPath, $maxSize = 100) {
		list($width, $height, $type) = getimagesize($tmpFile);

		switch ($type) {
			case IMAGETYPE_JPEG:
				$src = imagecreatefromjpeg($tmpFile);
				break;
			case IMAGETYPE_PNG:
				$src = imagecreatefrompng($tmpFile);
				break;
			default:
				throw new Exception("Formato immagine non supportato");
		}

		// Crop quadrato centrato
		$side = min($width, $height);
		$srcX = (int) floor(($width - $side) / 2);
		$srcY = (int) floor(($height - $side) / 2);

		$dst = imagecreatetruecolor($maxSize, $maxSize);
		imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $maxSize, $maxSize, $side, $side);

		// Salvataggio
		switch ($type) {
			case IMAGETYPE_JPEG:
				imagejpeg($dst, $destPath, 90);
				break;
			case IMAGETYPE_PNG:
				imagepng($dst, $destPath);
				break;
		}

		imagedestroy($src);
		imagedestroy($dst);
		return true;
	}
	/**
	 * Converte un’immagine in formato WebP usando GD
	 */
	private function convertToWebP(string $source, string $destination, int $quality = 80): bool
	{
		if (!file_exists($source)) {
			return false;
		}

		$info = getimagesize($source);
		if ($info === false) {
			return false;
		}

		$mime = $info['mime'];
		switch ($mime) {
			case 'image/jpeg':
				$image = imagecreatefromjpeg($source);
				break;
			case 'image/png':
				$image = imagecreatefrompng($source);
				imagepalettetotruecolor($image);
				imagealphablending($image, true);
				imagesavealpha($image, true);
				break;
			case 'image/gif':
				$image = imagecreatefromgif($source);
				break;
			case 'image/webp':
				// Già in formato webp
				copy($source, $destination);
				return true;
			default:
				return false;
		}

		$result = imagewebp($image, $destination, $quality);
		imagedestroy($image);
		return $result;
	}

	public function cover()
	{

		$userId = $_SESSION['user_id'];
		$user = $this->userModel->find($userId);

		$this->view('dashboard/cover', [
			'user' => $user,
		], 'dashboard');
	}
	public function updateCover()
	{
		error_log(print_r($_FILES, true));
		error_log(print_r($_POST, true));

		header('Content-Type: application/json');

		if (!$this->isValidCsrfToken()) {
			echo json_encode([
				'success' => false,
				'message' => 'Richiesta non valida. Ricarica la pagina e riprova.'
			]);
			exit;
		}

		$userId = $_SESSION['user_id'] ?? null;

		if (!$userId) {
			echo json_encode([
				'success' => false,
				'message' => 'Utente non autenticato'
			]);
			exit;
		}

		$user = $this->userModel->find($userId);

		$positionX = (int)($_POST['cover_position_x'] ?? 50);
		$positionY = (int)($_POST['cover_position_y'] ?? 50);

		$uploaded = false;
		$newCoverPath = null;

		// 1. UPLOAD FILE
		if (
			isset($_FILES['cover']) &&
			$_FILES['cover']['error'] === UPLOAD_ERR_OK
		) {
			$newCoverPath = $this->handleCoverUpload(
				$_FILES['cover'],
				$user['profile_cover'] ?? null
			);

			if ($newCoverPath) {
				$uploaded = true;
			} else {
				echo json_encode([
					'success' => false,
					'message' => 'Errore upload cover'
				]);
				exit;
			}
		}

		// 2. UPDATE DB
		$this->userModel->update_dashboard_cover($userId, [
			'profile_cover' => $newCoverPath ?? $user['profile_cover'],
			'cover_position_x' => $positionX,
			'cover_position_y' => $positionY
		]);
		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::COVER_UPDATED,
			'entity_type' => 'user',
			'entity_id' => (int) $userId,
			'success' => 1,
			'payload' => [
				'profile_cover' => $newCoverPath ?? $user['profile_cover'],
				'cover_position_x' => $positionX,
				'cover_position_y' => $positionY,
				'uploaded' => $uploaded,
			],
		]);

		// 3. RESPONSE SEMPRE
		echo json_encode([
			'success' => true,
			'message' => $uploaded ? 'Cover aggiornata' : 'Posizione aggiornata',
			'coverUrl' => $newCoverPath ?? $user['profile_cover'],
			'x' => $positionX,
			'y' => $positionY
		]);

		exit;
	}
	private function handleCoverUpload($file, $currentImagePath = null)
	{
		error_log('COVER FILE: ' . print_r($file, true));
		if ($file['error'] !== UPLOAD_ERR_OK) {
			return null;
		}

		$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
		$maxSize = 5 * 1024 * 1024;

		if (!in_array($file['type'], $allowedTypes)) return null;
		if ($file['size'] > $maxSize) return null;

		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$base = uniqid('cover_');

		$original = $base . '.' . $ext;
		$webp = $base . '.webp';

		$dest = APP_ROOT . '/public_assets/uploads/covers/' . $original;
		$destWebp = APP_ROOT . '/public_assets/uploads/covers/' . $webp;

		$relative = '/public_assets/uploads/covers/' . $webp;

		if (!move_uploaded_file($file['tmp_name'], $dest)) {
			return null;
		}

		$this->convertToWebP($dest, $destWebp);

		if ($currentImagePath && file_exists(APP_ROOT . $currentImagePath)) {
			@unlink(APP_ROOT . $currentImagePath);
		}

		return $relative;
	}
	public function updateSettings()
	{
		$userId = $_SESSION['user_id'];

		if (!$this->isValidCsrfToken()) {
			$user = $this->userModel->find($userId);
			$this->view('dashboard/settings', [
				'user' => $user,
				'error' => 'Richiesta non valida. Ricarica la pagina e riprova.'
			], 'dashboard');
			return;
		}

		$settings = $_POST['settings'] ?? [];

		// normalizza checkbox (non selezionati non arrivano)
		$all = [
			'show_email' => !empty($settings['show_email']),
			'show_bio' => !empty($settings['show_bio']),
			'show_comune' => !empty($settings['show_comune']),
			'show_instagram' => !empty($settings['show_instagram']),
			'show_facebook' => !empty($settings['show_facebook']),
			'show_tiktok' => !empty($settings['show_tiktok']),
			'show_youtube' => !empty($settings['show_youtube']),
			'show_nome' => !empty($settings['show_nome']),
			'newsletter_opt_in' => !empty($settings['newsletter_opt_in']),
		];

		$this->userModel->updateProfileSettings($userId, $all);
		$this->consentService->updateMarketingConsent($userId, !empty($settings['newsletter_opt_in']));
		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::PROFILE_SETTINGS_UPDATED,
			'entity_type' => 'user',
			'entity_id' => (int) $userId,
			'success' => 1,
			'payload' => $all,
		]);

		$user = $this->userModel->find($userId);

		$this->view('dashboard/settings', [
			'user' => $user,
			'success' => 'Impostazioni aggiornate con successo'
		], 'dashboard');
	}
	public function settings()
	{

		$userId = $_SESSION['user_id'] ?? null;
		$user = $this->userModel->find($userId);

		$this->view('dashboard/settings', [
			'user' => $user
		], 'dashboard');
	}

	public function deleteAccount()
	{
		$userId = $_SESSION['user_id'] ?? null;

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Location: /dashboard/settings');
			exit();
		}

		if (!$userId) {
			header('Location: /login');
			exit();
		}

		if (!$this->isValidCsrfToken()) {
			$user = $this->userModel->find($userId);
			$this->view('dashboard/settings', [
				'user' => $user,
				'error' => 'Richiesta non valida. Ricarica la pagina e riprova.'
			], 'dashboard');
			return;
		}

		$anonymized = $this->userModel->anonymizeAccount((int) $userId);

		if (!$anonymized) {
			$user = $this->userModel->find($userId);
			$this->view('dashboard/settings', [
				'user' => $user,
				'error' => 'Non è stato possibile completare la cancellazione dell\'account.'
			], 'dashboard');
			return;
		}

		Session::setFlash('success', 'Il tuo account è stato anonimizzato con successo.');
		unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role']);
		session_regenerate_id(true);
		header('Location: /');
		exit();
	}

	private function buildFavoriteGroups(array $favorites): array
	{
		$groups = [
			'event' => [],
			'guest' => [],
			'blog_post' => [],
			'regione' => [],
			'provincia' => [],
			'comune' => [],
		];

		foreach ($favorites as $favorite) {
			$type = $favorite['entity_type'] ?? '';
			$id = (int) ($favorite['entity_id'] ?? 0);
			if ($id <= 0 || !array_key_exists($type, $groups)) {
				continue;
			}

			$item = match ($type) {
				'event' => $this->buildFavoriteEvent((int) $id, $favorite),
				'guest' => $this->buildFavoriteGuest((int) $id, $favorite),
				'blog_post' => $this->buildFavoriteBlogPost((int) $id, $favorite),
				'regione' => $this->buildFavoriteRegione((int) $id, $favorite),
				'provincia' => $this->buildFavoriteProvincia((int) $id, $favorite),
				'comune' => $this->buildFavoriteComune((int) $id, $favorite),
				default => null,
			};

			if ($item !== null) {
				$groups[$type][] = $item;
			}
		}

		return $groups;
	}

	private function buildFavoriteEvent(int $id, array $favorite): ?array
	{
		$event = $this->eventModel->find($id);
		if (!$event) {
			return null;
		}

		return [
			'type' => 'event',
			'id' => $id,
			'title' => $event['titolo'] ?? 'Evento',
			'slug' => $event['slug'] ?? '',
			'url' => '/eventi-cosplay/' . ($event['slug'] ?? ''),
			'image' => $this->favoriteImageForEvent($id),
			'subtitle' => trim(($event['data_inizio'] ?? '') . ' ' . ($event['luogo'] ?? '')),
			'created_at' => $favorite['created_at'] ?? null,
		];
	}

	private function buildFavoriteGuest(int $id, array $favorite): ?array
	{
		$guest = $this->guestModel->find($id);
		if (!$guest) {
			return null;
		}

		return [
			'type' => 'guest',
			'id' => $id,
			'title' => $guest['name'] ?? 'Guest',
			'slug' => $guest['slug'] ?? '',
			'url' => '/ospiti/' . ($guest['slug'] ?? ''),
			'image' => !empty($guest['immagine']) ? '/public_assets/' . ltrim($guest['immagine'], '/') : '',
			'subtitle' => mb_strimwidth(strip_tags((string) ($guest['bio'] ?? '')), 0, 120, '...'),
			'created_at' => $favorite['created_at'] ?? null,
		];
	}

	private function buildFavoriteBlogPost(int $id, array $favorite): ?array
	{
		$post = $this->blogPostModel->find($id);
		if (!$post) {
			return null;
		}

		return [
			'type' => 'blog_post',
			'id' => $id,
			'title' => $post['titolo'] ?? 'Articolo',
			'slug' => $post['slug'] ?? '',
			'url' => '/blog/' . ($post['slug'] ?? ''),
			'image' => '',
			'subtitle' => mb_strimwidth(strip_tags((string) ($post['excerpt'] ?? $post['contenuto'] ?? '')), 0, 120, '...'),
			'created_at' => $favorite['created_at'] ?? null,
		];
	}

	private function buildFavoriteComune(int $id, array $favorite): ?array
	{
		$comune = $this->comuneModel->find($id);
		if (!$comune) {
			return null;
		}
		$provincia = !empty($comune['provincia_id']) ? $this->provinciaModel->find((int) $comune['provincia_id']) : null;
		$regione = !empty($provincia['regione_id'] ?? null) ? $this->regioneModel->find((int) $provincia['regione_id']) : null;

		return [
			'type' => 'comune',
			'id' => $id,
			'title' => $comune['nome'] ?? 'Comune',
			'slug' => $comune['slug'] ?? '',
			'url' => !empty($regione['slug'] ?? null) && !empty($provincia['slug'] ?? null) && !empty($comune['slug'] ?? null)
				? '/eventi-cosplay/' . $regione['slug'] . '/' . $provincia['slug'] . '/' . $comune['slug']
				: '/eventi-cosplay',
			'image' => '',
			'subtitle' => trim(($provincia['nome'] ?? '') . (!empty($regione['nome']) ? ', ' . $regione['nome'] : '')),
			'created_at' => $favorite['created_at'] ?? null,
		];
	}

	private function buildFavoriteRegione(int $id, array $favorite): ?array
	{
		$regione = $this->regioneModel->find($id);
		if (!$regione) {
			return null;
		}

		return [
			'type' => 'regione',
			'id' => $id,
			'title' => $regione['nome'] ?? 'Regione',
			'slug' => $regione['slug'] ?? '',
			'url' => !empty($regione['slug']) ? '/eventi-cosplay/' . $regione['slug'] : '/eventi-cosplay',
			'image' => '',
			'subtitle' => 'Regione',
			'created_at' => $favorite['created_at'] ?? null,
		];
	}

	private function buildFavoriteProvincia(int $id, array $favorite): ?array
	{
		$provincia = $this->provinciaModel->find($id);
		if (!$provincia) {
			return null;
		}

		$regione = !empty($provincia['regione_id']) ? $this->regioneModel->find((int) $provincia['regione_id']) : null;

		return [
			'type' => 'provincia',
			'id' => $id,
			'title' => $provincia['nome'] ?? 'Provincia',
			'slug' => $provincia['slug'] ?? '',
			'url' => !empty($regione['slug'] ?? null) && !empty($provincia['slug'] ?? null)
				? '/eventi-cosplay/' . $regione['slug'] . '/' . $provincia['slug']
				: '/eventi-cosplay',
			'image' => '',
			'subtitle' => trim(($regione['nome'] ?? '') . ' · Provincia'),
			'created_at' => $favorite['created_at'] ?? null,
		];
	}

	private function favoriteImageForEvent(int $eventId): string
	{
		$event = $this->eventModel->find($eventId);
		if (empty($event)) {
			return '';
		}

		return !empty($event['immagine']) ? '/public_assets/' . ltrim($event['immagine'], '/') : '';
	}

	private function isValidCsrfToken(): bool
	{
		return !empty($_POST['csrf_token'])
			&& !empty($_SESSION['csrf_token'])
			&& hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
	}
}
