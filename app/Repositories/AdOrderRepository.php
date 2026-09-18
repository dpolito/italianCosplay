<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AdOrderRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function create(array $data): int
	{
		$stmt = $this->db->prepare("
			INSERT INTO ad_orders
			(user_id, reservation_id, campaign_id, subtotal, vat, total, currency, status, created_at)
			VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, NOW())
		");

		$stmt->execute([
			$data['user_id'],
			$data['reservation_id'] ?? null,
			$data['campaign_id'] ?? null,
			$data['subtotal'],
			$data['vat'] ?? 0,
			$data['total'],
			$data['currency'] ?? 'EUR',
			$data['status'] ?? 'pending_payment',
		]);

		return (int)$this->db->lastInsertId();
	}

	public function findById(int $id): ?array
	{
		$stmt = $this->db->prepare('SELECT * FROM ad_orders WHERE id = ? LIMIT 1');
		$stmt->execute([$id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function markAsPaid(int $id): bool
	{
		$stmt = $this->db->prepare("UPDATE ad_orders SET status = 'paid', paid_at = NOW(), updated_at = NOW() WHERE id = ?");

		return $stmt->execute([$id]);
	}

	public function markAsFailed(int $id): bool
	{
		$stmt = $this->db->prepare("UPDATE ad_orders SET status = 'failed', updated_at = NOW() WHERE id = ?");

		return $stmt->execute([$id]);
	}
}
