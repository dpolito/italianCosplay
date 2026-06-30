<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AdTrackingRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function insertImpression(array $data): bool
	{
		$stmt = $this->db->prepare("
			INSERT INTO ad_impressions
			(campaign_id, banner_id, position_id, page, user_hash, ip_hash, user_agent_hash, device_type, country_code, created_at)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		");

		$ok = $stmt->execute([
			$data['campaign_id'],
			$data['banner_id'],
			$data['position_id'],
			$data['page'] ?? null,
			$data['user_hash'] ?? null,
			$data['ip_hash'] ?? null,
			$data['user_agent_hash'] ?? null,
			$data['device_type'] ?? 'unknown',
			$data['country_code'] ?? null,
			$data['created_at'],
		]);

		if ($ok) {
			$this->incrementDaily((int)$data['campaign_id'], (int)$data['position_id'], $data['device_type'] ?? 'unknown', true);
		}

		return $ok;
	}

	public function insertClick(array $data): bool
	{
		$stmt = $this->db->prepare("
			INSERT INTO ad_clicks
			(campaign_id, banner_id, position_id, page, target_url, user_hash, ip_hash, user_agent_hash, referrer, device_type, country_code, created_at)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		");

		$ok = $stmt->execute([
			$data['campaign_id'],
			$data['banner_id'],
			$data['position_id'],
			$data['page'] ?? null,
			$data['target_url'] ?? null,
			$data['user_hash'] ?? null,
			$data['ip_hash'] ?? null,
			$data['user_agent_hash'] ?? null,
			$data['referrer'] ?? null,
			$data['device_type'] ?? 'unknown',
			$data['country_code'] ?? null,
			$data['created_at'],
		]);

		if ($ok) {
			$this->incrementDaily((int)$data['campaign_id'], (int)$data['position_id'], $data['device_type'] ?? 'unknown', false);
		}

		return $ok;
	}

	public function isDuplicateImpression(array $data): bool
	{
		if (empty($data['user_hash'])) {
			return false;
		}

		$stmt = $this->db->prepare("
			SELECT COUNT(*)
			FROM ad_impressions
			WHERE campaign_id = ?
			  AND banner_id = ?
			  AND position_id = ?
			  AND user_hash = ?
			  AND created_at >= (NOW() - INTERVAL 10 MINUTE)
		");

		$stmt->execute([
			$data['campaign_id'],
			$data['banner_id'],
			$data['position_id'],
			$data['user_hash'],
		]);

		return (int)$stmt->fetchColumn() > 0;
	}

	private function incrementDaily(int $campaignId, int $positionId, string $deviceType, bool $isImpression): void
	{
		$deviceColumn = match ($deviceType) {
			'desktop' => 'desktop_impressions',
			'mobile' => 'mobile_impressions',
			'tablet' => 'tablet_impressions',
			default => null,
		};

		$updates = $isImpression ? 'impressions = impressions + 1' : 'clicks = clicks + 1';
		if ($isImpression && $deviceColumn) {
			$updates .= ", {$deviceColumn} = {$deviceColumn} + 1";
		}

		$stmt = $this->db->prepare("
			INSERT INTO ad_stats_daily (campaign_id, position_id, date, impressions, clicks, {$this->initialDeviceColumn($deviceColumn)}, created_at)
			VALUES (?, ?, CURDATE(), ?, ?, {$this->initialDeviceValue($deviceColumn)}, NOW())
			ON DUPLICATE KEY UPDATE {$updates}, updated_at = NOW()
		");

		$stmt->execute([
			$campaignId,
			$positionId,
			$isImpression ? 1 : 0,
			$isImpression ? 0 : 1,
		]);
	}

	private function initialDeviceColumn(?string $deviceColumn): string
	{
		return $deviceColumn ?: 'desktop_impressions';
	}

	private function initialDeviceValue(?string $deviceColumn): string
	{
		return $deviceColumn ? '1' : '0';
	}
}
