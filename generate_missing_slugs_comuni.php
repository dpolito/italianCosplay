<?php
// generate_missing_slugs_comuni.php

// Definisci la costante APP_ROOT se non è già definita
if (!defined('APP_ROOT')) {
	define('APP_ROOT', __DIR__);
}

// Includi i file necessari
require_once APP_ROOT . '/app/config/app.php'; // Per APP_NAME, ecc.
require_once APP_ROOT . '/app/config/database.php'; // Per la configurazione del database
require_once APP_ROOT . '/app/core/Database.php'; // La classe Database
require_once APP_ROOT . '/app/models/BaseModel.php'; // La classe BaseModel
require_once APP_ROOT . '/app/models/Comune.php'; // Il modello Comune

echo "Inizio generazione slug per comuni mancanti...\n";

try {
	// Inizializza la connessione al database
	$config = require APP_ROOT . '/app/config/database.php';
	Database::getInstance($config); // Passa la configurazione al singleton

	$comuneModel = new Comune();

	// Ottieni la connessione al database tramite il metodo pubblico della BaseModel
	$dbConnection = $comuneModel->getDbConnection();

	// Recupera tutti i comuni che non hanno uno slug (o hanno uno slug vuoto)
	$stmt = $dbConnection->prepare("SELECT id, nome, slug FROM comuni ");
	$stmt->execute();
	$comuniWithoutSlug = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$updatedCount = 0;

	if (empty($comuniWithoutSlug)) {
		echo "Nessun comune trovato senza slug mancante.\n";
	} else {
		foreach ($comuniWithoutSlug as $comune) {
			$comuneId = $comune['id'];
			$comuneNome = $comune['nome'];

			// Genera uno slug unico riutilizzando la logica simile a quella nel modello Comune
			// (Replicato qui perché generateUniqueSlug è privato nel modello)
			$baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $comuneNome), '-'));
			$newSlug = $baseSlug;
			$counter = 1;

			while (true) {
				$checkSlugStmt = $dbConnection->prepare("SELECT COUNT(*) FROM comuni WHERE slug = :slug AND id != :id");
				$checkSlugStmt->bindParam(':slug', $newSlug, PDO::PARAM_STR);
				$checkSlugStmt->bindParam(':id', $comuneId, PDO::PARAM_INT);
				$checkSlugStmt->execute();
				$count = $checkSlugStmt->fetchColumn();

				if ($count == 0) {
					break; // Lo slug è unico
				}

				$newSlug = $baseSlug . '-' . $counter++;
			}

			// Aggiorna il comune con il nuovo slug
			$updateStmt = $dbConnection->prepare("UPDATE comuni SET slug = :slug WHERE id = :id");
			$updateStmt->bindParam(':slug', $newSlug, PDO::PARAM_STR);
			$updateStmt->bindParam(':id', $comuneId, PDO::PARAM_INT);

			if ($updateStmt->execute()) {
				echo "Aggiornato comune ID " . $comuneId . " (Nome: '" . $comuneNome . "') con slug: '" . $newSlug . "'\n";
				$updatedCount++;
			} else {
				echo "Errore nell'aggiornamento dello slug per comune ID " . $comuneId . ": " . implode(", ", $updateStmt->errorInfo()) . "\n";
			}
		}
	}

	echo "\nProcesso completato. " . $updatedCount . " comuni sono stati aggiornati con uno slug.\n";

} catch (PDOException $e) {
	echo "Errore di connessione al database: " . $e->getMessage() . "\n";
} catch (Exception $e) {
	echo "Si è verificato un errore: " . $e->getMessage() . "\n";
}

?>
