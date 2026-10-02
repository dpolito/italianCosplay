<?php
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$summary = is_array($currentImport['summary'] ?? null) ? $currentImport['summary'] : [];
$groups = is_array($summary['groups'] ?? null) ? $summary['groups'] : [];
?>

<style>
	@media (min-width: 1024px) {
		.legacy-summary-grid {
			display: grid;
			grid-template-columns: repeat(6, minmax(0, 1fr));
		}
	}
</style>

<div class="space-y-6" data-legacy-import-page data-csrf="<?= $h($csrf_token) ?>">
	<div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
		<div>
			<h2 class="text-2xl font-bold text-gray-900">Importazione foto legacy</h2>
			<p class="text-sm text-gray-600">Analizza un JSON del vecchio portale e importa le immagini usando la pipeline foto esistente.</p>
		</div>
		<?php if (!empty($currentImport['id'])): ?>
			<a class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50" href="/admin/photos/import-legacy/<?= (int) $currentImport['id'] ?>/report">Scarica report CSV</a>
		<?php endif; ?>
	</div>

	<section class="rounded-lg bg-white p-5 shadow-sm">
		<form id="legacy-analyze-form" class="grid gap-4 md:grid-cols-[1fr_1fr_auto]" enctype="multipart/form-data">
			<input type="hidden" name="csrf_token" value="<?= $h($csrf_token) ?>">
			<input type="hidden" name="destination_user_id" id="destination-user-id" value="">
			<label class="block">
				<span class="mb-1 block text-sm font-semibold text-gray-700">File JSON</span>
				<input class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" type="file" name="json_file" accept="application/json,.json" required>
			</label>
			<label class="block">
				<span class="mb-1 block text-sm font-semibold text-gray-700">Utente destinazione</span>
				<input id="destination-user-search" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" type="search" placeholder="Cerca username, nome o cognome" autocomplete="off" required>
				<div id="destination-user-results" class="mt-2 hidden rounded-md border border-gray-200 bg-white shadow-sm"></div>
			</label>
			<div class="flex items-end">
				<button class="w-full rounded-md bg-green-700 px-4 py-2 text-sm font-bold text-white hover:bg-green-800" type="submit">Analizza file</button>
			</div>
		</form>
		<div id="legacy-message" class="mt-4 hidden rounded-md px-4 py-3 text-sm"></div>
	</section>

	<?php if (!empty($currentImport)): ?>
		<section class="rounded-lg bg-white p-5 shadow-sm" data-import-id="<?= (int) $currentImport['id'] ?>">
			<div class="mb-4 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
				<div>
					<h3 class="text-lg font-bold text-gray-900">Import #<?= (int) $currentImport['id'] ?> · <?= $h($currentImport['filename']) ?></h3>
					<p class="text-sm text-gray-600">Utente destinazione: <strong><?= $h($currentImport['destination_username'] ?? '') ?></strong> · Stato: <span id="import-status"><?= $h($currentImport['status']) ?></span></p>
				</div>
				<a class="text-sm font-semibold text-green-700 hover:text-green-900" href="/admin/photos/import-legacy">Nuovo import</a>
			</div>

			<div class="legacy-summary-grid grid gap-3 md:grid-cols-3">
				<?php
				$cards = [
					'Foto' => (int) ($summary['total'] ?? $currentImport['total_records'] ?? 0),
					'Validi' => (int) ($summary['valid'] ?? $currentImport['valid_records'] ?? 0),
					'Con evento' => (int) ($summary['with_event'] ?? 0),
					'Senza evento' => (int) ($summary['without_event'] ?? 0),
					'Eventi' => (int) ($summary['event_groups'] ?? 0),
					'Anomalie' => (int) ($summary['invalid'] ?? 0),
				];
				foreach ($cards as $label => $value):
				?>
					<div class="rounded-md border border-gray-200 p-3">
						<div class="text-xs font-semibold uppercase text-gray-500"><?= $h($label) ?></div>
						<div class="text-2xl font-bold text-gray-900"><?= (int) $value ?></div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ($groups): ?>
				<form id="legacy-mapping-form" class="mt-6">
					<input type="hidden" name="csrf_token" value="<?= $h($csrf_token) ?>">
					<div class="overflow-x-auto rounded-md border border-gray-200">
						<table class="min-w-full divide-y divide-gray-200 text-sm">
							<thead class="bg-gray-50 text-left text-xs font-bold uppercase text-gray-500">
								<tr>
									<th class="px-3 py-2">Evento legacy</th>
									<th class="px-3 py-2">Anno</th>
									<th class="px-3 py-2">Foto</th>
									<th class="px-3 py-2">Evento nuovo</th>
									<th class="px-3 py-2">Stato</th>
									<th class="px-3 py-2">Azioni</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-gray-100">
								<?php foreach ($groups as $group): ?>
									<tr data-group-row data-group-key="<?= $h($group['key'] ?? '') ?>" data-event-name="<?= $h($group['event_name'] ?? '') ?>" data-event-year="<?= $h($group['event_year'] ?? '') ?>">
										<td class="px-3 py-2 font-medium text-gray-900">
											<div class="whitespace-nowrap"><?= $h($group['event_name'] ?? '') ?></div>
										</td>
										<td class="px-3 py-2"><?= $h($group['event_year'] ?? '') ?></td>
										<td class="px-3 py-2"><?= (int) ($group['photo_count'] ?? 0) ?></td>
										<td class="px-3 py-2">
											<div class="flex min-w-[520px] items-center gap-2">
											<select class="mapping-select min-w-[260px] flex-1 rounded-md border border-gray-300 px-2 py-1" data-key="<?= $h($group['key'] ?? '') ?>">
												<option value="">Nessun evento</option>
												<?php foreach (($group['candidates'] ?? []) as $event): ?>
													<option value="<?= (int) $event['id'] ?>" <?= (int) ($group['suggested_event_id'] ?? 0) === (int) $event['id'] ? 'selected' : '' ?>>
														#<?= (int) $event['id'] ?> · <?= $h($event['titolo'] ?? '') ?> <?= !empty($event['data_inizio']) ? '· ' . $h($event['data_inizio']) : '' ?>
													</option>
												<?php endforeach; ?>
											</select>
											<input class="event-search-input w-48 rounded-md border border-gray-300 px-2 py-1 text-sm" type="search" value="<?= $h($group['event_name'] ?? '') ?>" aria-label="Cerca evento nuovo">
											<button class="event-search-button rounded-md border border-gray-300 px-3 py-1 text-sm font-semibold text-gray-700 hover:bg-gray-50" type="button">Cerca</button>
											</div>
										</td>
										<td class="px-3 py-2">
											<?php $status = (string) ($group['match_status'] ?? 'missing'); ?>
											<span class="mapping-status rounded-full px-2 py-1 text-xs font-bold <?= $status === 'probable' ? 'bg-emerald-100 text-emerald-800' : ($status === 'verify' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') ?>">
												<?= $status === 'probable' ? 'Trovato' : ($status === 'verify' ? 'Da verificare' : 'Nessuna corrispondenza') ?>
											</span>
										</td>
										<td class="px-3 py-2">
											<button class="import-group-button whitespace-nowrap rounded-md bg-green-700 px-3 py-1 text-sm font-bold text-white hover:bg-green-800" type="button">Importa evento</button>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<div class="mt-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
						<label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700">
							<input id="mapping-verified" type="checkbox" class="h-4 w-4 rounded border-gray-300">
							Ho verificato la mappatura degli eventi
						</label>
						<div class="flex gap-2">
							<button id="save-mapping-button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50" type="submit">Salva mapping</button>
							<button id="start-import" class="rounded-md bg-green-700 px-4 py-2 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-40" type="button" disabled>Avvia importazione</button>
							<button id="retry-errors" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-bold text-white" type="button">Riprova errori</button>
						</div>
					</div>
				</form>
			<?php endif; ?>

			<div class="mt-6 rounded-md border border-gray-200 p-4">
				<div class="mb-2 flex justify-between text-sm font-semibold text-gray-700">
					<span>Avanzamento</span>
					<span id="progress-label"><?= (int) ($currentImport['processed_records'] ?? 0) ?> / <?= (int) ($currentImport['total_records'] ?? 0) ?></span>
				</div>
				<div class="h-4 overflow-hidden rounded-full bg-gray-200">
					<div id="progress-bar" class="h-full bg-green-700" style="width: <?= (int) ($currentImport['total_records'] ?? 0) > 0 ? min(100, round(((int) $currentImport['processed_records'] / (int) $currentImport['total_records']) * 100)) : 0 ?>%"></div>
				</div>
				<div class="mt-3 grid gap-2 text-sm md:grid-cols-4">
					<div>Importate: <strong id="stat-imported"><?= (int) ($currentImport['imported_records'] ?? 0) ?></strong></div>
					<div>Già presenti: <strong id="stat-skipped"><?= (int) ($currentImport['skipped_records'] ?? 0) ?></strong></div>
					<div>Errori: <strong id="stat-errors"><?= (int) ($currentImport['error_records'] ?? 0) ?></strong></div>
					<div>Rimanenti: <strong id="stat-remaining">-</strong></div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="rounded-lg bg-white p-5 shadow-sm">
		<h3 class="mb-3 text-lg font-bold text-gray-900">Importazioni recenti</h3>
		<div class="overflow-x-auto">
			<table class="min-w-full divide-y divide-gray-200 text-sm">
				<thead class="bg-gray-50 text-left text-xs font-bold uppercase text-gray-500">
					<tr>
						<th class="px-3 py-2">ID</th>
						<th class="px-3 py-2">File</th>
						<th class="px-3 py-2">Utente</th>
						<th class="px-3 py-2">Foto</th>
						<th class="px-3 py-2">Importate</th>
						<th class="px-3 py-2">Errori</th>
						<th class="px-3 py-2">Stato</th>
						<th class="px-3 py-2"></th>
					</tr>
				</thead>
				<tbody class="divide-y divide-gray-100">
					<?php foreach ($recentImports as $import): ?>
						<tr>
							<td class="px-3 py-2">#<?= (int) $import['id'] ?></td>
							<td class="px-3 py-2"><?= $h($import['filename']) ?></td>
							<td class="px-3 py-2"><?= $h($import['destination_username']) ?></td>
							<td class="px-3 py-2"><?= (int) $import['total_records'] ?></td>
							<td class="px-3 py-2"><?= (int) $import['imported_records'] ?></td>
							<td class="px-3 py-2"><?= (int) $import['error_records'] ?></td>
							<td class="px-3 py-2"><?= $h($import['status']) ?></td>
							<td class="px-3 py-2 text-right"><a class="font-semibold text-green-700" href="/admin/photos/import-legacy?import_id=<?= (int) $import['id'] ?>">Apri</a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</section>
