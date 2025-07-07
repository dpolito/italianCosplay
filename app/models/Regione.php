<?php

require_once APP_ROOT . '/app/models/BaseModel.php'; // Includi la BaseModel

class Regione extends BaseModel
{
	protected $table = 'regioni'; // Nome della tabella delle regioni

	public function __construct()
	{
		parent::__construct(); // Chiama il costruttore della BaseModel
	}

	/**
	 * Recupera tutte le regioni.
	 * @return array Array di regioni con 'id' e 'nome'.
	 */
	public function getAll()
	{
		$stmt = $this->db->query("SELECT id, nome FROM " . $this->table . " ORDER BY nome ASC");
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}
