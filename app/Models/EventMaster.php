<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class EventMaster extends BaseModel
{
	protected $table = 'events_master';

	public function getAll(): array
	{
		$stmt = $this->db->query("SELECT * FROM {$this->table} WHERE deleted_at IS NULL ORDER BY nome ASC");

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getPublic(): array
	{
		$stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE status = 'active' AND is_public = 1 AND deleted_at IS NULL ORDER BY nome ASC");
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function find(int $id): ?array
	{
		$stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id AND deleted_at IS NULL LIMIT 1");
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);
		$stmt->execute();

		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function findBySlug(string $slug): ?array
	{
		$stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE slug = :slug AND deleted_at IS NULL LIMIT 1");
		$stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
		$stmt->execute();

		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function findPublicBySlug(string $slug): ?array
	{
		$stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE slug = :slug AND status = 'active' AND is_public = 1 AND deleted_at IS NULL LIMIT 1");
		$stmt->execute([':slug' => $slug]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function countEvents(int $id): int
	{
		$stmt = $this->db->prepare("SELECT COUNT(*) FROM events WHERE event_master_id = :id AND deleted_at IS NULL");
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);
		$stmt->execute();

		return (int) $stmt->fetchColumn();
	}

	public function generateUniqueSlug(string $name, ?int $excludeId = null): string
	{
		$baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
		if ($baseSlug === '') {
			$baseSlug = 'evento-master';
		}
		$slug = $baseSlug;
		$counter = 1;

		while (true) {
			$query = "SELECT COUNT(*) FROM {$this->table} WHERE slug = :slug AND deleted_at IS NULL";
			if ($excludeId !== null) {
				$query .= " AND id != :exclude_id";
			}

			$stmt = $this->db->prepare($query);
			$stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
			if ($excludeId !== null) {
				$stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
			}
			$stmt->execute();

			if ((int) $stmt->fetchColumn() === 0) {
				break;
			}

			$slug = $baseSlug . '-' . $counter++;
		}

		return $slug;
	}

	public function create(array $data): int
	{
		$title = $data['nome'] ?? $data['titolo'] ?? $data['name'] ?? '';
		$requestedSlug = trim((string) ($data['slug'] ?? ''));
		$slugSource = $requestedSlug !== '' ? $requestedSlug : $title;
		$slug = $this->generateUniqueSlug($slugSource);

		$stmt = $this->db->prepare("
			INSERT INTO {$this->table} (
				nome, slug, descrizione, sito_web, social_facebook, social_twitter, social_instagram, social_tiktok, social_youtube, status, is_public, created_at, updated_at
			) VALUES (
				:nome, :slug, :descrizione, :sito_web, :social_facebook, :social_twitter, :social_instagram, :social_tiktok, :social_youtube, :status, :is_public, NOW(), NOW()
			)
		");

		$stmt->bindValue(':nome', $title);
		$stmt->bindValue(':slug', $slug);
		$stmt->bindValue(':descrizione', $data['descrizione'] ?: null);
		$stmt->bindValue(':sito_web', $data['sito_web'] ?: null);
		$stmt->bindValue(':social_facebook', $data['social_facebook'] ?: null);
		$stmt->bindValue(':social_twitter', $data['social_twitter'] ?: null);
		$stmt->bindValue(':social_instagram', $data['social_instagram'] ?: null);
		$stmt->bindValue(':social_tiktok', $data['social_tiktok'] ?: null);
		$stmt->bindValue(':social_youtube', $data['social_youtube'] ?: null);
		$stmt->bindValue(':status', $data['status'] ?? 'active');
		$stmt->bindValue(':is_public', array_key_exists('is_public', $data) ? (!empty($data['is_public']) ? 1 : 0) : 1, PDO::PARAM_INT);
		$stmt->execute();

		return (int) $this->db->lastInsertId();
	}

	public function update(int $id, array $data): bool
	{
		$current = $this->find($id);
		if (!$current) {
			return false;
		}

		$title = $data['nome'] ?? $data['titolo'] ?? $data['name'] ?? '';
		$requestedSlug = trim((string) ($data['slug'] ?? ''));
		if ($requestedSlug !== '') {
			$slug = $this->generateUniqueSlug($requestedSlug, $id);
		} elseif (!empty($current['slug'])) {
			$slug = $current['slug'];
		} else {
			$slug = $this->generateUniqueSlug($title, $id);
		}

		$stmt = $this->db->prepare("
			UPDATE {$this->table}
			SET nome = :nome,
				slug = :slug,
				descrizione = :descrizione,
				sito_web = :sito_web,
				social_facebook = :social_facebook,
				social_twitter = :social_twitter,
				social_instagram = :social_instagram,
				social_tiktok = :social_tiktok,
				social_youtube = :social_youtube,
				status = :status,
				is_public = :is_public,
				updated_at = NOW()
			WHERE id = :id
		");

		$stmt->bindValue(':nome', $title);
		$stmt->bindValue(':slug', $slug);
		$stmt->bindValue(':descrizione', $data['descrizione'] ?: null);
		$stmt->bindValue(':sito_web', $data['sito_web'] ?: null);
		$stmt->bindValue(':social_facebook', $data['social_facebook'] ?: null);
		$stmt->bindValue(':social_twitter', $data['social_twitter'] ?: null);
		$stmt->bindValue(':social_instagram', $data['social_instagram'] ?: null);
		$stmt->bindValue(':social_tiktok', $data['social_tiktok'] ?: null);
		$stmt->bindValue(':social_youtube', $data['social_youtube'] ?: null);
		$stmt->bindValue(':status', $data['status'] ?? ($current['status'] ?? 'active'));
		$stmt->bindValue(':is_public', array_key_exists('is_public', $data) ? (!empty($data['is_public']) ? 1 : 0) : (int) ($current['is_public'] ?? 1), PDO::PARAM_INT);
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);

		return $stmt->execute();
	}

	public function delete(int $id, ?int $deletedBy = null, ?string $reason = null): bool
	{
		$stmt = $this->db->prepare("
			UPDATE {$this->table}
			SET deleted_at = NOW(),
			    deleted_by = :deleted_by,
			    deletion_reason = :deletion_reason,
			    updated_at = NOW()
			WHERE id = :id
			  AND deleted_at IS NULL
		");
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);
		$stmt->bindValue(':deleted_by', $deletedBy, $deletedBy === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
		$stmt->bindValue(':deletion_reason', $reason);

		return $stmt->execute();
	}
}
