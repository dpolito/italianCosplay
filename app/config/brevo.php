<?php

declare(strict_types=1);

return [
	'api_key' => getenv('BREVO_API_KEY') ?: '',
	'daily_transactional_limit' => (int) (getenv('BREVO_DAILY_TRANSACTIONAL_LIMIT') ?: 300),
	'cache_ttl_seconds' => (int) (getenv('BREVO_QUOTA_CACHE_TTL') ?: 300),
	'webhook_secret' => getenv('BREVO_WEBHOOK_SECRET') ?: '',
];
