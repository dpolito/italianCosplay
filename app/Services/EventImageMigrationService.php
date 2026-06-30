<?php

namespace App\Services;

use PDO;

class EventImageMigrationService
{
	private PDO $db;
	private ImageService $imageService;

	private array $variants = [
		'thumb'  => 300,
		'medium' => 800,
		'large'  => 1600,
	];

	public function __construct(PDO $db)
	{
		$this->db = $db;
		$this->imageService = new ImageService($db);
	}

	/**
	 * Migra tutte le immagini presenti nella colonna events.immagine
	 */
	public function migrate(): void
	{
		$sql = "
            SELECT
                id,
                titolo,
                immagine
            FROM events
            WHERE immagine IS NOT NULL
            AND immagine != ''
        ";

		$stmt = $this->db->query($sql);

		$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if (!$events) {
			echo "Nessun evento trovato\n";
			return;
		}

		foreach ($events as $event) {

			echo "\n";
			echo "====================================\n";
			echo "Evento #{$event['id']}\n";
			echo "Titolo: {$event['titolo']}\n";

			$this->migrateEvent($event);
		}
	}

	/**
	 * Migra singolo evento
	 */
	private function migrateEvent(array $event): void
	{
		$absolutePath = APP_ROOT . $event['immagine'];

		if (!file_exists($absolutePath)) {

			echo "❌ File non trovato: {$absolutePath}\n";

			return;
		}

		// verifica se già migrato
		$check = $this->db->prepare("
            SELECT id
            FROM event_images
            WHERE event_id = :event_id
            LIMIT 1
        ");

		$check->execute([
			'event_id' => $event['id']
		]);

		if ($check->fetch()) {

			echo "⚠️ Evento già migrato\n";

			return;
		}

		$this->imageService->createFoldersForMigration();

		$slug = $this->slug($event['titolo']);

		$baseName = $slug . '-' . time();

		$uploadBase = APP_ROOT . '/public_assets/uploads/events/';

		$originalDestination =
			$uploadBase .
			'original/' .
			$baseName .
			'.webp';

		// converte in webp
		$converted = $this->imageService->convertExistingToWebP(
			$absolutePath,
			$originalDestination
		);

		if (!$converted) {

			echo "❌ Conversione WebP fallita\n";

			return;
		}

		[$width, $height] = getimagesize($originalDestination);

		$fileSize = filesize($originalDestination);

		// INSERT images
		$stmt = $this->db->prepare("
            INSERT INTO images (
                original_name,
                alt_text
            )
            VALUES (
                :original_name,
                :alt_text
            )
        ");

		$stmt->execute([
			'original_name' => basename($absolutePath),
			'alt_text' => $event['titolo'] . ' locandina ufficiale'
		]);

		$imageId = (int)$this->db->lastInsertId();

		// salva original
		$this->insertVariant(
			$imageId,
			'original',
			$baseName . '.webp',
			'/public_assets/uploads/events/original/' . $baseName . '.webp',
			$width,
			$height,
			$fileSize
		);

		// genera varianti
		foreach ($this->variants as $preset => $maxWidth) {

			$destination =
				$uploadBase .
				$preset .
				'/' .
				$baseName .
				'.webp';

			$resize = $this->imageService->resizeForMigration(
				$originalDestination,
				$destination,
				$maxWidth
			);

			if (!$resize) {

				echo "❌ Resize fallito: {$preset}\n";

				continue;
			}

			[$w, $h] = getimagesize($destination);

			$this->insertVariant(
				$imageId,
				$preset,
				$baseName . '.webp',
				'/public_assets/uploads/events/' . $preset . '/' . $baseName . '.webp',
				$w,
				$h,
				filesize($destination)
			);
		}

		// collega evento
		$stmt = $this->db->prepare("
            INSERT INTO event_images (
                event_id,
                image_id,
                type,
                is_primary
            )
            VALUES (
                :event_id,
                :image_id,
                'poster',
                1
            )
        ");

		$stmt->execute([
			'event_id' => $event['id'],
			'image_id' => $imageId
		]);

		echo "✅ Migrazione completata\n";
	}

	/**
	 * Inserisce variante
	 */
	private function insertVariant(
		int $imageId,
		string $preset,
		string $fileName,
		string $path,
		int $width,
		int $height,
		int $fileSize
	): void {

		$stmt = $this->db->prepare("
            INSERT INTO image_variants (
                image_id,
                preset,
                file_name,
                path,
                mime_type,
                extension,
                width,
                height,
                file_size
            )
            VALUES (
                :image_id,
                :preset,
                :file_name,
                :path,
                'image/webp',
                'webp',
                :width,
                :height,
                :file_size
            )
        ");

		$stmt->execute([
			'image_id' => $imageId,
			'preset' => $preset,
			'file_name' => $fileName,
			'path' => $path,
			'width' => $width,
			'height' => $height,
			'file_size' => $fileSize
		]);
	}

	/**
	 * Slug SEO
	 */
	private function slug(string $text): string
	{
		$text = strtolower($text);

		$text = iconv(
			'UTF-8',
			'ASCII//TRANSLIT',
			$text
		);

		$text = preg_replace(
			'/[^a-z0-9]+/',
			'-',
			$text
		);

		return trim($text, '-');
	}
}
