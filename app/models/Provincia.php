<?php

require_once APP_ROOT . '/app/models/BaseModel.php'; // Includi la BaseModel

class Provincia extends BaseModel
{
	protected $table = 'province'; // Nome della tabella delle province

	public function __construct()
	{
		parent::__construct(); // Chiama il costruttore della BaseModel
	}

	/**
	 * Recupera le province in base all'ID della regione.
	 * @param int $regioneId L'ID della regione.
	 * @return array Array di province con 'id' e 'nome'.
	 */
	public function getByRegioneId($regioneId)
	{
		$stmt = $this->db->prepare("SELECT id, nome FROM " . $this->table . " WHERE regione_id = :regione_id ORDER BY nome ASC");
		$stmt->bindParam(':regione_id', $regioneId, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}
