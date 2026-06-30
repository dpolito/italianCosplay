<?php
namespace App\Models;
use PDO;
use function preg_replace;
use function strtolower;
use function trim;
use function var_dump;

class Regione extends BaseModel
{
	protected $table = 'regioni';

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Recupera tutte le regioni.
	 * @return array Un array di regioni.
	 */
	public function getAll(): array
	{
		$stmt = $this->db->query("SELECT id, nome, slug FROM " . $this->table . " ORDER BY nome ASC");
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Trova una regione per ID.
	 * @param int $id L'ID della regione.
	 * @return array|null La regione come array associativo o null se non trovata.
	 */
	public function find($id)
	{
		$stmt = $this->db->prepare("SELECT * FROM " . $this->table . " WHERE id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Trova una regione per slug.
	 * @param string $slug Lo slug della regione.
	 * @return array|null La regione come array associativo o null se non trovata.
	 */
	public function findBySlug(string $slug)
	{
		$stmt = $this->db->prepare("SELECT * FROM " . $this->table . " WHERE slug = :slug");
		$stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function findByName(string $name)
	{
		$stmt = $this->db->prepare("SELECT id, nome, slug FROM " . $this->table . " WHERE nome = :name");
		$stmt->bindParam(':name', $name, PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
	/**
	 * Aggiorna un evento esistente.
	 * @param int $id L'ID dell'evento da aggiornare.
	 * @param array $data Dati dell'evento da aggiornare.
	 * @return bool True se l'evento è stato aggiornato con successo, false altrimenti.
	 */
	public function update(int $id, array $data): bool
	{
		// Recupera lo slug esistente o ne genera uno nuovo se il titolo è cambiato
		$currentRegione = $this->find($id);
		$slug = $currentRegione['slug']; // Mantiene lo slug esistente di default
		if ($currentRegione['nome'] !== $data['nome']) {
			$slug = $this->generateUniqueSlug($data['$currentRegione'], $id);
		}

		$query = "UPDATE " . $this->table . " SET
                      nome = :nome,
                      seo_title = :seo_title,
                      seo_description = :seo_description,
                      	intro_html = :intro_html
                      
                  WHERE id = :id";
		$stmt = $this->db->prepare($query);

		$stmt->bindValue(':nome', $data['nome']);
		$stmt->bindValue(':seo_title', $data['seo_title']);
		$stmt->bindValue(':seo_description', $data['seo_description']);
		$stmt->bindValue(':intro_html', $data['intro_html'] ?: null);

		$stmt->bindValue(':id', $id, PDO::PARAM_INT);

		return $stmt->execute();
	}
	/**
	 * Genera uno slug unico da un titolo.
	 * @param string $title Il titolo da cui generare lo slug.
	 * @param int|null $excludeId ID dell'evento da escludere nella ricerca di duplicati (per gli aggiornamenti).
	 * @return string Lo slug generato.
	 */
	public function generateUniqueSlug(string $title, ?int $excludeId = null): string
	{
		$baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
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
	public function delete(int $id): bool
	{
		$stmt = $this->db->prepare("DELETE FROM " . $this->table . " WHERE id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		return $stmt->execute();
	}
}
