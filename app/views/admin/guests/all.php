<?php
// Admin guest All - coerente con Events UX
?>

<div class="container mx-auto p-6">

	<div class="flex justify-between items-center mb-6">

		<a href="/admin/dashboard"
		   class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">

			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
			</svg>

			Torna alla Dashboard
		</a>

		<a href="/admin/guests/create"
		   class="inline-flex items-center px-4 py-2 bg-blue-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			Crea nuovo Guest
		</a>

	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">
		Tutti i Guest
	</h1>

	<?php
	if (isset($_SESSION['flash_messages'])) {
		foreach ($_SESSION['flash_messages'] as $type => $message) {
			echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
		}
		unset($_SESSION['flash_messages']);
	}
	?>

	<?php if (!empty($data['guests'])): ?>

		<div id="all-guest-list-container" class="bg-white rounded-lg shadow-lg overflow-hidden p-4">

			<input type="text"
			       class="search px-4 py-2 border rounded-lg mb-4 w-full md:w-1/3"
			       placeholder="Cerca articolo...">

			<table class="min-w-full leading-normal">

				<thead>
				<tr>

					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer sort" data-sort="id">
						ID
					</th>

					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer sort" data-sort="titolo">
						Nome e Cognome
					</th>

					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer sort" data-sort="slug">
						Slug
					</th>
					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer sort" data-sort="created_at">
						Data
					</th>

					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
						Azioni
					</th>

				</tr>
				</thead>

				<tbody class="list">

				<?php foreach ($data['guests'] as $post): ?>
					<tr>

						<td class="id px-5 py-5 border-b border-gray-200 bg-white text-sm">
							<?= htmlspecialchars($post['id']) ?>
						</td>

						<td class="titolo px-5 py-5 border-b border-gray-200 bg-white text-sm font-semibold text-gray-800">
							<?= htmlspecialchars($post['name']) ?>
						</td>

						<td class="slug px-5 py-5 border-b border-gray-200 bg-white text-sm text-gray-600">
							/guest/<?= htmlspecialchars($post['slug']) ?>
						</td>
						<td class="created_at px-5 py-5 border-b border-gray-200 bg-white text-sm">
							<?= date('d/m/Y', strtotime($post['created_at'])) ?>
						</td>

						<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm whitespace-nowrap">

							<a href="/admin/guests/edit/<?= $post['id'] ?>"
							   class="text-green-600 hover:text-green-900 mr-2">
								Modifica
							</a>

							<form action="/admin/guests/delete/<?= $post['id'] ?>"
							      method="POST"
							      class="inline-block"
							      onsubmit="return confirm('Sei sicuro di voler eliminare questo articolo?');">

								<input type="hidden" name="csrf_token"
								       value="<?= htmlspecialchars($data['csrf_token'] ?? '') ?>">

								<button type="submit"
								        class="text-red-600 hover:text-red-900 focus:outline-none focus:underline">
									Elimina
								</button>

							</form>

						</td>

					</tr>
				<?php endforeach; ?>

				</tbody>

			</table>
		</div>

		<!-- List.js -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/2.3.1/list.min.js"></script>

		<script>
			window.addEventListener('load', function() {

				var options = {
					valueNames: [
						'id',
						'titolo',
						'slug',
						'created_at'
					]
				};

				var containerElement = document.getElementById('all-guest-list-container');

				if (containerElement) {
					new List(containerElement, options);
				}
			});
		</script>

	<?php else: ?>
		<p class="text-gray-600">Nessun guest trovato.</p>
	<?php endif; ?>

</div>
