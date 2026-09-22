<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;

abstract class AdminAdsController extends Controller
{
	protected function ensureCsrfToken(): void
	{
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
	}

	protected function csrfIsValid(): bool
	{
		return !empty($_POST['csrf_token'])
			&& !empty($_SESSION['csrf_token'])
			&& hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
	}

	protected function flashAndRedirect(string $type, string $message, string $location): void
	{
		Session::setFlash($type, $message);
		header('Location: ' . $location);
		exit();
	}

	protected function isJsonRequest(): bool
	{
		$accept = $_SERVER['HTTP_ACCEPT'] ?? '';

		return str_contains($accept, 'application/json')
			|| !empty($_POST['ajax'])
			|| !empty($_GET['ajax']);
	}

	protected function jsonResponse(bool $success, string $message = '', array $data = [], int $statusCode = 200): void
	{
		http_response_code($statusCode);
		header('Content-Type: application/json; charset=utf-8');

		echo json_encode(array_filter([
			'success' => $success,
			'message' => $message,
			'data' => $data,
		], static fn ($value) => $value !== '' && $value !== []));

		exit();
	}
}
