<?php
namespace App\Models;
// Non è più necessario 'global $pdo;' qui, useremo la classe Database
use App\Core\Database;
use PDO;

class User{
	private $db; // Questa sarà la tua istanza PDO
	private $table = 'users'; // Il nome della tabella degli utenti
	public $id;
	public $role_id;
	public string $role = '';

	// Costruttore: inizializza la connessione al database
	public function __construct(){
		// Ottieni l'istanza della connessione PDO dalla classe Database
		// Assicurati che Database::getInstance() sia chiamato con la configurazione
		// nel tuo index.php prima di istanziare User.
		$this->db = Database::getInstance()->getConnection();
	}

	public function hasRole(string $roleName): bool
	{
		$sql = "
        SELECT COUNT(*)
        FROM users u
        JOIN user_roles r ON u.role_id = r.id
        WHERE u.id = :user_id
        AND r.name = :role
    ";

		$stmt = $this->db->prepare($sql);
		$stmt->execute([
			':user_id' => $this->id,
			':role' => $roleName
		]);

		return $stmt->fetchColumn() > 0;
	}
	public function load(int $userId): bool
	{
		$data = $this->find($userId);
		if (!$data) return false;

		$this->id = $data['id'];
		$this->role_id = $data['role_id'];
		$this->role = $this->getRoleName($this->role_id);

		return true;
	}
	public function getRoles(): array
	{
		$stmt = $this->db->prepare("SELECT id, name FROM user_roles ");
		$stmt->execute();
		return $stmt->fetchAll();
	}

	private function getRoleName(int $roleId): string
	{
		$stmt = $this->db->prepare("SELECT name FROM user_roles WHERE id = :id");
		$stmt->execute([':id' => $roleId]);
		return (string)$stmt->fetchColumn();
	}


	public function isAdmin(): bool
	{
		return $this->hasRole('admin');
	}

	public function isEditor(): bool
	{
		return $this->role === 'editor';
	}


	public function hasPermission($userId, $permissionName)
	{
		$sql = "SELECT COUNT(*) 
            FROM users u
            JOIN role_permissions rp ON u.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE u.id = :user_id
            AND p.name = :permission";

		$stmt = $this->db->prepare($sql);

		$stmt->execute([
			':user_id' => $userId,
			':permission' => $permissionName
		]);

		return $stmt->fetchColumn() > 0;
	}

