(function () {
	const root = document.querySelector('[data-photo-upload]');
	if (!root) return;

	const csrf = root.querySelector('[data-csrf]').value;
	const sessionJson = root.querySelector('[data-upload-session-json]');
	const eventSearch = root.querySelector('[data-event-search]');
	const eventIdInput = root.querySelector('[data-event-id]');
	const eventResults = root.querySelector('[data-event-results]');
	const eventSelected = root.querySelector('[data-event-selected]');
	const dropzone = root.querySelector('[data-dropzone]');
	const fileInput = root.querySelector('[data-file-input]');
	const startButton = root.querySelector('[data-start]');
	const confirmButton = root.querySelector('[data-confirm]');
	const cancelButton = root.querySelector('[data-cancel]');
	const list = root.querySelector('[data-file-list]');
	const totalNode = root.querySelector('[data-total]');
	const completedNode = root.querySelector('[data-completed]');
	const uploadingNode = root.querySelector('[data-uploading]');
	const waitingNode = root.querySelector('[data-waiting]');
	const failedNode = root.querySelector('[data-failed]');
	const totalBar = root.querySelector('[data-total-bar]');
	const summary = root.querySelector('[data-upload-summary]');
	const concurrency = Math.max(1, parseInt(root.dataset.concurrency || '2', 10));
	const maxUploadBytes = Math.max(1, parseInt(root.dataset.maxUploadBytes || '1', 10));
	let session = parseSession();
	let items = [];
	let active = 0;
	let started = false;

	function parseSession() {
		try { return JSON.parse(sessionJson ? sessionJson.textContent || 'null' : 'null'); } catch (error) { return null; }
	}

	function refreshCounts() {
		const total = items.length;
		const completed = items.filter((item) => item.status === 'done').length;
		const uploading = items.filter((item) => item.status === 'uploading').length;
		const waiting = items.filter((item) => item.status === 'waiting').length;
		const failed = items.filter((item) => item.status === 'error').length;
		totalNode.textContent = total.toString();
		completedNode.textContent = completed.toString();
		uploadingNode.textContent = uploading.toString();
		waitingNode.textContent = waiting.toString();
		failedNode.textContent = failed.toString();
		totalBar.style.width = total > 0 ? Math.round((completed / total) * 100) + '%' : '0%';
		const hasEvent = Boolean(eventIdInput.value);
		startButton.disabled = !hasEvent || waiting === 0;
		startButton.textContent = waiting > 0 ? 'Riprendi coda' : 'Carica';
		confirmButton.disabled = !session || completed === 0 || uploading > 0 || waiting > 0;
		cancelButton.disabled = !session;
	}

	function formatFileSize(bytes) {
		return (bytes / 1024 / 1024).toFixed(1).replace('.', ',') + ' MB';
	}

	function addFiles(selectedFiles) {
		Array.from(selectedFiles).forEach((file) => {
			const item = { file, status: file.size > maxUploadBytes ? 'error' : 'waiting', progress: 0, element: null, label: file.size > maxUploadBytes ? 'file troppo grande' : 'in attesa' };
			items.push(item);
			renderItem(item);
			updateItem(item, item.label);
		});
		refreshCounts();
		if (eventIdInput.value) {
			pump();
		} else {
			showSummary('Seleziona un evento', 'Le foto partiranno automaticamente appena scegli l’evento.', 'info');
		}
	}

	function addServerItem(serverItem) {
		const item = {
			id: serverItem.id,
			photoId: serverItem.photo_id,
			status: serverItem.status === 'completed' ? 'done' : 'error',
			progress: serverItem.status === 'completed' ? 100 : 0,
			serverItem,
			element: null,
			label: serverItem.status === 'completed' ? 'salvata' : (serverItem.error_message || 'errore')
		};
		items.push(item);
		renderItem(item);
		updateItem(item, item.label);
	}

	function renderItem(item) {
		const li = document.createElement('li');
		li.className = 'overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm';
		const name = item.file ? item.file.name : (item.serverItem.original_filename || 'foto');
		const size = item.file ? item.file.size : parseInt(item.serverItem.file_size || '0', 10);
		const previewUrl = item.file ? URL.createObjectURL(item.file) : (item.serverItem.thumbnail_url || item.serverItem.url || '');
		li.innerHTML = '<div class="aspect-[4/3] bg-gray-100"><img data-preview alt="" class="h-full w-full object-cover"></div><div class="p-3"><div class="flex items-start justify-between gap-3"><span class="min-w-0 truncate text-sm font-semibold" data-name></span><span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-bold" data-status>in attesa</span></div><p class="mt-1 text-xs text-gray-500" data-size></p><div class="mt-2 h-2 overflow-hidden rounded bg-gray-100"><div data-bar class="h-full w-0 bg-green-700"></div></div><div class="mt-2 flex flex-wrap gap-2"><button type="button" data-retry class="hidden rounded border border-red-700 px-3 py-1 text-sm font-semibold text-red-700">Riprova</button><button type="button" data-remove class="rounded border border-gray-300 px-3 py-1 text-sm font-semibold text-gray-700 hover:bg-gray-50">Rimuovi</button></div></div>';
		li.querySelector('[data-preview]').src = previewUrl;
		li.querySelector('[data-preview]').alt = 'Anteprima di ' + name;
		li.querySelector('[data-name]').textContent = name;
		li.querySelector('[data-size]').textContent = size > 0 ? formatFileSize(size) : '';
		item.previewUrl = item.file ? previewUrl : null;
		li.querySelector('[data-retry]').addEventListener('click', () => retryItem(item));
		li.querySelector('[data-remove]').addEventListener('click', () => removeItem(item));
		item.element = li;
		list.appendChild(li);
	}

	function updateItem(item, label) {
		const status = item.element.querySelector('[data-status]');
		const bar = item.element.querySelector('[data-bar]');
		const retry = item.element.querySelector('[data-retry]');
		status.textContent = label;
		bar.style.width = item.progress + '%';
		retry.classList.toggle('hidden', item.status !== 'error' || !item.file);
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
		if (!eventIdInput.value || !item.file) return;
		started = true;
		item.status = 'uploading';
		item.progress = 5;
		updateItem(item, 'caricamento');
		showSummary('Upload temporaneo in corso', 'Le foto vengono salvate progressivamente sul server.', 'info');
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
			item.progress = Math.max(5, Math.min(90, Math.round((event.loaded / event.total) * 90)));
			updateItem(item, item.progress + '%');
		};
		xhr.onload = () => {
			active--;
			let payload = {};
			try { payload = JSON.parse(xhr.responseText || '{}'); } catch (error) {}
			if (xhr.status >= 200 && xhr.status < 300 && payload.success) {
				session = payload.session || session;
				item.id = payload.item && payload.item.id;
				item.photoId = payload.item && payload.item.photo_id;
				item.serverItem = payload.item;
				item.status = 'done';
				item.progress = 100;
				revokePreview(item);
				updateItem(item, 'salvata');
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
			refreshCounts();
			pump();
			checkFinished();
		};
		xhr.send(formData);
	}

	function pump() {
		started = true;
		while (active < concurrency) {
			const next = items.find((item) => item.status === 'waiting');
			if (!next) break;
			uploadItem(next);
		}
		refreshCounts();
		checkFinished();
	}

	function checkFinished() {
		if (!started || active > 0) return;
		const pending = items.some((item) => item.status === 'waiting' || item.status === 'uploading');
		if (pending) return;
		const failed = items.filter((item) => item.status === 'error').length;
		const done = items.filter((item) => item.status === 'done').length;
		if (done > 0 && failed === 0) {
			showSummary('Upload temporaneo completato', done + ' foto sono al sicuro sul server. Controllale e conferma la pubblicazione.', 'success');
		} else if (done > 0 && failed > 0) {
			showSummary('Upload completato con errori', done + ' foto salvate, ' + failed + ' da controllare o riprovare.', 'warning');
		} else if (failed > 0) {
			showSummary('Upload non riuscito', 'Nessuna foto salvata. Controlla gli errori indicati sulle singole immagini e riprova.', 'error');
		}
		refreshCounts();
	}

	function showSummary(title, message, type) {
		if (!summary) return;
		const classes = {
			info: 'border-blue-200 bg-blue-50 text-blue-950',
			success: 'border-green-200 bg-green-50 text-green-950',
			warning: 'border-amber-200 bg-amber-50 text-amber-950',
			error: 'border-red-200 bg-red-50 text-red-950'
		};
		summary.className = 'rounded-xl border p-4 ' + (classes[type] || classes.info);
		summary.innerHTML = '<p class="font-bold"></p><p class="mt-1 text-sm"></p>';
		summary.querySelector('p').textContent = title;
		summary.querySelectorAll('p')[1].textContent = message;
		summary.classList.remove('hidden');
	}

	function retryItem(item) {
		if (!item.file || item.file.size > maxUploadBytes) return;
		item.status = 'waiting';
		item.progress = 0;
		updateItem(item, 'in attesa');
		pump();
	}

	async function removeItem(item) {
		if (item.status === 'uploading') return;
		if (item.id) {
			const payload = await post('/dashboard/photos/upload/item/remove', { item_id: item.id });
			if (!payload.success) {
				showSummary('Rimozione non riuscita', payload.message || 'Errore durante la rimozione.', 'error');
				return;
			}
		}
		revokePreview(item);
		item.element.remove();
		items = items.filter((candidate) => candidate !== item);
		refreshCounts();
	}

	async function post(url, data) {
		const formData = new FormData();
		formData.append('csrf_token', csrf);
		Object.entries(data).forEach(([key, value]) => formData.append(key, value));
		const response = await fetch(url, { method: 'POST', headers: { Accept: 'application/json' }, body: formData });
		return response.json();
	}

	function revokePreview(item) {
		if (item.previewUrl) {
			URL.revokeObjectURL(item.previewUrl);
			item.previewUrl = null;
		}
	}

	let searchTimer = null;
	function formatDate(value) {
		if (!value) return '';
		const parts = value.split('-');
		if (parts.length !== 3) return value;
		const date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
		return new Intl.DateTimeFormat('it-IT', { day: 'numeric', month: 'long', year: 'numeric' }).format(date);
	}

	function eventLabel(event) {
		const dates = event.data_inizio ? formatDate(event.data_inizio) + (event.data_fine && event.data_fine !== event.data_inizio ? ' - ' + formatDate(event.data_fine) : '') : '';
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
				button.textContent = eventLabel(event);
				button.addEventListener('click', () => {
					eventIdInput.value = event.id;
					eventSearch.value = event.titolo;
					eventSelected.textContent = 'Evento selezionato: ' + eventLabel(event);
					eventResults.classList.add('hidden');
					pump();
				});
				eventResults.appendChild(button);
			});
			eventResults.classList.toggle('hidden', !eventResults.children.length);
		}, 220);
	});

	['dragenter', 'dragover'].forEach((name) => dropzone.addEventListener(name, (event) => {
		event.preventDefault();
		dropzone.classList.add('border-green-600', 'bg-green-50');
	}));
	['dragleave', 'drop'].forEach((name) => dropzone.addEventListener(name, (event) => {
		event.preventDefault();
		dropzone.classList.remove('border-green-600', 'bg-green-50');
	}));
	dropzone.addEventListener('drop', (event) => addFiles(event.dataTransfer.files));
	fileInput.addEventListener('change', () => {
		addFiles(fileInput.files);
		fileInput.value = '';
	});
	startButton.addEventListener('click', pump);
	confirmButton.addEventListener('click', async () => {
		if (!session || active > 0) return;
		const payload = await post('/dashboard/photos/upload/session/confirm', { session_id: session.id });
		if (payload.success) {
			showSummary('Foto pubblicate', payload.photo_count + ' foto pubblicate correttamente.', 'success');
			window.location.href = '/dashboard/photos/event/' + encodeURIComponent(payload.event_id);
		} else {
			showSummary('Conferma non riuscita', payload.message || 'Operazione non riuscita.', 'error');
		}
	});
	cancelButton.addEventListener('click', async () => {
		if (!session || !confirm('Annullare il caricamento e rimuovere le foto temporanee?')) return;
		const payload = await post('/dashboard/photos/upload/session/cancel', { session_id: session.id });
		if (payload.success) {
			window.location.href = '/dashboard/photos/upload';
		} else {
			showSummary('Annullamento non riuscito', payload.message || 'Operazione non riuscita.', 'error');
		}
	});
	window.addEventListener('beforeunload', (event) => {
		if (active <= 0) return;
		event.preventDefault();
		event.returnValue = 'Alcune foto sono ancora in caricamento.';
	});

	if (session && Array.isArray(session.items)) {
		session.items.forEach(addServerItem);
		if (session.event_title && !eventSelected.textContent.trim()) {
			eventSelected.textContent = 'Evento selezionato: ' + session.event_title;
		}
		if (items.length > 0) {
			showSummary('Caricamento recuperato', session.completed_count + ' foto già salvate sul server.', 'success');
		}
	}
	refreshCounts();
})();
