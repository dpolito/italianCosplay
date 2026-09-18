<?php
// Questo file è un frammento di HTML e deve essere incluso in un layout admin.
// Non contiene i tag <html>, <head>, <body> completi.
$social = is_array($data['user']['social'] ?? null)
	? $data['user']['social']
	: json_decode($data['user']['social'] ?? '{}', true);
$profileSettings = is_array($data['user']['profile_settings'] ?? null)
	? $data['user']['profile_settings']
	: json_decode($data['user']['profile_settings'] ?? '{}', true);
$social = is_array($social) ? $social : [];
$profileSettings = is_array($profileSettings) ? $profileSettings : [];
?>

<div class="container mx-auto p-6">
	<div class="mb-6">
		<a href="/admin/users" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
			</svg>
			Torna a Gestione Utenti
		</a>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Modifica Utente: <?php echo htmlspecialchars($data['user']['username'] ?? ''); ?></h1>

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
		<form action="/admin/users/update/<?php echo htmlspecialchars($data['user']['id'] ?? ''); ?>" method="POST" enctype="multipart/form-data" data-ajax-submit="true" data-success-redirect="/admin/users">
			<input type="hidden" name="id" value="<?php echo htmlspecialchars($data['user']['id'] ?? ''); ?>">
			<!-- Campo CSRF Token -->
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">

			<div class="grid gap-4 md:grid-cols-2">
				<div class="mb-4">
					<label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username:</label>
					<input type="text" id="username" name="username" value="<?php echo htmlspecialchars($data['user']['username'] ?? ''); ?>" required class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500">
				</div>

				<div class="mb-4">
					<label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
					<input type="email" id="email" name="email" value="<?php echo htmlspecialchars($data['user']['email'] ?? ''); ?>" required class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500">
				</div>

				<div class="mb-4">
					<label for="first_name" class="block text-gray-700 text-sm font-bold mb-2">Nome:</label>
					<input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($data['user']['first_name'] ?? ''); ?>" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500">
				</div>

				<div class="mb-4">
					<label for="last_name" class="block text-gray-700 text-sm font-bold mb-2">Cognome:</label>
					<input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($data['user']['last_name'] ?? ''); ?>" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500">
				</div>

				<div class="mb-4 md:col-span-2">
					<label for="website" class="block text-gray-700 text-sm font-bold mb-2">Sito web:</label>
					<input type="url" id="website" name="website" value="<?php echo htmlspecialchars($data['user']['website'] ?? ''); ?>" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500">
				</div>

				<div class="mb-4 md:col-span-2">
					<label for="bio" class="block text-gray-700 text-sm font-bold mb-2">Bio:</label>
					<textarea id="bio" name="bio" rows="5" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500"><?php echo htmlspecialchars($data['user']['bio'] ?? ''); ?></textarea>
				</div>

				<div class="mb-4">
					<label for="avatar" class="block text-gray-700 text-sm font-bold mb-2">Immagine profilo:</label>
					<input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700">
					<?php if (!empty($data['user']['avatar'])): ?>
						<img src="<?php echo htmlspecialchars($data['user']['avatar'], ENT_QUOTES, 'UTF-8'); ?>" alt="Immagine profilo" class="mt-3 h-20 w-20 rounded-full object-cover">
					<?php endif; ?>
				</div>

				<div class="mb-4">
					<label for="profile_cover" class="block text-gray-700 text-sm font-bold mb-2">Cover profilo:</label>
					<input type="file" id="profile_cover" name="profile_cover" accept="image/jpeg,image/png,image/gif,image/webp" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700">
					<?php if (!empty($data['user']['profile_cover'])): ?>
						<img src="<?php echo htmlspecialchars($data['user']['profile_cover'], ENT_QUOTES, 'UTF-8'); ?>" alt="Cover profilo" class="mt-3 h-20 w-full rounded-lg object-cover">
					<?php endif; ?>
				</div>

				<div class="relative mb-4">
					<label for="comune" class="block text-gray-700 text-sm font-bold mb-2">Comune:</label>
					<input
						type="search"
						id="comune"
						value="<?php echo htmlspecialchars((string) ($data['user']['comune_name'] ?? '')); ?>"
						placeholder="Cerca comune..."
						autocomplete="off"
						aria-autocomplete="list"
						aria-controls="comune-list"
						class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500"
					>
					<input type="hidden" id="comune_id" name="comune_id" value="<?php echo htmlspecialchars((string) ($data['user']['comune_id'] ?? '')); ?>">
					<ul id="comune-list" class="absolute z-20 mt-1 hidden max-h-60 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg" role="listbox"></ul>
					<p class="mt-1 text-xs text-gray-500">Digita almeno due caratteri e seleziona un comune dall’elenco.</p>
				</div>

				<div class="mb-4 flex items-end">
					<label class="inline-flex items-center gap-2 text-sm font-bold text-gray-700">
						<input type="checkbox" name="verified" value="1" <?php echo !empty($data['user']['verified']) ? 'checked' : ''; ?> class="rounded border-gray-300 text-green-600 focus:ring-green-500">
						Utente verificato
					</label>
				</div>

				<div class="md:col-span-2 rounded-lg border border-blue-200 bg-blue-50 p-4">
					<p class="text-sm font-bold text-blue-950">Consensi e dichiarazioni</p>
					<div class="mt-3 grid gap-3 md:grid-cols-2">
						<label class="inline-flex items-center gap-2 text-sm text-gray-700">
							<input type="checkbox" name="age_declared_adult" value="1" <?php echo !empty($data['user']['age_declared_adult']) ? 'checked' : ''; ?> class="rounded border-gray-300 text-green-600 focus:ring-green-500">
							Dichiara di avere almeno 18 anni
						</label>
						<label class="inline-flex items-center gap-2 text-sm text-gray-700">
							<input type="checkbox" name="marketing_opted_in" value="1" <?php echo !empty($data['user']['marketing_opted_in']) ? 'checked' : ''; ?> class="rounded border-gray-300 text-green-600 focus:ring-green-500">
							Newsletter e comunicazioni marketing
						</label>
					</div>
				</div>

				<div class="md:col-span-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
					<p class="text-sm font-bold text-gray-900">Social</p>
					<div class="mt-3 grid gap-3 md:grid-cols-2">
						<?php foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'tiktok' => 'TikTok', 'youtube' => 'YouTube'] as $network => $label): ?>
							<label class="block text-sm font-semibold text-gray-700">
								<?php echo $label; ?>
								<input type="text" name="social[<?php echo $network; ?>]" value="<?php echo htmlspecialchars($social[$network] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="255" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-700 focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500">
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="md:col-span-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
					<p class="text-sm font-bold text-gray-900">Visibilità nel profilo pubblico</p>
					<div class="mt-3 grid gap-3 sm:grid-cols-2">
						<?php foreach (['show_nome' => 'Nome e cognome', 'show_email' => 'Email', 'show_bio' => 'Bio', 'show_comune' => 'Comune', 'show_instagram' => 'Instagram', 'show_facebook' => 'Facebook', 'show_tiktok' => 'TikTok', 'show_youtube' => 'YouTube'] as $setting => $label): ?>
							<label class="inline-flex items-center gap-2 text-sm text-gray-700">
								<input type="checkbox" name="settings[<?php echo $setting; ?>]" value="1" <?php echo !empty($profileSettings[$setting]) ? 'checked' : ''; ?> class="rounded border-gray-300 text-green-600 focus:ring-green-500">
								<?php echo $label; ?>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="mb-4">
					<label for="password" class="block text-gray-700 text-sm font-bold mb-2">Nuova Password (lascia vuoto per non cambiare):</label>
					<input type="password" id="password" name="password" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500">
				</div>

				<div class="mb-6 md:col-span-2">
					<label for="role" class="block text-gray-700 text-sm font-bold mb-2">Ruolo:</label>
					<select id="role" name="role_id" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500">
						<option value="">Seleziona un tipo di ruolo</option>
						<?php if(!empty($data['roles'])):
							foreach($data['roles'] as $role):  ?>

								<option value="<?php echo htmlspecialchars($role['id']); ?>"
										<?php echo (isset($role['id']) && $role['id'] == ($data['user']['role_id'] ?? null)) ? 'selected' : ''; ?>>
									<?php echo htmlspecialchars($role['name']); ?>
								</option>
							<?php endforeach; endif; ?>
					</select>
				</div>
			</div>

			<div class="flex items-center justify-between">
				<button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
					Aggiorna Utente
				</button>
				<a href="/admin/users" class="inline-block align-baseline font-semibold text-green-600 hover:text-green-800">
					Annulla
				</a>
			</div>
		</form>
	</div>
