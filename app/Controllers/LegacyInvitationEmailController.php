<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\LegacyInvitationEmailService;

class LegacyInvitationEmailController
{
	private const AGENDA_URL = 'https://www.italiancosplay.it/agenda-cosplay';

	public function agenda(): void
	{
		$token = (string) ($_GET['c'] ?? '');
		$service = new LegacyInvitationEmailService();
		$service->trackClick(
			$token,
			$_SERVER['HTTP_USER_AGENT'] ?? null
		);

		header('Location: ' . self::AGENDA_URL, true, 302);
		exit();
	}
}
