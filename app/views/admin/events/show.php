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

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Dettagli Evento: <?php echo htmlspecialchars($data['event']['titolo'] ?? ''); ?></h1>

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
		<div class="mb-4">
			<h3 class="text-xl font-semibold text-gray-700 mb-2">Titolo:</h3>
			<p class="text-gray-600"><?php echo htmlspecialchars($data['event']['titolo'] ?? 'N/A'); ?></p>
		</div>

		<div class="mb-4">
			<h3 class="text-xl font-semibold text-gray-700 mb-2">Descrizione:</h3>
			<p class="text-gray-600"><?php echo nl2br(htmlspecialchars($data['event']['descrizione'] ?? 'N/A')); ?></p>
		</div>

		<div class="mb-4">
			<h3 class="text-xl font-semibold text-gray-700 mb-2">Data Inizio:</h3>
			<p class="text-gray-600"><?php echo htmlspecialchars(date('d/m/Y', strtotime($data['event']['data_inizio'] ?? ''))); ?></p>
		</div>

		<div class="mb-4">
			<h3 class="text-xl font-semibold text-gray-700 mb-2">Data Fine:</h3>
			<p class="text-gray-600"><?php echo htmlspecialchars(date('d/m/Y', strtotime($data['event']['data_fine'] ?? ''))); ?></p>
		</div>

		<div class="mb-4">
			<h3 class="text-xl font-semibold text-gray-700 mb-2">Luogo:</h3>
			<p class="text-gray-600"><?php echo htmlspecialchars($data['event']['luogo'] ?? 'N/A'); ?></p>
		</div>

		<div class="mb-4">
			<h3 class="text-xl font-semibold text-gray-700 mb-2">URL Immagine:</h3>
			<?php if (!empty($data['event']['immagine'])): ?>
				<a href="<?php echo htmlspecialchars($data['event']['immagine']); ?>" target="_blank" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($data['event']['immagine']); ?></a>
				<img src="<?php echo htmlspecialchars($data['event']['immagine']); ?>" alt="Immagine Evento" class="mt-2 max-w-full h-auto rounded-lg shadow-md">
			<?php else: ?>
				<p class="text-gray-600">Nessuna immagine fornita.</p>
			<?php endif; ?>
		</div>

		<div class="mb-4">
			<h3 class="text-xl font-semibold text-gray-700 mb-2">Approvato:</h3>
			<p class="text-gray-600">
				<?php echo (isset($data['event']['approvato']) && $data['event']['approvato'] == 1) ? 'Sì' : 'No'; ?>
			</p>
		</div>

		<div class="flex items-center justify-start mt-6">
			<?php if (isset($data['event']['approvato']) && $data['event']['approvato'] == 0): // Se in attesa (0) ?>
				<form action="/admin/events/approve/<?php echo htmlspecialchars($data['event']['id'] ?? ''); ?>" method="POST" class="inline-block mr-4" onsubmit="return confirm('Sei sicuro di voler approvare questo evento?');">
					<button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
						Approva Evento
					</button>
				</form>
			<?php endif; ?>
			<a href="/admin/events/edit/<?php echo htmlspecialchars($data['event']['id'] ?? ''); ?>" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md focus:outline-none focus:shadow-outline transition duration-300 ease-in-out mr-4">
				Modifica Evento
			</a>
			<form action="/admin/events/delete/<?php echo htmlspecialchars($data['event']['id'] ?? ''); ?>" method="POST" class="inline-block" onsubmit="return confirm('Sei sicuro di voler eliminare questo evento?');">
				<button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
					Elimina Evento
				</button>
			</form>
		</div>
	</div>
</div>
