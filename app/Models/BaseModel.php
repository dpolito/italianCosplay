<?php
namespace App\Models;
use App\Core\Database;
use PDO;


class BaseModel
{
	protected $db;
	protected $table; // Il nome della tabella associata al modello

	public function __construct()
	{
		// Ottieni l'istanza della connessione PDO dalla classe Database
		$this->db = Database::getInstance()->getConnection();
	}

	/**
	 * Restituisce l'istanza della connessione PDO.
	 * Questo metodo è stato aggiunto per permettere l'accesso alla connessione
	 * da classi esterne in modo controllato, ad esempio per script di utility.
	 * @return PDO L'istanza della connessione PDO.
	 */
	public function getDbConnection(): PDO
	{
		return $this->db;
	}

	// Metodi comuni che potrebbero essere ereditati da altri modelli
	// public function find($id) { ... }
	// public function getAll() { ... }
}
