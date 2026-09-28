<?php
$user = $data['user'] ?? ($user ?? []);
$adStats = $data['adStats'] ?? [];
$favoritesSummary = $data['favoritesSummary'] ?? [];
$agendaCounts = $data['agendaCounts'] ?? [];
$cosplayPortfolioCount = (int) ($data['cosplayPortfolioCount'] ?? 0);
$organizations = $data['organizations'] ?? [];
$eventMasterClaims = $data['eventMasterClaims'] ?? [];
$organizationInvitations = $data['organizationInvitations'] ?? [];
$claimStatuses = ['pending' => 'In attesa', 'approved' => 'Approvata', 'rejected' => 'Rifiutata', 'cancelled' => 'Annullata'];
$avatar = $user['avatar'] ?? '/public_assets/images/default_avatar.png';
$displayName = $user['username'] ?? $user['first_name'] ?? 'Cosplayer';
$publicProfileUrl = rtrim(URL_ROOT_SITE, '/') . '/u/' . rawurlencode((string) ($user['username'] ?? ''));
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

	<?php if ($organizationInvitations): ?>
	<section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm md:p-6" aria-labelledby="organization-invitations-title">
		<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-amber-800">Inviti ricevuti</p>
				<h2 id="organization-invitations-title" class="mt-1 text-2xl font-extrabold text-amber-950">Hai inviti da esaminare</h2>
				<p class="mt-2 text-sm text-amber-900">Puoi accettare o rifiutare la partecipazione a un’organizzazione.</p>
			</div>
			<a href="/dashboard/organization-invitations" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-amber-800 px-4 py-3 text-sm font-bold text-white hover:bg-amber-900">Visualizza inviti (<?= count($organizationInvitations) ?>)</a>
		</div>
	</section>
	<?php endif; ?>

	<?php if ($organizations || $eventMasterClaims): ?>
	<section class="grid gap-6 xl:grid-cols-2" aria-label="Gestione organizzazioni ed eventi master">
		<div class="rounded-2xl bg-white p-5 shadow-sm md:p-6">
				<div class="flex items-center justify-between gap-3"><div><p class="text-sm font-bold uppercase tracking-wide text-green-900">Organizzazioni</p><h2 class="mt-1 text-2xl font-extrabold text-gray-950">Le mie organizzazioni</h2></div><a href="/dashboard/organizations" class="text-sm font-bold text-green-900 hover:underline">Gestisci</a></div>
			<?php if ($organizations): ?><div class="mt-5 space-y-3"><?php foreach ($organizations as $organization): ?><a href="/dashboard/organizations/<?= (int) $organization['id'] ?>" class="block rounded-xl border border-gray-200 p-4 hover:border-green-300"><div class="flex flex-wrap items-center justify-between gap-2"><h3 class="font-bold text-gray-950"><?= htmlspecialchars((string) $organization['name'], ENT_QUOTES, 'UTF-8') ?></h3><span class="text-sm font-semibold text-gray-600"><?= htmlspecialchars((string) $organization['role'], ENT_QUOTES, 'UTF-8') ?></span></div><p class="mt-2 text-sm text-gray-600"><?= (int) $organization['master_count'] ?> master · <?= (int) $organization['member_count'] ?> membri</p></a><?php endforeach; ?></div><?php else: ?><p class="mt-5 text-sm text-gray-600">Non gestisci ancora nessuna organizzazione.</p><?php endif; ?>
		</div>
		<div class="rounded-2xl bg-white p-5 shadow-sm md:p-6">
			<div class="flex items-center justify-between gap-3"><div><p class="text-sm font-bold uppercase tracking-wide text-amber-800">Riscatti</p><h2 class="mt-1 text-2xl font-extrabold text-gray-950">Le mie richieste</h2></div><span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-bold text-amber-900"><?= count($eventMasterClaims) ?></span></div>
			<?php if ($eventMasterClaims): ?><div class="mt-5 space-y-3"><?php foreach ($eventMasterClaims as $claim): ?><div class="rounded-xl border border-gray-200 p-4"><div class="flex flex-wrap items-center justify-between gap-2"><a href="/eventi-master/<?= rawurlencode((string) $claim['event_master_slug']) ?>" class="font-bold text-green-900 hover:underline"><?= htmlspecialchars((string) $claim['event_master_name'], ENT_QUOTES, 'UTF-8') ?></a><span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700"><?= htmlspecialchars($claimStatuses[(string) ($claim['status'] ?? '')] ?? (string) $claim['status'], ENT_QUOTES, 'UTF-8') ?></span></div><p class="mt-2 text-sm text-gray-600"><?= htmlspecialchars((string) $claim['organization_name'], ENT_QUOTES, 'UTF-8') ?></p><?php if (!empty($claim['review_notes'])): ?><p class="mt-2 text-sm text-gray-500">Nota dello staff: <?= htmlspecialchars((string) $claim['review_notes'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?></div><?php endforeach; ?></div><?php else: ?><p class="mt-5 text-sm text-gray-600">Non hai ancora inviato richieste di gestione.</p><?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<section class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm md:p-6" aria-labelledby="dashboard-public-profile-title">
		<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-emerald-800">Profilo pubblico</p>
				<h2 id="dashboard-public-profile-title" class="mt-1 text-xl font-extrabold text-emerald-950">
					Questo è il tuo profilo pubblico
				</h2>
				<p class="mt-1 text-sm leading-relaxed text-gray-700">
					Consulta la pagina che vedono gli altri utenti o condividi il tuo profilo ItalianCosplay.
				</p>
				<p class="mt-3 break-all rounded-lg bg-white/70 px-3 py-2 text-sm text-gray-700">
					<?php echo htmlspecialchars($publicProfileUrl, ENT_QUOTES, 'UTF-8'); ?>
				</p>
			</div>

			<div class="flex flex-col gap-3 sm:flex-row">
				<a href="<?php echo htmlspecialchars($publicProfileUrl, ENT_QUOTES, 'UTF-8'); ?>"
				   target="_blank"
				   rel="noopener noreferrer"
				   class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-emerald-800 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
					<i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
					Consulta profilo
				</a>
				<button type="button"
				        class="copy-dashboard-profile-link inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-emerald-800 bg-white px-4 py-3 text-sm font-bold text-emerald-900 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2"
				        data-copy="<?php echo htmlspecialchars($publicProfileUrl, ENT_QUOTES, 'UTF-8'); ?>">
					<i class="fa-solid fa-share-nodes" aria-hidden="true"></i>
					<span>Condividi</span>
				</button>
			</div>
		</div>
		<p class="copy-dashboard-profile-feedback mt-3 hidden text-sm font-semibold text-emerald-800" role="status" aria-live="polite"></p>
	</section>

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const publicProfile = document.querySelector('[aria-labelledby="dashboard-public-profile-title"]');
			const organizationSection = document.querySelector('[aria-label="Gestione organizzazioni ed eventi master"]');
			if (publicProfile && organizationSection) organizationSection.parentNode.insertBefore(publicProfile, organizationSection);
		});
	</script>

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

		<?php if (!empty($featureFlags['enable_personal_agenda'])): ?>
			<a href="/dashboard/events" class="rounded-2xl bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-green-700">
				<span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-900">
					<i class="fa-solid fa-calendar-check"></i>
				</span>
				<h2 class="mt-4 text-xl font-bold text-gray-950">Agenda Cosplay</h2>
				<p class="mt-2 text-sm leading-relaxed text-gray-700">Organizza gli eventi salvati e tieni d'occhio quelli a cui vuoi partecipare.</p>
				<p class="mt-4 text-sm font-semibold text-blue-900"><?php echo (int) array_sum($agendaCounts); ?> eventi in agenda</p>
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

<script>
	document.addEventListener('DOMContentLoaded', () => {
		document.querySelectorAll('.copy-dashboard-profile-link').forEach(button => {
			button.addEventListener('click', async () => {
				const value = button.dataset.copy || '';
				const feedback = button.closest('section')?.querySelector('.copy-dashboard-profile-feedback');
				if (!value || !feedback) return;

				try {
					if (navigator.share) {
						await navigator.share({
							title: 'Il mio profilo su ItalianCosplay',
							url: value,
						});
						feedback.textContent = 'Profilo condiviso.';
					} else {
						await navigator.clipboard.writeText(value);
						feedback.textContent = 'Link del profilo copiato.';
					}
				} catch (error) {
					if (error?.name === 'AbortError') return;
					window.prompt('Copia il link del profilo:', value);
					feedback.textContent = 'Puoi copiare il link dalla finestra aperta.';
				}

				feedback.classList.remove('hidden');
				window.setTimeout(() => feedback.classList.add('hidden'), 2500);
			});
		});
	});
</script>
