<?php
// app/views/dashboard/profile.php

$user = $data['user'] ?? ($user ?? []);
$csrf = $_SESSION['csrf_token'] ?? '';
$social = json_decode($user['social'] ?? '{}', true);

$avatar = $user['avatar'] ?? '/assets/img/default_avatar.png';
$displayName = $user['username'] ?? 'Cosplayer';

$completionSteps = 1
	+ (!empty($user['avatar']) ? 1 : 0)
	+ (!empty($user['bio']) ? 1 : 0)
	+ (!empty($user['website']) ? 1 : 0)
	+ (!empty($user['comune_id']) ? 1 : 0);

$completionPercent = min(100, (int) round(($completionSteps / 5) * 100));
?>

<section class="mx-auto max-w-6xl space-y-6" aria-labelledby="profile-page-title">

	<!-- HEADER -->
	<header class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
		<div class="grid gap-6 md:grid-cols-[minmax(0,1fr)_260px] md:items-center">

			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-green-900">
					Profilo community
				</p>

				<h1 id="profile-page-title"
				    class="mt-2 text-3xl font-extrabold leading-tight text-gray-950 md:text-4xl">
					Il tuo profilo
				</h1>

				<p class="mt-3 max-w-2xl text-base leading-relaxed text-gray-700">
					Completa le informazioni del tuo profilo per renderti più riconoscibile
					all’interno della community cosplay.
				</p>
			</div>

			<aside class="rounded-xl border border-green-100 bg-green-50 p-4" aria-label="Completamento profilo">
				<div class="flex items-center justify-between gap-3">
					<p class="text-sm font-bold text-green-950">Profilo completato</p>
					<p class="text-lg font-extrabold text-green-950">
						<?php echo $completionPercent; ?>%
					</p>
				</div>

				<div class="mt-3 h-3 overflow-hidden rounded-full bg-white">
					<div class="h-full rounded-full bg-green-800"
					     style="width: <?php echo $completionPercent; ?>%">
					</div>
				</div>

				<p class="mt-3 text-sm text-gray-700">
					Completa bio, social e località per migliorare il tuo profilo.
				</p>
			</aside>

		</div>
	</header>

	<!-- ALERT -->
	<?php if (!empty($data['success'])): ?>
		<div class="rounded-xl border border-green-200 bg-green-50 p-4 text-green-900" role="status">
			<?php echo htmlspecialchars($data['success']); ?>
		</div>
	<?php endif; ?>

	<?php if (!empty($data['error'])): ?>
		<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800" role="alert">
			<?php echo htmlspecialchars($data['error']); ?>
		</div>
	<?php endif; ?>

	<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">

		<!-- FORM -->
		<form method="POST"
		      action="/dashboard/profile/update"
		      class="space-y-6">

			<input type="hidden"
			       name="csrf_token"
			       value="<?php echo htmlspecialchars($csrf); ?>">

			<!-- ACCOUNT -->
			<section class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
				<h2 class="text-xl font-bold text-gray-950">
					Informazioni account
				</h2>

				<div class="mt-6 space-y-5">

					<div>
						<label class="mb-2 block text-sm font-bold text-gray-900">
							Username
						</label>

						<div class="flex items-center gap-3">
							<input type="text"
							       readonly
							       value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>"
							       class="w-full rounded-xl border border-gray-200 bg-gray-100 px-4 py-3 text-gray-700">

							<button type="button"
							        class="copy-username rounded-lg border border-gray-300 px-4 py-3 text-sm font-bold hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2"
							        data-copy="<?php echo htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
							        aria-label="Copia username">
								Copia
							</button>
						</div>
					</div>

					<div>
						<label class="mb-2 block text-sm font-bold text-gray-900">
							Email
						</label>

						<input type="email"
						       readonly
						       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
						       class="w-full rounded-xl border border-gray-200 bg-gray-100 px-4 py-3 text-gray-700">

						<p class="mt-2 text-sm text-gray-500">
							Il cambio email sarà disponibile prossimamente.
						</p>
					</div>

				</div>
			</section>

			<!-- DATI PERSONALI -->
			<section class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
				<h2 class="text-xl font-bold text-gray-950">
					Informazioni personali
				</h2>

				<div class="mt-6 grid gap-5 md:grid-cols-2">

					<div>
						<label class="mb-2 block text-sm font-bold text-gray-900">
							Nome
						</label>

						<input type="text"
						       name="first_name"
						       value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>"
						       class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-700">
					</div>

					<div>
						<label class="mb-2 block text-sm font-bold text-gray-900">
							Cognome
						</label>

						<input type="text"
						       name="last_name"
						       value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>"
						       class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-700">
					</div>

				</div>

				<div class="mt-5 relative">
					<label class="mb-2 block text-sm font-bold text-gray-900">
						Comune
					</label>

					<input type="text"
					       id="comune"
					       name="comune"
					       autocomplete="off"
					       value="<?php echo htmlspecialchars($user['comune_name'] ?? ''); ?>"
					       placeholder="Cerca il tuo comune..."
					       class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-700">

					<input type="hidden"
					       name="comune_id"
					       id="comune_id"
					       value="<?php echo $user['comune_id'] ?? ''; ?>">

					<ul id="comune-list"
					    class="absolute z-50 mt-2 hidden max-h-60 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl">
					</ul>
				</div>

				<div class="mt-5">
					<label class="mb-2 block text-sm font-bold text-gray-900">
						Sito web
					</label>

					<input type="url"
					       name="website"
					       value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>"
					       placeholder="https://..."
					       class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-700">
				</div>

			</section>

			<!-- SOCIAL -->
			<section class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
				<h2 class="text-xl font-bold text-gray-950">
					Social community
				</h2>

				<div class="mt-6 grid gap-5 md:grid-cols-2">

					<div>
						<label class="mb-2 block text-sm font-bold text-gray-900">
							Instagram
						</label>

						<input type="text"
						       name="social[instagram]"
						       value="<?php echo htmlspecialchars($social['instagram'] ?? ''); ?>"
						       placeholder="@username"
						       class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-700">
					</div>

					<div>
						<label class="mb-2 block text-sm font-bold text-gray-900">
							Facebook
						</label>

						<input type="text"
						       name="social[facebook]"
						       value="<?php echo htmlspecialchars($social['facebook'] ?? ''); ?>"
						       placeholder="Pagina o profilo Facebook"
						       class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-700">
					</div>

					<div>
						<label class="mb-2 block text-sm font-bold text-gray-900">
							TikTok
						</label>

						<input type="text"
						       name="social[tiktok]"
						       value="<?php echo htmlspecialchars($social['tiktok'] ?? ''); ?>"
						       placeholder="@username"
						       class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-700">
					</div>

					<div>
						<label class="mb-2 block text-sm font-bold text-gray-900">
							YouTube
						</label>

						<input type="text"
						       name="social[youtube]"
						       value="<?php echo htmlspecialchars($social['youtube'] ?? ''); ?>"
						       placeholder="Canale YouTube"
						       class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-700">
					</div>

				</div>
			</section>

			<!-- BIO -->
			<section class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
				<h2 class="text-xl font-bold text-gray-950">
					Bio community
				</h2>

				<div class="mt-5">
					<label class="mb-2 block text-sm font-bold text-gray-900">
						Raccontati alla community
					</label>

					<textarea name="bio"
					          id="bio"
					          rows="5"
					          maxlength="500"
					          class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-700"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>

					<div class="mt-2 flex items-center justify-between">
						<p class="text-sm text-gray-500">
							Parla del tuo cosplay, fandom o personaggi preferiti.
						</p>

						<p class="text-sm text-gray-500">
							<span id="bio-counter">0</span>/500
						</p>
					</div>
				</div>
			</section>

			<!-- SAVE -->
			<div class="sticky bottom-4 z-40">
				<button type="submit"
				        class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-green-800 px-6 py-4 text-lg font-bold text-white shadow-lg transition hover:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2">
						<i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
					Salva modifiche
				</button>
			</div>

		</form>

		<!-- SIDEBAR -->
		<aside class="space-y-6">

			<!-- PREVIEW -->
			<section class="rounded-2xl bg-white p-5 shadow-sm">
				<h2 class="text-xl font-bold text-gray-950">
					Anteprima profilo
				</h2>

				<div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-5 text-center">

					<img src="<?php echo htmlspecialchars($avatar); ?>"
					     alt="Avatar profilo"
					     class="mx-auto h-24 w-24 rounded-full object-cover shadow-md">

					<p class="mt-4 text-lg font-extrabold text-gray-950">
						<?php echo htmlspecialchars($displayName); ?>
					</p>

					<p class="mt-1 text-sm text-gray-600">
						Membro ItalianCosplay
					</p>

					<a href="/dashboard/avatar"
					   class="mt-5 inline-flex w-full items-center justify-center rounded-lg border border-green-800 px-4 py-3 font-bold text-green-900 transition hover:bg-green-50">
						Cambia avatar
					</a>

				</div>
			</section>

			<!-- TIPS -->
			<section class="rounded-2xl bg-white p-5 shadow-sm">
				<h2 class="text-xl font-bold text-gray-950">
					Consigli rapidi
				</h2>

				<ul class="mt-4 space-y-4 text-sm leading-relaxed text-gray-700">

					<li class="flex gap-3">
						<i class="fa-solid fa-check mt-1 text-green-800" aria-hidden="true"></i>
						<span>
							Completa i social per essere riconosciuto più facilmente.
						</span>
					</li>

					<li class="flex gap-3">
						<i class="fa-solid fa-check mt-1 text-green-800" aria-hidden="true"></i>
						<span>
							Una bio personale rende il profilo più interessante.
						</span>
					</li>

					<li class="flex gap-3">
						<i class="fa-solid fa-check mt-1 text-green-800" aria-hidden="true"></i>
						<span>
							Aggiungi il comune per trovare cosplayer vicini.
						</span>
					</li>

				</ul>

			</section>

		</aside>

	</div>

