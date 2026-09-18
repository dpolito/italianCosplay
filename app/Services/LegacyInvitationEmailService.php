<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Mailer;
use App\Repositories\LegacyInvitationEmailRepository;
use InvalidArgumentException;

class LegacyInvitationEmailService
{
	private const AGENDA_URL = 'https://www.italiancosplay.it/agenda-cosplay';
	private const TRACKING_URL = 'https://www.italiancosplay.it/legacy-invitation/agenda';

	private LegacyInvitationEmailRepository $repository;

	public function __construct()
	{
		$this->repository = new LegacyInvitationEmailRepository();
	}

	public function getOverview(): array
	{
		$recipients = $this->repository->findAllWithStatus();
		$pending = 0;
		$sent = 0;
		$failed = 0;
		$clicked = 0;

		foreach ($recipients as $recipient) {
			if (($recipient['status'] ?? null) === 'sent') {
				$sent++;
			} elseif (($recipient['status'] ?? null) === 'failed') {
				$failed++;
			} else {
				$pending++;
			}
			if ((int) ($recipient['click_count'] ?? 0) > 0) {
				$clicked++;
			}
		}

		return [
			'recipients' => $recipients,
			'pending_count' => $pending,
			'sent_count' => $sent,
			'failed_count' => $failed,
			'clicked_count' => $clicked,
		];
	}

	public function getAdminList(array $query): array
	{
		$page = max((int) ($query['page'] ?? 1), 1);
		$perPage = (int) ($query['perPage'] ?? 25);
		if (!in_array($perPage, [10, 25, 50, 100], true)) {
			$perPage = 25;
		}

		return $this->repository->findForAdminList(
			(string) ($query['search'] ?? ''),
			(string) ($query['sort'] ?? 'id'),
			(string) ($query['direction'] ?? 'desc'),
			$page,
			$perPage
		);
	}

	public function importRecipients(string $rawEmails, ?string $sourceLabel): array
	{
		$sourceLabel = trim((string) $sourceLabel) !== '' ? mb_substr(trim((string) $sourceLabel), 0, 120) : null;
		$lines = preg_split('/[\r\n,;]+/', $rawEmails) ?: [];
		$result = ['created' => 0, 'duplicates' => 0, 'invalid' => 0];

		foreach ($lines as $line) {
			$email = mb_strtolower(trim($line));
			if ($email === '') {
				continue;
			}
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$result['invalid']++;
				continue;
			}

			$created = $this->repository->insertRecipient($email, null, $sourceLabel, null, hash('sha256', $email . random_bytes(32)));
			if ($created) {
				$result['created']++;
			} else {
				$result['duplicates']++;
			}
		}

