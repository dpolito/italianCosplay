<?php
namespace App\Core;
use App\Services\SiteFeatureFlagService;
use App\Models\user;
use function file_exists;
use function var_dump;

class Controller
{
	private static ?array $featureFlagCache = null;

	protected function userId(): ?int
	{
		return $_SESSION['user_id'] ?? null;
	}

	protected function featureEnabled(string $flagKey): bool
	{
		if (self::$featureFlagCache === null) {
			self::$featureFlagCache = (new SiteFeatureFlagService())->getEnabledMap();
		}

		return !empty(self::$featureFlagCache[$flagKey]);
	}

	protected function requireFeature(string $flagKey, string $message = 'Funzionalità temporaneamente disattivata.'): void
	{
		if ($this->featureEnabled($flagKey)) {
			return;
		}

		Session::setFlash('error', $message);
		header('Location: /');
		exit();
	}
	protected function requirePermission(string $permission): void
	{
		// ✅ utente loggato?
		$userId = $_SESSION['user_id'] ?? null;

		if (!$userId) {
			Session::setFlash('error', 'Devi effettuare il login.');
			header('Location: /login');
			exit();
		}

		// ✅ carica utente dal DB
		$userModel = new User();

		if (!$userModel->hasPermission($userId, $permission)) {
			header('HTTP/1.1 403 Forbidden');
			die('Accesso negato');
		}
	}


	/**
	 * Carica una vista e la inserisce in un layout.
	 *
	 * @param string $view Il percorso della vista da caricare (es. 'home/index').
	 * @param array $data Un array associativo di dati da passare alla vista.
	 * @param string $layout Il nome del layout da usare (es. 'default', 'admin').
	 */
	protected function view($view, $data = [], $layout = 'default')
	{
		// Rende le variabili dell'array $data disponibili nella vista
		extract($data);

		$content_for_layout = ''; // Inizializza a stringa vuota per garantire che sia sempre definita

		// Costruisce il percorso completo del file della vista.
		$viewPath = APP_ROOT . '/app/views/' . $view . '.php';

		// Inizia l'output buffering per catturare il contenuto della vista specifica.
		ob_start();

		// Verifica se il file della vista esiste prima di includerlo.
		if (file_exists($viewPath)) {
			require_once $viewPath;
		} else {
			// Gestione dell'errore se la vista non esiste.
			// Logga l'errore dettagliato lato server per il debug.
			error_log("Errore Controller: File vista non trovato: " . $viewPath);
			// Mostra un messaggio di errore generico all'utente all'interno del contenuto.
			echo "<h1>Errore: Vista non trovata!</h1><p>Il file della vista " . htmlspecialchars($view) . ".php non esiste o non è accessibile.</p>";
			// Non usiamo 'return;' qui, in modo che ob_get_clean() catturi questo messaggio.
		}

		// Cattura il contenuto del buffer e lo salva nella variabile $content_for_layout.
		// Questa variabile sarà poi accessibile all'interno del file di layout.
		$content_for_layout = ob_get_clean();

		// Costruisce il percorso completo del file di layout.

		$layoutPath = APP_ROOT . '/app/views/layouts/' . $layout . '.php';

		// Verifica se il file di layout esiste prima di includerlo.
		if (file_exists($layoutPath)) {
			// Se il layout esiste, lo includiamo. Il layout userà $content_for_layout.
			require_once $layoutPath;
		} else {
			// Gestione dell'errore se il layout non esiste.
			error_log("Errore Controller: File layout non trovato: " . $layoutPath);
			// Se il layout è mancante, stampiamo direttamente il contenuto della vista (o l'errore della vista).
			echo "<h1>Errore: Layout non trovato!</h1><h2 style='color: red;'>Il file di layout " . htmlspecialchars($layout) . ".php non esiste.</h2>";
			echo $content_for_layout; // Mostra il contenuto della vista anche senza layout
		}
	}
}