</div>

<script>
(() => {
	const page = document.querySelector('[data-legacy-import-page]');
	if (!page) return;
	const csrf = page.dataset.csrf;
	const message = document.getElementById('legacy-message');
	const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
	const showMessage = (text, ok = true) => {
		if (!message) return;
		message.textContent = text;
		message.className = 'mt-4 rounded-md px-4 py-3 text-sm ' + (ok ? 'bg-emerald-100 text-emerald-900' : 'bg-red-100 text-red-900');
	};

	const userSearch = document.getElementById('destination-user-search');
	const userId = document.getElementById('destination-user-id');
	const userResults = document.getElementById('destination-user-results');
	let userTimer = null;
	userSearch?.addEventListener('input', () => {
		clearTimeout(userTimer);
		userId.value = '';
		const q = userSearch.value.trim();
		if (q.length < 2) {
			userResults.classList.add('hidden');
			return;
		}
		userTimer = setTimeout(async () => {
			const res = await fetch('/api/photos/users/search?q=' + encodeURIComponent(q));
			const data = await res.json();
			userResults.innerHTML = (data.users || []).map(user => `<button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-50" data-id="${Number(user.id)}" data-label="${escapeHtml(user.username)}">#${Number(user.id)} · ${escapeHtml(user.username)}</button>`).join('');
			userResults.classList.toggle('hidden', !userResults.innerHTML);
		}, 250);
	});
	userResults?.addEventListener('click', event => {
		const button = event.target.closest('button[data-id]');
		if (!button) return;
		userId.value = button.dataset.id;
		userSearch.value = button.dataset.label;
		userResults.classList.add('hidden');
	});

	document.getElementById('legacy-analyze-form')?.addEventListener('submit', async event => {
		event.preventDefault();
		if (!userId.value) {
			showMessage('Seleziona un utente destinazione dai risultati.', false);
			return;
		}
		const res = await fetch('/admin/photos/import-legacy/analyze', { method: 'POST', body: new FormData(event.currentTarget) });
		const data = await res.json();
		if (!data.success) {
			showMessage(data.message || 'Analisi non riuscita.', false);
			return;
		}
		location.href = '/admin/photos/import-legacy?import_id=' + data.import_id;
	});

	const importSection = document.querySelector('[data-import-id]');
	const importId = importSection?.dataset.importId;
	document.getElementById('mapping-verified')?.addEventListener('change', event => {
		document.getElementById('start-import').disabled = !event.target.checked;
	});
	const currentMapping = () => {
		const mapping = {};
		document.querySelectorAll('.mapping-select').forEach(select => {
			mapping[select.dataset.key] = select.value ? Number(select.value) : null;
		});
		return mapping;
	};
	const saveMapping = async () => {
		const body = new FormData();
		body.set('csrf_token', csrf);
		body.set('mapping', JSON.stringify(currentMapping()));
		const res = await fetch(`/admin/photos/import-legacy/${importId}/mapping`, { method: 'POST', body });
		const data = await res.json();
		showMessage(data.message || (data.success ? 'Mapping salvato.' : 'Errore mapping.'), data.success);
		return data.success;
	};
	document.getElementById('legacy-mapping-form')?.addEventListener('submit', async event => {
		event.preventDefault();
		await saveMapping();
	});

	document.querySelectorAll('[data-group-row]').forEach(row => {
		const select = row.querySelector('.mapping-select');
		const searchInput = row.querySelector('.event-search-input');
		const searchButton = row.querySelector('.event-search-button');
		const status = row.querySelector('.mapping-status');
		searchButton?.addEventListener('click', async () => {
			const query = searchInput.value.trim();
			const year = row.dataset.eventYear || '';
			searchButton.disabled = true;
			searchButton.textContent = 'Cerco...';
			try {
				const res = await fetch('/admin/photos/import-legacy/events/search?q=' + encodeURIComponent(query) + '&year=' + encodeURIComponent(year));
				const data = await res.json();
				if (!data.success) {
					showMessage(data.message || 'Ricerca evento non riuscita.', false);
					return;
				}
				const selected = select.value;
				select.innerHTML = '<option value="">Nessun evento</option>' + (data.events || []).map(event => {
					const label = `#${Number(event.id)} · ${escapeHtml(event.titolo)}${event.data_inizio ? ' · ' + escapeHtml(event.data_inizio) : ''}`;
					return `<option value="${Number(event.id)}">${label}</option>`;
				}).join('');
				if ([...select.options].some(option => option.value === selected)) {
					select.value = selected;
				}
				status.textContent = (data.events || []).length > 0 ? 'Risultati trovati' : 'Nessuna corrispondenza';
				status.className = 'mapping-status rounded-full px-2 py-1 text-xs font-bold ' + ((data.events || []).length > 0 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800');
			} finally {
				searchButton.disabled = false;
				searchButton.textContent = 'Cerca';
			}
		});
	});

	document.querySelectorAll('.import-group-button').forEach(button => {
		button.addEventListener('click', async () => {
			const row = button.closest('[data-group-row]');
			const select = row?.querySelector('.mapping-select');
			if (!row || !select?.value) {
				showMessage('Seleziona un evento nuovo prima di importare questo gruppo.', false);
				return;
			}
			button.disabled = true;
			button.textContent = 'Importo...';
			const saved = await saveMapping();
			if (saved) {
				await runBatches(false, row.dataset.groupKey);
			}
			button.disabled = false;
			button.textContent = 'Importa evento';
		});
	});

	const updateProgress = data => {
		const total = Number(data.total || 0);
		const processed = Number(data.total_processed || 0);
		document.getElementById('progress-label').textContent = `${processed} / ${total}`;
		document.getElementById('progress-bar').style.width = total > 0 ? `${Math.min(100, Math.round((processed / total) * 100))}%` : '0%';
		document.getElementById('stat-imported').textContent = data.imported || 0;
		document.getElementById('stat-skipped').textContent = data.skipped || 0;
		document.getElementById('stat-errors').textContent = data.errors || 0;
		document.getElementById('stat-remaining').textContent = data.remaining || 0;
		document.getElementById('import-status').textContent = data.status || '';
	};
	const runBatches = async (retry, groupKey = '') => {
		if (!importId) return;
		let completed = false;
		while (!completed) {
			const body = new FormData();
			body.set('csrf_token', csrf);
			if (retry) body.set('retry_errors', '1');
			if (groupKey) body.set('group_key', groupKey);
			const res = await fetch(`/admin/photos/import-legacy/${importId}/process`, { method: 'POST', body });
			const data = await res.json();
			if (!data.success) {
				showMessage(data.message || 'Batch non riuscito.', false);
				return;
			}
			updateProgress(data);
			completed = data.completed || data.group_completed || data.processed === 0;
		}
		showMessage('Importazione aggiornata.');
	};
	document.getElementById('start-import')?.addEventListener('click', () => runBatches(false));
	document.getElementById('retry-errors')?.addEventListener('click', () => runBatches(true));
})();
</script>
