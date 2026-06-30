<?php
$top = $data['top'] ?? [];
$trending = $data['trending'] ?? [];
$new = $data['new'] ?? [];
$feed = $data['feed'] ?? [];

$siteBaseUrl = rtrim(URL_ROOT_SITE, '/');
$eventsBaseUrl = $siteBaseUrl . '/eventi-cosplay';
$monthUrl = $siteBaseUrl . '/eventi-cosplay-mese';
$weekendUrl = $siteBaseUrl . '/eventi-cosplay-weekend';
$reportUrl = $siteBaseUrl . '/segnala-evento-cosplay';
$allEvents = array_values(array_filter(array_merge($top, $feed, $trending, $new), function ($event) {
	return !empty($event['slug']);
}));

if (!function_exists('home_h')) {
	function home_h($value): string
	{
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('home_absolute_url')) {
	function home_absolute_url(?string $url, string $siteBaseUrl): string
	{
		if (empty($url)) {
			return '';
		}

		if (preg_match('/^https?:\/\//i', $url)) {
			return $url;
		}

		return $siteBaseUrl . '/' . ltrim($url, '/');
	}
}

if (!function_exists('home_date_label')) {
	function home_date_label(?string $startDate, ?string $endDate = null): string
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

if (!function_exists('home_event_status')) {
	function home_event_status(?string $startDate, ?string $endDate = null): array
	{
		if (empty($startDate)) {
			return ['label' => 'Prossimamente', 'class' => 'bg-green-100 text-green-900'];
		}

		$today = new DateTimeImmutable('today');
		$start = new DateTimeImmutable(date('Y-m-d', strtotime($startDate)));
		$end = !empty($endDate) ? new DateTimeImmutable(date('Y-m-d', strtotime($endDate))) : $start;

		if ($today >= $start && $today <= $end) {
			return ['label' => 'Oggi', 'class' => 'bg-red-100 text-red-800'];
		}

		$weekday = (int)$today->format('N');
		$saturday = $weekday >= 6 ? $today->modify('last saturday') : $today->modify('next saturday');
		$sunday = $saturday->modify('+1 day');

		if ($start <= $sunday && $end >= $saturday) {
			return ['label' => 'Questo weekend', 'class' => 'bg-amber-100 text-amber-900'];
		}

		return ['label' => 'Prossimamente', 'class' => 'bg-green-100 text-green-900'];
	}
}

if (!function_exists('home_event_card')) {
	function home_event_card(array $event, string $variant, int $index, string $eventsBaseUrl, string $siteBaseUrl): string
	{
		$eventUrl = $eventsBaseUrl . '/' . ($event['slug'] ?? '');
		$imageUrl = $event['immagine'];
		$startDate = $event['data_inizio'] ?? null;
		$endDate = $event['data_fine'] ?? null;
		$dateLabel = home_date_label($startDate, $endDate);
		$status = home_event_status($startDate, $endDate);
		$city = $event['comune_nome'] ?? '';
		$province = $event['provincia_nome'] ?? '';
		$region = $event['regione_nome'] ?? '';
		$year = $startDate ? date('Y', strtotime($startDate)) : '';
		$title = trim(($event['titolo'] ?? 'Evento cosplay') . ($city ? ' a ' . $city : '') . ($year ? ' ' . $year : ''));
		$description = mb_strimwidth(trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($event['descrizione'] ?? '', ENT_QUOTES, 'UTF-8')))), 0, 145, '...');
		$loading = $index < 2 ? 'eager' : 'lazy';
		$fetchPriority = $index === 0 ? 'high' : 'auto';
		$badge = $variant === 'top' ? 'Top evento' : ($variant === 'new' ? 'Nuovo evento' : ($variant === 'trending' ? 'Trending' : $status['label']));
		$badgeClass = $variant === 'top'
			? 'bg-amber-400 text-gray-950'
			: ($variant === 'new'
				? 'bg-green-800 text-white'
				: ($variant === 'trending' ? 'bg-sky-100 text-sky-900' : $status['class']));

		ob_start();
		?>
		<article class="group overflow-hidden rounded-xl bg-white shadow-md transition hover:-translate-y-0.5 hover:shadow-xl focus-within:ring-2 focus-within:ring-green-800">
			<a href="<?php echo home_h($eventUrl); ?>" class="block" title="<?php echo home_h($title); ?>">
				<div class="relative aspect-[16/10] bg-gray-200">
					<?php if ($imageUrl): ?>
						<img
							src="<?php echo home_h($imageUrl); ?>"
							width="<?php echo home_h($event['immagine_width'] ?? 800); ?>"
							height="<?php echo home_h($event['immagine_height'] ?? 500); ?>"
							loading="<?php echo $loading; ?>"
							fetchpriority="<?php echo $fetchPriority; ?>"
							class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
							alt="<?php echo home_h($title . ' evento cosplay'); ?>"
						>
					<?php else: ?>
						<div class="flex h-full w-full items-center justify-center px-4 text-center text-sm font-semibold text-gray-500">
							Immagine evento non disponibile
						</div>
					<?php endif; ?>
					<span class="absolute left-3 top-3 rounded-full px-3 py-1 text-xs font-bold <?php echo home_h($badgeClass); ?>">
						<?php echo home_h($badge); ?>
					</span>
				</div>
				<div class="p-5">
					<p class="mb-2 text-sm font-bold text-green-900">
						<time datetime="<?php echo home_h($startDate ?? ''); ?>"><?php echo home_h($dateLabel); ?></time>
					</p>
					<h3 class="text-xl font-extrabold leading-snug text-gray-950 group-hover:text-green-900">
						<?php echo home_h($title); ?>
					</h3>
					<p class="mt-3 text-sm font-semibold text-gray-700">
						<?php echo home_h(implode(' · ', array_filter([$city, $province, $region]))); ?>
					</p>
					<?php if (!empty($event['tipo_evento_nome'])): ?>
						<p class="mt-2 inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
							<?php echo home_h($event['tipo_evento_nome']); ?>
						</p>
					<?php endif; ?>
					<?php if ($description): ?>
						<p class="mt-3 text-sm leading-relaxed text-gray-600">
							<?php echo home_h($description); ?>
						</p>
					<?php endif; ?>
					<span class="mt-4 inline-flex font-bold text-green-900 group-hover:underline">
						Vedi dettagli evento
					</span>
				</div>
			</a>
		</article>
		<?php
		return ob_get_clean();
	}
}

