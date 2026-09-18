<?php
use App\Helpers\AdPlacement;
$events = $data['events'] ?? [];
$nuovi = $data['nuovi'] ?? [];
$piuImportanti = $data['piuImportanti'] ?? [];
$top3 = $data['top3'] ?? [];
$breadcrumbs = $data['breadcrumbs'] ?? [];
$weekend = $data['weekend'] ?? '';
$eventiTopTitoli = $data['eventiTop_titoli'] ?? '';
$testoDescrittivo = $data['testo_descrittivo'] ?? '';

$siteBaseUrl = rtrim(URL_ROOT_SITE, '/');
$eventsBaseUrl = $siteBaseUrl . '/eventi-cosplay';
$weekendUrl = $siteBaseUrl . '/eventi-cosplay-weekend';
$monthUrl = $siteBaseUrl . '/eventi-cosplay-mese';
$pageTitle = 'Eventi cosplay nel weekend ' . $weekend;
$allVisibleEvents = array_values(array_filter(array_merge($top3, $piuImportanti, $nuovi, $events), function ($event) {
	return !empty($event['slug']);
}));
$hasEvents = !empty($events)
	|| !empty($top3)
	|| !empty($piuImportanti)
	|| !empty($nuovi);

if (!function_exists('event_weekend_h')) {
	function event_weekend_h($value): string
	{
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('event_weekend_absolute_url')) {
	function event_weekend_absolute_url(?string $url, string $siteBaseUrl): string
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

if (!function_exists('event_weekend_date_label')) {
	function event_weekend_date_label(?string $startDate, ?string $endDate = null): string
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

if (!function_exists('event_weekend_status')) {
	function event_weekend_status(?string $startDate, ?string $endDate = null): array
	{
		if (empty($startDate)) {
			return ['label' => 'Weekend', 'class' => 'bg-green-100 text-green-900'];
		}

		$today = new DateTimeImmutable('today');
		$start = new DateTimeImmutable(date('Y-m-d', strtotime($startDate)));
		$end = !empty($endDate) ? new DateTimeImmutable(date('Y-m-d', strtotime($endDate))) : $start;

		if ($today >= $start && $today <= $end) {
			return ['label' => 'Oggi', 'class' => 'bg-red-100 text-red-800'];
		}

		return ['label' => 'Questo weekend', 'class' => 'bg-amber-100 text-amber-900'];
	}
}

if (!function_exists('event_weekend_card')) {
	function event_weekend_card(array $event, string $variant, int $index, string $eventsBaseUrl, string $siteBaseUrl): string
	{
		$eventUrl = $eventsBaseUrl . '/' . ($event['slug'] ?? '');
		$imageUrl = event_weekend_absolute_url($event['immagine'] ?? '', $siteBaseUrl);
		$startDate = $event['data_inizio'] ?? null;
		$endDate = $event['data_fine'] ?? null;
		$dateLabel = event_weekend_date_label($startDate, $endDate);
		$status = event_weekend_status($startDate, $endDate);
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
			<a href="<?php echo event_weekend_h($eventUrl); ?>" class="block" title="<?php echo event_weekend_h($title); ?>">
				<div class="relative aspect-[16/10] bg-gray-200">
					<?php if ($imageUrl): ?>
						<img
							src="<?php echo event_weekend_h($imageUrl); ?>"
							width="<?php echo event_weekend_h($event['immagine_width'] ?? 800); ?>"
							height="<?php echo event_weekend_h($event['immagine_height'] ?? 500); ?>"
							loading="<?php echo $loading; ?>"
							fetchpriority="<?php echo $fetchPriority; ?>"
							class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
							alt="<?php echo event_weekend_h($title . ' evento cosplay nel weekend'); ?>"
						>
					<?php else: ?>
						<div class="flex h-full w-full items-center justify-center px-4 text-center text-sm font-semibold text-gray-500">
							Immagine evento non disponibile
						</div>
					<?php endif; ?>
					<span class="absolute left-3 top-3 rounded-full px-3 py-1 text-xs font-bold <?php echo event_weekend_h($badgeClass); ?>">
						<?php echo event_weekend_h($badge); ?>
					</span>
				</div>
				<div class="p-5">
					<p class="mb-2 text-sm font-bold text-green-900">
						<time datetime="<?php echo event_weekend_h($startDate ?? ''); ?>"><?php echo event_weekend_h($dateLabel); ?></time>
					</p>
					<h3 class="text-xl font-extrabold leading-snug text-gray-950 group-hover:text-green-900">
						<?php echo event_weekend_h($title); ?>
					</h3>
					<p class="mt-3 text-sm font-semibold text-gray-700">
						<?php echo event_weekend_h(implode(' · ', array_filter([$city, $province, $region]))); ?>
					</p>
					<?php if (!empty($event['tipo_evento_nome'])): ?>
						<p class="mt-2 inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
							<?php echo event_weekend_h($event['tipo_evento_nome']); ?>
						</p>
					<?php endif; ?>
					<?php if ($description): ?>
						<p class="mt-3 text-sm leading-relaxed text-gray-600">
							<?php echo event_weekend_h($description); ?>
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

<?php echo $data['SchemaListaEventi'] ?? ''; ?>
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
							<a href="<?php echo event_weekend_h($crumb['url']); ?>" class="font-medium text-green-900 hover:underline">
								<?php echo event_weekend_h($crumb['label'] ?? ''); ?>
							</a>
						<?php else: ?>
							<span><?php echo event_weekend_h($crumb['label'] ?? ''); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>

		<header class="mb-8 rounded-xl bg-white p-5 shadow-md md:p-8">
			<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
				<div>
					<p class="mb-2 text-sm font-bold uppercase tracking-wide text-green-900">
						Agenda cosplay del fine settimana
					</p>
					<h1 class="text-3xl font-extrabold leading-tight text-gray-950 md:text-5xl">
						<?php echo event_weekend_h($pageTitle); ?>
					</h1>
					<div class="mt-4 text-lg leading-relaxed text-gray-700">
						<?php if ($testoDescrittivo): ?>
							<p><?php echo nl2br(event_weekend_h($testoDescrittivo)); ?></p>
						<?php else: ?>
							<p>Questo weekend, <?php echo event_weekend_h($weekend); ?>, trovi fiere del fumetto, raduni cosplay, festival anime e manga e appuntamenti nerd in tutta Italia.</p>
						<?php endif; ?>
						<?php if ($eventiTopTitoli): ?>
							<p class="mt-3">
								Tra gli appuntamenti da tenere d'occhio: <?php echo event_weekend_h($eventiTopTitoli); ?>.
							</p>
						<?php endif; ?>
					</div>
				</div>

				<aside class="rounded-lg border border-green-100 bg-green-50 p-4" aria-label="Riepilogo weekend">
					<p class="text-sm font-semibold text-green-900">Eventi nel weekend</p>
					<p class="mt-1 text-4xl font-extrabold text-gray-950"><?php echo count($seenSlugs); ?></p>
					<p class="mt-2 text-sm text-gray-700">
						Schede evento con date, città, immagini, link ufficiali e consigli per scoprire altri appuntamenti.
					</p>
					<a href="/segnala-evento-cosplay" class="mt-4 inline-flex w-full items-center justify-center rounded-lg bg-green-800 px-4 py-3 font-bold text-white shadow hover:bg-green-900">
						Segnala un evento
					</a>
				</aside>
			</div>
		</header>

		<div class="mb-8">
			<?php $context = 'weekend'; require APP_ROOT . '/app/views/components/telegram-channel-cta.php'; ?>
		</div>

		<?php echo AdPlacement::render('events_weekend_specific_top', 'events_weekend_specific', 'mb-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md'); ?>

		<nav class="mb-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Navigazione rapida eventi">
			<a href="<?php echo event_weekend_h($eventsBaseUrl); ?>" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
				Tutti gli eventi cosplay
			</a>
			<a href="<?php echo event_weekend_h($monthUrl); ?>" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
				Eventi cosplay del mese
			</a>
			<a href="#top-eventi-weekend" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
				Top eventi weekend
			</a>
			<a href="#eventi-weekend" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
				Lista completa weekend
			</a>
		</nav>

		<?php if (!empty($top3)): ?>
			<section id="top-eventi-weekend" class="mb-10" aria-labelledby="top-eventi-heading">
				<h2 id="top-eventi-heading" class="mb-5 text-2xl font-bold text-gray-950">Top eventi cosplay del weekend</h2>
				<div class="grid grid-cols-1 gap-6 md:grid-cols-3">
					<?php foreach ($top3 as $index => $event): ?>
						<?php echo event_weekend_card($event, 'top', $index, $eventsBaseUrl, $siteBaseUrl); ?>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php echo AdPlacement::render('events_weekend_specific_inline', 'events_weekend_specific', 'mt-10 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md'); ?>

		<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_360px]">
			<section id="eventi-weekend" aria-labelledby="eventi-weekend-heading">
				<div class="mb-5">
					<h2 id="eventi-weekend-heading" class="text-2xl font-bold text-gray-950">
						Tutti gli eventi cosplay in programma questo weekend
					</h2>
					<p class="mt-1 text-gray-600">
						Apri una scheda per vedere programma, luogo, social ufficiali e altri eventi correlati.
					</p>
				</div>

				<?php if ($hasEvents): ?>
					<?php if (!empty($events)): ?>

						<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
							<?php foreach ($events as $index => $event): ?>
								<?php echo event_weekend_card(
									$event,
									'default',
									$index + 3,
									$eventsBaseUrl,
									$siteBaseUrl
								); ?>
							<?php endforeach; ?>
						</div>

					<?php else: ?>

						<div class="rounded-xl bg-white p-6 shadow-md text-center">
							<p class="text-gray-700">
								Non ci sono altri eventi oltre agli appuntamenti selezionati.
							</p>
						</div>

					<?php endif; ?>
				<?php endif; ?>
				<?php if (!$hasEvents): ?>

					<div class="rounded-xl bg-white p-8 text-center shadow-md">

						<h2 class="text-2xl font-bold text-gray-950">
							Nessun evento trovato per questo weekend
						</h2>

						<p class="mx-auto mt-3 max-w-2xl text-gray-700">
							Non ci sono ancora eventi cosplay pubblicati per il fine settimana selezionato.
							Esplora il calendario completo o segnala un nuovo evento.
						</p>

					</div>

				<?php endif; ?>
			</section>

			<aside class="space-y-6 lg:sticky lg:top-6 lg:self-start">
				<?php if (!empty($piuImportanti)): ?>
					<section class="rounded-xl bg-white p-5 shadow-md" aria-labelledby="eventi-importanti-heading">
						<h2 id="eventi-importanti-heading" class="mb-4 text-xl font-bold text-gray-950">Eventi più importanti</h2>
						<div class="space-y-4">
							<?php foreach ($piuImportanti as $index => $event): ?>
								<?php echo event_weekend_card($event, 'default', $index + 8, $eventsBaseUrl, $siteBaseUrl); ?>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php if (!empty($nuovi)): ?>
					<section class="rounded-xl bg-white p-5 shadow-md" aria-labelledby="eventi-crescita-heading">
						<h2 id="eventi-crescita-heading" class="mb-4 text-xl font-bold text-gray-950">Eventi in crescita</h2>
						<div class="space-y-4">
							<?php foreach ($nuovi as $index => $event): ?>
								<?php echo event_weekend_card($event, 'new', $index + 12, $eventsBaseUrl, $siteBaseUrl); ?>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<section class="rounded-xl bg-white p-5 shadow-md" aria-labelledby="link-utili-weekend">
					<h2 id="link-utili-weekend" class="mb-4 text-xl font-bold text-gray-950">Esplora altri eventi</h2>
					<ul class="space-y-3 text-sm">
						<li><a href="<?php echo event_weekend_h($eventsBaseUrl); ?>" class="font-semibold text-green-900 hover:underline">Tutti gli eventi cosplay in Italia</a></li>
						<li><a href="<?php echo event_weekend_h($monthUrl); ?>" class="font-semibold text-green-900 hover:underline">Eventi cosplay di questo mese</a></li>
						<li><a href="/segnala-evento-cosplay" class="font-semibold text-green-900 hover:underline">Segnala un evento cosplay</a></li>
					</ul>
				</section>
			</aside>
		</div>

		<section class="mt-12 rounded-xl bg-white p-5 shadow-md md:p-8" aria-labelledby="faq-weekend">
			<h2 id="faq-weekend" class="mb-5 text-2xl font-bold text-gray-950">Domande frequenti sugli eventi cosplay del weekend</h2>
			<div class="grid gap-5 md:grid-cols-3">
				<article>
					<h3 class="text-lg font-bold text-gray-950">Come scelgo un evento cosplay per il weekend?</h3>
					<p class="mt-2 text-sm leading-relaxed text-gray-700">Controlla data, città, programma e canali ufficiali nella scheda evento. Gli eventi top aiutano a individuare gli appuntamenti più rilevanti.</p>
				</article>
				<article>
					<h3 class="text-lg font-bold text-gray-950">Gli eventi del weekend sono aggiornati?</h3>
					<p class="mt-2 text-sm leading-relaxed text-gray-700">La pagina raccoglie gli eventi approvati e disponibili per il fine settimana. Verifica sempre eventuali modifiche sui canali ufficiali dell'organizzazione.</p>
				</article>
				<article>
					<h3 class="text-lg font-bold text-gray-950">Posso proporre un evento mancante?</h3>
					<p class="mt-2 text-sm leading-relaxed text-gray-700">Sì, puoi inviare fiere, raduni e contest tramite il modulo di segnalazione. Gli eventi vengono controllati prima della pubblicazione.</p>
				</article>
			</div>
		</section>

		<?php echo AdPlacement::render('events_weekend_specific_bottom', 'events_weekend_specific', 'mt-12 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md'); ?>
	</div>
</main>
