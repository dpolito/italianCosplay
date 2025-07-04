<!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo APP_NAME; ?> - Dashboard Admin</title>
	<!-- Inclusione di Tailwind CSS via CDN -->
	<script src="https://cdn.tailwindcss.com"></script>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<style>
		body {
			font-family: 'Inter', sans-serif;
			background-color: #f3f4f6; /* Sfondo leggermente grigio */
			color: #333;
		}
		/* Stili per i messaggi flash (copiati da admin-dashboard-ui) */
		.flash-message {
			padding: 1rem;
			margin-bottom: 1.5rem;
			border-radius: 0.5rem;
			font-weight: 500;
		}
		.flash-message.success {
			background-color: #d4edda; /* Verde chiaro */
			color: #155724; /* Verde scuro */
			border: 1px solid #c3e6cb;
		}
		.flash-message.error {
			background-color: #f8d7da; /* Rosso chiaro */
			color: #721c24; /* Rosso scuro */
			border: 1px solid #f5c6cb;
		}
		.flash-message.info {
			background-color: #d1ecf1; /* Blu chiaro */
			color: #0c5460; /* Blu scuro */
			border: 1px solid #bee5eb;
		}
	</style>
</head>
<body class="flex flex-col min-h-screen">
<header class="bg-blue-600 text-white p-4 shadow-md">
	<div class="container mx-auto flex justify-between items-center">
		<h1 class="text-2xl font-bold">Dashboard Amministratore</h1>
		<nav>
			<a href="/logout" class="bg-blue-700 hover:bg-blue-800 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out">Logout</a>
		</nav>
	</div>
</header>
<main class="flex-grow container mx-auto p-6">
<?php
// Qui verrà incluso il contenuto della vista specifica (ad es. la dashboard)
// $content_for_layout è una variabile che il tuo Controller dovrebbe passare
// o che il tuo sistema di rendering delle viste dovrebbe gestire.
// Se il tuo metodo view() include direttamente il file della vista,
// allora il contenuto della vista sarà semplicemente qui.
if (isset($content_for_layout)) {
	echo $content_for_layout;
} else {
	// Se non usi $content_for_layout, la tua vista verrà inclusa qui.
	// Ad esempio, se il tuo Controller::view() fa un 'require' del file della vista.
	// Non è necessario un require_once qui, poiché il Controller gestirà l'inclusione.
}
?>
</main>
<footer class="bg-gray-800 text-white p-4 text-center mt-8">
	<p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Tutti i diritti riservati.</p>
</footer>
</body>
</html>
