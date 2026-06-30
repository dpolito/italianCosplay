<?php
$user = $data['user'] ?? ($user ?? []);
$currentCover = $user['profile_cover'] ?? '/public_assets/images/default_cover.png';
$avatar = $user['avatar'] ?? '/assets/img/default_avatar.png';
$displayName = $user['username'] ?? $user['first_name'] ?? 'Cosplayer';
$posX = $user['cover_position_x'] ?? 50;
$posY = $user['cover_position_y'] ?? 50;
$completionSteps = 1
	+ (!empty($user['avatar']) ? 1 : 0)
	+ (!empty($user['bio']) ? 1 : 0)
	+ (!empty($user['website']) ? 1 : 0)
	+ (!empty($user['comune_id']) ? 1 : 0);
$completionPercent = min(100, (int) round(($completionSteps / 5) * 100));
?>

<section class="mx-auto max-w-6xl space-y-6" aria-labelledby="cover-page-title">
	<header class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
		<div class="grid gap-6 md:grid-cols-[minmax(0,1fr)_260px] md:items-center">
			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-green-900">Profilo community</p>
				<h1 id="cover-page-title" class="mt-2 text-3xl font-extrabold leading-tight text-gray-950 md:text-4xl">
					Aggiorna la tua cover
				</h1>
				<p class="mt-3 max-w-2xl text-base leading-relaxed text-gray-700">
					Scegli un'immagine di copertina che racconti il tuo stile cosplay, il tuo fandom o la tua presenza nella community.
				</p>
			</div>

			<aside class="rounded-xl border border-green-100 bg-green-50 p-4" aria-label="Completamento profilo">
				<div class="flex items-center justify-between gap-3">
					<p class="text-sm font-bold text-green-950">Profilo completato</p>
					<p class="text-lg font-extrabold text-green-950"><?php echo $completionPercent; ?>%</p>
				</div>
				<div class="mt-3 h-3 overflow-hidden rounded-full bg-white">
					<div class="h-full rounded-full bg-green-800" style="width: <?php echo $completionPercent; ?>%"></div>
				</div>
				<p class="mt-3 text-sm text-gray-700">
					Cover, avatar e bio rendono il profilo più riconoscibile.
				</p>
			</aside>
		</div>
	</header>

	<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
		<form id="cover-form" class="rounded-2xl bg-white p-5 shadow-sm md:p-8" enctype="multipart/form-data">
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
			<div class="mb-6">
				<h2 class="text-xl font-bold text-gray-950">Immagine di copertina</h2>
				<p class="mt-2 text-sm leading-relaxed text-gray-600">
					Usa un'immagine orizzontale e luminosa. Dopo la scelta puoi regolare la posizione prima di salvare.
				</p>
			</div>

			<input type="file" id="cover-input" name="cover" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only">

			<div
				id="cover-dropzone"
				class="relative min-h-[220px] cursor-pointer overflow-hidden rounded-2xl border-2 border-dashed border-gray-300 bg-gray-100 shadow-sm transition hover:border-green-800 focus-within:ring-2 focus-within:ring-green-700 md:min-h-[320px]"
				role="button"
				tabindex="0"
				aria-label="Scegli o trascina una nuova immagine di copertina"
			>
				<img
					id="cover-preview"
					src="<?php echo htmlspecialchars($currentCover, ENT_QUOTES, 'UTF-8'); ?>"
					alt="Anteprima cover profilo"
					class="absolute inset-0 h-full w-full object-cover"
					style="object-position: <?php echo (int)$posX; ?>% <?php echo (int)$posY; ?>%;"
					draggable="false"
				>
				<div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-5 text-white">
					<p class="text-lg font-extrabold">Tocca per cambiare cover</p>
					<p class="mt-1 text-sm text-white/90">Regola posizione orizzontale e verticale prima di salvare.</p>
				</div>
			</div>

			<div class="mt-5 grid gap-5 md:grid-cols-2">
				<div>
					<label for="cover-x" class="mb-2 block text-sm font-bold text-gray-900">Posizione orizzontale</label>
					<input type="range" id="cover-x" min="0" max="100" value="<?php echo (int)$posX; ?>" class="w-full accent-green-800">
				</div>

				<div>
					<label for="cover-y" class="mb-2 block text-sm font-bold text-gray-900">Posizione verticale</label>
					<input type="range" id="cover-y" min="0" max="100" value="<?php echo (int)$posY; ?>" class="w-full accent-green-800">
				</div>
			</div>

			<div id="cover-status" class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700" role="status" aria-live="polite">
				<div class="flex items-start gap-3">
					<span id="cover-status-icon" class="mt-0.5 inline-flex h-6 w-6 flex-none items-center justify-center rounded-full bg-gray-200 text-gray-700">
						<i class="fa-solid fa-circle-info text-xs" aria-hidden="true"></i>
					</span>
					<span id="cover-status-text">Nessuna modifica salvata in questa sessione.</span>
				</div>
			</div>

			<div class="mt-6 flex flex-col gap-3 sm:flex-row">
				<button type="button" id="save-cover" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg bg-green-800 px-5 py-3 font-bold text-white shadow transition hover:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2">
					<i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
					Salva cover
				</button>

				<button type="button" id="choose-cover" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg border border-gray-300 px-5 py-3 font-bold text-gray-800 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2">
					<i class="fa-solid fa-image" aria-hidden="true"></i>
					Cambia immagine
				</button>
			</div>
		</form>

		<aside class="space-y-6">
			<section class="rounded-2xl bg-white p-5 shadow-sm" aria-labelledby="cover-preview-title">
				<h2 id="cover-preview-title" class="text-xl font-bold text-gray-950">Anteprima profilo</h2>
				<div class="mt-5 overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
					<div class="relative h-28">
						<img id="cover-sidebar-preview" src="<?php echo htmlspecialchars($currentCover, ENT_QUOTES, 'UTF-8'); ?>" alt="Anteprima cover compatta" class="absolute inset-0 h-full w-full object-cover" style="object-position: <?php echo (int)$posX; ?>% <?php echo (int)$posY; ?>%;">
					</div>
					<div class="-mt-8 px-5 pb-5 text-center">
						<img src="<?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar di <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>" class="mx-auto h-20 w-20 rounded-full border-4 border-white object-cover shadow-md">
						<p class="mt-3 text-lg font-extrabold text-gray-950"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></p>
						<p class="text-sm text-gray-600">Membro ItalianCosplay</p>
					</div>
				</div>
			</section>

			<section class="rounded-2xl bg-white p-5 shadow-sm" aria-labelledby="cover-tips-title">
				<h2 id="cover-tips-title" class="text-xl font-bold text-gray-950">Consigli rapidi</h2>
				<ul class="mt-4 space-y-3 text-sm leading-relaxed text-gray-700">
					<li class="flex gap-3"><i class="fa-solid fa-check mt-1 text-green-800" aria-hidden="true"></i><span>Scegli immagini orizzontali: funzionano meglio da mobile e desktop.</span></li>
					<li class="flex gap-3"><i class="fa-solid fa-check mt-1 text-green-800" aria-hidden="true"></i><span>Evita testo importante vicino ai bordi: potrebbe essere tagliato.</span></li>
					<li class="flex gap-3"><i class="fa-solid fa-check mt-1 text-green-800" aria-hidden="true"></i><span>Usa una cover coerente con il tuo cosplay, fotografia o fandom.</span></li>
				</ul>
			</section>
		</aside>
	</div>
