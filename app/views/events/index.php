<?php
$events = $data['events'] ?? [];
$allRegioni = $data['all_regioni'] ?? [];
$allProvince = $data['all_province'] ?? [];
$allComuni = $data['all_comuni'] ?? [];
$selectedRegione = $data['selected_regione'] ?? null;
$selectedProvincia = $data['selected_provincia'] ?? null;
$selectedComune = $data['selected_comune'] ?? null;
$breadcrumbs = $data['breadcrumbs'] ?? [];
$provinceCorrelate = $data['provinceCorrelate'] ?? [];

$siteBaseUrl = rtrim(URL_ROOT_SITE, '/');
$eventsBasePath = $siteBaseUrl . '/eventi-cosplay';
$currentLabel = $breadcrumbs ? ($breadcrumbs[array_key_last($breadcrumbs)]['label'] ?? 'Eventi Cosplay Italia') : 'Eventi Cosplay Italia';
$locationName = $selectedComune['nome'] ?? $selectedProvincia['nome'] ?? $selectedRegione['nome'] ?? 'Italia';
$pageTitle = $currentLabel;
$eventCount = count($events);
$currentLocationFavorite = null;
$featureFlags = (new \App\Services\SiteFeatureFlagService())->getEnabledMap();

if (!empty($selectedComune)) {
	$currentLocationFavorite = [
		'label' => 'Salva questo comune',
		'entity_type' => 'comune',
		'entity_id' => (int) ($selectedComune['id'] ?? 0),
		'url' => $eventsBasePath . '/' . rawurlencode((string) ($selectedRegione['slug'] ?? '')) . '/' . rawurlencode((string) ($selectedProvincia['slug'] ?? '')) . '/' . rawurlencode((string) ($selectedComune['slug'] ?? '')),
		'description' => ($selectedComune['nome'] ?? 'Comune') . (!empty($selectedProvincia['nome']) ? ' · ' . $selectedProvincia['nome'] : ''),
	];
} elseif (!empty($selectedProvincia)) {
	$currentLocationFavorite = [
		'label' => 'Salva questa provincia',
		'entity_type' => 'provincia',
		'entity_id' => (int) ($selectedProvincia['id'] ?? 0),
		'url' => $eventsBasePath . '/' . rawurlencode((string) ($selectedRegione['slug'] ?? '')) . '/' . rawurlencode((string) ($selectedProvincia['slug'] ?? '')),
		'description' => ($selectedProvincia['nome'] ?? 'Provincia') . (!empty($selectedRegione['nome']) ? ' · ' . $selectedRegione['nome'] : ''),
	];
} elseif (!empty($selectedRegione)) {
	$currentLocationFavorite = [
		'label' => 'Salva questa regione',
		'entity_type' => 'regione',
		'entity_id' => (int) ($selectedRegione['id'] ?? 0),
		'url' => $eventsBasePath . '/' . rawurlencode((string) ($selectedRegione['slug'] ?? '')),
		'description' => $selectedRegione['nome'] ?? 'Regione',
	];
}

