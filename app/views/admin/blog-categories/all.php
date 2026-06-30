<?php
// Admin Blog Categories - All
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

		<a href="/admin/blog-categories/create"
		   class="inline-flex items-center px-4 py-2 bg-blue-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			Crea nuova categoria
		</a>

	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">
		Tutte le Categorie Blog
	</h1>

	<?php
	if (isset($_SESSION['flash_messages'])) {
		foreach ($_SESSION['flash_messages'] as $type => $message) {
			echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
		}
		unset($_SESSION['flash_messages']);
	}
	?>

	<?php if (!empty($data['categories'])): ?>

		<div id="all-blog-categories-container" class="bg-white rounded-lg shadow-lg overflow-hidden p-4">

			<input type="text"
			       class="search px-4 py-2 border rounded-lg mb-4 w-full md:w-1/3"
			       placeholder="Cerca categoria...">

			<table class="min-w-full leading-normal">

				<thead>
				<tr>

					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer sort" data-sort="id">
						ID
					</th>

					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer sort" data-sort="name">
						Nome
					</th>

					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer sort" data-sort="slug">
						Slug
					</th>

					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
						SEO Title
					</th>

					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
						SEO Description
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

				<?php foreach ($data['categories'] as $category): ?>
					<tr>

						<td class="id px-5 py-5 border-b border-gray-200 bg-white text-sm">
							<?= htmlspecialchars($category['id']) ?>
						</td>

						<td class="name px-5 py-5 border-b border-gray-200 bg-white text-sm font-semibold text-gray-800">
							<?= htmlspecialchars($category['name']) ?>
						</td>

						<td class="slug px-5 py-5 border-b border-gray-200 bg-white text-sm text-gray-600">
							<?= htmlspecialchars($category['slug']) ?>
						</td>

						<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-gray-600">
							<?= htmlspecialchars($category['seo_title'] ?? '-') ?>
						</td>

						<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-gray-600">
							<?= htmlspecialchars(mb_strimwidth($category['seo_description'] ?? '', 0, 80, '...')) ?>
						</td>

						<td class="created_at px-5 py-5 border-b border-gray-200 bg-white text-sm">
							<?= date('d/m/Y', strtotime($category['created_at'])) ?>
						</td>

						<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm whitespace-nowrap">

							<a href="/admin/blog-categories/edit/<?= $category['id'] ?>"
							   class="text-green-600 hover:text-green-900 mr-2">
								Modifica
							</a>

							<form action="/admin/blog-categories/delete/<?= $category['id'] ?>"
							      method="POST"
							      class="inline-block"
							      onsubmit="return confirm('Sei sicuro di voler eliminare questa categoria?');">

								<input type="hidden" name="csrf_token"
								       value="<?= htmlspecialchars($data['csrf_token'] ?? '') ?>">

								<button type="submit"
								        class="text-red-600 hover:text-red-900">
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
			window.addEventListener('load', function () {

				const options = {
					valueNames: ['id', 'name', 'slug', 'created_at']
				};

				const container = document.getElementById('all-blog-categories-container');

				if (container) {
					new List(container, options);
				}
			});
		</script>

	<?php else: ?>
		<p class="text-gray-600">Nessuna categoria trovata.</p>
	<?php endif; ?>

</div>
