<?php
// Admin Blog Edit - ItalianCosplay CMS
?>

<div class="container mx-auto p-6">

	<!-- BACK -->
	<div class="mb-6">
		<a href="/admin/blog/all"
		   class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m5 5h12"/>
			</svg>
			Torna al Blog
		</a>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">
		Modifica Articolo Blog
	</h1>

	<?php
	if (isset($_SESSION['flash_messages'])) {
		foreach ($_SESSION['flash_messages'] as $type => $message) {
			echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . $message . '</div>';
		}
		unset($_SESSION['flash_messages']);
	}
	?>

	<div class="bg-white rounded-lg shadow-lg p-8">

		<form action="/admin/blog/update/<?= htmlspecialchars($data['post']['id']) ?>"
		      method="POST"
		      enctype="multipart/form-data"
		      id="blogForm" >

			<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data['csrf_token']) ?>">
			<input type="hidden" name="id" value="<?= (int)$data['post']['id'] ?>">

			<!-- ================= ROW 1 ================= -->
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">Titolo *</label>
					<input type="text"
					       name="titolo"
					       id="titolo"
					       value="<?= htmlspecialchars($data['post']['titolo']) ?>"
					       class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500"
					       required>
				</div>

				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">Slug SEO</label>
					<input type="text"
					       name="slug"
					       id="slug"
					       value="<?= htmlspecialchars($data['post']['slug']) ?>"
					       class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500">
				</div>

			</div>

			<!-- ================= ROW 2 ================= -->
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">Meta Title</label>
					<input type="text"
					       name="meta_title"
					       value="<?= htmlspecialchars($data['post']['meta_title']) ?>"
					       class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500">
				</div>

				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">Categoria</label>
					<select name="categoria_id"
					        class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500">
						<option value="">Seleziona categoria</option>
						<?php if(!empty($data['categorie'])):
							foreach($data['categorie'] as $categoria): ?>
								<option value="<?php echo htmlspecialchars($categoria['id']); ?>"
									<?php echo (isset($data['post']['categoria_id']) && $data['post']['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
									<?php echo htmlspecialchars($categoria['name']); ?>
								</option>
							<?php endforeach; endif; ?>
					</select>
				</div>

			</div>

			<!-- ================= META DESC ================= -->
			<div class="mb-6">
				<label class="block text-gray-700 text-sm font-bold mb-2">Meta Description</label>
				<textarea name="meta_description"
				          rows="2"
				          class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500"><?= htmlspecialchars($data['post']['meta_description']) ?></textarea>
			</div>

			<!-- ================= EXCERPT ================= -->
			<div class="mb-6">
				<label class="block text-gray-700 text-sm font-bold mb-2">Excerpt</label>
				<textarea name="excerpt"
				          rows="3"
				          class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500"><?= htmlspecialchars($data['post']['excerpt']) ?></textarea>
			</div>

			<!-- ================= EVENT LINK ================= -->
			<div class="mb-6">
				<label class="block text-gray-700 text-sm font-bold mb-2">Evento collegato</label>
				<select name="related_event_id"
				        class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500">
					<option value="">Nessun evento</option>
				</select>
			</div>

			<!-- ================= STATUS ================= -->
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
			<div class="mb-6">
				<label class="block text-gray-700 text-sm font-bold mb-2">Stato</label>
				<select name="status"
				        class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500">
					<option value="draft" <?= $data['post']['status'] === 'draft' ? 'selected' : '' ?>>Bozza</option>
					<option value="published" <?= $data['post']['status'] === 'published' ? 'selected' : '' ?>>Pubblicato</option>
					<option value="published" <?= $data['post']['status'] === 'scheduled' ? 'selected' : '' ?>>Schedulato</option>
				</select>
			</div>
				<div class="mb-6">
					<label class="block text-gray-700 text-sm font-bold mb-2">Data pubblicazione</label>
					<input type="date" id="published_at" name="published_at" value="<?= $data['post']['published_at_date']  ?>" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline">
				</div>
			</div>
			<!-- ================= COVER IMAGE ================= -->
			<div class="mb-6">
				<label class="block text-gray-700 text-sm font-bold mb-2">Cover Image</label>

				<input type="file"
				       name="cover_image"
				       class="shadow border rounded-lg w-full py-2 px-3">

				<?php if (!empty($data['post']['cover_image_id'])): ?>
					<div class="mt-3">
						<p class="text-xs text-gray-500">Immagine attuale</p>
						<img src="<?= $data['post']['immagine'] ?>"
						     class="w-48 rounded shadow mt-2">
					</div>
				<?php endif; ?>
			</div>

			<!-- ================= SEO PREVIEW ================= -->
			<div class="mb-6 p-4 bg-gray-50 border rounded-lg">

				<p class="text-xs text-gray-500 mb-2">Anteprima Google</p>

				<div class="text-blue-700 text-lg" id="seoTitle">
					<?= htmlspecialchars($data['post']['titolo']) ?>
				</div>

				<div class="text-green-700 text-sm">
					www.italiancosplay.it/blog/<span id="seoSlug">
						<?= htmlspecialchars($data['post']['slug']) ?>
					</span>
				</div>

				<div class="text-gray-600 text-sm mt-1" id="seoDesc">
					<?= htmlspecialchars($data['post']['meta_description']) ?>
				</div>

			</div>

			<!-- ================= CONTENT EDITOR ================= -->
			<div class="mb-6">

				<label class="block text-gray-700 text-sm font-bold mb-2">
					Contenuto *
				</label>
				<input type="hidden" name="contenuto" id="contenuto">
				<style>
					.ck-editor__editable {
						min-height: 70vh;
					}
				</style>
				<div id="editor-wrapper" style="min-height: 600px;">
					<div id="editor"></div>
				</div>

			</div>

			<!-- ================= BUTTONS ================= -->
			<div class="flex items-center justify-between">

				<button type="submit"
				        class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg">
					Aggiorna Articolo
				</button>

				<a href="/admin/blog/all"
				   class="text-gray-600 hover:text-gray-900">
					Annulla
				</a>

			</div>

		</form>
	</div>
</div>

<!-- ================= JS ================= -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
	ClassicEditor
		.create(document.querySelector('#editor'))
		.then(editor => {
			window.editor = editor;

			// carica contenuto iniziale
			editor.setData(`<?= $data['post']['contenuto'] ?? '' ?>`);

			// sync con hidden input
			editor.model.document.on('change:data', () => {
				document.querySelector('#contenuto').value = editor.getData();
			});
		})
		.catch(error => {
			console.error(error);
		});
</script>
<script>
	document.getElementById('blogForm').addEventListener('submit', function () {
		document.querySelector('#contenuto').value = window.editor.getData();
	});
</script>
<script>

	document.addEventListener('DOMContentLoaded', function () {

		const titolo = document.getElementById('titolo');
		const slug = document.getElementById('slug');
		const metaDesc = document.querySelector('[name="meta_description"]');

		const seoTitle = document.getElementById('seoTitle');
		const seoSlug = document.getElementById('seoSlug');
		const seoDesc = document.getElementById('seoDesc');

		const editor = document.getElementById('editor');
		const textarea = document.getElementById('contenuto');

		function makeSlug(str) {
			return str
				.toLowerCase()
				.replace(/[^a-z0-9]+/g, '-')
				.replace(/(^-|-$)/g, '');
		}

		let slugEdited = false;

		slug.addEventListener('input', function () {
			slugEdited = true;

			seoSlug.innerText = slug.value;
		});

		titolo.addEventListener('input', function () {

			if (!slugEdited) {
				slug.value = makeSlug(titolo.value);
			}

			seoTitle.innerText = titolo.value;
			seoSlug.innerText = slug.value;
		});

		slug.addEventListener('input', function () {
			seoSlug.innerText = slug.value;
		});

		metaDesc.addEventListener('input', function () {
			seoDesc.innerText = metaDesc.value;
		});

	});
</script>
