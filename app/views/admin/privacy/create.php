<div class="container mx-auto p-6">
	<div class="mb-6">
		<a href="/admin/privacy" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
			</svg>
			Torna alle versioni
		</a>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Nuova versione privacy</h1>

	<div class="bg-white rounded-lg shadow-lg p-8">
		<form action="/admin/privacy/store" method="POST" onsubmit="return syncPrivacyEditor()">
			<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">

			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">Versione</label>
					<input type="number" name="version_number" value="<?= $nextVersionNumber ?>" min="1" class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500" required>
				</div>
				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">Titolo</label>
					<input type="text" name="title" class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500" placeholder="Privacy Policy v1" required>
				</div>
			</div>

			<div class="mb-6">
				<label class="block text-gray-700 text-sm font-bold mb-2">Data pubblicazione</label>
				<input type="datetime-local" name="published_at" class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500">
			</div>

			<div class="mb-6">
				<label class="block text-gray-700 text-sm font-bold mb-2">Testo privacy</label>
				<div id="privacy-editor" class="bg-white rounded-lg min-h-[150px]"></div>
				<input type="hidden" name="content" id="privacy-content">
				<p class="text-sm text-gray-500 mt-2">Puoi inserire testo semplice con paragrafi separati da righe vuote.</p>
			</div>

			<label class="inline-flex items-center gap-2 mb-6">
				<input type="checkbox" name="is_active" value="1" class="rounded border-gray-300">
				<span class="text-sm text-gray-700">Imposta come versione attiva</span>
			</label>

			<div class="flex items-center justify-between">
				<button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg">Salva versione</button>
				<a href="/admin/privacy" class="text-gray-600 hover:text-gray-900">Annulla</a>
			</div>
		</form>
	</div>
</div>

<script src="/public_assets/js/wysiwyg-editor.js"></script>
<script>
	document.addEventListener('DOMContentLoaded', function () {
		const editor = WysiwygEditor.init('#privacy-editor', {
			name: null,
			placeholder: 'Scrivi qui il testo della privacy...',
			content: '',
		});

		const contentField = document.getElementById('privacy-content');

		window.syncPrivacyEditor = function () {
			const content = editor.getContent();
			if (!content || !content.replace(/<[^>]*>/g, '').trim()) {
				alert('Inserisci il testo della privacy.');
				return false;
			}
			contentField.value = content;
			return true;
		};
	});
</script>
