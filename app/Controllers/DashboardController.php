<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use Exception;

class DashboardController extends Controller
{
	private $userModel;
	private $uploadDir = APP_ROOT . '/public_assets/uploads/avatar/';

	public function __construct()
	{
		$this->userModel = new User();
		if (!isset($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
		// Middleware: deve essere loggato
		//$this->middleware('AuthMiddleware');
	}

	public function overview()
	{
		$userId = $_SESSION['user_id'];
		$user = $this->userModel->find($userId);

		$this->view('dashboard/overview', [
			'user' => $user
		], 'dashboard'); // 🔥 layout custom
	}

	public function profile()
	{
		$userId = $_SESSION['user_id'] ?? null;
		$user = $this->userModel->find($userId);

		$this->view('dashboard/profile', [
			'user' => $user
		], 'dashboard');
	}

	public function updateProfile()
	{
		if (!$this->isValidCsrfToken()) {
			$this->view('dashboard/profile', [
				'error' => 'Token CSRF non valido.',
			], 'dashboard');
			return;
		}

		$userId = $_SESSION['user_id'] ?? null;
		if (!$userId) {
			header('Location: /login');
		}

		$errors = [];

		$firstName  = trim($_POST['first_name'] ?? '');
		$lastName   = trim($_POST['last_name'] ?? '');
		$website    = trim($_POST['website'] ?? '');
		$bio        = trim($_POST['bio'] ?? '');
		$social = $_POST['social'] ?? [];
		$socialJson = json_encode($social, JSON_UNESCAPED_UNICODE);
		$comune_id = trim($_POST['comune_id'] ?? '');

		// VALIDAZIONI

		if ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
			$errors[] = 'URL sito non valido.';
		}

		if (!empty($errors)) {
			$this->view('dashboard/profile', [
				'error' => implode(' ', $errors),
			], 'dashboard');
		}

		// UPDATE
		$userModel = new User();

		$userModel->update_dashboard($userId, [
			'first_name' => $firstName,
			'last_name'  => $lastName,
			'website'    => $website,
			'bio'        => $bio,
			'social'     => $socialJson,
			'comune_id'  => $comune_id
		]);
		$user = $this->userModel->find($userId);
		$this->view('dashboard/profile', [
			'success' => 'Profilo aggiornato con successo.',
			'user' => $user
		], 'dashboard');
	}

	public function changePassword()
	{
		$userId = $_SESSION['user_id'] ?? null;
		$user = $this->userModel->find($userId);

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			if (!$this->isValidCsrfToken()) {
				$this->view('dashboard/change_password', [
					'user' => $user,
					'errors' => ['Sessione scaduta o richiesta non valida. Ricarica la pagina e riprova.'],
					'csrf_token' => $_SESSION['csrf_token']
				], 'dashboard');
				return;
			}

			$current = $_POST['current_password'] ?? '';
			$new     = $_POST['new_password'] ?? '';
			$confirm = $_POST['confirm_password'] ?? '';

			$errors = [];

			if (!$user || !password_verify($current, $user['password'])) {
				$errors[] = "Password attuale non corretta.";
			}
			if (strlen($new) < 8) {
				$errors[] = "La nuova password deve essere di almeno 8 caratteri.";
			}
			if ($new !== $confirm) {
				$errors[] = "La conferma della password non corrisponde.";
			}

			if (empty($errors)) {
				$this->userModel->update_dashboard_password($userId, [
					'password' => password_hash($new, PASSWORD_DEFAULT)
				]);
				$this->view('dashboard/change_password', [
					'success' => 'Password aggiornata con successo.',
					'user' => $user,
					'csrf_token' => $_SESSION['csrf_token']
				], 'dashboard');
				return;
			} else {
				$this->view('dashboard/change_password', [
					'user' => $user,
					'errors' => $errors,
					'csrf_token' => $_SESSION['csrf_token']
				], 'dashboard');
				return;
			}
		}

		// GET → mostra il form
		$this->view('dashboard/change_password', [
			'user' => $user,
			'csrf_token' => $_SESSION['csrf_token']
		], 'dashboard');
	}

