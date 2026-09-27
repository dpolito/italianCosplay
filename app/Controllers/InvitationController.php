<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\UserInvitationService;

class InvitationController extends Controller
{
	private UserInvitationService $userInvitationService;

	public function __construct()
	{
		$this->userInvitationService = new UserInvitationService();
	}

	public function block(): void
	{
		$token = trim((string) ($_GET['token'] ?? ''));
		$blocked = $token !== '' && $this->userInvitationService->blockByToken($token);

		$this->view('home/invitation-blocked', [
			'blocked' => $blocked,
			'pageTitle' => 'Preferenze inviti | ItalianCosplay',
			'noindex' => true,
		]);
	}

	public function accept(): void
	{
		$token = trim((string) ($_GET['token'] ?? ''));
		$invitation = $this->userInvitationService->findPendingByToken($token);
		if (!$invitation) {
			Session::setFlash('error', 'Invito non valido o scaduto.');
			header('Location: /register');
			exit();
		}

		if (!empty($_SESSION['user_id'])) {
			header('Location: /dashboard/inviti/accetta?token=' . rawurlencode($token));
			exit();
		}

		$_SESSION['pending_user_invitation'] = [
			'token' => $token,
			'email' => (string) $invitation['email'],
			'inviter_username' => (string) ($invitation['inviter_username'] ?? ''),
		];

		if (!empty($invitation['invited_user_id'])) {
			Session::setFlash('info', 'Questo invito è associato a un account già registrato. Accedi con quella email per accettarlo.');
			header('Location: /login');
			exit();
		}

		header('Location: /register');
		exit();
	}
}