if (!function_exists('home_compact_event_link')) {
	function home_compact_event_link(array $event, string $variant, int $index, string $eventsBaseUrl, string $siteBaseUrl): string
	{
		$eventUrl = $eventsBaseUrl . '/' . ($event['slug'] ?? '');
		$imageUrl = home_absolute_url($event['immagine'] ?? '', $siteBaseUrl);
		$title = $event['titolo'] ?? 'Evento cosplay';
		$dateLabel = home_date_label($event['data_inizio'] ?? null, $event['data_fine'] ?? null);
		$city = $event['comune_nome'] ?? '';

		ob_start();
		?>
		<a href="<?php echo home_h($eventUrl); ?>" class="flex gap-3 rounded-lg p-2 transition hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-800" title="<?php echo home_h($title); ?>">
			<?php if ($imageUrl): ?>
				<img
					src="<?php echo home_h($imageUrl); ?>"
					width="<?php echo home_h($event['immagine_width'] ?? 96); ?>"
					height="<?php echo home_h($event['immagine_height'] ?? 96); ?>"
					class="h-16 w-16 flex-none rounded-lg object-cover"
					loading="lazy"
					alt="<?php echo home_h($title . ' evento cosplay'); ?>"
				>
			<?php endif; ?>
			<span class="min-w-0">
				<span class="block text-sm font-bold leading-snug text-gray-950"><?php echo home_h($title); ?></span>
				<span class="mt-1 block text-xs text-gray-600"><?php echo home_h($dateLabel); ?><?php echo $city ? ' · ' . home_h($city) : ''; ?></span>
			</span>
		</a>
		<?php
		return ob_get_clean();
	}
}

$seenSlugs = [];
$itemListSchema = [
	'@context' => 'https://schema.org',
	'@type' => 'ItemList',
	'name' => 'ItalianCosplay - eventi cosplay in Italia',
	'numberOfItems' => count($allEvents),
	'itemListElement' => [],
];

foreach ($allEvents as $event) {
	$slug = $event['slug'] ?? '';
	if (!$slug || isset($seenSlugs[$slug])) {
		continue;
	}

	$seenSlugs[$slug] = true;
	$itemListSchema['itemListElement'][] = [
		'@type' => 'ListItem',
		'position' => count($itemListSchema['itemListElement']) + 1,
		'url' => $eventsBaseUrl . '/' . $slug,
		'name' => $event['titolo'] ?? '',
	];
}

$websiteSchema = [
	'@context' => 'https://schema.org',
	'@type' => 'WebSite',
	'name' => 'ItalianCosplay',
	'url' => $siteBaseUrl . '/',
	'potentialAction' => [
		'@type' => 'SearchAction',
		'target' => $eventsBaseUrl . '?q={search_term_string}',
		'query-input' => 'required name=search_term_string',
	],
];

