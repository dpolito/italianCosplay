<?php
use App\Core\Session;
// Questo file è un frammento di HTML e deve essere incluso in un layout admin.
// Non contiene i tag <html>, <head>, <body> completi.
$breadcrumbs = $data['breadcrumbs'] ?? [];
?>
<div class="container mx-auto p-4">
	<!-- Breadcrumbs -->
	<nav class="text-sm text-gray-800 mb-4">
		<?php foreach ($breadcrumbs as $index => $crumb): ?>
			<?php if (isset($crumb['url'])): ?>
				<a href="<?php echo htmlspecialchars($crumb['url']); ?>" class="hover:underline"><?php echo htmlspecialchars($crumb['label']); ?></a>
				<?php if ($index < count($breadcrumbs) - 1): ?>
					<span class="mx-1">/</span>
				<?php endif; ?>
			<?php else: ?>
				<span><?php echo htmlspecialchars($crumb['label']); ?></span>
			<?php endif; ?>
		<?php endforeach; ?>
	</nav>
<h1 class="text-4xl font-bold mb-6 text-center">
	Segnala un Evento Cosplay
</h1>
<div class="bg-white rounded-2xl shadow-sm p-6 mb-10">

	<h2 class="text-2xl font-bold mb-4">
		Pubblica il tuo evento su ItalianCosplay
	</h2>

	<p class="mb-4 text-lg leading-relaxed">
		Hai organizzato una fiera del fumetto, un raduno cosplay, un festival nerd o una manifestazione dedicata al mondo anime, manga e videogiochi?
		Puoi segnalare gratuitamente il tuo evento su ItalianCosplay ed entrare nel calendario degli eventi cosplay in Italia.
	</p>

	<p class="mb-4 text-lg leading-relaxed">
		Raccogliamo eventi cosplay da tutta Italia, dalle grandi fiere comics ai piccoli eventi locali organizzati da associazioni, community e gruppi di appassionati.
		Ogni evento approvato viene pubblicato nel nostro calendario eventi cosplay e reso facilmente consultabile dagli utenti tramite filtri per regione, provincia e comune.
	</p>

	<p class="mb-0 text-lg leading-relaxed">
		Prima dell’approvazione il nostro team verifica le informazioni inviate per garantire qualità, correttezza e pertinenza degli eventi pubblicati.
	</p>

</div>
<div class="bg-gray-50 rounded-2xl p-6 mb-10">

	<h2 class="text-2xl font-bold mb-4">
		Quali eventi puoi segnalare?
	</h2>

	<div class="grid md:grid-cols-2 gap-4">

		<ul class="list-disc pl-5 space-y-2">
			<li>Fiere del fumetto</li>
			<li>Raduni cosplay</li>
			<li>Festival anime e manga</li>
			<li>Eventi gaming e videogiochi</li>
		</ul>

		<ul class="list-disc pl-5 space-y-2">
			<li>Manifestazioni fantasy</li>
			<li>Contest cosplay</li>
			<li>Eventi nerd e geek</li>
			<li>Convention comics</li>
		</ul>

	</div>

</div>
<div class="bg-white rounded-2xl shadow-sm p-6 mb-10">

	<h2 class="text-2xl font-bold mb-4">
		Come funziona la segnalazione
	</h2>

	<div class="space-y-4">

		<div class="flex items-start gap-4">
			<div class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center font-bold">
				1
			</div>
			<div>
				<h3 class="font-semibold text-lg">
					Compila il modulo
				</h3>
				<p class="text-gray-700">
					Inserisci nome evento, date, location, descrizione e link ufficiali.
				</p>
			</div>
		</div>

		<div class="flex items-start gap-4">
			<div class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center font-bold">
				2
			</div>
			<div>
				<h3 class="font-semibold text-lg">
					Verifica del team
				</h3>
				<p class="text-gray-700">
					Controlliamo le informazioni inviate per evitare eventi duplicati o dati incompleti.
				</p>
			</div>
		</div>

		<div class="flex items-start gap-4">
			<div class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center font-bold">
				3
			</div>
			<div>
				<h3 class="font-semibold text-lg">
					Pubblicazione evento
				</h3>
				<p class="text-gray-700">
					Dopo l’approvazione l’evento verrà pubblicato nel calendario eventi cosplay di ItalianCosplay.
				</p>
			</div>
		</div>

	</div>

</div>

