<?php use App\Core\Session; $event = $event ?? []; $regions = $regions ?? []; $provinces = $provinces ?? []; $municipalities = $municipalities ?? []; $types = $types ?? []; ?>
<div class="mx-auto max-w-6xl p-6">
	<a href="/dashboard/organizations/<?php echo (int) $organizationId; ?>/masters/<?php echo (int) $event['event_master_id']; ?>" class="font-semibold text-green-800">← Torna al master</a>
	<h1 class="mt-5 text-3xl font-bold">Modifica edizione</h1>
	<p class="mt-1 text-slate-500">Le modifiche vengono applicate all'edizione già pubblicata o in revisione.</p>
	<?php if (!empty($canManage) && !empty($event['approvato'])): ?><div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4"><p class="text-sm text-amber-900">Puoi ritirare questa edizione dalla pubblicazione senza cancellarla.</p><form data-withdraw-form method="post" action="/dashboard/organizations/<?php echo (int) $organizationId; ?>/events/<?php echo (int) $event['id']; ?>/withdraw"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>"><button class="rounded-lg border border-amber-700 px-4 py-2 font-bold text-amber-950">Ritira edizione</button></form></div><?php endif; ?>
	<?php if ($message = Session::getFlash('error')): ?><div class="mt-4 rounded-lg bg-red-100 p-4 text-red-800"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
	<div id="dashboard-event-feedback" class="mt-4 hidden rounded-lg p-4 text-sm"></div>
	<form class="mt-6 space-y-5 rounded-2xl bg-white p-6 shadow" action="/dashboard/organizations/<?php echo (int) $organizationId; ?>/events/<?php echo (int) $event['id']; ?>/update" method="post" data-ajax-submit="true">
		<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
		<input type="hidden" name="guest_ids" id="dashboard-guest-ids" value="">
		<div class="grid gap-5 md:grid-cols-2">
			<label class="font-semibold">Titolo<input required class="mt-1 w-full rounded-lg border p-3" name="titolo" value="<?php echo htmlspecialchars($event['titolo'] ?? ''); ?>"></label>
			<label class="font-semibold md:max-w-[150px]">Anno<input required type="number" min="2000" max="2100" class="mt-1 w-full rounded-lg border p-3" name="anno" value="<?php echo (int) ($event['year'] ?? date('Y')); ?>"></label>
			<label class="font-semibold">Luogo<input required class="mt-1 w-full rounded-lg border p-3" name="luogo" value="<?php echo htmlspecialchars($event['luogo'] ?? ''); ?>"></label>
			<label class="font-semibold md:max-w-[220px]">Data inizio<input required type="date" class="mt-1 w-full rounded-lg border p-3" name="data_inizio" value="<?php echo htmlspecialchars(substr((string) ($event['data_inizio'] ?? ''), 0, 10)); ?>"></label>
			<label class="font-semibold md:max-w-[220px]">Data fine<input type="date" class="mt-1 w-full rounded-lg border p-3" name="data_fine" value="<?php echo htmlspecialchars(substr((string) ($event['data_fine'] ?? ''), 0, 10)); ?>"></label>
			<label class="font-semibold">Regione<select class="mt-1 w-full rounded-lg border p-3" name="regione_id"><?php foreach ($regions as $region): ?><option value="<?php echo (int) $region['id']; ?>" <?php echo (int) $region['id'] === (int) ($event['regione_id'] ?? 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars($region['nome']); ?></option><?php endforeach; ?></select></label>
			<label class="font-semibold">Provincia<select class="mt-1 w-full rounded-lg border p-3" name="provincia_id" id="dashboard-event-province"><?php foreach ($provinces as $province): ?><option value="<?php echo (int) $province['id']; ?>" <?php echo (int) $province['id'] === (int) ($event['provincia_id'] ?? 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars($province['nome']); ?></option><?php endforeach; ?></select></label>
			<label class="font-semibold">Comune<select class="mt-1 w-full rounded-lg border p-3" name="comune_id" id="dashboard-event-municipality"><?php foreach ($municipalities as $municipality): ?><option value="<?php echo (int) $municipality['id']; ?>" <?php echo (int) $municipality['id'] === (int) ($event['comune_id'] ?? 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars($municipality['nome']); ?></option><?php endforeach; ?></select></label>
			<label class="font-semibold">Tipo evento<select class="mt-1 w-full rounded-lg border p-3" name="tipo_evento_id"><?php foreach ($types as $type): ?><option value="<?php echo (int) $type['id']; ?>" <?php echo (int) $type['id'] === (int) ($event['tipo_evento_id'] ?? 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['nome']); ?></option><?php endforeach; ?></select></label>
		</div>
		<div>
			<label for="event-description-editor" class="font-semibold">Descrizione</label>
			<div id="event-description-editor" class="mt-1 min-h-48 rounded-lg border bg-white"></div>
		</div>
		<div class="grid gap-5 md:grid-cols-2"><label class="font-semibold">Sito web<input type="url" class="mt-1 w-full rounded-lg border p-3" name="sito_web" value="<?php echo htmlspecialchars($event['sito_web'] ?? ''); ?>"></label><?php foreach (['social_facebook'=>'Facebook','social_instagram'=>'Instagram','social_tiktok'=>'TikTok','social_youtube'=>'YouTube'] as $field => $label): ?><label class="font-semibold"><?php echo $label; ?><input type="url" class="mt-1 w-full rounded-lg border p-3" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($event[$field] ?? ''); ?>"></label><?php endforeach; ?></div>
		<div class="flex flex-wrap gap-6"><label><input type="checkbox" name="is_paid" value="1" <?php echo !empty($event['is_paid']) ? 'checked' : ''; ?>> Evento a pagamento</label><label><input type="checkbox" name="has_cosplay_contest" value="1" <?php echo !empty($event['has_cosplay_contest']) ? 'checked' : ''; ?>> Contest cosplay</label></div>
		<section class="rounded-xl border border-slate-200 p-4">
			<div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg font-bold">Ospiti evento</h2><button type="button" id="dashboard-new-guest" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white">+ Nuovo ospite</button></div>
			<div class="relative mt-3"><input id="dashboard-guest-search" type="search" autocomplete="off" placeholder="Cerca ospite..." class="w-full rounded-lg border p-3"><div id="dashboard-guest-results" class="absolute z-10 hidden max-h-60 w-full overflow-auto rounded-lg border bg-white shadow"></div></div>
			<div id="dashboard-selected-guests" class="mt-4 flex flex-wrap gap-2"></div>
		</section>
		<div class="flex justify-end"><button class="rounded-lg bg-green-700 px-5 py-3 font-bold text-white" type="submit">Salva modifiche</button></div>
	</form>
</div>
<script src="/public_assets/js/wysiwyg-editor.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
	WysiwygEditor.init('#event-description-editor', {
		content: <?php echo json_encode($event['descrizione'] ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
		name: 'descrizione',
		placeholder: "Scrivi la descrizione dell'edizione...",
	});

	const region = document.querySelector('[name="regione_id"]');
	const province = document.getElementById('dashboard-event-province');
	const municipality = document.getElementById('dashboard-event-municipality');
	if (!region || !province || !municipality) return;
	region.addEventListener('change', async function () {
		province.innerHTML = '<option value="">Caricamento province...</option>';
		municipality.innerHTML = '<option value="">Seleziona comune</option>';
		const response = await fetch('/api/province/' + encodeURIComponent(region.value));
		const rows = await response.json();
		province.innerHTML = '<option value="">Seleziona provincia</option>';
		rows.forEach(function (row) {
			province.insertAdjacentHTML('beforeend', '<option value="' + Number(row.id) + '">' + String(row.nome).replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</option>');
		});
	});
	province.addEventListener('change', async function () {
		municipality.innerHTML = '<option value="">Caricamento comuni...</option>';
		const response = await fetch('/api/comuni/' + encodeURIComponent(province.value));
		const rows = await response.json();
		municipality.innerHTML = '<option value="">Seleziona comune</option>';
		rows.forEach(function (row) {
			municipality.insertAdjacentHTML('beforeend', '<option value="' + Number(row.id) + '">' + String(row.nome).replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</option>');
		});
	});

	let selectedGuests = <?php echo json_encode(array_map(static function (array $guest): array { $guest['_attached'] = true; return $guest; }, $event['guests'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	const guestSearch = document.getElementById('dashboard-guest-search');
	const guestResults = document.getElementById('dashboard-guest-results');
	const selectedGuestBox = document.getElementById('dashboard-selected-guests');
	const guestIds = document.getElementById('dashboard-guest-ids');
	function renderGuests() {
		selectedGuestBox.innerHTML = '';
		selectedGuests.forEach(function (guest) {
			const item = document.createElement('span');
			item.className = 'inline-flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800';
			item.textContent = guest.name;
			const remove = document.createElement('button');
			remove.type = 'button'; remove.className = 'font-bold'; remove.textContent = '×';
			remove.addEventListener('click', async function () {
				if (guest._attached) {
					if (!window.confirm('Rimuovere questo guest dall’edizione?')) return;
					const body = new FormData();
					body.append('csrf_token', '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES); ?>');
					try {
						const response = await fetch('/dashboard/events/<?php echo (int) $event['id']; ?>/guests/' + Number(guest.id) + '/remove', { method: 'POST', body: body, headers: { Accept: 'application/json' } });
						const result = await response.json();
						if (!response.ok || !result.success) throw new Error(result.message || 'Guest non rimosso.');
					} catch (error) {
						window.alert(error.message || 'Guest non rimosso.');
						return;
					}
				}
				selectedGuests = selectedGuests.filter(function (row) { return Number(row.id) !== Number(guest.id); });
				renderGuests();
			});
			item.appendChild(remove); selectedGuestBox.appendChild(item);
		});
		guestIds.value = JSON.stringify(selectedGuests.map(function (guest) { return Number(guest.id); }));
	}
	let guestTimer;
	guestSearch.addEventListener('input', function () {
		clearTimeout(guestTimer); guestResults.innerHTML = ''; guestResults.classList.add('hidden');
		if (guestSearch.value.trim().length < 2) return;
		guestTimer = setTimeout(async function () {
			const response = await fetch('/dashboard/events/<?php echo (int) $event['id']; ?>/guests/search?q=' + encodeURIComponent(guestSearch.value.trim()), { headers: { Accept: 'application/json' } });
			const payload = await response.json(); guestResults.classList.remove('hidden');
			(payload.guests || []).forEach(function (guest) {
				const option = document.createElement('button'); option.type = 'button'; option.className = 'block w-full px-3 py-2 text-left hover:bg-slate-50'; option.textContent = guest.name;
				option.addEventListener('click', function () { if (!selectedGuests.some(function (row) { return Number(row.id) === Number(guest.id); })) { guest._attached = false; selectedGuests.push(guest); } renderGuests(); guestSearch.value = ''; guestResults.classList.add('hidden'); });
				guestResults.appendChild(option);
			});
		}, 250);
	});
	document.getElementById('dashboard-new-guest').addEventListener('click', async function () {
		const name = window.prompt('Nome del nuovo guest:'); if (!name || name.trim().length < 2) return;
		const body = new FormData(); body.append('name', name.trim()); body.append('csrf_token', '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES); ?>');
		const response = await fetch('/dashboard/events/<?php echo (int) $event['id']; ?>/guests/create', { method: 'POST', body: body, headers: { Accept: 'application/json' } });
		const payload = await response.json(); if (!response.ok || !payload.success) { window.alert(payload.message || 'Guest non creato.'); return; }
		payload.guest._attached = false; selectedGuests.push(payload.guest); renderGuests();
	});
	renderGuests();
});
</script>
<?php if (!empty($canManage)): ?><script>
document.querySelectorAll('[data-withdraw-form]').forEach((form) => {
	form.addEventListener('submit', async (event) => {
		event.preventDefault();
		if (!window.confirm('Ritirare questa edizione dalla pubblicazione?')) return;
		const button = form.querySelector('button');
		if (button) button.disabled = true;
		try {
			const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
			const result = await response.json();
			if (!response.ok || !result.success) throw new Error(result.message || 'Operazione non riuscita.');
			form.replaceWith(document.createTextNode(result.message));
		} catch (error) {
			window.alert(error.message || 'Operazione non riuscita.');
			if (button) button.disabled = false;
		}
	});
});
</script><?php endif; ?>
<script>
document.querySelector('form[data-ajax-submit]').addEventListener('submit', async function (event) {
	event.preventDefault();
	const form = event.currentTarget;
	const button = form.querySelector('button[type="submit"]');
	const feedback = document.getElementById('dashboard-event-feedback');
	button.disabled = true;
	try {
		const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
		const result = await response.json();
		if (!response.ok || !result.success) throw new Error(result.message || 'Salvataggio non riuscito.');
		feedback.textContent = result.message || 'Edizione aggiornata correttamente.';
		feedback.className = 'mt-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800';
	} catch (error) {
		feedback.textContent = error.message || 'Salvataggio non riuscito.';
		feedback.className = 'mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800';
	} finally {
		button.disabled = false;
	}
});
</script>