	public function avatar()
	{
		$userId = $_SESSION['user_id'] ?? null;
		$user = $this->userModel->find($userId);
		$this->view('dashboard/avatar', [
			'user' => $user,
		], 'dashboard');
	}
	public function updateAvatar(){
		$userId = $_SESSION['user_id'] ?? null;
		$user = $this->userModel->find($userId);
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			if (!$this->isValidCsrfToken()) {
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Richiesta non valida. Ricarica la pagina e riprova.']);
				exit();
			}

			if (!empty($_FILES['avatar'])) {
				$newAvatarPath = $this->handleImageUpload($_FILES['avatar'], $user['avatar']);

				if ($newAvatarPath) {
					$this->userModel->update_dashboard_avatar($userId, [
						'avatar' => $newAvatarPath
					]);
					$user = $this->userModel->find($userId);
					echo json_encode(['success' => true, 'avatarUrl' => $user['avatar']]);
					exit();
				}
				// Se handleImageUpload ha dato errore, viene già settato il flash
			} else {
				echo json_encode(['success' => false, 'message' => 'errore']);
				exit();
			}
		}
	}

	/**
	 * Gestisce l'upload di un'immagine.
	 * @param array $file Il file caricato da $_FILES.
	 * @param string|null $currentImagePath Il percorso dell'immagine corrente (per la cancellazione).
	 * @return string|null Il percorso relativo dell'immagine caricata o null in caso di errore/nessun upload.
	 */
	private function handleImageUpload($file, $currentImagePath = null)
	{
		if ($file['error'] === UPLOAD_ERR_NO_FILE) {
			// Nessun file caricato, mantieni l'immagine esistente se presente
			return $currentImagePath;
		}

		if ($file['error'] !== UPLOAD_ERR_OK) {
			Session::setFlash('error', 'Errore durante l\'upload del file: ' . $file['error']);
			return null;
		}

		$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
		$maxSize = 5 * 1024 * 1024; // 5 MB

		if (!in_array($file['type'], $allowedTypes)) {
			Session::setFlash('error', 'Tipo di file non consentito. Sono ammessi solo JPEG, PNG, GIF, WEBP.');
			return null;
		}

		if ($file['size'] > $maxSize) {
			Session::setFlash('error', 'Il file è troppo grande. Dimensione massima consentita: 5MB.');
			return null;
		}

		// Crea il nome del file
		$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$baseFileName = uniqid('avatar_');
		$originalFileName = $baseFileName . '.' . $fileExtension;
		$webpFileName = $baseFileName . '.webp';

		$destinationPath = $this->uploadDir . $originalFileName;
		$webpPath = $this->uploadDir . $webpFileName;

		// Percorsi relativi per il database
		$relativeWebpPath = '/public_assets/uploads/avatar/' . $webpFileName;

		// Sposta l'immagine originale
		if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
			Session::setFlash('error', 'Impossibile spostare il file caricato.');
			return null;
		}
		$this->processAvatar($destinationPath, $destinationPath);
		// Converti in WebP
		if (!$this->convertToWebP($destinationPath, $webpPath)) {
			Session::setFlash('error', 'Errore nella conversione in WebP. L\'immagine originale è stata salvata.');
			return '/public_assets/uploads/events/' . $originalFileName;
		}

		// Elimina immagine precedente, se presente
		if ($currentImagePath && file_exists(APP_ROOT . $currentImagePath)) {
			unlink(APP_ROOT . $currentImagePath);
		}

		// (Opzionale) elimina anche l'originale se vuoi mantenere solo il WebP
		// unlink($destinationPath);

		return $relativeWebpPath;
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
		$srcX = (int) floor(($width - $side) / 2);
		$srcY = (int) floor(($height - $side) / 2);

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
	/**
	 * Converte un’immagine in formato WebP usando GD
	 */
	private function convertToWebP(string $source, string $destination, int $quality = 80): bool
	{
		if (!file_exists($source)) {
			return false;
		}

		$info = getimagesize($source);
		if ($info === false) {
			return false;
		}

		$mime = $info['mime'];
		switch ($mime) {
			case 'image/jpeg':
				$image = imagecreatefromjpeg($source);
				break;
			case 'image/png':
				$image = imagecreatefrompng($source);
				imagepalettetotruecolor($image);
				imagealphablending($image, true);
				imagesavealpha($image, true);
				break;
			case 'image/gif':
				$image = imagecreatefromgif($source);
				break;
			case 'image/webp':
				// Già in formato webp
				copy($source, $destination);
				return true;
			default:
				return false;
		}

		$result = imagewebp($image, $destination, $quality);
		imagedestroy($image);
		return $result;
	}

	public function cover()
	{

		$userId = $_SESSION['user_id'];
		$user = $this->userModel->find($userId);

		$this->view('dashboard/cover', [
			'user' => $user,
		], 'dashboard');
	}
	public function updateCover()
	{
		error_log(print_r($_FILES, true));
		error_log(print_r($_POST, true));

		header('Content-Type: application/json');

		if (!$this->isValidCsrfToken()) {
			echo json_encode([
				'success' => false,
				'message' => 'Richiesta non valida. Ricarica la pagina e riprova.'
			]);
			exit;
		}

		$userId = $_SESSION['user_id'] ?? null;

		if (!$userId) {
			echo json_encode([
				'success' => false,
				'message' => 'Utente non autenticato'
			]);
			exit;
		}

		$user = $this->userModel->find($userId);

		$positionX = (int)($_POST['cover_position_x'] ?? 50);
		$positionY = (int)($_POST['cover_position_y'] ?? 50);

		$uploaded = false;
		$newCoverPath = null;

		// 1. UPLOAD FILE
		if (
			isset($_FILES['cover']) &&
			$_FILES['cover']['error'] === UPLOAD_ERR_OK
		) {
			$newCoverPath = $this->handleCoverUpload(
				$_FILES['cover'],
				$user['profile_cover'] ?? null
			);

			if ($newCoverPath) {
				$uploaded = true;
			} else {
				echo json_encode([
					'success' => false,
					'message' => 'Errore upload cover'
				]);
				exit;
			}
		}

		// 2. UPDATE DB
		$this->userModel->update_dashboard_cover($userId, [
			'profile_cover' => $newCoverPath ?? $user['profile_cover'],
			'cover_position_x' => $positionX,
			'cover_position_y' => $positionY
		]);

		// 3. RESPONSE SEMPRE
		echo json_encode([
			'success' => true,
			'message' => $uploaded ? 'Cover aggiornata' : 'Posizione aggiornata',
			'coverUrl' => $newCoverPath ?? $user['profile_cover'],
			'x' => $positionX,
			'y' => $positionY
		]);

		exit;
	}
	private function handleCoverUpload($file, $currentImagePath = null)
	{
		error_log('COVER FILE: ' . print_r($file, true));
		if ($file['error'] !== UPLOAD_ERR_OK) {
			return null;
		}

		$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
		$maxSize = 5 * 1024 * 1024;

		if (!in_array($file['type'], $allowedTypes)) return null;
		if ($file['size'] > $maxSize) return null;

		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$base = uniqid('cover_');

		$original = $base . '.' . $ext;
		$webp = $base . '.webp';

		$dest = APP_ROOT . '/public_assets/uploads/covers/' . $original;
		$destWebp = APP_ROOT . '/public_assets/uploads/covers/' . $webp;

		$relative = '/public_assets/uploads/covers/' . $webp;

		if (!move_uploaded_file($file['tmp_name'], $dest)) {
			return null;
		}

		$this->convertToWebP($dest, $destWebp);

		if ($currentImagePath && file_exists(APP_ROOT . $currentImagePath)) {
			@unlink(APP_ROOT . $currentImagePath);
		}

		return $relative;
	}
	public function updateSettings()
	{
		$userId = $_SESSION['user_id'];

		if (!$this->isValidCsrfToken()) {
			$user = $this->userModel->find($userId);
			$this->view('dashboard/settings', [
				'user' => $user,
				'error' => 'Richiesta non valida. Ricarica la pagina e riprova.'
			], 'dashboard');
			return;
		}

		$settings = $_POST['settings'] ?? [];

		// normalizza checkbox (non selezionati non arrivano)
		$all = [
			'show_email' => !empty($settings['show_email']),
			'show_bio' => !empty($settings['show_bio']),
			'show_comune' => !empty($settings['show_comune']),
			'show_instagram' => !empty($settings['show_instagram']),
			'show_facebook' => !empty($settings['show_facebook']),
			'show_tiktok' => !empty($settings['show_tiktok']),
			'show_youtube' => !empty($settings['show_youtube']),
			'show_nome' => !empty($settings['show_nome']),
		];

		$this->userModel->updateProfileSettings($userId, $all);

		$user = $this->userModel->find($userId);

		$this->view('dashboard/settings', [
			'user' => $user,
			'success' => 'Impostazioni aggiornate con successo'
		], 'dashboard');
	}
	public function settings()
	{

		$userId = $_SESSION['user_id'] ?? null;
		$user = $this->userModel->find($userId);

		$this->view('dashboard/settings', [
			'user' => $user
		], 'dashboard');
	}

	private function isValidCsrfToken(): bool
	{
		return !empty($_POST['csrf_token'])
			&& !empty($_SESSION['csrf_token'])
			&& hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
	}
}
