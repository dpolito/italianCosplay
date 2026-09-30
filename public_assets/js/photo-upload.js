(function () {
	const root = document.querySelector('[data-photo-upload]');
	if (!root) return;

	const csrf = root.querySelector('[data-csrf]').value;
	const eventSearch = root.querySelector('[data-event-search]');
	const eventIdInput = root.querySelector('[data-event-id]');
	const eventResults = root.querySelector('[data-event-results]');
	const eventSelected = root.querySelector('[data-event-selected]');
	const dropzone = root.querySelector('[data-dropzone]');
	const fileInput = root.querySelector('[data-file-input]');
	const startButton = root.querySelector('[data-start]');
	const list = root.querySelector('[data-file-list]');
	const totalNode = root.querySelector('[data-total]');
	const completedNode = root.querySelector('[data-completed]');
	const summary = root.querySelector('[data-upload-summary]');
	const concurrency = Math.max(1, parseInt(root.dataset.concurrency || '2', 10));
	const maxUploadBytes = Math.max(1, parseInt(root.dataset.maxUploadBytes || '1', 10));
	let files = [];
	let active = 0;
	let completed = 0;
	let started = false;

	function refreshCounts() {
		totalNode.textContent = files.length.toString();
		completedNode.textContent = completed.toString();
		const hasEvent = Boolean(eventIdInput.value);
		const hasFiles = files.length > 0;
		startButton.disabled = !hasEvent || !hasFiles;
		startButton.textContent = !hasEvent && hasFiles ? 'Seleziona un evento' : 'Carica';
		startButton.classList.toggle('bg-green-800', hasEvent && hasFiles);
		startButton.classList.toggle('hover:bg-green-700', hasEvent && hasFiles);
		startButton.classList.toggle('bg-gray-400', !hasEvent || !hasFiles);
		startButton.classList.toggle('cursor-not-allowed', !hasEvent || !hasFiles);
		if (!started && hasFiles) {
			showSummary('Pronte per il caricamento', 'Seleziona un evento e premi Carica. Ogni foto avrà uno stato indipendente.', 'info');
		}
	}

	function formatFileSize(bytes) {
		return (bytes / 1024 / 1024).toFixed(1).replace('.', ',') + ' MB';
	}

	function addFiles(selectedFiles) {
		Array.from(selectedFiles).forEach((file) => {
			const item = { file, status: file.size > maxUploadBytes ? 'error' : 'waiting', progress: 0, element: null };
			files.push(item);
			renderItem(item);
			if (item.status === 'error') {
				updateItem(item, 'file troppo grande');
			} else {
				updateItem(item, 'in attesa');
			}
		});
		refreshCounts();
	}

	function renderItem(item) {
		const li = document.createElement('li');
		li.className = 'overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm';
		const previewUrl = URL.createObjectURL(item.file);
		li.innerHTML = '<div class="aspect-[4/3] bg-gray-100"><img data-preview alt="" class="h-full w-full object-cover"></div><div class="p-3"><div class="flex items-start justify-between gap-3"><span class="min-w-0 truncate text-sm font-semibold" data-name></span><span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-bold" data-status>in attesa</span></div><p class="mt-1 text-xs text-gray-500" data-size></p><div class="mt-2 h-2 overflow-hidden rounded bg-gray-100"><div data-bar class="h-full w-0 bg-green-700"></div></div><button type="button" data-retry class="mt-2 hidden rounded border border-red-700 px-3 py-1 text-sm font-semibold text-red-700">Riprova</button></div>';
		li.querySelector('[data-preview]').src = previewUrl;
		li.querySelector('[data-preview]').alt = 'Anteprima di ' + item.file.name;
		li.querySelector('[data-name]').textContent = item.file.name;
		li.querySelector('[data-size]').textContent = formatFileSize(item.file.size);
		item.previewUrl = previewUrl;
		li.querySelector('[data-retry]').addEventListener('click', () => {
			if (item.file.size > maxUploadBytes) {
				updateItem(item, 'file troppo grande');
				return;
			}
			item.status = 'waiting';
			item.progress = 0;
			updateItem(item, 'in attesa');
			pump();
		});
		item.element = li;
		list.appendChild(li);
	}

	function updateItem(item, label) {
		const status = item.element.querySelector('[data-status]');
		const bar = item.element.querySelector('[data-bar]');
		const retry = item.element.querySelector('[data-retry]');
		status.textContent = label;
		bar.style.width = item.progress + '%';
		retry.classList.toggle('hidden', item.status !== 'error');
		status.className = 'shrink-0 rounded-full px-2 py-0.5 text-xs font-bold ' + statusClass(item.status);
		bar.className = 'h-full ' + barClass(item.status);
		item.element.classList.toggle('border-red-300', item.status === 'error');
		item.element.classList.toggle('border-green-300', item.status === 'done');
	}

	function statusClass(status) {
		if (status === 'done') return 'bg-green-100 text-green-800';
		if (status === 'error') return 'bg-red-100 text-red-800';
		if (status === 'uploading') return 'bg-blue-100 text-blue-800';
		return 'bg-gray-100 text-gray-700';
	}

	function barClass(status) {
		if (status === 'done') return 'bg-green-700';
		if (status === 'error') return 'bg-red-600';
		if (status === 'uploading') return 'bg-blue-700';
		return 'bg-gray-300';
	}

	function uploadItem(item) {
		started = true;
		item.status = 'uploading';
		item.progress = 5;
		updateItem(item, 'caricamento');
		showSummary('Upload in corso', 'Le foto vengono caricate una alla volta in coda. Puoi lasciare aperta questa pagina.', 'info');
		active++;
		const formData = new FormData();
		formData.append('csrf_token', csrf);
		formData.append('event_id', eventIdInput.value);
		formData.append('photo', item.file);
		const xhr = new XMLHttpRequest();
		xhr.open('POST', '/dashboard/photos/upload');
		xhr.setRequestHeader('Accept', 'application/json');
		xhr.upload.onprogress = (event) => {
			if (!event.lengthComputable) return;
			item.progress = Math.max(5, Math.min(85, Math.round((event.loaded / event.total) * 85)));
			updateItem(item, item.progress + '%');
		};
		xhr.onload = () => {
			active--;
			let payload = {};
			try { payload = JSON.parse(xhr.responseText || '{}'); } catch (e) {}
			if (xhr.status >= 200 && xhr.status < 300 && payload.success) {
				item.status = 'done';
				item.progress = 100;
				completed++;
				if (item.previewUrl) {
					URL.revokeObjectURL(item.previewUrl);
					item.previewUrl = null;
				}
				updateItem(item, 'completata');
			} else {
				item.status = 'error';
				updateItem(item, payload.message || 'errore');
			}
			refreshCounts();
			pump();
			checkFinished();
		};
		xhr.onerror = () => {
			active--;
			item.status = 'error';
			updateItem(item, 'errore di rete');
			pump();
			checkFinished();
		};
		xhr.send(formData);
	}

	function pump() {
		started = true;
		while (active < concurrency) {
			const next = files.find((item) => item.status === 'waiting');
			if (!next) break;
			uploadItem(next);
		}
		checkFinished();
	}

	function checkFinished() {
		if (!started || active > 0) return;
		const pending = files.some((item) => item.status === 'waiting' || item.status === 'uploading');
		if (pending) return;
		const failed = files.filter((item) => item.status === 'error').length;
		const done = files.filter((item) => item.status === 'done').length;
		if (done > 0 && failed === 0) {
			showSummary('Upload completato', done + ' foto caricate correttamente. Ora puoi gestire associazioni, cosplayer e cosplay.', 'success', true);
		} else if (done > 0 && failed > 0) {
			showSummary('Upload completato con errori', done + ' foto caricate, ' + failed + ' da controllare o riprovare.', 'warning', true);
		} else if (failed > 0) {
			showSummary('Upload non riuscito', 'Nessuna foto caricata. Controlla gli errori indicati sulle singole immagini e riprova.', 'error', false);
		}
	}

	function showSummary(title, message, type, showManageLink) {
		if (!summary) return;
		const classes = {
			info: 'border-blue-200 bg-blue-50 text-blue-950',
			success: 'border-green-200 bg-green-50 text-green-950',
			warning: 'border-amber-200 bg-amber-50 text-amber-950',
			error: 'border-red-200 bg-red-50 text-red-950'
		};
		const manageUrl = '/dashboard/photos/event/' + encodeURIComponent(eventIdInput.value || '');
		summary.className = 'rounded-xl border p-4 ' + (classes[type] || classes.info);
		summary.innerHTML = '<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><p class="font-bold"></p><p class="mt-1 text-sm"></p></div><div class="flex flex-wrap gap-2" data-actions></div></div>';
		summary.querySelector('p').textContent = title;
		summary.querySelectorAll('p')[1].textContent = message;
		const actions = summary.querySelector('[data-actions]');
		if (showManageLink && eventIdInput.value) {
			const link = document.createElement('a');
			link.href = manageUrl;
			link.className = 'rounded-lg bg-green-800 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700';
			link.textContent = 'Gestisci foto';
			actions.appendChild(link);
		}
		if (files.some((item) => item.status === 'error')) {
			const retry = document.createElement('button');
			retry.type = 'button';
			retry.className = 'rounded-lg border border-red-700 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50';
			retry.textContent = 'Riprova errori';
			retry.addEventListener('click', retryFailed);
			actions.appendChild(retry);
		}
		summary.classList.remove('hidden');
	}

	function retryFailed() {
		files.forEach((item) => {
			if (item.status !== 'error' || item.file.size > maxUploadBytes) return;
			item.status = 'waiting';
			item.progress = 0;
			updateItem(item, 'in attesa');
		});
		pump();
	}

	let searchTimer = null;
	function formatDate(value) {
		if (!value) return '';
		const parts = value.split('-');
		if (parts.length !== 3) return value;
		const date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
		return new Intl.DateTimeFormat('it-IT', {
			day: 'numeric',
			month: 'long',
			year: 'numeric'
		}).format(date);
	}

	function eventLabel(event) {
		const dates = event.data_inizio
			? formatDate(event.data_inizio) + (event.data_fine && event.data_fine !== event.data_inizio ? ' - ' + formatDate(event.data_fine) : '')
			: '';
		const place = event.comune_nome || event.luogo || '';
		return [event.titolo, dates, place].filter(Boolean).join(' · ');
	}

	eventSearch.addEventListener('input', () => {
		clearTimeout(searchTimer);
		const query = eventSearch.value.trim();
		if (query.length < 2) {
			eventResults.classList.add('hidden');
			return;
		}
		searchTimer = setTimeout(async () => {
			const response = await fetch('/api/photos/events/search?q=' + encodeURIComponent(query), { headers: { Accept: 'application/json' } });
			const payload = await response.json();
			eventResults.innerHTML = '';
			(payload.events || []).forEach((event) => {
				const button = document.createElement('button');
				button.type = 'button';
				button.className = 'block w-full px-3 py-2 text-left hover:bg-green-50';
				button.innerHTML = '<span class="block font-semibold"></span><span class="block text-sm text-gray-600"></span>';
				button.querySelector('span').textContent = event.titolo;
				const resultDates = event.data_inizio
					? formatDate(event.data_inizio) + (event.data_fine && event.data_fine !== event.data_inizio ? ' - ' + formatDate(event.data_fine) : '')
					: '';
				button.querySelectorAll('span')[1].textContent = [resultDates, event.comune_nome || event.luogo].filter(Boolean).join(' · ');
				button.addEventListener('click', () => {
					eventIdInput.value = event.id;
					eventSelected.textContent = 'Evento selezionato: ' + eventLabel(event);
					eventResults.classList.add('hidden');
					refreshCounts();
				});
				eventResults.appendChild(button);
			});
			eventResults.classList.toggle('hidden', !eventResults.children.length);
		}, 220);
	});

	fileInput.addEventListener('change', () => addFiles(fileInput.files));
	['dragenter', 'dragover'].forEach((name) => dropzone.addEventListener(name, (event) => {
		event.preventDefault();
		dropzone.classList.add('border-green-700', 'bg-green-50');
	}));
	['dragleave', 'drop'].forEach((name) => dropzone.addEventListener(name, (event) => {
		event.preventDefault();
		dropzone.classList.remove('border-green-700', 'bg-green-50');
	}));
	dropzone.addEventListener('drop', (event) => addFiles(event.dataTransfer.files));
	startButton.addEventListener('click', pump);
	refreshCounts();
})();
