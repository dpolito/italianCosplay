<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AdBannerRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function findById(int $id): ?array
	{
		$stmt = $this->db->prepare('SELECT * FROM ad_banners WHERE id = ? LIMIT 1');
		$stmt->execute([$id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function getByUser(int $userId): array
	{
		$stmt = $this->db->prepare('SELECT * FROM ad_banners WHERE user_id = ? ORDER BY created_at DESC');
		$stmt->execute([$userId]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function create(array $data): int
	{
		$stmt = $this->db->prepare("
			INSERT INTO ad_banners
			(user_id, title, description, image_path, mobile_image_path, logo_path, target_url, alt_text, sponsor_name, facebook_url, instagram_url, tiktok_url, creative_type, type, status, is_active, created_at)
			VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
		");

		$stmt->execute([
			$data['user_id'] ?? null,
			$data['title'],
			$data['description'] ?? null,
			$data['image_path'] ?? null,
			$data['mobile_image_path'] ?? null,
			$data['logo_path'] ?? null,
			$data['target_url'],
			$data['alt_text'] ?? null,
			$data['sponsor_name'] ?? null,
			$data['facebook_url'] ?? null,
			$data['instagram_url'] ?? null,
			$data['tiktok_url'] ?? null,
			$data['creative_type'] ?? 'image',
			$data['type'] ?? 'sponsor',
			$data['status'] ?? 'pending_review',
		]);

		return (int)$this->db->lastInsertId();
	}

	public function update(int $id, array $data): bool
	{
		$fields = [
			'title = ?',
			'target_url = ?',
			'type = ?',
			'updated_at = NOW()',
		];
		$params = [
			$data['title'],
			$data['target_url'],
			$data['type'] ?? 'sponsor',
		];

		if (!empty($data['image_path'])) {
			$fields[] = 'image_path = ?';
			$params[] = $data['image_path'];
		}

		if (array_key_exists('mobile_image_path', $data)) {
			$fields[] = 'mobile_image_path = ?';
			$params[] = $data['mobile_image_path'] ?: null;
		}

		$params[] = $id;

		$stmt = $this->db->prepare(
			'UPDATE ad_banners SET ' . implode(', ', $fields) . ' WHERE id = ?'
		);

		return $stmt->execute($params);
	}

	public function delete(int $id): bool
	{
		$stmt = $this->db->prepare('DELETE FROM ad_banners WHERE id = ?');

		return $stmt->execute([$id]);
	}

	public function updateStatus(int $id, string $status, bool $isActive = true): bool
	{
		$stmt = $this->db->prepare('UPDATE ad_banners SET status = ?, is_active = ?, updated_at = NOW() WHERE id = ?');

		return $stmt->execute([$status, $isActive ? 1 : 0, $id]);
	}

	public function belongsToUser(int $bannerId, int $userId): bool
	{
		$stmt = $this->db->prepare('SELECT COUNT(*) FROM ad_banners WHERE id = ? AND user_id = ?');
		$stmt->execute([$bannerId, $userId]);

		return (int)$stmt->fetchColumn() > 0;
	}

	public function getRandomInternalBanner(): ?array
	{
		$stmt = $this->db->query("
			SELECT *
			FROM ad_banners
			WHERE type = 'internal' AND status = 'approved' AND is_active = 1
			ORDER BY RAND()
			LIMIT 1
		");
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}
}
