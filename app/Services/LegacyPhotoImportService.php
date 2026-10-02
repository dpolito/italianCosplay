<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\LegacyPhotoImportRepository;
use App\Support\AuditLogActionType;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use Throwable;

final class LegacyPhotoImportService
{
	private const SOURCE = 'italiancosplay.com';
	private const MAX_JSON_BYTES = 5_242_880;
	private const MAX_RECORDS = 5000;
	private const BATCH_SIZE = 5;

	private PDO $db;
	private LegacyPhotoImportRepository $imports;
	private LegacyPhotoDownloader $downloader;
	private PhotoService $photoService;
	private AuditLogService $auditLogService;

	public function __construct(
		?LegacyPhotoImportRepository $imports = null,
		?LegacyPhotoDownloader $downloader = null,
		?PhotoService $photoService = null,
	) {
		$this->db = Database::getInstance()->getConnection();
		$this->imports = $imports ?? new LegacyPhotoImportRepository($this->db);
		$this->downloader = $downloader ?? new LegacyPhotoDownloader();
		$this->photoService = $photoService ?? new PhotoService();
		$this->auditLogService = new AuditLogService();
	}

	public function recentImports(): array
	{
		return $this->imports->listRecentImports(12);
	}

	public function getImport(int $importId): ?array
	{
		$import = $this->imports->findImport($importId);
		if (!$import) {
			return null;
		}
		$import['summary'] = json_decode((string) ($import['summary_json'] ?? '[]'), true) ?: [];
		$import['mapping'] = json_decode((string) ($import['mapping_json'] ?? '[]'), true) ?: [];
		return $import;
	}

	public function analyzeUpload(array $file, int $destinationUserId, int $adminUserId): array
	{
		$this->validateJsonUpload($file);
		$this->assertUserExists($destinationUserId);
		$records = json_decode((string) file_get_contents((string) $file['tmp_name']), true, 512, JSON_THROW_ON_ERROR);
		if (!is_array($records) || array_is_list($records) === false) {
			throw new InvalidArgumentException('Il JSON deve contenere un array di record.');
		}
		if (count($records) > self::MAX_RECORDS) {
			throw new InvalidArgumentException('Il JSON contiene troppi record.');
		}

		$normalized = [];
		$errors = [];
		$seen = [];
		$eventGroups = [];
		foreach ($records as $index => $record) {
			if (!is_array($record)) {
				$errors[] = ['index' => $index, 'message' => 'Record non oggetto.'];
				continue;
			}
			try {
				$item = $this->normalizeRecord($record);
				if (isset($seen[$item['legacy_id']])) {
					$errors[] = ['index' => $index, 'legacy_id' => $item['legacy_id'], 'message' => 'legacy_id duplicato nel JSON.'];
					continue;
				}
				$seen[$item['legacy_id']] = true;
				$normalized[] = $item;
				if ($item['legacy_event_name'] !== null && $item['legacy_event_name'] !== '') {
					$key = $this->mappingKey($item['legacy_event_name'], $item['legacy_event_year']);
					$eventGroups[$key] ??= [
						'key' => $key,
						'event_name' => $item['legacy_event_name'],
						'event_year' => $item['legacy_event_year'],
						'photo_count' => 0,
					];
					$eventGroups[$key]['photo_count']++;
				}
			} catch (Throwable $exception) {
				$errors[] = ['index' => $index, 'message' => $exception->getMessage()];
			}
		}

		$groups = $this->matchEventGroups(array_values($eventGroups));
		$mapping = [];
		foreach ($groups as $group) {
			$mapping[$group['key']] = $group['suggested_event_id'];
		}
		foreach ($normalized as &$item) {
			$key = $item['legacy_event_name'] ? $this->mappingKey($item['legacy_event_name'], $item['legacy_event_year']) : null;
			$item['target_event_id'] = $key !== null ? ($mapping[$key] ?? null) : null;
			$item['status'] = ($item['legacy_event_name'] && $item['target_event_id'] === null) ? 'blocked_event_mapping' : 'pending';
		}
		unset($item);

		$summary = [
			'total' => count($records),
			'valid' => count($normalized),
			'invalid' => count($errors),
			'with_event' => count(array_filter($normalized, fn (array $item): bool => (string) ($item['legacy_event_name'] ?? '') !== '')),
			'without_event' => count(array_filter($normalized, fn (array $item): bool => (string) ($item['legacy_event_name'] ?? '') === '')),
			'event_groups' => count($groups),
			'errors' => array_slice($errors, 0, 50),
			'groups' => $groups,
		];

		$this->db->beginTransaction();
		try {
			$importId = $this->imports->createImport([
				'source' => self::SOURCE,
				'filename' => $this->safeFilename((string) ($file['name'] ?? 'legacy-photos.json')),
				'destination_user_id' => $destinationUserId,
				'total_records' => count($records),
				'valid_records' => count($normalized),
				'summary' => $summary,
				'mapping' => $mapping,
				'created_by_user_id' => $adminUserId,
			]);
			$this->imports->addItems($importId, $normalized);
			$this->imports->refreshCounters($importId);
			$this->db->commit();
		} catch (Throwable $exception) {
			$this->db->rollBack();
			throw $exception;
		}

		$this->auditLogService->logAudit([
			'user_id' => $adminUserId,
			'action_type' => AuditLogActionType::LEGACY_PHOTO_IMPORT_ANALYZED,
			'entity_type' => 'legacy_photo_import',
			'entity_id' => $importId,
			'payload' => ['valid_records' => count($normalized), 'invalid_records' => count($errors)],
		]);
		error_log('LegacyPhotoImport #' . $importId . ' analyzed');

		return ['import_id' => $importId, 'summary' => $summary];
	}

