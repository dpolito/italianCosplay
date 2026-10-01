<?php
declare(strict_types=1);

namespace App\Services;

final class ApiKeyService
{
	private const PREFIX_LENGTH = 16;

	public function generate(string $environment): array
	{
		$environment = $environment === 'test' ? 'test' : 'live';
		$secret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
		$key = 'ic_' . $environment . '_' . $secret;

		return [
			'plain_key' => $key,
			'prefix' => $this->prefix($key),
			'hash' => password_hash($key, PASSWORD_DEFAULT),
		];
	}

	public function prefix(string $key): string
	{
		return mb_substr($key, 0, self::PREFIX_LENGTH);
	}

	public function environmentFromKey(string $key): ?string
	{
		if (str_starts_with($key, 'ic_live_')) {
			return 'live';
		}
		if (str_starts_with($key, 'ic_test_')) {
			return 'test';
		}

		return null;
	}

	public function verify(string $key, string $hash): bool
	{
		return password_verify($key, $hash);
	}
}
