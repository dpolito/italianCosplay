<?php
// Admin Blog All - coerente con Events UX
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

		<a href="/admin/blog/create"
		   class="inline-flex items-center px-4 py-2 bg-blue-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			Crea nuovo articolo
		</a>

	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">
		Tutti gli Articoli Blog
	</h1>

	<?php
	if (isset($_SESSION['flash_messages'])) {
		foreach ($_SESSION['flash_messages'] as $type => $message) {
			echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
		}
		unset($_SESSION['flash_messages']);
	}
	?>

	<?php if (!empty($data['posts'])): ?>

		<div id="all-blog-list-container" class="bg-white rounded-lg shadow-lg overflow-hidden p-4">

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
						Titolo
					</th>

					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer sort" data-sort="slug">
						Slug
					</th>

					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer sort" data-sort="status">
						Status
					</th>

					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer sort" data-sort="views">
						Views
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

				<?php foreach ($data['posts'] as $post): ?>
					<tr>

						<td class="id px-5 py-5 border-b border-gray-200 bg-white text-sm">
							<?= htmlspecialchars($post['id']) ?>
						</td>

						<td class="titolo px-5 py-5 border-b border-gray-200 bg-white text-sm font-semibold text-gray-800">
							<?= htmlspecialchars($post['titolo']) ?>

							<?php if (!empty($post['excerpt'])): ?>
								<p class="text-xs text-gray-500">
									<?= htmlspecialchars(mb_strimwidth($post['excerpt'], 0, 90, '...')) ?>
								</p>
							<?php endif; ?>
						</td>

						<td class="slug px-5 py-5 border-b border-gray-200 bg-white text-sm text-gray-600">
							/blog/<?= htmlspecialchars($post['slug']) ?>
						</td>

						<td class="status px-5 py-5 border-b border-gray-200 bg-white text-sm">

							<?php if ($post['status'] === 'published'): ?>
								<span class="relative inline-block px-3 py-1 font-semibold leading-tight">
									<span class="absolute inset-0 bg-green-200 opacity-50 rounded-full"></span>
									<span class="relative text-xs text-green-900">Pubblicato</span>
								</span>
							<?php else: ?>
								<span class="relative inline-block px-3 py-1 font-semibold leading-tight">
									<span class="absolute inset-0 bg-yellow-200 opacity-50 rounded-full"></span>
									<span class="relative text-xs text-yellow-900">Bozza</span>
								</span>
							<?php endif; ?>

						</td>

						<td class="views px-5 py-5 border-b border-gray-200 bg-white text-sm">
							<?= (int)$post['views'] ?>
						</td>

						<td class="created_at px-5 py-5 border-b border-gray-200 bg-white text-sm">
							<?= date('d/m/Y', strtotime($post['created_at'])) ?>
						</td>

						<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm whitespace-nowrap">

							<a href="/admin/blog/edit/<?= $post['id'] ?>"
							   class="text-green-600 hover:text-green-900 mr-2">
								Modifica
							</a>

							<?php if ($post['status'] === 'published'): ?>
								<a href="/blog/<?= $post['slug'] ?>"
								   target="_blank"
								   class="text-blue-600 hover:text-blue-900 mr-2">
									Visita
								</a>
							<?php endif; ?>

							<form action="/admin/blog/delete/<?= $post['id'] ?>"
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
						'status',
						'views',
						'created_at'
					]
				};

				var containerElement = document.getElementById('all-blog-list-container');

				if (containerElement) {
					new List(containerElement, options);
				}
			});
		</script>

	<?php else: ?>
		<p class="text-gray-600">Nessun articolo trovato.</p>
	<?php endif; ?>

</div>
