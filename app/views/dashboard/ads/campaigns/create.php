<?php
$positions = $positions ?? [];
$banners = $banners ?? [];
$regioni = $regioni ?? [];
$province = $province ?? [];
$csrf_token = $csrf_token ?? '';
$oldInput = $old_input ?? [];
?>
<section class="space-y-6">
	<div>
		<p class="text-sm font-bold uppercase tracking-wide text-green-900">Acquista visibilità</p>
		<h1 class="text-2xl font-bold text-gray-950">Nuova campagna banner</h1>
		<p class="mt-1 text-gray-600">Prenota uno spazio anche per date future, carica un banner o crea una scheda sponsor responsive.</p>
	</div>

	<?php if ($message = \App\Core\Session::getFlash('error')): ?>
		<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
			<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
		</div>
	<?php endif; ?>
	<?php if ($message = \App\Core\Session::getFlash('success')): ?>
		<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
			<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
		</div>
	<?php endif; ?>

	<form action="/dashboard/ads/campaigns/store" method="POST" enctype="multipart/form-data" class="space-y-6">
		<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

		<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
			<h2 class="text-lg font-bold text-gray-950">1. Scegli la posizione</h2>
			<div class="mt-4 grid gap-3 md:grid-cols-2">
				<label class="block">
					<span class="text-sm font-bold text-gray-800">Cerca posizione</span>
					<input type="search" data-position-search class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none" placeholder="Es. homepage, blog, eventi">
				</label>
				<label class="block">
					<span class="text-sm font-bold text-gray-800">Filtra per pagina</span>
					<select data-position-filter class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none">
						<option value="all">Tutte le pagine</option>
						<?php
						$pageGroups = [];
						foreach ($positions as $position) {
							$pageGroups[(string)$position['page']] = true;
						}
						ksort($pageGroups);
						foreach (array_keys($pageGroups) as $page):
						?>
							<option value="<?php echo htmlspecialchars($page, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(ucfirst($page), ENT_QUOTES, 'UTF-8'); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>
			<div class="mt-5 grid gap-4 md:grid-cols-2">
				<?php foreach ($positions as $position): ?>
					<?php
					$positionCode = (string)($position['code'] ?? '');
					$positionTargetScope = 'national';
					if (str_starts_with($positionCode, 'events_region')) {
						$positionTargetScope = 'region';
					} elseif (str_starts_with($positionCode, 'events_province')) {
						$positionTargetScope = 'province';
					}
					?>
					<label class="position-card cursor-pointer rounded-xl border border-gray-200 p-4 transition hover:border-green-500 has-[:checked]:border-green-600 has-[:checked]:bg-green-50" data-position-card data-position-page="<?php echo htmlspecialchars((string)$position['page'], ENT_QUOTES, 'UTF-8'); ?>" data-position-text="<?php echo htmlspecialchars(strtolower(trim((string)$position['name'] . ' ' . (string)$position['page'] . ' ' . (string)$position['code'])), ENT_QUOTES, 'UTF-8'); ?>" data-position-target-scope="<?php echo htmlspecialchars($positionTargetScope, ENT_QUOTES, 'UTF-8'); ?>">
						<input type="radio" name="position_id" value="<?php echo (int)$position['id']; ?>" class="sr-only" required <?php echo ((int)($oldInput['position_id'] ?? 0) === (int)$position['id']) ? 'checked' : ''; ?>>
						<div class="flex items-start justify-between gap-3">
							<div>
								<h3 class="font-bold text-gray-950"><?php echo htmlspecialchars($position['name']); ?></h3>
								<p class="text-sm text-gray-600"><?php echo htmlspecialchars($position['page']); ?> · <?php echo (int)$position['width']; ?>x<?php echo (int)$position['height']; ?></p>
								<?php if (!empty($position['estimated_monthly_impressions'])): ?>
									<p class="mt-2 text-sm text-gray-700">Visualizzazioni medie: <?php echo number_format((int)$position['estimated_monthly_impressions'], 0, ',', '.'); ?>/mese</p>
								<?php endif; ?>
								<?php if (!empty($position['average_ctr'])): ?>
									<p class="text-sm text-gray-700">CTR medio: <?php echo number_format((float)$position['average_ctr'], 2, ',', '.'); ?>%</p>
								<?php endif; ?>
							</div>
							<span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-900">Disponibile</span>
						</div>
						<?php if (!empty($position['prices'])): ?>
							<div class="mt-3 flex flex-wrap gap-2">
								<?php foreach ($position['prices'] as $price): ?>
									<span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700"><?php echo (int)$price['duration_days']; ?> giorni · <?php echo number_format((float)$price['price'], 2, ',', '.'); ?> €</span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</label>
				<?php endforeach; ?>
			</div>
			<p class="mt-3 text-xs text-gray-500" data-position-empty-state hidden>Nessuna posizione corrisponde al filtro selezionato.</p>
		</section>

		<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hidden" data-target-section>
			<h2 class="text-lg font-bold text-gray-950">2. Target territoriale</h2>
			<p class="mt-1 text-sm text-gray-600">La campagna sarà mostrata solo nel contesto geografico selezionato.</p>
			<div class="mt-4 grid gap-3 md:grid-cols-3" data-target-options>
				<label class="cursor-pointer rounded-xl border border-gray-200 p-4 transition hover:border-green-500 has-[:checked]:border-green-600 has-[:checked]:bg-green-50" data-target-option="national">
					<input type="radio" name="target_type" value="national" class="sr-only" <?php echo (($oldInput['target_type'] ?? 'national') === 'national') ? 'checked' : ''; ?>>
					<h3 class="font-bold text-gray-950">Nazionale</h3>
					<p class="mt-1 text-sm text-gray-600">Mostra la campagna solo sulle pagine nazionali.</p>
				</label>
				<label class="cursor-pointer rounded-xl border border-gray-200 p-4 transition hover:border-green-500 has-[:checked]:border-green-600 has-[:checked]:bg-green-50" data-target-option="region">
					<input type="radio" name="target_type" value="region" class="sr-only" <?php echo (($oldInput['target_type'] ?? '') === 'region') ? 'checked' : ''; ?>>
					<h3 class="font-bold text-gray-950">Regione</h3>
					<p class="mt-1 text-sm text-gray-600">Mostra la campagna solo in una regione specifica.</p>
				</label>
				<label class="cursor-pointer rounded-xl border border-gray-200 p-4 transition hover:border-green-500 has-[:checked]:border-green-600 has-[:checked]:bg-green-50" data-target-option="province">
					<input type="radio" name="target_type" value="province" class="sr-only" <?php echo (($oldInput['target_type'] ?? '') === 'province') ? 'checked' : ''; ?>>
					<h3 class="font-bold text-gray-950">Provincia</h3>
					<p class="mt-1 text-sm text-gray-600">Mostra la campagna solo in una provincia specifica.</p>
				</label>
			</div>
			<div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4 hidden" data-target-container>
				<div class="grid gap-4 md:grid-cols-2">
					<label class="block hidden" data-target-group="region">
						<span class="text-sm font-bold text-gray-800">Seleziona regione</span>
						<select name="target_value" data-target-value="region" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none">
							<option value="">Scegli una regione</option>
							<?php foreach ($regioni as $regione): ?>
								<option value="<?php echo htmlspecialchars($regione['slug'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($oldInput['target_value'] ?? '') === ($regione['slug'] ?? '')) ? 'selected' : ''; ?>><?php echo htmlspecialchars($regione['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="block hidden" data-target-group="province">
						<span class="text-sm font-bold text-gray-800">Seleziona provincia</span>
						<select name="target_value" data-target-value="province" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none">
							<option value="">Scegli una provincia</option>
							<?php foreach ($province as $provincia): ?>
								<option value="<?php echo htmlspecialchars($provincia['slug'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($oldInput['target_value'] ?? '') === ($provincia['slug'] ?? '')) ? 'selected' : ''; ?>><?php echo htmlspecialchars($provincia['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>
				<p class="mt-2 text-xs text-gray-500">Il targeting nazionale ignora il valore. Regione e provincia usano lo slug come chiave di matching.</p>
			</div>
		</section>

		<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
			<h2 class="text-lg font-bold text-gray-950">3. Banner</h2>
			<p class="mt-1 text-sm text-gray-600">Scegli se usare un banner già creato oppure crearne uno nuovo per questa campagna.</p>
			<div class="mt-4 grid gap-3 md:grid-cols-2">
				<label class="cursor-pointer rounded-xl border border-gray-200 p-4 transition hover:border-green-500 has-[:checked]:border-green-600 has-[:checked]:bg-green-50">
					<input type="radio" name="banner_mode" value="existing" class="sr-only" <?php echo (($oldInput['banner_mode'] ?? 'new') === 'existing') ? 'checked' : ''; ?>>
					<div class="flex items-start justify-between gap-3">
						<div>
							<h3 class="font-bold text-gray-950">Banner esistente</h3>
							<p class="text-sm text-gray-600">Riutilizza una creatività già presente nel tuo account.</p>
						</div>
						<span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-900">Riutilizzo</span>
					</div>
				</label>
				<label class="cursor-pointer rounded-xl border border-gray-200 p-4 transition hover:border-green-500 has-[:checked]:border-green-600 has-[:checked]:bg-green-50">
					<input type="radio" name="banner_mode" value="new" class="sr-only" <?php echo (($oldInput['banner_mode'] ?? 'new') === 'new') ? 'checked' : ''; ?>>
					<div class="flex items-start justify-between gap-3">
						<div>
							<h3 class="font-bold text-gray-950">Nuovo banner</h3>
							<p class="text-sm text-gray-600">Carica una nuova creatività e la colleghi alla campagna.</p>
						</div>
						<span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700">Nuovo</span>
					</div>
				</label>
			</div>

			<div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4" data-banner-existing-panel>
				<label class="block">
					<span class="text-sm font-bold text-gray-800">Seleziona banner esistente</span>
					<select name="existing_banner_id" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none">
						<option value="">Seleziona un banner</option>
						<?php foreach ($banners as $banner): ?>
							<option value="<?php echo (int)$banner['id']; ?>" <?php echo ((int)($oldInput['existing_banner_id'] ?? 0) === (int)$banner['id']) ? 'selected' : ''; ?>>
								<?php echo htmlspecialchars($banner['title'] ?? 'Banner', ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($banner['status'] ?? 'draft', ENT_QUOTES, 'UTF-8'); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<span class="mt-1 block text-xs text-gray-500">Utilizzabile solo se il banner non è già collegato a una campagna attiva o in pending.</span>
				</label>
			</div>

			<div class="mt-5" data-banner-new-panel>
				<h3 class="text-base font-bold text-gray-950">Oppure crea un nuovo banner</h3>
				<p class="mt-1 text-sm text-gray-600">Compila questi campi solo se vuoi creare una creatività nuova per questa campagna.</p>
				<div class="mt-4 grid gap-4 md:grid-cols-2">
					<label class="block">
						<span class="text-sm font-bold text-gray-800">Titolo</span>
						<input name="title" required value="<?php echo htmlspecialchars($oldInput['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none" placeholder="Nome evento o sponsor">
					</label>
					<label class="block">
						<span class="text-sm font-bold text-gray-800">URL di destinazione</span>
						<input name="target_url" type="url" required value="<?php echo htmlspecialchars($oldInput['target_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none" placeholder="https://...">
					</label>
					<label class="block md:col-span-2">
						<span class="text-sm font-bold text-gray-800">Testo breve</span>
						<textarea name="description" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none" placeholder="Descrivi in poche parole cosa promuovi"><?php echo htmlspecialchars($oldInput['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
					</label>
					<label class="block">
						<span class="text-sm font-bold text-gray-800">Banner immagine opzionale</span>
						<input name="banner" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3">
						<span class="mt-1 block text-xs text-gray-500">Se non carichi un’immagine, verrà usata una scheda sponsor responsive.</span>
					</label>
					<label class="block">
						<span class="text-sm font-bold text-gray-800">Nome sponsor</span>
						<input name="sponsor_name" value="<?php echo htmlspecialchars($oldInput['sponsor_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none">
					</label>
				</div>
			</div>
		</section>

		<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
			<h2 class="text-lg font-bold text-gray-950">4. Periodo</h2>
			<div class="mt-4 grid gap-4 md:grid-cols-3">
				<label class="block">
					<span class="text-sm font-bold text-gray-800">Dal</span>
					<input name="start_date" type="date" required value="<?php echo htmlspecialchars($oldInput['start_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none">
				</label>
				<label class="block">
					<span class="text-sm font-bold text-gray-800">Durata</span>
					<select name="duration_days" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none">
						<option value="7" <?php echo (int)($oldInput['duration_days'] ?? 30) === 7 ? 'selected' : ''; ?>>7 giorni</option>
						<option value="15" <?php echo (int)($oldInput['duration_days'] ?? 30) === 15 ? 'selected' : ''; ?>>15 giorni</option>
						<option value="30" <?php echo (int)($oldInput['duration_days'] ?? 30) === 30 ? 'selected' : ''; ?>>30 giorni</option>
						<option value="60" <?php echo (int)($oldInput['duration_days'] ?? 30) === 60 ? 'selected' : ''; ?>>60 giorni</option>
					</select>
				</label>
				<label class="block">
					<span class="text-sm font-bold text-gray-800">Oppure data fine</span>
					<input name="end_date" type="date" value="<?php echo htmlspecialchars($oldInput['end_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none">
				</label>
			</div>
		</section>

		<div class="flex flex-col gap-3 md:flex-row md:justify-end">
			<a href="/dashboard/ads/campaigns" class="rounded-lg border border-gray-300 px-5 py-3 text-center text-sm font-bold text-gray-800 hover:bg-gray-50">Annulla</a>
			<button class="rounded-lg bg-green-700 px-5 py-3 text-sm font-bold text-white hover:bg-green-800">Vai al riepilogo ordine</button>
		</div>
	</form>
</section>

<script>
(function () {
	const existingRadio = document.querySelector('input[name="banner_mode"][value="existing"]');
	const newRadio = document.querySelector('input[name="banner_mode"][value="new"]');
	const existingPanel = document.querySelector('[data-banner-existing-panel]');
	const newPanel = document.querySelector('[data-banner-new-panel]');
	const existingSelect = document.querySelector('select[name="existing_banner_id"]');
	const targetRadios = Array.from(document.querySelectorAll('input[name="target_type"]'));
	const targetGroups = Array.from(document.querySelectorAll('[data-target-group]'));
	const targetFields = Array.from(document.querySelectorAll('[data-target-value]'));
	const targetSection = document.querySelector('[data-target-section]');
	const targetContainer = document.querySelector('[data-target-container]');
	const targetOptions = Array.from(document.querySelectorAll('[data-target-option]'));
	const searchInput = document.querySelector('[data-position-search]');
	const filterSelect = document.querySelector('[data-position-filter]');
	const cards = Array.from(document.querySelectorAll('[data-position-card]'));
	const emptyState = document.querySelector('[data-position-empty-state]');
	const newFields = [
		document.querySelector('input[name="title"]'),
		document.querySelector('input[name="target_url"]'),
		document.querySelector('textarea[name="description"]'),
		document.querySelector('input[name="banner"]'),
		document.querySelector('input[name="sponsor_name"]'),
	];

	function setPanelState() {
		const useExisting = existingRadio && existingRadio.checked;

		if (existingPanel) {
			existingPanel.classList.toggle('hidden', !useExisting);
		}
		if (newPanel) {
			newPanel.classList.toggle('hidden', useExisting);
		}
		if (existingSelect) {
			existingSelect.disabled = !useExisting;
		}

		newFields.forEach((field) => {
			if (!field) return;
			field.disabled = useExisting;
			if (field.type === 'file') {
				return;
			}
			if (useExisting) {
				field.removeAttribute('required');
			} else if (field.name === 'title' || field.name === 'target_url') {
				field.setAttribute('required', 'required');
			}
		});
	}

	function setTargetState() {
		const selectedCard = document.querySelector('input[name="position_id"]:checked')?.closest('[data-position-card]');
		const targetScope = selectedCard?.dataset.positionTargetScope || 'national';

		if (targetSection) {
			targetSection.classList.toggle('hidden', targetScope === 'national');
		}

		if (targetContainer) {
			targetContainer.classList.toggle('hidden', targetScope === 'national');
		}

		targetOptions.forEach((option) => {
			option.classList.toggle('hidden', option.dataset.targetOption !== targetScope);
		});

		if (targetScope === 'national') {
			const national = document.querySelector('input[name="target_type"][value="national"]');
			if (national) {
				national.checked = true;
			}
			return;
		}

		const targetType = targetScope;
		const targetRadio = document.querySelector(`input[name="target_type"][value="${targetType}"]`);
		if (targetRadio) {
			targetRadio.checked = true;
		}

		targetGroups.forEach((group) => {
			group.classList.toggle('hidden', group.dataset.targetGroup !== targetType);
		});

		targetFields.forEach((field) => {
			const isActive = field.dataset.targetValue === targetType;
			field.disabled = !isActive;
			field.name = isActive ? 'target_value' : 'target_value_disabled';
			if (!isActive) {
				field.value = '';
			}
		});
	}

	function filterPositions() {
		const query = (searchInput?.value || '').trim().toLowerCase();
		const selectedPage = filterSelect?.value || 'all';
		let visibleCount = 0;

		cards.forEach((card) => {
			const page = card.dataset.positionPage || '';
			const text = card.dataset.positionText || '';
			const matchesPage = selectedPage === 'all' || page === selectedPage;
			const matchesQuery = query === '' || text.includes(query);
			const visible = matchesPage && matchesQuery;

			card.classList.toggle('hidden', !visible);
			if (visible) {
				visibleCount += 1;
			}
		});

		if (emptyState) {
			emptyState.hidden = visibleCount > 0;
		}
	}

	if (existingRadio) existingRadio.addEventListener('change', setPanelState);
	if (newRadio) newRadio.addEventListener('change', setPanelState);
	document.querySelectorAll('input[name="position_id"]').forEach((radio) => radio.addEventListener('change', setTargetState));
	targetRadios.forEach((radio) => radio.addEventListener('change', setTargetState));
	if (searchInput) searchInput.addEventListener('input', filterPositions);
	if (filterSelect) filterSelect.addEventListener('change', filterPositions);
	setPanelState();
	setTargetState();
	filterPositions();
})();
</script>
