<?php
use App\Core\Session;

$breadcrumbs = $data['breadcrumbs'] ?? [];
$value = static function (string $key, string $default = '') use ($data): string {
	return htmlspecialchars((string)($data[$key] ?? $default), ENT_QUOTES, 'UTF-8');
};
$selectedValue = static function (string $key, string $default = '') use ($data): string {
	return htmlspecialchars((string)($data[$key] ?? $default), ENT_QUOTES, 'UTF-8');
};

$pageTitle = 'Segnala un evento cosplay';
$pageDescription = 'Invia gratuitamente il tuo evento cosplay, fiera comics, raduno o festival nerd su ItalianCosplay per la revisione del team.';
?>
<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
	<nav class="mb-5 text-sm text-slate-600" aria-label="Breadcrumb">
		<ol class="flex flex-wrap items-center gap-2">
			<?php foreach ($breadcrumbs as $index => $crumb): ?>
				<li class="flex items-center gap-2">
					<?php if (!empty($crumb['url']) && $index < count($breadcrumbs) - 1): ?>
						<a href="<?php echo htmlspecialchars($crumb['url'], ENT_QUOTES, 'UTF-8'); ?>" class="font-medium text-slate-700 transition hover:text-emerald-800 hover:underline">
							<?php echo htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8'); ?>
						</a>
					<?php else: ?>
						<span class="font-medium text-slate-950"><?php echo htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8'); ?></span>
					<?php endif; ?>
					<?php if ($index < count($breadcrumbs) - 1): ?>
						<span class="text-slate-400">/</span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>

	<div class="grid gap-8 lg:grid-cols-[1.05fr_0.95fr]">
		<header class="overflow-hidden rounded-3xl bg-slate-950 text-white shadow-xl">
			<div class="relative px-6 py-8 sm:px-8 lg:px-10 lg:py-10">
				<div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(16,185,129,0.24),_transparent_32%),radial-gradient(circle_at_bottom_left,_rgba(59,130,246,0.18),_transparent_28%)]"></div>
				<div class="relative">
					<span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-200">Segnalazione eventi</span>
					<h1 class="mt-5 text-3xl font-black tracking-tight sm:text-4xl lg:text-5xl">
						Segnala il tuo evento cosplay
					</h1>
					<p class="mt-4 max-w-2xl text-base leading-7 text-slate-200 sm:text-lg">
						Invia gratuitamente fiere del fumetto, raduni cosplay, festival anime e manga, contest, convention comics e altri appuntamenti nerd: il nostro team li verifica prima della pubblicazione.
					</p>

					<div class="mt-8 grid gap-3 sm:grid-cols-3">
						<div class="rounded-2xl border border-white/10 bg-white/5 p-4">
							<p class="text-xs font-semibold uppercase tracking-wide text-emerald-200">1. Compila</p>
							<p class="mt-2 text-sm text-slate-200">Inserisci titolo, date, luogo e riferimenti ufficiali.</p>
						</div>
						<div class="rounded-2xl border border-white/10 bg-white/5 p-4">
							<p class="text-xs font-semibold uppercase tracking-wide text-emerald-200">2. Verifica</p>
							<p class="mt-2 text-sm text-slate-200">Controlliamo i dati per evitare duplicati o informazioni incomplete.</p>
						</div>
						<div class="rounded-2xl border border-white/10 bg-white/5 p-4">
							<p class="text-xs font-semibold uppercase tracking-wide text-emerald-200">3. Pubblica</p>
							<p class="mt-2 text-sm text-slate-200">Se approvato, l’evento entra nel calendario eventi cosplay.</p>
						</div>
					</div>
				</div>
			</div>
		</header>

		<aside class="space-y-4">
			<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
				<h2 class="text-lg font-bold text-slate-950">Quali eventi puoi segnalare?</h2>
				<ul class="mt-4 grid gap-3 text-sm text-slate-700 sm:grid-cols-2">
					<li class="rounded-2xl bg-slate-50 px-4 py-3">Fiere del fumetto</li>
					<li class="rounded-2xl bg-slate-50 px-4 py-3">Raduni cosplay</li>
					<li class="rounded-2xl bg-slate-50 px-4 py-3">Festival anime e manga</li>
					<li class="rounded-2xl bg-slate-50 px-4 py-3">Eventi gaming e videogiochi</li>
					<li class="rounded-2xl bg-slate-50 px-4 py-3">Contest cosplay</li>
					<li class="rounded-2xl bg-slate-50 px-4 py-3">Convention comics</li>
				</ul>
			</div>

			<div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6">
				<h2 class="text-lg font-bold text-emerald-950">Privacy</h2>
				<p class="mt-3 text-sm leading-6 text-emerald-900">
					Prima di inviare la segnalazione, ti chiediamo di accettare l'informativa privacy.
					Servirà a gestire correttamente i dati inseriti e la revisione della segnalazione.
				</p>
			</div>
		</aside>
	</div>

	<div class="mt-8 grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
		<div class="space-y-6">
			<?php if ($message = Session::getFlash('success')): ?>
				<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 shadow-sm" role="status">
					<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
				</div>
			<?php endif; ?>

			<?php if ($message = Session::getFlash('error')): ?>
				<div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-900 shadow-sm" role="alert">
					<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
				</div>
			<?php endif; ?>

			<section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
				<div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
					<div>
						<h2 class="text-2xl font-bold text-slate-950">Modulo di segnalazione</h2>
						<p class="mt-1 text-sm leading-6 text-slate-600">I campi con asterisco sono obbligatori. Più dati inserisci, più veloce sarà la revisione.</p>
					</div>
					<p class="text-sm font-medium text-slate-500">Tempo medio: 3-5 minuti</p>
				</div>

				<form id="eventForm" class="mt-6 space-y-6" action="/eventi-cosplay/store" method="POST" enctype="multipart/form-data" data-track-form="event-report">
					<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

					<div class="grid gap-5 md:grid-cols-2">
						<div>
							<label for="titolo" class="block text-sm font-semibold text-slate-800">Titolo evento *</label>
							<input type="text" id="titolo" name="titolo" value="<?php echo $value('titolo'); ?>" required autocomplete="off" class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
						</div>
						<div>
							<label for="tipo_evento_id" class="block text-sm font-semibold text-slate-800">Tipo evento</label>
							<select id="tipo_evento_id" name="tipo_evento_id" class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
								<option value="">Seleziona un tipo di evento</option>
								<?php if (!empty($data['tipi_evento'])): ?>
									<?php foreach ($data['tipi_evento'] as $tipo): ?>
										<option value="<?php echo htmlspecialchars((string)$tipo['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($selectedValue('tipo_evento_id') !== '' && $selectedValue('tipo_evento_id') == (string)$tipo['id']) ? 'selected' : ''; ?>>
											<?php echo htmlspecialchars($tipo['nome'], ENT_QUOTES, 'UTF-8'); ?>
										</option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</div>
					</div>

					<div>
						<label for="descrizione" class="block text-sm font-semibold text-slate-800">Descrizione</label>
						<textarea name="descrizione" id="descrizione" rows="7" class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"><?php echo $value('descrizione'); ?></textarea>
						<p class="mt-2 text-xs leading-5 text-slate-500">Racconta cosa rende l’evento interessante: attività, contest, ospiti, area cosplay, biglietti o orari.</p>
					</div>

					<div class="grid gap-5 md:grid-cols-2">
						<div>
							<label for="data_inizio" class="block text-sm font-semibold text-slate-800">Data inizio *</label>
							<input type="date" id="data_inizio" name="data_inizio" value="<?php echo $value('data_inizio'); ?>" required class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
						</div>
						<div>
							<label for="data_fine" class="block text-sm font-semibold text-slate-800">Data fine</label>
							<input type="date" id="data_fine" name="data_fine" value="<?php echo $value('data_fine'); ?>" class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
						</div>
					</div>

					<div>
						<label for="luogo" class="block text-sm font-semibold text-slate-800">Luogo *</label>
						<input type="text" id="luogo" name="luogo" value="<?php echo $value('luogo'); ?>" required autocomplete="street-address" class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
					</div>

					<div class="grid gap-5 md:grid-cols-3">
						<div>
							<label for="regione_id" class="block text-sm font-semibold text-slate-800">Regione</label>
							<select id="regione_id" name="regione_id" class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
								<option value="">Caricamento regioni...</option>
							</select>
						</div>
						<div>
							<label for="provincia_id" class="block text-sm font-semibold text-slate-800">Provincia</label>
							<select id="provincia_id" name="provincia_id" disabled class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
								<option value="">Seleziona prima una regione</option>
							</select>
						</div>
						<div>
							<label for="comune_id" class="block text-sm font-semibold text-slate-800">Comune</label>
							<select id="comune_id" name="comune_id" disabled class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
								<option value="">Seleziona prima una provincia</option>
							</select>
						</div>
					</div>

					<div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
						<div>
							<label for="sito_web" class="block text-sm font-semibold text-slate-800">Sito ufficiale</label>
							<input type="url" id="sito_web" name="sito_web" value="<?php echo $value('sito_web'); ?>" placeholder="https://..." class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
						</div>
						<div>
							<label for="social_facebook" class="block text-sm font-semibold text-slate-800">Facebook</label>
							<input type="url" id="social_facebook" name="social_facebook" value="<?php echo $value('social_facebook'); ?>" placeholder="https://facebook.com/..." class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
						</div>
						<div>
							<label for="social_instagram" class="block text-sm font-semibold text-slate-800">Instagram</label>
							<input type="url" id="social_instagram" name="social_instagram" value="<?php echo $value('social_instagram'); ?>" placeholder="https://instagram.com/..." class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
						</div>
						<div>
							<label for="social_tiktok" class="block text-sm font-semibold text-slate-800">TikTok</label>
							<input type="url" id="social_tiktok" name="social_tiktok" value="<?php echo $value('social_tiktok'); ?>" placeholder="https://tiktok.com/..." class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
						</div>
						<div>
							<label for="social_youtube" class="block text-sm font-semibold text-slate-800">YouTube</label>
							<input type="url" id="social_youtube" name="social_youtube" value="<?php echo $value('social_youtube'); ?>" placeholder="https://youtube.com/..." class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
						</div>
						<div>
							<label for="social_twitter" class="block text-sm font-semibold text-slate-800">X / Twitter</label>
							<input type="url" id="social_twitter" name="social_twitter" value="<?php echo $value('social_twitter'); ?>" placeholder="https://x.com/..." class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
						</div>
					</div>

					<div class="grid gap-5 md:grid-cols-2">
						<div>
							<label for="immagine" class="block text-sm font-semibold text-slate-800">Immagine evento</label>
							<input type="file" id="immagine" name="immagine" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm file:mr-4 file:rounded-xl file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800">
							<p class="mt-2 text-xs leading-5 text-slate-500">Formati consigliati: JPG, PNG o WebP.</p>
						</div>
						<div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
							<p class="text-sm font-semibold text-slate-900">Prima di inviare</p>
							<ul class="mt-3 space-y-2 text-sm leading-6 text-slate-600">
								<li>Controlla che date e luogo siano corretti.</li>
								<li>Inserisci solo link ufficiali o verificabili.</li>
								<li>Le informazioni complete accelerano la pubblicazione.</li>
							</ul>
						</div>
					</div>

					<div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
						<div class="space-y-3">
							<label for="privacy_accept" class="flex cursor-pointer items-start gap-3 text-sm text-slate-700">
								<input type="checkbox" id="privacy_accept" name="privacy_accept" value="1" required class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
								<span>
									Ho letto e accetto l'
									<a href="/privacy" target="_blank" rel="noopener noreferrer" class="font-semibold text-emerald-800 underline decoration-emerald-300 underline-offset-2 hover:text-emerald-950">
										informativa privacy
									</a>
									per la gestione della segnalazione.
								</span>
							</label>
							<p class="text-sm text-slate-500">Inviando il modulo accetti anche la revisione del team prima della pubblicazione.</p>
						</div>
						<button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-200" data-track-submit>
							Segnala evento
						</button>
					</div>
				</form>
			</section>
		</div>

		<aside class="space-y-6">
			<section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
				<h2 class="text-lg font-bold text-slate-950">Perché conviene</h2>
				<div class="mt-4 space-y-4 text-sm leading-6 text-slate-700">
					<p>La segnalazione corretta aiuta gli utenti a trovare il tuo evento nei filtri per regione, provincia e comune.</p>
					<p>Una scheda completa migliora la qualità della pagina e aumenta le possibilità di essere approvata più rapidamente.</p>
					<p>Per gli eventi approvati, la pagina pubblica può portare traffico organico e visibilità nelle ricerche locali.</p>
				</div>
			</section>

			<section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
				<h2 class="text-lg font-bold text-slate-950">Cosa ci aiuta davvero</h2>
				<ul class="mt-4 space-y-3 text-sm leading-6 text-slate-700">
					<li>Un titolo chiaro e riconoscibile.</li>
					<li>Date corrette e località completa.</li>
					<li>Link ufficiali per verificare più in fretta la segnalazione.</li>
				</ul>
			</section>
		</aside>
	</div>
