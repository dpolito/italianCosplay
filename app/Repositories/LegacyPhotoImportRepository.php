<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class LegacyPhotoImportRepository
{
	private PDO $db;

	public function __construct(?PDO $db = null)
	{
		$this->db = $db ?? Database::getInstance()->getConnection();
	}

	public function createImport(array $data): int
	{
		$stmt = $this->db->prepare(
			"INSERT INTO legacy_photo_imports
				(source, filename, destination_user_id, total_records, valid_records, status, summary_json, mapping_json, created_by_user_id, created_at)
			 VALUES
				(:source, :filename, :destination_user_id, :total_records, :valid_records, 'analyzed', :summary_json, :mapping_json, :created_by_user_id, NOW())"
		);
		$stmt->execute([
			':source' => $data['source'],
			':filename' => $data['filename'],
			':destination_user_id' => $data['destination_user_id'],
			':total_records' => $data['total_records'],
			':valid_records' => $data['valid_records'],
			':summary_json' => json_encode($data['summary'] ?? [], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
			':mapping_json' => json_encode($data['mapping'] ?? [], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
			':created_by_user_id' => $data['created_by_user_id'],
		]);
		return (int) $this->db->lastInsertId();
	}

	public function addItems(int $importId, array $items): void
	{
		$stmt = $this->db->prepare(
			"INSERT INTO legacy_photo_import_items
				(import_id, legacy_source, legacy_id, legacy_title, legacy_author, legacy_published_at, legacy_event_name, legacy_event_year,
				 original_url, thumbnail_url, legacy_detail_url, legacy_views, legacy_favorites, target_event_id, status, created_at)
			 VALUES
				(:import_id, :legacy_source, :legacy_id, :legacy_title, :legacy_author, :legacy_published_at, :legacy_event_name, :legacy_event_year,
				 :original_url, :thumbnail_url, :legacy_detail_url, :legacy_views, :legacy_favorites, :target_event_id, :status, NOW())"
		);
		foreach ($items as $item) {
			$stmt->execute([
				':import_id' => $importId,
				':legacy_source' => $item['legacy_source'],
				':legacy_id' => $item['legacy_id'],
				':legacy_title' => $item['legacy_title'],
				':legacy_author' => $item['legacy_author'],
				':legacy_published_at' => $item['legacy_published_at'],
				':legacy_event_name' => $item['legacy_event_name'],
				':legacy_event_year' => $item['legacy_event_year'],
				':original_url' => $item['original_url'],
				':thumbnail_url' => $item['thumbnail_url'],
				':legacy_detail_url' => $item['legacy_detail_url'],
				':legacy_views' => $item['legacy_views'],
				':legacy_favorites' => $item['legacy_favorites'],
				':target_event_id' => $item['target_event_id'],
				':status' => $item['status'],
			]);
		}
	}

	public function findImport(int $importId): ?array
	{
		$stmt = $this->db->prepare(
			"SELECT i.*, u.username AS destination_username, u.first_name AS destination_first_name, u.last_name AS destination_last_name
			 FROM legacy_photo_imports i
			 INNER JOIN users u ON u.id = i.destination_user_id
			 WHERE i.id = :id
			 LIMIT 1"
		);
		$stmt->execute([':id' => $importId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function listRecentImports(int $limit = 10): array
	{
		$stmt = $this->db->prepare(
			"SELECT i.*, u.username AS destination_username
			 FROM legacy_photo_imports i
			 INNER JOIN users u ON u.id = i.destination_user_id
			 ORDER BY i.created_at DESC, i.id DESC
			 LIMIT :limit"
		);
		$stmt->bindValue(':limit', max(1, min($limit, 50)), PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function listItems(int $importId, array $statuses = [], int $limit = 200): array
	{
		$where = 'WHERE import_id = :import_id';
		$params = [':import_id' => $importId];
		if ($statuses !== []) {
			$placeholders = [];
			foreach (array_values($statuses) as $index => $status) {
				$key = ':status_' . $index;
				$placeholders[] = $key;
				$params[$key] = $status;
			}
			$where .= ' AND status IN (' . implode(',', $placeholders) . ')';
		}
		$stmt = $this->db->prepare("SELECT * FROM legacy_photo_import_items {$where} ORDER BY id ASC LIMIT " . max(1, min($limit, 1000)));
		$stmt->execute($params);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function updateMapping(int $importId, array $mapping): void
	{
		$this->db->beginTransaction();
		try {
			$stmt = $this->db->prepare(
				"UPDATE legacy_photo_import_items
				 SET target_event_id = :event_id,
					 status = CASE
						WHEN status IN ('imported','imported_without_event','skipped_already_imported') THEN status
						WHEN :event_id_block IS NULL AND legacy_event_name IS NOT NULL AND legacy_event_name <> '' THEN 'blocked_event_mapping'
						ELSE 'pending'
					 END,
					 updated_at = NOW()
				 WHERE import_id = :import_id
				   AND COALESCE(legacy_event_name, '') = :event_name
				   AND (legacy_event_year <=> :event_year)"
			);
			foreach ($mapping as $key => $eventId) {
				[$eventName, $eventYear] = $this->decodeMappingKey($key);
				$stmt->execute([
					':event_id' => $eventId,
					':event_id_block' => $eventId,
					':import_id' => $importId,
					':event_name' => $eventName,
					':event_year' => $eventYear,
				]);
			}
			$this->updateImport($importId, [
				'status' => 'ready',
				'mapping_json' => json_encode($mapping, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
			]);
			$this->refreshCounters($importId);
			$this->db->commit();
		} catch (\Throwable $exception) {
			$this->db->rollBack();
			throw $exception;
		}
	}

	public function claimBatch(int $importId, int $batchSize, bool $retryErrors = false, ?string $groupKey = null): array
	{
		$statuses = $retryErrors ? ["'error'"] : ["'pending'"];
		$statusSql = implode(',', $statuses);
		$groupSql = '';
		$params = [':import_id' => $importId];
		if ($groupKey !== null && $groupKey !== '') {
			[$eventName, $eventYear] = $this->decodeMappingKey($groupKey);
			$groupSql = " AND COALESCE(legacy_event_name, '') = :event_name AND (legacy_event_year <=> :event_year)";
			$params[':event_name'] = $eventName;
			$params[':event_year'] = $eventYear;
		}
		$this->db->beginTransaction();
		try {
			$stmt = $this->db->prepare(
				"SELECT *
				 FROM legacy_photo_import_items
				 WHERE import_id = :import_id
				   AND status IN ({$statusSql})
				   AND (locked_at IS NULL OR locked_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE))
				   {$groupSql}
				 ORDER BY id ASC
				 LIMIT " . max(1, min($batchSize, 20)) . "
				 FOR UPDATE"
			);
			$stmt->execute($params);
			$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if ($items) {
				$ids = array_map('intval', array_column($items, 'id'));
				$this->db->exec("UPDATE legacy_photo_import_items SET status = 'processing', locked_at = NOW(), updated_at = NOW() WHERE id IN (" . implode(',', $ids) . ")");
			}
			$this->db->commit();
			return $items;
		} catch (\Throwable $exception) {
			$this->db->rollBack();
			throw $exception;
		}
	}

	public function markItemImported(int $itemId, int $photoId, string $status = 'imported'): void
	{
		$stmt = $this->db->prepare("UPDATE legacy_photo_import_items SET status = :status, photo_id = :photo_id, error_code = NULL, error_message = NULL, locked_at = NULL, processed_at = NOW(), updated_at = NOW() WHERE id = :id");
		$stmt->execute([':status' => $status, ':photo_id' => $photoId, ':id' => $itemId]);
	}

	public function markItemSkipped(int $itemId, string $status = 'skipped_already_imported'): void
	{
		$stmt = $this->db->prepare("UPDATE legacy_photo_import_items SET status = :status, locked_at = NULL, processed_at = NOW(), updated_at = NOW() WHERE id = :id");
		$stmt->execute([':status' => $status, ':id' => $itemId]);
	}

	public function markItemError(int $itemId, string $code, string $message): void
	{
		$stmt = $this->db->prepare("UPDATE legacy_photo_import_items SET status = 'error', error_code = :code, error_message = :message, locked_at = NULL, processed_at = NOW(), updated_at = NOW() WHERE id = :id");
		$stmt->execute([':code' => mb_substr($code, 0, 80), ':message' => mb_substr($message, 0, 500), ':id' => $itemId]);
	}

	public function findLegacyMapping(string $source, int $legacyId): ?array
	{
		$stmt = $this->db->prepare("SELECT * FROM legacy_photo_mappings WHERE legacy_source = :source AND legacy_id = :legacy_id LIMIT 1");
		$stmt->execute([':source' => $source, ':legacy_id' => $legacyId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function createLegacyMapping(string $source, int $legacyId, int $photoId, ?string $detailUrl): void
	{
		$stmt = $this->db->prepare(
			"INSERT INTO legacy_photo_mappings (legacy_source, legacy_id, photo_id, legacy_detail_url, created_at)
			 VALUES (:source, :legacy_id, :photo_id, :detail_url, NOW())"
		);
		$stmt->execute([':source' => $source, ':legacy_id' => $legacyId, ':photo_id' => $photoId, ':detail_url' => $detailUrl]);
	}

	public function refreshCounters(int $importId): array
	{
		$stmt = $this->db->prepare(
			"SELECT
				COUNT(*) AS total,
				SUM(status IN ('processing','imported','imported_without_event','skipped_already_imported','error')) AS processed,
				SUM(status IN ('imported','imported_without_event')) AS imported,
				SUM(status = 'skipped_already_imported') AS skipped,
				SUM(status = 'error') AS errors,
				SUM(status IN ('pending','processing')) AS remaining
			 FROM legacy_photo_import_items
			 WHERE import_id = :import_id"
		);
		$stmt->execute([':import_id' => $importId]);
		$counters = array_map('intval', $stmt->fetch(PDO::FETCH_ASSOC) ?: []);
		$status = ($counters['remaining'] ?? 0) === 0
			? (($counters['errors'] ?? 0) > 0 ? 'completed_with_errors' : 'completed')
			: 'processing';
		$stmt = $this->db->prepare(
			"UPDATE legacy_photo_imports
			 SET processed_records = :processed,
				 imported_records = :imported,
				 skipped_records = :skipped,
				 error_records = :errors,
				 status = :status,
				 updated_at = NOW(),
				 completed_at = CASE WHEN :is_complete = 1 AND completed_at IS NULL THEN NOW() ELSE completed_at END
			 WHERE id = :id"
		);
		$stmt->execute([
			':processed' => $counters['processed'] ?? 0,
			':imported' => $counters['imported'] ?? 0,
			':skipped' => $counters['skipped'] ?? 0,
			':errors' => $counters['errors'] ?? 0,
			':status' => $status,
			':is_complete' => ($counters['remaining'] ?? 0) === 0 ? 1 : 0,
			':id' => $importId,
		]);
		$counters['status'] = $status;
		return $counters;
	}

	public function searchEventsForImport(string $query, ?int $year = null): array
	{
		$sql = "SELECT id, titolo, slug, data_inizio, data_fine, year FROM events WHERE approvato = 1 AND deleted_at IS NULL";
		$params = [];
		if ($query !== '') {
			$sql .= " AND (titolo LIKE :query_title OR slug LIKE :query_slug)";
			$params[':query_title'] = '%' . $query . '%';
			$params[':query_slug'] = '%' . $query . '%';
		}
		if ($year !== null) {
			$sql .= " AND (year = :year_value OR YEAR(data_inizio) = :year_from_start)";
			$params[':year_value'] = $year;
			$params[':year_from_start'] = $year;
		}
		$sql .= " ORDER BY COALESCE(data_inizio, created_at) DESC, titolo ASC LIMIT 50";
		$stmt = $this->db->prepare($sql);
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
		}
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	private function updateImport(int $importId, array $data): void
	{
		$sets = [];
		$params = [':id' => $importId];
		foreach ($data as $key => $value) {
			$sets[] = "{$key} = :{$key}";
			$params[':' . $key] = $value;
		}
		$sets[] = 'updated_at = NOW()';
		$stmt = $this->db->prepare('UPDATE legacy_photo_imports SET ' . implode(', ', $sets) . ' WHERE id = :id');
		$stmt->execute($params);
	}

	private function decodeMappingKey(string $key): array
	{
		$decoded = json_decode(base64_decode($key, true) ?: '[]', true);
		return [(string) ($decoded['event'] ?? ''), isset($decoded['year']) ? (int) $decoded['year'] : null];
	}
}
