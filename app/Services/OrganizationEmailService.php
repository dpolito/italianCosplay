<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Mailer;
use App\Repositories\OrganizationEmailDeliveryRepository;
use InvalidArgumentException;

class OrganizationEmailService
{
	private OrganizationEmailDeliveryRepository $repository;

	public function __construct()
	{
		$this->repository = new OrganizationEmailDeliveryRepository();
	}

	public function getOverview(): array
	{
		$organizations = $this->repository->findOrganizationsWithEmailStatus();
		$pending = 0;
		$sent = 0;
		$failed = 0;

		foreach ($organizations as $organization) {
			if (($organization['delivery_status'] ?? null) === 'sent') {
				$sent++;
			} elseif (($organization['delivery_status'] ?? null) === 'failed') {
				$failed++;
			} else {
				$pending++;
			}
		}

		return [
			'organizations' => $organizations,
			'pending_count' => $pending,
			'sent_count' => $sent,
			'failed_count' => $failed,
		];
	}

	public function sendToPendingOrganizations(string $subject, string $body, int $sentBy): array
	{
		$subject = trim($subject);
		$body = trim($body);

		$this->validateMessage($subject, $body);

		$recipients = $this->repository->findPendingRecipients();
		$mailer = new Mailer();
		$result = ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'total' => count($recipients)];

		foreach ($recipients as $recipient) {
			$email = trim((string) ($recipient['email'] ?? ''));
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$this->repository->recordFailed((int) $recipient['id'], $email, $subject, $sentBy, 'Email non valida.');
				$result['failed']++;
				continue;
			}

