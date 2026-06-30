<?php
namespace App\Models;
use PDO;


class Comune extends BaseModel
{
	protected $table = 'comuni';

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Genera uno slug unico da un nome.
	 * @param string $name Il nome da cui generare lo slug.
	 * @param int|null $excludeId ID del comune da escludere nella ricerca di duplicati (per gli aggiornamenti).
	 * @return string Lo slug generato e unico.
	 */
	private function generateUniqueSlug($name, $excludeId = null)
	{
		$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
		$originalSlug = $slug;
		$counter = 1;

		while (true) {
			$stmt = $this->db->prepare("SELECT COUNT(*) FROM " . $this->table . " WHERE slug = :slug" . ($excludeId ? " AND id != :exclude_id" : ""));
			$stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
			if ($excludeId) {
				$stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
			}
			$stmt->execute();
			$count = $stmt->fetchColumn();

			if ($count == 0) {
				break; // Lo slug è unico
			}

			$slug = $originalSlug . '-' . $counter++;
		}
		return $slug;
	}

	/**
	 * Recupera tutti i comuni, opzionalmente filtrati per provincia, includendo lo slug.
	 * @param int|null $provinciaId L'ID della provincia per filtrare i comuni.
	 * @return array Array di comuni con 'id', 'nome' e 'slug'.
	 */
	public function getAll($provinciaId = null)
	{
		$sql = "SELECT id, nome, slug, provincia_id FROM " . $this->table;
		if ($provinciaId !== null) {
			$sql .= " WHERE provincia_id = :provincia_id";
		}
		$sql .= " ORDER BY nome ASC";

		$stmt = $this->db->prepare($sql);
		if ($provinciaId !== null) {
			$stmt->bindParam(':provincia_id', $provinciaId, PDO::PARAM_INT);
		}
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Trova un comune per ID, includendo lo slug.
	 * @param int $id L'ID del comune.
	 * @return array|null Il comune come array associativo o null se non trovato.
	 */
	public function find($id)
	{
		$stmt = $this->db->prepare("SELECT id, nome, provincia_id, slug FROM " . $this->table . " WHERE id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Trova un comune per slug.
	 * @param string $slug Lo slug del comune.
	 * @return array|null Il comune come array associativo o null se non trovato.
	 */
	public function findBySlug($slug)
	{
		$stmt = $this->db->prepare("SELECT id, nome, provincia_id, slug FROM " . $this->table . " WHERE slug = :slug");
		$stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
	/**
	 * Trova un comune per nome.
	 * @param string $nome Lo slug del comune.
	 * @return array|null Il comune come array associativo o null se non trovato.
	 */
	public function findByNome($nome)
	{
		$stmt = $this->db->prepare("SELECT id, nome, provincia_id FROM " . $this->table . " WHERE nome = :nome");
		$stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function search($search)
	{
		if ($search === '') {
			echo json_encode([]);
			exit;
		}

		$stmt = $this->db->prepare("
            SELECT c.id, c.nome as comune, p.nome AS provincia, r.nome AS regione
            FROM comuni c
            JOIN province p ON c.provincia_id = p.id
            JOIN regioni r ON p.regione_id = r.id
            WHERE c.nome LIKE :q
            ORDER BY c.nome
            LIMIT 10
        ");
		$stmt->execute([':q' => "%$search%"]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	// Se i comuni vengono creati/aggiornati tramite l'applicazione,
	// dovresti aggiungere metodi create/update qui che utilizzano generateUniqueSlug.
	// Esempio (se necessario):
	/*
	public function create($data)
	{
		$data['slug'] = $this->generateUniqueSlug($data['nome']);
		$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (nome, provincia_id, slug) VALUES (:nome, :provincia_id, :slug)");
		$stmt->bindParam(':nome', $data['nome'], PDO::PARAM_STR);
		$stmt->bindParam(':provincia_id', $data['provincia_id'], PDO::PARAM_INT);
		$stmt->bindParam(':slug', $data['slug'], PDO::PARAM_STR);
		return $stmt->execute();
	}

	public function update($id, $data)
	{
		$existingComune = $this->find($id);
		if (!$existingComune) {
			return false;
		}
		if ($existingComune['nome'] !== $data['nome']) {
			$data['slug'] = $this->generateUniqueSlug($data['nome'], $id);
		} else {
			$data['slug'] = $existingComune['slug'];
		}

		$stmt = $this->db->prepare("UPDATE " . $this->table . " SET nome = :nome, provincia_id = :provincia_id, slug = :slug WHERE id = :id");
		$stmt->bindParam(':nome', $data['nome'], PDO::PARAM_STR);
		$stmt->bindParam(':provincia_id', $data['provincia_id'], PDO::PARAM_INT);
		$stmt->bindParam(':slug', $data['slug'], PDO::PARAM_STR);
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		return $stmt->execute();
	}
	*/
}
