<?php
// Questo file è ora un frammento di HTML e deve essere incluso in un layout.
// Non contiene i tag <html>, <head>, <body> completi.
// Le risorse CSS (Tailwind) e i tag base devono essere nel layout che lo include.
?>


<main class="flex-grow container mx-auto p-6">
	<div class="bg-white rounded-lg shadow-lg p-8 mb-6">
		<h2 class="text-3xl font-semibold text-gray-800 mb-4">Benvenuto, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>!</h2>
		<p class="text-gray-600">Da qui puoi gestire le diverse sezioni del tuo sito.</p>
	</div>

	<?php
	// Visualizza i messaggi flash
	if (isset($_SESSION['flash_messages'])) {
		foreach ($_SESSION['flash_messages'] as $type => $message) {
			echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
		}
		unset($_SESSION['flash_messages']); // Pulisci i messaggi flash dopo averli visualizzati
	}
	?>

	<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
		<!-- Card Gestione Utenti -->
		<div class="bg-white rounded-lg shadow-lg p-6 flex flex-col items-center text-center">
			<div class="text-blue-500 mb-4">
				<!-- Icona di esempio per gli utenti (potresti usare Font Awesome o Lucide React) -->
				<svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-3-3H5a3 3 0 00-3 3v2h5m0 0l3-3m-3 3l-3-3m3 3v-2.5M17 11V9a2 2 0 00-2-2H9a2 2 0 00-2 2v2m9 6h-6v-3h6v3z" />
				</svg>
			</div>
			<h3 class="text-xl font-semibold text-gray-700 mb-3">Gestione Utenti</h3>
			<p class="text-gray-600 mb-4">Visualizza, crea, modifica ed elimina gli utenti del sistema.</p>
			<a href="/admin/users" class="mt-auto bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out">Vai a Gestione Utenti</a>
		</div>
		<!-- Card Gestione Eventi -->
		<div class="bg-white rounded-lg shadow-lg p-6 flex flex-col items-center text-center">
			<div class="text-green-500 mb-4">
				<!-- Icona di esempio per gli eventi -->
				<svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
				</svg>
			</div>
			<h3 class="text-xl font-semibold text-gray-700 mb-3">Gestione Eventi</h3>
			<p class="text-gray-600 mb-4">Approva, modifica ed elimina gli eventi segnalati dagli utenti.</p>
			<a href="/admin/events/all" class="mt-auto bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out">Vai a Gestione Eventi</a>
		</div>
		<!-- Card Gestione Eventi -->
		<div class="bg-white rounded-lg shadow-lg p-6 flex flex-col items-center text-center">
			<div class="text-green-500 mb-4">
				<!-- Icona di esempio per gli eventi -->
				<svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
				</svg>
			</div>
			<h3 class="text-xl font-semibold text-gray-700 mb-3">Gestione Eventi da approvare</h3>
			<p class="text-gray-600 mb-4">Approva, modifica ed elimina gli eventi segnalati dagli utenti.</p>
			<a href="/admin/events/pending" class="mt-auto bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out">Vai a Gestione Eventi</a>
		</div>

		<!-- Card Altre Funzionalità (Esempio) -->
		<div class="bg-white rounded-lg shadow-lg p-6 flex flex-col items-center text-center">
			<div class="text-purple-500 mb-4">
				<!-- Icona di esempio per altre funzionalità -->
				<svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
					<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
				</svg>
			</div>
			<h3 class="text-xl font-semibold text-gray-700 mb-3">Impostazioni</h3>
			<p class="text-gray-600 mb-4">Configura le impostazioni generali del sito e altre opzioni.</p>
			<a href="#" class="mt-auto bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out">Vai a Impostazioni</a>
		</div>
	</div>
</main>
