<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Services\PhotoUploadSessionService;

if (!defined('APP_ROOT')) {
	define('APP_ROOT', dirname(__DIR__));
}

require_once APP_ROOT . '/app/bootstrap/env.php';
Database::getInstance(require APP_ROOT . '/app/config/database.php');

$service = new PhotoUploadSessionService();
$count = $service->cleanupExpired();

echo 'Expired photo upload sessions cleaned: ' . $count . PHP_EOL;