if (!function_exists('event_index_h')) {
	function event_index_h($value): string
	{
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('event_index_absolute_url')) {
	function event_index_absolute_url(?string $url, string $siteBaseUrl): string
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

if (!function_exists('event_index_date_label')) {
	function event_index_date_label(?string $startDate, ?string $endDate = null): string
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

if (!function_exists('event_index_status')) {
	function event_index_status(?string $startDate, ?string $endDate = null): array
	{
		if (empty($startDate)) {
			return ['label' => 'Prossimamente', 'class' => 'bg-gray-100 text-gray-800'];
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

if (!function_exists('event_index_location_url')) {
	function event_index_location_url(string $eventsBasePath, ?array $regione, ?array $provincia = null, ?array $comune = null): string
	{
		$url = $eventsBasePath;
		if (!empty($regione['slug'])) {
			$url .= '/' . rawurlencode($regione['slug']);
		}
		if (!empty($provincia['slug'])) {
			$url .= '/' . rawurlencode($provincia['slug']);
		}
		if (!empty($comune['slug'])) {
			$url .= '/' . rawurlencode($comune['slug']);
		}

		return $url;
	}
}

$introFallback = "Scopri i prossimi eventi cosplay in {$locationName}: fiere del fumetto, raduni, festival anime e manga, contest cosplay e appuntamenti nerd aggiornati per data e localita.";
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
	'numberOfItems' => $eventCount,
	'itemListElement' => [],
];

foreach (array_slice($events, 0, 24) as $index => $event) {
	$itemListSchema['itemListElement'][] = [
		'@type' => 'ListItem',
		'position' => $index + 1,
		'url' => $eventsBasePath . '/' . ($event['slug'] ?? ''),
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
							<a href="<?php echo event_index_h($crumb['url']); ?>" class="font-medium text-green-900 hover:underline">
								<?php echo event_index_h($crumb['label'] ?? ''); ?>
							</a>
						<?php else: ?>
							<span><?php echo event_index_h($crumb['label'] ?? ''); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>

		<header class="mb-8 rounded-xl bg-white p-5 shadow-md md:p-8">
			<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start">
				<div>
					<p class="mb-2 text-sm font-bold uppercase tracking-wide text-green-900">
						Calendario eventi cosplay
					</p>
					<h1 class="text-3xl font-extrabold leading-tight text-gray-950 md:text-5xl">
						<?php echo event_index_h($pageTitle); ?>
					</h1>
					<div class="mt-5 flex flex-wrap gap-3">
						<a href="#eventi-cosplay" class="inline-flex items-center rounded-full bg-green-800 px-5 py-3 text-sm font-bold text-white shadow hover:bg-green-900">
							Vedi gli eventi
						</a>
						<a href="#filtra-eventi" class="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-5 py-3 text-sm font-bold text-green-900 hover:bg-green-100">
							Filtra per località
						</a>
					</div>

					<div class="mt-5 rounded-2xl border border-gray-200 bg-gray-50 p-4">
						<div class="max-w-4xl text-sm leading-7 text-gray-700 md:text-base">
							<div id="eventi-descrizione-corta">
								<?php
								$introText = !empty($data['testo_descrittivo'])
									? strip_tags((string) $data['testo_descrittivo'])
									: $introFallback;
								$introShort = mb_strimwidth(trim(preg_replace('/\s+/', ' ', $introText)), 0, 260, '...');
								?>
								<p><?php echo event_index_h($introShort); ?></p>
							</div>
							<div id="eventi-descrizione-completa" class="hidden">
								<?php if (!empty($data['testo_descrittivo'])): ?>
									<?php echo $data['testo_descrittivo']; ?>
								<?php else: ?>
									<p><?php echo event_index_h($introFallback); ?></p>
								<?php endif; ?>
							</div>
						</div>
						<button
							type="button"
							class="mt-3 inline-flex items-center rounded-full border border-green-200 bg-white px-4 py-2 text-sm font-bold text-green-900 hover:bg-green-50"
							data-toggle-text
							data-label-show="Mostra tutto"
							data-label-hide="Mostra meno"
							aria-expanded="false"
							aria-controls="eventi-descrizione-completa"
						>
							Mostra tutto
						</button>
					</div>
				</div>

				<div class="grid gap-4 lg:grid-cols-2">
					<aside class="rounded-lg border border-green-100 bg-green-50 p-4" aria-label="Riepilogo eventi">
						<p class="text-sm font-semibold text-green-900">Eventi trovati</p>
						<p class="mt-1 text-4xl font-extrabold text-gray-950"><?php echo (int)$eventCount; ?></p>
						<p class="mt-2 text-sm text-gray-700">
							<?php echo event_index_h($locationName); ?>, aggiornati con date, luoghi e schede evento.
						</p>
						<a href="/segnala-evento-cosplay" class="mt-4 inline-flex w-full items-center justify-center rounded-lg bg-green-800 px-4 py-3 font-bold text-white shadow hover:bg-green-900">
							Segnala un evento
						</a>
					</aside>
					<?php if (!empty($featureFlags['enable_favorites']) && !empty($_SESSION['user_id']) && !empty($currentLocationFavorite)): ?>
						<aside class="rounded-lg border border-amber-100 bg-amber-50 p-4" aria-label="Preferiti location">
							<p class="text-sm font-semibold text-amber-900">Salva la località</p>
							<p class="mt-1 text-sm text-gray-700">Regione, provincia e comune possono essere salvati separatamente.</p>
							<div class="mt-4">
								<form method="post" action="/dashboard/favorites/toggle" class="js-favorite-toggle">
									<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
									<input type="hidden" name="entity_type" value="<?php echo event_index_h($currentLocationFavorite['entity_type']); ?>">
									<input type="hidden" name="entity_id" value="<?php echo (int) $currentLocationFavorite['entity_id']; ?>">
									<input type="hidden" name="redirect_to" value="<?php echo event_index_h($currentLocationFavorite['url']); ?>">
									<button type="submit" class="js-favorite-button inline-flex w-full items-center justify-center gap-2 rounded-lg border border-amber-300 bg-white px-4 py-3 text-sm font-bold text-amber-900 transition hover:bg-amber-100" data-label-add="Salva tra i preferiti" data-label-remove="Rimuovi dai preferiti" data-icon-add="fa-bookmark" data-icon-remove="fa-bookmark-slash" data-active="0">
										<i class="fa-solid fa-bookmark" aria-hidden="true"></i>
										<span>Salva <?php echo event_index_h($currentLocationFavorite['label'] ?? 'location'); ?></span>
									</button>
								</form>
							</div>
						</aside>
					<?php endif; ?>
				</div>
			</div>
		</header>

		<?php
		$eventsTopPlacement = null;
		$eventsInlinePlacement = null;

		if (empty($selectedComune)) {
			$eventsTopPlacement = 'events_national_top';
			$eventsInlinePlacement = 'events_national_inline';
			$adContext = ['page_type' => 'national'];

			if (!empty($selectedProvincia)) {
				$eventsTopPlacement = 'events_province_top';
				$eventsInlinePlacement = 'events_province_inline';
				$adContext = [
					'page_type' => 'province',
					'regione_slug' => $selectedRegione['slug'] ?? null,
					'provincia_slug' => $selectedProvincia['slug'] ?? null,
				];
			} elseif (!empty($selectedRegione)) {
				$eventsTopPlacement = 'events_region_top';
				$eventsInlinePlacement = 'events_region_inline';
				$adContext = [
					'page_type' => 'region',
					'regione_slug' => $selectedRegione['slug'] ?? null,
				];
			}

			echo \App\Helpers\AdPlacement::render($eventsTopPlacement, 'events', 'mb-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md', '', $adContext);
		}
		?>

		<nav class="mb-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Navigazione rapida eventi">
			<a href="<?php echo event_index_h($eventsBasePath); ?>" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
				Tutti gli eventi cosplay
			</a>
			<a href="<?php echo event_index_h($eventsBasePath . '-mese'); ?>" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
				Eventi cosplay del mese
			</a>
			<a href="<?php echo event_index_h($eventsBasePath . '-weekend'); ?>" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
				Eventi cosplay nel weekend
			</a>
			<?php if ($selectedRegione): ?>
				<a href="<?php echo event_index_h(event_index_location_url($eventsBasePath, $selectedRegione)); ?>" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
					Prossimi eventi in <?php echo event_index_h($selectedRegione['nome']); ?>
				</a>
			<?php else: ?>
				<a href="#eventi-cosplay" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
					Vedi prossimi eventi
				</a>
			<?php endif; ?>
		</nav>

		<?php if (!empty($eventsInlinePlacement)): ?>
			<?php echo \App\Helpers\AdPlacement::render($eventsInlinePlacement, 'events', 'mb-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md', '', $adContext ?? ['page_type' => 'national']); ?>
		<?php endif; ?>

		<section id="filtra-eventi" class="relative z-30 mb-8 rounded-xl bg-green-950 p-4 shadow-lg md:sticky md:top-4 md:p-6">
			<h2 class="mb-4 text-xl font-bold text-white">Filtra eventi cosplay per località</h2>
			<div class="grid grid-cols-1 gap-4 md:grid-cols-3">
				<div>
					<label for="filter_regione" class="mb-1 block text-sm font-semibold text-white">Regione</label>
					<select id="filter_regione" name="regione_id" class="block w-full rounded-md border-gray-300 bg-white px-3 py-3 text-base text-gray-900 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500">
						<option value="">Tutte le regioni</option>
						<?php foreach ($allRegioni as $regione): ?>
							<option value="<?php echo event_index_h($regione['slug']); ?>" <?php echo ($selectedRegione && $selectedRegione['id'] == $regione['id']) ? 'selected' : ''; ?>>
								<?php echo event_index_h($regione['nome']); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label for="filter_provincia" class="mb-1 block text-sm font-semibold text-white">Provincia</label>
					<select id="filter_provincia" name="provincia_id" class="block w-full rounded-md border-gray-300 bg-white px-3 py-3 text-base text-gray-900 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500" <?php echo (!$selectedRegione) ? 'disabled' : ''; ?>>
						<option value="">Tutte le province</option>
						<?php foreach ($allProvince as $provincia): ?>
							<option value="<?php echo event_index_h($provincia['slug']); ?>" <?php echo ($selectedProvincia && $selectedProvincia['id'] == $provincia['id']) ? 'selected' : ''; ?>>
								<?php echo event_index_h($provincia['nome']); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label for="filter_comune" class="mb-1 block text-sm font-semibold text-white">Comune</label>
					<select id="filter_comune" name="comune_id" class="block w-full rounded-md border-gray-300 bg-white px-3 py-3 text-base text-gray-900 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500" <?php echo (!$selectedProvincia) ? 'disabled' : ''; ?>>
						<option value="">Tutti i comuni</option>
						<?php foreach ($allComuni as $comune): ?>
							<option value="<?php echo event_index_h($comune['slug']); ?>" <?php echo ($selectedComune && $selectedComune['id'] == $comune['id']) ? 'selected' : ''; ?>>
								<?php echo event_index_h($comune['nome']); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</section>

		<section id="eventi-cosplay" aria-labelledby="lista-eventi">
			<div class="mb-5 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
				<div>
					<h2 id="lista-eventi" class="text-2xl font-bold text-gray-950">
						Prossimi eventi cosplay <?php echo $locationName !== 'Italia' ? 'in ' . event_index_h($locationName) : 'in Italia'; ?>
					</h2>
					<p class="mt-1 text-gray-600">
						Apri una scheda evento per scoprire programma, date, indirizzo, social e altri eventi correlati.
					</p>
				</div>
			</div>

			<?php if (!empty($events)): ?>
				<div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
					<?php foreach ($events as $index => $event): ?>
						<?php
					//var_dump($event);
						$eventUrl = $eventsBasePath . '/' . ($event['slug'] ?? '');
						$imageUrl = $event['immagine'];
						$startDate = $event['data_inizio'] ?? null;
						$endDate = $event['data_fine'] ?? null;
						$dateLabel = event_index_date_label($startDate, $endDate);
						$status = event_index_status($startDate, $endDate);
						$eventCity = $event['comune_nome'] ?? '';
						$eventRegion = $event['regione_nome'] ?? '';
						$eventYear = $startDate ? date('Y', strtotime($startDate)) : '';
						$cardTitle = trim(($event['titolo'] ?? 'Evento cosplay') . ($eventCity ? ' a ' . $eventCity : '') . ($eventYear ? ' ' . $eventYear : ''));
						$description = mb_strimwidth(trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($event['descrizione'] ?? '', ENT_QUOTES, 'UTF-8')))), 0, 150, '...');
						$loading = ($index < 3) ? 'eager' : 'lazy';
						$fetchPriority = ($index === 0) ? 'high' : 'auto';
						$agendaStatus = $agendaStates[$event['id']] ?? null;
						$agendaLabels = [
							'mi_interessa' => ['label' => 'Mi interessa', 'class' => 'bg-blue-100 text-blue-800'],
							'ci_vado' => ['label' => 'Ci vado', 'class' => 'bg-emerald-100 text-emerald-800'],
							'forse_vado' => ['label' => 'Forse vado', 'class' => 'bg-amber-100 text-amber-900'],
						];
						?>
						<article class="group overflow-hidden rounded-xl bg-white shadow-md transition hover:-translate-y-0.5 hover:shadow-xl focus-within:ring-2 focus-within:ring-green-700">
							<a href="<?php echo event_index_h($eventUrl); ?>" class="block" title="<?php echo event_index_h($cardTitle); ?>">
								<div class="relative aspect-[16/10] bg-gray-200">
									<?php if ($imageUrl): ?>
										<img
											src="<?php echo event_index_h($imageUrl); ?>"
											width="<?php echo event_index_h($event['immagine_width'] ?? 800); ?>"
											height="<?php echo event_index_h($event['immagine_height'] ?? 500); ?>"
											loading="<?php echo $loading; ?>"
											fetchpriority="<?php echo $fetchPriority; ?>"
											class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
											alt="<?php echo event_index_h($cardTitle . ' evento cosplay'); ?>"
										>
									<?php else: ?>
										<div class="flex h-full w-full items-center justify-center px-4 text-center text-sm font-semibold text-gray-500">
											Immagine evento non disponibile
										</div>
									<?php endif; ?>
									<span class="absolute left-3 top-3 rounded-full px-3 py-1 text-xs font-bold <?php echo event_index_h($status['class']); ?>">
										<?php echo event_index_h($status['label']); ?>
									</span>
									<?php if (!empty($agendaStatus) && isset($agendaLabels[$agendaStatus])): ?>
										<span data-agenda-badge class="absolute right-3 top-3 rounded-full px-3 py-1 text-xs font-bold <?php echo event_index_h($agendaLabels[$agendaStatus]['class']); ?>">
											<?php echo event_index_h($agendaLabels[$agendaStatus]['label']); ?>
										</span>
									<?php endif; ?>
								</div>

								<div class="p-5">
									<p class="mb-2 text-sm font-bold text-green-900">
										<time datetime="<?php echo event_index_h($startDate ?? ''); ?>"><?php echo event_index_h($dateLabel); ?></time>
									</p>
									<h3 class="text-xl font-extrabold leading-snug text-gray-950 group-hover:text-green-900">
										<?php echo event_index_h($cardTitle); ?>
									</h3>
									<p class="mt-3 text-sm font-semibold text-gray-700">
										<?php echo event_index_h(implode(' · ', array_filter([$eventCity, $event['provincia_nome'] ?? '', $eventRegion]))); ?>
									</p>
									<?php if (!empty($event['tipo_evento_nome'])): ?>
										<p class="mt-2 inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
											<?php echo event_index_h($event['tipo_evento_nome']); ?>
										</p>
									<?php endif; ?>
									<?php if ($description): ?>
										<p class="mt-3 text-sm leading-relaxed text-gray-600">
											<?php echo event_index_h($description); ?>
										</p>
									<?php endif; ?>
									<span class="mt-4 inline-flex font-bold text-green-900 group-hover:underline">
										Vedi dettagli evento
									</span>
								</div>
							</a>
							<?php if (!empty($featureFlags['enable_personal_agenda']) && !empty($_SESSION['user_id'])): ?>
								<div class="border-t border-gray-100 bg-gray-50 p-4">
									<p class="mb-3 text-xs font-bold uppercase tracking-wide text-gray-500">Agenda personale</p>
									<div class="grid grid-cols-3 gap-2">
										<?php foreach ([
											'mi_interessa' => 'Mi interessa',
											'ci_vado' => 'Ci vado',
											'forse_vado' => 'Forse vado',
										] as $agendaValue => $agendaLabel): ?>
											<form method="post" action="/eventi-cosplay/agenda/update" class="js-agenda-toggle">
												<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
												<input type="hidden" name="event_id" value="<?php echo (int) $event['id']; ?>">
												<input type="hidden" name="status" value="<?php echo event_index_h($agendaValue); ?>">
												<input type="hidden" name="redirect_to" value="<?php echo event_index_h($eventUrl); ?>">
												<button type="submit" class="js-agenda-button w-full rounded-lg border px-3 py-2 text-xs font-bold transition <?php echo ($agendaStatus === $agendaValue) ? 'border-green-700 bg-green-700 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-green-50'; ?>" data-agenda-status="<?php echo event_index_h($agendaValue); ?>" data-active="<?php echo ($agendaStatus === $agendaValue) ? '1' : '0'; ?>">
													<?php echo event_index_h($agendaLabel); ?>
												</button>
											</form>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else: ?>
				<div class="rounded-xl bg-white p-8 text-center shadow-md">
					<h2 class="text-2xl font-bold text-gray-950">Nessun evento trovato</h2>
					<p class="mx-auto mt-3 max-w-2xl text-gray-700">
						Non ci sono eventi cosplay pubblicati per questa localita. Puoi esplorare tutti gli eventi in Italia oppure tornare a controllare nei prossimi giorni.
					</p>
					<div class="mt-5 flex flex-col justify-center gap-3 sm:flex-row">
						<a href="<?php echo event_index_h($eventsBasePath); ?>" class="rounded-lg bg-green-800 px-4 py-3 font-bold text-white hover:bg-green-900">
							Vedi tutti gli eventi
						</a>
						<a href="/segnala-evento-cosplay" class="rounded-lg border border-green-800 px-4 py-3 font-bold text-green-900 hover:bg-green-50">
							Segnala un evento
						</a>
					</div>
				</div>
			<?php endif; ?>
		</section>

		<?php if (!empty($provinceCorrelate) && !empty($selectedRegione)): ?>
			<section class="mt-12" aria-labelledby="province-correlate">
				<h2 id="province-correlate" class="mb-5 text-2xl font-bold text-gray-950">
					Eventi cosplay nelle province della regione <?php echo event_index_h($selectedRegione['nome']); ?>
				</h2>
				<div class="grid gap-4 md:grid-cols-2">
					<?php foreach ($provinceCorrelate as $provincia): ?>
						<?php if ((int)($provincia['totale_eventi'] ?? 0) > 0): ?>
							<a href="<?php echo event_index_h($eventsBasePath . '/' . $selectedRegione['slug'] . '/' . $provincia['provincia_slug']); ?>" class="rounded-xl bg-white p-5 shadow-md transition hover:bg-green-50 hover:shadow-lg" title="Eventi cosplay a <?php echo event_index_h($provincia['provincia_nome']); ?>">
								<h3 class="text-lg font-bold text-gray-950">
									Eventi cosplay a <?php echo event_index_h($provincia['provincia_nome']); ?>
								</h3>
								<p class="mt-2 text-sm text-gray-700">
									<?php echo (int)$provincia['totale_eventi']; ?> eventi in programma
								</p>
								<?php if (!empty($provincia['prossimo_evento'])): ?>
									<p class="mt-3 text-sm text-gray-700">
										Prossimo evento:
										<strong><?php echo event_index_h($provincia['prossimo_evento']['titolo'] . ' - ' . date('d/m/Y', strtotime($provincia['prossimo_evento']['data_inizio']))); ?></strong>
									</p>
								<?php endif; ?>
							</a>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<section class="mt-12 rounded-xl bg-white p-5 shadow-md md:p-8" aria-labelledby="potrebbero-interessarti">
			<h2 id="potrebbero-interessarti" class="text-2xl font-bold text-gray-950">Potrebbero interessarti</h2>
			<div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
				<?php foreach (array_slice($allRegioni, 0, 8) as $regione): ?>
					<a href="<?php echo event_index_h($eventsBasePath . '/' . $regione['slug']); ?>" class="rounded-lg border border-gray-200 p-4 font-semibold text-green-900 hover:border-green-800 hover:bg-green-50">
						Eventi cosplay in <?php echo event_index_h($regione['nome']); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="mt-12 rounded-xl bg-white p-5 shadow-md md:p-8" aria-labelledby="faq-eventi">
			<h2 id="faq-eventi" class="mb-5 text-2xl font-bold text-gray-950">Domande frequenti sugli eventi cosplay</h2>
			<div class="grid gap-5 md:grid-cols-3">
				<article>
					<h3 class="text-lg font-bold text-gray-950">Come trovo eventi cosplay vicino a me?</h3>
					<p class="mt-2 text-sm leading-relaxed text-gray-700">Usa i filtri per regione, provincia e comune: ogni pagina locale raccoglie gli eventi approvati e le schede dettagliate disponibili.</p>
				</article>
				<article>
					<h3 class="text-lg font-bold text-gray-950">Gli eventi sono aggiornati?</h3>
					<p class="mt-2 text-sm leading-relaxed text-gray-700">Le schede vengono aggiornate con date, luogo e canali ufficiali quando disponibili. Verifica sempre eventuali variazioni sui link degli organizzatori.</p>
				</article>
				<article>
					<h3 class="text-lg font-bold text-gray-950">Posso segnalare un evento?</h3>
					<p class="mt-2 text-sm leading-relaxed text-gray-700">Sì, puoi segnalare fiere, raduni, contest e festival tramite il modulo dedicato. Gli eventi vengono verificati prima della pubblicazione.</p>
				</article>
			</div>
		</section>
	</div>
</main>

<script>
	const allRegioniData = <?php echo json_encode($allRegioni, JSON_UNESCAPED_UNICODE); ?>;
	const allProvinceData = <?php echo json_encode($allProvince, JSON_UNESCAPED_UNICODE); ?>;
	const allComuniData = <?php echo json_encode($allComuni, JSON_UNESCAPED_UNICODE); ?>;

	const filterRegione = document.getElementById('filter_regione');
	const filterProvincia = document.getElementById('filter_provincia');
	const filterComune = document.getElementById('filter_comune');
	const eventsBasePath = '<?php echo $eventsBasePath; ?>';
	const toggleTextButton = document.querySelector('[data-toggle-text]');
	const shortDescription = document.getElementById('eventi-descrizione-corta');
	const fullDescription = document.getElementById('eventi-descrizione-completa');

	function redirect(url) {
		window.location.href = url;
	}

	if (toggleTextButton && shortDescription && fullDescription) {
		toggleTextButton.addEventListener('click', function () {
			const isOpen = !fullDescription.classList.contains('hidden');

			if (isOpen) {
				fullDescription.classList.add('hidden');
				shortDescription.classList.remove('hidden');
				toggleTextButton.textContent = toggleTextButton.dataset.labelShow || 'Mostra tutto';
				toggleTextButton.setAttribute('aria-expanded', 'false');
				return;
			}

			shortDescription.classList.add('hidden');
			fullDescription.classList.remove('hidden');
			toggleTextButton.textContent = toggleTextButton.dataset.labelHide || 'Mostra meno';
			toggleTextButton.setAttribute('aria-expanded', 'true');
		});
	}

	function resetProvince() {
		filterProvincia.innerHTML = '<option value="">Tutte le province</option>';
		filterProvincia.disabled = true;
	}

	function resetComuni() {
		filterComune.innerHTML = '<option value="">Tutti i comuni</option>';
		filterComune.disabled = true;
	}

	function updateProvinceDropdown(regioneSlug) {
		resetProvince();
		resetComuni();

		if (!regioneSlug) return;

		const regione = allRegioniData.find(r => r.slug === regioneSlug);
		if (!regione) return;

		allProvinceData
			.filter(p => parseInt(p.regione_id) === parseInt(regione.id))
			.forEach(p => {
				const opt = document.createElement('option');
				opt.value = p.slug;
				opt.textContent = p.nome;
				filterProvincia.appendChild(opt);
			});

		filterProvincia.disabled = false;
	}

	function updateComuneDropdown(provinciaSlug) {
		resetComuni();

		if (!provinciaSlug) return;

		const provincia = allProvinceData.find(p => p.slug === provinciaSlug);
		if (!provincia) return;

		allComuniData
			.filter(c => parseInt(c.provincia_id) === parseInt(provincia.id))
			.forEach(c => {
				const opt = document.createElement('option');
				opt.value = c.slug;
				opt.textContent = c.nome;
				filterComune.appendChild(opt);
			});

		filterComune.disabled = false;
	}

	filterRegione.addEventListener('change', function() {
		const slug = this.value;
		let url = eventsBasePath;

		if (slug !== '') {
			url += '/' + encodeURIComponent(slug);
		}

		redirect(url);
	});

	filterProvincia.addEventListener('change', function() {
		const provincia = this.value;
		const regione = filterRegione.value;

		if (!regione) {
			redirect(eventsBasePath);
			return;
		}

		updateComuneDropdown(provincia);

		if (!provincia) {
			redirect(`${eventsBasePath}/${regione}`);
			return;
		}

		redirect(`${eventsBasePath}/${regione}/${provincia}`);
	});

	filterComune.addEventListener('change', function() {
		const comune = this.value;
		const regione = filterRegione.value;
		const provincia = filterProvincia.value;

		if (!regione || !provincia) {
			redirect(eventsBasePath);
			return;
		}

		if (!comune) {
			redirect(`${eventsBasePath}/${regione}/${provincia}`);
			return;
		}

		redirect(`${eventsBasePath}/${regione}/${provincia}/${comune}`);
	});
</script>
