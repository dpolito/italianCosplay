<?php

class Database
{
	private static $instance = null;
	private $pdo;

	private function __construct(array $config)
	{
		$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
		$options = [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false,
		];
		try {
			$this->pdo = new PDO($dsn, $config['user'], $config['password'], $options);
		} catch (PDOException $e) {
			// In un ambiente di produzione, logga l'errore e mostra un messaggio generico.
			// error_log("Errore di connessione al database: " . $e->getMessage());
			die("Impossibile connettersi al database. Si prega di riprovare più tardi.");
		}
	}

	public static function getInstance(array $config = [])
	{
		if (self::$instance === null) {
			self::$instance = new Database($config);
		}
		return self::$instance;
	}

	public function getConnection()
	{
		return $this->pdo;
	}
}
