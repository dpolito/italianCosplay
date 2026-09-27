<?php

use App\Core\Database;

if (php_sapi_name() !== 'cli') {
	exit("CLI only");
}

if (!defined('APP_ROOT')) {
	define('APP_ROOT', dirname(__DIR__));
}

function import(){
	// Includi i file necessari
	$autoloadPath = APP_ROOT . '/vendor/autoload.php';
	if (file_exists($autoloadPath)) {
		require_once $autoloadPath;
	}
	require_once APP_ROOT . '/app/bootstrap/env.php';
	require_once APP_ROOT . '/app/config/app.php';
	require_once APP_ROOT . '/app/config/database.php';

	$config = require APP_ROOT . '/app/config/database.php';
	$db = Database::getInstance($config)->getConnection();

	// Recupera la pagina dal DB
	$stmt = $db->query("SELECT last_page FROM anilist_sync WHERE id = 1");
	$page = (int)$stmt->fetchColumn();

	// Query GraphQL
	$query = '
query ($page: Int) {
  Page(page: $page, perPage: 10) {
    pageInfo {
      hasNextPage
    }
    media(type: ANIME, sort: POPULARITY_DESC) {
      id
      title { romaji english native }
      description
      episodes
      format
      season
      seasonYear
      status
      averageScore
      popularity
      coverImage { large }
      bannerImage
      characters(perPage: 10) {
        edges {
          role
          node {
            id
            name { full native }
            image { large }
          }
        }
      }
    }
  }
}';

	$variables = ['page' => $page];

	// cURL
	$ch = curl_init("https://graphql.anilist.co");
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
		CURLOPT_POSTFIELDS => json_encode(['query'=>$query,'variables'=>$variables]),
	]);

	$response = curl_exec($ch);
	if(curl_errno($ch)) {
		echo "cURL error: ".curl_error($ch).PHP_EOL;
		exit;
	}
	curl_close($ch);

	$data = json_decode($response, true);
	if(!isset($data['data']['Page']['media'])) {
		echo "Errore: dati non ricevuti da AniList".PHP_EOL;
		exit;
	}

	// Import anime e personaggi
	foreach ($data['data']['Page']['media'] as $anime) {
		// Inserimento anime
		$stmt = $db->prepare("
        INSERT INTO anilist_anime 
        (anilist_id,title_romaji,title_english,title_native,description,episodes,format,season,season_year,status,average_score,popularity,cover_large,banner_image,created_at,updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())
        ON DUPLICATE KEY UPDATE
            title_romaji=VALUES(title_romaji),
            title_english=VALUES(title_english),
            episodes=VALUES(episodes),
            updated_at=NOW()
    ");
		$stmt->execute([
			$anime['id'],
			$anime['title']['romaji'] ?? null,
			$anime['title']['english'] ?? null,
			$anime['title']['native'] ?? null,
			$anime['description'] ?? null,
			$anime['episodes'] ?? null,
			$anime['format'] ?? null,
			$anime['season'] ?? null,
			$anime['seasonYear'] ?? null,
			$anime['status'] ?? null,
			$anime['averageScore'] ?? null,
			$anime['popularity'] ?? null,
			$anime['coverImage']['large'] ?? null,
			$anime['bannerImage'] ?? null,
		]);

		// Recupero ID corretto (anime_id)
		$stmt2 = $db->prepare("SELECT id FROM anilist_anime WHERE anilist_id = ?");
		$stmt2->execute([$anime['id']]);
		$animeId = (int)$stmt2->fetchColumn();

		// Personaggi
		foreach ($anime['characters']['edges'] as $edge) {
			$char = $edge['node'];
			$stmt = $db->prepare("
            INSERT INTO anilist_characters
            (anilist_id,name_full,name_native,image_large,created_at,updated_at)
            VALUES (?,?,?,?,NOW(),NOW())
            ON DUPLICATE KEY UPDATE
                name_full=VALUES(name_full),
                updated_at=NOW()
        ");
			$stmt->execute([
				$char['id'],
				$char['name']['full'] ?? null,
				$char['name']['native'] ?? null,
				$char['image']['large'] ?? null,
			]);

			// Recupero ID corretto (character_id)
			$stmt2 = $db->prepare("SELECT id FROM anilist_characters WHERE anilist_id = ?");
			$stmt2->execute([$char['id']]);
			$characterId = (int)$stmt2->fetchColumn();

			// Collegamento anime ↔ personaggio
			$stmt = $db->prepare("
            INSERT IGNORE INTO anilist_anime_character
            (anime_id, character_id, role)
            VALUES (?,?,?)
        ");
			$stmt->execute([$animeId, $characterId, $edge['role'] ?? null]);
		}
	}

	// Aggiorna la pagina
	$hasNext = $data['data']['Page']['pageInfo']['hasNextPage'] ?? false;
	$nextPage = $hasNext ? $page + 1 : 1;

	$stmt = $db->prepare("UPDATE anilist_sync SET last_page = ? WHERE id = 1");
	$stmt->execute([$nextPage]);

	echo "Pagina $page importata, prossima pagina: $nextPage".PHP_EOL;
}
import();
