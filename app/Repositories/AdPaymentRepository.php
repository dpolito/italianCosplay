<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AdPaymentRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function create(array $data): int
	{
		$stmt = $this->db->prepare("
			INSERT INTO ad_payments
			(order_id, campaign_id, provider, provider_reference, amount, currency, status, created_at)
			VALUES
			(?, ?, ?, ?, ?, ?, ?, NOW())
		");

		$stmt->execute([
			$data['order_id'] ?? null,
			$data['campaign_id'],
			$data['provider'] ?? 'adyen',
			$data['provider_reference'] ?? null,
			$data['amount'],
			$data['currency'] ?? 'EUR',
			$data['status'] ?? 'pending',
		]);

		return (int)$this->db->lastInsertId();
	}

	public function findById(int $id): ?array
	{
		$stmt = $this->db->prepare('SELECT * FROM ad_payments WHERE id = ? LIMIT 1');
		$stmt->execute([$id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function findByCampaign(int $campaignId): ?array
	{
		$stmt = $this->db->prepare('SELECT * FROM ad_payments WHERE campaign_id = ? ORDER BY created_at DESC LIMIT 1');
		$stmt->execute([$campaignId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function markAsPaid(int $id, ?string $providerReference = null): bool
	{
		$stmt = $this->db->prepare("
			UPDATE ad_payments
			SET status = 'paid', provider_reference = COALESCE(?, provider_reference), paid_at = NOW(), updated_at = NOW()
			WHERE id = ?
		");

		return $stmt->execute([$providerReference, $id]);
	}

	public function markAsFailed(int $id): bool
	{
		$stmt = $this->db->prepare("UPDATE ad_payments SET status = 'failed', updated_at = NOW() WHERE id = ?");

		return $stmt->execute([$id]);
	}

	public function refund(int $id): bool
	{
		$stmt = $this->db->prepare("UPDATE ad_payments SET status = 'refunded', updated_at = NOW() WHERE id = ?");

		return $stmt->execute([$id]);
	}
}
