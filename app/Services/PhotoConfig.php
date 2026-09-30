<?php
declare(strict_types=1);

namespace App\Services;

final class PhotoConfig
{
	public int $largeMaxSide;
	public int $thumbMaxSide;
	public int $largeQuality;
	public int $thumbQuality;
	public int $maxUploadBytes;
	public int $maxPixels;
	public int $uploadConcurrency;

	public function __construct()
	{
		$this->largeMaxSide = $this->intEnv('PHOTO_MAX_WIDTH', 1920);
		$this->thumbMaxSide = $this->intEnv('PHOTO_THUMB_MAX_WIDTH', 500);
		$this->largeQuality = $this->intEnv('PHOTO_WEBP_QUALITY', 82);
		$this->thumbQuality = $this->intEnv('PHOTO_THUMB_WEBP_QUALITY', 72);
		$this->maxUploadBytes = $this->intEnv('PHOTO_MAX_UPLOAD_MB', 20) * 1024 * 1024;
		$this->maxPixels = $this->intEnv('PHOTO_MAX_PIXELS', 42000000);
		$this->uploadConcurrency = $this->intEnv('PHOTO_UPLOAD_CONCURRENCY', 2);
	}

	private function intEnv(string $key, int $default): int
	{
		$value = function_exists('app_env_value') ? app_env_value($key, (string) $default) : (getenv($key) ?: (string) $default);
		return max(1, (int) $value);
	}
}

