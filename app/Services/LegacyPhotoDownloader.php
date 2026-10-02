<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

final class LegacyPhotoDownloader
{
	private const ALLOWED_HOSTS = ['www.italiancosplay.com', 'italiancosplay.com'];

	public function __construct(
		private int $timeoutSeconds = 12,
		private int $maxBytes = 20971520,
	) {
	}

	public function assertAllowedUrl(string $url): void
	{
		$parts = parse_url($url);
		$scheme = strtolower((string) ($parts['scheme'] ?? ''));
		$host = strtolower((string) ($parts['host'] ?? ''));
		if ($scheme !== 'https' || !in_array($host, self::ALLOWED_HOSTS, true)) {
			throw new InvalidArgumentException('URL remoto non autorizzato.');
		}
		$ip = gethostbyname($host);
		if ($ip === $host || !$this->isPublicIp($ip)) {
			throw new InvalidArgumentException('Host remoto non sicuro.');
		}
	}

	public function download(string $url): string
	{
		if (!function_exists('curl_init')) {
			throw new RuntimeException('Estensione cURL non disponibile sul server.');
		}
		$this->assertAllowedUrl($url);
		$temp = tempnam(sys_get_temp_dir(), 'ic_legacy_photo_');
		if (!$temp) {
			throw new RuntimeException('Impossibile creare file temporaneo.');
		}
		$handle = fopen($temp, 'wb');
		if (!$handle) {
			throw new RuntimeException('Impossibile aprire file temporaneo.');
		}
		$bytes = 0;
		$locationHeader = null;
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds,
			CURLOPT_TIMEOUT => $this->timeoutSeconds,
			CURLOPT_HEADERFUNCTION => function ($curl, string $header) use (&$locationHeader): int {
				if (stripos($header, 'Location:') === 0) {
					$locationHeader = trim(substr($header, 9));
				}
				return strlen($header);
			},
			CURLOPT_WRITEFUNCTION => function ($curl, string $chunk) use ($handle, &$bytes): int {
				$bytes += strlen($chunk);
				if ($bytes > $this->maxBytes) {
					return 0;
				}
				fwrite($handle, $chunk);
				return strlen($chunk);
			},
			CURLOPT_USERAGENT => 'ItalianCosplay Legacy Photo Importer',
		]);
		$status = 0;
		$currentUrl = $url;
		for ($redirects = 0; $redirects <= 3; $redirects++) {
			$this->assertAllowedUrl($currentUrl);
			$bytes = 0;
			$locationHeader = null;
			ftruncate($handle, 0);
			rewind($handle);
			curl_setopt($ch, CURLOPT_URL, $currentUrl);
			$result = curl_exec($ch);
			$status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
			if ($result === false) {
				$error = curl_error($ch) ?: 'Download fallito.';
				curl_close($ch);
				fclose($handle);
				@unlink($temp);
				throw new RuntimeException($error);
			}
			if ($status >= 300 && $status < 400) {
				$location = $locationHeader;
				if (!$location) {
					curl_close($ch);
					fclose($handle);
					@unlink($temp);
					throw new RuntimeException('Redirect remoto senza destinazione.');
				}
				$currentUrl = $this->absoluteRedirectUrl($currentUrl, $location);
				continue;
			}
			break;
		}
		curl_close($ch);
		fclose($handle);
		if ($status < 200 || $status >= 300) {
			@unlink($temp);
			throw new RuntimeException('Download non riuscito. HTTP ' . $status);
		}
		if ($bytes <= 0 || $bytes > $this->maxBytes) {
			@unlink($temp);
			throw new RuntimeException('Dimensione immagine non valida.');
		}
		$mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $temp) ?: '';
		if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || !getimagesize($temp)) {
			@unlink($temp);
			throw new RuntimeException('Il file remoto non è una immagine valida.');
		}
		return $temp;
	}

	private function isPublicIp(string $ip): bool
	{
		return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
	}

	private function absoluteRedirectUrl(string $currentUrl, string $location): string
	{
		if (parse_url($location, PHP_URL_SCHEME)) {
			return $location;
		}
		$parts = parse_url($currentUrl);
		$scheme = (string) ($parts['scheme'] ?? 'https');
		$host = (string) ($parts['host'] ?? '');
		if (str_starts_with($location, '/')) {
			return $scheme . '://' . $host . $location;
		}
		$path = (string) ($parts['path'] ?? '/');
		return $scheme . '://' . $host . rtrim(dirname($path), '/') . '/' . ltrim($location, '/');
	}
}
