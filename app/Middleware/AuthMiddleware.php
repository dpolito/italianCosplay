<?php
namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Session;

class AuthMiddleware extends Middleware
{
	public function handle(): void
	{
		error_log('AUTH CHECK: ' . print_r($_SESSION, true));

		$userId = Session::get('user_id');

		error_log("DEBUG SESSION: " . print_r($_SESSION, true));

		if (!$userId) {
			Session::setFlash('error', 'Devi effettuare il login.');
			header('Location: /login');
			exit();
		}
	}
	public function middleware($middleware)
	{
		$middlewares = is_array($middleware) ? $middleware : [$middleware];
		foreach ($middlewares as $m) {
			(new $m())->handle();
		}
	}
}
