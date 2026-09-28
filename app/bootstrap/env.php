<?php

declare(strict_types=1);

use Dotenv\Dotenv;

if (!defined('APP_ROOT')) {
	return;
}

$autoloadPath = APP_ROOT . '/vendor/autoload.php';
if (!class_exists(Dotenv::class) && file_exists($autoloadPath)) {
	require_once $autoloadPath;
}

if (class_exists(Dotenv::class)) {
	Dotenv::createImmutable(APP_ROOT)->safeLoad();
	return;
}

$envPath = APP_ROOT . '/.env';
if (!is_readable($envPath)) {
	return;
}

$values = parse_ini_file($envPath, false, INI_SCANNER_RAW);
if (!is_array($values)) {
	return;
}

foreach ($values as $key => $value) {
	if (!is_string($key) || $key === '') {
		continue;
	}

	if (array_key_exists($key, $_ENV) || array_key_exists($key, $_SERVER) || getenv($key) !== false) {
		continue;
	}

	$stringValue = trim((string) $value);
	$_ENV[$key] = $stringValue;
	$_SERVER[$key] = $stringValue;
	putenv($key . '=' . $stringValue);
}
