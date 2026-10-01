<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\ApiRequestLogRepository;

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/app/bootstrap/env.php';
require_once APP_ROOT . '/app/config/app.php';

$dbConfig = require APP_ROOT . '/app/config/database.php';
Database::getInstance($dbConfig);

$config = require APP_ROOT . '/app/config/api_management.php';
$days = (int) ($config['security']['log_retention_days'] ?? 90);
$deleted = (new ApiRequestLogRepository())->purgeOlderThan($days);

echo sprintf("[%s] Deleted %d API request logs older than %d days.\n", date('c'), $deleted, $days);
