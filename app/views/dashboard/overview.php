<?php
$user = $data['user'] ?? ($user ?? []);
$avatar = $user['avatar'] ?? '/assets/img/default_avatar.png';
$displayName = $user['username'] ?? $user['first_name'] ?? 'Cosplayer';
$completionSteps = 1
	+ (!empty($user['avatar']) ? 1 : 0)
	+ (!empty($user['bio']) ? 1 : 0)
	+ (!empty($user['website']) ? 1 : 0)
	+ (!empty($user['comune_id']) ? 1 : 0);
$completionPercent = min(100, (int) round(($completionSteps / 5) * 100));
?>

<section class="mx-auto max-w-6xl space-y-6" aria-labelledby="overview-page-title">
	<header class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
		<div class="grid gap-6 md:grid-cols-[minmax(0,1fr)_260px] md:items-center">
			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-green-900">Dashboard community</p>
				<h1 id="overview-page-title" class="mt-2 text-3xl font-extrabold leading-tight text-gray-950 md:text-4xl">
					Ciao, <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>
				</h1>
				<p class="mt-3 max-w-2xl text-base leading-relaxed text-gray-700">
					Gestisci il tuo profilo ItalianCosplay, aggiorna avatar, cover, informazioni pubbliche e impostazioni di visibilità.
				</p>
			</div>

			<aside class="rounded-xl border border-green-100 bg-green-50 p-4" aria-label="Riepilogo profilo">
				<div class="flex items-center gap-4">
					<img src="<?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar di <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>" class="h-16 w-16 rounded-full object-cover shadow-md" width="64" height="64">
					<div>
						<p class="font-bold text-green-950"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></p>
						<p class="mt-1 text-sm text-gray-700">Membro ItalianCosplay</p>
					</div>
				</div>
				<div class="mt-4 flex items-center justify-between gap-3">
					<p class="text-sm font-bold text-green-950">Profilo completato</p>
					<p class="text-lg font-extrabold text-green-950"><?php echo $completionPercent; ?>%</p>
				</div>
				<div class="mt-3 h-3 overflow-hidden rounded-full bg-white">
					<div class="h-full rounded-full bg-green-800" style="width: <?php echo $completionPercent; ?>%"></div>
				</div>
			</aside>
		</div>
	</header>

	<div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
		<a href="/dashboard/profile" class="rounded-2xl bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-green-700">
			<span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-900">
				<i class="fa-solid fa-user"></i>
			</span>
			<h2 class="mt-4 text-xl font-bold text-gray-950">Profilo</h2>
			<p class="mt-2 text-sm leading-relaxed text-gray-700">Aggiorna bio, social, comune e informazioni pubbliche.</p>
		</a>

		<a href="/dashboard/avatar" class="rounded-2xl bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-green-700">
			<span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-900">
				<i class="fa-solid fa-camera"></i>
			</span>
			<h2 class="mt-4 text-xl font-bold text-gray-950">Avatar</h2>
			<p class="mt-2 text-sm leading-relaxed text-gray-700">Carica e centra la foto profilo che ti rappresenta nella community.</p>
		</a>

		<a href="/dashboard/cover" class="rounded-2xl bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-green-700">
			<span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-900">
				<i class="fa-solid fa-image"></i>
			</span>
			<h2 class="mt-4 text-xl font-bold text-gray-950">Cover</h2>
			<p class="mt-2 text-sm leading-relaxed text-gray-700">Personalizza l’immagine di copertina del tuo profilo pubblico.</p>
		</a>

		<a href="/dashboard/settings" class="rounded-2xl bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-green-700">
			<span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-900">
				<i class="fa-solid fa-sliders"></i>
			</span>
			<h2 class="mt-4 text-xl font-bold text-gray-950">Privacy</h2>
			<p class="mt-2 text-sm leading-relaxed text-gray-700">Scegli cosa mostrare agli altri utenti nel tuo profilo.</p>
		</a>

		<a href="/dashboard/change_password" class="rounded-2xl bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-green-700">
			<span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-900">
				<i class="fa-solid fa-lock"></i>
			</span>
			<h2 class="mt-4 text-xl font-bold text-gray-950">Password</h2>
			<p class="mt-2 text-sm leading-relaxed text-gray-700">Aggiorna le credenziali per mantenere sicuro l’account.</p>
		</a>
	</div>

	<section class="rounded-2xl bg-white p-5 shadow-sm md:p-8" aria-labelledby="next-steps-title">
		<h2 id="next-steps-title" class="text-xl font-bold text-gray-950">Prossimi passi consigliati</h2>
		<div class="mt-5 grid gap-4 md:grid-cols-3">
			<div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
				<p class="font-bold text-gray-950">1. Scegli un avatar</p>
				<p class="mt-2 text-sm text-gray-700">Una foto chiara rende il profilo più riconoscibile.</p>
			</div>
			<div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
				<p class="font-bold text-gray-950">2. Scrivi una bio</p>
				<p class="mt-2 text-sm text-gray-700">Racconta cosplay, fandom o ruolo nella community.</p>
			</div>
			<div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
				<p class="font-bold text-gray-950">3. Aggiungi social</p>
				<p class="mt-2 text-sm text-gray-700">Aiuta altri utenti a trovarti e riconoscerti.</p>
			</div>
		</div>
	</section>
</section>
