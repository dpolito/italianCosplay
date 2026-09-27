<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

echo "=== ItalianCosplay ENV TEST ===\n\n";

echo "PHP version: " . PHP_VERSION . "\n";
echo "__DIR__: " . __DIR__ . "\n\n";

/*
 * 1. Verifica presenza .env
 */
$envFile = __DIR__ . '/.env';

echo "--- FILE .env ---\n";

if (file_exists($envFile)) {
	echo "[OK] .env trovato\n";
	echo "Path: {$envFile}\n";
	echo "Readable: " . (is_readable($envFile) ? 'SI' : 'NO') . "\n";
} else {
	echo "[ERRORE] .env NON trovato\n";
}

echo "\n";

/*
 * 2. Controllo getenv()
 */
echo "--- getenv() ---\n";

$brevoKey = getenv('BREVO_API_KEY');

echo "BREVO_API_KEY: ";
echo $brevoKey !== false && $brevoKey !== ''
	? '[OK] presente (' . strlen($brevoKey) . " caratteri)\n"
	: "[NON DISPONIBILE]\n";

echo "BREVO_DAILY_TRANSACTIONAL_LIMIT: ";
var_dump(getenv('BREVO_DAILY_TRANSACTIONAL_LIMIT'));

echo "BREVO_QUOTA_CACHE_TTL: ";
var_dump(getenv('BREVO_QUOTA_CACHE_TTL'));

echo "\n";

/*
 * 3. Controllo $_ENV
 */
echo "--- \$_ENV ---\n";

echo "BREVO_API_KEY: ";
echo isset($_ENV['BREVO_API_KEY'])
	? '[OK] presente (' . strlen((string) $_ENV['BREVO_API_KEY']) . " caratteri)\n"
	: "[NON DISPONIBILE]\n";

echo "BREVO_DAILY_TRANSACTIONAL_LIMIT: ";
var_dump($_ENV['BREVO_DAILY_TRANSACTIONAL_LIMIT'] ?? null);

echo "BREVO_QUOTA_CACHE_TTL: ";
var_dump($_ENV['BREVO_QUOTA_CACHE_TTL'] ?? null);

echo "\n";

/*
 * 4. Controllo $_SERVER
 */
echo "--- \$_SERVER ---\n";

echo "BREVO_API_KEY: ";
echo isset($_SERVER['BREVO_API_KEY'])
	? '[OK] presente (' . strlen((string) $_SERVER['BREVO_API_KEY']) . " caratteri)\n"
	: "[NON DISPONIBILE]\n";

echo "BREVO_DAILY_TRANSACTIONAL_LIMIT: ";
var_dump($_SERVER['BREVO_DAILY_TRANSACTIONAL_LIMIT'] ?? null);

echo "BREVO_QUOTA_CACHE_TTL: ";
var_dump($_SERVER['BREVO_QUOTA_CACHE_TTL'] ?? null);

echo "\n";

/*
 * 5. Test parse_ini_file()
 *
 * Serve esclusivamente per capire se PHP riesce fisicamente
 * a leggere e interpretare il file .env.
 */
echo "--- parse_ini_file(.env) ---\n";

if (is_readable($envFile)) {

	$parsed = @parse_ini_file($envFile, false, INI_SCANNER_RAW);

	if ($parsed === false) {
		echo "[ERRORE] PHP non riesce a interpretare il file .env\n";
	} else {
		echo "[OK] PHP riesce a leggere il file .env\n";

		echo "BREVO_API_KEY: ";
		echo isset($parsed['BREVO_API_KEY'])
			? '[OK] presente (' . strlen((string) $parsed['BREVO_API_KEY']) . " caratteri)\n"
			: "[NON TROVATA]\n";

		echo "BREVO_DAILY_TRANSACTIONAL_LIMIT: ";
		var_dump($parsed['BREVO_DAILY_TRANSACTIONAL_LIMIT'] ?? null);

		echo "BREVO_QUOTA_CACHE_TTL: ";
		var_dump($parsed['BREVO_QUOTA_CACHE_TTL'] ?? null);
	}
}

echo "\n=== FINE TEST ===\n";