</div>

<script>
(function () {
	const input = document.getElementById('comune');
	const list = document.getElementById('comune-list');
	const hiddenId = document.getElementById('comune_id');
	if (!input || !list || !hiddenId) return;

	let timer;
	let controller;

	const closeList = () => {
		list.innerHTML = '';
		list.classList.add('hidden');
	};

	const renderResults = (comuni) => {
		list.innerHTML = '';

		if (!comuni.length) {
			const empty = document.createElement('li');
			empty.className = 'px-4 py-3 text-sm text-gray-500';
			empty.textContent = 'Nessun comune trovato.';
			list.appendChild(empty);
			list.classList.remove('hidden');
			return;
		}

		comuni.forEach((comune) => {
			const option = document.createElement('li');
			option.className = 'cursor-pointer border-b border-gray-100 px-4 py-3 hover:bg-green-50';
			option.setAttribute('role', 'option');

			const name = document.createElement('div');
			name.className = 'font-semibold text-gray-900';
			name.textContent = comune.comune;

			const meta = document.createElement('div');
			meta.className = 'text-sm text-gray-500';
			meta.textContent = `${comune.provincia}, ${comune.regione}`;

			option.append(name, meta);
			option.addEventListener('click', () => {
				input.value = comune.comune;
				hiddenId.value = comune.id;
				closeList();
			});
			list.appendChild(option);
		});

		list.classList.remove('hidden');
	};

	input.addEventListener('input', () => {
		hiddenId.value = '';
		closeList();
		clearTimeout(timer);
		if (controller) controller.abort();

		const query = input.value.trim();
		if (query.length < 2) return;

		timer = setTimeout(async () => {
			controller = new AbortController();
			try {
				const response = await fetch(`/api/search/${encodeURIComponent(query)}`, {
					headers: { Accept: 'application/json' },
					signal: controller.signal
				});
				if (!response.ok) throw new Error('Comune non disponibile');
				renderResults(await response.json());
			} catch (error) {
				if (error.name !== 'AbortError') closeList();
			}
		}, 250);
	});

	input.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') closeList();
	});

	document.addEventListener('click', (event) => {
		if (!input.parentElement.contains(event.target)) closeList();
	});
})();
</script>
