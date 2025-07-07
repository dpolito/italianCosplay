<?php

require_once APP_ROOT . '/app/models/BaseModel.php'; // Includi la BaseModel

class Comune extends BaseModel
{
	protected $table = 'comuni'; // Nome della tabella dei comuni

	public function __construct()
	{
		parent::__construct(); // Chiama il costruttore della BaseModel
	}

	/**
	 * Recupera i comuni in base all'ID della provincia.
	 * @param int $provinciaId L'ID della provincia.
	 * @return array Array di comuni con 'id' e 'nome'.
	 */
	public function getByProvinciaId($provinciaId)
	{
		$stmt = $this->db->prepare("SELECT id, nome FROM " . $this->table . " WHERE provincia_id = :provincia_id ORDER BY nome ASC");
		$stmt->bindParam(':provincia_id', $provinciaId, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}
