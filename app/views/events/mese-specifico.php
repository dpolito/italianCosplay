<?php
use App\Helpers\AdPlacement;
$events = $data['events'] ?? [];
$nuovi = $data['nuovi'] ?? [];
$piuImportanti = $data['piuImportanti'] ?? [];
$top3 = $data['top3'] ?? [];
$breadcrumbs = $data['breadcrumbs'] ?? [];
$mese = $data['mese'] ?? '';
$testoDescrittivo = $data['testo_descrittivo'] ?? '';
$weekendDelMese = $data['weekendDelMese'] ?? '';
$eventCount = (int) ($data['eventCount'] ?? 0);

$siteBaseUrl = rtrim(URL_ROOT_SITE, '/');
$eventsBaseUrl = $siteBaseUrl . '/eventi-cosplay';
$monthUrl = $siteBaseUrl . '/eventi-cosplay-mese';
$weekendUrl = $siteBaseUrl . '/eventi-cosplay-weekend';
$pageTitle = 'Eventi cosplay ' . $mese;
$allVisibleEvents = array_values(array_filter(array_merge($top3, $piuImportanti, $nuovi, $events), function ($event) {
	return !empty($event['slug']);
}));

if (!function_exists('event_month_h')) {
	function event_month_h($value): string
	{
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('event_month_absolute_url')) {
	function event_month_absolute_url(?string $url, string $siteBaseUrl): string
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

if (!function_exists('event_month_date_label')) {
	function event_month_date_label(?string $startDate, ?string $endDate = null): string
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

if (!function_exists('event_month_status')) {
	function event_month_status(?string $startDate, ?string $endDate = null): array
	{
		if (empty($startDate)) {
			return ['label' => 'Questo mese', 'class' => 'bg-green-100 text-green-900'];
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

		return ['label' => 'Questo mese', 'class' => 'bg-green-100 text-green-900'];
	}
}

if (!function_exists('event_month_card')) {
	function event_month_card(array $event, string $variant, int $index, string $eventsBaseUrl, string $siteBaseUrl): string
	{
		$eventUrl = $eventsBaseUrl . '/' . ($event['slug'] ?? '');
		$imageUrl = $event['immagine'];
		$startDate = $event['data_inizio'] ?? null;
		$endDate = $event['data_fine'] ?? null;
		$dateLabel = event_month_date_label($startDate, $endDate);
		$status = event_month_status($startDate, $endDate);
		$city = $event['comune_nome'] ?? '';
		$province = $event['provincia_nome'] ?? '';
		$region = $event['regione_nome'] ?? '';
		$year = $startDate ? date('Y', strtotime($startDate)) : '';
		$title = trim(($event['titolo'] ?? 'Evento cosplay') . ($city ? ' a ' . $city : '') . ($year ? ' ' . $year : ''));
		$description = mb_strimwidth(trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($event['descrizione'] ?? '', ENT_QUOTES, 'UTF-8')))), 0, 145, '...');
		$loading = $index < 2 ? 'eager' : 'lazy';
		$fetchPriority = $index === 0 ? 'high' : 'auto';
		$badge = $variant === 'top' ? 'Top evento' : ($variant === 'new' ? 'In crescita' : $status['label']);
		$badgeClass = $variant === 'top' ? 'bg-amber-400 text-gray-950' : ($variant === 'new' ? 'bg-green-800 text-white' : $status['class']);

		ob_start();
		?>
		<article class="group overflow-hidden rounded-xl bg-white shadow-md transition hover:-translate-y-0.5 hover:shadow-xl focus-within:ring-2 focus-within:ring-green-800">
			<a href="<?php echo event_month_h($eventUrl); ?>" class="block" title="<?php echo event_month_h($title); ?>">
				<div class="relative aspect-[16/10] bg-gray-200">
					<?php if ($imageUrl): ?>
						<img
								src="<?php echo event_month_h($imageUrl); ?>"
								width="<?php echo event_month_h($event['immagine_width'] ?? 800); ?>"
								height="<?php echo event_month_h($event['immagine_height'] ?? 500); ?>"
								loading="<?php echo $loading; ?>"
								fetchpriority="<?php echo $fetchPriority; ?>"
								class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
								alt="<?php echo event_month_h($title . ' evento cosplay del mese'); ?>"
						>
					<?php else: ?>
						<div class="flex h-full w-full items-center justify-center px-4 text-center text-sm font-semibold text-gray-500">
							Immagine evento non disponibile
						</div>
					<?php endif; ?>
					<span class="absolute left-3 top-3 rounded-full px-3 py-1 text-xs font-bold <?php echo event_month_h($badgeClass); ?>">
						<?php echo event_month_h($badge); ?>
					</span>
				</div>
				<div class="p-5">
					<p class="mb-2 text-sm font-bold text-green-900">
						<time datetime="<?php echo event_month_h($startDate ?? ''); ?>"><?php echo event_month_h($dateLabel); ?></time>
					</p>
					<h3 class="text-xl font-extrabold leading-snug text-gray-950 group-hover:text-green-900">
						<?php echo event_month_h($title); ?>
					</h3>
					<p class="mt-3 text-sm font-semibold text-gray-700">
						<?php echo event_month_h(implode(' · ', array_filter([$city, $province, $region]))); ?>
					</p>
					<?php if (!empty($event['tipo_evento_nome'])): ?>
						<p class="mt-2 inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
							<?php echo event_month_h($event['tipo_evento_nome']); ?>
						</p>
					<?php endif; ?>
					<?php if ($description): ?>
						<p class="mt-3 text-sm leading-relaxed text-gray-600">
							<?php echo event_month_h($description); ?>
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
	'name' => $pageTitle,
	'numberOfItems' => count($allVisibleEvents),
	'itemListElement' => [],
];

$seenSlugs = [];
foreach ($allVisibleEvents as $event) {
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
?>

<script type="application/ld+json"><?php echo json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<script type="application/ld+json"><?php echo json_encode($itemListSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

<main class="bg-gray-100">
	<div class="container mx-auto px-4 py-6 md:px-6">
		<nav class="mb-5 text-sm text-gray-700" aria-label="Breadcrumb">
			<ol class="flex flex-wrap items-center gap-2">
				<?php foreach ($breadcrumbs as $index => $crumb): ?>
					<li class="flex items-center gap-2">
						<?php if ($index > 0): ?>
							<span class="text-gray-400" aria-hidden="true">/</span>
						<?php endif; ?>
						<?php if (!empty($crumb['url'])): ?>
							<a href="<?php echo event_month_h($crumb['url']); ?>" class="font-medium text-green-900 hover:underline">
								<?php echo event_month_h($crumb['label'] ?? ''); ?>
							</a>
						<?php else: ?>
							<span><?php echo event_month_h($crumb['label'] ?? ''); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>

		<header class="mb-8 rounded-xl bg-white p-5 shadow-md md:p-8">
			<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
				<div>
					<p class="mb-2 text-sm font-bold uppercase tracking-wide text-green-900">
						Calendario mensile cosplay
					</p>
					<h1 class="text-3xl font-extrabold leading-tight text-gray-950 md:text-5xl">
						<?php echo event_month_h($pageTitle); ?>
					</h1>
					<div class="mt-4 text-lg leading-relaxed text-gray-700">
						<?php if ($testoDescrittivo): ?>
							<?php echo $testoDescrittivo; ?>
						<?php else: ?>
							<p>Scopri gli eventi cosplay in programma nel mese di <?php echo event_month_h($mese); ?>: fiere del fumetto, raduni, contest cosplay, festival anime e appuntamenti nerd in tutta Italia.</p>
						<?php endif; ?>
					</div>
				</div>

				<aside class="rounded-lg border border-green-100 bg-green-50 p-4" aria-label="Riepilogo mese">
					<p class="text-sm font-semibold text-green-900">Eventi del mese</p>
					<p class="mt-1 text-4xl font-extrabold text-gray-950"><?php echo $eventCount; ?></p>
					<p class="mt-2 text-sm text-gray-700">
						Eventi con date, località, immagini, dettagli e link alle schede complete.
					</p>
					<a href="/segnala-evento-cosplay" class="mt-4 inline-flex w-full items-center justify-center rounded-lg bg-green-800 px-4 py-3 font-bold text-white shadow hover:bg-green-900">
						Segnala un evento
					</a>
				</aside>
			</div>
		</header>

		<?php echo AdPlacement::render('events_month_specific_top', 'events_month_specific', 'mb-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md'); ?>

		<nav class="mb-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Navigazione rapida eventi">
			<a href="<?php echo event_month_h($eventsBaseUrl); ?>" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
				Tutti gli eventi cosplay
			</a>
			<a href="<?php echo event_month_h($weekendUrl); ?>" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
				Eventi cosplay del weekend
			</a>
			<a href="#top-eventi-mese" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
				Top eventi del mese
			</a>
			<a href="#eventi-mese" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
				Lista completa del mese
			</a>
		</nav>

		<?php if (!empty($top3)): ?>
			<section id="top-eventi-mese" class="mb-10" aria-labelledby="top-eventi-mese-heading">
				<h2 id="top-eventi-mese-heading" class="mb-5 text-2xl font-bold text-gray-950">Top eventi cosplay del mese</h2>
				<div class="grid grid-cols-1 gap-6 md:grid-cols-3">
					<?php foreach ($top3 as $index => $event): ?>
						<?php echo event_month_card($event, 'top', $index, $eventsBaseUrl, $siteBaseUrl); ?>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php echo AdPlacement::render('events_month_specific_inline', 'events_month_specific', 'mt-10 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md'); ?>

		<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_360px]">
			<section id="eventi-mese" aria-labelledby="eventi-mese-heading">
				<div class="mb-5">
					<h2 id="eventi-mese-heading" class="text-2xl font-bold text-gray-950">
						Tutti gli eventi cosplay in programma questo mese
					</h2>
					<p class="mt-1 text-gray-600">
						Apri una scheda evento per consultare data, luogo, programma, social ufficiali e altri appuntamenti correlati.
					</p>
				</div>

				<?php if (!empty($events)): ?>
					<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
						<?php foreach ($events as $index => $event): ?>
							<?php echo event_month_card($event, 'default', $index + 3, $eventsBaseUrl, $siteBaseUrl); ?>
						<?php endforeach; ?>
					</div>
				<?php else: ?>
					<div class="rounded-xl bg-white p-8 text-center shadow-md">
						<h2 class="text-2xl font-bold text-gray-950">Nessun evento trovato per questo mese</h2>
						<p class="mx-auto mt-3 max-w-2xl text-gray-700">
							Non ci sono ancora eventi cosplay pubblicati per il mese selezionato. Esplora il calendario completo o segnala un nuovo evento.
						</p>
						<div class="mt-5 flex flex-col justify-center gap-3 sm:flex-row">
							<a href="<?php echo event_month_h($eventsBaseUrl); ?>" class="rounded-lg bg-green-800 px-4 py-3 font-bold text-white hover:bg-green-900">
								Vedi tutti gli eventi
							</a>
							<a href="/segnala-evento-cosplay" class="rounded-lg border border-green-800 px-4 py-3 font-bold text-green-900 hover:bg-green-50">
								Segnala un evento
							</a>
						</div>
					</div>
				<?php endif; ?>
			</section>

			<aside class="space-y-6 lg:sticky lg:top-6 lg:self-start">
				<?php if (!empty($piuImportanti)): ?>
					<section class="rounded-xl bg-white p-5 shadow-md" aria-labelledby="eventi-importanti-mese-heading">
						<h2 id="eventi-importanti-mese-heading" class="mb-4 text-xl font-bold text-gray-950">Eventi più importanti</h2>
						<div class="space-y-4">
							<?php foreach ($piuImportanti as $index => $event): ?>
								<?php echo event_month_card($event, 'default', $index + 8, $eventsBaseUrl, $siteBaseUrl); ?>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php if (!empty($nuovi)): ?>
					<section class="rounded-xl bg-white p-5 shadow-md" aria-labelledby="eventi-crescita-mese-heading">
						<h2 id="eventi-crescita-mese-heading" class="mb-4 text-xl font-bold text-gray-950">Eventi in crescita</h2>
						<div class="space-y-4">
							<?php foreach ($nuovi as $index => $event): ?>
								<?php echo event_month_card($event, 'new', $index + 12, $eventsBaseUrl, $siteBaseUrl); ?>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<section class="rounded-xl bg-white p-5 shadow-md" aria-labelledby="link-utili-mese">
					<h2 id="link-utili-mese" class="mb-4 text-xl font-bold text-gray-950">Esplora altri eventi</h2>
					<ul class="space-y-3 text-sm">
						<li><a href="<?php echo event_month_h($eventsBaseUrl); ?>" class="font-semibold text-green-900 hover:underline">Tutti gli eventi cosplay in Italia</a></li>
						<li><a href="<?php echo event_month_h($weekendUrl); ?>" class="font-semibold text-green-900 hover:underline">Eventi cosplay nel weekend</a></li>
						<li><a href="/segnala-evento-cosplay" class="font-semibold text-green-900 hover:underline">Segnala un evento cosplay</a></li>
					</ul>
				</section>

			</aside>
			<?php if (!empty($weekendDelMese)): ?>
				<section class="mb-10 rounded-xl bg-white p-6 shadow-md">
					<h2 class="mb-4 text-2xl font-bold text-gray-950">
						Weekend cosplay di <?php echo event_month_h($mese); ?>
					</h2>
					<div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
						<?php foreach($weekendDelMese as $weekend): ?>
							<a href="<?php echo $weekendUrl . '/' . $weekend['slug']; ?>"
							   class="rounded-lg bg-green-50 p-4 font-bold text-green-900 hover:bg-green-100">
								<?php echo event_month_h($weekend['label']); ?>
							</a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>
		</div>



		<section class="mt-12 rounded-xl bg-white p-5 shadow-md md:p-8" aria-labelledby="faq-mese">
			<h2 id="faq-mese" class="mb-5 text-2xl font-bold text-gray-950">Domande frequenti sugli eventi cosplay del mese</h2>
			<div class="grid gap-5 md:grid-cols-3">
				<article>
					<h3 class="text-lg font-bold text-gray-950">Come trovo gli eventi cosplay del mese?</h3>
					<p class="mt-2 text-sm leading-relaxed text-gray-700">Consulta la lista completa e apri le schede evento per vedere città, data, luogo e canali ufficiali dell'organizzazione.</p>
				</article>
				<article>
					<h3 class="text-lg font-bold text-gray-950">Gli eventi mensili sono ordinati per importanza?</h3>
					<p class="mt-2 text-sm leading-relaxed text-gray-700">La pagina evidenzia top eventi, appuntamenti importanti e nuovi eventi in crescita, così puoi scoprire sia grandi fiere sia raduni locali.</p>
				</article>
				<article>
					<h3 class="text-lg font-bold text-gray-950">Posso inserire un evento mancante?</h3>
					<p class="mt-2 text-sm leading-relaxed text-gray-700">Sì, puoi segnalare fiere, contest, festival e raduni cosplay tramite il modulo dedicato. Ogni evento viene verificato prima della pubblicazione.</p>
				</article>
			</div>
		</section>

		<?php echo AdPlacement::render('events_month_specific_bottom', 'events_month_specific', 'mt-12 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md'); ?>
	</div>
</main>
