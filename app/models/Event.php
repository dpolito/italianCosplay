<?php

require_once APP_ROOT . '/app/models/BaseModel.php';

class Event extends BaseModel
{
	protected $table = 'events'; // Nome della tabella nel database

	public function __construct()
	{
		parent::__construct();
	}

	public function create($data)
	{
		$sql = "INSERT INTO {$this->table} (titolo, descrizione, data_inizio, data_fine, luogo, regione_id, provincia_id, comune_id, latitudine, longitudine, sito_web, social_facebook, social_twitter, social_instagram, social_tiktok, social_youtube, tipo_evento_id, immagine, approvato, created_at, updated_at) VALUES (:titolo, :descrizione, :data_inizio, :data_fine, :luogo, :regione_id, :provincia_id, :comune_id, :latitudine, :longitudine, :sito_web, :social_facebook, :social_twitter, :social_instagram, :social_tiktok, :social_youtube, :tipo_evento_id, :immagine, :approvato, NOW(), NOW())";
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([
			'titolo'           => $data['titolo'],
			'descrizione'      => $data['descrizione'],
			'data_inizio'      => $data['data_inizio'],
			'data_fine'        => $data['data_fine'],
			'luogo'            => $data['luogo'],
			'regione_id'       => $data['regione_id'] ?? null,
			'provincia_id'     => $data['provincia_id'] ?? null,
			'comune_id'        => $data['comune_id'] ?? null,
			'latitudine'       => $data['latitudine'] ?? null,
			'longitudine'      => $data['longitudine'] ?? null,
			'sito_web'         => $data['sito_web'] ?? null,
			'social_facebook'  => $data['social_facebook'] ?? null,
			'social_twitter'   => $data['social_twitter'] ?? null,
			'social_instagram' => $data['social_instagram'] ?? null,
			'social_tiktok'    => $data['social_tiktok'] ?? null,
			'social_youtube'   => $data['social_youtube'] ?? null,
			'tipo_evento_id'   => $data['tipo_evento_id'] ?? null,
			'immagine'         => $data['immagine'] ?? null,
			'approvato'        => $data['approvato'] ?? 0 // Di default non approvato, in attesa di moderazione
		]);
	}

	public function update($id, $data)
	{
		$sql = "UPDATE {$this->table} SET titolo = :titolo, descrizione = :descrizione, data_inizio = :data_inizio, data_fine = :data_fine, luogo = :luogo, regione_id = :regione_id, provincia_id = :provincia_id, comune_id = :comune_id, latitudine = :latitudine, longitudine = :longitudine, sito_web = :sito_web, social_facebook = :social_facebook, social_twitter = :social_twitter, social_instagram = :social_instagram, social_tiktok = :social_tiktok, social_youtube = :social_youtube, tipo_evento_id = :tipo_evento_id, immagine = :immagine, approvato = :approvato, updated_at = NOW() WHERE id = :id";
		$stmt = $this->db->prepare($sql);
		return $stmt->execute([
			'titolo'           => $data['titolo'],
			'descrizione'      => $data['descrizione'],
			'data_inizio'      => $data['data_inizio'],
			'data_fine'        => $data['data_fine'],
			'luogo'            => $data['luogo'],
			'regione_id'       => $data['regione_id'] ?? null,
			'provincia_id'     => $data['provincia_id'] ?? null,
			'comune_id'        => $data['comune_id'] ?? null,
			'latitudine'       => $data['latitudine'] ?? null,
			'longitudine'      => $data['longitudine'] ?? null,
			'sito_web'         => $data['sito_web'] ?? null,
			'social_facebook'  => $data['social_facebook'] ?? null,
			'social_twitter'   => $data['social_twitter'] ?? null,
			'social_instagram' => $data['social_instagram'] ?? null,
			'social_tiktok'    => $data['social_tiktok'] ?? null,
			'social_youtube'   => $data['social_youtube'] ?? null,
			'tipo_evento_id'   => $data['tipo_evento_id'] ?? null,
			'immagine'         => $data['immagine'] ?? null,
			'approvato'        => $data['approvato'] ?? 0,
			'id'               => $id
		]);
	}

	public function delete($id)
	{
		$stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
		return $stmt->execute(['id' => $id]);
	}

	public function getApprovedEvents()
	{
		$stmt = $this->db->query("SELECT * FROM {$this->table} WHERE approvato = 1 ORDER BY data_inizio ASC, data_fine ASC");
		return $stmt->fetchAll();
	}

	public function getPendingEvents()
	{
		$stmt = $this->db->query("SELECT * FROM {$this->table} WHERE approvato = 0 ORDER BY created_at ASC");
		return $stmt->fetchAll();
	}

	public function approveEvent($id)
	{
		$stmt = $this->db->prepare("UPDATE {$this->table} SET approvato = 1, updated_at = NOW() WHERE id = :id");
		return $stmt->execute(['id' => $id]);
	}
}
