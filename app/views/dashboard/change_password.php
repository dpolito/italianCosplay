
<?php
// app/views/dashboard/change_password.php

$user = $data['user'] ?? ($user ?? []);
$avatar = $user['avatar'] ?? '/assets/img/default_avatar.png';
$displayName = $user['username'] ?? 'Cosplayer';
?>

<section class="mx-auto max-w-6xl space-y-6" aria-labelledby="password-page-title">

	<!-- HEADER -->
	<header class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
		<div class="grid gap-6 md:grid-cols-[minmax(0,1fr)_260px] md:items-center">

			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-green-900">
					Sicurezza account
				</p>

				<h1 id="password-page-title"
				    class="mt-2 text-3xl font-extrabold leading-tight text-gray-950 md:text-4xl">
					Cambia password
				</h1>

				<p class="mt-3 max-w-2xl text-base leading-relaxed text-gray-700">
					Aggiorna la password del tuo account ItalianCosplay per mantenere il profilo sicuro.
				</p>
			</div>

			<aside class="rounded-xl border border-green-100 bg-green-50 p-4" aria-label="Riepilogo account">
				<div class="flex items-center gap-4">

					<img src="<?php echo htmlspecialchars($avatar); ?>"
					     alt="Avatar utente"
					     class="h-16 w-16 rounded-full object-cover shadow-md">

					<div>
						<p class="font-bold text-green-950">
							<?php echo htmlspecialchars($displayName); ?>
						</p>

						<p class="mt-1 text-sm text-gray-700">
							Account protetto ItalianCosplay
						</p>
					</div>

				</div>
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
		<form action=""
		      method="POST"
		      class="space-y-6">

			<input type="hidden"
			       name="csrf_token"
			       value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">

			<section class="rounded-2xl bg-white p-5 shadow-sm md:p-8">

				<h2 class="text-xl font-bold text-gray-950">
					Aggiorna password
				</h2>

				<p class="mt-2 text-sm leading-relaxed text-gray-600">
					Usa una password lunga e sicura. Ti consigliamo almeno 8 caratteri con lettere,
					numeri e simboli.
				</p>

				<div class="mt-6 space-y-5">

					<!-- CURRENT -->
					<div>
						<label for="current_password"
						       class="mb-2 block text-sm font-bold text-gray-900">
							Password attuale
						</label>

						<div class="relative">
							<input type="password"
							       name="current_password"
							       id="current_password"
							       required
							       autocomplete="current-password"
							       class="w-full rounded-xl border border-gray-200 px-4 py-3 pr-12 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-700">

							<button type="button"
							        class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-800"
							        data-target="current_password"
							        aria-label="Mostra o nascondi password attuale">
								<i class="fa-solid fa-eye" aria-hidden="true"></i>
							</button>
						</div>
					</div>

					<!-- NEW -->
					<div>
						<label for="new_password"
						       class="mb-2 block text-sm font-bold text-gray-900">
							Nuova password
						</label>

						<div class="relative">
							<input type="password"
							       name="new_password"
							       id="new_password"
							       required
							       autocomplete="new-password"
							       class="w-full rounded-xl border border-gray-200 px-4 py-3 pr-12 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-700">

							<button type="button"
							        class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-800"
							        data-target="new_password"
							        aria-label="Mostra o nascondi nuova password">
								<i class="fa-solid fa-eye" aria-hidden="true"></i>
							</button>
						</div>

						<!-- PASSWORD STRENGTH -->
						<div class="mt-4">

							<div class="mb-2 flex items-center justify-between">
								<p class="text-sm font-semibold text-gray-700">
									Sicurezza password
								</p>

								<p id="password-strength-text"
								   class="text-sm font-bold text-gray-500">
									Debole
								</p>
							</div>

							<div class="h-3 overflow-hidden rounded-full bg-gray-100">
								<div id="password-strength-bar"
								     class="h-full w-0 rounded-full transition-all duration-300">
								</div>
							</div>

						</div>

					</div>

					<!-- CONFIRM -->
					<div>
						<label for="confirm_password"
						       class="mb-2 block text-sm font-bold text-gray-900">
							Conferma nuova password
						</label>

						<div class="relative">
							<input type="password"
							       name="confirm_password"
							       id="confirm_password"
							       required
							       autocomplete="new-password"
							       class="w-full rounded-xl border border-gray-200 px-4 py-3 pr-12 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-700">

							<button type="button"
							        class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-800"
							        data-target="confirm_password"
							        aria-label="Mostra o nascondi conferma password">
								<i class="fa-solid fa-eye" aria-hidden="true"></i>
							</button>
						</div>

						<p id="password-match"
						   class="mt-2 text-sm font-medium">
						</p>
					</div>

				</div>

			</section>

			<!-- SAVE -->
			<div class="sticky bottom-4 z-40">
				<button type="submit"
				        class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-green-800 px-6 py-4 text-lg font-bold text-white shadow-lg transition hover:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2">
					<i class="fa-solid fa-lock" aria-hidden="true"></i>
					Aggiorna password
				</button>
			</div>

		</form>

		<!-- SIDEBAR -->
		<aside class="space-y-6">

			<!-- SECURITY TIPS -->
			<section class="rounded-2xl bg-white p-5 shadow-sm">

				<h2 class="text-xl font-bold text-gray-950">
					Consigli sicurezza
				</h2>

				<ul class="mt-4 space-y-4 text-sm leading-relaxed text-gray-700">

					<li class="flex gap-3">
						<i class="fa-solid fa-check mt-1 text-green-800" aria-hidden="true"></i>
						<span>
							Non usare la stessa password su altri siti.
						</span>
					</li>

					<li class="flex gap-3">
						<i class="fa-solid fa-check mt-1 text-green-800" aria-hidden="true"></i>
						<span>
							Usa almeno 8 caratteri con lettere e numeri.
						</span>
					</li>

					<li class="flex gap-3">
						<i class="fa-solid fa-check mt-1 text-green-800" aria-hidden="true"></i>
						<span>
							Evita password semplici come "123456".
						</span>
					</li>

				</ul>

			</section>

			<!-- ACCOUNT STATUS -->
			<section class="rounded-2xl bg-white p-5 shadow-sm">

				<h2 class="text-xl font-bold text-gray-950">
					Stato account
				</h2>

				<div class="mt-5 rounded-xl border border-green-100 bg-green-50 p-4">

					<div class="flex items-center gap-3">
						<span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-green-800 text-white">
							<i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
						</span>

						<div>
							<p class="font-bold text-green-950">
								Account protetto
							</p>

							<p class="text-sm text-gray-700">
								La sicurezza del profilo è attiva.
							</p>
						</div>
					</div>

				</div>

			</section>

		</aside>

	</div>

