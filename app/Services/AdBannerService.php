<?php

namespace App\Services;

use App\Models\AdBanner;
use App\Repositories\AdBannerRepository;
use Exception;

class AdBannerService
{
	private AdBannerRepository $bannerRepository;

	public function __construct()
	{
		$this->bannerRepository = new AdBannerRepository();
	}

	/**
	 * LISTA BANNER UTENTE
	 */
	public function getByUser(int $userId): array
	{
		return $this->bannerRepository->getByUser($userId);
	}

	/**
	 * FIND BY ID
	 */
	public function findById(int $id): ?array
	{
		return $this->bannerRepository->findById($id);
	}

	/**
	 * CREATE BANNER
	 */
	public function create(array $data): int
	{
		$this->validateBaseData($data);

		$imagePath = $this->handleImageUpload($data['image']);

		if (!$imagePath) {
			throw new Exception("Errore upload immagine banner.");
		}

		$banner = AdBanner::fromArray([
			'user_id'    => $data['user_id'],
			'title'      => $data['title'],
			'target_url' => $data['target_url'],
			'type'       => $data['type'] ?? 'sponsor',
			'image_path' => $imagePath
		]);

		return $this->bannerRepository->create($banner);
	}

	/**
	 * UPDATE BANNER
	 */
	public function update(int $id, array $data): bool
	{
		$banner = $this->bannerRepository->findById($id);

		if (!$banner) {
			throw new Exception("Banner non trovato.");
		}

		$this->validateBaseData($data, false);

		$imagePath = null;

		if (!empty($data['image'])) {
			$imagePath = $this->handleImageUpload($data['image']);
		}

		$updateData = [
			'title'      => $data['title'],
			'target_url' => $data['target_url'],
			'type'       => $data['type'] ?? 'sponsor'
		];

		if ($imagePath) {
			$updateData['image_path'] = $imagePath;
		}

		return $this->bannerRepository->update($id, $updateData);
	}

	/**
	 * DELETE LOGICO
	 */
	public function delete(int $id): bool
	{
		return $this->bannerRepository->delete($id);
	}

	/**
	 * VALIDAZIONE BASE
	 */
	private function validateBaseData(array $data, bool $requireImage = true): void
	{
		if (empty($data['title'])) {
			throw new Exception("Titolo obbligatorio.");
		}

		if (empty($data['target_url']) || !filter_var($data['target_url'], FILTER_VALIDATE_URL)) {
			throw new Exception("URL non valido.");
		}

		if ($requireImage && empty($data['image'])) {
			throw new Exception("Immagine obbligatoria.");
		}
	}

	/**
	 * UPLOAD IMMAGINE BANNER
	 */
	private function handleImageUpload(array $file): ?string
	{
		if ($file['error'] !== UPLOAD_ERR_OK) {
			return null;
		}

		$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
		$maxSize = 5 * 1024 * 1024;

		if (!in_array($file['type'], $allowedTypes)) {
			throw new Exception("Formato immagine non valido.");
		}

		if ($file['size'] > $maxSize) {
			throw new Exception("Immagine troppo grande (max 5MB).");
		}

		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$base = uniqid('banner_');

		$original = $base . '.' . $ext;
		$webp     = $base . '.webp';

		$uploadDir = APP_ROOT . '/public_assets/uploads/banners/';

		$originalPath = $uploadDir . $original;
		$webpPath     = $uploadDir . $webp;

		if (!move_uploaded_file($file['tmp_name'], $originalPath)) {
			throw new Exception("Errore upload file.");
		}

		$this->convertToWebP($originalPath, $webpPath);

		return '/public_assets/uploads/banners/' . $webp;
	}

	/**
	 * CONVERSIONE WEBP
	 */
	private function convertToWebP(string $source, string $destination, int $quality = 80): bool
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
				imagealphablending($image, true);
				imagesavealpha($image, true);
				break;

			case 'image/webp':
				copy($source, $destination);
				return true;

			default:
				return false;
		}

		$result = imagewebp($image, $destination, $quality);
		imagedestroy($image);

		return $result;
	}
}