$organizationSchema = [
	'@context' => 'https://schema.org',
	'@type' => 'Organization',
	'name' => 'ItalianCosplay',
	'url' => $siteBaseUrl . '/',
	'logo' => $siteBaseUrl . '/favicon-192x192.png',
];

$regionLinks = [
	['label' => 'Eventi cosplay in Lombardia', 'url' => $eventsBaseUrl . '/lombardia'],
	['label' => 'Eventi cosplay nel Lazio', 'url' => $eventsBaseUrl . '/lazio'],
	['label' => 'Eventi cosplay in Piemonte', 'url' => $eventsBaseUrl . '/piemonte'],
	['label' => 'Eventi cosplay in Emilia-Romagna', 'url' => $eventsBaseUrl . '/emilia-romagna'],
	['label' => 'Eventi cosplay in Veneto', 'url' => $eventsBaseUrl . '/veneto'],
	['label' => 'Eventi cosplay in Toscana', 'url' => $eventsBaseUrl . '/toscana'],
];
?>

<script type="application/ld+json"><?php echo json_encode($websiteSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<script type="application/ld+json"><?php echo json_encode($organizationSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<script type="application/ld+json"><?php echo json_encode($itemListSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

<main class="bg-gray-100">
	<section class="bg-white">
		<div class="mx-auto max-w-7xl px-4 py-8 md:py-12">
			<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-center">
				<div>
					<p class="mb-2 text-sm font-bold uppercase tracking-wide text-green-900">
						Calendario cosplay italiano
					</p>
					<h1 class="text-4xl font-extrabold leading-tight text-gray-950 md:text-6xl">
						Eventi cosplay in Italia
					</h1>
					<p class="mt-5 max-w-3xl text-lg leading-relaxed text-gray-700">
						Scopri fiere del fumetto, raduni cosplay, festival anime e manga, contest e appuntamenti nerd aggiornati. ItalianCosplay raccoglie gli eventi in programma in tutta Italia con schede, date, città e link utili.
					</p>
					<div class="mt-6 flex flex-col gap-3 sm:flex-row">
						<a href="<?php echo home_h($eventsBaseUrl); ?>" class="inline-flex items-center justify-center rounded-lg bg-green-800 px-5 py-3 font-bold text-white shadow hover:bg-green-900">
							Vedi tutti gli eventi
						</a>
						<a href="<?php echo home_h($reportUrl); ?>" class="inline-flex items-center justify-center rounded-lg border border-green-800 px-5 py-3 font-bold text-green-900 hover:bg-green-50">
							Segnala un evento
						</a>
					</div>
				</div>

				<aside class="rounded-xl border border-green-100 bg-green-50 p-5" aria-label="Riepilogo homepage">
					<p class="text-sm font-semibold text-green-900">Eventi in evidenza</p>
					<p class="mt-1 text-5xl font-extrabold text-gray-950"><?php echo count($seenSlugs); ?></p>
					<p class="mt-2 text-sm leading-relaxed text-gray-700">
						Una selezione dinamica di eventi top, nuovi e in crescita per aiutarti a scegliere il prossimo appuntamento.
					</p>
				</aside>
			</div>
		</div>
	</section>

	<div class="mx-auto max-w-7xl px-4 py-8">
		<nav class="mb-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Navigazione rapida eventi">
			<a href="<?php echo home_h($eventsBaseUrl); ?>" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">Tutti gli eventi cosplay</a>
			<a href="<?php echo home_h($monthUrl); ?>" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">Eventi cosplay del mese</a>
			<a href="<?php echo home_h($weekendUrl); ?>" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">Eventi cosplay nel weekend</a>
			<a href="#regioni-cosplay" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">Eventi per regione</a>
		</nav>

		<?php if (!empty($top)): ?>
			<section class="mb-10" aria-labelledby="top-eventi-home">
				<h2 id="top-eventi-home" class="mb-5 text-2xl font-bold text-gray-950">Top eventi cosplay da non perdere</h2>
				<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
					<?php foreach ($top as $index => $event): ?>
						<?php echo home_event_card($event, 'top', $index, $eventsBaseUrl, $siteBaseUrl); ?>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_360px]">
			<section aria-labelledby="feed-eventi-home">
				<div class="mb-5">
					<h2 id="feed-eventi-home" class="text-2xl font-bold text-gray-950">Scopri i prossimi eventi cosplay</h2>
					<p class="mt-1 text-gray-600">
						Apri una scheda evento per consultare data, luogo, programma, canali ufficiali e altri eventi correlati.
					</p>
				</div>

				<?php if (!empty($feed)): ?>
					<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
						<?php foreach ($feed as $index => $event): ?>
							<?php echo home_event_card($event, 'default', $index + 4, $eventsBaseUrl, $siteBaseUrl); ?>
						<?php endforeach; ?>
					</div>
				<?php else: ?>
					<div class="rounded-xl bg-white p-8 text-center shadow-md">
						<h2 class="text-2xl font-bold text-gray-950">Nessun evento disponibile</h2>
						<p class="mx-auto mt-3 max-w-2xl text-gray-700">
							Il calendario si aggiorna con nuovi eventi cosplay, fiere e raduni. Torna a controllare oppure segnala un appuntamento.
						</p>
						<a href="<?php echo home_h($reportUrl); ?>" class="mt-5 inline-flex rounded-lg bg-green-800 px-4 py-3 font-bold text-white hover:bg-green-900">
							Segnala un evento
						</a>
					</div>
				<?php endif; ?>
			</section>

			<aside class="space-y-6 lg:sticky lg:top-6 lg:self-start">
				<?php if (!empty($trending)): ?>
					<section class="rounded-xl bg-white p-5 shadow-md" aria-labelledby="trending-home">
						<h2 id="trending-home" class="mb-4 text-xl font-bold text-gray-950">Eventi trending</h2>
						<div class="space-y-2">
							<?php foreach ($trending as $index => $event): ?>
								<?php echo home_compact_event_link($event, 'trending', $index, $eventsBaseUrl, $siteBaseUrl); ?>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php if (!empty($new)): ?>
					<section class="rounded-xl bg-white p-5 shadow-md" aria-labelledby="nuovi-home">
						<h2 id="nuovi-home" class="mb-4 text-xl font-bold text-gray-950">Nuovi eventi pubblicati</h2>
						<div class="space-y-2">
							<?php foreach ($new as $index => $event): ?>
								<?php echo home_compact_event_link($event, 'new', $index, $eventsBaseUrl, $siteBaseUrl); ?>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<section id="regioni-cosplay" class="rounded-xl bg-white p-5 shadow-md" aria-labelledby="esplora-home">
					<h2 id="esplora-home" class="mb-4 text-xl font-bold text-gray-950">Esplora eventi cosplay</h2>
					<div class="space-y-3 text-sm">
						<a href="<?php echo home_h($eventsBaseUrl); ?>" class="block font-semibold text-green-900 hover:underline">Tutti gli eventi cosplay in Italia</a>
						<a href="<?php echo home_h($monthUrl); ?>" class="block font-semibold text-green-900 hover:underline">Eventi cosplay questo mese</a>
						<a href="<?php echo home_h($weekendUrl); ?>" class="block font-semibold text-green-900 hover:underline">Eventi cosplay nel weekend</a>
						<?php foreach ($regionLinks as $link): ?>
							<a href="<?php echo home_h($link['url']); ?>" class="block font-semibold text-green-900 hover:underline">
								<?php echo home_h($link['label']); ?>
							</a>
						<?php endforeach; ?>
					</div>
				</section>
			</aside>
		</div>

		<section class="mt-12 rounded-xl bg-white p-5 shadow-md md:p-8" aria-labelledby="come-funziona-home">
			<h2 id="come-funziona-home" class="text-2xl font-bold text-gray-950">Come usare ItalianCosplay</h2>
			<div class="mt-5 grid gap-5 md:grid-cols-3">
				<article>
					<h3 class="text-lg font-bold text-gray-950">Trova eventi vicino a te</h3>
					<p class="mt-2 text-sm leading-relaxed text-gray-700">Parti dalla pagina degli eventi e filtra per regione, provincia o comune per scoprire gli appuntamenti cosplay più vicini.</p>
				</article>
				<article>
					<h3 class="text-lg font-bold text-gray-950">Organizza il weekend</h3>
					<p class="mt-2 text-sm leading-relaxed text-gray-700">La pagina weekend raccoglie gli eventi concentrati nel fine settimana, utile per scegliere fiere e raduni last minute.</p>
				</article>
				<article>
					<h3 class="text-lg font-bold text-gray-950">Segnala nuovi eventi</h3>
					<p class="mt-2 text-sm leading-relaxed text-gray-700">Organizzatori e community possono inviare eventi cosplay, comics, anime, manga e gaming tramite il modulo dedicato.</p>
				</article>
			</div>
		</section>
	</div>
</main>
