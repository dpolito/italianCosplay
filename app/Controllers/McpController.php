<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ApiAccessService;
use App\Services\ApiV1ReadService;
use Throwable;

final class McpController extends Controller
{
	private const PROTOCOL_VERSION = '2025-11-25';

	private ?ApiV1ReadService $api = null;
	private ?ApiAccessService $access = null;

	public function __construct()
	{
	}

	public function handle(): void
	{
		if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
			$this->json(['error' => 'MCP endpoint accepts POST requests only.'], 405);
		}

		$payload = json_decode((string) file_get_contents('php://input'), true);
		if (!is_array($payload)) {
			$this->json($this->error(null, -32700, 'Parse error'), 400);
		}

		$response = $this->handleMessage($payload);
		if ($response === null) {
			http_response_code(204);
			exit();
		}

		$this->json($response);
	}

	private function handleMessage(array $message): ?array
	{
		$id = $message['id'] ?? null;
		$method = (string) ($message['method'] ?? '');
		$params = is_array($message['params'] ?? null) ? $message['params'] : [];

		if (!array_key_exists('id', $message)) {
			return null;
		}

		return match ($method) {
			'initialize' => $this->result($id, [
				'protocolVersion' => self::PROTOCOL_VERSION,
				'capabilities' => ['tools' => ['listChanged' => false]],
				'serverInfo' => ['name' => 'italiancosplay', 'version' => '1.0.0'],
				'instructions' => 'Read-only ItalianCosplay tools for approved cosplay, comics, gaming and pop-culture events in Italy. Use search_events before get_event or get_event_photos when the slug is unknown. Results are intentionally bounded by API anti-dump limits.',
			]),
			'tools/list' => $this->result($id, ['tools' => $this->tools()]),
			'tools/call' => $this->callTool($id, $params),
			default => $this->error($id, -32601, 'Method not found'),
		};
	}

	private function callTool(mixed $id, array $params): array
	{
		$name = (string) ($params['name'] ?? '');
		$arguments = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

		try {
			$result = match ($name) {
				'search_events' => $this->authorizedCall('events:search', '/api/v1/events/search', fn (): array => $this->api()->searchEvents($arguments)),
				'get_event' => $this->authorizedCall('events:read', '/api/v1/events/' . rawurlencode((string) ($arguments['slug'] ?? '')), fn (): array => $this->api()->getEvent((string) ($arguments['slug'] ?? ''))),
				'get_event_photos' => $this->authorizedCall('events:photos', '/api/v1/events/' . rawurlencode((string) ($arguments['slug'] ?? '')) . '/photos', fn (): array => $this->api()->getEventPhotos((string) ($arguments['slug'] ?? ''), $arguments)),
				'get_locations' => $this->authorizedCall('locations:read', '/api/v1/locations/' . rawurlencode((string) ($arguments['type'] ?? '')), fn (): array => $this->api()->getLocations((string) ($arguments['type'] ?? ''), $arguments)),
				default => null,
			};
		} catch (Throwable $exception) {
			error_log('MCP tool error: ' . $exception->getMessage());
			return $this->error($id, -32603, 'Internal error');
		}

		if ($result === null) {
			return $this->error($id, -32602, 'Unknown tool.');
		}
		if (($result['success'] ?? false) !== true) {
			return $this->result($id, [
				'isError' => true,
				'content' => [['type' => 'text', 'text' => ($result['error']['message'] ?? 'Tool error')]],
				'structuredContent' => $result,
			]);
		}

		return $this->result($id, [
			'content' => [['type' => 'text', 'text' => $this->summary($name, $result['data'] ?? [])]],
			'structuredContent' => $result['data'] ?? [],
		]);
	}

	private function authorizedCall(string $scope, string $auditEndpoint, callable $callback): array
	{
		$apiKey = app_env_value('ITALIANCOSPLAY_AI_API_KEY');
		if ($apiKey === '') {
			return ['success' => false, 'status' => 503, 'error' => ['code' => 'AI_API_KEY_MISSING', 'message' => 'ItalianCosplay AI API key is not configured.']];
		}

		$originalAuthorization = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
		$originalRequestUri = $_SERVER['REQUEST_URI'] ?? null;
		$originalMethod = $_SERVER['REQUEST_METHOD'] ?? null;
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $apiKey;
		$_SERVER['REQUEST_URI'] = $auditEndpoint;
		$_SERVER['REQUEST_METHOD'] = 'GET';

		try {
			$this->access()->authorize($scope);
			$result = $callback();
			$this->access()->logSuccess((int) ($result['status'] ?? 200));
		} finally {
			if ($originalAuthorization === null) {
				unset($_SERVER['HTTP_AUTHORIZATION']);
			} else {
				$_SERVER['HTTP_AUTHORIZATION'] = $originalAuthorization;
			}
			if ($originalRequestUri === null) {
				unset($_SERVER['REQUEST_URI']);
			} else {
				$_SERVER['REQUEST_URI'] = $originalRequestUri;
			}
			if ($originalMethod === null) {
				unset($_SERVER['REQUEST_METHOD']);
			} else {
				$_SERVER['REQUEST_METHOD'] = $originalMethod;
			}
		}

		return $result;
	}

	private function api(): ApiV1ReadService
	{
		if ($this->api === null) {
			$this->api = new ApiV1ReadService();
		}

		return $this->api;
	}

	private function access(): ApiAccessService
	{
		if ($this->access === null) {
			$this->access = new ApiAccessService();
		}

		return $this->access;
	}

	private function tools(): array
	{
		return [
			[
				'name' => 'search_events',
				'title' => 'Search ItalianCosplay events',
				'description' => 'Search the ItalianCosplay event database for approved cosplay, comics, gaming and pop-culture events in Italy. Use when the user asks which events are happening in a location or date range. Requires a meaningful query, location filter, or bounded date range; results are capped to prevent dumps.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'q' => ['type' => 'string', 'description' => 'Event name or keyword, at least 2 characters when provided.'],
						'from' => ['type' => 'string', 'format' => 'date', 'description' => 'Start date in YYYY-MM-DD.'],
						'to' => ['type' => 'string', 'format' => 'date', 'description' => 'End date in YYYY-MM-DD. Maximum range follows ItalianCosplay API limits.'],
						'regione' => ['type' => 'integer', 'description' => 'ItalianCosplay region ID. Use get_locations when only the region name is known.'],
						'provincia' => ['type' => 'integer', 'description' => 'ItalianCosplay province ID.'],
						'comune' => ['type' => 'integer', 'description' => 'ItalianCosplay city/municipality ID.'],
						'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20],
					],
					'additionalProperties' => false,
				],
				'annotations' => ['readOnlyHint' => true, 'destructiveHint' => false, 'openWorldHint' => false],
			],
			[
				'name' => 'get_event',
				'title' => 'Get ItalianCosplay event details',
				'description' => 'Get detailed information about a specific event already identified in the ItalianCosplay database. Use for dates, venue, schedule-like details when available, guests if present in the API, official website, social links and canonical ItalianCosplay URL. Do not invent missing fields.',
				'inputSchema' => [
					'type' => 'object',
					'required' => ['slug'],
					'properties' => ['slug' => ['type' => 'string', 'pattern' => '^[A-Za-z0-9-]+$']],
					'additionalProperties' => false,
				],
				'annotations' => ['readOnlyHint' => true, 'destructiveHint' => false, 'openWorldHint' => false],
			],
			[
				'name' => 'get_event_photos',
				'title' => 'Get ItalianCosplay event photos',
				'description' => 'Get public photos associated with a specific ItalianCosplay event edition. Use after identifying an event slug. This tool does not expose a global photo archive.',
				'inputSchema' => [
					'type' => 'object',
					'required' => ['slug'],
					'properties' => [
						'slug' => ['type' => 'string', 'pattern' => '^[A-Za-z0-9-]+$'],
						'page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5],
						'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20],
					],
					'additionalProperties' => false,
				],
				'annotations' => ['readOnlyHint' => true, 'destructiveHint' => false, 'openWorldHint' => false],
			],
			[
				'name' => 'get_locations',
				'title' => 'Get ItalianCosplay locations',
				'description' => 'Resolve Italian geographic locations supported by ItalianCosplay event search. Use regioni first, then province with region_id, then comuni with province_id.',
				'inputSchema' => [
					'type' => 'object',
					'required' => ['type'],
					'properties' => [
						'type' => ['type' => 'string', 'enum' => ['regioni', 'province', 'comuni']],
						'region_id' => ['type' => 'integer'],
						'province_id' => ['type' => 'integer'],
					],
					'additionalProperties' => false,
				],
				'annotations' => ['readOnlyHint' => true, 'destructiveHint' => false, 'openWorldHint' => false],
			],
		];
	}

	private function summary(string $toolName, array $data): string
	{
		$count = isset($data['items']) && is_array($data['items']) ? count($data['items']) : null;
		return match ($toolName) {
			'search_events' => 'Found ' . (int) $count . ' ItalianCosplay events.',
			'get_event_photos' => 'Found ' . (int) $count . ' public event photos.',
			'get_locations' => 'Found ' . (int) $count . ' locations.',
			'get_event' => 'Retrieved ItalianCosplay event details.',
			default => 'Tool completed.',
		};
	}

	private function result(mixed $id, array $result): array
	{
		return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
	}

	private function error(mixed $id, int $code, string $message): array
	{
		return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
	}

	private function json(array $payload, int $status = 200): void
	{
		http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-store');
		echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit();
	}
}