			$html = $this->renderEmailBody((string) $recipient['name'], $body);
			$sent = $mailer->send($email, (string) $recipient['name'], $subject, $html, null, [Mailer::TAG_ORGANIZATION_EMAIL]);
			if ($sent) {
				$this->repository->recordSent((int) $recipient['id'], $email, $subject, $sentBy);
				$result['sent']++;
			} else {
				$this->repository->recordFailed((int) $recipient['id'], $email, $subject, $sentBy, 'Invio SMTP non riuscito.');
				$result['failed']++;
			}
		}

		return $result;
	}

	public function sendTest(string $email, string $subject, string $body): bool
	{
		$email = trim($email);
		$subject = trim($subject);
		$body = trim($body);

		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			throw new InvalidArgumentException('Email di test non valida.');
		}
		$this->validateMessage($subject, $body);

		return (new Mailer())->send($email, 'Test ItalianCosplay', '[TEST] ' . $subject, $this->renderEmailBody('Test ItalianCosplay', $body), null, [Mailer::TAG_ORGANIZATION_EMAIL]);
	}

	private function validateMessage(string $subject, string $body): void
	{
		if (mb_strlen($subject) < 5 || mb_strlen($subject) > 255) {
			throw new InvalidArgumentException('Oggetto email non valido.');
		}
		if (mb_strlen($body) < 20 || mb_strlen($body) > 10000) {
			throw new InvalidArgumentException('Testo email non valido.');
		}
	}

	private function renderEmailBody(string $organizationName, string $body): string
	{
		$safeName = htmlspecialchars($organizationName, ENT_QUOTES, 'UTF-8');
		$safeBody = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));

		return '<!doctype html>'
			. '<html lang="it" xmlns="http://www.w3.org/1999/xhtml">'
			. '<head>'
			. '<meta charset="UTF-8">'
			. '<meta name="viewport" content="width=device-width">'
			. '<meta http-equiv="X-UA-Compatible" content="IE=edge">'
			. '<meta name="x-apple-disable-message-reformatting">'
			. '<title>ItalianCosplay.it</title>'
			. '<style>'
			. 'html,body{margin:0 auto!important;padding:0!important;height:100%!important;width:100%!important;background:#f3f4f6;}'
			. '*{-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;}'
			. 'table,td{mso-table-lspace:0pt!important;mso-table-rspace:0pt!important;}'
			. 'table{border-spacing:0!important;border-collapse:collapse!important;table-layout:fixed!important;margin:0 auto!important;}'
			. 'img{-ms-interpolation-mode:bicubic;}'
			. 'a{text-decoration:none;}'
			. '.email-body a{color:#15803d!important;font-weight:700;}'
			. '@media screen and (max-width:500px){.email-section{padding:28px 22px!important;}.email-title{font-size:24px!important;}.footer-col{display:block!important;width:100%!important;}}'
			. '</style>'
			. '</head>'
			. '<body width="100%" style="margin:0;padding:0!important;mso-line-height-rule:exactly;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">'
			. '<center style="width:100%;background-color:#f3f4f6;">'
			. '<div style="display:none;font-size:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">Il tuo evento cosplay è su ItalianCosplay.it e può essere gestito direttamente.</div>'
			. '<div style="max-width:620px;margin:0 auto;" class="email-container">'
			. '<table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:auto;">'
			. '<tr><td style="padding:24px 28px;text-align:center;background:#ffffff;">'
			. '<img src="https://www.italiancosplay.it/public_assets/images/logo_italian_cosplay.png" width="210" alt="ItalianCosplay.it" style="height:auto;max-width:210px;border:0;display:inline-block;">'
			. '</td></tr>'
			. '<tr><td style="background:#14532d;padding:34px 34px 32px;text-align:center;color:#ffffff;">'
			. '<div style="font-size:12px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#bbf7d0;">Organizzatori eventi cosplay</div>'
			. '<h1 class="email-title" style="margin:12px 0 0;font-size:30px;line-height:1.25;font-weight:800;color:#ffffff;">Il tuo evento è già su ItalianCosplay.it</h1>'
			. '<p style="margin:14px 0 0;font-size:16px;line-height:1.6;color:#dcfce7;">Puoi riscattarne la gestione e mantenerlo aggiornato con gli strumenti del sito.</p>'
			. '</td></tr>'
			. '<tr><td class="email-section" style="background:#ffffff;padding:38px 42px;">'
			. '<p style="margin:0 0 18px;font-size:16px;line-height:1.7;color:#1f2937;">Ciao <strong style="color:#14532d;">' . $safeName . '</strong>,</p>'
			. '<div class="email-body" style="font-size:15px;line-height:1.8;color:#1f2937;">' . $safeBody . '</div>'
			. '</td></tr>'
			. '<tr><td style="background:#dcfce7;padding:34px 42px;text-align:center;">'
			. '<h2 style="margin:0;color:#14532d;font-size:21px;line-height:1.35;font-weight:800;">Vuoi saperne di più?</h2>'
			. '<p style="margin:12px 0 24px;color:#166534;font-size:15px;line-height:1.7;">Abbiamo preparato una pagina per spiegare come funziona il riscatto degli eventi e perché può essere utile agli organizzatori.</p>'
			. '<a href="https://www.italiancosplay.it/organizzatori-eventi-cosplay" style="display:inline-block;background:#15803d;color:#ffffff;text-decoration:none;font-weight:800;border-radius:8px;padding:13px 22px;font-size:15px;">Scopri come funziona</a>'
			. '</td></tr>'
			. '<tr><td style="background:#111827;padding:30px 34px;color:#d1d5db;">'
			. '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">'
			. '<tr>'
			. '<td class="footer-col" width="50%" valign="top" style="padding-right:12px;">'
			. '<h3 style="margin:0 0 10px;color:#ffffff;font-size:14px;line-height:1.4;">ItalianCosplay.it</h3>'
			. '<p style="margin:0;font-size:12px;line-height:1.7;color:#d1d5db;">Calendario italiano dedicato a eventi cosplay, fiere comics, festival nerd e appuntamenti pop culture.</p>'
			. '</td>'
			. '<td class="footer-col" width="50%" valign="top" style="padding-left:12px;">'
			. '<h3 style="margin:0 0 10px;color:#ffffff;font-size:14px;line-height:1.4;">Contatti</h3>'
			. '<p style="margin:0;font-size:12px;line-height:1.7;color:#d1d5db;">Per richieste o correzioni puoi scrivere a <a href="mailto:mail@italiancosplay.it" style="color:#bbf7d0;font-weight:700;">mail@italiancosplay.it</a>.</p>'
			. '</td>'
			. '</tr>'
			. '</table>'
			. '</td></tr>'
			. '<tr><td style="background:#030712;padding:18px 34px;text-align:center;color:#9ca3af;font-size:11px;line-height:1.6;">'
			. '&copy; ' . date('Y') . ' ItalianCosplay.it. Ricevi questa comunicazione perché la tua organizzazione o il tuo evento risultano censiti nel calendario.'
			. '</td></tr>'
			. '</table>'
			. '</div>'
			. '</center>'
			. '</body>'
			. '</html>';
	}
}
