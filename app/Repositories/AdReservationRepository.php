<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AdReservationRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function findById(int $id): ?array
	{
		$stmt = $this->db->prepare('SELECT * FROM ad_reservations WHERE id = ? LIMIT 1');
		$stmt->execute([$id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function countActiveSlots(int $positionId, string $startDate, string $endDate, ?int $excludeReservationId = null): int
	{
		$params = [$positionId, $startDate, $endDate];
		$excludeSql = '';

		if ($excludeReservationId) {
			$excludeSql = 'AND id <> ?';
			$params[] = $excludeReservationId;
		}

		$stmt = $this->db->prepare("
			SELECT COALESCE(SUM(reserved_slots), 0)
			FROM ad_reservations
			WHERE position_id = ?
			  AND status = 'reserved'
			  AND expires_at > NOW()
			  AND start_date <= ?
			  AND end_date >= ?
			  {$excludeSql}
		");
		$stmt->execute($params);

		return (int)$stmt->fetchColumn();
	}

	public function create(array $data): int
	{
		$stmt = $this->db->prepare("
			INSERT INTO ad_reservations
			(user_id, position_id, start_date, end_date, reserved_slots, price, currency, status, expires_at, created_at)
			VALUES
			(?, ?, ?, ?, ?, ?, ?, 'reserved', DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW())
		");

		$stmt->execute([
			$data['user_id'] ?? null,
			$data['position_id'],
			$data['start_date'],
			$data['end_date'],
			$data['reserved_slots'] ?? 1,
			$data['price'] ?? null,
			$data['currency'] ?? 'EUR',
			$data['ttl_minutes'] ?? 15,
		]);

		return (int)$this->db->lastInsertId();
	}

	public function convert(int $reservationId, int $campaignId): bool
	{
		$stmt = $this->db->prepare("
			UPDATE ad_reservations
			SET status = 'converted', campaign_id = ?
			WHERE id = ? AND status = 'reserved'
		");

		return $stmt->execute([$campaignId, $reservationId]);
	}

	public function expireOld(): int
	{
		$stmt = $this->db->prepare("
			UPDATE ad_reservations
			SET status = 'expired'
			WHERE status = 'reserved' AND expires_at <= NOW()
		");
		$stmt->execute();

		return $stmt->rowCount();
	}
}
