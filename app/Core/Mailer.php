<?php

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
	private PHPMailer $mail;

	public const TAG_REGISTRATION = 'registration';
	public const TAG_PASSWORD_RESET = 'password_reset';
	public const TAG_EVENT_CLAIM = 'event_claim';
	public const TAG_ORGANIZATION_INVITATION = 'organization_invitation';
	public const TAG_EVENT_APPROVED = 'event_approved';
	public const TAG_EVENT_REPORT = 'event_report';
	public const TAG_ORGANIZATION_EMAIL = 'organization_email';
	public const TAG_LEGACY_INVITATION = 'legacy_invitation';
	public const TAG_ADVERTISING = 'advertising';

	public function __construct()
	{
		// Carica manualmente i file di PHPMailer
		require_once __DIR__ . '/../../libs/PHPMailer/PHPMailer.php';
		require_once __DIR__ . '/../../libs/PHPMailer/SMTP.php';
		require_once __DIR__ . '/../../libs/PHPMailer/Exception.php';

		$this->mail = new PHPMailer(true);

		// Configurazione di base
		$this->mail->isSMTP();
		$this->mail->Host       = 'smtp-relay.sendinblue.com';   // 🔹 server SMTP
		$this->mail->SMTPAuth   = true;
		$this->mail->Username   = 'd.polito81@gmail.com';   // 🔹 tua email
		$this->mail->Password   = 'XzHWYBIpAQq0DUES';           // 🔹 tua password
		$this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // o 'ssl'
		$this->mail->Port       = 587;                  // 🔹 porta (es. 465 per SSL)

		$this->mail->CharSet    = 'UTF-8';
		$this->mail->isHTML(true);
		$this->mail->setFrom('mail@italiancosplay.it', 'ItalianCosplay');
	}

	/**
	 * Invia una mail.
	 *
	 * @param string $toEmail
	 * @param string $toName
	 * @param string $subject
	 * @param string $bodyHtml
	 * @param string|null $bodyText
	 * @return bool
	 */
	public function send(
		string $toEmail,
		string $toName,
		string $subject,
		string $bodyHtml,
		?string $bodyText = null,
		array $tags = []
	): bool {
		try {
			$this->mail->clearAddresses();
			$this->mail->clearCustomHeaders();

			$this->mail->addAddress($toEmail, $toName);
			$this->mail->Subject = $subject;
			$this->mail->Body    = $bodyHtml;
			$this->mail->AltBody = $bodyText ?? strip_tags($bodyHtml);

			$this->applyTags($tags);

			$sent = $this->mail->send();

		} catch (\Throwable $e) {
			$error = $this->mail->ErrorInfo ?: $e->getMessage();

			error_log('Mailer exception: ' . $error);

			throw new \RuntimeException(
				'Errore durante l\'invio email: ' . $error,
				0,
				$e
			);
		}

		if (!$sent) {
			$error = $this->mail->ErrorInfo ?: 'Errore SMTP sconosciuto.';

			error_log('Mailer error: ' . $error);

			throw new \RuntimeException(
				'Invio email fallito: ' . $error
			);
		}

		return true;
	}

	private function applyTags(array $tags): void
	{
		$cleanTags = array_values(array_filter(array_map(static function ($tag): string {
			$tag = strtolower(trim((string) $tag));

			return preg_replace('/[^a-z0-9_-]/', '', $tag) ?? '';
		}, $tags)));

		if ($cleanTags === []) {
			return;
		}

		$this->mail->addCustomHeader('X-Mailin-Tag', implode(',', array_unique($cleanTags)));
	}
}
