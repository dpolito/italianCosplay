<?php
// generate_missing_province_slugs.php
// Definisci la costante APP_ROOT se non è già definita
if(!defined('APP_ROOT')){
	define('APP_ROOT', __DIR__);
}
// Includi i file necessari
require_once APP_ROOT . '/app/config/app.php'; // Per APP_NAME, ecc.
require_once APP_ROOT . '/app/config/database.php'; // Per la configurazione del database
require_once APP_ROOT . '/app/core/Database.php'; // La classe Database
require_once APP_ROOT . '/app/models/BaseModel.php'; // La classe BaseModel
require_once APP_ROOT . '/app/models/Provincia.php'; // Il modello Provincia
require_once APP_ROOT . '/app/models/Comune.php'; // Il modello Provincia
require_once APP_ROOT . '/app/models/Event.php'; // Il modello Provincia
echo "Inizio importazione eventi...\n";
try{
	// Inizializza la connessione al database
	$config = require APP_ROOT . '/app/config/database.php';
	Database::getInstance($config); // Passa la configurazione al singleton
	$provinciaModel = new Provincia();
	$comuniModel = new Comune();
	$eventModel = new Event();

	echo "\nleggo il json.\n";
	$json = file_get_contents("json.txt");
	// Decodifica in array associativo
	$data = json_decode($json, true);
	// Controllo errori di parsing
	if($data === null){
		die("Errore nel parsing del JSON");
	}
	// Scorro tutti gli eventi
	foreach($data["eventi"] as $evento){
		$comune_id = null;
		$provincia_id = null;
		$regione_id = null;
		if($evento["COMUNEIT"] != null){
			$comune = $comuniModel->findByNome($evento["COMUNEIT"]);
			if($comune != null){
				$comune_id = $comune['id'];
				$provincia_id = $comune['provincia_id'];
				$regione_id = $provinciaModel->find($provincia_id)['regione_id'];
			}
		}
		$slug = $eventModel->generateUniqueSlug($evento["titolo"]);

		$evento_import = [
			'titolo'           => $evento["titolo"],
			'descrizione'      => $evento["descrizione"],
			'data_inizio'      => $evento["data_dal"] ?? null,
			'data_fine'        => $evento["data_al"] ?? null, // Usa null se vuoto
			'luogo'            => $evento["COMUNEIT"] ?? '',
			'regione_id'       => $regione_id,
			'provincia_id'     => $provincia_id,
			'comune_id'        => $comune_id,
			'latitudine'       => $evento["latitudine"] ?? null,
			'longitudine'      => $evento["longitudine"] ?? null,
			'sito_web'         => $evento["sito_web"] ?? null,
			'social_facebook'  => $evento["facebook"] ?? null,
			'social_twitter'   => null,
			'social_instagram' => null,
			'social_tiktok'    => null,
			'social_youtube'   => null,
			'tipo_evento_id'   => 1,
			'immagine'         => null,
			'approvato'        => 1,
			'slug'             => $slug,
		];

		if($evento["data_dal"] != null){
			var_dump($evento);
			$eventModel->create($evento_import);
		}


		echo "\nevento ".$evento["titolo"]." creato \n";
		echo "<hr>";
	}
	echo "\nProcesso completato. gli eventi sono stati importati.\n";
}catch(PDOException $e){
	echo "Errore di connessione al database: " . $e->getMessage() . "\n";
}catch(Exception $e){
	echo "Si è verificato un errore: " . $e->getMessage() . "\n";
}
?>
