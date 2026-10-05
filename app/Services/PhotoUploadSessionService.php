<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\PhotoUploadSessionRepository;
use App\Support\AuditLogActionType;
use InvalidArgumentException;
use PDO;
use Throwable;

final class PhotoUploadSessionService
{
	private const EXPIRES_SECONDS = 86400;

	private PDO $db;
	private PhotoUploadSessionRepository $sessions;
	private PhotoService $photoService;
	private AuditLogService $auditLogService;

	public function __construct(?PhotoUploadSessionRepository $sessions = null, ?PhotoService $photoService = null)
	{
		$this->db = Database::getInstance()->getConnection();
		$this->sessions = $sessions ?? new PhotoUploadSessionRepository($this->db);
		$this->photoService = $photoService ?? new PhotoService();
		$this->auditLogService = new AuditLogService();
	}

	public function current(int $userId): ?array
	{
		$session = $this->sessions->findOpenForUser($userId);
		return $session ? $this->decorateSession($session) : null;
	}

	public function start(int $userId, int $eventId): array
	{
		$this->assertEventExists($eventId);
		$existing = $this->sessions->findOpenForUser($userId);
		if ($existing && (int) $existing['event_id'] === $eventId) {
			return $this->decorateSession($existing);
		}
		if ($existing) {
			throw new InvalidArgumentException('Hai già un caricamento in corso. Completalo o annullalo prima di iniziarne uno nuovo.');
		}

		$sessionId = $this->sessions->create($userId, $eventId, date('Y-m-d H:i:s', time() + self::EXPIRES_SECONDS));
		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::PHOTO_UPLOAD_SESSION_STARTED,
			'entity_type' => 'photo_upload_session',
			'entity_id' => $sessionId,
			'payload' => ['event_id' => $eventId],
		]);
		return $this->decorateSession($this->sessions->findForUser($sessionId, $userId) ?? []);
	}

	public function upload(int $userId, int $eventId, array $file): array
	{
		$session = $this->start($userId, $eventId);
		$sessionId = (int) $session['id'];
		$tmpName = (string) ($file['tmp_name'] ?? '');
		if ($tmpName === '' || !is_file($tmpName)) {
			throw new InvalidArgumentException('File temporaneo non disponibile.');
		}
		$fileHash = hash_file('sha256', $tmpName);
		$fileSize = (int) ($file['size'] ?? filesize($tmpName));
		$existing = $this->sessions->findItemByFingerprint($sessionId, $fileHash, $fileSize);
		if ($existing) {
			return ['item' => $this->decorateItem($existing), 'session' => $this->decorateSession($this->sessions->findForUser($sessionId, $userId) ?? [])];
		}

		$this->sessions->touch($sessionId, 'uploading');
		try {
			$photo = $this->photoService->uploadTemporary($file, $eventId, $userId);
			$itemId = $this->sessions->createItem([
				'upload_session_id' => $sessionId,
				'photo_id' => (int) $photo['id'],
				'original_filename' => mb_substr((string) ($file['name'] ?? 'foto'), 0, 255),
				'mime_type' => $this->detectMime($tmpName),
				'file_size' => $fileSize,
				'file_hash' => $fileHash,
				'status' => 'completed',
				'error_message' => null,
			]);
			$this->sessions->touch($sessionId, 'ready');
			$item = $this->sessions->findItemForUser($itemId, $userId);
			return ['item' => $this->decorateItem($item ?? []), 'session' => $this->decorateSession($this->sessions->findForUser($sessionId, $userId) ?? [])];
		} catch (Throwable $exception) {
			$this->sessions->createItem([
				'upload_session_id' => $sessionId,
				'photo_id' => null,
				'original_filename' => mb_substr((string) ($file['name'] ?? 'foto'), 0, 255),
				'mime_type' => $this->detectMime($tmpName),
				'file_size' => $fileSize,
				'file_hash' => $fileHash,
				'status' => 'error',
				'error_message' => mb_substr($exception->getMessage(), 0, 255),
			]);
			throw $exception;
		}
	}

	public function uploadForSubmission(int $userId, int $submissionId, array $file): array
	{
		$session = $this->startForSubmission($userId, $submissionId);
		$sessionId = (int) $session['id'];
		$tmpName = (string) ($file['tmp_name'] ?? '');
		if ($tmpName === '' || !is_file($tmpName)) {
			throw new InvalidArgumentException('File temporaneo non disponibile.');
		}
		$fileHash = hash_file('sha256', $tmpName);
		$fileSize = (int) ($file['size'] ?? filesize($tmpName));
		$existing = $this->sessions->findItemByFingerprint($sessionId, $fileHash, $fileSize);
		if ($existing) {
			return ['item' => $this->decorateItem($existing), 'session' => $this->decorateSession($this->sessions->findForUser($sessionId, $userId) ?? [])];
		}

		$this->sessions->touch($sessionId, 'uploading');
		try {
			$photo = $this->photoService->uploadTemporaryForSubmission($file, $submissionId, $userId);
			$itemId = $this->sessions->createItem([
				'upload_session_id' => $sessionId,
				'photo_id' => (int) $photo['id'],
				'original_filename' => mb_substr((string) ($file['name'] ?? 'foto'), 0, 255),
				'mime_type' => $this->detectMime($tmpName),
				'file_size' => $fileSize,
				'file_hash' => $fileHash,
				'status' => 'completed',
				'error_message' => null,
			]);
			$this->sessions->touch($sessionId, 'ready');
			$item = $this->sessions->findItemForUser($itemId, $userId);
			return ['item' => $this->decorateItem($item ?? []), 'session' => $this->decorateSession($this->sessions->findForUser($sessionId, $userId) ?? [])];
		} catch (Throwable $exception) {
			$this->sessions->createItem([
				'upload_session_id' => $sessionId,
				'photo_id' => null,
				'original_filename' => mb_substr((string) ($file['name'] ?? 'foto'), 0, 255),
				'mime_type' => $this->detectMime($tmpName),
				'file_size' => $fileSize,
				'file_hash' => $fileHash,
				'status' => 'error',
				'error_message' => mb_substr($exception->getMessage(), 0, 255),
			]);
			throw $exception;
		}
	}

	public function startForSubmission(int $userId, int $submissionId): array
	{
		$this->assertSubmissionExists($submissionId);
		$existing = $this->sessions->findOpenForUser($userId);
		if ($existing && (int) ($existing['event_submission_id'] ?? 0) === $submissionId) {
			return $this->decorateSession($existing);
		}
		if ($existing) {
			throw new InvalidArgumentException('Hai già un caricamento in corso. Completalo o annullalo prima di iniziarne uno nuovo.');
		}

		$sessionId = $this->sessions->createForSubmission($userId, $submissionId, date('Y-m-d H:i:s', time() + self::EXPIRES_SECONDS));
		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::PHOTO_UPLOAD_SESSION_STARTED,
			'entity_type' => 'photo_upload_session',
			'entity_id' => $sessionId,
			'payload' => ['event_submission_id' => $submissionId],
		]);
		return $this->decorateSession($this->sessions->findForUser($sessionId, $userId) ?? []);
	}

	public function removeItem(int $userId, int $itemId): void
	{
		$item = $this->sessions->findItemForUser($itemId, $userId);
		if (!$item || (string) $item['status'] === 'removed') {
			throw new InvalidArgumentException('Foto temporanea non trovata.');
		}
		if (!empty($item['photo_id'])) {
			$this->photoService->deleteOwned((int) $item['photo_id'], $userId);
		}
		$this->sessions->markItemRemoved($itemId);
	}

	public function confirm(int $userId, int $sessionId): array
	{
		$session = $this->sessions->findForUser($sessionId, $userId);
		if (!$session || !in_array((string) $session['status'], ['draft', 'uploading', 'ready'], true)) {
			throw new InvalidArgumentException('Sessione di caricamento non disponibile.');
		}
		if (strtotime((string) $session['expires_at']) <= time()) {
			throw new InvalidArgumentException('Sessione di caricamento scaduta.');
		}

		$items = $this->sessions->listItems($sessionId);
		$completed = array_values(array_filter($items, static fn (array $item): bool => (string) $item['status'] === 'completed' && !empty($item['photo_id'])));
		if (!$completed) {
			throw new InvalidArgumentException('Carica almeno una foto prima di confermare.');
		}

		$published = 0;
		$this->db->beginTransaction();
		try {
			if (empty($session['event_submission_id'])) {
				foreach ($completed as $item) {
					$this->photoService->publishOwned((int) $item['photo_id'], $userId);
					$published++;
				}
			} else {
				$published = count($completed);
			}
			$this->sessions->touch($sessionId, 'completed');
			$this->db->commit();
		} catch (Throwable $exception) {
			$this->db->rollBack();
			throw $exception;
		}

		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::PHOTO_UPLOAD_SESSION_CONFIRMED,
			'entity_type' => 'photo_upload_session',
			'entity_id' => $sessionId,
			'payload' => [
				'event_id' => (int) ($session['event_id'] ?? 0),
				'event_submission_id' => (int) ($session['event_submission_id'] ?? 0),
				'photo_count' => $published,
			],
		]);

		return [
			'event_id' => (int) ($session['event_id'] ?? 0),
			'event_submission_id' => (int) ($session['event_submission_id'] ?? 0),
			'photo_count' => $published,
			'pending_review' => !empty($session['event_submission_id']),
		];
	}

	public function cancel(int $userId, int $sessionId): void
	{
		$session = $this->sessions->findForUser($sessionId, $userId);
		if (!$session || !in_array((string) $session['status'], ['draft', 'uploading', 'ready'], true)) {
			throw new InvalidArgumentException('Sessione di caricamento non disponibile.');
		}
		foreach ($this->sessions->listItems($sessionId) as $item) {
			if (!empty($item['photo_id']) && (string) $item['status'] === 'completed') {
				$this->photoService->deleteOwned((int) $item['photo_id'], $userId);
			}
		}
		$this->sessions->touch($sessionId, 'cancelled');
	}

	public function cleanupExpired(): int
	{
		$count = 0;
		foreach ($this->sessions->expiredSessions() as $session) {
			$this->cancel((int) $session['user_id'], (int) $session['id']);
			$this->sessions->touch((int) $session['id'], 'expired');
			$count++;
		}
		return $count;
	}

	private function decorateSession(array $session): array
	{
		$items = !empty($session['id']) ? array_map(fn (array $item): array => $this->decorateItem($item), $this->sessions->listItems((int) $session['id'])) : [];
		return array_merge($session, [
			'items' => $items,
			'completed_count' => count(array_filter($items, static fn (array $item): bool => (string) $item['status'] === 'completed')),
			'error_count' => count(array_filter($items, static fn (array $item): bool => (string) $item['status'] === 'error')),
		]);
	}

	private function decorateItem(array $item): array
	{
		if (!$item) {
			return [];
		}
		if (!empty($item['storage_key'])) {
			$item['url'] = '/public_assets/uploads/photos/' . ltrim((string) $item['storage_key'], '/');
		}
		if (!empty($item['thumbnail_storage_key'])) {
			$item['thumbnail_url'] = '/public_assets/uploads/photos/' . ltrim((string) $item['thumbnail_storage_key'], '/');
		}
		return $item;
	}

	private function assertEventExists(int $eventId): void
	{
		$stmt = $this->db->prepare("SELECT 1 FROM events WHERE id = :id AND approvato = 1 AND deleted_at IS NULL LIMIT 1");
		$stmt->execute([':id' => $eventId]);
		if (!$stmt->fetchColumn()) {
			throw new InvalidArgumentException('Evento non valido.');
		}
	}

	private function assertSubmissionExists(int $submissionId): void
	{
		$stmt = $this->db->prepare("SELECT 1 FROM photo_event_submissions WHERE id = :id AND status = 'pending' LIMIT 1");
		$stmt->execute([':id' => $submissionId]);
		if (!$stmt->fetchColumn()) {
			throw new InvalidArgumentException('Segnalazione evento non valida.');
		}
	}

	private function detectMime(string $path): ?string
	{
		if ($path === '' || !is_file($path)) {
			return null;
		}
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		if (!$finfo) {
			return null;
		}
		$mime = finfo_file($finfo, $path) ?: null;
		finfo_close($finfo);
		return $mime;
	}
}
