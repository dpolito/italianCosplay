<?php
namespace App\Middleware;
use App\Core\Middleware;
use App\Core\Session;
use App\Models\User;

class PermissionMiddleware extends Middleware
{
	private string $permission;

	public function __construct(string $permission)
	{
		$this->permission = $permission;
	}

	public function handle(): void
	{
		error_log('AUTH CHECK: ' . print_r($_SESSION, true));

		$userId = Session::get('user_id');

		if (!$userId) {
			Session::setFlash('error', 'Devi effettuare il login.');
			header('Location: /login');
			exit();
		}

		$userModel = new User();
		$user = $userModel->find((int)$userId);

		// 🔴 utente non esiste più
		if (!$user) {
			session_destroy();
			header('Location: /login');
			exit();
		}

		// ✅ controllo permesso PASSANDO L'UTENTE
		if (!$userModel->hasPermission($user['id'], $this->permission)) {
			header('HTTP/1.1 403 Forbidden');
			die('Accesso negato');
		}
	}
}
