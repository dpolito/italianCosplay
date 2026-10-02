<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\PhotoRepository;
use App\Storage\LocalPhotoStorage;
use App\Storage\PhotoStorageInterface;
use App\Support\AuditLogActionType;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

final class PhotoService
{
	private PDO $db;
	private PhotoRepository $photos;
	private PhotoStorageInterface $storage;
	private PhotoConfig $config;
	private AuditLogService $auditLogService;
	private PhotoAnalyticsService $photoAnalyticsService;

	public function __construct(?PhotoRepository $photos = null, ?PhotoStorageInterface $storage = null, ?PhotoConfig $config = null)
	{
		$this->db = Database::getInstance()->getConnection();
		$this->photos = $photos ?? new PhotoRepository($this->db);
		$this->storage = $storage ?? new LocalPhotoStorage();
		$this->config = $config ?? new PhotoConfig();
		$this->auditLogService = new AuditLogService();
		$this->photoAnalyticsService = new PhotoAnalyticsService();
	}

	public function getConfig(): PhotoConfig
	{
		return $this->config;
	}

	public function upload(array $file, int $eventId, int $userId): array
	{
		$this->validateUpload($file, true);
		return $this->createFromLocalFile(
			(string) $file['tmp_name'],
			$eventId,
			$userId,
			$this->sanitizeOriginalName((string) ($file['name'] ?? 'foto')),
			'dashboard_upload'
		);
	}

	public function createFromLocalFile(string $sourcePath, int $eventId, int $userId, string $originalFilename, string $analyticsSource = 'server_import'): array
	{
		$tempLarge = null;
		$tempThumb = null;
		$storedLargeKey = null;
		$storedThumbKey = null;
		try {
			$this->assertEventExists($eventId);
			[$sourceWidth, $sourceHeight, $mime] = $this->readImageInfo($sourcePath);
			if ($sourceWidth * $sourceHeight > $this->config->maxPixels) {
				throw new InvalidArgumentException('Immagine troppo grande in pixel.');
			}

			$tempLarge = tempnam(sys_get_temp_dir(), 'ic_photo_large_');
			$tempThumb = tempnam(sys_get_temp_dir(), 'ic_photo_thumb_');
			if (!$tempLarge || !$tempThumb) {
				throw new RuntimeException('Impossibile creare file temporanei.');
			}

			if ($this->isHeicMime($mime)) {
				[$largeWidth, $largeHeight, $thumbWidth, $thumbHeight] = $this->processWithImagick($sourcePath, $tempLarge, $tempThumb);
			} else {
				$image = $this->createImageResource($sourcePath, $mime);
				$image = $this->applyJpegOrientation($image, $sourcePath, $mime);
				[$large, $largeWidth, $largeHeight] = $this->resizeResource($image, $sourceWidth, $sourceHeight, $this->config->largeMaxSide);
				[$thumb, $thumbWidth, $thumbHeight] = $this->resizeResource($image, $sourceWidth, $sourceHeight, $this->config->thumbMaxSide);
				if (!imagewebp($large, $tempLarge, $this->config->largeQuality) || !imagewebp($thumb, $tempThumb, $this->config->thumbQuality)) {
					throw new RuntimeException('Conversione WebP non riuscita.');
				}
				imagedestroy($image);
				imagedestroy($large);
				imagedestroy($thumb);
			}

			$baseKey = date('Y/m/') . $eventId . '/' . bin2hex(random_bytes(16));
			$largeKey = $baseKey . '.webp';
			$thumbKey = $baseKey . '.thumb.webp';
			$this->storage->store($tempLarge, $largeKey);
			$storedLargeKey = $largeKey;
			$this->storage->store($tempThumb, $thumbKey);
			$storedThumbKey = $thumbKey;
			$tempLarge = null;
			$tempThumb = null;

			$photoId = $this->photos->create([
				'event_id' => $eventId,
				'uploaded_by_user_id' => $userId,
				'storage_key' => $largeKey,
				'thumbnail_storage_key' => $thumbKey,
				'original_filename' => $this->sanitizeOriginalName($originalFilename),
				'width' => $largeWidth,
				'height' => $largeHeight,
				'thumbnail_width' => $thumbWidth,
				'thumbnail_height' => $thumbHeight,
				'filesize' => filesize($this->storage->getAbsolutePath($largeKey)) ?: 0,
				'status' => 'published',
			]);
			$storedLargeKey = null;
			$storedThumbKey = null;

			$this->auditLogService->logAudit([
				'user_id' => $userId,
				'action_type' => AuditLogActionType::PHOTO_UPLOADED,
				'entity_type' => 'photo',
				'entity_id' => $photoId,
				'payload' => ['event_id' => $eventId],
			]);
			$this->photoAnalyticsService->track('photo_upload_success', [
				'photo_id' => $photoId,
				'event_id' => $eventId,
				'uploaded_by_user_id' => $userId,
				'source' => $analyticsSource,
			], false);

			return [
				'id' => $photoId,
				'thumbnail_url' => $this->storage->getPublicUrl($thumbKey),
				'url' => $this->storage->getPublicUrl($largeKey),
				'width' => $largeWidth,
				'height' => $largeHeight,
			];
		} catch (Throwable $exception) {
			if ($tempLarge && is_file($tempLarge)) unlink($tempLarge);
			if ($tempThumb && is_file($tempThumb)) unlink($tempThumb);
			if ($storedLargeKey !== null) {
				$this->storage->delete($storedLargeKey);
			}
			if ($storedThumbKey !== null) {
				$this->storage->delete($storedThumbKey);
			}
			$this->auditLogService->logAudit([
				'user_id' => $userId,
				'action_type' => AuditLogActionType::PHOTO_UPLOADED,
				'entity_type' => 'photo',
				'entity_id' => null,
				'success' => 0,
				'error_message' => mb_substr($exception->getMessage(), 0, 250),
				'payload' => ['event_id' => $eventId],
			]);
			throw $exception;
		}
	}

