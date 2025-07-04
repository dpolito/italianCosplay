<?php

// Assicurati che questo percorso punti correttamente al tuo file di connessione al database
// Potrebbe essere un file di configurazione o una classe base per il database.
// Se hai una classe base per i modelli, questa classe potrebbe estenderla.
// Per questo esempio, assumiamo una connessione diretta o una classe DB disponibile.

class User
{
	private $db;
	private $table = 'users'; // Il nome della tabella degli utenti

	// Costruttore: inizializza la connessione al database
	public function __construct()
	{
		// Questo è un esempio di come potresti ottenere la connessione al database.
		// Se hai una classe 'DB' o un metodo per ottenere la connessione PDO, usalo qui.
		// Ad esempio: $this->db = DB::getInstance()->getConnection();
		// Oppure, se la connessione è globale: global $pdo; $this->db = $pdo;
		// Per ora, useremo una variabile globale $pdo come esempio.
		global $pdo; // Assumiamo che $pdo sia la tua istanza PDO creata in index.php o in un file di configurazione
		$this->db = $pdo;
	}

	/**
	 * Trova un utente per ID
	 * @param int $id L'ID dell'utente
	 * @return array|null L'utente come array associativo o null se non trovato
	 */
	public function find($id)
	{
		$stmt = $this->db->prepare("SELECT * FROM " . $this->table . " WHERE id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Trova un utente per username
	 * @param string $username L'username dell'utente
	 * @return array|null L'utente come array associativo o null se non trovato
	 */
	public function findByUsername($username)
	{
		$stmt = $this->db->prepare("SELECT * FROM " . $this->table . " WHERE username = :username");
		$stmt->bindParam(':username', $username, PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Trova un utente per email
	 * @param string $email L'email dell'utente
	 * @return array|null L'utente come array associativo o null se non trovato
	 */
	public function findByEmail($email)
	{
		$stmt = $this->db->prepare("SELECT * FROM " . $this->table . " WHERE email = :email");
		$stmt->bindParam(':email', $email, PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Recupera tutti gli utenti
	 * @return array Array di utenti
	 */
	public function getAllUsers()
	{
		$stmt = $this->db->query("SELECT * FROM " . $this->table);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Crea un nuovo utente
	 * @param array $data Dati dell'utente (username, email, password, role)
	 * @return bool True se l'utente è stato creato con successo, false altrimenti
	 */
	public function create($data)
	{
		// Hash della password prima di salvarla nel database!
		$hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

		$stmt = $this->db->prepare("INSERT INTO " . $this->table . " (username, email, password, role) VALUES (:username, :email, :password, :role)");

		$stmt->bindParam(':username', $data['username'], PDO::PARAM_STR);
		$stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
		$stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
		$stmt->bindParam(':role', $data['role'], PDO::PARAM_STR); // 'user' o 'admin'

		return $stmt->execute();
	}

	/**
	 * Aggiorna un utente esistente
	 * @param int $id L'ID dell'utente da aggiornare
	 * @param array $data Dati dell'utente da aggiornare (username, email, password, role - password è opzionale)
	 * @return bool True se l'utente è stato aggiornato con successo, false altrimenti
	 */
	public function update($id, $data)
	{
		$query = "UPDATE " . $this->table . " SET username = :username, email = :email, role = :role";
		if (!empty($data['password'])) {
			$hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
			$query .= ", password = :password";
		}
		$query .= " WHERE id = :id";

		$stmt = $this->db->prepare($query);

		$stmt->bindParam(':username', $data['username'], PDO::PARAM_STR);
		$stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
		$stmt->bindParam(':role', $data['role'], PDO::PARAM_STR);
		if (!empty($data['password'])) {
			$stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
		}
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);

		return $stmt->execute();
	}

	/**
	 * Elimina un utente per ID
	 * @param int $id L'ID dell'utente da eliminare
	 * @return bool True se l'utente è stato eliminato con successo, false altrimenti
	 */
	public function delete($id)
	{
		$stmt = $this->db->prepare("DELETE FROM " . $this->table . " WHERE id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		return $stmt->execute();
	}
}