	public function saveMapping(int $importId, array $mapping, int $adminUserId): void
	{
		$clean = [];
		foreach ($mapping as $key => $eventId) {
			$clean[(string) $key] = ((int) $eventId) > 0 ? (int) $eventId : null;
		}
		$this->imports->updateMapping($importId, $clean);
		$this->auditLogService->logAudit([
			'user_id' => $adminUserId,
			'action_type' => AuditLogActionType::LEGACY_PHOTO_IMPORT_MAPPING_UPDATED,
			'entity_type' => 'legacy_photo_import',
			'entity_id' => $importId,
		]);
	}

	public function processBatch(int $importId, int $adminUserId, bool $retryErrors = false, ?string $groupKey = null): array
	{
		$import = $this->imports->findImport($importId);
		if (!$import) {
			throw new InvalidArgumentException('Import non trovato.');
		}
		$items = $this->imports->claimBatch($importId, self::BATCH_SIZE, $retryErrors, $groupKey);
		foreach ($items as $item) {
			$this->processItem($item, (int) $import['destination_user_id'], $adminUserId);
		}
		$counters = $this->imports->refreshCounters($importId);
		if (in_array($counters['status'] ?? '', ['completed', 'completed_with_errors'], true)) {
			error_log('LegacyPhotoImport #' . $importId . ' completed');
		}
		return [
			'processed' => count($items),
			'imported' => $counters['imported'] ?? 0,
			'skipped' => $counters['skipped'] ?? 0,
			'errors' => $counters['errors'] ?? 0,
			'total_processed' => $counters['processed'] ?? 0,
			'total' => $counters['total'] ?? 0,
			'remaining' => $counters['remaining'] ?? 0,
			'completed' => in_array($counters['status'] ?? '', ['completed', 'completed_with_errors'], true),
			'status' => $counters['status'] ?? 'processing',
			'group_completed' => $groupKey !== null && count($items) === 0,
		];
	}

	public function searchEvents(string $query, ?int $year): array
	{
		return $this->imports->searchEventsForImport(trim($query), $year);
	}

	public function status(int $importId): array
	{
		$import = $this->getImport($importId);
		if (!$import) {
			throw new InvalidArgumentException('Import non trovato.');
		}
		$errors = $this->imports->listItems($importId, ['error'], 50);
		return ['import' => $import, 'errors' => $errors];
	}

	public function reportCsv(int $importId): string
	{
		$rows = $this->imports->listItems($importId, [], 1000);
		$out = fopen('php://temp', 'r+');
		fputcsv($out, ['legacy_id', 'titolo', 'evento_legacy', 'anno', 'evento_nuovo_id', 'status', 'photo_id', 'errore']);
		foreach ($rows as $row) {
			fputcsv($out, [
				$row['legacy_id'],
				$row['legacy_title'],
				$row['legacy_event_name'],
				$row['legacy_event_year'],
				$row['target_event_id'],
				$row['status'],
				$row['photo_id'],
				$row['error_message'],
			]);
		}
		rewind($out);
		return (string) stream_get_contents($out);
	}

	private function processItem(array $item, int $destinationUserId, int $adminUserId): void
	{
		$temp = null;
		try {
			if ($this->imports->findLegacyMapping((string) $item['legacy_source'], (int) $item['legacy_id'])) {
				$this->imports->markItemSkipped((int) $item['id']);
				return;
			}
			if ((int) ($item['target_event_id'] ?? 0) <= 0) {
				$this->imports->markItemError((int) $item['id'], 'BLOCKED_EVENT_MAPPING', 'Evento non associato.');
				return;
			}
			$temp = $this->downloader->download((string) $item['original_url']);
			$photo = $this->photoService->createFromLocalFile(
				$temp,
				(int) $item['target_event_id'],
				$destinationUserId,
				(string) ($item['legacy_title'] ?: ('legacy-' . $item['legacy_id'])),
				'legacy_import'
			);
			$this->imports->createLegacyMapping((string) $item['legacy_source'], (int) $item['legacy_id'], (int) $photo['id'], $item['legacy_detail_url'] ?: null);
			$this->imports->markItemImported((int) $item['id'], (int) $photo['id']);
			error_log('Legacy photo ' . (int) $item['legacy_id'] . ' imported as photo ' . (int) $photo['id']);
		} catch (Throwable $exception) {
			$this->imports->markItemError((int) $item['id'], $this->errorCode($exception), $exception->getMessage());
			error_log('Legacy photo ' . (int) ($item['legacy_id'] ?? 0) . ' import failed: ' . $exception->getMessage());
		} finally {
			if ($temp !== null && is_file($temp)) {
				@unlink($temp);
			}
		}
	}

