<?php
// Questo file è un frammento di HTML e deve essere incluso in un layout admin.
// Non contiene i tag <html>, <head>, <body> completi.
?>

<div class="container mx-auto p-6">
	<div class="mb-6 flex justify-between items-center">
		<a href="/admin/events/all" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
			</svg>
			Torna a Tutti gli Eventi
		</a>
		<div>
			<?php if (isset($data['event']['id'])): ?>
				<form action="/admin/events/delete/<?php echo htmlspecialchars($data['event']['id']); ?>" method="POST" class="inline-block" onsubmit="return confirm('Sei sicuro di voler eliminare questo evento?');">
					<!-- CSRF Token per il form di eliminazione -->
					<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">
					<button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-semibold rounded-lg shadow-md hover:bg-red-700 transition duration-300 ease-in-out">
						<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
							<path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
						</svg>
						Elimina
					</button>
				</form>
			<?php endif; ?>
		</div>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Modifica Evento: <?php echo htmlspecialchars($data['event']['titolo'] ?? ''); ?></h1>

	<?php
	// Visualizza i messaggi flash o errori passati direttamente
	if (isset($_SESSION['flash_messages'])) {
		foreach ($_SESSION['flash_messages'] as $type => $message) {
			echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
		}
		unset($_SESSION['flash_messages']);
	} elseif (isset($data['error'])) {
		echo '<div class="flash-message error">' . htmlspecialchars($data['error']) . '</div>';
	}
	?>

	<div class="bg-white rounded-lg shadow-lg p-8">
		<form id="eventForm" action="/admin/events/update/<?php echo htmlspecialchars($data['event']['id'] ?? ''); ?>" method="POST">
			<input type="hidden" name="id" value="<?php echo htmlspecialchars($data['event']['id'] ?? ''); ?>">
			<!-- Campo CSRF Token -->
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">

			<div class="mb-4">
				<label for="titolo" class="block text-gray-700 text-sm font-bold mb-2">Titolo:</label>
				<input type="text" id="titolo" name="titolo" value="<?php echo htmlspecialchars($data['event']['titolo'] ?? ''); ?>" required class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="descrizione" class="block text-gray-700 text-sm font-bold mb-2">Descrizione:</label>
				<!-- Div per Quill.js -->
				<div id="editor" class="bg-white border border-gray-300 rounded-lg p-3 min-h-[150px] focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"></div>
				<!-- Input hidden per inviare il contenuto HTML di Quill -->
				<input type="hidden" name="descrizione" id="descrizione_hidden" value="<?php echo htmlspecialchars($data['event']['descrizione'] ?? ''); ?>">
			</div>

			<div class="mb-4">
				<label for="data_inizio" class="block text-gray-700 text-sm font-bold mb-2">Data Inizio:</label>
				<input type="date" id="data_inizio" name="data_inizio" value="<?php echo htmlspecialchars($data['event']['data_inizio'] ?? ''); ?>" required class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="data_fine" class="block text-gray-700 text-sm font-bold mb-2">Data Fine:</label>
				<input type="date" id="data_fine" name="data_fine" value="<?php echo htmlspecialchars($data['event']['data_fine'] ?? ''); ?>" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="luogo" class="block text-gray-700 text-sm font-bold mb-2">Luogo:</label>
				<input type="text" id="luogo" name="luogo" value="<?php echo htmlspecialchars($data['event']['luogo'] ?? ''); ?>" required class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="regione_id" class="block text-gray-700 text-sm font-bold mb-2">Regione:</label>
				<select id="regione_id" name="regione_id" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
					<option value="">Caricamento Regioni...</option>
				</select>
			</div>

			<div class="mb-4">
				<label for="provincia_id" class="block text-gray-700 text-sm font-bold mb-2">Provincia:</label>
				<select id="provincia_id" name="provincia_id" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500" disabled>
					<option value="">Seleziona Regione prima</option>
				</select>
			</div>

			<div class="mb-4">
				<label for="comune_id" class="block text-gray-700 text-sm font-bold mb-2">Comune:</label>
				<select id="comune_id" name="comune_id" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500" disabled>
					<option value="">Seleziona Provincia prima</option>
				</select>
			</div>

			<div class="mb-4">
				<label for="latitudine" class="block text-gray-700 text-sm font-bold mb-2">Latitudine:</label>
				<input type="number" step="0.0000001" id="latitudine" name="latitudine" value="<?php echo htmlspecialchars($data['event']['latitudine'] ?? ''); ?>" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="longitudine" class="block text-gray-700 text-sm font-bold mb-2">Longitudine:</label>
				<input type="number" step="0.0000001" id="longitudine" name="longitudine" value="<?php echo htmlspecialchars($data['event']['longitudine'] ?? ''); ?>" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="sito_web" class="block text-gray-700 text-sm font-bold mb-2">Sito Web:</label>
				<input type="text" id="sito_web" name="sito_web" value="<?php echo htmlspecialchars($data['event']['sito_web'] ?? ''); ?>" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="social_facebook" class="block text-gray-700 text-sm font-bold mb-2">Social Facebook:</label>
				<input type="text" id="social_facebook" name="social_facebook" value="<?php echo htmlspecialchars($data['event']['social_facebook'] ?? ''); ?>" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="social_twitter" class="block text-gray-700 text-sm font-bold mb-2">Social Twitter:</label>
				<input type="text" id="social_twitter" name="social_twitter" value="<?php echo htmlspecialchars($data['event']['social_twitter'] ?? ''); ?>" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="social_instagram" class="block text-gray-700 text-sm font-bold mb-2">Social Instagram:</label>
				<input type="text" id="social_instagram" name="social_instagram" value="<?php echo htmlspecialchars($data['event']['social_instagram'] ?? ''); ?>" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="social_tiktok" class="block text-gray-700 text-sm font-bold mb-2">Social TikTok:</label>
				<input type="text" id="social_tiktok" name="social_tiktok" value="<?php echo htmlspecialchars($data['event']['social_tiktok'] ?? ''); ?>" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="social_youtube" class="block text-gray-700 text-sm font-bold mb-2">Social YouTube:</label>
				<input type="text" id="social_youtube" name="social_youtube" value="<?php echo htmlspecialchars($data['event']['social_youtube'] ?? ''); ?>" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="tipo_evento_id" class="block text-gray-700 text-sm font-bold mb-2">ID Tipo Evento:</label>
				<input type="text" id="tipo_evento_id" name="tipo_evento_id" value="<?php echo htmlspecialchars($data['event']['tipo_evento_id'] ?? ''); ?>" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="immagine" class="block text-gray-700 text-sm font-bold mb-2">URL Immagine (Opzionale):</label>
				<input type="text" id="immagine" name="immagine" value="<?php echo htmlspecialchars($data['event']['immagine'] ?? ''); ?>" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-6">
				<label for="approvato" class="block text-gray-700 text-sm font-bold mb-2">Approvato:</label>
				<select id="approvato" name="approvato" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
					<option value="0" <?php echo (isset($data['event']['approvato']) && $data['event']['approvato'] == 0) ? 'selected' : ''; ?>>In Attesa</option>
					<option value="1" <?php echo (isset($data['event']['approvato']) && $data['event']['approvato'] == 1) ? 'selected' : ''; ?>>Approvato</option>
				</select>
			</div>

			<div class="flex items-center justify-between">
				<button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
					Aggiorna Evento
				</button>
				<a href="/admin/events/all" class="inline-block align-baseline font-semibold text-blue-600 hover:text-blue-800">
					Annulla
				</a>
			</div>
		</form>
	</div>
