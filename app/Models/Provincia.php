<?php
namespace App\Models;
use PDO;

class Provincia extends BaseModel
{
	protected $table = 'province';

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Genera uno slug unico da un nome.
	 * @param string $name Il nome da cui generare lo slug.
	 * @param int|null $excludeId ID della provincia da escludere nella ricerca di duplicati (per gli aggiornamenti).
	 * @return string Lo slug generato.
	 */
	private function generateUniqueSlug(string $name, ?int $excludeId = null): string
	{
		$baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
		$slug = $baseSlug;
		$counter = 1;

		while (true) {
			$query = "SELECT COUNT(*) FROM " . $this->table . " WHERE slug = :slug";
			if ($excludeId !== null) {
				$query .= " AND id != :exclude_id";
			}
			$stmt = $this->db->prepare($query);
			$stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
			if ($excludeId !== null) {
				$stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
			}
			$stmt->execute();
			$count = $stmt->fetchColumn();

			if ($count == 0) {
				break; // Lo slug è unico
			}

			$slug = $baseSlug . '-' . $counter++;
		}
		return $slug;
	}

	/**
	 * Recupera le province basate sull'ID della regione.
	 * @param int|null $regioneId L'ID della regione per filtrare le province.
	 * @return array Array di province con 'id', 'nome' e 'slug'.
	 */
	public function getByRegioneId(?int $regioneId = null): array
	{
		error_log('Regione id' . $regioneId);
		$sql = "SELECT id, nome, slug, regione_id FROM " . $this->table;
		if ($regioneId !== null) {
			$sql .= " WHERE regione_id = :regione_id";
		}
		$sql .= " ORDER BY nome ASC";
		error_log('Regione id' . $sql);
		$stmt = $this->db->prepare($sql);
		if ($regioneId !== null) {
			$stmt->bindParam(':regione_id', $regioneId, PDO::PARAM_INT);
		}
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Trova una provincia per ID.
	 * @param int $id L'ID della provincia.
	 * @return array|null La provincia come array associativo o null se non trovata.
	 */
	public function find($id)
	{
		$stmt = $this->db->prepare("SELECT id, nome, slug, regione_id FROM " . $this->table . " WHERE id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Trova una provincia per slug.
	 * @param string $slug Lo slug della provincia.
	 * @return array|null La provincia come array associativo o null se non trovata.
	 */
	public function findBySlug(string $slug)
	{
		$stmt = $this->db->prepare("SELECT id, nome, slug, regione_id FROM " . $this->table . " WHERE slug = :slug");
		$stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
	public function getAll(): array
	{
		$stmt = $this->db->query("SELECT id, nome, slug FROM " . $this->table . " ORDER BY nome ASC");
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

}
