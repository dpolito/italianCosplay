<?php
// generate_missing_province_slugs.php

// Definisci la costante APP_ROOT se non è già definita
if (!defined('APP_ROOT')) {
	define('APP_ROOT', __DIR__);
}

// Includi i file necessari
require_once APP_ROOT . '/app/config/app.php'; // Per APP_NAME, ecc.
require_once APP_ROOT . '/app/config/database.php'; // Per la configurazione del database
require_once APP_ROOT . '/app/core/Database.php'; // La classe Database
require_once APP_ROOT . '/app/models/BaseModel.php'; // La classe BaseModel
require_once APP_ROOT . '/app/models/Provincia.php'; // Il modello Provincia

echo "Inizio generazione slug per province mancanti...\n";

try {
	// Inizializza la connessione al database
	$config = require APP_ROOT . '/app/config/database.php';
	Database::getInstance($config); // Passa la configurazione al singleton

	$provinciaModel = new Provincia();

	// Ottieni la connessione al database tramite il metodo pubblico della BaseModel
	$dbConnection = $provinciaModel->getDbConnection();

	// Recupera tutte le province che non hanno uno slug (o hanno uno slug vuoto)
	$stmt = $dbConnection->prepare("SELECT id, nome, slug FROM province WHERE slug IS NULL OR slug = ''");
	$stmt->execute();
	$provinceWithoutSlug = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$updatedCount = 0;

	if (empty($provinceWithoutSlug)) {
		echo "Nessuna provincia trovata senza slug mancante.\n";
	} else {
		foreach ($provinceWithoutSlug as $provincia) {
			$provinciaId = $provincia['id'];
			$provinciaNome = $provincia['nome'];

			// Genera uno slug unico basato sul nome della provincia
			$baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $provinciaNome), '-'));
			$newSlug = $baseSlug;
			$counter = 1;

			// Assicurati che lo slug sia unico nella tabella province
			while (true) {
				$checkSlugStmt = $dbConnection->prepare("SELECT COUNT(*) FROM province WHERE slug = :slug AND id != :id");
				$checkSlugStmt->bindParam(':slug', $newSlug, PDO::PARAM_STR);
				$checkSlugStmt->bindParam(':id', $provinciaId, PDO::PARAM_INT);
				$checkSlugStmt->execute();
				$count = $checkSlugStmt->fetchColumn();

				if ($count == 0) {
					break; // Lo slug è unico
				}

				$newSlug = $baseSlug . '-' . $counter++;
			}

			// Aggiorna la provincia con il nuovo slug
			$updateStmt = $dbConnection->prepare("UPDATE province SET slug = :slug WHERE id = :id");
			$updateStmt->bindParam(':slug', $newSlug, PDO::PARAM_STR);
			$updateStmt->bindParam(':id', $provinciaId, PDO::PARAM_INT);

			if ($updateStmt->execute()) {
				echo "Aggiornata provincia ID " . $provinciaId . " (Nome: '" . $provinciaNome . "') con slug: '" . $newSlug . "'\n";
				$updatedCount++;
			} else {
				echo "Errore nell'aggiornamento dello slug per provincia ID " . $provinciaId . ": " . implode(", ", $updateStmt->errorInfo()) . "\n";
			}
		}
	}

	echo "\nProcesso completato. " . $updatedCount . " province sono state aggiornate con uno slug.\n";

} catch (PDOException $e) {
	echo "Errore di connessione al database: " . $e->getMessage() . "\n";
} catch (Exception $e) {
	echo "Si è verificato un errore: " . $e->getMessage() . "\n";
}

?>
