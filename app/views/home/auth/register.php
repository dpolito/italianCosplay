<?php
$old = is_array($data['old'] ?? null) ? $data['old'] : [];
$errors = $data['errors'] ?? [];

if (is_string($errors)) {
	$errors = [$errors];
}
?>

<section class="mx-auto max-w-6xl px-4 py-10 md:py-14">
	<div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-start">
		<div class="rounded-3xl bg-gradient-to-br from-emerald-950 via-green-900 to-teal-900 p-8 text-white shadow-2xl md:p-10">
			<p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-200">ItalianCosplay account</p>
			<h1 class="mt-3 text-3xl font-black leading-tight md:text-5xl">Crea la tua agenda cosplay personale</h1>
			<p class="mt-4 max-w-xl text-base leading-7 text-emerald-50/90 md:text-lg">
				Segui eventi, regioni, province, comuni e articoli per ritrovare tutto più velocemente quando organizzi le prossime uscite cosplay.
			</p>

			<ul class="mt-8 space-y-3 text-sm md:text-base">
				<li class="flex gap-3"><span class="mt-1 h-2.5 w-2.5 rounded-full bg-emerald-300"></span>Segna gli eventi come "ci vado", "mi interessa" o "forse vado"</li>
				<li class="flex gap-3"><span class="mt-1 h-2.5 w-2.5 rounded-full bg-emerald-300"></span>Segui regioni, province e comuni per tornare subito alle tue zone</li>
				<li class="flex gap-3"><span class="mt-1 h-2.5 w-2.5 rounded-full bg-emerald-300"></span>Salva articoli, ospiti e contenuti utili nella tua area personale</li>
			</ul>

			<div class="mt-8 rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur">
				<p class="text-sm font-semibold text-emerald-100">Già registrato?</p>
				<a href="/login" class="mt-2 inline-flex items-center gap-2 font-bold text-white underline decoration-emerald-200 decoration-2 underline-offset-4">
					Vai al login
				</a>
			</div>
		</div>

		<div class="rounded-3xl bg-white p-6 shadow-xl md:p-8">
			<div class="mb-6">
				<h2 class="text-3xl font-black text-gray-950">Inizia a seguire gli eventi</h2>
				<p class="mt-2 text-sm leading-6 text-gray-600">
					Bastano pochi dati. Ti servirà una email valida per confermare l'account e proteggere la tua area personale.
				</p>
			</div>

			<?php if (!empty($data['success'])): ?>
				<div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
					<?php echo htmlspecialchars($data['success'], ENT_QUOTES, 'UTF-8'); ?>
				</div>
			<?php endif; ?>

			<?php if (!empty($errors)): ?>
				<div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
					<p class="font-semibold">Controlla questi campi:</p>
					<ul class="mt-2 list-disc space-y-1 pl-5">
						<?php foreach ($errors as $error): ?>
							<li><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<form action="/register" method="POST" class="space-y-5">
				<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($data['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

				<div>
					<label for="username" class="block text-sm font-semibold text-gray-800">Username</label>
					<p class="mt-1 text-xs text-gray-500">Scegli un nome pubblico, senza spazi, per la tua area ItalianCosplay.</p>
					<input type="text" name="username" id="username" value="<?php echo htmlspecialchars((string) ($old['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="username" maxlength="50"
					       class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-200">
				</div>

				<div>
					<label for="email" class="block text-sm font-semibold text-gray-800">Email</label>
					<p class="mt-1 text-xs text-gray-500">Serve per attivare l'account e ricevere solo comunicazioni legate alla registrazione, salvo tuo consenso newsletter.</p>
					<input type="email" name="email" id="email" value="<?php echo htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="email" inputmode="email"
					       class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-200">
				</div>

				<div class="relative">
					<label for="password" class="block text-sm font-semibold text-gray-800">Password</label>
					<p class="mt-1 text-xs text-gray-500">Minimo 8 caratteri. Usa una password unica e sicura.</p>
					<input type="password" name="password" id="password" required autocomplete="new-password" minlength="8"
					       class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 pr-20 text-sm shadow-sm focus:border-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-200">
					<button type="button" id="toggle-password" class="absolute right-3 top-[3.1rem] rounded-full px-3 py-1 text-sm font-semibold text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-emerald-300">
						Mostra
					</button>
				</div>

				<div class="relative">
					<label for="password_confirm" class="block text-sm font-semibold text-gray-800">Conferma password</label>
					<p class="mt-1 text-xs text-gray-500">Serve a evitare errori di digitazione.</p>
					<input type="password" name="password_confirm" id="password_confirm" required autocomplete="new-password" minlength="8"
					       class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 pr-20 text-sm shadow-sm focus:border-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-200">
					<button type="button" id="toggle-password-confirm" class="absolute right-3 top-[3.1rem] rounded-full px-3 py-1 text-sm font-semibold text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-emerald-300">
						Mostra
					</button>
				</div>

				<div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
					<label class="flex cursor-pointer items-start gap-3 text-sm text-gray-700" for="privacy_accept">
						<input type="checkbox" id="privacy_accept" name="privacy_accept" value="1" required
						       class="mt-1 h-4 w-4 rounded border-gray-300 text-emerald-800 focus:ring-emerald-300">
						<span>
							Dichiaro di aver letto e accetto l'
							<a href="/privacy" target="_blank" rel="noopener noreferrer" class="font-semibold text-emerald-800 hover:text-emerald-900 hover:underline">
								informativa privacy
							</a>
							e acconsento al trattamento dei dati necessari alla registrazione.
						</span>
					</label>
					<p class="mt-2 text-xs leading-5 text-gray-500">
						Il consenso è richiesto per creare l'account e gestire la tua registrazione in sicurezza.
					</p>
				</div>

				<div class="rounded-2xl border border-blue-200 bg-blue-50 p-4">
					<label class="flex cursor-pointer items-start gap-3 text-sm text-gray-700" for="age_declaration">
						<input type="checkbox" id="age_declaration" name="age_declaration" value="1" required
						       <?php echo !empty($old['age_declaration']) ? 'checked' : ''; ?>
						       class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-800 focus:ring-blue-300">
						<span>
							Dichiaro di avere almeno 18 anni e di poter creare un account su ItalianCosplay.
						</span>
					</label>
					<p class="mt-2 text-xs leading-5 text-gray-500">
						Questa dichiarazione è separata dal consenso privacy ed è obbligatoria per completare la registrazione.
					</p>
				</div>

				<div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
					<label class="flex cursor-pointer items-start gap-3 text-sm text-gray-700" for="newsletter_opt_in">
						<input type="checkbox" id="newsletter_opt_in" name="newsletter_opt_in" value="1"
						       <?php echo !empty($old['newsletter_opt_in']) ? 'checked' : ''; ?>
						       class="mt-1 h-4 w-4 rounded border-gray-300 text-amber-700 focus:ring-amber-300">
						<span>
							Voglio ricevere in futuro email con nuovi eventi cosplay, contenuti utili e aggiornamenti editoriali da ItalianCosplay.
						</span>
					</label>
					<p class="mt-2 text-xs leading-5 text-gray-500">
						Opzione facoltativa: non influenza la creazione dell'account e non è necessaria per registrarti.
					</p>
				</div>

				<button type="submit" class="w-full rounded-xl bg-emerald-800 px-4 py-3.5 text-base font-bold text-white shadow-lg transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:ring-offset-2">
					Crea la mia agenda cosplay
				</button>

				<p class="text-center text-sm text-gray-600">
					Hai già un account?
					<a href="/login" class="font-semibold text-emerald-800 hover:text-emerald-900 hover:underline">Accedi qui</a>
				</p>
			</form>
		</div>
	</div>
</section>

<script>
	document.addEventListener('DOMContentLoaded', function () {
		const toggleVisibility = (inputId, buttonId) => {
			const input = document.getElementById(inputId);
			const button = document.getElementById(buttonId);

			if (!input || !button) {
				return;
			}

			button.addEventListener('click', function () {
				const type = input.type === 'password' ? 'text' : 'password';
				input.type = type;
				button.textContent = type === 'password' ? 'Mostra' : 'Nascondi';
			});
		};

		toggleVisibility('password', 'toggle-password');
		toggleVisibility('password_confirm', 'toggle-password-confirm');
	});
</script>
