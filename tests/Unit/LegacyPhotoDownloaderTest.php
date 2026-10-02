<?php
declare(strict_types=1);

use App\Services\LegacyPhotoDownloader;
use PHPUnit\Framework\TestCase;

final class LegacyPhotoDownloaderTest extends TestCase
{
	public function testRejectsNonHttpsUrl(): void
	{
		$this->expectException(InvalidArgumentException::class);
		(new LegacyPhotoDownloader())->assertAllowedUrl('http://www.italiancosplay.com/photo.jpg');
	}

	public function testRejectsUnauthorizedHost(): void
	{
		$this->expectException(InvalidArgumentException::class);
		(new LegacyPhotoDownloader())->assertAllowedUrl('https://example.com/photo.jpg');
	}

	public function testRejectsLocalhost(): void
	{
		$this->expectException(InvalidArgumentException::class);
		(new LegacyPhotoDownloader())->assertAllowedUrl('https://localhost/photo.jpg');
	}
}
