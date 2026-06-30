<?php
namespace App\Models;
use PDO;


class TipoEvento extends BaseModel
{
	protected $table = 'tipo_evento'; // Nome della tabella dei tipi di evento

	public function __construct()
	{
		parent::__construct(); // Chiama il costruttore della BaseModel
	}

	/**
	 * Recupera tutti i tipi di evento.
	 * @return array Array di tipi di evento con 'id' e 'nome'.
	 */
	public function getAll()
	{
		$stmt = $this->db->query("SELECT id, nome FROM " . $this->table . " ORDER BY nome ASC");
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Trova un tipo di evento per ID.
	 * @param int $id L'ID del tipo di evento.
	 * @return array|null Il tipo di evento come array associativo o null se non trovato.
	 */
	public function find($id)
	{
		$stmt = $this->db->prepare("SELECT id, nome FROM " . $this->table . " WHERE id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
}
