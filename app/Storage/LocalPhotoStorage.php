<?php
declare(strict_types=1);

namespace App\Storage;

use RuntimeException;

final class LocalPhotoStorage implements PhotoStorageInterface
{
	private string $basePath;
	private string $publicPrefix;

	public function __construct(?string $basePath = null, string $publicPrefix = '/public_assets/uploads/photos')
	{
		$this->basePath = rtrim($basePath ?? APP_ROOT . '/public_assets/uploads/photos', '/');
		$this->publicPrefix = rtrim($publicPrefix, '/');
	}

	public function store(string $sourcePath, string $storageKey): void
	{
		$target = $this->getAbsolutePath($storageKey);
		$directory = dirname($target);
		if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
			throw new RuntimeException('Impossibile creare la directory foto.');
		}
		if (!rename($sourcePath, $target)) {
			throw new RuntimeException('Impossibile salvare la foto elaborata.');
		}
	}

	public function delete(string $storageKey): void
	{
		$path = $this->getAbsolutePath($storageKey);
		if (is_file($path)) {
			unlink($path);
		}
	}

	public function exists(string $storageKey): bool
	{
		return is_file($this->getAbsolutePath($storageKey));
	}

	public function getPublicUrl(string $storageKey): string
	{
		return $this->publicPrefix . '/' . ltrim($this->cleanKey($storageKey), '/');
	}

	public function getAbsolutePath(string $storageKey): string
	{
		return $this->basePath . '/' . $this->cleanKey($storageKey);
	}

	private function cleanKey(string $storageKey): string
	{
		$key = ltrim(str_replace('\\', '/', $storageKey), '/');
		if ($key === '' || str_contains($key, '..')) {
			throw new RuntimeException('Chiave storage non valida.');
		}
		return $key;
	}
}

