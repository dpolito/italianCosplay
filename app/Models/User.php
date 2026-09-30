<?php
namespace App\Models;
// Non è più necessario 'global $pdo;' qui, useremo la classe Database
use App\Core\Database;
use PDO;
use Throwable;

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
	        AND u.anonymized_at IS NULL
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
            AND u.anonymized_at IS NULL
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
		$sql = "
			SELECT u.*,
			       comuni.nome AS comune_name,
		       privacy_accept.accepted_at AS privacy_accepted_at,
		       privacy_accept.privacy_policy_version_id AS privacy_policy_version_id,
		       marketing_consent.opted_in AS marketing_opted_in,
		       marketing_consent.opted_in_at AS marketing_opted_in_at,
		       age_declaration.declared_adult AS age_declared_adult,
		       age_declaration.declared_at AS age_declared_at
			FROM " . $this->table . " u
			LEFT JOIN comuni ON comuni.id = u.comune_id
			LEFT JOIN (
				SELECT pa.user_id, pa.privacy_policy_version_id, pa.accepted_at
				FROM privacy_policy_acceptances pa
				INNER JOIN (
					SELECT user_id, MAX(accepted_at) AS max_accepted_at
					FROM privacy_policy_acceptances
					WHERE user_id IS NOT NULL
					GROUP BY user_id
				) latest_pa
					ON latest_pa.user_id = pa.user_id
				   AND latest_pa.max_accepted_at = pa.accepted_at
			) privacy_accept ON privacy_accept.user_id = u.id
			LEFT JOIN user_marketing_consents marketing_consent ON marketing_consent.user_id = u.id
			LEFT JOIN user_age_declarations age_declaration ON age_declaration.user_id = u.id
			WHERE u.id = :id
			  AND u.anonymized_at IS NULL";
		$stmt = $this->db->prepare($sql);
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
		$stmt = $this->db->prepare("SELECT * FROM " . $this->table . " WHERE username = :username AND anonymized_at IS NULL");
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
		$stmt = $this->db->prepare("SELECT * FROM " . $this->table . " WHERE email = :email AND anonymized_at IS NULL");
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
		$sql = "
			SELECT u.*,
		       privacy_accept.accepted_at AS privacy_accepted_at,
		       privacy_accept.privacy_policy_version_id AS privacy_policy_version_id,
		       marketing_consent.opted_in AS marketing_opted_in,
		       marketing_consent.opted_in_at AS marketing_opted_in_at,
		       age_declaration.declared_adult AS age_declared_adult,
		       age_declaration.declared_at AS age_declared_at
			FROM " . $this->table . " u
			LEFT JOIN (
				SELECT pa.user_id, pa.privacy_policy_version_id, pa.accepted_at
				FROM privacy_policy_acceptances pa
				INNER JOIN (
					SELECT user_id, MAX(accepted_at) AS max_accepted_at
					FROM privacy_policy_acceptances
					WHERE user_id IS NOT NULL
					GROUP BY user_id
				) latest_pa
					ON latest_pa.user_id = pa.user_id
				   AND latest_pa.max_accepted_at = pa.accepted_at
			) privacy_accept ON privacy_accept.user_id = u.id
			LEFT JOIN user_marketing_consents marketing_consent ON marketing_consent.user_id = u.id
			LEFT JOIN user_age_declarations age_declaration ON age_declaration.user_id = u.id
			WHERE u.anonymized_at IS NULL";
		$stmt = $this->db->query($sql);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function searchPublicProfiles(array $filters = []): array
	{
		$sql = "SELECT u.*, comuni.nome AS comune_name
				FROM users u
				LEFT JOIN comuni ON comuni.id = u.comune_id
				LEFT JOIN user_roles ur ON ur.id = u.role_id
				WHERE 1=1
				AND u.verified = 1
				AND u.anonymized_at IS NULL
				AND (ur.name IS NULL OR ur.name <> 'admin')";

		$params = [];

		if (!empty($filters['q'])) {
			$sql .= " AND (u.username LIKE :q_username OR u.first_name LIKE :q_first_name OR u.last_name LIKE :q_last_name OR u.bio LIKE :q_bio)";
			$params[':q_username'] = '%' . $filters['q'] . '%';
			$params[':q_first_name'] = '%' . $filters['q'] . '%';
			$params[':q_last_name'] = '%' . $filters['q'] . '%';
			$params[':q_bio'] = '%' . $filters['q'] . '%';
		}

		if (!empty($filters['location'])) {
			$sql .= " AND (comuni.nome LIKE :location)";
			$params[':location'] = '%' . $filters['location'] . '%';
		}

		if (!empty($filters['has_bio'])) {
			$sql .= " AND u.bio IS NOT NULL AND u.bio <> ''";
		}

		$sort = $filters['sort'] ?? 'recent';
		$direction = strtoupper($filters['direction'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
		$orderBy = match ($sort) {
			'username' => 'u.username',
			'created_old' => 'u.created_at',
			default => 'u.created_at',
		};

		$sql .= " ORDER BY {$orderBy} {$direction}";

		$page = max(1, (int) ($filters['page'] ?? 1));
		$perPage = min(24, max(6, (int) ($filters['per_page'] ?? 12)));
		$offset = ($page - 1) * $perPage;

		$sql .= " LIMIT :limit OFFSET :offset";

		$stmt = $this->db->prepare($sql);
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value, PDO::PARAM_STR);
		}
		$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function countPublicProfiles(array $filters = []): int
	{
		$sql = "SELECT COUNT(*)
				FROM users u
				LEFT JOIN comuni ON comuni.id = u.comune_id
				LEFT JOIN user_roles ur ON ur.id = u.role_id
					WHERE 1=1
					AND u.verified = 1
					AND u.anonymized_at IS NULL
					AND (ur.name IS NULL OR ur.name <> 'admin')";
		$params = [];

		if (!empty($filters['q'])) {
			$sql .= " AND (u.username LIKE :q_username OR u.first_name LIKE :q_first_name OR u.last_name LIKE :q_last_name OR u.bio LIKE :q_bio)";
			$params[':q_username'] = '%' . $filters['q'] . '%';
			$params[':q_first_name'] = '%' . $filters['q'] . '%';
			$params[':q_last_name'] = '%' . $filters['q'] . '%';
			$params[':q_bio'] = '%' . $filters['q'] . '%';
		}

		if (!empty($filters['location'])) {
			$sql .= " AND (comuni.nome LIKE :location)";
			$params[':location'] = '%' . $filters['location'] . '%';
		}

		if (!empty($filters['has_bio'])) {
			$sql .= " AND u.bio IS NOT NULL AND u.bio <> ''";
		}

		$stmt = $this->db->prepare($sql);
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value, PDO::PARAM_STR);
		}
		$stmt->execute();

		return (int) $stmt->fetchColumn();
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
		$stmt = $this->db->prepare("SELECT * FROM users WHERE verification_token = :token AND anonymized_at IS NULL");
		$stmt->execute([':token' => $token]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function verifyUser($id)
	{
		$stmt = $this->db->prepare("UPDATE users SET verified = 1, verification_token = NULL WHERE id = :id");
		return $stmt->execute([':id' => $id]);
	}

	public function recordSuccessfulLogin(int $id): bool
	{
		try {
			$stmt = $this->db->prepare(
				"UPDATE users
				SET last_login_at = NOW(),
					last_activity_at = NOW(),
					login_count = COALESCE(login_count, 0) + 1,
					updated_at = NOW()
				WHERE id = :id
				  AND anonymized_at IS NULL"
			);

			return $stmt->execute([':id' => $id]);
		} catch (Throwable $exception) {
			error_log('User login analytics update failed: ' . $exception->getMessage());
			return false;
		}
	}


	/**
	 * Aggiorna un utente esistente
	 *
	 * @param int   $id   L'ID dell'utente da aggiornare
	 * @param array $data Dati dell'utente da aggiornare (username, email, password, role_id - password è opzionale)
	 *
	 * @return bool True se l'utente è stato aggiornato con successo, false altrimenti
	 */
	public function update($id, $data){
		$query = "UPDATE " . $this->table . " SET username = :username, email = :email, role_id = :role_id, verified = :verified, first_name = :first_name, last_name = :last_name, website = :website, bio = :bio, social = :social, avatar = :avatar, profile_cover = :profile_cover, comune_id = :comune_id";
		if(!empty($data['password'])){
			$hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
			$query .= ", password = :password";
		}
		$query .= " WHERE id = :id";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':username', $data['username'], PDO::PARAM_STR);
		$stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
		$stmt->bindValue(':role_id', (int) $data['role_id'], PDO::PARAM_INT);
		$stmt->bindValue(':verified', (int) ($data['verified'] ?? 0), PDO::PARAM_INT);
		$stmt->bindValue(':first_name', $data['first_name'] ?? '', PDO::PARAM_STR);
		$stmt->bindValue(':last_name', $data['last_name'] ?? '', PDO::PARAM_STR);
		$stmt->bindValue(':website', $data['website'] ?? '', PDO::PARAM_STR);
		$stmt->bindValue(':bio', $data['bio'] ?? '', PDO::PARAM_STR);
		$stmt->bindValue(':social', $data['social'] ?? '{}', PDO::PARAM_STR);
		$stmt->bindValue(':avatar', $data['avatar'] ?? '', PDO::PARAM_STR);
		$stmt->bindValue(':profile_cover', $data['profile_cover'] ?? '', PDO::PARAM_STR);
		$stmt->bindValue(':comune_id', ($data['comune_id'] ?? '') === '' ? null : (int) $data['comune_id'], PDO::PARAM_INT);
		if(!empty($data['password'])){
			$stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
		}
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		return $stmt->execute();
	}
	public function update_dashboard(int $id, array $data): bool
	{
		$comuneId = ($data['comune_id'] ?? null) === '' ? null : $data['comune_id'];

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

		$stmt->bindValue(':first_name', $data['first_name'], PDO::PARAM_STR);
		$stmt->bindValue(':last_name', $data['last_name'], PDO::PARAM_STR);
		$stmt->bindValue(':website', $data['website'], PDO::PARAM_STR);
		$stmt->bindValue(':bio', $data['bio'], PDO::PARAM_STR);
		$stmt->bindValue(':social', $data['social'], PDO::PARAM_STR);
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);

		if ($comuneId === null) {
			$stmt->bindValue(':comune_id', null, PDO::PARAM_NULL);
		} else {
			$stmt->bindValue(':comune_id', (int) $comuneId, PDO::PARAM_INT);
		}

		return $stmt->execute();
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
	public function delete($id, ?int $deletedBy = null, ?string $reason = null): bool
	{
		return $this->anonymizeAccount((int) $id, $deletedBy, $reason);
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

	public function anonymizeAccount(int $id, ?int $anonymizedBy = null, ?string $reason = null): bool
	{
		$anonymousSuffix = bin2hex(random_bytes(8));
		$anonymousUsername = 'deleted_user_' . $id . '_' . $anonymousSuffix;
		$anonymousEmail = 'deleted_user_' . $id . '_' . $anonymousSuffix . '@anon.local';

		$sql = "UPDATE users SET
				username = :username,
				email = :email,
				password = :password,
				verified = 0,
				verification_token = NULL,
				first_name = '',
				last_name = '',
				website = '',
				bio = '',
				social = '{}',
				avatar = '',
				profile_cover = '',
				cover_position_x = 50,
				cover_position_y = 50,
				comune_id = NULL,
				profile_settings = '{}',
				deactivated_at = COALESCE(deactivated_at, NOW()),
				deactivated_by = :deactivated_by,
				deactivation_reason = :deactivation_reason,
				anonymized_at = NOW(),
				anonymized_by = :anonymized_by,
				account_deletion_requested_at = COALESCE(account_deletion_requested_at, NOW()),
				account_deletion_reason = :account_deletion_reason,
				updated_at = NOW()
			WHERE id = :id
			  AND anonymized_at IS NULL";

		$stmt = $this->db->prepare($sql);

		return $stmt->execute([
			'username' => $anonymousUsername,
			'email' => $anonymousEmail,
			'password' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
			'deactivated_by' => $anonymizedBy,
			'deactivation_reason' => $reason,
			'anonymized_by' => $anonymizedBy,
			'account_deletion_reason' => $reason,
			'id' => $id,
		]);
	}

	public function deactivateAccount(int $id, ?int $deactivatedBy = null, ?string $reason = null): bool
	{
		$sql = "UPDATE users SET
				verified = 0,
				verification_token = NULL,
				deactivated_at = COALESCE(deactivated_at, NOW()),
				deactivated_by = :deactivated_by,
				deactivation_reason = :deactivation_reason,
				updated_at = NOW()
			WHERE id = :id
			  AND anonymized_at IS NULL
			  AND deactivated_at IS NULL";

		$stmt = $this->db->prepare($sql);

		return $stmt->execute([
			'deactivated_by' => $deactivatedBy,
			'deactivation_reason' => $reason,
			'id' => $id,
		]);
	}
}
