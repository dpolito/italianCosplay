<?php
$master = $data['eventMaster'] ?? [];
$organizations = $data['organizations'] ?? [];
$masterName = (string) ($master['nome'] ?? 'questo evento');
$masterSlug = (string) ($master['slug'] ?? '');
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>

<main class="bg-gray-100 py-10 md:py-14">
	<div class="container mx-auto px-4 md:px-6">
		<a href="/eventi-master/<?= $h($masterSlug) ?>" class="text-sm font-semibold text-green-900 hover:underline">Torna alla pagina dell’evento</a>

		<div class="mt-5 grid gap-6 lg:grid-cols-[0.95fr_1.05fr] lg:items-start">
			<section class="rounded-3xl bg-gradient-to-br from-emerald-950 via-green-900 to-teal-900 p-6 text-white shadow-xl md:p-8">
				<p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-200">Gestione evento</p>
				<h1 class="mt-3 text-3xl font-black leading-tight md:text-4xl">Riscatta <?= $h($masterName) ?></h1>
				<p class="mt-4 text-base leading-7 text-emerald-50/90">Se rappresenti l’organizzazione che gestisce questo evento, puoi chiedere di amministrare il master e le sue edizioni da ItalianCosplay.</p>

				<div class="mt-8 space-y-5">
					<div class="flex gap-4"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-300 font-black text-emerald-950">1</span><div><h2 class="font-bold">Invii la richiesta</h2><p class="mt-1 text-sm leading-6 text-emerald-50/80">Indichi l’organizzazione e ci spieghi il tuo rapporto con essa.</p></div></div>
					<div class="flex gap-4"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-300 font-black text-emerald-950">2</span><div><h2 class="font-bold">Verifichiamo i dati</h2><p class="mt-1 text-sm leading-6 text-emerald-50/80">Lo staff controlla che la richiesta provenga da una persona autorizzata.</p></div></div>
					<div class="flex gap-4"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-300 font-black text-emerald-950">3</span><div><h2 class="font-bold">Gestisci il master</h2><p class="mt-1 text-sm leading-6 text-emerald-50/80">Dopo l’approvazione potrai gestire il master e le relative edizioni dalla tua area.</p></div></div>
				</div>

				<div class="mt-8 rounded-2xl border border-white/15 bg-white/10 p-4 text-sm leading-6 text-emerald-50/90">Il riscatto non modifica i dati pubblici automaticamente e non trasferisce la proprietà dell’organizzazione. Serve a verificare chi è autorizzato a gestirli.</div>
			</section>

			<section class="rounded-3xl bg-white p-6 shadow-xl md:p-8">
				<h2 class="text-2xl font-black text-gray-950">Invia la richiesta</h2>
				<p class="mt-2 text-sm leading-6 text-gray-600">Riceverai una conferma via email. Ti contatteremo se servono ulteriori informazioni.</p>
				<?php if ($message = \App\Core\Session::getFlash('error')): ?><div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= $h($message) ?></div><?php endif; ?>

				<form action="/eventi-master/<?= $h($masterSlug) ?>/riscatta" method="POST" class="mt-6 space-y-5">
					<input type="hidden" name="csrf_token" value="<?= $h($data['csrf_token'] ?? '') ?>">
					<div class="relative" data-organization-typeahead>
						<label for="organization-search" class="block text-sm font-semibold text-gray-800">Cerca un’organizzazione esistente</label>
						<input type="search" id="organization-search" autocomplete="off" placeholder="Nome dell’organizzazione" class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-green-800 focus:outline-none focus:ring-2 focus:ring-green-200" aria-autocomplete="list" aria-controls="organization-results">
						<input type="hidden" name="organization_id" id="organization_id" data-organization-id>
						<div id="organization-results" data-organization-results class="absolute z-10 mt-1 hidden max-h-60 w-full overflow-auto rounded-xl border border-gray-200 bg-white shadow-lg" role="listbox"></div>
						<p data-organization-selected class="mt-1 hidden text-sm text-green-800"></p>
						<p class="mt-1 text-xs text-gray-500">Puoi cercare anche un’organizzazione pubblica che non è ancora associata al tuo account.</p>
					</div>
					<div><label for="organization_name" class="block text-sm font-semibold text-gray-800">Oppure crea una nuova organizzazione</label><input type="text" name="organization_name" id="organization_name" maxlength="180" class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-green-800 focus:outline-none focus:ring-2 focus:ring-green-200" placeholder="Es. XYZ Events"><p class="mt-1 text-xs text-gray-500">Compilalo solo se non trovi l’organizzazione che cerchi.</p></div>
					<div><label for="evidence" class="block text-sm font-semibold text-gray-800">Perché sei autorizzato?</label><textarea name="evidence" id="evidence" maxlength="5000" rows="6" required class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-green-800 focus:outline-none focus:ring-2 focus:ring-green-200" placeholder="Sito ufficiale, email aziendale, pagina social, ruolo nell’organizzazione..."></textarea><p class="mt-1 text-xs text-gray-500">Non inserire password o dati sensibili non necessari.</p></div>
					<div class="rounded-2xl border border-gray-200 bg-gray-50 p-4"><label for="privacy_accept" class="flex cursor-pointer items-start gap-3 text-sm leading-6 text-gray-700"><input type="checkbox" name="privacy_accept" id="privacy_accept" value="1" required class="mt-1 h-4 w-4 rounded border-gray-300 text-green-800 focus:ring-green-300"><span>Dichiaro di aver letto l’<a href="/privacy" target="_blank" rel="noopener noreferrer" class="font-semibold text-green-900 underline">informativa privacy</a> e autorizzo il trattamento dei dati necessari alla verifica di questa richiesta.</span></label></div>
					<button type="submit" class="w-full rounded-xl bg-green-800 px-5 py-3 font-bold text-white shadow-sm hover:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-300">Invia richiesta di riscatto</button>
				</form>
			</section>
		</div>
	</div>
