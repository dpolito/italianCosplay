<?php
// generate_missing_region_slugs.php

// Definisci la costante APP_ROOT se non è già definita
if (!defined('APP_ROOT')) {
	define('APP_ROOT', __DIR__);
}

// Includi i file necessari
require_once APP_ROOT . '/app/config/app.php'; // Per APP_NAME, ecc.
require_once APP_ROOT . '/app/config/database.php'; // Per la configurazione del database
require_once APP_ROOT . '/app/core/Database.php'; // La classe Database
require_once APP_ROOT . '/app/models/BaseModel.php'; // La classe BaseModel
require_once APP_ROOT . '/app/models/Regione.php'; // Il modello Regione

echo "Inizio generazione slug per regioni mancanti...\n";

try {
	// Inizializza la connessione al database
	$config = require APP_ROOT . '/app/config/database.php';
	Database::getInstance($config); // Passa la configurazione al singleton

	$regioneModel = new Regione();

	// Ottieni la connessione al database tramite il metodo pubblico della BaseModel
	$dbConnection = $regioneModel->getDbConnection();

	// Recupera tutte le regioni che non hanno uno slug (o hanno uno slug vuoto)
	$stmt = $dbConnection->prepare("SELECT id, nome, slug FROM regioni WHERE slug IS NULL OR slug = ''");
	$stmt->execute();
	$regioniWithoutSlug = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$updatedCount = 0;

	if (empty($regioniWithoutSlug)) {
		echo "Nessuna regione trovata senza slug mancante.\n";
	} else {
		foreach ($regioniWithoutSlug as $regione) {
			$regioneId = $regione['id'];
			$regioneNome = $regione['nome'];

			// Genera uno slug unico basato sul nome della regione
			$baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $regioneNome), '-'));
			$newSlug = $baseSlug;
			$counter = 1;

			// Assicurati che lo slug sia unico nella tabella regioni
			while (true) {
				$checkSlugStmt = $dbConnection->prepare("SELECT COUNT(*) FROM regioni WHERE slug = :slug AND id != :id");
				$checkSlugStmt->bindParam(':slug', $newSlug, PDO::PARAM_STR);
				$checkSlugStmt->bindParam(':id', $regioneId, PDO::PARAM_INT);
				$checkSlugStmt->execute();
				$count = $checkSlugStmt->fetchColumn();

				if ($count == 0) {
					break; // Lo slug è unico
				}

				$newSlug = $baseSlug . '-' . $counter++;
			}

			// Aggiorna la regione con il nuovo slug
			$updateStmt = $dbConnection->prepare("UPDATE regioni SET slug = :slug WHERE id = :id");
			$updateStmt->bindParam(':slug', $newSlug, PDO::PARAM_STR);
			$updateStmt->bindParam(':id', $regioneId, PDO::PARAM_INT);

			if ($updateStmt->execute()) {
				echo "Aggiornata regione ID " . $regioneId . " (Nome: '" . $regioneNome . "') con slug: '" . $newSlug . "'\n";
				$updatedCount++;
			} else {
				echo "Errore nell'aggiornamento dello slug per regione ID " . $regioneId . ": " . implode(", ", $updateStmt->errorInfo()) . "\n";
			}
		}
	}

	echo "\nProcesso completato. " . $updatedCount . " regioni sono state aggiornate con uno slug.\n";

} catch (PDOException $e) {
	echo "Errore di connessione al database: " . $e->getMessage() . "\n";
} catch (Exception $e) {
	echo "Si è verificato un errore: " . $e->getMessage() . "\n";
}

?>
