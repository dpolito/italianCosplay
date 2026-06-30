<?php

namespace App\Services;

use Exception;
use PDO;
use PDOException;
use function file_exists;
use function filesize;
use function finfo_file;
use function finfo_open;
use function getimagesize;
use function in_array;
use function time;
use function unlink;
use function var_dump;
use const FILEINFO_MIME_TYPE;
use const UPLOAD_ERR_OK;

class ImageService
{
	private PDO $db;
	private string $basePath;

	private array $variants = [
		'thumb'  => 300,
		'medium' => 800,
		'large'  => 1600,
	];

	public function __construct(PDO $db)
	{
		$this->db = $db;
		$this->basePath = APP_ROOT . '/public_assets/uploads/';
	}

	/**
	 * UPLOAD GENERICO (event / blog_post / future entities)
	 */
	public function upload(
		array $file,
		string $entityType,
		int $entityId,
		string $altText,
		bool $isPrimary = false
	): ?int {
		if ($file['error'] !== UPLOAD_ERR_OK) {
			return null;
		}

		$mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']);

		$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

		if (!in_array($mime, $allowed)) return null;

		if ($file['size'] > 5 * 1024 * 1024) return null;

		$this->createFolders($entityType);

		$baseName = $entityType . '-' . $entityId . '-' . time();

		$originalPath = $this->basePath . $entityType . '/original/' . $baseName . '.webp';

		if (!$this->convertToWebP($file['tmp_name'], $originalPath)) {
			return null;
		}

		[$width, $height] = getimagesize($originalPath);
		$fileSize = filesize($originalPath);

		// 🔹 ORIGINAL
		$imageId = $this->insertImageRow(
			$entityType,
			$entityId,
			$file['name'],
			$altText,
			'original',
			$baseName . '.webp',
			'/uploads/' . $entityType . '/original/' . $baseName . '.webp',
			$width,
			$height,
			$fileSize,
			$isPrimary
		);

		// 🔹 VARIANTS
		$this->generateVariants(
			$imageId,
			$entityType,
			$entityId,
			$originalPath,
			$baseName,
			$altText
		);

		return $imageId;
	}

