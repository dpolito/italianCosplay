<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_NAME', 'Italian Cosplay Test');
define('URL_ROOT', getenv('IC_TEST_BASE_URL') ?: 'http://localhost:8080');
define('URL_ROOT_SITE', getenv('IC_TEST_BASE_URL') ?: 'http://localhost:8080');

require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/app/bootstrap/env.php';
require_once APP_ROOT . '/app/config/app.php';
