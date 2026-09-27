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
}