	private function normalizeRecord(array $record): array
	{
		$legacyId = filter_var($record['legacy_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		if (!$legacyId) {
			throw new InvalidArgumentException('legacy_id mancante o non valido.');
		}
		$originalUrl = trim((string) ($record['original_url'] ?? ''));
		if ($originalUrl === '') {
			throw new InvalidArgumentException('original_url mancante.');
		}
		$this->downloader->assertAllowedUrl($originalUrl);
		return [
			'legacy_source' => self::SOURCE,
			'legacy_id' => (int) $legacyId,
			'legacy_title' => $this->nullableString($record['titolo'] ?? null, 255),
			'legacy_author' => $this->nullableString($record['autore'] ?? null, 255),
			'legacy_published_at' => $this->nullableDate($record['data_pubblicazione'] ?? null),
			'legacy_event_name' => $this->nullableString($record['evento'] ?? null, 255),
			'legacy_event_year' => filter_var($record['anno_evento'] ?? null, FILTER_VALIDATE_INT) ?: null,
			'original_url' => $originalUrl,
			'thumbnail_url' => $this->nullableString($record['thumbnail_url'] ?? null, 1000),
			'legacy_detail_url' => $this->nullableString($record['dettaglio_url'] ?? null, 1000),
			'legacy_views' => filter_var($record['visualizzazioni'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) ?: null,
			'legacy_favorites' => filter_var($record['preferiti'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) ?: null,
			'target_event_id' => null,
			'status' => 'pending',
		];
	}

	private function matchEventGroups(array $groups): array
	{
		foreach ($groups as &$group) {
			$candidates = $this->imports->searchEventsForImport((string) $group['event_name'], $group['event_year']);
			$exact = array_values(array_filter($candidates, fn (array $event): bool => $this->normalizeName((string) $event['titolo']) === $this->normalizeName((string) $group['event_name'])));
			$group['candidates'] = $candidates;
			$group['suggested_event_id'] = count($exact) === 1 ? (int) $exact[0]['id'] : null;
			$group['match_status'] = count($exact) === 1 ? 'probable' : (count($candidates) > 1 ? 'verify' : 'missing');
		}
		unset($group);
		return $groups;
	}

	private function mappingKey(string $eventName, ?int $year): string
	{
		return base64_encode(json_encode(['event' => $eventName, 'year' => $year], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
	}

	private function normalizeName(string $name): string
	{
		$name = mb_strtolower(trim($name));
		$name = preg_replace('/[^\pL\pN]+/u', ' ', $name) ?: '';
		return trim(preg_replace('/\s+/', ' ', $name) ?: '');
	}

	private function validateJsonUpload(array $file): void
	{
		if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
			throw new InvalidArgumentException('File JSON non ricevuto.');
		}
		if ((int) ($file['size'] ?? 0) <= 0 || (int) $file['size'] > self::MAX_JSON_BYTES) {
			throw new InvalidArgumentException('File JSON troppo grande.');
		}
	}

	private function assertUserExists(int $userId): void
	{
		$stmt = $this->db->prepare('SELECT 1 FROM users WHERE id = :id AND anonymized_at IS NULL LIMIT 1');
		$stmt->execute([':id' => $userId]);
		if (!$stmt->fetchColumn()) {
			throw new InvalidArgumentException('Utente destinazione non valido.');
		}
	}

	private function nullableString(mixed $value, int $limit): ?string
	{
		$value = trim((string) $value);
		return $value !== '' ? mb_substr($value, 0, $limit) : null;
	}

	private function nullableDate(mixed $value): ?string
	{
		$value = trim((string) $value);
		if ($value === '') {
			return null;
		}
		try {
			return (new DateTimeImmutable($value))->format('Y-m-d H:i:s');
		} catch (Throwable) {
			return null;
		}
	}

	private function safeFilename(string $filename): string
	{
		$filename = preg_replace('/[^\pL\pN._ -]+/u', '', $filename) ?: 'legacy-photos.json';
		return mb_substr($filename, 0, 255);
	}

	private function errorCode(Throwable $exception): string
	{
		$message = mb_strtolower($exception->getMessage());
		if (str_contains($message, 'http') || str_contains($message, 'download')) {
			return 'ERROR_DOWNLOAD';
		}
		if (str_contains($message, 'immagine') || str_contains($message, 'mime')) {
			return 'ERROR_IMAGE';
		}
		return 'ERROR_IMPORT';
	}
}
