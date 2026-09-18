<?php
use App\Helpers\EventCard;
use App\Helpers\MonthHelper;
use App\Helpers\WeekendHelper;

$topEvents = $top ?? [];
$trendingEvents = $trending ?? [];
$latestPosts = $latestPosts ?? [];
$latestEvents = $latestEvents ?? [];
$upcomingEvents = $upcomingEvents ?? [];
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

$h = static function ($value): string {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$dateLabel = static function (?string $startDate, ?string $endDate = null): string {
	if (empty($startDate)) {
		return 'Data da confermare';
	}

	$label = date('d/m/Y', strtotime($startDate));

	if (!empty($endDate) && $endDate !== $startDate) {
		$label .= ' - ' . date('d/m/Y', strtotime($endDate));
	}

	return $label;
};

$eventImage = static function (array $event): string {
	$imagePath = (string) ($event['immagine'] ?? '');

	if ($imagePath === '') {
		return 'https://placehold.co/800x500';
	}

	if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
		return $imagePath;
	}

	return rtrim(URL_ROOT_SITE, '/') . '/' . ltrim($imagePath, '/');
};

$renderEventCard = static function (array $event, string $variant = 'default', bool $lazy = true): string {
	return EventCard::render($event, $variant, $lazy);
};

$currentWeekend = WeekendHelper::getCurrentWeekend();
$currentWeekendSlug = WeekendHelper::generateSlug($currentWeekend['start'], $currentWeekend['end']);
$currentMonth = MonthHelper::current();
$currentMonthSlug = MonthHelper::generateSlug($currentMonth['month'], $currentMonth['year']);

$renderCompactEvent = static function (array $event, int $index = 0) use ($h, $dateLabel, $eventImage): string {
	$title = (string) ($event['titolo'] ?? 'Evento cosplay');
	$slug = (string) ($event['slug'] ?? '');
	$city = (string) ($event['comune_nome'] ?? '');
	$province = (string) ($event['provincia_nome'] ?? '');
	$region = (string) ($event['regione_nome'] ?? '');
	$location = implode(' · ', array_filter([$city, $province, $region]));
	$startDate = (string) ($event['data_inizio'] ?? '');
	$endDate = (string) ($event['data_fine'] ?? '');
	$link = rtrim(URL_ROOT_SITE, '/') . '/eventi-cosplay/' . $h($slug);
	$imageUrl = $eventImage($event);
	$loading = $index < 2 ? 'eager' : 'lazy';
	$fetchPriority = $index === 0 ? 'high' : 'auto';

	return '
		<a href="' . $link . '" class="group block rounded-xl border border-slate-200 bg-white p-2 shadow-sm transition hover:border-emerald-300 hover:shadow-md">
			<article class="grid grid-cols-[6.5rem_1fr] gap-3 lg:block">
				<div class="aspect-[4/3] overflow-hidden rounded-lg bg-slate-100">
					<img src="' . $h($imageUrl) . '" alt="' . $h($title . ' evento cosplay') . '" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]" loading="' . $loading . '" fetchpriority="' . $fetchPriority . '">
				</div>
				<div class="min-w-0 py-1 lg:pt-3">
					<p class="text-xs font-bold uppercase tracking-wide text-emerald-700">' . $h($dateLabel($startDate, $endDate)) . '</p>
					<h3 class="mt-1 line-clamp-2 text-base font-black leading-tight text-slate-950">' . $h($title) . '</h3>
					' . ($location !== '' ? '<p class="mt-2 truncate text-sm text-slate-600">' . $h($location) . '</p>' : '') . '
					<span class="mt-2 inline-flex text-sm font-bold text-emerald-800">Apri scheda</span>
				</div>
			</article>
		</a>';
};

$primaryDiscoveryEvents = !empty($topEvents) ? $topEvents : $upcomingEvents;
$secondaryDiscoveryEvents = !empty($upcomingEvents) ? $upcomingEvents : $latestEvents;
$mostViewedEvents = !empty($trendingEvents) ? $trendingEvents : $primaryDiscoveryEvents;
$mostViewedIntro = !empty($trendingEvents)
	? 'Una selezione basata sulle visite recenti e sul punteggio evento, utile per scoprire cosa si sta muovendo adesso.'
	: 'Una selezione di eventi in evidenza mentre raccogliamo abbastanza segnali di visita recenti.';
