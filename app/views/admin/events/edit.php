<?php
// Questo file è un frammento di HTML e deve essere incluso in un layout admin.
// Non contiene i tag <html>, <head>, <body> completi.
?>

<div class="container mx-auto p-6">
	<div class="mb-6 flex justify-between items-center">
		<a href="/admin/events/all" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
			</svg>
			Torna a Tutti gli Eventi
		</a>
		<div>
			<?php use App\Core\Session;

			if(isset($data['event']['id'])): ?>
				<form action="<?php echo htmlspecialchars(URL_ROOT_SITE . '/admin/events/copy/' . $data['event']['id']); ?>" method="POST" class="inline-block mr-2" onsubmit="return confirm('Vuoi creare una copia di questo evento? La nuova edizione sarà in attesa di approvazione.');">
					<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">
					<button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 transition duration-300 ease-in-out">
						<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
							<path stroke-linecap="round" stroke-linejoin="round" d="M8 16h8M8 12h8m-7-8h5a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V8l3-4zM7 4v4h4"/>
						</svg>
						Copia evento
					</button>
				</form>
				<form action="/admin/events/delete/<?php echo htmlspecialchars($data['event']['id']); ?>" method="POST" class="inline-block" onsubmit="return confirm('Sei sicuro di voler eliminare questo evento?');">
					<!-- CSRF Token per il form di eliminazione -->
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

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Modifica Evento: <?php echo htmlspecialchars($data['event']['titolo'] ?? ''); ?></h1>

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
		<form id="eventForm" action="/admin/events/update/<?php echo htmlspecialchars($data['event']['id'] ?? ''); ?>" method="POST" enctype="multipart/form-data" data-ajax-submit="true" data-success-redirect="/admin/events/edit/<?php echo htmlspecialchars($data['event']['id'] ?? ''); ?>">
			<input type="hidden" name="id" value="<?php echo htmlspecialchars($data['event']['id'] ?? ''); ?>">
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">
			<input type="hidden" name="guest_ids" id="guest_ids">
			<!-- Titolo -->
			<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
				<div class="mb-4">
					<label for="titolo" class="block text-gray-700 text-sm font-bold mb-2">Titolo:</label>
					<input type="text" id="titolo" name="titolo" value="<?php echo htmlspecialchars($data['event']['titolo'] ?? ''); ?>" required class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline">
				</div>
				<div class="mb-4">
					<label for="slug" class="block text-gray-700 text-sm font-bold mb-2">Slug SEO:</label>
					<input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($data['event']['slug'] ?? ''); ?>" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline" placeholder="auto-generato se vuoto">
					<p class="text-xs text-gray-500 mt-1">URL finale: /eventi/slug</p>
				</div>
				<div class="mb-4">
					<label for="anno" class="block text-gray-700 text-sm font-bold mb-2">Anno:</label>
					<input type="number" id="anno" name="anno" value="<?php echo htmlspecialchars((string) ($data['event']['year'] ?? date('Y'))); ?>" min="2000" max="2100" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline">
				</div>
				<div class="mb-4">
					<label for="tipo_evento_id" class="block text-gray-700 text-sm font-bold mb-2">Tipo Evento:</label>
					<select id="tipo_evento_id" name="tipo_evento_id" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
						<option value="">Seleziona un tipo di evento</option>
						<?php if(!empty($data['tipi_evento'])):
							foreach($data['tipi_evento'] as $tipo): ?>
								<option value="<?php echo htmlspecialchars($tipo['id']); ?>"
									<?php echo (isset($data['event']['tipo_evento_id']) && $data['event']['tipo_evento_id'] == $tipo['id']) ? 'selected' : ''; ?>>
									<?php echo htmlspecialchars($tipo['nome']); ?>
								</option>
							<?php endforeach; endif; ?>
					</select>
				</div>
				<div class="mb-4">
					<label for="event_master_id" class="block text-gray-700 text-sm font-bold mb-2">Evento Master:</label>
					<select id="event_master_id" name="event_master_id" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
						<option value="">Seleziona un evento master</option>
						<?php if(!empty($data['event_masters'])):
							foreach($data['event_masters'] as $eventMaster): ?>
								<option value="<?php echo htmlspecialchars($eventMaster['id']); ?>"
									<?php echo (isset($data['event']['event_master_id']) && $data['event']['event_master_id'] == $eventMaster['id']) ? 'selected' : ''; ?>>
									<?php echo htmlspecialchars($eventMaster['nome'] ?? $eventMaster['name'] ?? 'Evento master'); ?>
								</option>
							<?php endforeach; endif; ?>
					</select>
				</div>
				<div class="mb-6">
					<label for="approvato" class="block text-gray-700 text-sm font-bold mb-2">Approvato:</label>
					<select id="approvato" name="approvato" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
						<option value="0" <?php echo (isset($data['event']['approvato']) && $data['event']['approvato'] == 0) ? 'selected' : ''; ?>>In Attesa</option>
						<option value="1" <?php echo (isset($data['event']['approvato']) && $data['event']['approvato'] == 1) ? 'selected' : ''; ?>>Approvato</option>
					</select>
				</div>
				<div class="mb-6">
					<label for="event_size" class="block text-gray-700 text-sm font-bold mb-2">Size:</label>
					<input type="text" id="event_size" name="event_size" value="<?php echo htmlspecialchars($data['event']['event_size'] ?? ''); ?>" required class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline">
				</div>
			</div>

			<!-- Descrizione -->
			<div class="mb-4">
				<label class="block text-gray-700 text-sm font-bold mb-2">Descrizione:</label>
				<div id="editor"></div>
			</div>

			<div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
				<h3 class="text-lg font-semibold text-gray-900 mb-2">SEO personalizzata (opzionale)</h3>
				<p class="text-sm text-gray-600 mb-4">Se lasci i campi vuoti, vengono mantenuti i meta tag automatici dell'evento.</p>
				<label for="seo_title" class="block text-gray-700 text-sm font-bold mb-2">SEO Title:</label>
				<input type="text" id="seo_title" name="seo_title" maxlength="255" value="<?php echo htmlspecialchars($data['seo_title'] ?? $data['event']['seo_title'] ?? ''); ?>" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
				<label for="seo_description" class="block text-gray-700 text-sm font-bold mt-4 mb-2">SEO Description:</label>
				<textarea id="seo_description" name="seo_description" maxlength="500" rows="3" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500"><?php echo htmlspecialchars($data['seo_description'] ?? $data['event']['seo_description'] ?? ''); ?></textarea>
			</div>

			<!-- Date (2 colonne) -->
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
				<div>
					<label for="data_inizio" class="block text-gray-700 text-sm font-bold mb-2">Data Inizio:</label>
					<input type="date" id="data_inizio" name="data_inizio" value="<?php echo htmlspecialchars($data['event']['data_inizio'] ?? ''); ?>" required class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline">
				</div>
				<div>
					<label for="data_fine" class="block text-gray-700 text-sm font-bold mb-2">Data Fine:</label>
					<input type="date" id="data_fine" name="data_fine" value="<?php echo htmlspecialchars($data['event']['data_fine'] ?? ''); ?>" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline">
				</div>
			</div>

			<!-- Luogo -->
			<div class="mb-4">
				<label for="luogo" class="block text-gray-700 text-sm font-bold mb-2">Luogo:</label>
				<input type="text" id="luogo" name="luogo" value="<?php echo htmlspecialchars($data['event']['luogo'] ?? ''); ?>" required class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
			</div>

			<!-- Regione/Provincia/Comune (3 colonne) -->
			<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
				<div>
					<label for="regione_id" class="block text-gray-700 text-sm font-bold mb-2">Regione:</label>
					<select id="regione_id" name="regione_id" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
						<option value="">Caricamento Regioni...</option>
					</select>
				</div>
				<div>
					<label for="provincia_id" class="block text-gray-700 text-sm font-bold mb-2">Provincia:</label>
					<select id="provincia_id" name="provincia_id" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500" disabled>
						<option value="">Seleziona Regione prima</option>
					</select>
				</div>
				<div>
					<label for="comune_id" class="block text-gray-700 text-sm font-bold mb-2">Comune:</label>
					<select id="comune_id" name="comune_id" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500" disabled>
						<option value="">Seleziona Provincia prima</option>
					</select>
				</div>
			</div>

			<!-- Latitudine/Longitudine (2 colonne) -->
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
				<div>
					<label for="latitudine" class="block text-gray-700 text-sm font-bold mb-2">Latitudine:</label>
					<input type="number" step="0.0000001" id="latitudine" name="latitudine" value="<?php echo htmlspecialchars($data['event']['latitudine'] ?? ''); ?>" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
				</div>
				<div>
					<label for="longitudine" class="block text-gray-700 text-sm font-bold mb-2">Longitudine:</label>
					<input type="number" step="0.0000001" id="longitudine" name="longitudine" value="<?php echo htmlspecialchars($data['event']['longitudine'] ?? ''); ?>" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
				</div>
			</div>

			<!-- Sito Web -->
			<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
				<div class="mb-4">
					<label for="sito_web" class="block text-gray-700 text-sm font-bold mb-2">Sito Web:</label>
					<input type="text" id="sito_web" name="sito_web" value="<?php echo htmlspecialchars($data['event']['sito_web'] ?? ''); ?>" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
				</div>

				<div class="mb-4">
					<label for="social_facebook" class="block text-gray-700 text-sm font-bold mb-2">Social Facebook:</label>
					<input type="text" id="social_facebook" name="social_facebook" value="<?php echo htmlspecialchars($data['event']['social_facebook'] ?? ''); ?>" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
				</div>
				<div class="mb-4">
					<label for="social_instagram" class="block text-gray-700 text-sm font-bold mb-2">Social Instagram:</label>
					<input type="text" id="social_instagram" name="social_instagram" value="<?php echo htmlspecialchars($data['event']['social_instagram'] ?? ''); ?>" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
				</div>
				<div class="mb-4">
					<label for="social_tiktok" class="block text-gray-700 text-sm font-bold mb-2">Social TikTok:</label>
					<input type="text" id="social_tiktok" name="social_tiktok" value="<?php echo htmlspecialchars($data['event']['social_tiktok'] ?? ''); ?>" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
				</div>
				<div class="mb-4">
					<label for="social_youtube" class="block text-gray-700 text-sm font-bold mb-2">Social YouTube:</label>
					<input type="text" id="social_youtube" name="social_youtube" value="<?php echo htmlspecialchars($data['event']['social_youtube'] ?? ''); ?>" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
				</div>
			</div>
			<!-- Tipo evento -->

			<!-- Immagine -->
			<div class="mb-4">
				<label for="immagine" class="block text-gray-700 text-sm font-bold mb-2">Carica Nuova Immagine:</label>
				<input type="file" id="immagine" name="immagine" accept="image/jpeg,image/png,image/gif,image/webp" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
				<?php if(!empty($data['event']['immagine'])): ?>
				<input type="text" style="display: none" name="id_immagine" id="id_immagine" value="<?php echo htmlspecialchars($data['event']['id_immagine']); ?>"
					<div class="mt-2">
						<p class="text-sm text-gray-600">Immagine attuale:</p>
						<img src="<?php echo htmlspecialchars($data['event']['immagine']); ?>" alt="Immagine Evento Corrente" class="w-32 h-auto rounded-lg shadow-md mt-1">
					</div>
				<?php endif; ?>
			</div>
			<div class="mt-6 bg-white dark:bg-gray-800 shadow rounded-lg p-6">

				<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
					Attributi evento
				</h3>

				<!-- Evento a pagamento -->
				<div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">

					<div>
						<p class="text-sm font-medium text-gray-900 dark:text-white">
							Evento a pagamento
						</p>
						<p class="text-xs text-gray-500 dark:text-gray-400">
							Indica se l’evento richiede un biglietto
						</p>
					</div>

					<label class="relative inline-flex items-center cursor-pointer">
						<input
								type="checkbox"
								name="is_paid"
								value="1"
								class="sr-only peer"
							<?php echo !empty($event['is_paid']) ? 'checked' : ''; ?>
						>
						<div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer
                  peer-checked:after:translate-x-full
                  peer-checked:after:border-white after:content-[''] after:absolute
                  after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300
                  after:border after:rounded-full after:h-5 after:w-5 after:transition-all
                  peer-checked:bg-blue-600"></div>
					</label>

				</div>

				<!-- Gara cosplay -->
				<div class="flex items-center justify-between py-3">

					<div>
						<p class="text-sm font-medium text-gray-900 dark:text-white">
							Gara cosplay presente
						</p>
						<p class="text-xs text-gray-500 dark:text-gray-400">
							L’evento include una competizione cosplay
						</p>
					</div>

					<label class="relative inline-flex items-center cursor-pointer">
						<input
								type="checkbox"
								name="has_cosplay_contest"
								value="1"
								class="sr-only peer"
							<?php echo !empty($event['has_cosplay_contest']) ? 'checked' : ''; ?>
						>
						<div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer
                  peer-checked:after:translate-x-full
                  peer-checked:after:border-white after:content-[''] after:absolute
                  after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300
                  after:border after:rounded-full after:h-5 after:w-5 after:transition-all
                  peer-checked:bg-blue-600"></div>
					</label>

				</div>

			</div>
			<div class="mt-6 bg-white dark:bg-gray-800 shadow rounded-lg p-6">

				<div class="flex items-center justify-between mb-4">
					<h3 class="text-lg font-semibold text-gray-900 dark:text-white">
						Ospiti evento
					</h3>

					<button
							type="button"
							onclick="openGuestModal()"
							class="px-3 py-1.5 text-sm rounded bg-blue-600 text-white hover:bg-blue-700"
					>
						+ Nuovo ospite
					</button>
				</div>

				<!-- AUTOCOMPLETE INPUT -->
				<div class="relative">

					<input
							type="text"
							id="guest_search"
							placeholder="Cerca ospite..."
							class="w-full border rounded px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
							oninput="searchGuests(this.value)"
					>

					<!-- DROPDOWN RESULTS -->
					<div
							id="guest_results"
							class="absolute z-10 w-full bg-white dark:bg-gray-700 border rounded mt-1 hidden max-h-60 overflow-auto"
					></div>

				</div>

				<!-- SELECTED GUESTS -->
				<div id="selected_guests" class="flex flex-wrap gap-2 mt-4"></div>

			</div>
			<div id="guest_modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">

				<div class="bg-white dark:bg-gray-800 w-full max-w-md rounded-lg p-6">

					<h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
						Nuovo ospite
					</h3>

					<input id="new_guest_name"
					       class="w-full border rounded px-3 py-2 mb-3 dark:bg-gray-700 dark:text-white"
					       placeholder="Nome ospite">

					<button type="button" onclick="createGuest()" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
						Salva ospite
					</button>

					<button  type="button"
					         onclick="closeGuestModal()"
							class="w-full mt-2 text-sm text-gray-500"
					>
						Annulla
					</button>

				</div>

			</div>
			<!-- Pulsanti -->
			<div class="flex items-center justify-between">
				<button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300">Aggiorna Evento</button>
				<a href="/admin/events/all" class="font-semibold text-green-600 hover:text-green-800">Annulla</a>
			</div>
		</form>
	</div>