</section>

<script>
	document.addEventListener('DOMContentLoaded', () => {
		const input = document.getElementById('cover-input');
		const drop = document.getElementById('cover-dropzone');
		const img = document.getElementById('cover-preview');
		const sidebarImg = document.getElementById('cover-sidebar-preview');
		const x = document.getElementById('cover-x');
		const y = document.getElementById('cover-y');
		const status = document.getElementById('cover-status');
		const statusText = document.getElementById('cover-status-text');
		const statusIcon = document.getElementById('cover-status-icon');
		const chooseButton = document.getElementById('choose-cover');
		const saveButton = document.getElementById('save-cover');
		const csrfToken = <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>;

		function setStatus(type, message) {
			statusText.textContent = message;
			status.className = 'mt-5 rounded-xl border p-4 text-sm';
			statusIcon.className = 'mt-0.5 inline-flex h-6 w-6 flex-none items-center justify-center rounded-full';

			if (type === 'success') {
				status.classList.add('border-green-200', 'bg-green-50', 'text-green-900');
				statusIcon.classList.add('bg-green-100', 'text-green-800');
				statusIcon.innerHTML = '<i class="fa-solid fa-check text-xs" aria-hidden="true"></i>';
			} else if (type === 'error') {
				status.classList.add('border-red-200', 'bg-red-50', 'text-red-800');
				statusIcon.classList.add('bg-red-100', 'text-red-700');
				statusIcon.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-xs" aria-hidden="true"></i>';
			} else {
				status.classList.add('border-gray-200', 'bg-gray-50', 'text-gray-700');
				statusIcon.classList.add('bg-gray-200', 'text-gray-700');
				statusIcon.innerHTML = '<i class="fa-solid fa-circle-info text-xs" aria-hidden="true"></i>';
			}
		}

		function openPicker() {
			input.click();
		}

		function updatePreview() {
			const position = `${x.value}% ${y.value}%`;
			img.style.objectPosition = position;
			sidebarImg.style.objectPosition = position;
		}

		input.addEventListener('change', (event) => {
			const file = event.target.files[0];
			if (!file) return;

			const url = URL.createObjectURL(file);
			img.src = url;
			sidebarImg.src = url;
			setStatus('info', 'Anteprima aggiornata. Regola la posizione e salva quando ti piace.');
		});

		chooseButton.addEventListener('click', openPicker);
		drop.addEventListener('click', openPicker);
		drop.addEventListener('keydown', (event) => {
			if (event.key === 'Enter' || event.key === ' ') {
				event.preventDefault();
				openPicker();
			}
		});

		x.addEventListener('input', updatePreview);
		y.addEventListener('input', updatePreview);

		saveButton.addEventListener('click', () => {
			const file = input.files[0];
			const formData = new FormData();

			if (file) {
				formData.append('cover', file);
			}

			formData.append('cover_position_x', x.value);
			formData.append('cover_position_y', y.value);
			formData.append('csrf_token', csrfToken);
			setStatus('info', 'Salvataggio cover in corso...');

			fetch('/dashboard/updateCover', {
				method: 'POST',
				body: formData,
				headers: {
					'Accept': 'application/json'
				}
			})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						if (data.coverUrl) {
							img.src = data.coverUrl;
							sidebarImg.src = data.coverUrl;
						}
						setStatus('success', data.message || 'Cover aggiornata con successo.');
					} else {
						setStatus('error', data.message || 'Errore durante il salvataggio.');
					}
				})
				.catch(() => {
					setStatus('error', 'Errore di rete durante il salvataggio.');
				});
		});
	});
</script>