	public function decorate(array $photo): array
	{
		$photo['url'] = $this->storage->getPublicUrl((string) $photo['storage_key']);
		$photo['thumbnail_url'] = $this->storage->getPublicUrl((string) $photo['thumbnail_storage_key']);
		return $photo;
	}

	public function deleteOwned(int $photoId, int $userId): void
	{
		$photo = $this->photos->deleteOwned($photoId, $userId);
		if (!$photo) {
			throw new InvalidArgumentException('Foto non trovata o non autorizzata.');
		}
		$this->storage->delete((string) $photo['storage_key']);
		$this->storage->delete((string) $photo['thumbnail_storage_key']);
		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::PHOTO_DELETED,
			'entity_type' => 'photo',
			'entity_id' => $photoId,
			'payload' => ['event_id' => (int) $photo['event_id']],
		]);
	}

	public function addAssociation(array $photoIds, int $actorUserId, ?int $userId, ?int $cosplayId, ?string $displayName, ?string $instagramUsername, bool $pending = false): int
	{
		$count = 0;
		foreach (array_unique(array_filter(array_map('intval', $photoIds))) as $photoId) {
			$isOwner = (bool) $this->photos->findForOwner($photoId, $actorUserId);
			$isSelfClaim = $pending && $userId === $actorUserId && $this->photos->findPublished($photoId);
			if (!$isOwner && !$isSelfClaim) {
				continue;
			}
			$status = $pending ? 'pending' : 'confirmed';
			if ($userId !== null && $userId !== $actorUserId) {
				$status = 'pending';
			}
			if ($this->photos->addCosplayer($photoId, $userId, $cosplayId, $displayName, $this->normalizeInstagram($instagramUsername), $actorUserId, $status)) {
				$count++;
			}
		}
		$this->auditLogService->logAudit([
			'user_id' => $actorUserId,
			'action_type' => AuditLogActionType::PHOTO_COSPLAYER_ASSOCIATED,
			'entity_type' => 'photo_cosplayer',
			'payload' => ['photo_count' => $count, 'associated_user_id' => $userId, 'cosplay_id' => $cosplayId, 'pending' => $pending],
		]);
		return $count;
	}

	public function removeAssociation(int $associationId, int $ownerUserId): void
	{
		if ($associationId <= 0 || !$this->photos->deleteAssociationForOwner($associationId, $ownerUserId)) {
			throw new InvalidArgumentException('Associazione non trovata o non autorizzata.');
		}

		$this->auditLogService->logAudit([
			'user_id' => $ownerUserId,
			'action_type' => AuditLogActionType::PHOTO_COSPLAYER_REMOVED,
			'entity_type' => 'photo_cosplayer',
			'entity_id' => $associationId,
		]);
	}

	public function report(int $photoId, ?int $userId, string $reason, string $message): int
	{
		$photo = $this->photos->findPublished($photoId);
		if (!$photo) {
			throw new InvalidArgumentException('Foto non trovata.');
		}
		$reason = mb_substr(trim($reason), 0, 80);
		if ($reason === '') {
			$reason = 'segnalazione';
		}
		$message = mb_substr(trim($message), 0, 500);
		$reportId = $this->photos->report($photoId, $userId, $reason, $message);
		$this->auditLogService->logAudit([
			'user_id' => $userId,
			'action_type' => AuditLogActionType::PHOTO_REPORTED,
			'entity_type' => 'photo',
			'entity_id' => $photoId,
			'payload' => ['reason' => $reason, 'report_id' => $reportId],
		]);
		$this->notifyPhotoReport($reportId, $photo, $userId, $reason, $message);

		return $reportId;
	}

	private function notifyPhotoReport(int $reportId, array $photo, ?int $userId, string $reason, string $message): void
	{
		$botToken = defined('TELEGRAM_BOT_TOKEN') ? (string) TELEGRAM_BOT_TOKEN : '';
		$chatId = defined('TELEGRAM_CHAT_ID') ? (string) TELEGRAM_CHAT_ID : '';
		if (trim($botToken) === '' || trim($chatId) === '') {
			return;
		}

		$photoUrl = (defined('URL_ROOT_SITE') ? rtrim((string) URL_ROOT_SITE, '/') : '') . '/eventi-cosplay/' . rawurlencode((string) ($photo['event_slug'] ?? '')) . '/foto/' . (int) $photo['id'];
		$adminUrl = (defined('URL_ROOT_SITE') ? rtrim((string) URL_ROOT_SITE, '/') : '') . '/admin/photos/reports';
		$text = implode("\n", array_filter([
			'<b>Nuova segnalazione foto</b>',
			'Report #' . $reportId . ' - Foto #' . (int) $photo['id'],
			'Evento: ' . htmlspecialchars((string) ($photo['event_title'] ?? 'N/D'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
			'Motivo: ' . htmlspecialchars($reason, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
			$message !== '' ? 'Messaggio: ' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '',
			$userId !== null ? 'Utente segnalante: #' . $userId : 'Utente segnalante: anonimo',
			'Foto: ' . htmlspecialchars($photoUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
			'Admin: ' . htmlspecialchars($adminUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
		]));

		$result = (new TelegramNotificationService($botToken, $chatId))->sendMessage($text, [
			'parse_mode' => 'HTML',
			'disable_web_page_preview' => true,
		]);

		if (($result['success'] ?? false) !== true) {
			error_log('Photo report Telegram notification failed: ' . (string) ($result['error'] ?? 'Unknown error'));
		}
	}

	private function validateUpload(array $file, bool $requireUploadedFile = true): void
	{
		$error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
		if ($error !== UPLOAD_ERR_OK) {
			throw new InvalidArgumentException($this->uploadErrorMessage($error));
		}
		if (empty($file['tmp_name']) || !is_file((string) $file['tmp_name']) || ($requireUploadedFile && !is_uploaded_file((string) $file['tmp_name']))) {
			throw new InvalidArgumentException('File temporaneo non disponibile. Riprova selezionando nuovamente la foto.');
		}
		if (($file['size'] ?? 0) <= 0 || (int) $file['size'] > $this->config->maxUploadBytes) {
			throw new InvalidArgumentException('File troppo grande.');
		}
	}

	private function uploadErrorMessage(int $error): string
	{
		return match ($error) {
			UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File troppo grande per la configurazione PHP.',
			UPLOAD_ERR_PARTIAL => 'Upload incompleto. Riprova con questa singola foto.',
			UPLOAD_ERR_NO_FILE => 'Nessun file ricevuto dal server.',
			UPLOAD_ERR_NO_TMP_DIR => 'Directory temporanea PHP non disponibile.',
			UPLOAD_ERR_CANT_WRITE => 'Il server non riesce a scrivere il file temporaneo.',
			UPLOAD_ERR_EXTENSION => 'Upload bloccato da una estensione PHP.',
			default => 'Upload non valido. Codice errore PHP: ' . $error,
		};
	}

	private function readImageInfo(string $path): array
	{
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($finfo, $path) ?: '';
		finfo_close($finfo);
		$allowed = ['image/jpeg', 'image/png', 'image/webp'];
		if ($this->isHeicMime($mime) && $this->imagickSupportsHeic()) {
			$image = new \Imagick($path);
			return [$image->getImageWidth(), $image->getImageHeight(), $mime];
		}
		if (!in_array($mime, $allowed, true)) {
			throw new InvalidArgumentException('Formato immagine non supportato.');
		}
		$size = getimagesize($path);
		if (!$size || empty($size[0]) || empty($size[1])) {
			throw new InvalidArgumentException('Immagine non leggibile.');
		}
		return [(int) $size[0], (int) $size[1], $mime];
	}

	private function createImageResource(string $path, string $mime): \GdImage
	{
		$image = match ($mime) {
			'image/jpeg' => imagecreatefromjpeg($path),
			'image/png' => imagecreatefrompng($path),
			'image/webp' => imagecreatefromwebp($path),
			default => false,
		};
		if (!$image instanceof \GdImage) {
			throw new RuntimeException('Impossibile elaborare immagine.');
		}
		imagepalettetotruecolor($image);
		imagealphablending($image, true);
		imagesavealpha($image, true);
		return $image;
	}

	private function processWithImagick(string $sourcePath, string $largePath, string $thumbPath): array
	{
		if (!$this->imagickSupportsHeic()) {
			throw new InvalidArgumentException('HEIC/HEIF non supportato da questo server.');
		}

		$image = new \Imagick($sourcePath);
		if (method_exists($image, 'autoOrient')) {
			$image->autoOrient();
		} else {
			$image->autoOrientImage();
		}
		$image->stripImage();

		$large = clone $image;
		$this->resizeImagick($large, $this->config->largeMaxSide);
		$large->setImageFormat('webp');
		$large->setImageCompressionQuality($this->config->largeQuality);
		$large->writeImage($largePath);
		$largeWidth = $large->getImageWidth();
		$largeHeight = $large->getImageHeight();

		$thumb = clone $image;
		$this->resizeImagick($thumb, $this->config->thumbMaxSide);
		$thumb->setImageFormat('webp');
		$thumb->setImageCompressionQuality($this->config->thumbQuality);
		$thumb->writeImage($thumbPath);
		$thumbWidth = $thumb->getImageWidth();
		$thumbHeight = $thumb->getImageHeight();

		$image->clear();
		$large->clear();
		$thumb->clear();

		return [$largeWidth, $largeHeight, $thumbWidth, $thumbHeight];
	}

	private function resizeImagick(\Imagick $image, int $maxSide): void
	{
		$width = $image->getImageWidth();
		$height = $image->getImageHeight();
		if (max($width, $height) <= $maxSide) {
			return;
		}
		$image->thumbnailImage($maxSide, $maxSide, true, true);
	}

	private function applyJpegOrientation(\GdImage $image, string $path, string $mime): \GdImage
	{
		if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
			return $image;
		}
		$exif = @exif_read_data($path);
		$orientation = (int) ($exif['Orientation'] ?? 1);
		return match ($orientation) {
			3 => imagerotate($image, 180, 0),
			6 => imagerotate($image, -90, 0),
			8 => imagerotate($image, 90, 0),
			default => $image,
		};
	}

	private function resizeResource(\GdImage $source, int $sourceWidth, int $sourceHeight, int $maxSide): array
	{
		$width = imagesx($source) ?: $sourceWidth;
		$height = imagesy($source) ?: $sourceHeight;
		$ratio = min(1, $maxSide / max($width, $height));
		$newWidth = max(1, (int) round($width * $ratio));
		$newHeight = max(1, (int) round($height * $ratio));
		$target = imagecreatetruecolor($newWidth, $newHeight);
		imagealphablending($target, false);
		imagesavealpha($target, true);
		imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
		return [$target, $newWidth, $newHeight];
	}

	private function assertEventExists(int $eventId): void
	{
		$stmt = $this->db->prepare("SELECT 1 FROM events WHERE id = :id AND approvato = 1 AND deleted_at IS NULL LIMIT 1");
		$stmt->execute([':id' => $eventId]);
		if (!$stmt->fetchColumn()) {
			throw new InvalidArgumentException('Evento non valido.');
		}
	}

	private function sanitizeOriginalName(string $name): string
	{
		$name = preg_replace('/[^\pL\pN._ -]+/u', '', $name) ?: 'foto';
		return mb_substr($name, 0, 255);
	}

	private function normalizeInstagram(?string $username): ?string
	{
		$username = trim((string) $username);
		$username = ltrim($username, '@');
		return $username !== '' ? mb_substr($username, 0, 60) : null;
	}

	private function isHeicMime(string $mime): bool
	{
		return in_array($mime, ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'], true);
	}

	private function imagickSupportsHeic(): bool
	{
		if (!class_exists(\Imagick::class)) {
			return false;
		}
		$formats = array_map('strtoupper', \Imagick::queryFormats());
		return in_array('HEIC', $formats, true) || in_array('HEIF', $formats, true);
	}
}