</section>

<script>
	document.addEventListener('DOMContentLoaded', () => {

		/* SHOW/HIDE PASSWORD */
		document.querySelectorAll('.toggle-password').forEach(button => {

			button.addEventListener('click', () => {

				const target = document.getElementById(button.dataset.target);

				if (target.type === 'password') {

					target.type = 'text';
					button.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';

				} else {

					target.type = 'password';
					button.innerHTML = '<i class="fa-solid fa-eye"></i>';

				}

			});

		});

		/* PASSWORD STRENGTH */
		const passwordInput = document.getElementById('new_password');
		const strengthBar = document.getElementById('password-strength-bar');
		const strengthText = document.getElementById('password-strength-text');

		passwordInput.addEventListener('input', () => {

			const value = passwordInput.value;
			let score = 0;

			if (value.length >= 8) score++;
			if (/[A-Z]/.test(value)) score++;
			if (/[0-9]/.test(value)) score++;
			if (/[^A-Za-z0-9]/.test(value)) score++;

			if (score <= 1) {

				strengthBar.className = 'h-full w-1/4 rounded-full bg-red-500 transition-all duration-300';
				strengthText.textContent = 'Debole';
				strengthText.className = 'text-sm font-bold text-red-600';

			} else if (score === 2) {

				strengthBar.className = 'h-full w-2/4 rounded-full bg-yellow-500 transition-all duration-300';
				strengthText.textContent = 'Media';
				strengthText.className = 'text-sm font-bold text-yellow-600';

			} else if (score === 3) {

				strengthBar.className = 'h-full w-3/4 rounded-full bg-green-500 transition-all duration-300';
				strengthText.textContent = 'Buona';
				strengthText.className = 'text-sm font-bold text-green-600';

			} else {

				strengthBar.className = 'h-full w-full rounded-full bg-green-700 transition-all duration-300';
				strengthText.textContent = 'Molto sicura';
				strengthText.className = 'text-sm font-bold text-green-700';

			}

		});

		/* PASSWORD MATCH */
		const confirmPassword = document.getElementById('confirm_password');
		const matchText = document.getElementById('password-match');

		function checkPasswords() {

			if (!confirmPassword.value) {

				matchText.textContent = '';
				return;

			}

			if (passwordInput.value === confirmPassword.value) {

				matchText.textContent = 'Le password coincidono';
				matchText.className = 'mt-2 text-sm font-semibold text-green-700';

			} else {

				matchText.textContent = 'Le password non coincidono';
				matchText.className = 'mt-2 text-sm font-semibold text-red-600';

			}

		}

		passwordInput.addEventListener('input', checkPasswords);
		confirmPassword.addEventListener('input', checkPasswords);

	});
</script>
