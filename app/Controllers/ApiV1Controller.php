<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ApiAccessService;
use App\Services\ApiV1ReadService;

final class ApiV1Controller extends Controller
{
	private ApiV1ReadService $api;

	public function __construct()
	{
		$this->api = new ApiV1ReadService();
	}

	public function searchEvents(): void
	{
		$this->jsonResult($this->api->searchEvents($_GET));
	}

	public function showEvent(array $params): void
	{
		$this->jsonResult($this->api->getEvent((string) ($params[0] ?? '')));
	}

	public function eventPhotos(array $params): void
	{
		$this->jsonResult($this->api->getEventPhotos((string) ($params[0] ?? ''), $_GET));
	}

	public function locations(array $params): void
	{
		$this->jsonResult($this->api->getLocations((string) ($params[0] ?? 'regioni'), $_GET));
	}

	private function jsonResult(array $result): void
	{
		$status = ($result['success'] ?? false) ? 200 : (int) ($result['status'] ?? 500);
		(new ApiAccessService())->logSuccess($status);
		http_response_code($status);

		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-store');
		unset($result['status']);
		echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit();
	}
}