<?php

if ($message = Session::getFlash('success')): ?>
	<div class="mb-4 p-3 rounded-lg shadow bg-green-100 text-green-800 border border-green-300">
		<?php echo  htmlspecialchars($message) ?>
	</div>
<?php endif; ?>


<?php
if ($message = Session::getFlash('error')): ?>
	<div class="mb-4 p-3 rounded-lg shadow bg-red-100 text-red-800 border border-red-300">
		<?php echo  htmlspecialchars($message) ?>
	</div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow-lg p-8">
	<form id="eventForm" action="/eventi-cosplay/store" method="POST" enctype="multipart/form-data">
		<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">

		<!-- Titolo -->
		<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
			<div class="mb-4">
				<label for="titolo" class="block text-gray-700 text-sm font-bold mb-2">Titolo:</label>
				<input type="text" id="titolo" name="titolo" value="" required class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline">
			</div>
			<div class="mb-4">
				<label for="tipo_evento_id" class="block text-gray-700 text-sm font-bold mb-2">Tipo Evento:</label>
				<select id="tipo_evento_id" name="tipo_evento_id" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
					<option value="">Seleziona un tipo di evento</option>
					<?php if(!empty($data['tipi_evento'])):
						foreach($data['tipi_evento'] as $tipo): ?>
							<option value="<?php echo htmlspecialchars($tipo['id']); ?>">
								<?php echo htmlspecialchars($tipo['nome']); ?>
							</option>
						<?php endforeach; endif; ?>
				</select>
			</div>
		</div>

		<!-- Descrizione -->
		<div class="mb-4">
			<label for="descrizione" class="block text-gray-700 text-sm font-bold mb-2">Descrizione:</label>
			<textarea class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline" name="descrizione" id="descrizione" rows="6"></textarea>
		</div>

		<!-- Date (2 colonne) -->
		<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
			<div>
				<label for="data_inizio" class="block text-gray-700 text-sm font-bold mb-2">Data Inizio:</label>
				<input type="date" id="data_inizio" name="data_inizio" value="" required class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline">
			</div>
			<div>
				<label for="data_fine" class="block text-gray-700 text-sm font-bold mb-2">Data Fine:</label>
				<input type="date" id="data_fine" name="data_fine" value="" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline">
			</div>
		</div>

		<!-- Luogo -->
		<div class="mb-4">
			<label for="luogo" class="block text-gray-700 text-sm font-bold mb-2">Luogo:</label>
			<input type="text" id="luogo" name="luogo" value="" required class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
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

		<!-- Sito Web -->
		<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
			<div class="mb-4">
				<label for="sito_web" class="block text-gray-700 text-sm font-bold mb-2">Sito Web:</label>
				<input type="text" id="sito_web" name="sito_web" value="" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
			</div>

			<div class="mb-4">
				<label for="social_facebook" class="block text-gray-700 text-sm font-bold mb-2">Social Facebook:</label>
				<input type="text" id="social_facebook" name="social_facebook" value="" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
			</div>
			<div class="mb-4">
				<label for="social_instagram" class="block text-gray-700 text-sm font-bold mb-2">Social Instagram:</label>
				<input type="text" id="social_instagram" name="social_instagram" value="" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
			</div>
			<div class="mb-4">
				<label for="social_tiktok" class="block text-gray-700 text-sm font-bold mb-2">Social TikTok:</label>
				<input type="text" id="social_tiktok" name="social_tiktok" value="" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
			</div>
			<div class="mb-4">
				<label for="social_youtube" class="block text-gray-700 text-sm font-bold mb-2">Social YouTube:</label>
				<input type="text" id="social_youtube" name="social_youtube" value="" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
			</div>
		</div>
		<!-- Tipo evento -->

		<!-- Immagine -->
		<div class="mb-4">
			<label for="immagine" class="block text-gray-700 text-sm font-bold mb-2">Carica Nuova Immagine:</label>
			<input type="file" id="immagine" name="immagine" accept="image/jpeg,image/png,image/gif,image/webp" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">

		</div>
		<!-- Pulsanti -->
		<div class="flex items-center justify-between">
			<button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300">Segnala Evento</button>

		</div>
	</form>
</div>
</div>


<script>
	document.addEventListener('DOMContentLoaded', function(){
		// --- Logica per le Dropdown Dinamiche (Regioni, Province, Comuni) ---

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
