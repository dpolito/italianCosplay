<?php
$guest = $data['guest'];
?>

<div class="container mx-auto p-6">
	<div class="mb-6">
		<a href="/admin/guests/all" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m5 5h12"/>
			</svg>
			Torna ai Guests
		</a>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Modifica Guest</h1>

	<div class="bg-white rounded-lg shadow-lg p-8">
		<form action="/admin/guests/update/<?= (int)$guest['id'] ?>" method="POST" enctype="multipart/form-data" id="guestForm" data-ajax-submit="true" data-success-redirect="/admin/guests/edit/<?= (int)$guest['id'] ?>">
			<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data['csrf_token']) ?>">
			<input type="hidden" name="id" value="<?= (int)$guest['id'] ?>">

			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">Nome e Cognome *</label>
					<input type="text" name="name" id="name" value="<?= htmlspecialchars($guest['name']) ?>" class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500" required>
				</div>
				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">Slug SEO</label>
					<input type="text" name="slug" id="slug" value="<?= htmlspecialchars($guest['slug']) ?>" class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500">
					<p class="text-xs text-gray-500 mt-1">URL finale: /guest/<?= htmlspecialchars($guest['slug']) ?></p>
				</div>
			</div>

			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">Sito</label>
					<input type="text" name="website" value="<?= htmlspecialchars($guest['website'] ?? '') ?>" class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500">
				</div>
				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">Instagram</label>
					<input type="text" name="instagram" value="<?= htmlspecialchars($guest['instagram'] ?? '') ?>" class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500">
				</div>
			</div>

			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">TikTok</label>
					<input type="text" name="tiktok" value="<?= htmlspecialchars($guest['tiktok'] ?? '') ?>" class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500">
				</div>
				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">Youtube</label>
					<input type="text" name="youtube" value="<?= htmlspecialchars($guest['youtube'] ?? '') ?>" class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500">
				</div>
			</div>

			<div class="mb-6">
				<label class="block text-gray-700 text-sm font-bold mb-2">Immagine Cover SEO</label>
				<?php if (!empty($guest['immagine'])): ?>
					<div class="mb-4">
						<img src="<?= htmlspecialchars($guest['immagine']) ?>" alt="<?= htmlspecialchars($guest['name']) ?>" class="max-w-sm rounded-lg shadow border">
					</div>
				<?php endif; ?>
				<input type="file" name="cover_image" accept="image/*" class="shadow border rounded-lg w-full py-2 px-3">
				<p class="text-xs text-gray-500 mt-1">Lascia vuoto per mantenere l'immagine esistente</p>
			</div>

			<div class="mb-6">
				<label class="block text-gray-700 text-sm font-bold mb-2">Bio *</label>
				<div id="editor"></div>
			</div>

			<div class="flex items-center justify-between">
				<button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg">Salva Modifiche</button>
				<a href="/admin/guests/all" class="text-gray-600 hover:text-gray-900">Annulla</a>
			</div>
		</form>
	</div>
</div>

<script src="/public_assets/js/wysiwyg-editor.js"></script>
<script>
	WysiwygEditor.init('#editor', {
		name: 'bio',
		placeholder: 'Scrivi la bio del guest...',
		content: <?= json_encode($guest['bio'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
	});
</script>
<script>
	document.addEventListener('DOMContentLoaded', function () {
		const name = document.getElementById('name');
		const slug = document.getElementById('slug');

		function makeSlug(str) {
			return str.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
		}

		let slugEdited = true;

		slug.addEventListener('input', function () {
			slugEdited = true;
		});

		name.addEventListener('input', function () {
			if (!slugEdited) {
				slug.value = makeSlug(name.value);
			}
		});
	});
</script>
