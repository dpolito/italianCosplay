(function () {
	const root = document.querySelector('[data-photo-manage]');
	if (!root) return;
	const csrf = root.querySelector('[data-csrf]').value;
	const selectedCount = root.querySelector('[data-selected-count]');
	const userSearch = root.querySelector('[data-user-search]');
	const userId = root.querySelector('[data-user-id]');
	const userResults = root.querySelector('[data-user-results]');
	const cosplaySelect = root.querySelector('[data-cosplay-id]');
	const displayName = root.querySelector('[data-display-name]');
	const instagram = root.querySelector('[data-instagram]');
	const feedback = root.querySelector('[data-manage-feedback]');

	function selectedIds() {
		return Array.from(root.querySelectorAll('[data-photo-checkbox]:checked')).map((node) => parseInt(node.value, 10));
	}

	function refresh() {
		selectedCount.textContent = selectedIds().length.toString();
	}

	function showFeedback(message, type) {
		if (!feedback) return;
		const classes = {
			success: 'border-green-200 bg-green-50 text-green-900',
			error: 'border-red-200 bg-red-50 text-red-900',
			info: 'border-blue-200 bg-blue-50 text-blue-900'
		};
		feedback.className = 'mb-4 rounded-xl border p-4 text-sm font-semibold ' + (classes[type] || classes.info);
		feedback.textContent = message;
		feedback.classList.remove('hidden');
	}

	function selectedCosplayLabel() {
		const option = cosplaySelect.options[cosplaySelect.selectedIndex];
		return option && option.value ? option.textContent : '';
	}

	function associationLabel(row) {
		const name = row.username ? '@' + row.username : (row.display_name || 'Cosplayer');
		const cosplay = row.custom_name || row.name_full || row.name_native || '';
		return cosplay ? name + ' · ' + cosplay : name;
	}

	function renderAssociationsForPhoto(photoId, associations) {
		const card = root.querySelector('[data-photo-card][data-photo-id="' + photoId + '"]');
		if (!card) return;
		const box = card.querySelector('[data-associations]');
		box.innerHTML = '';
		if (!associations || associations.length === 0) {
			const empty = document.createElement('span');
			empty.dataset.emptyAssociation = 'true';
			empty.className = 'text-xs text-gray-500';
			empty.textContent = 'Nessuna associazione';
			box.appendChild(empty);
			return;
		}
		associations.forEach((association) => {
			const badge = document.createElement('span');
			badge.dataset.associationId = association.id;
			badge.className = 'inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800';
			const text = document.createElement('span');
			text.textContent = associationLabel(association);
			const remove = document.createElement('button');
			remove.type = 'button';
			remove.dataset.removeAssociation = association.id;
			remove.className = 'ml-1 rounded-full px-1 text-green-900 hover:bg-green-200';
			remove.setAttribute('aria-label', 'Rimuovi associazione');
			remove.textContent = '×';
			badge.appendChild(text);
			badge.appendChild(remove);
			box.appendChild(badge);
		});
	}

	function renderAssociations(grouped) {
		Object.entries(grouped || {}).forEach(([photoId, associations]) => renderAssociationsForPhoto(photoId, associations));
	}

	root.querySelectorAll('[data-photo-checkbox]').forEach((checkbox) => checkbox.addEventListener('change', refresh));

	let searchTimer = null;
	userSearch.addEventListener('input', () => {
		clearTimeout(searchTimer);
		const query = userSearch.value.trim();
		userId.value = '';
		if (query.length < 2) {
			userResults.classList.add('hidden');
			return;
		}
		searchTimer = setTimeout(async () => {
			const response = await fetch('/api/photos/users/search?q=' + encodeURIComponent(query), { headers: { Accept: 'application/json' } });
			const payload = await response.json();
			userResults.innerHTML = '';
			(payload.users || []).forEach((user) => {
				const button = document.createElement('button');
				button.type = 'button';
				button.className = 'block w-full px-3 py-2 text-left hover:bg-green-50';
				button.textContent = '@' + user.username;
				button.addEventListener('click', async () => {
					userId.value = user.id;
					userSearch.value = '@' + user.username;
					userResults.classList.add('hidden');
					await loadCosplays(user.id);
				});
				userResults.appendChild(button);
			});
			userResults.classList.toggle('hidden', !userResults.children.length);
		}, 220);
	});

	async function loadCosplays(id) {
		const response = await fetch('/api/photos/users/' + id + '/cosplays', { headers: { Accept: 'application/json' } });
		const payload = await response.json();
		cosplaySelect.innerHTML = '<option value="">Cosplay non specificato</option>';
		(payload.cosplays || []).forEach((item) => {
			const option = document.createElement('option');
			option.value = item.id;
			option.textContent = item.custom_name || item.name_full || item.name_native || 'Cosplay';
			cosplaySelect.appendChild(option);
		});
	}

	async function post(action, data) {
		const formData = new FormData();
		formData.append('csrf_token', csrf);
		Object.entries(data).forEach(([key, value]) => formData.append(key, value));
		const response = await fetch(action, { method: 'POST', headers: { Accept: 'application/json' }, body: formData });
		const payload = await response.json();
		if (!payload.success) showFeedback(payload.message || 'Operazione non riuscita.', 'error');
		return payload;
	}

	root.querySelector('[data-associate]').addEventListener('click', async () => {
		const ids = selectedIds();
		if (!ids.length) {
			showFeedback('Seleziona almeno una foto prima di associare un cosplayer.', 'error');
			return;
		}
		if (!userId.value && !displayName.value.trim()) {
			showFeedback('Cerca un utente registrato oppure inserisci un nome non registrato.', 'error');
			return;
		}
		const payload = await post('/dashboard/photos/associate', {
			photo_ids: JSON.stringify(ids),
			user_id: userId.value,
			cosplay_id: cosplaySelect.value,
			display_name: displayName.value,
			instagram_username: instagram.value
		});
		if (payload.success) {
			renderAssociations(payload.associations || {});
			showFeedback(payload.message || 'Associazione salvata.', 'success');
		}
	});

	root.querySelector('[data-self]').addEventListener('click', async () => {
		const ids = selectedIds();
		if (!ids.length) {
			showFeedback('Seleziona almeno una foto prima di usare Associami.', 'error');
			return;
		}
		const payload = await post('/dashboard/photos/associate', {
			photo_ids: JSON.stringify(ids),
			user_id: root.dataset.currentUserId || '',
			cosplay_id: cosplaySelect.value,
			display_name: '',
			instagram_username: ''
		});
		if (payload.success) {
			renderAssociations(payload.associations || {});
			showFeedback(payload.message || 'Ti sei associato alle foto selezionate.', 'success');
		}
	});

	root.addEventListener('click', async (event) => {
		const button = event.target.closest('[data-remove-association]');
		if (!button) return;
		const payload = await post('/dashboard/photos/associations/remove', { association_id: button.dataset.removeAssociation });
		if (payload.success) {
			const card = button.closest('[data-photo-card]');
			button.closest('[data-association-id]')?.remove();
			if (card && !card.querySelector('[data-association-id]')) {
				const box = card.querySelector('[data-associations]');
				const empty = document.createElement('span');
				empty.dataset.emptyAssociation = 'true';
				empty.className = 'text-xs text-gray-500';
				empty.textContent = 'Nessuna associazione';
				box.appendChild(empty);
			}
			showFeedback(payload.message || 'Associazione rimossa.', 'success');
		}
	});

	root.querySelector('[data-delete]').addEventListener('click', async () => {
		const ids = selectedIds();
		if (!ids.length) {
			showFeedback('Seleziona almeno una foto da eliminare.', 'error');
			return;
		}
		if (!confirm('Eliminare le foto selezionate?')) return;
		let deleted = 0;
		for (const id of ids) {
			const payload = await post('/dashboard/photos/delete', { photo_id: id });
			if (payload.success) {
				const checkbox = root.querySelector('[data-photo-checkbox][value="' + id + '"]');
				checkbox.closest('[data-photo-card]').remove();
				deleted++;
			}
		}
		if (deleted > 0) {
			showFeedback(deleted + ' foto eliminate.', 'success');
		}
		refresh();
	});

	refresh();
})();