	/**
	 * Trova un utente per ID
	 *
	 * @param int $id L'ID dell'utente
	 *
	 * @return array|null L'utente come array associativo o null se non trovato
	 */
	public function find($id){
		$stmt = $this->db->prepare("SELECT " . $this->table . ".*, comuni.nome as comune_name FROM " . $this->table . " left join comuni on comuni.id = " . $this->table . ".comune_id WHERE " . $this->table . ".id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Trova un utente per username
	 *
	 * @param string $username L'username dell'utente
	 *
	 * @return array|null L'utente come array associativo o null se non trovato
	 */
	public function findByUsername($username){
		$stmt = $this->db->prepare("SELECT * FROM " . $this->table . " WHERE username = :username");
		$stmt->bindParam(':username', $username, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Trova un utente per email
	 *
	 * @param string $email L'email dell'utente
	 *
	 * @return array|null L'utente come array associativo o null se non trovato
	 */
	public function findByEmail($email){
		$stmt = $this->db->prepare("SELECT * FROM " . $this->table . " WHERE email = :email");
		$stmt->bindParam(':email', $email, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Recupera tutti gli utenti
	 *
	 * @return array Array di utenti
	 */
	public function getAllUsers(){
		$stmt = $this->db->query("SELECT * FROM " . $this->table);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Crea un nuovo utente
	 *
	 * @param array $data Dati dell'utente (username, email, password, role)
	 *
	 * @return bool True se l'utente è stato creato con successo, false altrimenti
	 */
	public function create($data)
	{
		$hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
		$stmt = $this->db->prepare(
			"INSERT INTO users (username,email,password,role_id,verified,verification_token)
        VALUES (:username,:email,:password,:role_id,:verified,:verification_token)"
		);
		return $stmt->execute([
			':username' => $data['username'],
			':email' => $data['email'],
			':password' => $hashed_password,
			':role_id' => $data['role_id'],
			':verified' => $data['verified'],
			':verification_token' => $data['verification_token']
		]);
	}
	public function findByVerificationToken($token)
	{
		$stmt = $this->db->prepare("SELECT * FROM users WHERE verification_token = :token");
		$stmt->execute([':token' => $token]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function verifyUser($id)
	{
		$stmt = $this->db->prepare("UPDATE users SET verified = 1, verification_token = NULL WHERE id = :id");
		return $stmt->execute([':id' => $id]);
	}


	/**
	 * Aggiorna un utente esistente
	 *
	 * @param int   $id   L'ID dell'utente da aggiornare
	 * @param array $data Dati dell'utente da aggiornare (username, email, password, role - password è opzionale)
	 *
	 * @return bool True se l'utente è stato aggiornato con successo, false altrimenti
	 */
	public function update($id, $data){
		$query = "UPDATE " . $this->table . " SET username = :username, email = :email, role = :role, role_id = :role_id";
		if(!empty($data['password'])){
			$hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
			$query .= ", password = :password";
		}
		$query .= " WHERE id = :id";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':username', $data['username'], PDO::PARAM_STR);
		$stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
		$stmt->bindParam(':role', $data['role'], PDO::PARAM_STR);
		$stmt->bindParam(':role_id', $data['role_id'], PDO::PARAM_STR); // 'user' o 'admin'
		if(!empty($data['password'])){
			$stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
		}
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		return $stmt->execute();
	}
	public function update_dashboard(int $id, array $data): bool
	{
		$sql = "UPDATE users SET
                first_name = :first_name,
                last_name = :last_name,
                website = :website,
                bio = :bio,
                social = :social,
                comune_id = :comune_id,
                updated_at = NOW()
            WHERE id = :id";

		$stmt = $this->db->prepare($sql);

		return $stmt->execute([
			'first_name' => $data['first_name'],
			'last_name'  => $data['last_name'],
			'website'    => $data['website'],
			'bio'        => $data['bio'],
			'social'     => $data['social'],
			'comune_id'  => $data['comune_id'],
			'id'         => $id,
		]);
	}
	public function update_dashboard_password(int $id, array $data): bool
	{
		$sql = "UPDATE users SET
                password = :password,
                updated_at = NOW()
            WHERE id = :id";

		$stmt = $this->db->prepare($sql);

		return $stmt->execute([
			'password'   => $data['password'],
			'id'         => $id,
		]);
	}
	public function update_dashboard_avatar(int $id, array $data): bool
	{
		$sql = "UPDATE users SET
                avatar = :avatar,
                updated_at = NOW()
            WHERE id = :id";

		$stmt = $this->db->prepare($sql);

		return $stmt->execute([
			'avatar'   => $data['avatar'],
			'id'         => $id,
		]);
	}

	/**
	 * Elimina un utente per ID
	 *
	 * @param int $id L'ID dell'utente da eliminare
	 *
	 * @return bool True se l'utente è stato eliminato con successo, false altrimenti
	 */
	public function delete($id){
		$stmt = $this->db->prepare("DELETE FROM " . $this->table . " WHERE id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);

		return $stmt->execute();
	}
	function processAvatar($tmpFile, $destPath, $maxSize = 100) {
		list($width, $height, $type) = getimagesize($tmpFile);

		switch ($type) {
			case IMAGETYPE_JPEG:
				$src = imagecreatefromjpeg($tmpFile);
				break;
			case IMAGETYPE_PNG:
				$src = imagecreatefrompng($tmpFile);
				break;
			default:
				throw new Exception("Formato immagine non supportato");
		}

		// Crop quadrato centrato
		$side = min($width, $height);
		$srcX = ($width - $side) / 2;
		$srcY = ($height - $side) / 2;

		$dst = imagecreatetruecolor($maxSize, $maxSize);
		imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $maxSize, $maxSize, $side, $side);

		// Salvataggio
		switch ($type) {
			case IMAGETYPE_JPEG:
				imagejpeg($dst, $destPath, 90);
				break;
			case IMAGETYPE_PNG:
				imagepng($dst, $destPath);
				break;
		}

		imagedestroy($src);
		imagedestroy($dst);
		return true;
	}
	public function update_dashboard_cover(int $id, array $data): bool
	{
		$sql = "UPDATE users SET
                profile_cover = :profile_cover,
                cover_position_x = :cover_position_x,
                cover_position_y = :cover_position_y,
                updated_at = NOW()
            WHERE id = :id";

		$stmt = $this->db->prepare($sql);

		return $stmt->execute([
			'profile_cover'     => $data['profile_cover'],
			'cover_position_x'  => $data['cover_position_x'],
			'cover_position_y'  => $data['cover_position_y'],
			'id'                => $id,
		]);
	}
	public function updateCoverPosition(int $id, array $data): bool
	{
		$sql = "UPDATE users SET
				cover_position_x = :x,
				cover_position_y = :y,
				updated_at = NOW()
			WHERE id = :id";

		$stmt = $this->db->prepare($sql);

		return $stmt->execute([
			'x'  => $data['x'],
			'y'  => $data['y'],
			'id' => $id
		]);
	}
	public function getProfileSettings($user)
	{
		return json_decode($user['profile_settings'] ?? '{}', true);
	}
	public function updateProfileSettings($id, array $settings): bool
	{
		$sql = "UPDATE users SET profile_settings = :settings, updated_at = NOW() WHERE id = :id";

		$stmt = $this->db->prepare($sql);

		return $stmt->execute([
			'settings' => json_encode($settings),
			'id' => $id
		]);
	}
}
