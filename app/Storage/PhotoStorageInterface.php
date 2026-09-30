<?php
declare(strict_types=1);

namespace App\Storage;

interface PhotoStorageInterface
{
	public function store(string $sourcePath, string $storageKey): void;
	public function delete(string $storageKey): void;
	public function exists(string $storageKey): bool;
	public function getPublicUrl(string $storageKey): string;
	public function getAbsolutePath(string $storageKey): string;
}

