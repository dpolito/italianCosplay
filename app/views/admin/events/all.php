<?php
// Questo file è un frammento di HTML e deve essere incluso in un layout admin.
// Non contiene i tag <html>, <head>, <body> completi.
?>

<div class="container mx-auto p-6">
	<div class="mb-6">
		<a title="" href="/admin/dashboard" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
			</svg>
			Torna alla Dashboard
		</a>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Tutti gli Eventi</h1>

	<?php
	// Visualizza i messaggi flash
	if (isset($_SESSION['flash_messages'])) {
		foreach ($_SESSION['flash_messages'] as $type => $message) {
			echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
		}
		unset($_SESSION['flash_messages']);
	}
	?>

	<?php if (!empty($data['events'])): ?>
		<div id="all-events-list-container" class="bg-white rounded-lg shadow-lg overflow-hidden p-4">
			<input type="text" class="search px-4 py-2 border rounded-lg mb-4 w-full md:w-1/3" placeholder="Cerca evento...">

			<table class="min-w-full leading-normal">
				<thead>
				<tr>
					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer sort" data-sort="id">
						ID
					</th>
					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer sort" data-sort="titolo">
						Titolo
					</th>
					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer sort" data-sort="data_inizio">
						Data Inizio
					</th>
					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer sort" data-sort="data_fine">
						Data Fine
					</th>
					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer sort" data-sort="luogo">
						Luogo
					</th>
					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer sort" data-sort="approvato_status">
						Approvato
					</th>
					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
						Azioni
					</th>
				</tr>
				</thead>
				<tbody class="list">
				<?php foreach ($data['events'] as $event): ?>
					<tr>
						<td class="id px-5 py-5 border-b border-gray-200 bg-white text-sm">
							<?php echo htmlspecialchars($event['id']); ?>
						</td>
						<td class="titolo px-5 py-5 border-b border-gray-200 bg-white text-sm">
							<?php echo htmlspecialchars($event['titolo']); ?>
						</td>
						<td class="data_inizio px-5 py-5 border-b border-gray-200 bg-white text-sm">
							<?php echo htmlspecialchars(date('d/m/Y', strtotime($event['data_inizio']))); ?>
						</td>
						<td class="data_fine px-5 py-5 border-b border-gray-200 bg-white text-sm">
							<?php echo htmlspecialchars(date('d/m/Y', strtotime($event['data_fine']))); ?>
						</td>
						<td class="luogo px-5 py-5 border-b border-gray-200 bg-white text-sm">
							<?php echo htmlspecialchars($event['luogo']); ?>
						</td>
						<td class="approvato_status px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <span class="relative inline-block px-3 py-1 font-semibold leading-tight">
                                    <span aria-hidden="true" class="absolute inset-0 <?php echo ($event['approvato'] == 0 ? 'bg-yellow-200' : 'bg-green-200'); ?> opacity-50 rounded-full"></span>
                                    <span class="relative text-xs <?php echo ($event['approvato'] == 0 ? 'text-yellow-900' : 'text-green-900'); ?>">
                                        <?php echo ($event['approvato'] == 1 ? 'Approvato' : 'In Attesa'); ?>
                                    </span>
                                </span>
						</td>
						<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm whitespace-nowrap">
							<a title="" href="/admin/events/show/<?php echo htmlspecialchars($event['id']); ?>" class="text-blue-600 hover:text-blue-900 mr-2">Dettagli</a>
							<?php if ($event['approvato'] == 0): ?>
								<form action="/admin/events/approve/<?php echo htmlspecialchars($event['id']); ?>" method="POST" class="inline-block mr-2" onsubmit="return confirm('Sei sicuro di voler approvare questo evento?');">
									<button type="submit" class="text-green-600 hover:text-green-900 focus:outline-none focus:underline">Approva</button>
								</form>
							<?php endif; ?>
							<a title="" href="/admin/events/edit/<?php echo htmlspecialchars($event['id']); ?>" class="text-indigo-600 hover:text-indigo-900 mr-2">Modifica</a>
							<form action="/admin/events/delete/<?php echo htmlspecialchars($event['id']); ?>" method="POST" class="inline-block" onsubmit="return confirm('Sei sicuro di voler eliminare questo evento?');">
								<button type="submit" class="text-red-600 hover:text-red-900 focus:outline-none focus:underline">Elimina</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<!-- Inclusione di List.js -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/2.3.1/list.min.js"></script>
		<script>
			window.addEventListener('load', function() {
				var options = {
					valueNames: [ 'id', 'titolo', 'data_inizio', 'data_fine', 'luogo', 'approvato_status' ]
				};

				var containerElement = document.getElementById('all-events-list-container'); // ID del contenitore
				if (containerElement) {
					var eventsList = new List(containerElement, options);
				} else {
					console.error("Elemento '#all-events-list-container' non trovato. Impossibile inizializzare List.js.");
				}
			});
		</script>
	<?php else: ?>
		<p class="text-gray-600">Nessun evento trovato.</p>
	<?php endif; ?>
</div>