		return $result;
	}

	public function importRecipientsFromJsonUpload(array $file, ?string $sourceLabel): array
	{
		if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
			throw new InvalidArgumentException('File JSON non caricato correttamente.');
		}

		$fileName = (string) ($file['name'] ?? '');
		if (!str_ends_with(mb_strtolower($fileName), '.json')) {
			throw new InvalidArgumentException('Il file deve essere in formato .json.');
		}

		$fileSize = (int) ($file['size'] ?? 0);
		if ($fileSize < 1 || $fileSize > 5 * 1024 * 1024) {
			throw new InvalidArgumentException('Il file JSON deve essere inferiore a 5 MB.');
		}

		$tmpName = (string) ($file['tmp_name'] ?? '');
		$content = is_uploaded_file($tmpName) ? file_get_contents($tmpName) : false;
		if ($content === false) {
			throw new InvalidArgumentException('Impossibile leggere il file JSON.');
		}

		$decoded = json_decode($content, true);
		if (!is_array($decoded)) {
			throw new InvalidArgumentException('JSON non valido: atteso un array di email o di oggetti.');
		}

		$sourceLabel = trim((string) $sourceLabel) !== '' ? mb_substr(trim((string) $sourceLabel), 0, 120) : 'utenti_email_ultima_visita.json';
		$result = ['created' => 0, 'duplicates' => 0, 'invalid' => 0];

		foreach ($decoded as $item) {
			$email = null;
			$lastVisitedAt = null;

			if (is_string($item)) {
				$email = $item;
			} elseif (is_array($item)) {
				$email = $item['email'] ?? $item['Email'] ?? $item['mail'] ?? null;
				$lastVisitedAt = $this->normalizeDateTime($item['ultima_visita'] ?? $item['last_visit'] ?? $item['last_visited_at'] ?? $item['ultimo_accesso'] ?? null);
			}

			$email = mb_strtolower(trim((string) $email));
			if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$result['invalid']++;
				continue;
			}

			$created = $this->repository->insertRecipient($email, null, $sourceLabel, $lastVisitedAt, hash('sha256', $email . random_bytes(32)));
			if ($created) {
				$result['created']++;
			} else {
				$result['duplicates']++;
			}
		}

		return $result;
	}

	public function sendToPendingRecipients(string $subject, string $body, int $sentBy, int $batchLimit = 50): array
	{
		$subject = trim($subject);
		$body = trim($body);
		$this->validateMessage($subject, $body);
		$batchLimit = max(1, min(200, $batchLimit));

		$recipients = $this->repository->findPendingRecipients($batchLimit);
		$mailer = new Mailer();
		$result = ['sent' => 0, 'failed' => 0, 'total' => count($recipients), 'batch_limit' => $batchLimit];

		foreach ($recipients as $recipient) {
			$email = trim((string) ($recipient['email'] ?? ''));
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$this->repository->recordFailed((int) $recipient['id'], $subject, $sentBy, 'Email non valida.');
				$result['failed']++;
				continue;
			}

			$name = (string) ($recipient['recipient_name'] ?: 'Cosplayer');
			$trackingUrl = $this->buildTrackingUrl((string) $recipient['tracking_token']);
			$html = $this->renderEmailBody($name, $body, $trackingUrl);
			$sent = $mailer->send($email, $name, $subject, $html, null, [Mailer::TAG_LEGACY_INVITATION]);

			if ($sent) {
				$this->repository->recordSent((int) $recipient['id'], $subject, $sentBy);
				$result['sent']++;
			} else {
				$this->repository->recordFailed((int) $recipient['id'], $subject, $sentBy, 'Invio SMTP non riuscito.');
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

		$testUrl = self::AGENDA_URL . '?utm_source=legacy_email_test&utm_medium=email&utm_campaign=legacy_reinvite';

		return (new Mailer())->send($email, 'Test ItalianCosplay', '[TEST] ' . $subject, $this->renderEmailBody('Test ItalianCosplay', $body, $testUrl), null, [Mailer::TAG_LEGACY_INVITATION]);
	}

	public function trackClick(string $trackingToken, ?string $userAgent): ?array
	{
		if (!preg_match('/^[a-f0-9]{64}$/', $trackingToken)) {
			return null;
		}

		return $this->repository->recordClick($trackingToken, $userAgent);
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

	private function normalizeDateTime(mixed $value): ?string
	{
		if (!is_string($value) && !is_numeric($value)) {
			return null;
		}

		$raw = trim((string) $value);
		if ($raw === '') {
			return null;
		}

		$timestamp = is_numeric($raw) ? (int) $raw : strtotime($raw);
		if ($timestamp === false || $timestamp < 1) {
			return null;
		}

		return date('Y-m-d H:i:s', $timestamp);
	}

	private function buildTrackingUrl(string $trackingToken): string
	{
		return self::TRACKING_URL . '?c=' . rawurlencode($trackingToken)
			. '&utm_source=legacy_email&utm_medium=email&utm_campaign=legacy_reinvite';
	}

	private function renderEmailBody(string $recipientName, string $body, string $trackingUrl): string
	{
		$safeName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
		$safeBody = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
		$safeTrackingUrl = htmlspecialchars($trackingUrl, ENT_QUOTES, 'UTF-8');

		return '<!doctype html>'
			. '<html lang="it" xmlns="http://www.w3.org/1999/xhtml">'
			. '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width"><title>ItalianCosplay.it</title>'
			. '<style>body{margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;}a{text-decoration:none;}.email-body a{color:#15803d;font-weight:700;}@media screen and (max-width:500px){.email-section{padding:28px 22px!important;}.email-title{font-size:24px!important;}}</style>'
			. '</head>'
			. '<body><center style="width:100%;background:#f3f4f6;">'
			. '<div style="display:none;font-size:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">ItalianCosplay.it è tornato con un sito rinnovato e una nuova agenda cosplay.</div>'
			. '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width:620px;margin:0 auto;background:#ffffff;">'
			. '<tr><td style="padding:24px 28px;text-align:center;"><img src="https://www.italiancosplay.it/public_assets/images/logo_italian_cosplay.png" width="210" alt="ItalianCosplay.it" style="height:auto;max-width:210px;border:0;"></td></tr>'
			. '<tr><td style="background:#14532d;padding:34px;text-align:center;color:#ffffff;"><div style="font-size:12px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#bbf7d0;">Il nuovo ItalianCosplay.it</div><h1 class="email-title" style="margin:12px 0 0;font-size:30px;line-height:1.25;font-weight:800;color:#ffffff;">La tua agenda cosplay è pronta</h1><p style="margin:14px 0 0;font-size:16px;line-height:1.6;color:#dcfce7;">Abbiamo migliorato il sito per aiutarti a scoprire e seguire gli eventi cosplay in Italia.</p></td></tr>'
			. '<tr><td class="email-section" style="background:#ffffff;padding:38px 42px;"><p style="margin:0 0 18px;font-size:16px;line-height:1.7;color:#1f2937;">Ciao <strong style="color:#14532d;">' . $safeName . '</strong>,</p><div class="email-body" style="font-size:15px;line-height:1.8;color:#1f2937;">' . $safeBody . '</div></td></tr>'
			. '<tr><td style="background:#dcfce7;padding:34px 42px;text-align:center;"><h2 style="margin:0;color:#14532d;font-size:21px;line-height:1.35;font-weight:800;">Riscopri il calendario cosplay</h2><p style="margin:12px 0 24px;color:#166534;font-size:15px;line-height:1.7;">Trova eventi, fiere comics e appuntamenti pop culture, poi salvali nella tua agenda personale.</p><a href="' . $safeTrackingUrl . '" style="display:inline-block;background:#15803d;color:#ffffff;text-decoration:none;font-weight:800;border-radius:8px;padding:13px 22px;font-size:15px;">Vai all’agenda cosplay</a></td></tr>'
			. '<tr><td style="background:#111827;padding:30px 34px;color:#d1d5db;"><h3 style="margin:0 0 10px;color:#ffffff;font-size:14px;">ItalianCosplay.it</h3><p style="margin:0;font-size:12px;line-height:1.7;color:#d1d5db;">Calendario italiano dedicato a eventi cosplay, fiere comics, festival nerd e appuntamenti pop culture. Per richieste puoi scrivere a <a href="mailto:mail@italiancosplay.it" style="color:#bbf7d0;font-weight:700;">mail@italiancosplay.it</a>.</p></td></tr>'
			. '<tr><td style="background:#030712;padding:18px 34px;text-align:center;color:#9ca3af;font-size:11px;line-height:1.6;">&copy; ' . date('Y') . ' ItalianCosplay.it. Ricevi questa comunicazione perché il tuo indirizzo era presente nei contatti del vecchio sito.</td></tr>'
			. '</table></center></body></html>';
	}
}
