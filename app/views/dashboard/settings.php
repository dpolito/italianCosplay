<?php
$user = $data['user'] ?? ($user ?? []);
$settings = json_decode($user['profile_settings'] ?? '{}', true);
$marketingOptIn = !empty($user['marketing_opted_in']);
$avatar = $user['avatar'] ?? '/assets/img/default_avatar.png';
$displayName = $user['username'] ?? $user['first_name'] ?? 'Cosplayer';

function checked($settings, $key)
{
	return !empty($settings[$key]) ? 'checked' : '';
}
?>

<section class="mx-auto max-w-6xl space-y-6" aria-labelledby="settings-page-title">
	<header class="rounded-2xl bg-gradient-to-br from-green-900 via-green-800 to-emerald-700 p-5 text-white shadow-sm md:p-8">
		<div class="grid gap-6 md:grid-cols-[minmax(0,1fr)_280px] md:items-center">
			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-emerald-100">Impostazioni profilo</p>
				<h1 id="settings-page-title" class="mt-2 text-3xl font-extrabold leading-tight text-white md:text-4xl">
					Privacy e visibilità
				</h1>
				<p class="mt-3 max-w-2xl text-base leading-relaxed text-emerald-50/90">
					Scegli quali informazioni mostrare alla community ItalianCosplay nel tuo profilo pubblico.
				</p>
			</div>

			<aside class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm" aria-label="Anteprima utente">
				<div class="flex items-center gap-4">
					<img src="<?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar di <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>" class="h-16 w-16 rounded-full border-2 border-white object-cover shadow-md" width="64" height="64">
					<div>
						<p class="font-bold text-white"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></p>
						<p class="mt-1 text-sm text-emerald-50/80">Controllo profilo pubblico</p>
					</div>
				</div>
			</aside>
		</div>
	</header>

	<?php if (!empty($data['success'])): ?>
		<div class="rounded-xl border border-green-200 bg-green-50 p-4 text-green-900" role="status">
			<?php echo htmlspecialchars($data['success'], ENT_QUOTES, 'UTF-8'); ?>
		</div>
	<?php endif; ?>

	<?php if (!empty($data['error'])): ?>
		<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800" role="alert">
			<?php echo htmlspecialchars($data['error'], ENT_QUOTES, 'UTF-8'); ?>
		</div>
	<?php endif; ?>

	<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
		<form method="POST" action="/dashboard/updatesettings" class="space-y-6">
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

			<section class="rounded-2xl bg-white p-5 shadow-sm md:p-8" aria-labelledby="public-profile-title">
				<h2 id="public-profile-title" class="text-xl font-bold text-gray-950">Profilo pubblico</h2>
				<p class="mt-2 text-sm leading-relaxed text-gray-600">
					Attiva solo le informazioni che vuoi rendere visibili agli altri utenti.
				</p>

				<div class="mt-6 space-y-4">
					<label class="flex min-h-16 cursor-pointer items-center justify-between gap-4 rounded-xl border border-gray-200 p-4 transition hover:bg-green-50">
						<span>
							<span class="block font-bold text-gray-950">Mostra Nome e Cognome</span>
							<span class="mt-1 block text-sm text-gray-600">Sconsigliato: il tuo nome e cognome dovrebbero restare privati</span>
						</span>
						<input type="checkbox" name="settings[show_nome]" value="1" class="h-6 w-6 flex-none accent-green-800" <?php echo checked($settings, 'show_nome'); ?>>
					</label>
					<label class="flex min-h-16 cursor-pointer items-center justify-between gap-4 rounded-xl border border-gray-200 p-4 transition hover:bg-green-50">
						<span>
							<span class="block font-bold text-gray-950">Mostra email</span>
							<span class="mt-1 block text-sm text-gray-600">Sconsigliato: la tua email di solito dovrebbe restare privata.</span>
						</span>
						<input type="checkbox" name="settings[show_email]" value="1" class="h-6 w-6 flex-none accent-green-800" <?php echo checked($settings, 'show_email'); ?>>
					</label>

					<label class="flex min-h-16 cursor-pointer items-center justify-between gap-4 rounded-xl border border-gray-200 p-4 transition hover:bg-green-50">
						<span>
							<span class="block font-bold text-gray-950">Mostra bio</span>
							<span class="mt-1 block text-sm text-gray-600">La descrizione pubblica aiuta la community a conoscerti.</span>
						</span>
						<input type="checkbox" name="settings[show_bio]" value="1" class="h-6 w-6 flex-none accent-green-800" <?php echo checked($settings, 'show_bio'); ?>>
					</label>

					<label class="flex min-h-16 cursor-pointer items-center justify-between gap-4 rounded-xl border border-gray-200 p-4 transition hover:bg-green-50">
						<span>
							<span class="block font-bold text-gray-950">Mostra comune</span>
							<span class="mt-1 block text-sm text-gray-600">Utile per trovare cosplayer, fotografi ed eventi vicini.</span>
						</span>
						<input type="checkbox" name="settings[show_comune]" value="1" class="h-6 w-6 flex-none accent-green-800" <?php echo checked($settings, 'show_comune'); ?>>
					</label>

					<label class="flex min-h-16 cursor-pointer items-center justify-between gap-4 rounded-xl border border-amber-200 bg-amber-50 p-4 transition hover:bg-amber-100">
						<span>
							<span class="block font-bold text-gray-950">Newsletter / marketing</span>
							<span class="mt-1 block text-sm text-gray-600">Ricevi comunicazioni facoltative, offerte e aggiornamenti promozionali.</span>
						</span>
						<input type="checkbox" name="settings[newsletter_opt_in]" value="1" class="h-6 w-6 flex-none accent-amber-600" <?php echo $marketingOptIn ? 'checked' : ''; ?>>
					</label>
				</div>
			</section>

			<section class="rounded-2xl bg-white p-5 shadow-sm md:p-8" aria-labelledby="social-visibility-title">
				<h2 id="social-visibility-title" class="text-xl font-bold text-gray-950">Social network</h2>
				<p class="mt-2 text-sm leading-relaxed text-gray-600">
					Scegli quali canali mostrare sul profilo pubblico.
				</p>

				<div class="mt-6 grid gap-4 md:grid-cols-2">
					<?php
					$socialItems = [
						'show_instagram' => ['label' => 'Instagram', 'icon' => 'fa-brands fa-instagram'],
						'show_facebook' => ['label' => 'Facebook', 'icon' => 'fa-brands fa-facebook-f'],
						'show_tiktok' => ['label' => 'TikTok', 'icon' => 'fa-brands fa-tiktok'],
						'show_youtube' => ['label' => 'YouTube', 'icon' => 'fa-brands fa-youtube'],
					];
					foreach ($socialItems as $key => $item): ?>
						<label class="flex min-h-16 cursor-pointer items-center justify-between gap-4 rounded-xl border border-gray-200 p-4 transition hover:bg-green-50">
							<span class="flex items-center gap-3">
								<span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-green-100 text-green-900">
									<i class="<?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
								</span>
								<span class="font-bold text-gray-950"><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
							</span>
							<input type="checkbox" name="settings[<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>]" value="1" class="h-6 w-6 flex-none accent-green-800" <?php echo checked($settings, $key); ?>>
						</label>
					<?php endforeach; ?>
				</div>
			</section>

			<div class="sticky bottom-4 z-40">
				<button type="submit" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-green-800 px-6 py-4 text-lg font-bold text-white shadow-lg transition hover:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2">
					<i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
					Salva impostazioni
				</button>
			</div>
		</form>

		<aside class="space-y-6">
			<section class="rounded-2xl bg-white p-5 shadow-sm" aria-labelledby="privacy-tips-title">
				<h2 id="privacy-tips-title" class="text-xl font-bold text-gray-950">Consigli privacy</h2>
				<ul class="mt-4 space-y-4 text-sm leading-relaxed text-gray-700">
					<li class="flex gap-3">
						<i class="fa-solid fa-check mt-1 text-green-800" aria-hidden="true"></i>
						<span>Mostra solo i dati che vuoi condividere pubblicamente.</span>
					</li>
					<li class="flex gap-3">
						<i class="fa-solid fa-check mt-1 text-green-800" aria-hidden="true"></i>
						<span>I social rendono più facile riconoscerti nella community.</span>
					</li>
					<li class="flex gap-3">
						<i class="fa-solid fa-check mt-1 text-green-800" aria-hidden="true"></i>
						<span>Puoi modificare queste preferenze in qualsiasi momento.</span>
					</li>
				</ul>
			</section>

			<section class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm" aria-labelledby="delete-account-title">
				<h2 id="delete-account-title" class="text-xl font-bold text-red-950">Cancella account</h2>
				<p class="mt-2 text-sm leading-relaxed text-red-900">
					Se confermi, il tuo account verrà anonimizzato. I collegamenti interni resteranno validi, ma non potrai più accedere con queste credenziali.
				</p>

				<div class="mt-4 rounded-xl bg-white p-4 text-sm leading-relaxed text-gray-700">
					<p class="font-bold text-gray-950">Cosa succede:</p>
					<ul class="mt-2 space-y-2">
						<li>username ed email vengono sostituiti con valori anonimi</li>
						<li>password e token vengono azzerati</li>
						<li>avatar, cover e dati pubblici vengono rimossi dal profilo</li>
					</ul>
				</div>

				<form method="POST" action="/dashboard/delete-account" class="mt-5">
					<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
					<button type="submit" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-red-700 px-6 py-4 text-lg font-bold text-white shadow-lg transition hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2" onclick="return confirm('Vuoi davvero anonimizzare il tuo account? Questa azione non si può annullare.');">
						<i class="fa-solid fa-user-slash" aria-hidden="true"></i>
						Annulla e anonimizza account
					</button>
				</form>
			</section>
		</aside>
	</div>
</section>