</div>

<!-- Inclusione di Quill.js CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<!-- Inclusione di Quill.js JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Inizializzazione di Quill.js
		var quill = new Quill('#editor', {
			theme: 'snow', // Tema 'snow' per una barra degli strumenti visibile
			modules: {
				toolbar: [
					[{ 'header': [1, 2, 3, 4, 5, 6, false] }],
					['bold', 'italic', 'underline', 'strike'],        // formattazioni base
					['blockquote', 'code-block'],
					[{ 'list': 'ordered'}, { 'list': 'bullet' }],
					[{ 'script': 'sub'}, { 'script': 'super' }],      // script superscript/subscript
					[{ 'indent': '-1'}, { 'indent': '+1' }],          // indentazione
					[{ 'direction': 'rtl' }],                         // direzione testo
					[{ 'color': [] }, { 'background': [] }],          // colore testo e sfondo
					[{ 'font': [] }],
					[{ 'align': [] }],
					['link', 'image', 'video'], // Inserimento link, immagine, video
					['clean']                                         // rimuovi formattazione
				]
			}
		});

		// Imposta il contenuto iniziale dell'editor dal campo nascosto
		var initialContent = document.getElementById('descrizione_hidden').value;
		quill.root.innerHTML = initialContent;

		// Gestione della sottomissione del form
		var form = document.getElementById('eventForm');
		form.onsubmit = function() {
			// Aggiorna il valore del campo hidden 'descrizione' con il contenuto HTML di Quill
			document.getElementById('descrizione_hidden').value = quill.root.innerHTML;
			return true; // Permette la sottomissione del form
		};

		// --- Logica per le Dropdown Dinamiche (Regioni, Province, Comuni) ---

		const regioneSelect = document.getElementById('regione_id');
		const provinciaSelect = document.getElementById('provincia_id');
		const comuneSelect = document.getElementById('comune_id');

		// Valori iniziali dell'evento (se presenti)
		const initialRegioneId = "<?php echo htmlspecialchars($data['event']['regione_id'] ?? ''); ?>";
		const initialProvinciaId = "<?php echo htmlspecialchars($data['event']['provincia_id'] ?? ''); ?>";
		const initialComuneId = "<?php echo htmlspecialchars($data['event']['comune_id'] ?? ''); ?>";

		// Funzione generica per recuperare dati JSON da un URL
		async function fetchData(relativePath) {
			try {
				// Prepend window.location.origin per rendere l'URL assoluto
				const url = window.location.origin + relativePath;
				const response = await fetch(url);
				if (!response.ok) {
					throw new Error(`HTTP error! status: ${response.status}`);
				}
				return await response.json();
			} catch (error) {
				console.error("Errore nel recupero dei dati:", error);
				return [];
			}
		}

		// Funzione generica per popolare un dropdown
		function populateDropdown(dropdownElement, data, selectedValue = null, placeholderText = "Seleziona...") {
			dropdownElement.innerHTML = `<option value="">${placeholderText}</option>`;
			data.forEach(item => {
				const option = document.createElement('option');
				option.value = item.id; // Assumendo che l'API restituisca 'id'
				option.textContent = item.nome; // Assumendo che l'API restituisca 'nome'
				if (selectedValue && selectedValue == item.id) {
					option.selected = true;
				}
				dropdownElement.appendChild(option);
			});
			dropdownElement.disabled = false; // Riabilita il dropdown dopo il caricamento
		}

		// Carica le regioni all'avvio della pagina
		async function loadRegioni() {
			const regioni = await fetchData('/api/regioni'); // Sostituisci con il tuo endpoint API reale
			populateDropdown(regioneSelect, regioni, initialRegioneId, "Seleziona Regione");
			if (initialRegioneId) {
				await loadProvince(initialRegioneId); // Carica le province se la regione iniziale è impostata
			}
		}

		// Carica le province in base alla regione selezionata
		async function loadProvince(regioneId) {
			provinciaSelect.innerHTML = '<option value="">Caricamento Province...</option>';
			provinciaSelect.disabled = true;
			comuneSelect.innerHTML = '<option value="">Seleziona Comune</option>';
			comuneSelect.disabled = true;

			const province = await fetchData(`/api/province/${regioneId}`); // Sostituisci con il tuo endpoint API reale
			populateDropdown(provinciaSelect, province, initialProvinciaId, "Seleziona Provincia");
			if (initialProvinciaId) { // Solo se la provincia iniziale è stata trovata
				await loadComuni(initialProvinciaId); // Carica i comuni se la provincia iniziale è impostata
			}
		}

		// Carica i comuni in base alla provincia selezionata
		async function loadComuni(provinciaId) {
			comuneSelect.innerHTML = '<option value="">Caricamento Comuni...</option>';
			comuneSelect.disabled = true;

			const comuni = await fetchData(`/api/comuni/${provinciaId}`); // Sostituisci con il tuo endpoint API reale
			populateDropdown(comuneSelect, comuni, initialComuneId, "Seleziona Comune");
		}

		// Listener per il cambio della regione
		regioneSelect.addEventListener('change', async (event) => {
			const selectedRegioneId = event.target.value;
			if (selectedRegioneId) {
				await loadProvince(selectedRegioneId);
			} else {
				// Reset province e comuni se la regione non è selezionata
				provinciaSelect.innerHTML = '<option value="">Seleziona Provincia</option>';
				provinciaSelect.disabled = true;
				comuneSelect.innerHTML = '<option value="">Seleziona Comune</option>';
				comuneSelect.disabled = true;
			}
		});

		// Listener per il cambio della provincia
		provinciaSelect.addEventListener('change', async (event) => {
			const selectedProvinciaId = event.target.value;
			if (selectedProvinciaId) {
				await loadComuni(selectedProvinciaId);
			} else {
				// Reset comuni se la provincia non è selezionata
				comuneSelect.innerHTML = '<option value="">Seleziona Comune</option>';
				comuneSelect.disabled = true;
			}
		});

		// Avvia il caricamento delle regioni all'inizio
		loadRegioni();
	});
</script>
