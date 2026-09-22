<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\EmailDeliveryEventService;

class AdminEmailDeliveryEventController extends Controller
{
	private EmailDeliveryEventService $service;

	public function __construct()
	{
		$this->service = new EmailDeliveryEventService();
	}

	public function index(): void
	{
		$this->view('admin/email-delivery-events/index', [
			'overview' => $this->service->getAdminOverview($_GET),
		], 'admin');
	}

	public function data(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$result = $this->service->getAdminList($_GET);

		echo json_encode([
			'success' => true,
			'data' => $result['data'],
			'meta' => [
				'total' => $result['total'],
				'page' => $result['page'],
				'perPage' => $result['perPage'],
				'pages' => $result['pages'],
			],
		], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	public function detail(array $params): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$id = (int) ($params[0] ?? 0);
		$detail = $id > 0 ? $this->service->getDetail($id) : null;
		if ($detail === null) {
			http_response_code(404);
			echo json_encode([
				'success' => false,
				'message' => 'Evento email non trovato.',
			], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			return;
		}

		echo json_encode([
			'success' => true,
			'data' => $detail,
		], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}
}
