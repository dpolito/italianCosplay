<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Models\Event;

class PendingUserActionService
{
	private const SESSION_KEY = 'pending_user_action';
	private const ALLOWED_AGENDA_STATUSES = ['mi_interessa', 'ci_vado', 'forse_vado'];
	private const ALLOWED_COSPLAY_STATUSES = ['porterò', 'forse'];

	private Event $eventModel;
	private EventAgendaService $eventAgendaService;
	private FavoriteService $favoriteService;
	private CosplayPortfolioService $cosplayPortfolioService;

	public function __construct()
	{
		$this->eventModel = new Event();
		$this->eventAgendaService = new EventAgendaService();
		$this->favoriteService = new FavoriteService();
		$this->cosplayPortfolioService = new CosplayPortfolioService();
	}

	public function storeFavorite(int $eventId, string $returnUrl): bool
	{
		$event = $this->findApprovedEvent($eventId);
		if (!$event) {
			return false;
		}

		return $this->store([
			'type' => 'favorite',
			'event_id' => (int) $event['id'],
			'event_title' => (string) ($event['titolo'] ?? 'Evento'),
			'return_url' => $this->safeInternalReturnUrl($returnUrl, $event),
			'created_at' => time(),
		]);
	}

	public function storeAgenda(int $eventId, string $status, string $returnUrl): bool
	{
		if (!in_array($status, self::ALLOWED_AGENDA_STATUSES, true)) {
			return false;
		}

		$event = $this->findApprovedEvent($eventId);
		if (!$event) {
			return false;
		}

		return $this->store([
			'type' => 'agenda',
			'event_id' => (int) $event['id'],
			'event_title' => (string) ($event['titolo'] ?? 'Evento'),
			'agenda_status' => $status,
			'return_url' => $this->safeInternalReturnUrl($returnUrl, $event),
			'created_at' => time(),
		]);
	}

	public function storeCosplaySelection(int $eventId, int $portfolioId, string $status, string $returnUrl): bool
	{
		if ($portfolioId <= 0 || !in_array($status, self::ALLOWED_COSPLAY_STATUSES, true)) {
			return false;
		}

		$event = $this->findApprovedEvent($eventId);
		if (!$event) {
			return false;
		}

		return $this->store([
			'type' => 'cosplay_selection',
			'event_id' => (int) $event['id'],
			'event_title' => (string) ($event['titolo'] ?? 'Evento'),
			'portfolio_id' => $portfolioId,
			'cosplay_status' => $status,
			'return_url' => $this->safeInternalReturnUrl($returnUrl, $event),
			'created_at' => time(),
		]);
	}

	public function consumeForUser(int $userId): ?array
	{
		$action = Session::get(self::SESSION_KEY);
		Session::remove(self::SESSION_KEY);

		if ($userId <= 0 || !$this->isValidAction($action)) {
			return null;
		}

		$eventId = (int) $action['event_id'];
		$event = $this->findApprovedEvent($eventId);
		if (!$event) {
			return null;
		}

		$returnUrl = $this->safeInternalReturnUrl((string) $action['return_url'], $event);
		$title = (string) ($event['titolo'] ?? $action['event_title'] ?? 'Evento');
		$success = false;
		$message = '';

		if ($action['type'] === 'favorite') {
			$success = $this->favoriteService->addFavorite($userId, 'event', $eventId);
			$message = $success
				? $title . ' aggiunto ai preferiti.'
				: 'Impossibile salvare ' . $title . ' nei preferiti.';
		} elseif ($action['type'] === 'agenda') {
			$status = (string) $action['agenda_status'];
			$success = $this->eventAgendaService->setStatus($userId, $eventId, $status);
			$message = $success
				? $this->agendaSuccessMessage($title, $status)
				: 'Impossibile aggiornare la tua Agenda.';
		} elseif ($action['type'] === 'cosplay_selection') {
			$portfolioId = (int) $action['portfolio_id'];
			$status = (string) $action['cosplay_status'];
			$success = $this->cosplayPortfolioService->saveEventSelection($userId, $eventId, $portfolioId, $status);
			$message = $success
				? 'Cosplay collegato a ' . $title . '.'
				: 'Impossibile collegare il cosplay all’evento.';
		}

		return [
			'success' => $success,
			'message' => $message,
			'return_url' => $returnUrl,
			'type' => (string) $action['type'],
		];
	}

	public function getLoginContext(): ?array
	{
		$action = Session::get(self::SESSION_KEY);
		if (!$this->isValidAction($action)) {
			return null;
		}

		return [
			'title' => $this->contextTitle($action),
			'description' => 'Accedi o crea un account per salvare la tua scelta.',
		];
	}

	private function store(array $action): bool
	{
		Session::set(self::SESSION_KEY, $action);
		return true;
	}

	private function isValidAction($action): bool
	{
		if (!is_array($action) || empty($action['type']) || empty($action['event_id']) || empty($action['return_url'])) {
			return false;
		}

		$createdAt = (int) ($action['created_at'] ?? 0);
		if ($createdAt <= 0 || $createdAt < time() - 3600) {
			return false;
		}

		if ($action['type'] === 'favorite') {
			return true;
		}

		if ($action['type'] === 'agenda') {
			return isset($action['agenda_status']) && in_array((string) $action['agenda_status'], self::ALLOWED_AGENDA_STATUSES, true);
		}

		if ($action['type'] === 'cosplay_selection') {
			return isset($action['portfolio_id'], $action['cosplay_status'])
				&& (int) $action['portfolio_id'] > 0
				&& in_array((string) $action['cosplay_status'], self::ALLOWED_COSPLAY_STATUSES, true);
		}

		return false;
	}

	private function findApprovedEvent(int $eventId): ?array
	{
		if ($eventId <= 0) {
			return null;
		}

		$event = $this->eventModel->find($eventId);
		if (!is_array($event) || (int) ($event['approvato'] ?? 0) !== 1) {
			return null;
		}

		return $event;
	}

	private function safeInternalReturnUrl(string $returnUrl, array $event): string
	{
		$fallback = '/eventi-cosplay/' . rawurlencode((string) ($event['slug'] ?? ''));
		$path = parse_url($returnUrl, PHP_URL_PATH);

		if (!is_string($path) || $path === '' || !str_starts_with($path, '/')) {
			return $fallback;
		}

		if (str_starts_with($path, '//') || !str_starts_with($path, '/eventi-cosplay/')) {
			return $fallback;
		}

		$query = parse_url($returnUrl, PHP_URL_QUERY);
		return $path . (is_string($query) && $query !== '' ? '?' . $query : '');
	}

	private function agendaSuccessMessage(string $title, string $status): string
	{
		$labels = [
			'mi_interessa' => 'salvato nella tua Agenda.',
			'ci_vado' => 'aggiunto alla tua Agenda.',
			'forse_vado' => 'segnato come forse nella tua Agenda.',
		];

		return $title . ' ' . ($labels[$status] ?? 'aggiunto alla tua Agenda.');
	}

	private function contextTitle(array $action): string
	{
		$title = (string) ($action['event_title'] ?? 'questo evento');
		if ($action['type'] === 'favorite') {
			return 'Salva ' . $title . ' tra i preferiti';
		}
		if ($action['type'] === 'agenda') {
			return 'Aggiungi ' . $title . ' alla tua Agenda';
		}
		if ($action['type'] === 'cosplay_selection') {
			return 'Collega un cosplay a ' . $title;
		}

		return 'Completa la tua azione su ItalianCosplay';
	}
}
