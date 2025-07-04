<?php
// Questo file è un frammento di HTML e deve essere incluso in un layout admin.
// Non contiene i tag <html>, <head>, <body> completi.
?>

<div class="container mx-auto p-6">
	<div class="mb-6">
		<a href="/admin/events/pending" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
			</svg>
			Torna agli Eventi in Attesa
		</a>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Modifica Evento: <?php echo htmlspecialchars($data['event']['titolo'] ?? ''); ?></h1>

	<?php
	// Visualizza i messaggi flash o errori passati direttamente
	if (isset($_SESSION['flash_messages'])) {
		foreach ($_SESSION['flash_messages'] as $type => $message) {
			echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
		}
		unset($_SESSION['flash_messages']);
	} elseif (isset($data['error'])) {
		echo '<div class="flash-message error">' . htmlspecialchars($data['error']) . '</div>';
	}
	?>

	<div class="bg-white rounded-lg shadow-lg p-8">
		<form action="/events/<?php echo htmlspecialchars($data['event']['id'] ?? ''); ?>/update" method="POST">
			<input type="hidden" name="id" value="<?php echo htmlspecialchars($data['event']['id'] ?? ''); ?>">

			<div class="mb-4">
				<label for="titolo" class="block text-gray-700 text-sm font-bold mb-2">Titolo:</label>
				<input type="text" id="titolo" name="titolo" value="<?php echo htmlspecialchars($data['event']['titolo'] ?? ''); ?>" required class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="descrizione" class="block text-gray-700 text-sm font-bold mb-2">Descrizione:</label>
				<textarea id="descrizione" name="descrizione" rows="5" required class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500"><?php echo htmlspecialchars($data['event']['descrizione'] ?? ''); ?></textarea>
			</div>

			<div class="mb-4">
				<label for="data_inizio" class="block text-gray-700 text-sm font-bold mb-2">Data Inizio:</label>
				<input type="date" id="data_inizio" name="data_inizio" value="<?php echo htmlspecialchars($data['event']['data_inizio'] ?? ''); ?>" required class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="data_fine" class="block text-gray-700 text-sm font-bold mb-2">Data Fine:</label>
				<input type="date" id="data_fine" name="data_fine" value="<?php echo htmlspecialchars($data['event']['data_fine'] ?? ''); ?>" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="luogo" class="block text-gray-700 text-sm font-bold mb-2">Luogo:</label>
				<input type="text" id="luogo" name="luogo" value="<?php echo htmlspecialchars($data['event']['luogo'] ?? ''); ?>" required class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="immagine" class="block text-gray-700 text-sm font-bold mb-2">URL Immagine (Opzionale):</label>
				<input type="text" id="immagine" name="immagine" value="<?php echo htmlspecialchars($data['event']['immagine'] ?? ''); ?>" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-6">
				<label for="approvato" class="block text-gray-700 text-sm font-bold mb-2">Approvato:</label>
				<select id="approvato" name="approvato" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
					<option value="0" <?php echo (isset($data['event']['approvato']) && $data['event']['approvato'] == 0) ? 'selected' : ''; ?>>In Attesa</option>
					<option value="1" <?php echo (isset($data['event']['approvato']) && $data['event']['approvato'] == 1) ? 'selected' : ''; ?>>Approvato</option>
				</select>
			</div>

			<div class="flex items-center justify-between">
				<button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
					Aggiorna Evento
				</button>
				<a href="/admin/events/pending" class="inline-block align-baseline font-semibold text-blue-600 hover:text-blue-800">
					Annulla
				</a>
			</div>
		</form>
	</div>
</div>
