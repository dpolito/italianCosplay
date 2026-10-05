<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\PhotoEventSubmissionRepository;
use App\Support\AuditLogActionType;
use InvalidArgumentException;
use PDO;
use Throwable;

final class PhotoEventSubmissionService
{
	private PDO $db;
	private PhotoEventSubmissionRepository $submissions;
	private AuditLogService $auditLogService;

	public function __construct(?PhotoEventSubmissionRepository $submissions = null)
	{
		$this->db = Database::getInstance()->getConnection();
		$this->submissions = $submissions ?? new PhotoEventSubmissionRepository($this->db);
		$this->auditLogService = new AuditLogService();
	}

	public function createOrReuse(int $userId, array $input): array
	{
		$eventId = (int) ($input['event_id'] ?? 0) ?: null;
		$eventName = mb_substr(trim((string) ($input['event_name'] ?? '')), 0, 180);
		$year = (int) ($input['year'] ?? 0);
		$locationName = mb_substr(trim((string) ($input['location_name'] ?? '')), 0, 160);
		$eventDate = trim((string) ($input['event_date'] ?? ''));

		if ($eventName === '' && $eventId !== null) {
			$eventName = $this->eventTitle($eventId);
		}
		if (mb_strlen($eventName) < 3) {
			throw new InvalidArgumentException('Inserisci il nome dell\'evento.');
		}
		$currentYear = (int) date('Y');
		if ($year < 1990 || $year > $currentYear + 3) {
			throw new InvalidArgumentException('Anno evento non valido.');
		}
		if ($eventDate !== '' && !$this->validDate($eventDate)) {
			throw new InvalidArgumentException('Data evento non valida.');
		}
		if ($eventId !== null) {
			$this->assertEventExists($eventId);
		}

		$normalizedName = $this->normalizeName($eventName);
		$existing = $this->submissions->findPendingEquivalent($eventId, $normalizedName, $year);
		if ($existing) {
			return $existing;
		}

		$submissionId = $this->submissions->create([
			'user_id' => $userId,
			'event_id' => $eventId,
			'event_name' => $eventName,
			'normalized_event_name' => $normalizedName,
			'year' => $year,
			'location_name' => $locationName !== '' ? $locationName : null,
			'event_date' => $eventDate !== '' ? $eventDate : null,
		]);

		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::PHOTO_EVENT_SUBMISSION_CREATED,
			'entity_type' => 'photo_event_submission',
			'entity_id' => $submissionId,
			'payload' => ['event_id' => $eventId, 'event_name' => $eventName, 'year' => $year],
		]);

		return $this->submissions->find($submissionId) ?? [];
	}

	public function mergeIntoEvent(int $submissionId, int $eventId, int $adminId): void
	{
		$this->assertEventExists($eventId);
		$submission = $this->submissions->find($submissionId);
		if (!$submission || (string) $submission['status'] !== 'pending') {
			throw new InvalidArgumentException('Segnalazione non trovata o già risolta.');
		}

		$this->db->beginTransaction();
		try {
			$stmt = $this->db->prepare(
				"UPDATE photos
				 SET event_id = :event_id, event_submission_id = NULL, status = 'published', updated_at = NOW()
				 WHERE event_submission_id = :submission_id
				   AND deleted_at IS NULL
				   AND status = 'processing'"
			);
			$stmt->execute([':event_id' => $eventId, ':submission_id' => $submissionId]);
			$this->submissions->resolve($submissionId, $eventId, $adminId, 'merged');
			$this->db->commit();
		} catch (Throwable $exception) {
			$this->db->rollBack();
			throw $exception;
		}

		$this->auditLogService->logAudit([
			'user_id' => $adminId,
			'action_type' => AuditLogActionType::PHOTO_EVENT_SUBMISSION_RESOLVED,
			'entity_type' => 'photo_event_submission',
			'entity_id' => $submissionId,
			'payload' => ['action' => 'merge', 'event_id' => $eventId],
		]);
	}

	public function reject(int $submissionId, int $adminId): void
	{
		$submission = $this->submissions->find($submissionId);
		if (!$submission || (string) $submission['status'] !== 'pending') {
			throw new InvalidArgumentException('Segnalazione non trovata o già risolta.');
		}
		$this->submissions->reject($submissionId, $adminId);
		$this->auditLogService->logAudit([
			'user_id' => $adminId,
			'action_type' => AuditLogActionType::PHOTO_EVENT_SUBMISSION_RESOLVED,
			'entity_type' => 'photo_event_submission',
			'entity_id' => $submissionId,
			'payload' => ['action' => 'reject'],
		]);
	}

	private function assertEventExists(int $eventId): void
	{
		$stmt = $this->db->prepare("SELECT 1 FROM events WHERE id = :id AND approvato = 1 AND deleted_at IS NULL LIMIT 1");
		$stmt->execute([':id' => $eventId]);
		if (!$stmt->fetchColumn()) {
			throw new InvalidArgumentException('Evento di destinazione non valido.');
		}
	}

	private function eventTitle(int $eventId): string
	{
		$stmt = $this->db->prepare("SELECT titolo FROM events WHERE id = :id AND deleted_at IS NULL LIMIT 1");
		$stmt->execute([':id' => $eventId]);
		return (string) ($stmt->fetchColumn() ?: '');
	}

	private function validDate(string $date): bool
	{
		$parts = explode('-', $date);
		return count($parts) === 3 && checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
	}

	private function normalizeName(string $name): string
	{
		$name = mb_strtolower(trim($name));
		$name = preg_replace('/\s+/u', ' ', $name) ?? $name;
		return mb_substr($name, 0, 180);
	}
}