</div>
<script src="/public_assets/js/wysiwyg-editor.js"></script>
<script>
	document.addEventListener('DOMContentLoaded', function () {
		const titolo = document.getElementById('titolo');
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

		titolo.addEventListener('input', function () {
			if (!slugEdited) {
				slug.value = makeSlug(titolo.value);
			}
		});
	});
</script>
<script>
	WysiwygEditor.init('#editor', {
		name: 'descrizione',
		placeholder: 'Scrivi qui il tuo testo...',
		content: <?php echo json_encode($event['descrizione'] ?? '<p></p>', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
	});
</script>
<script>
	let selectedGuests = <?= json_encode($event['guests']) ?>;
	renderGuests();
	document.getElementById('guest_search').addEventListener('keydown', function(e) {
		if (e.key === 'Enter') {
			e.preventDefault();
		}
	});

	function searchGuests(query) {
		if (query.length < 2) {
			document.getElementById('guest_results').classList.add('hidden');
			return;
		}

		fetch(`/admin/guests/search?q=${encodeURIComponent(query)}`)
			.then(res => res.json())
			.then(data => {
				let box = document.getElementById('guest_results');
				box.innerHTML = '';
				box.classList.remove('hidden');

				data.forEach(g => {
					let div = document.createElement('div');
					div.className = "px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer";
					div.innerHTML = g.name;

					div.onclick = () => addGuest(g);

					box.appendChild(div);
				});
			});
	}

	function addGuest(guest) {
		if (selectedGuests.find(g => g.id === guest.id)) return;

		selectedGuests.push(guest);
		renderGuests();

		document.getElementById('guest_results').classList.add('hidden');
		document.getElementById('guest_search').value = '';
	}

	function renderGuests() {

		let box = document.getElementById('selected_guests');
		box.innerHTML = '';

		selectedGuests.forEach(g => {
			let el = document.createElement('div');

			el.className = "px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm flex items-center gap-2";

			el.innerHTML = `
            ${g.name}
            <span onclick="removeGuest(${g.id})" class="cursor-pointer font-bold">×</span>
        `;

			box.appendChild(el);
		});

		// 🔥 QUESTO È IL FIX IMPORTANTE
		document.getElementById('guest_ids').value =
			JSON.stringify(selectedGuests.map(g => g.id));
	}

	function removeGuest(id) {
		selectedGuests = selectedGuests.filter(g => g.id !== id);
		renderGuests();
	}
	function openGuestModal() {
		document.getElementById('guest_modal').classList.remove('hidden');
		document.getElementById('guest_modal').classList.add('flex');
	}

	function closeGuestModal() {
		document.getElementById('guest_modal').classList.add('hidden');
	}
	function createGuest() {
		let name = document.getElementById('new_guest_name').value;

		fetch('/admin/guests/create', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Accept': 'application/json'
			},
			body: JSON.stringify({ name })
		})
			.then(async res => {
				const text = await res.text(); // debug utile
				try {
					return JSON.parse(text);
				} catch (e) {
					console.error("Risposta non JSON:", text);
					throw new Error("Invalid JSON response");
				}
			})
			.then(g => {
				if (!g.id) {
					console.error("Errore creazione:", g);
					return;
				}

				addGuest(g);
				closeGuestModal();

				document.getElementById('new_guest_name').value = '';
			})
			.catch(err => {
				console.error("Errore fetch:", err);
			});
	}



	// --- Logica per le Dropdown Dinamiche (Regioni, Province, Comuni) ---
	document.addEventListener('DOMContentLoaded', function(){
		const regioneSelect = document.getElementById('regione_id');
		const provinciaSelect = document.getElementById('provincia_id');
		const comuneSelect = document.getElementById('comune_id');

		// Valori iniziali dell'evento (se presenti)
		const initialRegioneId = "<?php echo htmlspecialchars($data['event']['regione_id'] ?? ''); ?>";
		const initialProvinciaId = "<?php echo htmlspecialchars($data['event']['provincia_id'] ?? ''); ?>";
		const initialComuneId = "<?php echo htmlspecialchars($data['event']['comune_id'] ?? ''); ?>";

		// Funzione generica per recuperare dati JSON da un URL
		async function fetchData(relativePath){
			try {
				// Prepend window.location.origin per rendere l'URL assoluto
				const url = window.location.origin + relativePath;
				const response = await fetch(url);
				if(!response.ok){
					// Logga l'URL esatto che ha causato l'errore 404 per facilitare il debug del backend
					console.error(`Errore HTTP ${response.status} per l'URL: ${url}`);
					throw new Error(`HTTP error! status: ${response.status}`);
				}
				return await response.json();
			} catch(error) {
				console.error("Errore nel recupero dei dati:", error);
				return [];
			}
		}

		// Funzione generica per popolare un dropdown
		function populateDropdown(dropdownElement, data, selectedValue = null, placeholderText = "Seleziona..."){
			dropdownElement.innerHTML = `<option value="">${placeholderText}</option>`;
			data.forEach(item => {
				const option = document.createElement('option');
				option.value = item.id; // Assumendo che l'API restituisca 'id'
				option.textContent = item.nome; // Assumendo che l'API restituisca 'nome'
				if(selectedValue && selectedValue == item.id){
					option.selected = true;
				}
				dropdownElement.appendChild(option);
			});
			dropdownElement.disabled = false; // Riabilita il dropdown dopo il caricamento
		}

		// Carica le regioni all'avvio della pagina
		async function loadRegioni(){
			const regioni = await fetchData('/api/regioni'); // Sostituisci con il tuo endpoint API reale
			populateDropdown(regioneSelect, regioni, initialRegioneId, "Seleziona Regione");
			if(initialRegioneId){
				await loadProvince(initialRegioneId); // Carica le province se la regione iniziale è impostata
			}
		}

		// Carica le province in base alla regione selezionata
		async function loadProvince(regioneId){
			provinciaSelect.innerHTML = '<option value="">Caricamento Province...</option>';
			provinciaSelect.disabled = true;
			comuneSelect.innerHTML = '<option value="">Seleziona Comune</option>';
			comuneSelect.disabled = true;

			const province = await fetchData(`/api/province/${regioneId}`); // Sostituisci con il tuo endpoint API reale
			populateDropdown(provinciaSelect, province, initialProvinciaId, "Seleziona Provincia");
			if(initialProvinciaId){ // Solo se la provincia iniziale è stata trovata
				await loadComuni(initialProvinciaId); // Carica i comuni se la provincia iniziale è impostata
			}
		}

		// Carica i comuni in base alla provincia selezionata
		async function loadComuni(provinciaId){
			comuneSelect.innerHTML = '<option value="">Caricamento Comuni...</option>';
			comuneSelect.disabled = true;

			const comuni = await fetchData(`/api/comuni/${provinciaId}`); // Sostituisci con il tuo endpoint API reale
			populateDropdown(comuneSelect, comuni, initialComuneId, "Seleziona Comune");
		}

		// Listener per il cambio della regione
		regioneSelect.addEventListener('change', async(event) => {
			const selectedRegioneId = event.target.value;
			if(selectedRegioneId){
				await loadProvince(selectedRegioneId);
			} else{
				// Reset province e comuni se la regione non è selezionata
				provinciaSelect.innerHTML = '<option value="">Seleziona Provincia</option>';
				provinciaSelect.disabled = true;
				comuneSelect.innerHTML = '<option value="">Seleziona Comune</option>';
				comuneSelect.disabled = true;
			}
		});

		// Listener per il cambio della provincia
		provinciaSelect.addEventListener('change', async(event) => {
			const selectedProvinciaId = event.target.value;
			if(selectedProvinciaId){
				await loadComuni(selectedProvinciaId);
			} else{
				// Reset comuni se la provincia non è selezionata
				comuneSelect.innerHTML = '<option value="">Seleziona Comune</option>';
				comuneSelect.disabled = true;
			}
		});

		// Avvia il caricamento delle regioni all'inizio
		loadRegioni();
	});
</script>
