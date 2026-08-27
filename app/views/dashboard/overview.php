<?php
$user = $data['user'] ?? ($user ?? []);
$adStats = $data['adStats'] ?? [];
$favoritesSummary = $data['favoritesSummary'] ?? [];
$agendaCounts = $data['agendaCounts'] ?? [];
$ciVadoEvents = $data['ciVadoEvents'] ?? [];
$cosplayPortfolioCount = (int) ($data['cosplayPortfolioCount'] ?? 0);
$avatar = $user['avatar'] ?? '/assets/img/default_avatar.png';
$displayName = $user['username'] ?? $user['first_name'] ?? 'Cosplayer';
$featureFlags = (new \App\Services\SiteFeatureFlagService())->getEnabledMap();
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

		<?php if (!empty($featureFlags['enable_advertising'])): ?>
			<a href="/dashboard/ads" class="rounded-2xl bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-green-700">
				<span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-900">
					<i class="fa-solid fa-bullhorn"></i>
				</span>
				<h2 class="mt-4 text-xl font-bold text-gray-950">Advertising</h2>
				<p class="mt-2 text-sm leading-relaxed text-gray-700">Gestisci campagne, banner e monitora la tua visibilità sponsorizzata.</p>
			</a>
		<?php endif; ?>

		<?php if (!empty($featureFlags['enable_favorites'])): ?>
			<a href="/dashboard/favorites" class="rounded-2xl bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-green-700 md:col-span-2 xl:col-span-1">
				<span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-900">
					<i class="fa-solid fa-bookmark"></i>
				</span>
				<h2 class="mt-4 text-xl font-bold text-gray-950">Preferiti</h2>
				<p class="mt-2 text-sm leading-relaxed text-gray-700">Ritrova eventi, guest, articoli e location salvati.</p>
			</a>
		<?php endif; ?>

		<?php if (!empty($featureFlags['enable_cosplay_portfolio'])): ?>
			<a href="/dashboard/cosplay" class="rounded-2xl bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-green-700">
				<span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-fuchsia-100 text-fuchsia-900">
					<i class="fa-solid fa-mask"></i>
				</span>
				<h2 class="mt-4 text-xl font-bold text-gray-950">Portfolio cosplay</h2>
				<p class="mt-2 text-sm leading-relaxed text-gray-700">Gestisci i cosplay salvati e scegli cosa porterai ai prossimi eventi.</p>
				<p class="mt-4 text-sm font-semibold text-fuchsia-900"><?php echo $cosplayPortfolioCount; ?> cosplay salvati</p>
			</a>
		<?php endif; ?>
	</div>

	<?php if (!empty($featureFlags['enable_advertising'])): ?>
	<section class="rounded-2xl bg-white p-5 shadow-sm md:p-8" aria-labelledby="ads-overview-title">
		<div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-green-900">Advertising</p>
				<h2 id="ads-overview-title" class="mt-2 text-2xl font-bold text-gray-950">La tua visibilità sponsorizzata</h2>
				<p class="mt-2 text-gray-700">Tieni sotto controllo campagne attive, banner caricati e performance commerciali.</p>
			</div>
			<div class="flex flex-wrap gap-3">
				<a href="/dashboard/ads/campaigns" class="inline-flex rounded-lg bg-green-700 px-4 py-2 text-sm font-bold text-white hover:bg-green-800">Campagne</a>
				<a href="/dashboard/ads/banners" class="inline-flex rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-800 hover:bg-gray-50">Banner</a>
				<a href="/dashboard/ads/campaigns/create" class="inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">Compra spazio</a>
			</div>
		</div>

		<div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
			<div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
				<p class="text-sm font-semibold text-gray-500">Campagne</p>
				<p class="mt-2 text-3xl font-bold text-gray-900"><?php echo (int)($adStats['campaigns'] ?? 0); ?></p>
			</div>
			<div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
				<p class="text-sm font-semibold text-gray-500">Campagne attive</p>
				<p class="mt-2 text-3xl font-bold text-gray-900"><?php echo (int)($adStats['active_campaigns'] ?? 0); ?></p>
			</div>
			<div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
				<p class="text-sm font-semibold text-gray-500">Impression</p>
				<p class="mt-2 text-3xl font-bold text-gray-900"><?php echo number_format((int)($adStats['impressions'] ?? 0), 0, ',', '.'); ?></p>
			</div>
			<div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
				<p class="text-sm font-semibold text-gray-500">Click</p>
				<p class="mt-2 text-3xl font-bold text-gray-900"><?php echo number_format((int)($adStats['clicks'] ?? 0), 0, ',', '.'); ?></p>
			</div>
		</div>

		<div class="mt-4 grid gap-4 md:grid-cols-3">
			<div class="rounded-xl border border-gray-200 bg-white p-4">
				<p class="text-sm font-semibold text-gray-500">CTR medio</p>
				<p class="mt-2 text-2xl font-bold text-gray-900"><?php echo number_format((float)($adStats['ctr'] ?? 0), 2, ',', '.'); ?>%</p>
			</div>
			<div class="rounded-xl border border-gray-200 bg-white p-4">
				<p class="text-sm font-semibold text-gray-500">Spesa totale</p>
				<p class="mt-2 text-2xl font-bold text-gray-900"><?php echo number_format((float)($adStats['spend'] ?? 0), 2, ',', '.'); ?> €</p>
			</div>
			<div class="rounded-xl border border-gray-200 bg-white p-4">
				<p class="text-sm font-semibold text-gray-500">Stato</p>
				<p class="mt-2 text-2xl font-bold text-gray-900">Monitoraggio attivo</p>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php if (!empty($featureFlags['enable_favorites'])): ?>
	<section class="rounded-2xl bg-white p-5 shadow-sm md:p-8" aria-labelledby="favorites-overview-title">
		<div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-amber-800">Preferiti</p>
				<h2 id="favorites-overview-title" class="mt-2 text-2xl font-bold text-gray-950">I contenuti che ti sei salvato</h2>
				<p class="mt-2 text-gray-700">Una zona rapida per tornare su eventi, guest, articoli e location importanti per te.</p>
			</div>
			<div class="flex flex-wrap gap-3">
				<a href="/dashboard/favorites" class="inline-flex rounded-lg bg-amber-700 px-4 py-2 text-sm font-bold text-white hover:bg-amber-800">Apri preferiti</a>
			</div>
		</div>

		<div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
			<div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
				<p class="text-sm font-semibold text-amber-900">Totale</p>
				<p class="mt-2 text-3xl font-bold text-amber-950"><?php echo (int)($favoritesSummary['total'] ?? 0); ?></p>
			</div>
			<div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
				<p class="text-sm font-semibold text-amber-900">Eventi</p>
				<p class="mt-2 text-3xl font-bold text-amber-950"><?php echo (int)($favoritesSummary['event'] ?? 0); ?></p>
			</div>
			<div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
				<p class="text-sm font-semibold text-amber-900">Guest</p>
				<p class="mt-2 text-3xl font-bold text-amber-950"><?php echo (int)($favoritesSummary['guest'] ?? 0); ?></p>
			</div>
			<div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
				<p class="text-sm font-semibold text-amber-900">Articoli</p>
				<p class="mt-2 text-3xl font-bold text-amber-950"><?php echo (int)($favoritesSummary['blog_post'] ?? 0); ?></p>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php if (!empty($featureFlags['enable_personal_agenda'])): ?>
	<section class="rounded-2xl bg-white p-5 shadow-sm md:p-8" aria-labelledby="agenda-overview-title">
		<div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-blue-800">Agenda personale</p>
				<h2 id="agenda-overview-title" class="mt-2 text-2xl font-bold text-gray-950">Gli eventi dove hai detto "ci vado"</h2>
				<p class="mt-2 text-gray-700">Qui trovi i prossimi appuntamenti che hai già segnato nella tua agenda personale.</p>
			</div>
			<div class="flex flex-wrap gap-3">
				<a href="/dashboard/events" class="inline-flex rounded-lg bg-blue-700 px-4 py-2 text-sm font-bold text-white hover:bg-blue-800">Apri agenda</a>
			</div>
		</div>

		<div class="mt-6 grid gap-4 md:grid-cols-3">
			<div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
				<p class="text-sm font-semibold text-blue-900">Mi interessa</p>
				<p class="mt-2 text-3xl font-bold text-blue-950"><?php echo (int)($agendaCounts['mi_interessa'] ?? 0); ?></p>
			</div>
			<div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
				<p class="text-sm font-semibold text-blue-900">Ci vado</p>
				<p class="mt-2 text-3xl font-bold text-blue-950"><?php echo (int)($agendaCounts['ci_vado'] ?? 0); ?></p>
			</div>
			<div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
				<p class="text-sm font-semibold text-blue-900">Forse vado</p>
				<p class="mt-2 text-3xl font-bold text-blue-950"><?php echo (int)($agendaCounts['forse_vado'] ?? 0); ?></p>
			</div>
		</div>

		<div class="mt-6">
			<h3 class="text-lg font-bold text-gray-950">Prossimi "ci vado"</h3>
			<?php if (empty($ciVadoEvents)): ?>
				<div class="mt-3 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-5 text-gray-600">
					Nessun evento segnato come “ci vado” al momento.
				</div>
			<?php else: ?>
				<div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
					<?php foreach ($ciVadoEvents as $event): ?>
						<a href="/eventi-cosplay/<?php echo htmlspecialchars($event['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="rounded-xl border border-gray-200 bg-gray-50 p-4 transition hover:bg-blue-50">
							<p class="text-sm font-semibold text-blue-900"><?php echo htmlspecialchars($event['data_inizio'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
							<h4 class="mt-2 text-lg font-bold text-gray-950"><?php echo htmlspecialchars($event['titolo'] ?? 'Evento', ENT_QUOTES, 'UTF-8'); ?></h4>
							<p class="mt-1 text-sm text-gray-700">
								<?php echo htmlspecialchars(trim(($event['comune_nome'] ?? '') . (!empty($event['provincia_nome']) ? ' · ' . $event['provincia_nome'] : '') . (!empty($event['regione_nome']) ? ' · ' . $event['regione_nome'] : '')), ENT_QUOTES, 'UTF-8'); ?>
							</p>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

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
