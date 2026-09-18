<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class PrivacyPolicyRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function getLatestActiveVersion(): ?array
	{
		$stmt = $this->db->prepare(
			'SELECT * FROM privacy_policy_versions WHERE is_active = 1 ORDER BY version_number DESC, id DESC LIMIT 1'
		);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function getLatestVersion(): ?array
	{
		$stmt = $this->db->query(
			'SELECT * FROM privacy_policy_versions ORDER BY version_number DESC, id DESC LIMIT 1'
		);
		$row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;

		return $row ?: null;
	}

	public function getAllVersions(): array
	{
		$stmt = $this->db->query(
			'SELECT * FROM privacy_policy_versions ORDER BY version_number DESC, id DESC'
		);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findById(int $id): ?array
	{
		$stmt = $this->db->prepare('SELECT * FROM privacy_policy_versions WHERE id = ? LIMIT 1');
		$stmt->execute([$id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function createVersion(array $data): int
	{
		$stmt = $this->db->prepare(
			'INSERT INTO privacy_policy_versions
				(version_number, title, content, is_active, published_at, created_by, updated_by, created_at, updated_at)
			 VALUES
				(:version_number, :title, :content, :is_active, :published_at, :created_by, :updated_by, NOW(), NULL)'
		);
		$stmt->execute([
			'version_number' => (int) $data['version_number'],
			'title' => $data['title'],
			'content' => $data['content'],
			'is_active' => !empty($data['is_active']) ? 1 : 0,
			'published_at' => $data['published_at'] ?? null,
			'created_by' => $data['created_by'] ?? null,
			'updated_by' => $data['updated_by'] ?? null,
		]);

		return (int) $this->db->lastInsertId();
	}

	public function updateVersion(int $id, array $data): bool
	{
		$stmt = $this->db->prepare(
			'UPDATE privacy_policy_versions
			 SET version_number = :version_number,
			     title = :title,
			     content = :content,
			     is_active = :is_active,
			     published_at = :published_at,
			     updated_by = :updated_by,
			     updated_at = NOW()
			 WHERE id = :id'
		);

		return $stmt->execute([
			'id' => $id,
			'version_number' => (int) $data['version_number'],
			'title' => $data['title'],
			'content' => $data['content'],
			'is_active' => !empty($data['is_active']) ? 1 : 0,
			'published_at' => $data['published_at'] ?? null,
			'updated_by' => $data['updated_by'] ?? null,
		]);
	}

	public function setActiveVersion(int $id): bool
	{
		$this->db->beginTransaction();
		try {
			$this->db->exec('UPDATE privacy_policy_versions SET is_active = 0, updated_at = NOW()');
			$stmt = $this->db->prepare(
				'UPDATE privacy_policy_versions SET is_active = 1, updated_at = NOW() WHERE id = ?'
			);
			$result = $stmt->execute([$id]);
			$this->db->commit();
			return $result;
		} catch (\Throwable $e) {
			if ($this->db->inTransaction()) {
				$this->db->rollBack();
			}
			throw $e;
		}
	}
}