$regionLinks = [
	'Toscana' => 'toscana',
	'Lombardia' => 'lombardia',
	'Lazio' => 'lazio',
	'Veneto' => 'veneto',
	'Piemonte' => 'piemonte',
	'Emilia-Romagna' => 'emilia-romagna',
	'Campania' => 'campania',
	'Sicilia' => 'sicilia',
];
?>
<script type="application/ld+json"><?php echo json_encode($itemListSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<style>
	.home-mobile-event-list {
		display: grid;
	}

	.home-desktop-event-grid {
		display: none;
	}

	.home-feature-event-grid {
		display: grid;
	}

	.home-hero-grid {
		display: grid;
		gap: 1.75rem;
	}

	.home-hero-inner {
		padding-bottom: 1.5rem;
	}

	.home-desktop-only {
		display: none;
	}

	.home-mobile-only {
		display: block;
	}

	.home-mobile-actions {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 0.625rem;
	}

	.home-mobile-action {
		align-items: center;
		display: flex;
		justify-content: center;
		min-height: 3rem;
		white-space: normal;
	}

	.home-desktop-copy {
		display: none;
	}

	.home-mobile-copy {
		display: inline;
	}

	.home-desktop-info-grid {
		display: grid;
		gap: 1rem;
	}

	.home-desktop-actions {
		display: flex;
		flex-wrap: wrap;
		column-gap: 1rem;
		row-gap: 1rem;
	}

	@media (min-width: 768px) {
		.home-mobile-event-list {
			display: none;
		}

		.home-desktop-event-grid {
			display: grid;
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
	}

	@media (min-width: 1024px) {
		.home-desktop-only {
			display: block;
		}

		.home-mobile-only {
			display: none;
		}

		.home-desktop-copy {
			display: inline;
		}

		.home-mobile-copy {
			display: none;
		}

		.home-hero-grid {
			grid-template-columns: minmax(0, 0.85fr) minmax(0, 1.15fr);
			align-items: start;
		}

		.home-hero-inner {
			padding-bottom: 3rem;
		}

		.home-feature-event-grid {
			grid-template-columns: repeat(3, minmax(0, 1fr));
		}

		.home-desktop-info-grid {
			grid-template-columns: repeat(3, minmax(0, 1fr));
		}
	}

	@media (min-width: 1280px) {
		.home-desktop-event-grid {
			grid-template-columns: repeat(4, minmax(0, 1fr));
		}
	}
</style>

<main class="bg-slate-50 text-slate-900">
	<section class="bg-white">
		<div class="home-hero-inner mx-auto max-w-7xl px-4 pt-5 md:px-6 md:pt-10 lg:pt-12">
			<div class="home-hero-grid">
				<div>
					<p class="text-sm font-bold uppercase tracking-wide text-emerald-700">Eventi cosplay in Italia</p>
					<h1 class="mt-3 text-3xl font-black leading-tight tracking-tight text-slate-950 md:text-5xl">
						<span class="home-mobile-copy">Trova eventi cosplay vicino a te.</span>
						<span class="home-desktop-copy">Calendario eventi cosplay in Italia: fiere, raduni e convention.</span>
					</h1>
					<p class="mt-4 max-w-2xl text-base leading-7 text-slate-700 md:text-lg">
						<span class="home-mobile-copy">Scopri fiere, raduni e convention cosplay in Italia: prossime date, novità e percorsi rapidi per scegliere cosa visitare.</span>
						<span class="home-desktop-copy">ItalianCosplay raccoglie eventi cosplay, fiere del fumetto, comics e convention in un calendario pensato per pianificare, scoprire nuove date e navigare per territorio.</span>
					</p>

					<?php if ($showEvents): ?>
						<nav class="home-mobile-actions home-mobile-only mt-5" aria-label="Scorciatoie eventi cosplay mobile">
							<a href="/eventi-cosplay-weekend/<?php echo $h($currentWeekendSlug); ?>" class="home-mobile-action rounded-xl bg-emerald-700 px-3 py-2 text-center text-sm font-black leading-tight text-white shadow-sm transition hover:bg-emerald-800">Questo weekend</a>
							<a href="/eventi-cosplay-mese/<?php echo $h($currentMonthSlug); ?>" class="home-mobile-action rounded-xl border border-slate-200 bg-white px-3 py-2 text-center text-sm font-black leading-tight text-slate-900 transition hover:border-emerald-300 hover:bg-emerald-50">Questo mese</a>
							<a href="/eventi-cosplay" class="home-mobile-action rounded-xl border border-slate-200 bg-white px-3 py-2 text-center text-sm font-black leading-tight text-slate-900 transition hover:border-emerald-300 hover:bg-emerald-50">Tutti gli eventi</a>
							<a href="/segnala-evento-cosplay" class="home-mobile-action rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-center text-sm font-black leading-tight text-amber-950 transition hover:border-amber-300 hover:bg-amber-100">Segnala evento</a>
						</nav>

						<nav class="home-desktop-only mt-8" aria-label="Azioni principali eventi cosplay">
							<div class="home-desktop-actions">
								<a href="/eventi-cosplay" class="inline-flex rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-emerald-800">Esplora il calendario</a>
								<a href="/eventi-cosplay-weekend/<?php echo $h($currentWeekendSlug); ?>" class="inline-flex rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-900 transition hover:border-emerald-300 hover:bg-emerald-50">Eventi del weekend</a>
								<a href="/segnala-evento-cosplay" class="inline-flex rounded-xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm font-black text-amber-950 transition hover:bg-amber-100">Segnala un evento</a>
							</div>
						</nav>
					<?php endif; ?>
				</div>

				<?php if ($showEvents): ?>
					<div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-3 shadow-sm md:p-4 lg:mt-0">
						<div class="flex items-center justify-between gap-4 px-1">
							<div>
								<p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Scopri ora</p>
								<h2 class="text-xl font-black text-slate-950">Eventi in evidenza</h2>
							</div>
							<a href="/eventi-cosplay" class="shrink-0 text-sm font-bold text-emerald-800 hover:text-emerald-950">Vedi tutti</a>
						</div>
						<div class="home-feature-event-grid mt-3 gap-3">
							<?php foreach (array_slice($primaryDiscoveryEvents, 0, 3) as $index => $event): ?>
								<?php echo $renderCompactEvent($event, $index); ?>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php if ($showEvents): ?>
		<section class="home-desktop-only border-y border-slate-200 bg-slate-50">
			<div class="home-desktop-info-grid mx-auto max-w-7xl px-6 py-6">
				<div class="rounded-xl bg-white p-5 shadow-sm">
					<p class="text-sm font-bold uppercase tracking-wide text-emerald-700">Cosa trovi subito</p>
					<p class="mt-2 text-sm leading-6 text-slate-700">Le prossime fiere, i raduni e le convention cosplay in Italia, con date e schede evento facili da consultare.</p>
				</div>
				<div class="rounded-xl bg-white p-5 shadow-sm">
					<p class="text-sm font-bold uppercase tracking-wide text-emerald-700">Come scoprirli</p>
					<p class="mt-2 text-sm leading-6 text-slate-700">Puoi partire dal calendario, dal weekend, dal mese corrente oppure dalle pagine regionali per trovare eventi vicino a casa.</p>
				</div>
				<div class="rounded-xl bg-white p-5 shadow-sm">
					<p class="text-sm font-bold uppercase tracking-wide text-emerald-700">Perché seguirlo</p>
					<p class="mt-2 text-sm leading-6 text-slate-700">La home unisce discovery, novità, guide e collegamenti territoriali utili per utenti, organizzatori e futuri sponsor.</p>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php echo \App\Helpers\AdPlacement::render('homepage_top', 'homepage', 'mx-auto max-w-7xl px-4 md:px-6 my-6'); ?>

	<?php if ($showEvents): ?>
		<section class="bg-slate-50">
			<div class="mx-auto max-w-7xl px-4 py-8 md:px-6 md:py-12">
				<div class="flex items-end justify-between gap-4">
					<div>
						<p class="text-sm font-bold uppercase tracking-wide text-emerald-700">Prossime date</p>
						<h2 class="mt-1 text-2xl font-black tracking-tight md:text-4xl">In arrivo nel calendario</h2>
					</div>
					<a href="/eventi-cosplay" class="hidden text-sm font-bold text-emerald-800 hover:text-emerald-950 sm:inline-flex">Calendario completo</a>
				</div>

				<div class="home-mobile-event-list mt-5 gap-3">
					<?php foreach (array_slice($secondaryDiscoveryEvents, 0, 5) as $index => $event): ?>
						<?php echo $renderCompactEvent($event, $index + 4); ?>
					<?php endforeach; ?>
				</div>

				<div class="home-desktop-event-grid mt-7 gap-5">
					<?php foreach (array_slice($secondaryDiscoveryEvents, 0, 4) as $event): ?>
						<?php echo $renderEventCard($event, 'default', true); ?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ($showEvents && !empty($mostViewedEvents)): ?>
		<section class="bg-white">
			<div class="mx-auto max-w-7xl px-4 py-8 md:px-6 md:py-12">
				<div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
					<div>
						<p class="text-sm font-bold uppercase tracking-wide text-emerald-700">Più visti ora</p>
						<h2 class="mt-1 text-2xl font-black tracking-tight md:text-4xl">Eventi cosplay che stanno attirando attenzione</h2>
						<p class="mt-3 max-w-2xl text-sm leading-6 text-slate-700 md:text-base"><?php echo $h($mostViewedIntro); ?></p>
					</div>
					<a href="/eventi-cosplay" class="text-sm font-bold text-emerald-800 hover:text-emerald-950">Esplora eventi</a>
				</div>

				<div class="home-mobile-event-list mt-5 gap-3">
					<?php foreach (array_slice($mostViewedEvents, 0, 4) as $index => $event): ?>
						<?php echo $renderCompactEvent($event, $index + 20); ?>
					<?php endforeach; ?>
				</div>

				<div class="home-desktop-event-grid mt-7 gap-5">
					<?php foreach (array_slice($mostViewedEvents, 0, 4) as $event): ?>
						<?php echo $renderEventCard($event, 'top', true); ?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ($showEvents): ?>
		<section class="border-y border-slate-200 bg-white">
			<div class="mx-auto max-w-7xl px-4 py-8 md:px-6 md:py-12">
				<div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
					<div>
						<p class="text-sm font-bold uppercase tracking-wide text-emerald-700">Per regione</p>
						<h2 class="mt-1 text-2xl font-black tracking-tight md:text-4xl">Scopri eventi vicino a casa</h2>
						<p class="mt-3 max-w-2xl text-sm leading-6 text-slate-700 md:text-base">Le pagine territoriali aiutano utenti e motori di ricerca a collegare eventi, province e regioni.</p>
					</div>
					<a href="/eventi-cosplay" class="text-sm font-bold text-emerald-800 hover:text-emerald-950">Tutte le località</a>
				</div>

				<div class="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
					<?php foreach ($regionLinks as $regionName => $regionSlug): ?>
						<a href="/eventi-cosplay/<?php echo $h($regionSlug); ?>" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-800 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-900" aria-label="Scopri gli eventi cosplay in <?php echo $h($regionName); ?>">
							<?php echo $h($regionName); ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php echo \App\Helpers\AdPlacement::render('homepage_middle', 'homepage', 'mx-auto max-w-7xl px-4 md:px-6 my-6'); ?>

	<?php if ($showEvents && !empty($latestEvents)): ?>
		<section class="bg-slate-50">
			<div class="mx-auto max-w-7xl px-4 py-8 md:px-6 md:py-12">
				<div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
					<div>
						<p class="text-sm font-bold uppercase tracking-wide text-emerald-700">Novità</p>
						<h2 class="mt-1 text-2xl font-black tracking-tight md:text-4xl">Appena aggiunti</h2>
						<p class="mt-3 max-w-2xl text-sm leading-6 text-slate-700 md:text-base">Gli ultimi eventi pubblicati, utili per scoprire nuove date prima che finiscano sommerse nel calendario.</p>
					</div>
					<a href="/eventi-cosplay" class="text-sm font-bold text-emerald-800 hover:text-emerald-950">Vai al calendario</a>
				</div>

				<div class="home-mobile-event-list mt-5 gap-3">
					<?php foreach (array_slice($latestEvents, 0, 4) as $index => $event): ?>
						<?php echo $renderCompactEvent($event, $index + 9); ?>
					<?php endforeach; ?>
				</div>

				<div class="home-desktop-event-grid mt-7 gap-5">
					<?php foreach (array_slice($latestEvents, 0, 8) as $event): ?>
						<?php echo $renderEventCard($event, 'new', true); ?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ($showBlog): ?>
		<section class="bg-white">
			<div class="mx-auto max-w-7xl px-4 py-8 md:px-6 md:py-12">
				<div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
					<div>
						<p class="text-sm font-bold uppercase tracking-wide text-emerald-700">Guide cosplay</p>
						<h2 class="mt-1 text-2xl font-black tracking-tight md:text-4xl">Leggi prima di partire</h2>
						<p class="mt-3 max-w-2xl text-sm leading-6 text-slate-700 md:text-base">Contenuti utili dopo la scelta dell'evento: preparazione, consigli e approfondimenti collegabili al calendario.</p>
					</div>
					<a href="/blog" class="text-sm font-bold text-emerald-800 hover:text-emerald-950">Tutte le guide</a>
				</div>

				<div class="mt-6 grid gap-4 md:grid-cols-3">
					<?php foreach (array_slice($latestPosts, 0, 3) as $post): ?>
						<article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:border-emerald-300 hover:shadow-md">
							<a href="/blog/<?php echo $h($post['slug'] ?? ''); ?>" class="block">
								<?php if (!empty($post['cover_image'])): ?>
									<div class="aspect-[16/9] overflow-hidden bg-slate-100">
										<img
											src="/public_assets/<?php echo $h($post['cover_image']); ?>"
											alt="<?php echo $h($post['titolo'] ?? 'Guida cosplay'); ?>"
											class="h-full w-full object-cover transition duration-300 hover:scale-[1.03]"
											loading="lazy"
										>
									</div>
								<?php endif; ?>
								<div class="p-4">
									<p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Guida</p>
									<h3 class="mt-2 line-clamp-2 text-lg font-black leading-snug text-slate-950">
										<?php echo $h($post['titolo'] ?? 'Guida cosplay'); ?>
									</h3>
									<p class="mt-3 text-sm text-slate-600">
										Pubblicato il <?php echo $h(date('d/m/Y', strtotime($post['created_at'] ?? 'now'))); ?>
									</p>
								</div>
							</a>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="bg-slate-950 text-white">
		<div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 md:grid-cols-[1fr_0.7fr] md:px-6 md:py-12">
			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-emerald-300">ItalianCosplay.it</p>
				<h2 class="mt-2 text-2xl font-black tracking-tight md:text-4xl">Un calendario pensato per scoprire, non solo cercare.</h2>
				<p class="mt-4 max-w-3xl text-sm leading-6 text-slate-200 md:text-base">
					La home mette prima gli eventi e poi le guide: un percorso più naturale per chi arriva da mobile, ma utile anche per SEO, internal linking e future sponsorizzazioni territoriali.
				</p>
			</div>
			<div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-1">
				<div class="rounded-xl bg-white/10 p-4">
					<p class="text-2xl font-black"><?php echo (int) $approvedEventsCount; ?></p>
					<p class="mt-1 text-sm text-slate-300">Eventi approvati</p>
				</div>
				<div class="rounded-xl bg-white/10 p-4">
					<p class="text-2xl font-black"><?php echo count($topEvents); ?></p>
					<p class="mt-1 text-sm text-slate-300">Eventi in evidenza</p>
				</div>
				<div class="rounded-xl bg-white/10 p-4">
					<p class="text-2xl font-black"><?php echo (int) $publishedPostsCount; ?></p>
					<p class="mt-1 text-sm text-slate-300">Guide pubblicate</p>
				</div>
			</div>
		</div>
	</section>

	<?php echo \App\Helpers\AdPlacement::render('homepage_bottom', 'homepage', 'mx-auto max-w-7xl px-4 md:px-6 my-6'); ?>
</main>
