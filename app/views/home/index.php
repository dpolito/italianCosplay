<?php
use App\Helpers\EventCard;

$topEvents = $top ?? [];
$trendingEvents = $trending ?? [];
$newEvents = $new ?? [];
$latestPosts = $latestPosts ?? [];
$latestEvents = $latestEvents ?? [];
$showEvents = !empty($showEvents);
$showBlog = !empty($showBlog);

$homeUrl = rtrim(URL_ROOT_SITE, '/') . '/';
$itemListSchema = [
	'@context' => 'https://schema.org',
	'@type' => 'ItemList',
	'name' => 'Eventi cosplay in evidenza',
	'itemListElement' => [],
];

	if ($showEvents) {
	foreach (array_slice($topEvents, 0, 5) as $index => $event) {
	$itemListSchema['itemListElement'][] = [
		'@type' => 'ListItem',
		'position' => $index + 1,
		'url' => $homeUrl . 'eventi-cosplay/' . ($event['slug'] ?? ''),
		'name' => trim(($event['titolo'] ?? 'Evento cosplay') . ' ' . date('Y', strtotime($event['data_inizio'] ?? 'now'))),
	];
}
	}

$renderEventCard = static function (array $event, string $variant = 'default', bool $lazy = true): string {
	return EventCard::render($event, $variant, $lazy);
};
?>
<script type="application/ld+json"><?php echo json_encode($itemListSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

<main class="bg-slate-50 text-slate-900">
	<section class="relative overflow-hidden bg-white">
		<div class="absolute inset-x-0 top-0 h-80 bg-gradient-to-b from-emerald-50 to-transparent"></div>
		<div class="absolute right-0 top-10 h-72 w-72 rounded-full bg-amber-100/70 blur-3xl"></div>
		<div class="relative mx-auto max-w-7xl px-4 pb-12 pt-6 md:px-6 md:pb-16 md:pt-8">
			<div class="grid gap-10 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
				<div class="max-w-3xl">
					<p class="inline-flex rounded-full bg-emerald-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-emerald-900">
						Calendario eventi cosplay in Italia
					</p>
					<h1 class="mt-5 text-4xl font-black tracking-tight text-slate-950 md:text-6xl">
						Calendario eventi cosplay in Italia: fiere, raduni e convention.
					</h1>
					<p class="mt-5 max-w-2xl text-lg leading-8 text-slate-700 md:text-xl">
						ItalianCosplay mette al centro il calendario eventi cosplay in Italia, così trovi subito fiere del fumetto, raduni, comics e convention. Le guide restano il tuo secondo punto di riferimento per consigli e approfondimenti.
					</p>

					<div class="mt-8 flex flex-wrap gap-3">
						<?php if ($showEvents): ?>
							<a href="/eventi-cosplay" class="inline-flex items-center rounded-full bg-emerald-600 px-6 py-3 font-semibold text-white transition hover:bg-emerald-700">
								Esplora il calendario eventi cosplay
							</a>
						<?php endif; ?>
						<?php if ($showBlog): ?>
							<a href="/blog" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-6 py-3 font-semibold text-slate-900 transition hover:border-emerald-300 hover:bg-emerald-50">
								Portale guide cosplay
							</a>
						<?php endif; ?>
						<a href="/segnala-evento-cosplay" class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-6 py-3 font-semibold text-slate-700 transition hover:border-emerald-300 hover:text-emerald-900">
							Invia una segnalazione
						</a>
					</div>

					<div class="mt-8 grid gap-4 sm:grid-cols-3">
						<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
							<p class="text-3xl font-black text-slate-950"><?php echo count($topEvents); ?></p>
							<p class="mt-1 text-sm text-slate-600">Eventi consigliati</p>
						</div>
						<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
							<p class="text-3xl font-black text-slate-950"><?php echo (int) $approvedEventsCount; ?></p>
							<p class="mt-1 text-sm text-slate-600">Eventi approvati</p>
						</div>
						<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
							<p class="text-3xl font-black text-slate-950"><?php echo (int) $publishedPostsCount; ?></p>
							<p class="mt-1 text-sm text-slate-600">Guide cosplay</p>
						</div>
					</div>
				</div>

				<aside class="relative">
					<div class="rounded-[2rem] border border-slate-200 bg-slate-950 p-6 text-white shadow-2xl">
						<div class="flex items-center justify-between">
							<p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-300">Accesso rapido</p>
							<span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white/80">Sempre utile</span>
						</div>

						<div class="mt-6 space-y-3">
							<?php if ($showEvents): ?>
								<a href="/eventi-cosplay" class="flex items-center justify-between rounded-2xl bg-white/5 px-4 py-4 transition hover:bg-white/10">
									<span>
										<span class="block font-semibold text-white">Calendario eventi</span>
										<span class="block text-sm text-slate-300">Scopri fiere del fumetto, raduni e convention cosplay</span>
									</span>
									<span class="text-emerald-300">→</span>
								</a>
								<a href="/eventi-cosplay-weekend" class="flex items-center justify-between rounded-2xl bg-white/5 px-4 py-4 transition hover:bg-white/10">
									<span>
										<span class="block font-semibold text-white">Questo weekend</span>
										<span class="block text-sm text-slate-300">Trova subito eventi cosplay nel weekend</span>
									</span>
									<span class="text-emerald-300">→</span>
								</a>
							<?php endif; ?>
							<?php if ($showBlog): ?>
								<a href="/blog" class="flex items-center justify-between rounded-2xl bg-white/5 px-4 py-4 transition hover:bg-white/10">
									<span>
										<span class="block font-semibold text-white">Guide cosplay</span>
										<span class="block text-sm text-slate-300">Leggi articoli utili dopo aver scelto l'evento</span>
									</span>
									<span class="text-emerald-300">→</span>
								</a>
							<?php endif; ?>
						</div>
					</div>
				</aside>
			</div>
		</div>
	</section>

	<?php echo \App\Helpers\AdPlacement::render('homepage_top', 'homepage', 'mx-auto max-w-7xl px-4 md:px-6 -mt-4 mb-8'); ?>

	<?php if ($showEvents): ?>
	<section class="border-y border-slate-200 bg-slate-50">
		<div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 md:grid-cols-3 md:px-6">
			<div class="rounded-2xl bg-white p-5 shadow-sm">
				<p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-800">Cosa trovi subito</p>
				<p class="mt-2 text-base text-slate-700">Le prossime fiere, i raduni e le convention cosplay in Italia, senza dover cercare altrove.</p>
			</div>
			<div class="rounded-2xl bg-white p-5 shadow-sm">
				<p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-800">Perché seguirlo</p>
				<p class="mt-2 text-base text-slate-700">Hai un punto unico dove controllare date, leggere guide utili e scoprire nuovi eventi appena pubblicati.</p>
			</div>
			<div class="rounded-2xl bg-white p-5 shadow-sm">
				<p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-800">Come usarlo</p>
				<p class="mt-2 text-base text-slate-700">Apri una scheda evento, controlla dove e quando si svolge e passa alle guide per prepararti meglio.</p>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php echo \App\Helpers\AdPlacement::render('homepage_middle', 'homepage', 'mx-auto max-w-7xl px-4 md:px-6 my-8'); ?>

	<?php if ($showEvents): ?>
	<section class="bg-slate-50">
		<div class="mx-auto max-w-7xl px-4 py-12 md:px-6 md:py-16">
			<div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
				<div>
					<p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-700">Calendario eventi cosplay</p>
					<h2 class="mt-2 text-3xl font-black tracking-tight md:text-4xl">Prossimi eventi in programma</h2>
					<p class="mt-3 max-w-2xl text-slate-700">Qui trovi le prossime fiere, i raduni e le convention cosplay già in calendario, così puoi vedere subito cosa sta arrivando.</p>
				</div>
				<a href="/eventi-cosplay" class="inline-flex items-center font-semibold text-emerald-800 hover:text-emerald-950">
					Vedi tutti gli eventi
					<span class="ml-2">→</span>
				</a>
			</div>

			<div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
				<?php foreach (array_slice($topEvents, 0, 4) as $event): ?>
					<?php echo $renderEventCard($event, 'top', true); ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php if ($showBlog): ?>
	<section class="bg-slate-50">
		<div class="mx-auto max-w-7xl px-4 py-12 md:px-6 md:py-16">
			<div class="grid gap-8 lg:grid-cols-[1.4fr_0.6fr]">
				<div>
					<p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-700">Portale guide cosplay</p>
					<h2 class="mt-2 text-3xl font-black tracking-tight md:text-4xl">Ultime guide</h2>
					<p class="mt-3 max-w-2xl text-slate-700">Le guide completano il calendario con consigli pratici, notizie e approfondimenti utili per chi vuole prepararsi meglio agli eventi.</p>

					<div class="mt-8 grid gap-5 md:grid-cols-3">
						<?php foreach (array_slice($latestPosts, 0, 3) as $post): ?>
							<article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
								<a href="/blog/<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="block">
									<?php if (!empty($post['cover_image'])): ?>
										<div class="aspect-[16/10] overflow-hidden bg-slate-100">
											<img
												src="/public_assets/<?php echo htmlspecialchars($post['cover_image'], ENT_QUOTES, 'UTF-8'); ?>"
												alt="<?php echo htmlspecialchars($post['titolo'], ENT_QUOTES, 'UTF-8'); ?>"
												class="h-full w-full object-cover transition duration-300 hover:scale-[1.03]"
												loading="lazy"
											>
										</div>
									<?php endif; ?>
									<div class="p-5">
										<p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Guida cosplay</p>
										<h3 class="mt-2 text-xl font-black leading-snug text-slate-950">
											<?php echo htmlspecialchars($post['titolo'], ENT_QUOTES, 'UTF-8'); ?>
										</h3>
										<p class="mt-3 text-sm text-slate-700">
											Pubblicato il <?php echo date('d/m/Y', strtotime($post['created_at'])); ?>
										</p>
										<span class="mt-4 inline-flex font-semibold text-emerald-800">
											Leggi l'articolo →
										</span>
									</div>
								</a>
							</article>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="space-y-5">
					<div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
						<h3 class="text-xl font-black text-slate-950">Come usare il sito</h3>
						<ul class="mt-4 space-y-3 text-sm leading-6 text-slate-700">
							<li>1. Parti dal calendario per vedere subito le prossime date disponibili.</li>
							<li>2. Apri la scheda evento per controllare luogo, periodo e dettagli utili.</li>
							<li>3. Usa le guide come supporto: consigli e contenuti extra.</li>
						</ul>
					</div>

					<div class="rounded-3xl bg-emerald-700 p-6 text-white shadow-lg">
						<p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-100">Link utili</p>
						<div class="mt-4 space-y-3">
							<a href="/blog" class="block rounded-2xl bg-white/10 px-4 py-4 transition hover:bg-white/15">
								<span class="block font-semibold">Vai alle guide cosplay</span>
								<span class="block text-sm text-emerald-50/90">Tutti gli articoli, le guide e le notizie</span>
							</a>
							<a href="/segnala-evento-cosplay" class="block rounded-2xl bg-white/10 px-4 py-4 transition hover:bg-white/15">
								<span class="block font-semibold">Segnala un evento cosplay</span>
								<span class="block text-sm text-emerald-50/90">Invia una nuova data da aggiungere al calendario</span>
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php if ($showEvents): ?>
	<section class="bg-white">
		<div class="mx-auto max-w-7xl px-4 py-12 md:px-6 md:py-16">
			<div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
				<div>
					<p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-700">Calendario in primo piano</p>
					<h2 class="mt-2 text-3xl font-black tracking-tight md:text-4xl">Eventi cosplay da non perdere</h2>
					<p class="mt-3 max-w-2xl text-slate-700">Qui trovi le fiere cosplay più interessanti del momento, ordinate in modo semplice e veloce da consultare.</p>
				</div>
				<a href="/eventi-cosplay" class="inline-flex items-center font-semibold text-emerald-800 hover:text-emerald-950">
					Vedi il calendario completo
					<span class="ml-2">→</span>
				</a>
			</div>

			<div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
				<?php foreach (array_slice($upcomingEvents, 0, 4) as $event): ?>
					<?php echo $renderEventCard($event, 'default', true); ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php if ($showEvents): ?>
	<section class="bg-slate-50">
		<div class="mx-auto max-w-7xl px-4 py-12 md:px-6 md:py-16">
			<div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
				<div>
					<p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-700">Nuovi eventi</p>
					<h2 class="mt-2 text-3xl font-black tracking-tight md:text-4xl">Ultimi eventi inseriti</h2>
					<p class="mt-3 max-w-2xl text-slate-700">Qui trovi gli eventi cosplay appena aggiunti al portale, così puoi vedere subito le novità del calendario in Italia.</p>
				</div>
				<a href="/eventi-cosplay" class="inline-flex items-center font-semibold text-emerald-800 hover:text-emerald-950">
					Vai al calendario eventi
					<span class="ml-2">→</span>
				</a>
			</div>

			<div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
				<?php foreach (array_slice($latestEvents, 0, 8) as $event): ?>
					<?php echo $renderEventCard($event, 'new', true); ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<section class="bg-slate-50">
		<div class="mx-auto max-w-7xl px-4 py-12 md:px-6 md:py-16">
			<div class="grid gap-8 lg:grid-cols-[1fr_0.9fr]">
				<div>
					<p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-700">Per regione</p>
					<h2 class="mt-2 text-3xl font-black tracking-tight md:text-4xl">Eventi cosplay per regione</h2>
					<p class="mt-3 max-w-2xl text-slate-700">Le pagine territoriali ti aiutano a trovare più velocemente gli eventi cosplay vicino a casa e a navigare il calendario in modo naturale.</p>

					<div class="mt-6 flex flex-wrap gap-3">
						<?php foreach (['Toscana', 'Lombardia', 'Lazio', 'Veneto', 'Piemonte', 'Sicilia', 'Emilia-Romagna', 'Campania'] as $regione): ?>
							<a href="/eventi-cosplay" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-emerald-400 hover:bg-emerald-50 hover:text-emerald-900" aria-label="Scopri gli eventi cosplay in <?php echo htmlspecialchars($regione, ENT_QUOTES, 'UTF-8'); ?>">
								<?= htmlspecialchars($regione, ENT_QUOTES, 'UTF-8') ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="rounded-3xl bg-slate-950 p-6 text-white shadow-2xl">
					<p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-300">Come ti aiuta il portale</p>
					<ul class="mt-4 space-y-4 text-sm leading-6 text-slate-200">
						<li>Scopri eventi cosplay in Italia, schede dettagliate con date e location, e guide utili per prepararti al meglio.</li>
						<li>Usa il calendario per trovare subito fiere, raduni e convention cosplay, poi approfondisci con articoli e contenuti pensati per aiutarti a scegliere più in fretta.</li>
					</ul>
				</div>
			</div>
		</div>
	</section>

	<?php echo \App\Helpers\AdPlacement::render('homepage_bottom', 'homepage', 'mx-auto max-w-7xl px-4 md:px-6 my-8'); ?>
</main>
