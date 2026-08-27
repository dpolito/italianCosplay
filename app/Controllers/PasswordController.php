<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Session;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\AuditLogActionType;


class PasswordController extends Controller
{
	private User $userModel;
	private $db;
	private AuditLogService $auditLogService;

	public function __construct()
	{
		$this->userModel = new User();
		$this->db = Database::getInstance()->getConnection();
		$this->auditLogService = new AuditLogService();
	}

	// Mostra form "Password dimenticata" + gestisce POST
	public function forgot()
	{
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$email = trim($_POST['email'] ?? '');
			$csrf = $_POST['csrf_token'] ?? '';

			// CSRF check
			if (empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
				$this->auditLogService->logAudit([
					'user_id' => $user['id'] ?? null,
					'action_type' => AuditLogActionType::PASSWORD_RESET_REQUESTED,
					'entity_type' => 'user',
					'entity_id' => $user['id'] ?? null,
					'success' => 0,
					'payload' => [
						'email' => $email,
						'reason' => 'csrf_invalid',
					],
					'error_message' => 'csrf_invalid',
				]);
				Session::setFlash('error', 'Richiesta non valida (CSRF).');
				header('Location: /password/forgot');
				exit();
			}

			if (!$email) {
				$this->auditLogService->logAudit([
					'user_id' => null,
					'action_type' => AuditLogActionType::PASSWORD_RESET_REQUESTED,
					'entity_type' => 'user',
					'entity_id' => null,
					'success' => 0,
					'payload' => [
						'email' => $email,
						'reason' => 'missing_email',
					],
					'error_message' => 'missing_email',
				]);
				$this->view('auth/password_forgot', [
					'error' => 'Inserisci la tua email.',
					'csrf_token' => $_SESSION['csrf_token']
				]);
				return;
			}

			$user = $this->userModel->findByEmail($email);

			if (!$user) {
				$this->auditLogService->logAudit([
					'user_id' => null,
					'action_type' => AuditLogActionType::PASSWORD_RESET_REQUESTED,
					'entity_type' => 'user',
					'entity_id' => null,
					'success' => 0,
					'payload' => [
						'email' => $email,
						'reason' => 'email_not_found',
					],
					'error_message' => 'email_not_found',
				]);
				// Non riveliamo se l'email esiste o no
				$this->view('auth/password_forgot', [
					'success' => '1111Se l\'email esiste, riceverai un link per il reset.',
					'csrf_token' => $_SESSION['csrf_token']
				]);
				return;
			}


			// Genera token sicuro
			$token = bin2hex(random_bytes(32));
			$expires = date('Y-m-d H:i:s', time() + 3600); // 1h validità

			// Salva token in DB
			$stmt = $this->db->prepare(
				"INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)"
			);
			$stmt->execute([
				':user_id' => $user['id'],
				':token' => $token,
				':expires_at' => $expires
			]);
			$this->auditLogService->logAudit([
				'user_id' => $user['id'],
				'action_type' => AuditLogActionType::PASSWORD_RESET_REQUESTED,
				'entity_type' => 'user',
				'entity_id' => $user['id'],
				'success' => 1,
				'payload' => [
					'email' => $user['email'],
					'token_expires_at' => $expires,
				],
			]);

			// Invia email (funzione mail() o libreria tipo PHPMailer)
			$resetLink ="https://www.italiancosplay.it/password/reset/$token";
			$subject = "Reset Password ItalianCosplay";
			$message = "Ciao {$user['username']},\n\n";
			$message .= "Puoi resettare la tua password cliccando questo link:\n$resetLink\n\n";
			$message .= "Il link scadrà tra 1 ora.\n\n";
			$message .= "Se non hai richiesto il reset, ignora questa email.";

			$mailer = new Mailer();
			$esito = $mailer->send(
				$user['email'],
				$user['email'],
				$subject,
				$message
			);

			$this->view('auth/password_forgot', [
				'success' => 'Se l\'email esiste, riceverai un link per il reset.',
				'csrf_token' => $_SESSION['csrf_token']
			]);
			return;
		}

		// GET → mostra form
		$this->view('auth/password_forgot', [
			'csrf_token' => $_SESSION['csrf_token']
		]);
	}

	// Mostra form reset + gestisce POST
	public function reset($token)
	{

		$stmt = $this->db->prepare("SELECT * FROM password_resets WHERE token = :token AND expires_at >= NOW()");
		$stmt->execute([':token' => $token[1]]);
		$reset = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$reset) {
			die("Link non valido o scaduto.");
		}

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$password = trim($_POST['password'] ?? '');
			$confirm = trim($_POST['password_confirm'] ?? '');
			$csrf = $_POST['csrf_token'] ?? '';

			if (empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
				$this->auditLogService->logAudit([
					'user_id' => $reset['user_id'] ?? null,
					'action_type' => AuditLogActionType::PASSWORD_RESET_COMPLETED,
					'entity_type' => 'user',
					'entity_id' => $reset['user_id'] ?? null,
					'success' => 0,
					'payload' => [
						'token' => $token[1],
						'reason' => 'csrf_invalid',
					],
					'error_message' => 'csrf_invalid',
				]);
				Session::setFlash('error', 'Richiesta non valida (CSRF).');
				header("Location: /password/reset/$token[1]");
				exit();
			}

			if (!$password || $password !== $confirm) {
				$this->auditLogService->logAudit([
					'user_id' => $reset['user_id'] ?? null,
					'action_type' => AuditLogActionType::PASSWORD_RESET_COMPLETED,
					'entity_type' => 'user',
					'entity_id' => $reset['user_id'] ?? null,
					'success' => 0,
					'payload' => [
						'token' => $token[1],
						'reason' => 'password_mismatch',
					],
					'error_message' => 'password_mismatch',
				]);
				$this->view('auth/password_reset', [
					'error' => 'Le password non coincidono o sono vuote.',
					'csrf_token' => $_SESSION['csrf_token'],
					'token' => $token[1]
				]);
				return;
			}

			// Aggiorna password
			$hashed = password_hash($password, PASSWORD_DEFAULT);
			$stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
			$stmt->execute([
				':password' => $hashed,
				':id' => $reset['user_id']
			]);

			// Cancella token
			$stmt = $this->db->prepare("DELETE FROM password_resets WHERE id = :id");
			$stmt->execute([':id' => $reset['id']]);
			$this->auditLogService->logAudit([
				'user_id' => $reset['user_id'],
				'action_type' => AuditLogActionType::PASSWORD_RESET_COMPLETED,
				'entity_type' => 'user',
				'entity_id' => $reset['user_id'],
				'success' => 1,
				'payload' => [
					'token' => $token[1],
				],
			]);

			$this->view('auth/login', [
				'success' => 'Password aggiornata con successo. Puoi ora accedere.',
				'csrf_token' => $_SESSION['csrf_token']
			]);
			return;
		}

		$this->view('auth/password_reset', [
			'csrf_token' => $_SESSION['csrf_token'],
			'token' => $token[1]
		]);
	}
}
