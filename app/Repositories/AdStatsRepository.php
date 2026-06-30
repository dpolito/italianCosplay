<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AdStatsRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	/**
	 * STAT GLOBALI CAMPAGNA
	 */
	public function getCampaignStats(int $campaignId): array
	{
		$impressions = $this->countImpressions($campaignId);
		$clicks      = $this->countClicks($campaignId);

		$ctr = $impressions > 0
			? round(($clicks / $impressions) * 100, 2)
			: 0;

		return [
			'campaign_id' => $campaignId,
			'impressions'  => $impressions,
			'clicks'       => $clicks,
			'ctr'          => $ctr
		];
	}

	/**
	 * IMPRESSIONS COUNT
	 */
	public function countImpressions(int $campaignId): int
	{
		$stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM ad_impressions
            WHERE campaign_id = ?
        ");

		$stmt->execute([$campaignId]);

		return (int)$stmt->fetchColumn();
	}

	/**
	 * CLICKS COUNT
	 */
	public function countClicks(int $campaignId): int
	{
		$stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM ad_clicks
            WHERE campaign_id = ?
        ");

		$stmt->execute([$campaignId]);

		return (int)$stmt->fetchColumn();
	}

	/**
	 * STAT PER POSIZIONE
	 */
	public function getPositionStats(int $positionId): array
	{
		$stmt = $this->db->prepare("
            SELECT 
                (SELECT COUNT(*) FROM ad_impressions WHERE position_id = ?) AS impressions,
                (SELECT COUNT(*) FROM ad_clicks WHERE position_id = ?) AS clicks
        ");

		$stmt->execute([$positionId, $positionId]);

		$data = $stmt->fetch(PDO::FETCH_ASSOC);

		$impressions = (int)($data['impressions'] ?? 0);
		$clicks      = (int)($data['clicks'] ?? 0);

		$ctr = $impressions > 0
			? round(($clicks / $impressions) * 100, 2)
			: 0;

		return [
			'position_id' => $positionId,
			'impressions'  => $impressions,
			'clicks'       => $clicks,
			'ctr'          => $ctr
		];
	}

	/**
	 * STAT GLOBALI USER (quanto guadagna il sistema da user)
	 */
	public function getUserCampaignStats(int $userId): array
	{
		$stmt = $this->db->prepare("
            SELECT 
                COUNT(i.id) AS impressions,
                COUNT(c.id) AS clicks
            FROM ad_campaigns ac
            LEFT JOIN ad_impressions i ON i.campaign_id = ac.id
            LEFT JOIN ad_clicks c ON c.campaign_id = ac.id
            WHERE ac.user_id = ?
        ");

		$stmt->execute([$userId]);

		$data = $stmt->fetch(PDO::FETCH_ASSOC);

		$impressions = (int)($data['impressions'] ?? 0);
		$clicks      = (int)($data['clicks'] ?? 0);

		$ctr = $impressions > 0
			? round(($clicks / $impressions) * 100, 2)
			: 0;

		return [
			'user_id'     => $userId,
			'impressions' => $impressions,
			'clicks'      => $clicks,
			'ctr'         => $ctr
		];
	}

	/**
	 * TOP CAMPAIGNS PER PERFORMANCE
	 */
	public function getTopCampaigns(int $limit = 10): array
	{
		$stmt = $this->db->prepare("
            SELECT 
                ac.id,
                ac.user_id,
                ac.position_id,
                ac.banner_id,
                COUNT(i.id) AS impressions,
                COUNT(c.id) AS clicks
            FROM ad_campaigns ac
            LEFT JOIN ad_impressions i ON i.campaign_id = ac.id
            LEFT JOIN ad_clicks c ON c.campaign_id = ac.id
            GROUP BY ac.id
            ORDER BY clicks DESC, impressions DESC
            LIMIT ?
        ");

		$stmt->bindValue(1, $limit, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * PERFORMANCE PER GIORNO (per dashboard grafici)
	 */
	public function getDailyStats(int $campaignId): array
	{
		$stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) AS day,
                COUNT(*) AS impressions
            FROM ad_impressions
            WHERE campaign_id = ?
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ");

		$stmt->execute([$campaignId]);

		$impressions = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) AS day,
                COUNT(*) AS clicks
            FROM ad_clicks
            WHERE campaign_id = ?
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ");

		$stmt->execute([$campaignId]);

		$clicks = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return [
			'impressions' => $impressions,
			'clicks'      => $clicks
		];
	}
}