</main>
<script>
(function () {
	const typeahead = document.querySelector('[data-organization-typeahead]');
	const form = document.querySelector('form[action$="/riscatta"]');
	if (!typeahead || !form) return;
	const input = typeahead.querySelector('#organization-search');
	const organizationId = typeahead.querySelector('[data-organization-id]');
	const results = typeahead.querySelector('[data-organization-results]');
	const selected = typeahead.querySelector('[data-organization-selected]');
	const newName = form.querySelector('#organization_name');
	let timer;
	let controller;
	const clearSelection = () => { organizationId.value = ''; selected.textContent = ''; selected.classList.add('hidden'); };
	const closeResults = () => { results.innerHTML = ''; results.classList.add('hidden'); };
	const renderResults = (organizations) => {
		results.innerHTML = '';
		if (!organizations.length) {
			const empty = document.createElement('p'); empty.className = 'px-4 py-3 text-sm text-gray-500'; empty.textContent = 'Nessuna organizzazione trovata.'; results.appendChild(empty);
		} else organizations.forEach((organization) => {
			const button = document.createElement('button'); button.type = 'button'; button.className = 'block w-full px-4 py-3 text-left text-sm hover:bg-gray-50'; button.setAttribute('role', 'option'); button.textContent = organization.name;
			button.addEventListener('click', () => { organizationId.value = organization.id; input.value = organization.name; newName.value = ''; selected.textContent = 'Selezionata: ' + organization.name; selected.classList.remove('hidden'); closeResults(); });
			results.appendChild(button);
		});
		results.classList.remove('hidden');
	};
	input.addEventListener('input', () => {
		clearSelection(); clearTimeout(timer); if (controller) controller.abort();
		const query = input.value.trim(); if (query.length < 2) return closeResults();
		timer = setTimeout(async () => {
			controller = new AbortController();
			try { const response = await fetch('/eventi-master/<?= $h($masterSlug) ?>/organizzazioni/search?q=' + encodeURIComponent(query), { headers: { Accept: 'application/json' }, signal: controller.signal }); const payload = await response.json(); if (response.ok && payload.success) renderResults(payload.organizations || []); }
			catch (error) { if (error.name !== 'AbortError') closeResults(); }
		}, 250);
	});
	newName.addEventListener('input', () => { if (newName.value.trim() !== '') { input.value = ''; clearSelection(); closeResults(); } });
	input.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeResults(); });
	document.addEventListener('click', (event) => { if (!typeahead.contains(event.target)) closeResults(); });
	form.addEventListener('submit', (event) => { if (!organizationId.value && newName.value.trim() === '') { event.preventDefault(); newName.focus(); newName.setCustomValidity('Seleziona un’organizzazione o inserisci il nome di una nuova organizzazione.'); newName.reportValidity(); } else newName.setCustomValidity(''); });
})();
</script>
