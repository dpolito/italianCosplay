<?php

// Assicurati che Database.php sia già caricato in index.php
// e che la classe Database sia disponibile.

class Event
{
	private $db;
	private $table = 'events'; // Il nome della tabella degli eventi

	public function __construct()
	{
		// Ottieni l'istanza della connessione PDO dalla classe Database
		$this->db = Database::getInstance()->getConnection();
	}

	/**
	 * Recupera tutti gli eventi approvati.
	 * @return array Array di eventi.
	 */
	public function getApprovedEvents()
	{
		// Modificato per usare 'approvato = 1'
		$stmt = $this->db->query("SELECT * FROM " . $this->table . " WHERE approvato = 1 ORDER BY created_at DESC");
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Recupera tutti gli eventi in attesa di approvazione.
	 * @return array Array di eventi in attesa.
	 */
	public function getPendingEvents()
	{
		// Modificato per usare 'approvato = 0'
		$stmt = $this->db->query("SELECT * FROM " . $this->table . " WHERE approvato = 0 ORDER BY created_at ASC");
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Recupera tutti gli eventi (approvati e non).
	 * Questo metodo è per l'area amministrativa.
	 * @return array Array di tutti gli eventi.
	 */
	public function getAllEvents()
	{
		$stmt = $this->db->query("SELECT * FROM " . $this->table . " ORDER BY created_at DESC");
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Trova un evento per ID.
	 * @param int $id L'ID dell'evento.
	 * @return array|null L'evento come array associativo o null se non trovato.
	 */
	public function find($id)
	{
		$stmt = $this->db->prepare("SELECT * FROM " . $this->table . " WHERE id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Crea un nuovo evento.
	 * @param array $data Dati dell'evento.
	 * @return bool True se l'evento è stato creato con successo, false altrimenti.
	 */
	public function create($data)
	{
		// Imposta 'approvato' a 0 (false) per default per i nuovi eventi
		$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (titolo, descrizione, data_inizio, data_fine, luogo, regione_id, provincia_id, comune_id, latitudine, longitudine, sito_web, social_facebook, social_twitter, social_instagram, social_tiktok, social_youtube, tipo_evento_id, immagine, approvato) VALUES (:titolo, :descrizione, :data_inizio, :data_fine, :luogo, :regione_id, :provincia_id, :comune_id, :latitudine, :longitudine, :sito_web, :social_facebook, :social_twitter, :social_instagram, :social_tiktok, :social_youtube, :tipo_evento_id, :immagine, 0)");

		$stmt->bindParam(':titolo', $data['titolo'], PDO::PARAM_STR);
		$stmt->bindParam(':descrizione', $data['descrizione'], PDO::PARAM_STR);
		$stmt->bindParam(':data_inizio', $data['data_inizio'], PDO::PARAM_STR);
		$stmt->bindParam(':data_fine', $data['data_fine'], PDO::PARAM_STR);
		$stmt->bindParam(':luogo', $data['luogo'], PDO::PARAM_STR);
		$stmt->bindParam(':regione_id', $data['regione_id'], PDO::PARAM_INT);
		$stmt->bindParam(':provincia_id', $data['provincia_id'], PDO::PARAM_INT);
		$stmt->bindParam(':comune_id', $data['comune_id'], PDO::PARAM_INT);
		$stmt->bindParam(':latitudine', $data['latitudine'], PDO::PARAM_STR); // DECIMAL può essere STR o FLOAT
		$stmt->bindParam(':longitudine', $data['longitudine'], PDO::PARAM_STR); // DECIMAL può essere STR o FLOAT
		$stmt->bindParam(':sito_web', $data['sito_web'], PDO::PARAM_STR);
		$stmt->bindParam(':social_facebook', $data['social_facebook'], PDO::PARAM_STR);
		$stmt->bindParam(':social_twitter', $data['social_twitter'], PDO::PARAM_STR);
		$stmt->bindParam(':social_instagram', $data['social_instagram'], PDO::PARAM_STR);
		$stmt->bindParam(':social_tiktok', $data['social_tiktok'], PDO::PARAM_STR);
		$stmt->bindParam(':social_youtube', $data['social_youtube'], PDO::PARAM_STR);
		$stmt->bindParam(':tipo_evento_id', $data['tipo_evento_id'], PDO::PARAM_INT);
		$stmt->bindParam(':immagine', $data['immagine'], PDO::PARAM_STR);

		return $stmt->execute();
	}

	/**
	 * Aggiorna un evento esistente.
	 * @param int $id L'ID dell'evento da aggiornare.
	 * @param array $data Dati dell'evento da aggiornare.
	 * @return bool True se l'evento è stato aggiornato con successo, false altrimenti.
	 */
	public function update($id, $data)
	{
		$query = "UPDATE " . $this->table . " SET 
                    titolo = :titolo, 
                    descrizione = :descrizione, 
                    data_inizio = :data_inizio, 
                    data_fine = :data_fine, 
                    luogo = :luogo, 
                    regione_id = :regione_id, 
                    provincia_id = :provincia_id, 
                    comune_id = :comune_id, 
                    latitudine = :latitudine, 
                    longitudine = :longitudine, 
                    sito_web = :sito_web, 
                    social_facebook = :social_facebook, 
                    social_twitter = :social_twitter, 
                    social_instagram = :social_instagram, 
                    social_tiktok = :social_tiktok, 
                    social_youtube = :social_youtube, 
                    tipo_evento_id = :tipo_evento_id, 
                    immagine = :immagine";

		// Se 'approvato' è presente nei dati, lo includiamo nell'aggiornamento
		if (isset($data['approvato'])) {
			$query .= ", approvato = :approvato";
		}
		$query .= " WHERE id = :id";

		$stmt = $this->db->prepare($query);

		$stmt->bindParam(':titolo', $data['titolo'], PDO::PARAM_STR);
		$stmt->bindParam(':descrizione', $data['descrizione'], PDO::PARAM_STR);
		$stmt->bindParam(':data_inizio', $data['data_inizio'], PDO::PARAM_STR);
		$stmt->bindParam(':data_fine', $data['data_fine'], PDO::PARAM_STR);
		$stmt->bindParam(':luogo', $data['luogo'], PDO::PARAM_STR);
		$stmt->bindParam(':regione_id', $data['regione_id'], PDO::PARAM_INT);
		$stmt->bindParam(':provincia_id', $data['provincia_id'], PDO::PARAM_INT);
		$stmt->bindParam(':comune_id', $data['comune_id'], PDO::PARAM_INT);
		$stmt->bindParam(':latitudine', $data['latitudine'], PDO::PARAM_STR); // DECIMAL può essere STR o FLOAT
		$stmt->bindParam(':longitudine', $data['longitudine'], PDO::PARAM_STR); // DECIMAL può essere STR o FLOAT
		$stmt->bindParam(':sito_web', $data['sito_web'], PDO::PARAM_STR);
		$stmt->bindParam(':social_facebook', $data['social_facebook'], PDO::PARAM_STR);
		$stmt->bindParam(':social_twitter', $data['social_twitter'], PDO::PARAM_STR);
		$stmt->bindParam(':social_instagram', $data['social_instagram'], PDO::PARAM_STR);
		$stmt->bindParam(':social_tiktok', $data['social_tiktok'], PDO::PARAM_STR);
		$stmt->bindParam(':social_youtube', $data['social_youtube'], PDO::PARAM_STR);
		$stmt->bindParam(':tipo_evento_id', $data['tipo_evento_id'], PDO::PARAM_INT);
		$stmt->bindParam(':immagine', $data['immagine'], PDO::PARAM_STR);

		if (isset($data['approvato'])) {
			// Assicurati che il valore sia un intero (0 o 1)
			$stmt->bindParam(':approvato', $data['approvato'], PDO::PARAM_INT);
		}
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);

		return $stmt->execute();
	}

	/**
	 * Approva un evento cambiando il suo stato a 'approvato' (1).
	 * @param int $id L'ID dell'evento da approvare.
	 * @return bool True se l'evento è stato approvato con successo, false altrimenti.
	 */
	public function approveEvent($id)
	{
		// Imposta 'approvato' a 1 (true)
		$stmt = $this->db->prepare("UPDATE " . $this->table . " SET approvato = 1 WHERE id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		return $stmt->execute();
	}

	/**
	 * Elimina un evento per ID.
	 * @param int $id L'ID dell'evento da eliminare.
	 * @return bool True se l'evento è stato eliminato con successo, false altrimenti.
	 */
	public function delete($id)
	{
		$stmt = $this->db->prepare("DELETE FROM " . $this->table . " WHERE id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		return $stmt->execute();
	}
}
