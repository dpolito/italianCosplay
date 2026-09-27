<?php
// app/config/database.php

$appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
$databaseName = getenv('DB_NAME') ?: getenv('DB_DATABASE') ?: ($_ENV['DB_NAME'] ?? $_ENV['DB_DATABASE'] ?? 'italiancosplay_gemini');

if ($appEnv === 'testing' && !preg_match('/(test|testing|_test)$/i', $databaseName)) {
	throw new RuntimeException('Refusing to use a non-testing database while APP_ENV=testing.');
}

return [
	'host'     => getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'italiancosplay_gemini_db'),
	'dbname'   => $databaseName,
	'user'     => getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root'),
	'password' => getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? 'rootpassword123'),
	'charset'  => getenv('DB_CHARSET') ?: ($_ENV['DB_CHARSET'] ?? 'utf8mb4')
];
