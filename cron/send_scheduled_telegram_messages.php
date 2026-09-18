<?php

declare(strict_types=1);

use App\Core\Database;
use App\Services\TelegramChannelMessageService;

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/app/config/app.php';

if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
	require_once APP_ROOT . '/vendor/autoload.php';
}

$dbConfig = require APP_ROOT . '/app/config/database.php';
Database::getInstance($dbConfig);

$limit = isset($argv[1]) ? (int) $argv[1] : 10;
$result = (new TelegramChannelMessageService())->processDueMessages($limit);

echo sprintf(
	"OK - telegram scheduled messages: processed=%d sent=%d failed=%d\n",
	(int) $result['processed'],
	(int) $result['sent'],
	(int) $result['failed']
);
