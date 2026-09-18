<?php

namespace App\Services;

use App\Core\Database;
use App\Support\AuditLogActionType;
use App\Services\ImageService;
use PDO;
use function file_exists;
use function finfo_file;
use function finfo_open;
use function getimagesize;
use function imagealphablending;
use function imagecreatefromgif;
use function imagecreatefromjpeg;
use function imagecreatefrompng;
use function imagecreatetruecolor;
use function imagecopyresampled;
use function imagepalettetotruecolor;
use function imagedestroy;
use function imagewebp;
use function imagesavealpha;
use function in_array;
use function is_dir;
use function mkdir;
use function move_uploaded_file;
use function pathinfo;
use function random_bytes;
use function str_replace;
use function strtolower;
use function time;
use const FILEINFO_MIME_TYPE;
use const UPLOAD_ERR_OK;

class CosplayPortfolioService
{
	private PDO $db;
	private AuditLogService $auditLogService;

	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
		$this->auditLogService = new AuditLogService();
	}

	public function getUserPortfolio(int $userId): array
	{
		$stmt = $this->db->prepare(
			"SELECT
				p.id,
				p.user_id,
				p.anilist_character_id,
				p.custom_name,
				p.notes,
				p.reference_image,
				p.is_public,
				p.created_at,
				p.updated_at,
				c.name_full,
				c.name_native,
				c.image_large,
				c.description,
				GROUP_CONCAT(DISTINCT a.title_romaji ORDER BY a.popularity DESC SEPARATOR ', ') AS anime_titles
			FROM user_cosplay_portfolio p
			INNER JOIN anilist_characters c ON c.id = p.anilist_character_id
			LEFT JOIN anilist_anime_character ac ON ac.character_id = c.id
			LEFT JOIN anilist_anime a ON a.id = ac.anime_id
			WHERE p.user_id = :user_id
			GROUP BY p.id, p.user_id, p.anilist_character_id, p.custom_name, p.notes, p.reference_image, p.is_public, p.created_at, p.updated_at, c.name_full, c.name_native, c.image_large, c.description
			ORDER BY p.created_at DESC"
		);
		$stmt->execute([':user_id' => $userId]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getCharacterSuggestions(string $search = '', int $limit = 200): array
	{
		$search = trim($search);
		$sql = "SELECT
				c.id,
				c.name_full,
				c.name_native,
				c.image_large,
				GROUP_CONCAT(DISTINCT a.title_romaji ORDER BY a.popularity DESC SEPARATOR ', ') AS anime_titles
			FROM anilist_characters c
			LEFT JOIN anilist_anime_character ac ON ac.character_id = c.id
			LEFT JOIN anilist_anime a ON a.id = ac.anime_id";
		$params = [];

		if ($search !== '') {
			$sql .= " WHERE c.name_full LIKE :search_full OR c.name_native LIKE :search_native OR a.title_romaji LIKE :search_romaji OR a.title_english LIKE :search_english";
			$params[':search_full'] = '%' . $search . '%';
			$params[':search_native'] = '%' . $search . '%';
			$params[':search_romaji'] = '%' . $search . '%';
			$params[':search_english'] = '%' . $search . '%';
		}

		$sql .= " GROUP BY c.id, c.name_full, c.name_native, c.image_large ORDER BY COALESCE(c.name_full, c.name_native) ASC LIMIT :limit";
		$stmt = $this->db->prepare($sql);
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value, PDO::PARAM_STR);
		}
		$stmt->bindValue(':limit', max(1, min($limit, 1000)), PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function uploadReferenceImage(array $file, int $userId, int $portfolioId): ?string
	{
		if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
			return null;
		}

		$mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']);
		$allowed = ['image/jpeg', 'image/png', 'image/webp'];
		if (!in_array($mime, $allowed, true)) {
			return null;
		}

		if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
			return null;
		}

		$uploadDir = APP_ROOT . '/public_assets/uploads/cosplay_portfolio/' . $userId;
		if (!is_dir($uploadDir)) {
			mkdir($uploadDir, 0775, true);
		}

		$baseName = 'cosplay-' . $userId . '-' . $portfolioId . '-' . time() . '-' . bin2hex(random_bytes(4));
		$targetPath = $uploadDir . '/' . $baseName . '.webp';

		if (!$this->convertUploadToWebp($file['tmp_name'], $targetPath)) {
			return null;
		}

		return '/uploads/cosplay_portfolio/' . $userId . '/' . basename($targetPath);
	}

	public function getPortfolioItem(int $userId, int $portfolioId): ?array
	{
		$stmt = $this->db->prepare(
			"SELECT
				p.*,
				c.name_full,
				c.name_native,
				c.image_large,
				c.description
			FROM user_cosplay_portfolio p
			INNER JOIN anilist_characters c ON c.id = p.anilist_character_id
			WHERE p.user_id = :user_id AND p.id = :id"
		);
		$stmt->execute([
			':user_id' => $userId,
			':id' => $portfolioId,
		]);

		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function getLatestPortfolioItemByCharacter(int $userId, int $anilistCharacterId): ?array
	{
		$stmt = $this->db->prepare(
			"SELECT
				p.*,
				c.name_full,
				c.name_native,
				c.image_large,
				c.description
			FROM user_cosplay_portfolio p
			INNER JOIN anilist_characters c ON c.id = p.anilist_character_id
			WHERE p.user_id = :user_id AND p.anilist_character_id = :anilist_character_id
			ORDER BY p.created_at DESC, p.id DESC
			LIMIT 1"
		);
		$stmt->execute([
			':user_id' => $userId,
			':anilist_character_id' => $anilistCharacterId,
		]);

		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function getCharacterSelectionsForEvent(int $userId, int $eventId): array
	{
		$stmt = $this->db->prepare(
			"SELECT
				plan.id,
				plan.status,
				plan.portfolio_id,
				p.custom_name,
				p.anilist_character_id,
				c.name_full,
				c.name_native,
				c.image_large,
				c.description
			FROM user_event_cosplay_plans plan
			INNER JOIN user_cosplay_portfolio p ON p.id = plan.portfolio_id
			INNER JOIN anilist_characters c ON c.id = p.anilist_character_id
			WHERE plan.user_id = :user_id AND plan.event_id = :event_id
			ORDER BY plan.created_at DESC, plan.id DESC"
		);
		$stmt->execute([
			':user_id' => $userId,
			':event_id' => $eventId,
		]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getPublicEventCosplaySelections(int $eventId): array
	{
		$stmt = $this->db->prepare(
			"SELECT
				plan.id,
				plan.user_id,
				plan.event_id,
				plan.portfolio_id,
				plan.status,
				plan.created_at,
				u.username,
				u.avatar,
				p.custom_name,
				p.reference_image,
				p.is_public,
				p.anilist_character_id,
				c.name_full,
				c.name_native,
				c.image_large,
				c.description
			FROM user_event_cosplay_plans plan
			INNER JOIN users u ON u.id = plan.user_id
			INNER JOIN user_cosplay_portfolio p ON p.id = plan.portfolio_id
			INNER JOIN anilist_characters c ON c.id = p.anilist_character_id
			WHERE plan.event_id = :event_id
			  AND p.is_public = 1
			ORDER BY FIELD(plan.status, 'porterò', 'forse') ASC, plan.created_at DESC, plan.id DESC"
		);
		$stmt->execute([
			':event_id' => $eventId,
		]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getUserEventSelections(int $userId): array
	{
		$stmt = $this->db->prepare(
			"SELECT
				plan.id,
				plan.event_id,
				plan.portfolio_id,
				plan.status,
				plan.created_at,
				e.titolo,
				e.slug,
				e.data_inizio,
				e.data_fine,
				c.custom_name,
				c.reference_image,
				c.notes,
				ac.name_full,
				ac.name_native
			FROM user_event_cosplay_plans plan
			INNER JOIN events e ON e.id = plan.event_id
			INNER JOIN user_cosplay_portfolio c ON c.id = plan.portfolio_id
			INNER JOIN anilist_characters ac ON ac.id = c.anilist_character_id
			WHERE plan.user_id = :user_id
			ORDER BY e.data_inizio ASC, plan.created_at DESC"
		);
		$stmt->execute([':user_id' => $userId]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function savePortfolioItem(int $userId, array $data): bool
	{
		$portfolioId = (int) ($data['id'] ?? 0);
		$anilistCharacterId = (int) ($data['anilist_character_id'] ?? 0);
		$customName = trim((string) ($data['custom_name'] ?? ''));
		$notes = trim((string) ($data['notes'] ?? ''));
		$referenceImage = trim((string) ($data['reference_image'] ?? ''));
		$uploadedImage = $data['reference_image_file'] ?? null;
		$isPublic = !empty($data['is_public']) ? 1 : 0;

		if ($anilistCharacterId <= 0) {
			return false;
		}

		if (is_array($uploadedImage) && !empty($uploadedImage['tmp_name'])) {
			$uploadedPath = $this->uploadReferenceImage($uploadedImage, $userId, max(1, $portfolioId));
			if ($uploadedPath !== null) {
				$referenceImage = $uploadedPath;
			}
		}

		if ($portfolioId > 0) {
			$stmt = $this->db->prepare(
				"UPDATE user_cosplay_portfolio
				 SET anilist_character_id = :anilist_character_id,
				     custom_name = :custom_name,
				     notes = :notes,
				     reference_image = :reference_image,
				     is_public = :is_public,
				     updated_at = NOW()
				 WHERE id = :id AND user_id = :user_id"
			);
			$ok = $stmt->execute([
				':anilist_character_id' => $anilistCharacterId,
				':custom_name' => $customName !== '' ? $customName : null,
				':notes' => $notes !== '' ? $notes : null,
				':reference_image' => $referenceImage !== '' ? $referenceImage : null,
				':is_public' => $isPublic,
				':id' => $portfolioId,
				':user_id' => $userId,
			]);

			$this->auditLogService->logAudit([
				'user_id' => $userId,
				'action_type' => AuditLogActionType::COSPLAY_PORTFOLIO_UPDATED,
				'entity_type' => 'cosplay_portfolio',
				'entity_id' => $portfolioId,
				'success' => $ok ? 1 : 0,
				'payload' => [
					'action' => 'update',
					'anilist_character_id' => $anilistCharacterId,
					'is_public' => $isPublic,
				],
			]);

			return $ok;
		}

		$stmt = $this->db->prepare(
			"INSERT INTO user_cosplay_portfolio
			 (user_id, anilist_character_id, custom_name, notes, reference_image, is_public, created_at, updated_at)
			 VALUES
			 (:user_id, :anilist_character_id, :custom_name, :notes, :reference_image, :is_public, NOW(), NOW())"
		);
		$ok = $stmt->execute([
			':user_id' => $userId,
			':anilist_character_id' => $anilistCharacterId,
			':custom_name' => $customName !== '' ? $customName : null,
			':notes' => $notes !== '' ? $notes : null,
			':reference_image' => $referenceImage !== '' ? $referenceImage : null,
			':is_public' => $isPublic,
		]);

		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::COSPLAY_PORTFOLIO_CREATED,
			'entity_type' => 'cosplay_portfolio',
			'entity_id' => (int) $this->db->lastInsertId(),
			'success' => $ok ? 1 : 0,
			'payload' => [
				'action' => 'create',
				'anilist_character_id' => $anilistCharacterId,
				'is_public' => $isPublic,
			],
		]);

		return $ok;
	}

	private function convertUploadToWebp(string $source, string $dest, int $quality = 82): bool
	{
		$info = getimagesize($source);
		if (!$info) {
			return false;
		}

		switch ($info['mime']) {
			case 'image/jpeg':
				$image = imagecreatefromjpeg($source);
				break;
			case 'image/png':
				$image = imagecreatefrompng($source);
				imagepalettetotruecolor($image);
				imagesavealpha($image, true);
				break;
			case 'image/webp':
				return move_uploaded_file($source, $dest) || copy($source, $dest);
			default:
				return false;
		}

		$result = imagewebp($image, $dest, $quality);
		imagedestroy($image);

		return $result;
	}

	public function deletePortfolioItem(int $userId, int $portfolioId): bool
	{
		$stmt = $this->db->prepare(
			"DELETE FROM user_cosplay_portfolio WHERE id = :id AND user_id = :user_id"
		);
		$ok = $stmt->execute([
			':id' => $portfolioId,
			':user_id' => $userId,
		]);

		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::COSPLAY_PORTFOLIO_DELETED,
			'entity_type' => 'cosplay_portfolio',
			'entity_id' => $portfolioId,
			'success' => $ok ? 1 : 0,
			'payload' => [
				'action' => 'delete',
			],
		]);

		return $ok;
	}

	public function setPortfolioVisibility(int $userId, int $portfolioId, bool $isPublic): bool
	{
		$stmt = $this->db->prepare(
			"UPDATE user_cosplay_portfolio
			 SET is_public = :is_public, updated_at = NOW()
			 WHERE id = :id AND user_id = :user_id"
		);
		$ok = $stmt->execute([
			':is_public' => $isPublic ? 1 : 0,
			':id' => $portfolioId,
			':user_id' => $userId,
		]);

		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::COSPLAY_PORTFOLIO_VISIBILITY_UPDATED,
			'entity_type' => 'cosplay_portfolio',
			'entity_id' => $portfolioId,
			'success' => $ok ? 1 : 0,
			'payload' => [
				'is_public' => $isPublic ? 1 : 0,
			],
		]);

		return $ok;
	}

	public function saveEventSelection(int $userId, int $eventId, int $portfolioId, string $status = 'porterò'): bool
	{
		$allowed = ['porterò', 'forse', 'non porterò'];
		if (!in_array($status, $allowed, true) || $portfolioId <= 0) {
			return false;
		}

		$stmt = $this->db->prepare(
			"INSERT INTO user_event_cosplay_plans
			 (user_id, event_id, portfolio_id, status, created_at, updated_at)
			 VALUES
			 (:user_id, :event_id, :portfolio_id, :status, NOW(), NOW())
			 ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()"
		);
		$ok = $stmt->execute([
			':user_id' => $userId,
			':event_id' => $eventId,
			':portfolio_id' => $portfolioId,
			':status' => $status,
		]);

		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::COSPLAY_EVENT_SELECTION_UPDATED,
			'entity_type' => 'event',
			'entity_id' => $eventId,
			'success' => $ok ? 1 : 0,
			'payload' => [
				'action' => 'cosplay_selection',
				'portfolio_id' => $portfolioId,
				'status' => $status,
			],
		]);

		return $ok;
	}

	public function removeEventSelection(int $userId, int $eventId, ?int $portfolioId = null): bool
	{
		$sql = "DELETE FROM user_event_cosplay_plans WHERE user_id = :user_id AND event_id = :event_id";
		$params = [
			':user_id' => $userId,
			':event_id' => $eventId,
		];

		if ($portfolioId !== null && $portfolioId > 0) {
			$sql .= " AND portfolio_id = :portfolio_id";
			$params[':portfolio_id'] = $portfolioId;
		}

		$stmt = $this->db->prepare($sql);
		$ok = $stmt->execute($params);

		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::COSPLAY_EVENT_SELECTION_REMOVED,
			'entity_type' => 'event',
			'entity_id' => $eventId,
			'success' => $ok ? 1 : 0,
			'payload' => [
				'action' => 'cosplay_selection_removed',
			],
		]);

		return $ok;
	}
}
