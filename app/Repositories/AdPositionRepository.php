<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AdPositionRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function findById(int $id): ?array
	{
		$stmt = $this->db->prepare('SELECT * FROM ad_positions WHERE id = ? LIMIT 1');
		$stmt->execute([$id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function findByCode(string $code): ?array
	{
		$stmt = $this->db->prepare('SELECT * FROM ad_positions WHERE code = ? LIMIT 1');
		$stmt->execute([$code]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function getAll(): array
	{
		$stmt = $this->db->query('SELECT * FROM ad_positions ORDER BY sort_order ASC, name ASC');

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getAllActive(): array
	{
		$stmt = $this->db->query("
			SELECT *
			FROM ad_positions
			WHERE is_active = 1
			ORDER BY sort_order ASC, name ASC
		");

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getPrices(int $positionId): array
	{
		$stmt = $this->db->prepare("
			SELECT *
			FROM ad_position_prices
			WHERE position_id = ? AND is_active = 1
			ORDER BY duration_days ASC
		");
		$stmt->execute([$positionId]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findPriceForDuration(int $positionId, int $days): ?array
	{
		$stmt = $this->db->prepare("
			SELECT *
			FROM ad_position_prices
			WHERE position_id = ? AND duration_days = ? AND is_active = 1
			LIMIT 1
		");
		$stmt->execute([$positionId, $days]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function create(array $data): int
	{
		$stmt = $this->db->prepare("
			INSERT INTO ad_positions
			(name, code, page, description, width, height, mobile_width, mobile_height, max_slots, rotation_type, base_price, currency, min_days, max_days, estimated_monthly_impressions, average_ctr, is_sponsored_rel_required, is_active, sort_order, created_at)
			VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
		");

		$stmt->execute([
			$data['name'],
			$data['code'],
			$data['page'],
			$data['description'] ?? null,
			$data['width'] ?: null,
			$data['height'] ?: null,
			$data['mobile_width'] ?: null,
			$data['mobile_height'] ?: null,
			max(1, (int)($data['max_slots'] ?? 1)),
			$data['rotation_type'] ?? 'random',
			(float)($data['base_price'] ?? 0),
			$data['currency'] ?? 'EUR',
			max(1, (int)($data['min_days'] ?? 7)),
			max(1, (int)($data['max_days'] ?? 60)),
			$data['estimated_monthly_impressions'] ?: null,
			$data['average_ctr'] ?: null,
			!empty($data['is_sponsored_rel_required']) ? 1 : 0,
			!empty($data['is_active']) ? 1 : 0,
			(int)($data['sort_order'] ?? 0),
		]);

		return (int)$this->db->lastInsertId();
	}

	public function update(int $id, array $data): bool
	{
		$stmt = $this->db->prepare("
			UPDATE ad_positions
			SET name = ?,
				code = ?,
				page = ?,
				description = ?,
				width = ?,
				height = ?,
				mobile_width = ?,
				mobile_height = ?,
				max_slots = ?,
				rotation_type = ?,
				base_price = ?,
				currency = ?,
				min_days = ?,
				max_days = ?,
				estimated_monthly_impressions = ?,
				average_ctr = ?,
				is_sponsored_rel_required = ?,
				is_active = ?,
				sort_order = ?,
				updated_at = NOW()
			WHERE id = ?
		");

		return $stmt->execute([
			$data['name'],
			$data['code'],
			$data['page'],
			$data['description'] ?? null,
			$data['width'] ?: null,
			$data['height'] ?: null,
			$data['mobile_width'] ?: null,
			$data['mobile_height'] ?: null,
			max(1, (int)($data['max_slots'] ?? 1)),
			$data['rotation_type'] ?? 'random',
			(float)($data['base_price'] ?? 0),
			$data['currency'] ?? 'EUR',
			max(1, (int)($data['min_days'] ?? 7)),
			max(1, (int)($data['max_days'] ?? 60)),
			$data['estimated_monthly_impressions'] ?: null,
			$data['average_ctr'] ?: null,
			!empty($data['is_sponsored_rel_required']) ? 1 : 0,
			!empty($data['is_active']) ? 1 : 0,
			(int)($data['sort_order'] ?? 0),
			$id,
		]);
	}

	public function syncPrices(int $positionId, array $prices): void
	{
		$this->db->prepare('UPDATE ad_position_prices SET is_active = 0 WHERE position_id = ?')->execute([$positionId]);

		$stmt = $this->db->prepare("
			INSERT INTO ad_position_prices (position_id, duration_days, price, currency, is_active, created_at)
			VALUES (?, ?, ?, ?, 1, NOW())
			ON DUPLICATE KEY UPDATE price = VALUES(price), currency = VALUES(currency), is_active = 1, updated_at = NOW()
		");

		foreach ($prices as $days => $price) {
			$days = (int)$days;
			$price = (float)$price;
			if ($days <= 0 || $price <= 0) {
				continue;
			}

			$stmt->execute([$positionId, $days, $price, 'EUR']);
		}
	}

	public function setActive(int $id, bool $active): bool
	{
		$stmt = $this->db->prepare('UPDATE ad_positions SET is_active = ?, updated_at = NOW() WHERE id = ?');

		return $stmt->execute([$active ? 1 : 0, $id]);
	}
}