</section>

<script>
	document.addEventListener('DOMContentLoaded', () => {

		/* BIO COUNTER */
		const bio = document.getElementById('bio');
		const counter = document.getElementById('bio-counter');

		function updateCounter() {
			counter.textContent = bio.value.length;
		}

		updateCounter();
		bio.addEventListener('input', updateCounter);

		document.querySelectorAll('.copy-username').forEach(button => {
			button.addEventListener('click', async () => {
				const value = button.dataset.copy || '';
				if (!value) return;

				try {
					await navigator.clipboard.writeText(value);
					button.textContent = 'Copiato';
					window.setTimeout(() => button.textContent = 'Copia', 1600);
				} catch (error) {
					window.prompt('Copia username:', value);
				}
			});
		});

		/* COMUNE SEARCH */
		const input = document.getElementById('comune');
		const list = document.getElementById('comune-list');
		const hiddenId = document.getElementById('comune_id');

		let timer;

		input.addEventListener('input', () => {

			const query = input.value.trim();

			hiddenId.value = '';
			list.innerHTML = '';
			list.classList.add('hidden');

			if (query.length < 2) {
				return;
			}

			clearTimeout(timer);

			timer = setTimeout(() => {

				fetch(`/api/search/${encodeURIComponent(query)}`)
					.then(res => res.json())
					.then(data => {

						if (data.length === 0) {
							return;
						}

						list.innerHTML = '';

						data.forEach(item => {

							const li = document.createElement('li');

							li.className =
								'cursor-pointer border-b border-gray-100 px-4 py-3 hover:bg-green-50';

							const name = document.createElement('div');
							name.className = 'font-semibold text-gray-900';
							name.textContent = item.comune;

							const meta = document.createElement('div');
							meta.className = 'text-sm text-gray-500';
							meta.textContent = `${item.provincia}, ${item.regione}`;

							li.appendChild(name);
							li.appendChild(meta);

							li.addEventListener('click', () => {

								input.value = item.comune;
								hiddenId.value = item.id;

								list.innerHTML = '';
								list.classList.add('hidden');

							});

							list.appendChild(li);

						});

						list.classList.remove('hidden');

					});

			}, 300);

		});

		document.addEventListener('click', (e) => {

			if (!input.contains(e.target) && !list.contains(e.target)) {

				list.innerHTML = '';
				list.classList.add('hidden');

			}

		});

	});
</script>
