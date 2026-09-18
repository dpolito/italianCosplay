<?php
use App\Core\Session;
?>

<div class="container mx-auto p-6">
	<div class="mb-6 flex justify-between items-center">
		<a href="/admin/events-master" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
			</svg>
			Torna agli Eventi Master
		</a>
		<div>
			<?php if(isset($data['eventMaster']['id'])): ?>
				<form action="/admin/events-master/delete/<?php echo htmlspecialchars((string) $data['eventMaster']['id']); ?>" method="POST" class="inline-block" onsubmit="return confirm('Sei sicuro di voler eliminare questo evento master?');">
					<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">
					<button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-semibold rounded-lg shadow-md hover:bg-red-700 transition duration-300 ease-in-out">
						<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
							<path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
						</svg>
						Elimina
					</button>
				</form>
			<?php endif; ?>
		</div>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Modifica Evento Master: <?php echo htmlspecialchars($data['eventMaster']['nome'] ?? ''); ?></h1>

	<?php if($message = Session::getFlash('success')): ?>
		<div class="mb-4 p-3 rounded-lg shadow bg-green-100 text-green-800 border border-green-300">
			<?php echo htmlspecialchars($message) ?>
		</div>
	<?php endif; ?>

	<?php if($message = Session::getFlash('error')): ?>
		<div class="mb-4 p-3 rounded-lg shadow bg-red-100 text-red-800 border border-red-300">
			<?php echo htmlspecialchars($message) ?>
		</div>
	<?php endif; ?>

	<div class="bg-white rounded-lg shadow-lg p-8">
		<form action="/admin/events-master/update/<?php echo htmlspecialchars((string) ($data['eventMaster']['id'] ?? '')); ?>" method="POST" enctype="multipart/form-data" data-ajax-submit="true" data-success-redirect="/admin/events-master/edit/<?php echo htmlspecialchars((string) ($data['eventMaster']['id'] ?? '')); ?>">
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">

			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
				<div class="mb-4">
					<label for="status" class="block text-gray-700 text-sm font-bold mb-2">Stato:</label>
					<select id="status" name="status" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700">
						<?php foreach (['draft' => 'Bozza', 'pending_review' => 'In revisione', 'active' => 'Attivo', 'suspended' => 'Sospeso', 'archived' => 'Archiviato'] as $value => $label): ?><option value="<?php echo $value; ?>" <?php echo (($data['eventMaster']['status'] ?? 'active') === $value) ? 'selected' : ''; ?>><?php echo $label; ?></option><?php endforeach; ?>
					</select>
				</div>
				<div class="mb-4 flex items-end pb-2"><label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="is_public" value="1" <?php echo !empty($data['eventMaster']['is_public']) ? 'checked' : ''; ?>> Pubblico</label></div>
			</div>

			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
				<div class="mb-4">
					<label for="nome" class="block text-gray-700 text-sm font-bold mb-2">Nome:</label>
					<input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($data['eventMaster']['nome'] ?? ''); ?>" required class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline">
				</div>

				<div class="mb-4">
					<label for="slug" class="block text-gray-700 text-sm font-bold mb-2">Slug SEO:</label>
					<input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($data['eventMaster']['slug'] ?? ''); ?>" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline" placeholder="auto-generato se vuoto">
					<p class="text-xs text-gray-500 mt-1">URL finale: /eventi-master/slug</p>
				</div>

				<div class="mb-4">
					<label for="sito_web" class="block text-gray-700 text-sm font-bold mb-2">Sito web:</label>
					<input type="url" id="sito_web" name="sito_web" value="<?php echo htmlspecialchars($data['eventMaster']['sito_web'] ?? ''); ?>" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline">
				</div>
			</div>

			<div class="mb-4">
				<label for="descrizione" class="block text-gray-700 text-sm font-bold mb-2">Descrizione:</label>
				<div id="editor" class="bg-white rounded-lg min-h-[150px]"></div>
			</div>

			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
				<div class="mb-4">
					<label for="social_facebook" class="block text-gray-700 text-sm font-bold mb-2">Social Facebook:</label>
					<input type="url" id="social_facebook" name="social_facebook" value="<?php echo htmlspecialchars($data['eventMaster']['social_facebook'] ?? ''); ?>" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline">
				</div>
				<div class="mb-4">
					<label for="social_twitter" class="block text-gray-700 text-sm font-bold mb-2">Social Twitter:</label>
					<input type="url" id="social_twitter" name="social_twitter" value="<?php echo htmlspecialchars($data['eventMaster']['social_twitter'] ?? ''); ?>" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline">
				</div>
				<div class="mb-4">
					<label for="social_instagram" class="block text-gray-700 text-sm font-bold mb-2">Social Instagram:</label>
					<input type="url" id="social_instagram" name="social_instagram" value="<?php echo htmlspecialchars($data['eventMaster']['social_instagram'] ?? ''); ?>" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline">
				</div>
				<div class="mb-4">
					<label for="social_tiktok" class="block text-gray-700 text-sm font-bold mb-2">Social TikTok:</label>
					<input type="url" id="social_tiktok" name="social_tiktok" value="<?php echo htmlspecialchars($data['eventMaster']['social_tiktok'] ?? ''); ?>" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline">
				</div>
				<div class="mb-4">
					<label for="social_youtube" class="block text-gray-700 text-sm font-bold mb-2">Social YouTube:</label>
					<input type="url" id="social_youtube" name="social_youtube" value="<?php echo htmlspecialchars($data['eventMaster']['social_youtube'] ?? ''); ?>" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline">
				</div>
			</div>

			<div class="mb-4">
				<label for="immagine" class="block text-gray-700 text-sm font-bold mb-2">Immagine:</label>
				<input type="file" id="immagine" name="immagine" accept="image/jpeg,image/png,image/webp" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
				<?php if (!empty($data['cover']['url'] ?? '')): ?>
					<div class="mt-3">
						<p class="text-sm text-gray-600">Immagine attuale:</p>
						<img src="<?php echo htmlspecialchars($data['cover']['url']); ?>" alt="<?php echo htmlspecialchars($data['eventMaster']['nome'] ?? 'Evento master'); ?>" class="w-32 h-auto rounded-lg shadow-md mt-1">
					</div>
				<?php endif; ?>
			</div>

			<div class="mt-6 flex items-center justify-between">
				<a href="/admin/events-master" class="font-semibold text-green-600 hover:text-green-800">Annulla</a>
				<button type="submit" class="inline-flex items-center px-5 py-2.5 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 transition duration-300 ease-in-out">
					Aggiorna evento master
				</button>
			</div>
		</form>
	</div>
</div>

<script src="/public_assets/js/wysiwyg-editor.js"></script>
<script>
	document.addEventListener('DOMContentLoaded', function () {
		const nome = document.getElementById('nome');
		const slug = document.getElementById('slug');
		let slugEdited = slug.value.trim() !== '';

		function makeSlug(value) {
			return value
				.toLowerCase()
				.normalize('NFD')
				.replace(/[\u0300-\u036f]/g, '')
				.replace(/[^a-z0-9]+/g, '-')
				.replace(/(^-|-$)/g, '');
		}

		slug.addEventListener('input', function () {
			slugEdited = true;
		});

		nome.addEventListener('input', function () {
			if (!slugEdited) {
				slug.value = makeSlug(nome.value);
			}
		});

		WysiwygEditor.init('#editor', {
			content: <?php echo json_encode($data['eventMaster']['descrizione'] ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
			name: 'descrizione',
			placeholder: 'Scrivi la descrizione del master...',
		});
	});
</script>
