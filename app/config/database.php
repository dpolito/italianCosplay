	<?php
	// Normalmente si usano variabili d'ambiente per credenziali sensibili
	// Per semplicità, qui sono dirette, ma in produzione si userebbero file .env e getenv()
	return [
		'host'     => 'italiancosplay_gemini_db',
		'dbname'   => 'italiancosplay_gemini',
		'user'     => 'root',
		'password' => 'rootpassword123', // Inserisci la tua password di MySQL
		'charset'  => 'utf8mb4'
	];
