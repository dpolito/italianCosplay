<?php
// Questo file è un frammento di HTML e deve essere incluso in un layout admin.
// Non contiene i tag <html>, <head>, <body> completi.
?>

<div class="container mx-auto p-6">
	<div class="mb-6 flex justify-between items-center">
		<a href="/admin/events/all" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
			</svg>
			Torna a Tutti gli Eventi
		</a>
		<div>
			<a href="/admin/events/edit/<?php echo htmlspecialchars($data['event']['id'] ?? ''); ?>" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition duration-300 ease-in-out mr-2">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.1 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
				</svg>
				Modifica
			</a>
			<form action="/admin/events/delete/<?php echo htmlspecialchars($data['event']['id'] ?? ''); ?>" method="POST" class="inline-block" onsubmit="return confirm('Sei sicuro di voler eliminare questo evento?');">
				<!-- CSRF Token per il form di eliminazione -->
				<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">
				<button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-semibold rounded-lg shadow-md hover:bg-red-700 transition duration-300 ease-in-out">
					<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
						<path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
					</svg>
					Elimina
				</button>
			</form>
		</div>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Dettagli Evento: <?php echo htmlspecialchars($data['event']['titolo'] ?? 'N/A'); ?></h1>

	<?php
	// Visualizza i messaggi flash
	if (isset($_SESSION['flash_messages'])) {
		foreach ($_SESSION['flash_messages'] as $type => $message) {
			echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
		}
		unset($_SESSION['flash_messages']);
	}
	?>

	<?php if (!empty($data['event'])): ?>
		<div class="bg-white rounded-lg shadow-lg p-8">
			<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
				<div>
					<h3 class="text-xl font-semibold text-gray-700 mb-3">Informazioni Base</h3>
					<p class="text-gray-600 mb-2"><strong>ID:</strong> <?php echo htmlspecialchars($data['event']['id'] ?? 'N/A'); ?></p>
					<p class="text-gray-600 mb-2"><strong>Titolo:</strong> <?php echo htmlspecialchars($data['event']['titolo'] ?? 'N/A'); ?></p>
					<p class="text-gray-600 mb-2"><strong>Data Inizio:</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($data['event']['data_inizio'] ?? ''))); ?></p>
					<p class="text-gray-600 mb-2"><strong>Data Fine:</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($data['event']['data_fine'] ?? ''))); ?></p>
					<p class="text-gray-600 mb-2"><strong>Luogo:</strong> <?php echo htmlspecialchars($data['event']['luogo'] ?? 'N/A'); ?></p>
					<p class="text-gray-600 mb-2"><strong>Regione ID:</strong> <?php echo htmlspecialchars($data['event']['regione_id'] ?? 'N/A'); ?></p>
					<p class="text-gray-600 mb-2"><strong>Provincia ID:</strong> <?php echo htmlspecialchars($data['event']['provincia_id'] ?? 'N/A'); ?></p>
					<p class="text-gray-600 mb-2"><strong>Comune ID:</strong> <?php echo htmlspecialchars($data['event']['comune_id'] ?? 'N/A'); ?></p>
					<p class="text-gray-600 mb-2"><strong>Latitudine:</strong> <?php echo htmlspecialchars($data['event']['latitudine'] ?? 'N/A'); ?></p>
					<p class="text-gray-600 mb-2"><strong>Longitudine:</strong> <?php echo htmlspecialchars($data['event']['longitudine'] ?? 'N/A'); ?></p>
					<p class="text-gray-600 mb-2"><strong>Tipo Evento ID:</strong> <?php echo htmlspecialchars($data['event']['tipo_evento_id'] ?? 'N/A'); ?></p>
					<p class="text-gray-600 mb-2"><strong>Approvato:</strong>
						<span class="relative inline-block px-3 py-1 font-semibold leading-tight">
                            <span aria-hidden="true" class="absolute inset-0 <?php echo ($data['event']['approvato'] == 0 ? 'bg-yellow-200' : 'bg-green-200'); ?> opacity-50 rounded-full"></span>
                            <span class="relative text-xs <?php echo ($data['event']['approvato'] == 0 ? 'text-yellow-900' : 'text-green-900'); ?>">
                                <?php echo ($data['event']['approvato'] == 1 ? 'Sì' : 'No'); ?>
                            </span>
                        </span>
					</p>
					<p class="text-gray-600 mb-2"><strong>Creato il:</strong> <?php echo htmlspecialchars($data['event']['created_at'] ?? 'N/A'); ?></p>
					<p class="text-gray-600 mb-2"><strong>Aggiornato il:</strong> <?php echo htmlspecialchars($data['event']['updated_at'] ?? 'N/A'); ?></p>
				</div>
				<div>
					<h3 class="text-xl font-semibold text-gray-700 mb-3">Contatti & Social</h3>
					<p class="text-gray-600 mb-2"><strong>Sito Web:</strong> <?php echo htmlspecialchars($data['event']['sito_web'] ?? 'N/A'); ?></p>
					<p class="text-gray-600 mb-2"><strong>Facebook:</strong> <?php echo htmlspecialchars($data['event']['social_facebook'] ?? 'N/A'); ?></p>
					<p class="text-gray-600 mb-2"><strong>Twitter:</strong> <?php echo htmlspecialchars($data['event']['social_twitter'] ?? 'N/A'); ?></p>
					<p class="text-gray-600 mb-2"><strong>Instagram:</strong> <?php echo htmlspecialchars($data['event']['social_instagram'] ?? 'N/A'); ?></p>
					<p class="text-gray-600 mb-2"><strong>TikTok:</strong> <?php echo htmlspecialchars($data['event']['social_tiktok'] ?? 'N/A'); ?></p>
					<p class="text-gray-600 mb-2"><strong>YouTube:</strong> <?php echo htmlspecialchars($data['event']['social_youtube'] ?? 'N/A'); ?></p>

					<h3 class="text-xl font-semibold text-gray-700 mt-6 mb-3">Immagine</h3>
					<?php if (!empty($data['event']['immagine'])): ?>
						<img src="<?php echo htmlspecialchars($data['event']['immagine']); ?>" alt="Immagine Evento" class="w-full h-auto rounded-lg shadow-md max-w-sm">
					<?php else: ?>
						<p class="text-gray-600">Nessuna immagine disponibile.</p>
					<?php endif; ?>
				</div>
			</div>

			<div class="mt-8">
				<h3 class="text-xl font-semibold text-gray-700 mb-3">Descrizione Dettagliata</h3>
				<!-- La descrizione di Quill.js non deve essere passata attraverso htmlspecialchars() qui -->
				<div class="prose max-w-none text-gray-700">
					<?php echo $data['event']['descrizione'] ?? 'Nessuna descrizione disponibile.'; ?>
				</div>
			</div>
		</div>
	<?php else: ?>
		<p class="text-gray-600">Dettagli evento non disponibili.</p>
	<?php endif; ?>
</div>
