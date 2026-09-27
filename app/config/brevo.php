<?php

declare(strict_types=1);

return [
	'api_key' => trim((string) ($_ENV['BREVO_API_KEY'] ?? '')),
	'daily_transactional_limit' => (int) ($_ENV['BREVO_DAILY_TRANSACTIONAL_LIMIT'] ?? 300),
	'cache_ttl_seconds' => (int) ($_ENV['BREVO_QUOTA_CACHE_TTL'] ?? 300),
	'webhook_secret' => trim((string) ($_ENV['BREVO_WEBHOOK_SECRET'] ?? '')),
];
