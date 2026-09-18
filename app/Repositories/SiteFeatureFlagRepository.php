<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class SiteFeatureFlagRepository
{
	private PDO $db;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	public function getAll(): array
	{
		$stmt = $this->db->query('SELECT * FROM site_feature_flags ORDER BY sort_order ASC, label ASC');

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getEnabledMap(): array
	{
		$flags = $this->getAll();
		$map = [];

		foreach ($flags as $flag) {
			$map[$flag['flag_key']] = (int) ($flag['is_enabled'] ?? 0) === 1;
		}

		return $map;
	}

	public function upsertMany(array $flags): bool
	{
		$this->db->beginTransaction();

		try {
			$stmt = $this->db->prepare(
				'UPDATE site_feature_flags
				 SET is_enabled = :is_enabled,
				     updated_at = NOW()
				 WHERE flag_key = :flag_key'
			);

			foreach ($flags as $flagKey => $isEnabled) {
				$stmt->execute([
					'flag_key' => $flagKey,
					'is_enabled' => $isEnabled ? 1 : 0,
				]);
			}

			$this->db->commit();
			return true;
		} catch (\Throwable $throwable) {
			if ($this->db->inTransaction()) {
				$this->db->rollBack();
			}

			throw $throwable;
		}
	}
}