</section>

<script>
	document.addEventListener('DOMContentLoaded', function () {
		const regioneSelect = document.getElementById('regione_id');
		const provinciaSelect = document.getElementById('provincia_id');
		const comuneSelect = document.getElementById('comune_id');
		const trackingForm = document.querySelector('[data-track-form="event-report"]');
		const privacyCheckbox = document.getElementById('privacy_accept');
		let startedTracked = false;
		let privacyTracked = false;
		let progressBucket = 0;

		const initialRegioneId = "<?php echo htmlspecialchars((string)($data['regione_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>";
		const initialProvinciaId = "<?php echo htmlspecialchars((string)($data['provincia_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>";
		const initialComuneId = "<?php echo htmlspecialchars((string)($data['comune_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>";
		const csrfToken = "<?php echo htmlspecialchars((string)($data['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>";

		function sendTracking(eventName, extra = {}) {
			if (!trackingForm || !csrfToken) {
				return;
			}

			const payload = new URLSearchParams({
				csrf_token: csrfToken,
				event_name: eventName,
				page_url: window.location.pathname,
				...extra,
			});

			fetch('/eventi-cosplay/report/track', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
					'X-Requested-With': 'XMLHttpRequest',
				},
				credentials: 'same-origin',
				body: payload.toString(),
				keepalive: true,
			}).catch(() => {});
		}

		function getCompletedFields() {
			const fieldIds = ['titolo', 'descrizione', 'data_inizio', 'luogo', 'regione_id', 'provincia_id', 'comune_id'];
			if (privacyCheckbox) {
				fieldIds.push('privacy_accept');
			}

			let completed = 0;
			fieldIds.forEach((fieldId) => {
				const field = document.getElementById(fieldId);
				if (!field) {
					return;
				}
				if (field.type === 'checkbox') {
					if (field.checked) {
						completed += 1;
					}
					return;
				}
				if ((field.value || '').trim() !== '') {
					completed += 1;
				}
			});

			return Math.round((completed / fieldIds.length) * 100);
		}

		function trackProgress() {
			const progress = getCompletedFields();
			[25, 50, 75].forEach((bucket) => {
				if (progress >= bucket && progressBucket < bucket) {
					progressBucket = bucket;
					sendTracking('field_progress', { fields_completed: String(bucket), step_name: `progress_${bucket}` });
				}
			});
		}

		document.addEventListener('input', function (event) {
			if (!trackingForm || !trackingForm.contains(event.target)) {
				return;
			}
			if (!startedTracked) {
				startedTracked = true;
				sendTracking('form_start', { step_name: 'first_interaction' });
			}
			trackProgress();
		});

		document.addEventListener('change', function (event) {
			if (!trackingForm || !trackingForm.contains(event.target)) {
				return;
			}
			if (event.target && event.target.id === 'privacy_accept' && event.target.checked && !privacyTracked) {
				privacyTracked = true;
				sendTracking('privacy_accept', { step_name: 'privacy_checkbox', fields_completed: String(getCompletedFields()) });
			}
			trackProgress();
		});

		if (trackingForm) {
			trackingForm.addEventListener('submit', function () {
				sendTracking('submit_success', { step_name: 'submit', fields_completed: '100' });
			});
		}

		window.addEventListener('beforeunload', function () {
			if (startedTracked && progressBucket < 100) {
				sendTracking('submit_error', { step_name: 'abandon', fields_completed: String(getCompletedFields()) });
			}
		});

		async function fetchData(relativePath) {
			try {
				const response = await fetch(window.location.origin + relativePath);
				if (!response.ok) {
					throw new Error(`HTTP error: ${response.status}`);
				}
				return await response.json();
			} catch (error) {
				console.error('Errore nel recupero dei dati:', error);
				return [];
			}
		}

		function populateDropdown(dropdownElement, data, selectedValue = null, placeholderText = 'Seleziona...') {
			dropdownElement.innerHTML = `<option value="">${placeholderText}</option>`;
			data.forEach((item) => {
				const option = document.createElement('option');
				option.value = item.id;
				option.textContent = item.nome;
				if (selectedValue && selectedValue == item.id) {
					option.selected = true;
				}
				dropdownElement.appendChild(option);
			});
			dropdownElement.disabled = false;
		}

		async function loadRegioni() {
			const regioni = await fetchData('/api/regioni');
			populateDropdown(regioneSelect, regioni, initialRegioneId, 'Seleziona Regione');
			if (initialRegioneId) {
				await loadProvince(initialRegioneId);
			}
		}

		async function loadProvince(regioneId) {
			provinciaSelect.innerHTML = '<option value="">Caricamento province...</option>';
			provinciaSelect.disabled = true;
			comuneSelect.innerHTML = '<option value="">Seleziona Comune</option>';
			comuneSelect.disabled = true;

			const province = await fetchData(`/api/province/${regioneId}`);
			populateDropdown(provinciaSelect, province, initialProvinciaId, 'Seleziona Provincia');
			if (initialProvinciaId) {
				await loadComuni(initialProvinciaId);
			}
		}

		async function loadComuni(provinciaId) {
			comuneSelect.innerHTML = '<option value="">Caricamento comuni...</option>';
			comuneSelect.disabled = true;

			const comuni = await fetchData(`/api/comuni/${provinciaId}`);
			populateDropdown(comuneSelect, comuni, initialComuneId, 'Seleziona Comune');
		}

		regioneSelect.addEventListener('change', async (event) => {
			const selectedRegioneId = event.target.value;
			if (selectedRegioneId) {
				await loadProvince(selectedRegioneId);
			} else {
				provinciaSelect.innerHTML = '<option value="">Seleziona Provincia</option>';
				provinciaSelect.disabled = true;
				comuneSelect.innerHTML = '<option value="">Seleziona Comune</option>';
				comuneSelect.disabled = true;
			}
		});

		provinciaSelect.addEventListener('change', async (event) => {
			const selectedProvinciaId = event.target.value;
			if (selectedProvinciaId) {
				await loadComuni(selectedProvinciaId);
			} else {
				comuneSelect.innerHTML = '<option value="">Seleziona Comune</option>';
				comuneSelect.disabled = true;
			}
		});

		loadRegioni();
	});
</script>
