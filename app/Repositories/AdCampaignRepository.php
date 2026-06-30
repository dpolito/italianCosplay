<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AdCampaignRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function findById(int $id): ?array
	{
		$stmt = $this->db->prepare("
			SELECT c.*, p.name AS position_name, p.code AS position_code, p.page AS position_page,
				   p.width, p.height, p.mobile_width, p.mobile_height,
				   b.title AS banner_title, b.image_path, b.target_url, b.description AS banner_description,
				   b.sponsor_name, b.creative_type
			FROM ad_campaigns c
			INNER JOIN ad_positions p ON p.id = c.position_id
			INNER JOIN ad_banners b ON b.id = c.banner_id
			WHERE c.id = ?
			LIMIT 1
		");
		$stmt->execute([$id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	public function findByUser(int $userId): array
	{
		$stmt = $this->db->prepare("
			SELECT c.*, p.name AS position_name, p.code AS position_code, b.title AS banner_title, b.image_path
			FROM ad_campaigns c
			INNER JOIN ad_positions p ON p.id = c.position_id
			INNER JOIN ad_banners b ON b.id = c.banner_id
			WHERE c.user_id = ?
			ORDER BY c.created_at DESC
		");
		$stmt->execute([$userId]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findAllForAdmin(): array
	{
		$stmt = $this->db->query("
			SELECT c.*, p.name AS position_name, b.title AS banner_title, u.username
			FROM ad_campaigns c
			INNER JOIN ad_positions p ON p.id = c.position_id
			INNER JOIN ad_banners b ON b.id = c.banner_id
			INNER JOIN users u ON u.id = c.user_id
			ORDER BY c.created_at DESC
		");

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findActiveByPosition(int $positionId): array
	{
		$stmt = $this->db->prepare("
			SELECT c.*, b.title, b.image_path, b.target_url, b.alt_text, b.creative_type, b.description, b.sponsor_name
			FROM ad_campaigns c
			INNER JOIN ad_banners b ON b.id = c.banner_id
			WHERE c.position_id = ?
			  AND c.status = 'active'
			  AND c.approval_status = 'approved'
			  AND CURDATE() BETWEEN c.start_date AND c.end_date
			ORDER BY c.created_at DESC
		");
		$stmt->execute([$positionId]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function countBookedSlots(int $positionId, string $startDate, string $endDate, ?int $excludeCampaignId = null): int
	{
		$params = [$positionId, $startDate, $endDate];
		$excludeSql = '';

		if ($excludeCampaignId) {
			$excludeSql = 'AND id <> ?';
			$params[] = $excludeCampaignId;
		}

		$stmt = $this->db->prepare("
			SELECT COUNT(*)
			FROM ad_campaigns
			WHERE position_id = ?
			  AND status IN ('reserved','pending_payment','pending_approval','scheduled','active','paused')
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
			INSERT INTO ad_campaigns
			(user_id, position_id, banner_id, start_date, end_date, price, currency, status, approval_status, notes, created_at)
			VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
		");

		$stmt->execute([
			$data['user_id'],
			$data['position_id'],
			$data['banner_id'],
			$data['start_date'],
			$data['end_date'],
			$data['price'],
			$data['currency'] ?? 'EUR',
			$data['status'] ?? 'reserved',
			$data['approval_status'] ?? 'pending',
			$data['notes'] ?? null,
		]);

		return (int)$this->db->lastInsertId();
	}

	public function updateStatus(int $id, string $status): bool
	{
		$stmt = $this->db->prepare('UPDATE ad_campaigns SET status = ?, updated_at = NOW() WHERE id = ?');

		return $stmt->execute([$status, $id]);
	}

	public function updateApproval(int $id, string $approvalStatus, string $status, ?string $adminNotes = null): bool
	{
		$stmt = $this->db->prepare("
			UPDATE ad_campaigns
			SET approval_status = ?, status = ?, admin_notes = ?, updated_at = NOW()
			WHERE id = ?
		");

		return $stmt->execute([$approvalStatus, $status, $adminNotes, $id]);
	}

	public function activateDueCampaigns(): int
	{
		$stmt = $this->db->prepare("
			UPDATE ad_campaigns
			SET status = 'active', updated_at = NOW()
			WHERE status = 'scheduled'
			  AND approval_status = 'approved'
			  AND CURDATE() BETWEEN start_date AND end_date
		");
		$stmt->execute();

		return $stmt->rowCount();
	}

	public function expireEndedCampaigns(): int
	{
		$stmt = $this->db->prepare("
			UPDATE ad_campaigns
			SET status = 'expired', updated_at = NOW()
			WHERE status IN ('scheduled','active','paused')
			  AND end_date < CURDATE()
		");
		$stmt->execute();

		return $stmt->rowCount();
	}
}
