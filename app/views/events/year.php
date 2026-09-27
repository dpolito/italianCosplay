<?php
$year = (int) ($data['year'] ?? date('Y'));
$events = $data['events'] ?? [];
$eventsByMonth = $data['eventsByMonth'] ?? [];
$months = $data['months'] ?? [];
$summary = $data['summary'] ?? [];
$breadcrumbs = $data['breadcrumbs'] ?? [];
$eventCount = (int) ($data['eventCount'] ?? count($events));
$siteBaseUrl = rtrim(URL_ROOT_SITE, '/');
$eventsBaseUrl = $siteBaseUrl . '/eventi-cosplay';
$canonicalUrl = $data['canonicalUrl'] ?? ($siteBaseUrl . '/eventi-cosplay-' . $year);
$regionCount = (int) ($summary['region_count'] ?? 0);

if (!function_exists('event_year_h')) {
	function event_year_h($value): string
	{
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('event_year_date_label')) {
	function event_year_date_label(?string $startDate, ?string $endDate = null): string
	{
		if (empty($startDate)) {
			return 'Data da confermare';
		}

		$label = date('d/m/Y', strtotime($startDate));
		if (!empty($endDate) && $endDate !== $startDate) {
			$label .= ' - ' . date('d/m/Y', strtotime($endDate));
		}

		return $label;
	}
}

$breadcrumbSchema = [
	'@context' => 'https://schema.org',
	'@type' => 'BreadcrumbList',
	'itemListElement' => [],
];

foreach ($breadcrumbs as $index => $crumb) {
	$breadcrumbSchema['itemListElement'][] = [
		'@type' => 'ListItem',
		'position' => $index + 1,
		'name' => $crumb['label'] ?? '',
		'item' => $crumb['url'] ?? null,
	];
}

$itemListSchema = [
	'@context' => 'https://schema.org',
	'@type' => 'ItemList',
	'name' => 'Eventi cosplay ' . $year . ' in Italia',
	'numberOfItems' => $eventCount,
	'itemListElement' => [],
];

foreach ($events as $event) {
	if (empty($event['slug'])) {
		continue;
	}

	$itemListSchema['itemListElement'][] = [
		'@type' => 'ListItem',
		'position' => count($itemListSchema['itemListElement']) + 1,
		'url' => $eventsBaseUrl . '/' . $event['slug'],
		'name' => $event['titolo'] ?? '',
	];
}
?>

<script type="application/ld+json"><?php echo json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<script type="application/ld+json"><?php echo json_encode($itemListSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

<main class="bg-slate-50 text-slate-900">
	<section class="bg-white">
		<div class="mx-auto max-w-7xl px-4 py-6 md:px-6 md:py-8">
			<nav class="mb-5 text-sm text-slate-600" aria-label="Breadcrumb">
				<ol class="flex flex-wrap items-center gap-2">
					<?php foreach ($breadcrumbs as $index => $crumb): ?>
						<li class="flex items-center gap-2">
							<?php if ($index > 0): ?>
								<span class="text-slate-300" aria-hidden="true">/</span>
							<?php endif; ?>
							<a href="<?php echo event_year_h($crumb['url'] ?? '#'); ?>" class="font-bold text-emerald-800 hover:text-emerald-950">
								<?php echo event_year_h($crumb['label'] ?? ''); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ol>
			</nav>

			<header class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-stretch">
				<div>
					<p class="text-sm font-bold uppercase tracking-wide text-emerald-700">Calendario annuale cosplay</p>
					<h1 class="mt-2 text-3xl font-black leading-tight text-slate-950 md:text-5xl">
						Eventi Cosplay <?php echo $year; ?> in Italia
					</h1>
					<p class="mt-4 max-w-3xl text-base leading-7 text-slate-700 md:text-lg">
						Il calendario degli eventi cosplay <?php echo $year; ?> in Italia, con fiere del fumetto, festival nerd, contest cosplay e appuntamenti dedicati alla community.
					</p>
					<div class="mt-5 flex flex-wrap gap-3">
						<a href="#calendario-mesi" class="inline-flex rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-emerald-800">Calendario per mese</a>
						<a href="/segnala-evento-cosplay" class="inline-flex rounded-xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm font-black text-amber-950 transition hover:bg-amber-100">Segnala un evento</a>
					</div>
				</div>
				<aside class="rounded-xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm" aria-label="Riepilogo anno">
					<p class="text-sm font-bold text-emerald-900">Eventi confermati</p>
					<p class="mt-1 text-5xl font-black text-slate-950"><?php echo $eventCount; ?></p>
					<p class="mt-3 text-sm leading-6 text-slate-700">
						<?php echo $regionCount; ?> regioni con eventi pubblicati e approvati.
					</p>
				</aside>
			</header>
		</div>
	</section>

	<div class="mx-auto max-w-7xl px-4 py-6 md:px-6 md:py-8">
		<section id="calendario-mesi" class="mb-8 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
			<div class="mb-4">
				<p class="text-sm font-bold uppercase tracking-wide text-emerald-700">Mesi disponibili</p>
				<h2 class="mt-1 text-2xl font-black text-slate-950">Eventi cosplay <?php echo $year; ?> mese per mese</h2>
			</div>
			<div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
				<?php foreach ($months as $month): ?>
					<a href="/eventi-cosplay-mese/<?php echo event_year_h($month['slug']); ?>" class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-emerald-300 hover:bg-emerald-50">
						<span class="block text-base font-black text-slate-950"><?php echo event_year_h($month['label']); ?></span>
						<span class="mt-1 block text-sm font-semibold text-emerald-800"><?php echo (int) $month['total']; ?> eventi</span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>

		<section aria-labelledby="eventi-anno">
			<div class="mb-5">
				<p class="text-sm font-bold uppercase tracking-wide text-emerald-700">Lista eventi</p>
				<h2 id="eventi-anno" class="mt-1 text-2xl font-black text-slate-950 md:text-4xl">
					Tutti gli eventi cosplay <?php echo $year; ?>
				</h2>
			</div>

			<div class="space-y-8">
				<?php foreach ($months as $month): ?>
					<?php $monthEvents = $eventsByMonth[(int) $month['month']] ?? []; ?>
					<?php if (empty($monthEvents)) continue; ?>
					<section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
						<div class="mb-4 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
							<div>
								<h3 class="text-2xl font-black text-slate-950"><?php echo event_year_h($month['label']); ?></h3>
								<p class="mt-1 text-sm text-slate-600"><?php echo (int) $month['total']; ?> eventi pubblicati</p>
							</div>
							<a href="/eventi-cosplay-mese/<?php echo event_year_h($month['slug']); ?>" class="text-sm font-bold text-emerald-800 hover:text-emerald-950">Vedi il mese</a>
						</div>
						<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
							<?php foreach ($monthEvents as $index => $event): ?>
								<?php
								$eventUrl = $eventsBaseUrl . '/' . ($event['slug'] ?? '');
								$imageUrl = $event['immagine'] ?? '';
								$dateLabel = event_year_date_label($event['data_inizio'] ?? null, $event['data_fine'] ?? null);
								$locationLabel = implode(' · ', array_filter([$event['comune_nome'] ?? '', $event['provincia_nome'] ?? '', $event['regione_nome'] ?? '']));
								$loading = $index < 2 ? 'eager' : 'lazy';
								?>
								<article class="group overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:border-emerald-300 hover:shadow-md focus-within:ring-2 focus-within:ring-emerald-700">
									<a href="<?php echo event_year_h($eventUrl); ?>" class="block">
										<div class="relative aspect-[16/10] bg-slate-100">
											<?php if ($imageUrl !== ''): ?>
												<img src="<?php echo event_year_h($imageUrl); ?>" width="<?php echo event_year_h($event['immagine_width'] ?? 800); ?>" height="<?php echo event_year_h($event['immagine_height'] ?? 500); ?>" loading="<?php echo $loading; ?>" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]" alt="<?php echo event_year_h(($event['titolo'] ?? 'Evento cosplay') . ' ' . $year); ?>">
											<?php else: ?>
												<div class="flex h-full w-full items-center justify-center px-4 text-center text-sm font-bold text-slate-500">Immagine evento non disponibile</div>
											<?php endif; ?>
										</div>
										<div class="p-4">
											<p class="text-sm font-bold text-emerald-800"><time datetime="<?php echo event_year_h($event['data_inizio'] ?? ''); ?>"><?php echo event_year_h($dateLabel); ?></time></p>
											<h4 class="mt-2 text-lg font-black leading-snug text-slate-950 group-hover:text-emerald-900"><?php echo event_year_h($event['titolo'] ?? 'Evento cosplay'); ?></h4>
											<?php if ($locationLabel !== ''): ?>
												<p class="mt-2 text-sm font-semibold text-slate-600"><?php echo event_year_h($locationLabel); ?></p>
											<?php endif; ?>
											<span class="mt-3 inline-flex text-sm font-bold text-emerald-800">Apri scheda evento</span>
										</div>
									</a>
								</article>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endforeach; ?>
			</div>
		</section>
	</div>
</main>
