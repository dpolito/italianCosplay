<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\PhotoAnalyticsRepository;
use Throwable;

final class PhotoAnalyticsService
{
	private const COOKIE_NAME = 'ic_photo_visitor';
	private const VIEW_EVENTS = ['photo_hub_view', 'photo_event_gallery_view', 'photo_view'];
	private const CLICK_EVENTS = ['photo_filter_used', 'photo_open', 'photo_uploader_profile_click', 'photo_upload_cta_click'];
	private const ACTION_EVENTS = ['photo_upload_success', 'photo_self_claim'];

	private PhotoAnalyticsRepository $analytics;

	public function __construct(?PhotoAnalyticsRepository $analytics = null)
	{
		$this->analytics = $analytics ?? new PhotoAnalyticsRepository();
	}

	public function track(string $eventType, array $payload = [], bool $dedupe = true): void
	{
		if (!$this->isAllowedEvent($eventType) || $this->isBotRequest()) {
			return;
		}

		try {
			$data = $this->normalizePayload($eventType, $payload);
			if ($dedupe && in_array($eventType, self::VIEW_EVENTS, true)) {
				if ($this->analytics->hasRecentEvent($data['visitor_key'], $eventType, $data['event_id'], $data['photo_id'], $data['source'], 30)) {
					return;
				}
			}
			$this->analytics->record($data);
		} catch (Throwable $exception) {
			error_log('Photo analytics tracking failed: ' . $exception->getMessage());
		}
	}

	public function trackClick(array $payload): bool
	{
		$eventType = (string) ($payload['event_type'] ?? '');
		if (!in_array($eventType, self::CLICK_EVENTS, true)) {
			return false;
		}
		$this->track($eventType, $payload, false);
		return true;
	}

	public function dashboard(int $days, string $sort): array
	{
		try {
			return [
				'overview' => $this->analytics->overview($days),
				'funnel' => $this->analytics->countsByType($days),
				'events' => $this->analytics->eventStats($days, $sort),
				'uploaders' => $this->analytics->uploaderStats($days),
				'topPhotos' => $this->analytics->topPhotos($days),
				'filters' => $this->analytics->filterUsage($days),
				'topFilterEvents' => $this->analytics->topFilterEvents($days),
				'trend' => $this->analytics->dailyTrend(30),
			];
		} catch (Throwable $exception) {
			error_log('Photo analytics dashboard failed: ' . $exception->getMessage());
			return [
				'overview' => [],
				'funnel' => [],
				'events' => [],
				'uploaders' => [],
				'topPhotos' => [],
				'filters' => [],
				'topFilterEvents' => [],
				'trend' => [],
				'error' => 'Statistiche non disponibili. Verifica che la migrazione photo_analytics_events sia stata applicata.',
			];
		}
	}

	private function normalizePayload(string $eventType, array $payload): array
	{
		return [
			'event_type' => $eventType,
			'visitor_key' => $this->visitorKey(),
			'user_id' => isset($_SESSION['user_id']) ? max(1, (int) $_SESSION['user_id']) : null,
			'event_id' => $this->positiveInt($payload['event_id'] ?? null),
			'photo_id' => $this->positiveInt($payload['photo_id'] ?? null),
			'uploaded_by_user_id' => $this->positiveInt($payload['uploaded_by_user_id'] ?? null),
			'source' => $this->shortString($payload['source'] ?? null, 40),
			'filter_event_id' => $this->positiveInt($payload['filter_event_id'] ?? null),
			'filter_year' => $this->validYear($payload['filter_year'] ?? null),
			'filter_uploader_id' => $this->positiveInt($payload['filter_uploader_id'] ?? null),
			'request_path' => $this->shortString(parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: null, 255),
			'referrer' => $this->shortString($_SERVER['HTTP_REFERER'] ?? null, 500),
		];
	}

	private function visitorKey(): string
	{
		$token = (string) ($_COOKIE[self::COOKIE_NAME] ?? '');
		if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
			$token = bin2hex(random_bytes(16));
			setcookie(self::COOKIE_NAME, $token, [
				'expires' => time() + 31536000,
				'path' => '/',
				'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
				'httponly' => true,
				'samesite' => 'Lax',
			]);
			$_COOKIE[self::COOKIE_NAME] = $token;
		}
		return hash('sha256', $token);
	}

	private function isAllowedEvent(string $eventType): bool
	{
		return in_array($eventType, array_merge(self::VIEW_EVENTS, self::CLICK_EVENTS, self::ACTION_EVENTS), true);
	}

	private function isBotRequest(): bool
	{
		$ua = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
		if ($ua === '') {
			return false;
		}
		return preg_match('/bot|crawler|spider|slurp|bingpreview|facebookexternalhit|whatsapp|telegrambot|preview|headless|curl|wget|python-requests/', $ua) === 1;
	}

	private function positiveInt(mixed $value): ?int
	{
		$value = (int) $value;
		return $value > 0 ? $value : null;
	}

	private function validYear(mixed $value): ?int
	{
		$year = (int) $value;
		$current = (int) date('Y');
		return ($year >= 2000 && $year <= $current + 3) ? $year : null;
	}

	private function shortString(mixed $value, int $maxLength): ?string
	{
		$value = trim((string) $value);
		if ($value === '') {
			return null;
		}
		return mb_substr($value, 0, $maxLength);
	}
}
