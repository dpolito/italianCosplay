<?php
// Admin Blog Categories Edit - ItalianCosplay CMS
?>

<div class="container mx-auto p-6">

	<!-- Back -->
	<div class="mb-6">
		<a href="/admin/blog/categories/all"
		   class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition">

			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
			     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round"
				      d="M11 17l-5-5m0 0l5-5m5 5h12"/>
			</svg>

			Torna alle Categorie
		</a>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">
		Modifica Categoria Blog
	</h1>

	<?php if (isset($_SESSION['flash_messages'])): ?>
		<?php foreach ($_SESSION['flash_messages'] as $type => $message): ?>
			<div class="flash-message <?= htmlspecialchars($type) ?>">
				<?= $message ?>
			</div>
		<?php endforeach; unset($_SESSION['flash_messages']); ?>
	<?php elseif (!empty($data['error'])): ?>
		<div class="flash-message error">
			<?= $data['error'] ?>
		</div>
	<?php endif; ?>

	<div class="bg-white rounded-lg shadow-lg p-8">

		<form action="/admin/blog-categories/update/<?= (int)$data['category']['id'] ?>"
		      method="POST"
		      id="categoryForm">

			<input type="hidden" name="csrf_token"
			       value="<?= htmlspecialchars($data['csrf_token']) ?>">

			<input type="hidden" name="id"
			       value="<?= (int)$data['category']['id'] ?>">

			<!-- ================= ROW 1 ================= -->
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">
						Nome Categoria *
					</label>

					<input type="text"
					       name="name"
					       id="name"
					       value="<?= htmlspecialchars($data['category']['name']) ?>"
					       class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500"
					       required>
				</div>

				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">
						Slug SEO
					</label>

					<input type="text"
					       name="slug"
					       id="slug"
					       value="<?= htmlspecialchars($data['category']['slug']) ?>"
					       class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500">
				</div>

			</div>

			<!-- ================= ROW 2 ================= -->
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">
						SEO Title
					</label>

					<input type="text"
					       name="seo_title"
					       id="seo_title"
					       value="<?= htmlspecialchars($data['category']['seo_title'] ?? '') ?>"
					       class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500">
				</div>

				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">
						Slug Preview
					</label>

					<div class="p-2 bg-gray-50 border rounded text-sm text-gray-700">
						/blog/categoria/<span id="seoSlugPreview">
							<?= htmlspecialchars($data['category']['slug']) ?>
						</span>
					</div>
				</div>

			</div>

			<!-- ================= ROW 3 ================= -->
			<div class="mb-6">

				<label class="block text-gray-700 text-sm font-bold mb-2">
					SEO Description
				</label>

				<textarea name="seo_description"
				          id="seo_description"
				          rows="3"
				          class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500"><?= htmlspecialchars($data['category']['seo_description'] ?? '') ?></textarea>

			</div>

			<!-- ================= ROW 4 ================= -->
			<div class="mb-6">

				<label class="block text-gray-700 text-sm font-bold mb-2">
					Descrizione Categoria
				</label>

				<textarea name="description"
				          rows="4"
				          class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500"><?= htmlspecialchars($data['category']['description'] ?? '') ?></textarea>

			</div>

			<!-- ================= SEO PREVIEW ================= -->
			<div class="mb-6 p-4 bg-gray-50 border rounded-lg">

				<p class="text-xs text-gray-500 mb-2">Anteprima Google</p>

				<div class="text-blue-700 text-lg" id="seoPreviewTitle">
					<?= htmlspecialchars($data['category']['seo_title'] ?: $data['category']['name']) ?>
				</div>

				<div class="text-green-700 text-sm">
					www.italiancosplay.it/blog/categoria/<span id="seoPreviewSlug">
						<?= htmlspecialchars($data['category']['slug']) ?>
					</span>
				</div>

				<div class="text-gray-600 text-sm mt-1" id="seoPreviewDesc">
					<?= htmlspecialchars($data['category']['seo_description'] ?? '') ?>
				</div>

			</div>

			<!-- ================= SUBMIT ================= -->
			<div class="flex items-center justify-between">

				<button type="submit"
				        class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg">
					Aggiorna Categoria
				</button>

				<a href="/admin/blog/categories/all"
				   class="text-gray-600 hover:text-gray-900">
					Annulla
				</a>

			</div>

		</form>

	</div>
</div>

<!-- ================= JS ================= -->
<script>
	document.addEventListener('DOMContentLoaded', function () {

		const name = document.getElementById('name');
		const slug = document.getElementById('slug');
		const seoTitle = document.getElementById('seo_title');
		const seoDesc = document.getElementById('seo_description');

		const previewTitle = document.getElementById('seoPreviewTitle');
		const previewSlug = document.getElementById('seoPreviewSlug');
		const previewDesc = document.getElementById('seoPreviewDesc');

		let slugEdited = true; // in edit NON auto-override

		function makeSlug(str) {
			return str
				.toLowerCase()
				.replace(/[^a-z0-9]+/g, '-')
				.replace(/(^-|-$)/g, '');
		}

		name.addEventListener('input', function () {
			if (!slugEdited) {
				slug.value = makeSlug(name.value);
			}

			previewTitle.innerText = seoTitle.value || name.value;
			previewSlug.innerText = slug.value;
		});

		slug.addEventListener('input', function () {
			slugEdited = true;
			previewSlug.innerText = slug.value;
		});

		seoTitle.addEventListener('input', function () {
			previewTitle.innerText = seoTitle.value || name.value;
		});

		seoDesc.addEventListener('input', function () {
			previewDesc.innerText = seoDesc.value;
		});

	});
</script>
