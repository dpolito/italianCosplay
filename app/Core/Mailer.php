<?php

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
	private PHPMailer $mail;

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
	public function send(string $toEmail, string $toName, string $subject, string $bodyHtml, ?string $bodyText = null): bool
	{
		try {
			$this->mail->clearAddresses();
			$this->mail->addAddress($toEmail, $toName);
			$this->mail->Subject = $subject;
			$this->mail->Body    = $bodyHtml;
			$this->mail->AltBody = $bodyText ?? strip_tags($bodyHtml);

			return $this->mail->send();
		} catch (Exception $e) {
			error_log('Mailer error: ' . $this->mail->ErrorInfo);
			return false;
		}
	}
}
