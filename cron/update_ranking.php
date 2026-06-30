<?php

use App\Core\Database;
use App\Services\EventRankingService;

if (php_sapi_name() !== 'cli') {
	exit("CLI only");
}

if(!defined('APP_ROOT')){
	define('APP_ROOT', '/var/www/html/');
}



$db_config = require_once APP_ROOT . '/app/config/database.php';
Database::getInstance($db_config); // Passa la configurazione al singleton

$ranking = new EventRankingService();
$ranking->updateAllScores();

echo "Ranking aggiornato";