	public function deleteEntityImages(
		string $entityType,
		int $entityId
	): void {

		$stmt = $this->db->prepare("
        SELECT path
        FROM entity_images
        WHERE entity_type = :type
        AND entity_id = :id
    ");

		$stmt->execute([
			'type' => $entityType,
			'id' => $entityId
		]);

		$files = $stmt->fetchAll(PDO::FETCH_COLUMN);

		foreach ($files as $file) {

			$full = APP_ROOT . $file;

			if (file_exists($full)) {
				unlink($full);
			}
		}

		$stmt = $this->db->prepare("
        DELETE
        FROM entity_images
        WHERE entity_type = :type
        AND entity_id = :id
    ");

		$stmt->execute([
			'type' => $entityType,
			'id' => $entityId
		]);
	}

	public function replacePrimary(
		array $file,
		string $entityType,
		int $entityId,
		string $altText
	): ?int {

		$this->deleteEntityImages(
			$entityType,
			$entityId
		);

		return $this->upload(
			$file,
			$entityType,
			$entityId,
			$altText,
			true
		);
	}

	/**
	 * VARIANT GENERATION
	 */
	private function generateVariants(
		int $imageId,
		string $entityType,
		int $entityId,
		string $source,
		string $baseName,
		?string $altText
	): void {

		foreach ($this->variants as $preset => $width) {

			$dest = $this->basePath . $entityType . '/' . $preset . '/' . $baseName . '.webp';

			$this->resize($source, $dest, $width);

			[$w, $h] = getimagesize($dest);

			$this->insertImageRow(
				$entityType,
				$entityId,
				$baseName . '.webp',
				$altText,
				$preset,
				$baseName . '.webp',
				'/uploads/' . $entityType . '/' . $preset . '/' . $baseName . '.webp',
				$w,
				$h,
				filesize($dest),
				true
			);
		}
	}

	/**
	 * INSERT ROW (UNICA TABELLA entity_images)
	 */
	private function insertImageRow(
		string $entityType,
		int $entityId,
		string $fileName,
		?string $altText,
		string $preset,
		string $storedName,
		string $path,
		int $width,
		int $height,
		int $size,
		bool $isPrimary
	): int {

		$stmt = $this->db->prepare("
            INSERT INTO entity_images (
                entity_type,
                entity_id,
                file_name,
                path,
                alt_text,
                preset,
                width,
                height,
                file_size,
                is_primary
            ) VALUES (
                :entity_type,
                :entity_id,
                :file_name,
                :path,
                :alt_text,
                :preset,
                :width,
                :height,
                :file_size,
                :is_primary
            )
        ");


		$stmt->execute([
			'entity_type' => $entityType,
			'entity_id' => $entityId,
			'file_name' => $fileName,
			'path' => $path,
			'alt_text' => $altText,
			'preset' => $preset,
			'width' => $width,
			'height' => $height,
			'file_size' => $size,
			'is_primary' => $isPrimary ? 1 : 0
		]);


		return (int)$this->db->lastInsertId();
	}

	/**
	 * DELETE IMMAGINE (tutte le varianti)
	 */
	public function deleteByEntityImageId(int $imageId): void
	{
		$stmt = $this->db->prepare("
            SELECT path 
            FROM entity_images 
            WHERE id = :id OR (entity_type, entity_id) = (
                SELECT entity_type, entity_id FROM entity_images WHERE id = :id
            )
        ");

		$stmt->execute(['id' => $imageId]);
		$files = $stmt->fetchAll(PDO::FETCH_COLUMN);

		foreach ($files as $file) {
			$full = APP_ROOT . $file;
			if (file_exists($full)) {
				unlink($full);
			}
		}

		$this->db->prepare("
            DELETE FROM entity_images 
            WHERE id = :id
            OR (entity_type, entity_id) = (
                SELECT entity_type, entity_id FROM entity_images WHERE id = :id
            )
        ")->execute(['id' => $imageId]);
	}

	/**
	 * GET COVER / PRIMARY
	 */
	public function getPrimary(string $entityType, int $entityId, string $preset = 'medium'): ?array
	{
		$stmt = $this->db->prepare("
            SELECT *
            FROM entity_images
            WHERE entity_type = :type
              AND entity_id = :id
              AND preset = :preset
            ORDER BY is_primary DESC, id ASC
            LIMIT 1
        ");

		$stmt->execute([
			'type' => $entityType,
			'id' => $entityId,
			'preset' => $preset
		]);

		return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
	}

	/**
	 * GET GALLERY
	 */
	public function getGallery(string $entityType, int $entityId, string $preset = 'medium'): array
	{
		$stmt = $this->db->prepare("
            SELECT *
            FROM entity_images
            WHERE entity_type = :type
              AND entity_id = :id
              AND preset = :preset
            ORDER BY is_primary DESC, id ASC
        ");

		$stmt->execute([
			'type' => $entityType,
			'id' => $entityId,
			'preset' => $preset
		]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * RESIZE
	 */
	private function resize(string $source, string $dest, int $maxWidth, int $quality = 82): bool
	{
		[$width, $height] = getimagesize($source);

		if ($width <= $maxWidth) {
			return copy($source, $dest);
		}

		$ratio = $height / $width;

		$newWidth = $maxWidth;
		$newHeight = (int)($newWidth * $ratio);

		$src = imagecreatefromwebp($source);
		$dst = imagecreatetruecolor($newWidth, $newHeight);

		imagealphablending($dst, true);
		imagesavealpha($dst, true);

		imagecopyresampled(
			$dst,
			$src,
			0, 0, 0, 0,
			$newWidth, $newHeight,
			$width, $height
		);

		$result = imagewebp($dst, $dest, $quality);

		imagedestroy($src);
		imagedestroy($dst);

		return $result;
	}

	/**
	 * CONVERT TO WEBP
	 */
	private function convertToWebP(string $source, string $dest, int $quality = 82): bool
	{
		$info = getimagesize($source);
		if (!$info) return false;

		switch ($info['mime']) {
			case 'image/jpeg':
				$img = imagecreatefromjpeg($source);
				break;

			case 'image/png':
				$img = imagecreatefrompng($source);
				imagepalettetotruecolor($img);
				imagesavealpha($img, true);
				break;

			case 'image/gif':
				$img = imagecreatefromgif($source);
				break;

			case 'image/webp':
				return copy($source, $dest);

			default:
				return false;
		}

		$result = imagewebp($img, $dest, $quality);
		imagedestroy($img);

		return $result;
	}

	/**
	 * FOLDERS PER ENTITY
	 */
	private function createFolders(string $entityType): void
	{
		foreach (['original', 'thumb', 'medium', 'large'] as $dir) {

			$path = $this->basePath . $entityType . '/' . $dir;

			if (!is_dir($path)) {
				mkdir($path, 0755, true);
			}
		}
	}
}
