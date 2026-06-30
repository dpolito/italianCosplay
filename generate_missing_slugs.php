<?php
// generate_missing_slugs.php

// Definisci la costante APP_ROOT se non è già definita
if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
}

// Includi i file necessari
// Ho modificato i percorsi per includere 'app/' prima di 'config/'
require_once APP_ROOT . '/app/config/app.php'; // Per APP_NAME, ecc.
require_once APP_ROOT . '/app/config/database.php'; // Per la configurazione del database
require_once APP_ROOT . '/app/core/Database.php'; // La classe Database
require_once APP_ROOT . '/app/models/BaseModel.php'; // La classe BaseModel
require_once APP_ROOT . '/app/models/Event.php'; // Il modello Event

echo "Inizio generazione slug per eventi mancanti...\n";

try {
    // Inizializza la connessione al database
    // Ho modificato il percorso per includere 'app/' prima di 'config/'
    $config = require APP_ROOT . '/app/config/database.php';
    Database::getInstance($config); // Passa la configurazione al singleton

    $eventModel = new Event();

    // Ottieni la connessione al database tramite il nuovo metodo pubblico
    $dbConnection = $eventModel->getDbConnection();

    // Recupera tutti gli eventi che non hanno uno slug (o hanno uno slug vuoto)
    $stmt = $dbConnection->prepare("SELECT id, titolo, slug FROM events WHERE slug IS NULL OR slug = ''");
    $stmt->execute();
    $eventsWithoutSlug = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updatedCount = 0;

    if (empty($eventsWithoutSlug)) {
        echo "Nessun evento trovato senza slug mancante.\n";
    } else {
        foreach ($eventsWithoutSlug as $event) {
            $eventId = $event['id'];
            $eventTitle = $event['titolo'];

            // Genera uno slug unico riutilizzando la logica del modello Event
            // Nota: generateUniqueSlug è un metodo privato, quindi lo replichiamo qui o lo rendiamo pubblico
            // Per semplicità e per non modificare il modello per un'operazione una tantum, replichiamo la logica essenziale.
            // In un'applicazione più grande, potresti creare un metodo pubblico nel modello o un servizio.

            $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $eventTitle), '-'));
            $newSlug = $baseSlug;
            $counter = 1;

            while (true) {
                $checkSlugStmt = $dbConnection->prepare("SELECT COUNT(*) FROM events WHERE slug = :slug AND id != :id");
                $checkSlugStmt->bindParam(':slug', $newSlug, PDO::PARAM_STR);
                $checkSlugStmt->bindParam(':id', $eventId, PDO::PARAM_INT);
                $checkSlugStmt->execute();
                $count = $checkSlugStmt->fetchColumn();

                if ($count == 0) {
                    break; // Lo slug è unico
                }

                $newSlug = $baseSlug . '-' . $counter++;
            }

            // Aggiorna l'evento con il nuovo slug
            $updateStmt = $dbConnection->prepare("UPDATE events SET slug = :slug WHERE id = :id");
            $updateStmt->bindParam(':slug', $newSlug, PDO::PARAM_STR);
            $updateStmt->bindParam(':id', $eventId, PDO::PARAM_INT);

            if ($updateStmt->execute()) {
                echo "Aggiornato evento ID " . $eventId . " (Titolo: '" . $eventTitle . "') con slug: '" . $newSlug . "'\n";
                $updatedCount++;
            } else {
                echo "Errore nell'aggiornamento dello slug per evento ID " . $eventId . ": " . implode(", ", $updateStmt->errorInfo()) . "\n";
            }
        }
    }

    echo "\nProcesso completato. " . $updatedCount . " eventi sono stati aggiornati con uno slug.\n";

} catch (PDOException $e) {
    echo "Errore di connessione al database: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Si è verificato un errore: " . $e->getMessage() . "\n";
}

?>
