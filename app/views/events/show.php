<?php
// Questo file è un frammento di HTML e deve essere incluso in un layout pubblico.
// Non contiene i tag <html>, <head>, <body> completi.
?>

<?php

$breadcrumbs = $data['breadcrumbs'] ?? [];
$event = $data['event'] ?? null;
$eventMaster = $data['eventMaster'] ?? null;
$similarEvents = $data['similarEvents'] ?? ($similarEvents ?? []);
$hasVisibleMaster = !empty($data['hasVisibleMaster']);
$organizations = $data['organizations'] ?? [];
$masterClaimUrl = !empty($eventMaster['slug'])
	? URL_ROOT_SITE . '/eventi-master/' . rawurlencode((string) $eventMaster['slug']) . '/riscatta'
	: '';
$eventsBaseUrl = rtrim(URL_ROOT_SITE, '/') . '/eventi-cosplay';
$siteBaseUrl = rtrim(URL_ROOT_SITE, '/');
$featureFlags = (new \App\Services\SiteFeatureFlagService())->getEnabledMap();
$photoCount = (int) ($data['photoCount'] ?? 0);

if (!function_exists('event_show_absolute_url')) {
	function event_show_absolute_url(?string $url, string $siteBaseUrl): string
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

if (!function_exists('event_show_format_date')) {
	function event_show_format_date(?string $date): string
	{
		if (empty($date)) {
			return '';
		}

		return date('d/m/Y', strtotime($date));
	}
}

if (!function_exists('event_show_calendar_date')) {
	function event_show_calendar_date(?string $date, bool $end = false): string
	{
		if (empty($date)) {
			return '';
		}

		$timestamp = strtotime($date);
		if ($end) {
			$timestamp = strtotime('+1 day', $timestamp);
		}

		return gmdate('Ymd', $timestamp);
	}
}

if (!function_exists('event_show_schema_date')) {
	function event_show_schema_date(?string $date): ?string
	{
		if (empty($date)) {
			return null;
		}

		return date('c', strtotime($date));
	}
}

if (!function_exists('event_show_region_url')) {
	function event_show_region_url(array $event, array $breadcrumbs, string $eventsBaseUrl): string
	{
		if (!empty($event['regione_slug'])) {
			return $eventsBaseUrl . '/' . htmlspecialchars($event['regione_slug'], ENT_QUOTES, 'UTF-8');
		}

		if (!empty($event['regione_nome'])) {
			foreach ($breadcrumbs as $crumb) {
				if (($crumb['label'] ?? '') === $event['regione_nome'] && !empty($crumb['url'])) {
					return $crumb['url'];
				}
			}
		}

		return $eventsBaseUrl;
	}
}
?>

<?php if ($event): ?>
	<?php
	$eventTitle = trim((string)($event['titolo'] ?? 'Evento cosplay'));
	$eventYear = !empty($event['data_inizio']) ? date('Y', strtotime($event['data_inizio'])) : '';
	$cityName = trim((string)($event['comune_nome'] ?? ''));
	$regionName = trim((string)($event['regione_nome'] ?? ''));
	$provinceName = trim((string)($event['provincia_nome'] ?? ''));
	$placeName = trim((string)($event['luogo'] ?? ''));
	$eventUrl = $eventsBaseUrl . '/' . rawurlencode((string)($event['slug'] ?? ''));
	$regionUrl = event_show_region_url($event, $breadcrumbs, $eventsBaseUrl);
	$imageUrl = event_show_absolute_url($event['immagine'] ?? '', $siteBaseUrl);
	$plainDescription = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($event['descrizione'] ?? '', ENT_QUOTES, 'UTF-8'))));
	$shortDescription = mb_strimwidth($plainDescription, 0, 180, '...');
	$fullLocation = implode(', ', array_filter([$placeName, $cityName, $provinceName, $regionName]));
	$h1Parts = array_filter([
		$eventTitle,
		$cityName ?: $regionName,
		$eventYear,
	]);
	$h1 = implode(' ', $h1Parts);
	$dateLabel = event_show_format_date($event['data_inizio'] ?? '');

	if (!empty($event['data_fine']) && $event['data_fine'] !== ($event['data_inizio'] ?? null)) {
		$dateLabel .= ' - ' . event_show_format_date($event['data_fine']);
	}

	$calendarStart = event_show_calendar_date($event['data_inizio'] ?? '');
	$calendarEnd = event_show_calendar_date($event['data_fine'] ?? ($event['data_inizio'] ?? ''), true);
	$calendarText = rawurlencode($eventTitle . ($cityName ? ' a ' . $cityName : ''));
	$calendarDetails = rawurlencode($shortDescription . "\n\nScheda evento: " . $eventUrl);
	$calendarLocation = rawurlencode($fullLocation);
	$calendarUrl = $calendarStart && $calendarEnd
		? 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . $calendarText . '&dates=' . $calendarStart . '/' . $calendarEnd . '&details=' . $calendarDetails . '&location=' . $calendarLocation
		: '#';
	$successMessage = \App\Core\Session::getFlash('success');
	$errorMessage = \App\Core\Session::getFlash('error');
	$pendingActionType = \App\Core\Session::getFlash('pending_action_type');
	$shareText = trim($eventTitle . ($cityName ? ' a ' . $cityName : '') . ($eventYear ? ' ' . $eventYear : ''));
	$shareUrlEncoded = rawurlencode($eventUrl);
		$shareTextEncoded = rawurlencode($shareText);
		$selectedPortfolioIds = array_map(static fn (array $selection): int => (int) ($selection['portfolio_id'] ?? 0), $cosplaySelections ?? []);

	$schema = [
		'@context' => 'https://schema.org',
		'@type' => 'Event',
		'name' => $eventTitle,
		'description' => $shortDescription ?: $eventTitle,
		'startDate' => event_show_schema_date($event['data_inizio'] ?? ''),
		'eventStatus' => 'https://schema.org/EventScheduled',
		'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
		'url' => $eventUrl,
		'location' => [
			'@type' => 'Place',
			'name' => $placeName ?: ($cityName ?: $eventTitle),
			'address' => [
				'@type' => 'PostalAddress',
				'streetAddress' => $placeName,
				'addressLocality' => $cityName,
				'addressRegion' => $regionName,
				'addressCountry' => 'IT',
			],
		],
		'organizer' => [
			'@type' => 'Organization',
			'name' => $eventTitle,
			'url' => !empty($event['sito_web']) ? $event['sito_web'] : $eventUrl,
		],
	];

	if (!empty($event['data_fine'])) {
		$schema['endDate'] = event_show_schema_date($event['data_fine']);
	}

	if (!empty($imageUrl)) {
		$schema['image'] = [$imageUrl];
	}

	if (!empty($event['latitudine']) && !empty($event['longitudine'])) {
		$schema['location']['geo'] = [
			'@type' => 'GeoCoordinates',
			'latitude' => (float)$event['latitudine'],
			'longitude' => (float)$event['longitudine'],
		];
	}
	?>

	<script type="application/ld+json">
		<?php echo json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
	</script>

	<?php if (!empty($event['latitudine']) && !empty($event['longitudine'])): ?>
		<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
		<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css">
		<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css">
		<style>
			.leaflet-popup-content-wrapper,
			.leaflet-popup-tip {
				color: #000 !important;
				background: #fff !important;
				border: 1px solid #000 !important;
			}

			.leaflet-popup-close-button {
				color: #333 !important;
				background-color: #ddd;
			}

			.event-description {
				min-width: 0;
				overflow-wrap: anywhere;
				word-break: break-word;
			}

			.event-description.is-collapsed {
				display: -webkit-box;
				overflow: hidden;
				-webkit-box-orient: vertical;
				-webkit-line-clamp: 10;
			}

			.event-description-toggle {
				display: none;
			}

			@media (max-width: 767px) {
				.event-description-toggle {
					display: inline-flex;
				}
			}

			.event-description img,
			.event-description video,
			.event-description iframe,
			.event-description table {
				max-width: 100%;
			}

			.event-description table {
				display: block;
				overflow-x: auto;
			}

			.event-description pre {
				max-width: 100%;
				overflow-x: auto;
				white-space: pre-wrap;
				word-break: break-word;
			}
		</style>
	<?php endif; ?>

	<?php if ($successMessage || $errorMessage): ?>
		<div class="mx-auto mt-6 max-w-7xl px-4 sm:px-6 lg:px-8">
			<div class="rounded-xl border <?php echo $successMessage ? 'border-green-200 bg-green-50 text-green-900' : 'border-red-200 bg-red-50 text-red-800'; ?> px-4 py-3 text-sm font-semibold">
				<?php echo htmlspecialchars((string) ($successMessage ?: $errorMessage), ENT_QUOTES, 'UTF-8'); ?>
				<?php if ($successMessage && $pendingActionType === 'agenda'): ?>
					<a href="/dashboard/events" class="ml-2 inline-flex font-bold underline">Apri la mia Agenda</a>
				<?php elseif ($successMessage && $pendingActionType === 'favorite'): ?>
					<a href="/dashboard/favorites" class="ml-2 inline-flex font-bold underline">Apri preferiti</a>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<main class="bg-gray-100">
		<div class="container mx-auto px-4 py-6 md:px-6">
			<nav class="mb-5 text-sm text-gray-700" aria-label="Breadcrumb">
				<ol class="flex flex-wrap items-center gap-2">
					<?php foreach ($breadcrumbs as $index => $crumb): ?>
						<li class="flex items-center gap-2">
							<?php if ($index > 0): ?>
								<span class="text-gray-400">/</span>
							<?php endif; ?>

							<?php if (!empty($crumb['url'])): ?>
								<a href="<?php echo htmlspecialchars($crumb['url'], ENT_QUOTES, 'UTF-8'); ?>" class="font-medium text-green-900 hover:underline">
									<?php echo htmlspecialchars($crumb['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
								</a>
							<?php else: ?>
								<span class="text-gray-600"><?php echo htmlspecialchars($crumb['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</nav>

			<?php echo \App\Helpers\AdPlacement::render('event_header_sidebar', 'event_detail', 'mb-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md'); ?>

			<article class="overflow-hidden rounded-xl bg-white shadow-lg">
				<header class="grid gap-0 lg:grid-cols-[minmax(0,1.35fr)_minmax(340px,0.65fr)]">
					<div class="relative min-h-[320px] bg-gray-200 md:min-h-[460px]">
						<?php if (!empty($imageUrl)): ?>
							<img
									src="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>"
									alt="<?php echo htmlspecialchars($h1 . ' evento cosplay', ENT_QUOTES, 'UTF-8'); ?>"
									title="<?php echo htmlspecialchars($h1 . ' evento cosplay', ENT_QUOTES, 'UTF-8'); ?>"
									class="absolute inset-0 h-full w-full object-cover"
									fetchpriority="high"
									loading="eager"
							>
						<?php else: ?>
							<div class="absolute inset-0 flex items-center justify-center bg-gray-200 text-gray-500">
								<span class="text-lg font-semibold">Immagine evento non disponibile</span>
							</div>
						<?php endif; ?>
						<div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/35 to-transparent p-5 text-white md:p-8 lg:hidden">
							<p class="mb-2 text-sm font-semibold uppercase tracking-wide">Evento cosplay in Italia</p>
						</div>
					</div>

					<div class="flex flex-col justify-between gap-6 p-5 md:p-8">
						<div>
							<p class="mb-3 hidden text-sm font-semibold uppercase tracking-wide text-green-900 lg:block">
								Evento cosplay in Italia
							</p>
							<h1 class="text-3xl font-extrabold leading-tight text-gray-900 md:text-4xl">
								<?php echo htmlspecialchars($h1, ENT_QUOTES, 'UTF-8'); ?>
							</h1>
							<?php if (!empty($organizations)): ?>
								<div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-gray-600">
									<span>Organizzato da</span>
									<?php foreach ($organizations as $organization): ?>
										<a href="<?php echo htmlspecialchars(URL_ROOT_SITE . '/organizzazioni/' . rawurlencode((string) $organization['slug']), ENT_QUOTES, 'UTF-8'); ?>" class="font-bold text-green-900 hover:underline">
											<?php echo htmlspecialchars($organization['name'], ENT_QUOTES, 'UTF-8'); ?>
										</a>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<div class="mt-5 grid gap-3 text-base text-gray-700">
								<p class="flex gap-3">
									<span class="font-bold text-green-900">Data</span>
									<time datetime="<?php echo htmlspecialchars($event['data_inizio'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
										<?php echo htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8'); ?>
									</time>
								</p>
								<p class="flex gap-3">
									<span class="font-bold text-green-900">Luogo</span>
									<span><?php echo htmlspecialchars($fullLocation ?: 'Luogo da confermare', ENT_QUOTES, 'UTF-8'); ?></span>
								</p>
								<?php if (!empty($event['tipo_evento_nome'])): ?>
									<p class="flex gap-3">
										<span class="font-bold text-green-900">Tipo</span>
										<span><?php echo htmlspecialchars($event['tipo_evento_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
									</p>
								<?php endif; ?>
							</div>
							<?php if ($masterClaimUrl): ?>
								<div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
									<p class="text-sm leading-relaxed text-amber-950">
										Sei l’organizzatore di questo evento?
										La richiesta riguarda il master e tutte le sue edizioni.
									</p>
									<?php if (!empty($_SESSION['user_id'])): ?>
										<a href="<?php echo htmlspecialchars($masterClaimUrl, ENT_QUOTES, 'UTF-8'); ?>" class="mt-3 inline-flex rounded-lg bg-amber-500 px-4 py-2 text-sm font-bold text-gray-950 hover:bg-amber-400">
											Richiedi gestione evento
										</a>
									<?php else: ?>
										<a href="/register" class="mt-3 inline-flex rounded-lg bg-amber-500 px-4 py-2 text-sm font-bold text-gray-950 hover:bg-amber-400">
											Registrati per richiedere la gestione
										</a>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>

						<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
							<a href="<?php echo htmlspecialchars($eventUrl . '/foto', ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex items-center justify-center rounded-lg border border-green-800 px-4 py-3 text-center font-bold text-green-900 transition hover:bg-green-50">
								📸 Foto<?php echo $photoCount > 0 ? ' (' . $photoCount . ')' : ''; ?>
							</a>
							<a href="/segnala-evento-cosplay" class="inline-flex items-center justify-center rounded-lg bg-green-800 px-4 py-3 text-center font-bold text-white shadow-md transition hover:bg-green-900">
								Segnala evento
							</a>
							<a href="<?php echo htmlspecialchars($calendarUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-lg border border-green-800 px-4 py-3 text-center font-bold text-green-900 transition hover:bg-green-50">
								Aggiungi al calendario
							</a>
						</div>
					</div>
				</header>

				<div class="grid gap-8 p-5 md:p-8 lg:grid-cols-[minmax(0,1fr)_340px]">
					<div class="space-y-10">
						<section aria-labelledby="descrizione-evento">
							<h2 id="descrizione-evento" class="mb-4 text-2xl font-bold text-gray-900">
								Descrizione evento
							</h2>
							<div id="descrizione-evento-contenuto" class="event-description is-collapsed prose min-w-0 max-w-none break-words overflow-hidden text-gray-800">
								<?php echo $event['descrizione'] ?? ''; ?>
							</div>
							<button
								type="button"
								class="event-description-toggle mt-4 items-center rounded-lg border border-green-800 px-4 py-2 font-semibold text-green-900 transition hover:bg-green-50"
								data-description-toggle
								aria-controls="descrizione-evento-contenuto"
								aria-expanded="false"
							>
								Mostra di più
							</button>

							<p class="mt-5 text-gray-700">
								Consulta anche la lista degli
								<a href="<?php echo htmlspecialchars($eventsBaseUrl, ENT_QUOTES, 'UTF-8'); ?>" class="font-semibold text-green-900 hover:underline">eventi cosplay in Italia</a>
								e gli
								<a href="<?php echo htmlspecialchars($regionUrl, ENT_QUOTES, 'UTF-8'); ?>" class="font-semibold text-green-900 hover:underline">eventi cosplay in <?php echo htmlspecialchars($regionName ?: 'questa regione', ENT_QUOTES, 'UTF-8'); ?></a>.
							</p>
						</section>

						<?php echo \App\Helpers\AdPlacement::render('event_after_description', 'event_detail', 'overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md'); ?>

						<?php if (!empty($similarEvents)): ?>
							<section aria-labelledby="eventi-correlati">
								<h2 id="eventi-correlati" class="mb-5 text-2xl font-bold text-gray-900">
									Eventi correlati
								</h2>
								<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
									<?php foreach ($similarEvents as $similarEvent): ?>
										<?php
										$similarImage = event_show_absolute_url($similarEvent['immagine'] ?? '', $siteBaseUrl);
										$similarTitle = $similarEvent['titolo'] ?? 'Evento cosplay';
										$similarUrl = $eventsBaseUrl . '/' . rawurlencode((string)($similarEvent['slug'] ?? ''));
										$similarDate = event_show_format_date($similarEvent['data_inizio'] ?? '');
										$similarPlace = trim((string)($similarEvent['luogo'] ?? ''));
										?>
										<article class="min-w-0 overflow-hidden rounded-lg border border-gray-100 bg-white shadow-md transition hover:shadow-lg">
											<a href="<?php echo htmlspecialchars($similarUrl, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($similarTitle . ' evento cosplay', ENT_QUOTES, 'UTF-8'); ?>">
												<?php if (!empty($similarImage)): ?>
													<img
															src="<?php echo htmlspecialchars($similarImage, ENT_QUOTES, 'UTF-8'); ?>"
															class="h-40 w-full object-cover"
															loading="lazy"
															alt="<?php echo htmlspecialchars($similarTitle . ' evento cosplay', ENT_QUOTES, 'UTF-8'); ?>"
													>
												<?php endif; ?>
												<div class="min-w-0 p-4">
													<h3 class="mb-2 break-words text-lg font-bold text-gray-900">
														<?php echo htmlspecialchars($similarTitle, ENT_QUOTES, 'UTF-8'); ?>
													</h3>
													<?php if ($similarDate): ?>
														<p class="break-words text-sm text-gray-600">Data: <?php echo htmlspecialchars($similarDate, ENT_QUOTES, 'UTF-8'); ?></p>
													<?php endif; ?>
													<?php if ($similarPlace): ?>
														<p class="mt-1 break-words text-sm text-gray-600">Luogo: <?php echo htmlspecialchars($similarPlace, ENT_QUOTES, 'UTF-8'); ?></p>
													<?php endif; ?>
												</div>
											</a>
										</article>
									<?php endforeach; ?>
								</div>
							</section>
						<?php endif; ?>

						<?php if (!empty($event['latitudine']) && !empty($event['longitudine'])): ?>
							<section aria-labelledby="mappa-evento">
								<h2 id="mappa-evento" class="mb-4 text-2xl font-bold text-gray-900">
									Dove si trova
								</h2>
								<div id="mapid" class="mb-4 h-80 w-full rounded-lg shadow-md"></div>
								<div class="flex flex-col gap-3 sm:flex-row sm:items-center">
									<input
											aria-label="Punto di partenza"
											type="text"
											id="start-address"
											placeholder="Inserisci il punto di partenza"
											class="flex-grow rounded-lg border px-3 py-3 text-gray-700 shadow focus:border-green-500 focus:ring-1 focus:ring-green-500"
									>
									<button
											id="calc-route"
											class="rounded-lg bg-gray-200 px-4 py-3 font-semibold text-gray-800 shadow-md transition hover:bg-gray-300"
									>
										Calcola percorso
									</button>
								</div>
							</section>
						<?php endif; ?>

						<?php echo \App\Helpers\AdPlacement::render('event_related_bottom', 'event_detail', 'overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md'); ?>

						<?php if (!empty($publicCosplaySelections)): ?>
							<section class="rounded-2xl border border-fuchsia-200 bg-fuchsia-50 p-5" aria-labelledby="cosplay-evento-pubblico">
								<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
									<div>
										<h2 id="cosplay-evento-pubblico" class="text-2xl font-bold text-gray-900">
											Cosplay segnalati dagli utenti
										</h2>
										<p class="mt-1 text-sm text-gray-700">
											Chi ha già detto che parteciperà o che forse ci sarà.
										</p>
									</div>
									<span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-fuchsia-800 shadow-sm">
										<?php echo (int) count($publicCosplaySelections); ?> segnalazioni
									</span>
								</div>

								<details class="rounded-2xl border border-fuchsia-200 bg-white p-4 shadow-sm">
									<summary class="cursor-pointer list-none text-sm font-bold text-fuchsia-900">
										Mostra / nascondi cosplay
									</summary>
									<div class="mt-4 space-y-5">
										<?php
										$groupedSelections = [
											'porterò' => [],
											'forse' => [],
										];
										foreach ($publicCosplaySelections as $publicSelection) {
											$statusKey = (($publicSelection['status'] ?? '') === 'forse') ? 'forse' : 'porterò';
											$groupedSelections[$statusKey][] = $publicSelection;
										}
										$statusLabels = [
											'porterò' => 'Porterò',
											'forse' => 'Forse vado',
										];
										?>
										<?php foreach ($groupedSelections as $statusKey => $items): ?>
											<div>
												<div class="mb-3 flex items-center justify-between gap-3">
													<h3 class="text-sm font-bold uppercase tracking-wide text-gray-700"><?php echo htmlspecialchars($statusLabels[$statusKey], ENT_QUOTES, 'UTF-8'); ?></h3>
													<span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600"><?php echo (int) count($items); ?></span>
												</div>
												<?php if (!empty($items)): ?>
													<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
														<?php foreach ($items as $publicSelection): ?>
															<?php
															$publicLabel = trim((string) ($publicSelection['custom_name'] ?? ''));
															if ($publicLabel === '') {
																$publicLabel = trim((string) ($publicSelection['name_full'] ?? 'Cosplay'));
															}
															$publicUser = trim((string) ($publicSelection['username'] ?? ''));
															$publicImage = trim((string) ($publicSelection['reference_image'] ?? ''));
															if ($publicImage === '') {
																$publicImage = trim((string) ($publicSelection['image_large'] ?? ''));
															}
															$publicImageUrl = $publicImage !== '' ? (preg_match('/^https?:\\/\\//i', $publicImage) ? $publicImage : (URL_ROOT_SITE . '/public_assets/' . ltrim($publicImage, '/'))) : '';
															?>
															<div class="overflow-hidden rounded-2xl border border-fuchsia-100 bg-white shadow-sm">
																<div class="aspect-[4/3] bg-gray-100">
																	<?php if (!empty($publicImageUrl)): ?>
																		<img src="<?php echo htmlspecialchars($publicImageUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($publicLabel, ENT_QUOTES, 'UTF-8'); ?>" class="h-full w-full object-cover">
																	<?php else: ?>
																		<div class="flex h-full items-center justify-center text-sm font-semibold text-gray-400">Nessuna immagine</div>
																	<?php endif; ?>
																</div>
														<div class="space-y-2 p-4">
															<p class="text-base font-bold text-gray-900"><?php echo htmlspecialchars($publicLabel, ENT_QUOTES, 'UTF-8'); ?></p>
															<p class="text-sm text-gray-600">
																di
																<?php if (!empty($featureFlags['enable_public_profiles']) && $publicUser !== ''): ?>
																	<a href="<?php echo htmlspecialchars(URL_ROOT_SITE . '/u/' . rawurlencode($publicUser), ENT_QUOTES, 'UTF-8'); ?>" class="font-semibold text-fuchsia-900 hover:underline">
																		@<?php echo htmlspecialchars($publicUser, ENT_QUOTES, 'UTF-8'); ?>
																	</a>
																<?php else: ?>
																	<span class="font-semibold text-gray-700"><?php echo htmlspecialchars($publicUser !== '' ? '@' . $publicUser : 'utente', ENT_QUOTES, 'UTF-8'); ?></span>
																<?php endif; ?>
															</p>
														</div>
													</div>
												<?php endforeach; ?>
													</div>
												<?php else: ?>
													<p class="text-sm text-gray-600">Nessun cosplay segnalato per questo stato.</p>
												<?php endif; ?>
											</div>
										<?php endforeach; ?>
									</div>
								</details>
							</section>
						<?php endif; ?>

						<?php if (!empty($eventMaster) && !empty($eventMasterEvents)): ?>
							<section aria-labelledby="altre-edizioni">
								<div class="mb-4 flex items-center justify-between gap-3">
									<h2 id="altre-edizioni" class="text-2xl font-bold text-gray-900">
										Altre edizioni dello stesso evento
									</h2>
									<?php if ($hasVisibleMaster): ?>
										<a href="<?php echo htmlspecialchars(URL_ROOT_SITE . '/eventi-master/' . $eventMaster['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="text-sm font-semibold text-green-900 hover:underline">
											Vedi pagina master
										</a>
									<?php endif; ?>
								</div>
								<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
									<?php foreach ($eventMasterEvents as $masterEvent): ?>
										<?php
										$masterEventTitle = $masterEvent['titolo'] ?? 'Evento cosplay';
										$masterEventUrl = $eventsBaseUrl . '/' . rawurlencode((string)($masterEvent['slug'] ?? ''));
										$masterEventDate = event_show_format_date($masterEvent['data_inizio'] ?? '');
										$masterEventYear = !empty($masterEvent['year']) ? (string) $masterEvent['year'] : '';
										?>
										<article class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm">
											<h3 class="text-lg font-bold text-gray-900">
												<a href="<?php echo htmlspecialchars($masterEventUrl, ENT_QUOTES, 'UTF-8'); ?>" class="hover:text-green-900">
													<?php echo htmlspecialchars($masterEventTitle, ENT_QUOTES, 'UTF-8'); ?>
												</a>
											</h3>
											<p class="mt-2 text-sm text-gray-600">
												<?php echo htmlspecialchars(trim($masterEventDate . ($masterEventYear ? ' - ' . $masterEventYear : '')), ENT_QUOTES, 'UTF-8'); ?>
											</p>
											<?php if (!empty($masterEvent['luogo'])): ?>
												<p class="mt-1 text-sm text-gray-600">
													Luogo: <?php echo htmlspecialchars($masterEvent['luogo'], ENT_QUOTES, 'UTF-8'); ?>
												</p>
											<?php endif; ?>
										</article>
									<?php endforeach; ?>
								</div>
							</section>
						<?php endif; ?>

						<section aria-labelledby="eventi-periodo">
							<h2 id="eventi-periodo" class="mb-4 text-2xl font-bold text-gray-900">
								Eventi simili per periodo
							</h2>
							<div class="grid gap-3 sm:grid-cols-2">
								<a href="<?php echo htmlspecialchars($eventsBaseUrl . '-mese', ENT_QUOTES, 'UTF-8'); ?>" class="rounded-lg border border-gray-200 p-4 font-semibold text-green-900 hover:border-green-800 hover:bg-green-50">
									Eventi cosplay del mese
								</a>
								<a href="<?php echo htmlspecialchars($eventsBaseUrl . '-weekend', ENT_QUOTES, 'UTF-8'); ?>" class="rounded-lg border border-gray-200 p-4 font-semibold text-green-900 hover:border-green-800 hover:bg-green-50">
									Eventi cosplay del weekend
								</a>
							</div>
						</section>
					</div>

					<aside class="space-y-6">
						<section class="rounded-xl border border-gray-200 bg-gray-50 p-5" aria-labelledby="info-rapide">
							<h2 id="info-rapide" class="mb-4 text-xl font-bold text-gray-900">
								Info rapide
							</h2>
							<dl class="space-y-4 text-sm text-gray-700">
								<div>
									<dt class="font-bold text-gray-900">Data</dt>
									<dd><?php echo htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8'); ?></dd>
								</div>
								<div>
									<dt class="font-bold text-gray-900">Città</dt>
									<dd><?php echo htmlspecialchars($cityName ?: 'Da confermare', ENT_QUOTES, 'UTF-8'); ?></dd>
								</div>
								<div>
									<dt class="font-bold text-gray-900">Regione</dt>
									<dd>
										<a href="<?php echo htmlspecialchars($regionUrl, ENT_QUOTES, 'UTF-8'); ?>" class="font-semibold text-green-900 hover:underline">
											<?php echo htmlspecialchars($regionName ?: 'Eventi cosplay', ENT_QUOTES, 'UTF-8'); ?>
										</a>
									</dd>
								</div>
								<?php if ($placeName): ?>
									<div>
										<dt class="font-bold text-gray-900">Luogo</dt>
										<dd><?php echo htmlspecialchars($placeName, ENT_QUOTES, 'UTF-8'); ?></dd>
									</div>
								<?php endif; ?>
								<?php if (!empty($event['tipo_evento_nome'])): ?>
									<div>
										<dt class="font-bold text-gray-900">Categoria</dt>
										<dd><?php echo htmlspecialchars($event['tipo_evento_nome'], ENT_QUOTES, 'UTF-8'); ?></dd>
									</div>
								<?php endif; ?>
							</dl>
						</section>

						<section class="rounded-xl border border-gray-200 bg-white p-5" aria-labelledby="link-utili">
							<h2 id="link-utili" class="mb-4 text-xl font-bold text-gray-900">
								Link utili
							</h2>
							<ul class="space-y-3 text-sm">
								<li>
									<a href="<?php echo htmlspecialchars($eventsBaseUrl, ENT_QUOTES, 'UTF-8'); ?>" class="font-semibold text-green-900 hover:underline">
										Tutti gli eventi cosplay in Italia
									</a>
								</li>
								<li>
									<a href="<?php echo htmlspecialchars($regionUrl, ENT_QUOTES, 'UTF-8'); ?>" class="font-semibold text-green-900 hover:underline">
										Scopri altri eventi in <?php echo htmlspecialchars($regionName ?: 'questa regione', ENT_QUOTES, 'UTF-8'); ?>
									</a>
								</li>
								<li>
									<a href="<?php echo htmlspecialchars($eventsBaseUrl . '-mese', ENT_QUOTES, 'UTF-8'); ?>" class="font-semibold text-green-900 hover:underline">
										Eventi cosplay questo mese
									</a>
								</li>
								<li>
									<a href="<?php echo htmlspecialchars($eventsBaseUrl . '-weekend', ENT_QUOTES, 'UTF-8'); ?>" class="font-semibold text-green-900 hover:underline">
										Eventi cosplay nel weekend
									</a>
								</li>
								<?php if (!empty($event['sito_web'])): ?>
									<li>
										<a href="<?php echo htmlspecialchars($event['sito_web'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="font-semibold text-blue-800 hover:underline">
											Sito ufficiale evento
										</a>
									</li>
								<?php endif; ?>
							</ul>
						</section>

						<?php $context = 'default'; require APP_ROOT . '/app/views/components/telegram-channel-cta.php'; ?>

						<section class="rounded-xl border border-gray-200 bg-white p-5" aria-labelledby="condividi-evento">
							<h2 id="condividi-evento" class="mb-4 text-xl font-bold text-gray-900">
								Condividi evento
							</h2>
							<div class="grid grid-cols-2 gap-3 text-sm">
								<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $shareUrlEncoded; ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 font-semibold text-gray-800 hover:border-green-800 hover:bg-green-50" aria-label="Condividi su Facebook">
									<i class="fa-brands fa-facebook-f text-blue-700" aria-hidden="true"></i>
									<span>Facebook</span>
								</a>
								<a href="https://api.whatsapp.com/send?text=<?php echo $shareTextEncoded; ?>%20<?php echo $shareUrlEncoded; ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 font-semibold text-gray-800 hover:border-green-800 hover:bg-green-50" aria-label="Condividi su WhatsApp">
									<i class="fa-brands fa-whatsapp text-green-700" aria-hidden="true"></i>
									<span>WhatsApp</span>
								</a>
								<a href="https://t.me/share/url?url=<?php echo $shareUrlEncoded; ?>&text=<?php echo $shareTextEncoded; ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 font-semibold text-gray-800 hover:border-green-800 hover:bg-green-50" aria-label="Condividi su Telegram">
									<i class="fa-brands fa-telegram text-sky-600" aria-hidden="true"></i>
									<span>Telegram</span>
								</a>
								<a href="https://twitter.com/intent/tweet?url=<?php echo $shareUrlEncoded; ?>&text=<?php echo $shareTextEncoded; ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 font-semibold text-gray-800 hover:border-green-800 hover:bg-green-50" aria-label="Condividi su X">
									<i class="fa-brands fa-x-twitter text-gray-900" aria-hidden="true"></i>
									<span>X</span>
								</a>
								<a href="mailto:?subject=<?php echo $shareTextEncoded; ?>&body=<?php echo $shareTextEncoded; ?>%0A<?php echo $shareUrlEncoded; ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 font-semibold text-gray-800 hover:border-green-800 hover:bg-green-50" aria-label="Condividi via email">
									<i class="fa-solid fa-envelope text-red-700" aria-hidden="true"></i>
									<span>Email</span>
								</a>
								<button type="button" class="js-copy-event-link inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 font-semibold text-gray-800 hover:border-green-800 hover:bg-green-50" data-share-url="<?php echo htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8'); ?>">
									<i class="fa-solid fa-link text-green-900" aria-hidden="true"></i>
									<span>Copia link</span>
								</button>
							</div>
							<p class="js-copy-event-feedback mt-3 hidden text-sm font-semibold text-green-900" role="status">
								Link copiato negli appunti.
							</p>
						</section>

						<?php if (!empty($featureFlags['enable_favorites'])): ?>
						<section class="rounded-xl border border-amber-200 bg-amber-50 p-5" aria-labelledby="preferiti-evento">
							<h2 id="preferiti-evento" class="mb-4 text-xl font-bold text-gray-900">
								Preferiti
							</h2>
							<p class="mb-4 text-sm leading-relaxed text-gray-700">
								Salva questo evento per ritrovarlo più velocemente nella tua dashboard.
							</p>
							<form method="post" action="/dashboard/favorites/toggle" class="js-favorite-toggle space-y-3">
								<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
								<input type="hidden" name="entity_type" value="<?php echo htmlspecialchars($favoriteEntityType ?? 'event', ENT_QUOTES, 'UTF-8'); ?>">
								<input type="hidden" name="entity_id" value="<?php echo (int)($event['id'] ?? 0); ?>">
								<input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8'); ?>">
								<button type="submit" class="js-favorite-button inline-flex w-full items-center justify-center gap-2 rounded-lg <?php echo !empty($isFavorited) ? 'bg-amber-800 text-white hover:bg-amber-900' : 'bg-white text-amber-900 hover:bg-amber-100'; ?> px-4 py-3 font-bold border border-amber-300 transition" data-label-add="Salva tra i preferiti" data-label-remove="Rimuovi dai preferiti" data-icon-add="fa-bookmark" data-icon-remove="fa-bookmark-slash" data-active="<?php echo !empty($isFavorited) ? '1' : '0'; ?>">
									<i class="fa-solid <?php echo !empty($isFavorited) ? 'fa-bookmark-slash' : 'fa-bookmark'; ?>" aria-hidden="true"></i>
									<span><?php echo !empty($isFavorited) ? 'Rimuovi dai preferiti' : 'Salva tra i preferiti'; ?></span>
								</button>
							</form>
						</section>
						<?php endif; ?>

						<?php if (!empty($featureFlags['enable_personal_agenda'])): ?>
						<section class="rounded-xl border border-blue-200 bg-blue-50 p-5" aria-labelledby="agenda-evento">
							<h2 id="agenda-evento" class="mb-4 text-xl font-bold text-gray-900">
								Agenda personale
							</h2>
							<p class="mb-4 text-sm leading-relaxed text-gray-700">
								Segna questo evento come intento personale. Il badge comparirà anche nella lista eventi e nella tua dashboard.
							</p>
							<div class="grid gap-2">
								<?php
								$agendaOptions = [
									'mi_interessa' => ['label' => 'Mi interessa', 'class' => 'border-blue-300 bg-white text-blue-900 hover:bg-blue-100'],
									'ci_vado' => ['label' => 'Ci vado', 'class' => 'border-emerald-300 bg-white text-emerald-900 hover:bg-emerald-100'],
									'forse_vado' => ['label' => 'Forse vado', 'class' => 'border-amber-300 bg-white text-amber-900 hover:bg-amber-100'],
								];
								?>
								<?php foreach ($agendaOptions as $agendaValue => $agendaMeta): ?>
									<form method="post" action="/eventi-cosplay/agenda/update" class="js-agenda-toggle">
										<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
										<input type="hidden" name="event_id" value="<?php echo (int)($event['id'] ?? 0); ?>">
										<input type="hidden" name="status" value="<?php echo htmlspecialchars($agendaValue, ENT_QUOTES, 'UTF-8'); ?>">
										<input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8'); ?>">
										<button type="submit" class="js-agenda-button inline-flex w-full items-center justify-center gap-2 rounded-lg border px-4 py-3 text-sm font-bold transition <?php echo !empty($agendaStatus) && $agendaStatus === $agendaValue ? 'border-green-700 bg-green-700 text-white' : $agendaMeta['class']; ?>" data-agenda-status="<?php echo htmlspecialchars($agendaValue, ENT_QUOTES, 'UTF-8'); ?>" data-active="<?php echo (!empty($agendaStatus) && $agendaStatus === $agendaValue) ? '1' : '0'; ?>">
											<?php echo htmlspecialchars($agendaMeta['label'], ENT_QUOTES, 'UTF-8'); ?>
										</button>
									</form>
								<?php endforeach; ?>
							</div>
							<?php if (!empty($agendaStatus)): ?>
								<p class="mt-4 text-xs font-semibold uppercase tracking-wide text-blue-900" data-agenda-current>
									Stato attuale: <?php echo htmlspecialchars(str_replace('_', ' ', $agendaStatus), ENT_QUOTES, 'UTF-8'); ?>
								</p>
							<?php endif; ?>
						</section>
						<?php endif; ?>

						<?php if (!empty($featureFlags['enable_cosplay_portfolio'])): ?>
						<section class="rounded-xl border border-fuchsia-200 bg-fuchsia-50 p-5" aria-labelledby="cosplay-evento" data-cosplay-selection-box>
							<h2 id="cosplay-evento" class="mb-4 text-xl font-bold text-gray-900">
								Porterò questo cosplay
							</h2>
							<p class="mb-4 text-sm leading-relaxed text-gray-700">
								Scegli dal tuo portfolio il personaggio che porterai a questo evento.
							</p>
							<?php if (!empty($_SESSION['user_id'])): ?>
								<?php if (!empty($cosplaySelections)): ?>
									<div class="mb-4 space-y-2" data-cosplay-selection-summary>
										<p class="text-sm font-semibold text-fuchsia-900">Cosplay già associati</p>
										<?php foreach ($cosplaySelections as $selectionItem): ?>
											<div class="flex items-center justify-between gap-3 rounded-xl border border-fuchsia-200 bg-white p-3" data-cosplay-selection-row data-portfolio-id="<?php echo (int) ($selectionItem['portfolio_id'] ?? 0); ?>">
												<div class="min-w-0">
													<p class="truncate text-sm font-semibold text-gray-900" data-cosplay-selection-label>
													<?php echo htmlspecialchars($selectionItem['custom_name'] ?: ($selectionItem['name_full'] ?? 'Cosplay'), ENT_QUOTES, 'UTF-8'); ?>
													</p>
													<p class="text-xs font-semibold uppercase tracking-wide text-fuchsia-900" data-cosplay-selection-status>
														<?php echo htmlspecialchars(($selectionItem['status'] ?? 'porterò') === 'forse' ? 'Forse lo porterò' : 'Porterò', ENT_QUOTES, 'UTF-8'); ?>
													</p>
												</div>
												<form method="post" action="/dashboard/cosplay/event/remove" class="js-cosplay-event-remove" data-confirm="Vuoi rimuovere questo collegamento?">
													<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
													<input type="hidden" name="event_id" value="<?php echo (int)($event['id'] ?? 0); ?>">
													<input type="hidden" name="portfolio_id" value="<?php echo (int) ($selectionItem['portfolio_id'] ?? 0); ?>">
													<input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8'); ?>">
													<button type="submit" class="inline-flex rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-800 hover:bg-red-100">
														Rimuovi
													</button>
												</form>
											</div>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>

								<?php if (!empty($cosplayPortfolio)): ?>
									<div class="space-y-3">
										<p class="block text-sm font-bold text-gray-900">Aggiungi dal portfolio</p>
										<div class="grid gap-3">
											<?php foreach ($cosplayPortfolio as $cosplayItem): ?>
												<?php $isAlreadySelected = in_array((int) $cosplayItem['id'], $selectedPortfolioIds, true); ?>
												<form
													method="post"
													action="/dashboard/cosplay/event/save"
													class="js-cosplay-event-save rounded-xl border border-gray-200 bg-white p-4 <?php echo $isAlreadySelected ? 'hidden' : ''; ?>"
													data-portfolio-card
													data-portfolio-id="<?php echo (int) $cosplayItem['id']; ?>"
													data-cosplay-label="<?php echo htmlspecialchars($cosplayItem['custom_name'] ?: ($cosplayItem['name_full'] ?? 'Cosplay'), ENT_QUOTES, 'UTF-8'); ?>"
												>
													<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
													<input type="hidden" name="event_id" value="<?php echo (int)($event['id'] ?? 0); ?>">
													<input type="hidden" name="portfolio_id" value="<?php echo (int) $cosplayItem['id']; ?>">
													<input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8'); ?>">
													<div class="flex items-center justify-between gap-3">
														<div>
															<p class="text-sm font-semibold text-gray-900">
																<?php echo htmlspecialchars($cosplayItem['custom_name'] ?: ($cosplayItem['name_full'] ?? 'Cosplay'), ENT_QUOTES, 'UTF-8'); ?>
															</p>
															<p class="text-xs text-gray-500">
																<?php echo htmlspecialchars($cosplayItem['name_full'] ?: 'Personaggio Anilist', ENT_QUOTES, 'UTF-8'); ?>
															</p>
														</div>
														<div class="flex flex-wrap gap-2">
															<button type="submit" name="status" value="porterò" class="inline-flex items-center justify-center rounded-lg bg-fuchsia-800 px-4 py-3 text-sm font-bold text-white hover:bg-fuchsia-900">
																Porterò
															</button>
															<button type="submit" name="status" value="forse" class="inline-flex items-center justify-center rounded-lg border border-fuchsia-300 bg-white px-4 py-3 text-sm font-bold text-fuchsia-900 hover:bg-fuchsia-100">
																Forse
															</button>
														</div>
													</div>
												</form>
											<?php endforeach; ?>
										</div>
									</div>
								<?php else: ?>
									<p class="text-sm text-gray-700">Non hai ancora un portfolio cosplay.</p>
									<a href="/dashboard/cosplay" class="mt-3 inline-flex w-full items-center justify-center rounded-lg bg-fuchsia-800 px-4 py-3 font-bold text-white hover:bg-fuchsia-900">
										Crea il portfolio
									</a>
								<?php endif; ?>
							<?php else: ?>
								<a href="/login" class="inline-flex w-full items-center justify-center rounded-lg bg-fuchsia-800 px-4 py-3 font-bold text-white hover:bg-fuchsia-900">
									Collega un cosplay
								</a>
							<?php endif; ?>
						</section>
						<?php endif; ?>

						<?php
						$socialLinks = [
							'Facebook' => $event['social_facebook'] ?? '',
							'Twitter' => $event['social_twitter'] ?? '',
							'Instagram' => $event['social_instagram'] ?? '',
							'TikTok' => $event['social_tiktok'] ?? '',
							'YouTube' => $event['social_youtube'] ?? '',
						];
						$socialLinks = array_filter($socialLinks);
						?>
						<?php if (!empty($socialLinks)): ?>
							<section class="rounded-xl border border-gray-200 bg-white p-5" aria-labelledby="canali-ufficiali">
								<h2 id="canali-ufficiali" class="mb-4 text-xl font-bold text-gray-900">
									Canali ufficiali
								</h2>
								<ul class="space-y-3 text-sm">
									<?php foreach ($socialLinks as $label => $url): ?>
										<li>
											<a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="font-semibold text-blue-800 hover:underline">
												<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							</section>
						<?php endif; ?>

						<section class="rounded-xl border border-gray-200 bg-white p-5" aria-labelledby="nota-evento">
							<h2 id="nota-evento" class="mb-3 text-xl font-bold text-gray-900">
								Nota sull'evento
							</h2>
							<p class="text-sm leading-relaxed text-gray-700">
								Lo stato dell'evento, eventuali modifiche al programma e le informazioni sui biglietti sono sempre da verificare sui canali ufficiali dell'organizzazione.
							</p>
							<p class="mt-3 text-sm leading-relaxed text-gray-700">
								Per richieste di modifica o rimozione scrivi a mail@italiancosplay.it.
							</p>
						</section>
					</aside>
				</div>
			</article>
		</div>
	</main>

	<?php if (!empty($event['latitudine']) && !empty($event['longitudine'])): ?>
		<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
		<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
		<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				const latitude = <?php echo json_encode((float)$event['latitudine']); ?>;
				const longitude = <?php echo json_encode((float)$event['longitudine']); ?>;
				const eventTitle = <?php echo json_encode($eventTitle); ?>;
				const eventLocation = <?php echo json_encode($fullLocation); ?>;
				const apiKey = "eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6Ijk1YTkzNTZjOGVjMTQwNTdiMmVkNDE2ZTdlMWNlNTJmIiwiaCI6Im11cm11cjY0In0=";

				if (!latitude || !longitude || typeof L === 'undefined') {
					return;
				}

				const map = L.map('mapid').setView([latitude, longitude], 13);

				L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
					attribution: '&copy; OpenStreetMap contributors'
				}).addTo(map);

				const destination = L.latLng(latitude, longitude);
				L.marker(destination)
					.addTo(map)
					.bindPopup('<b>' + eventTitle + '</b><br>' + eventLocation)
					.openPopup();

				let control = null;
				const routeButton = document.getElementById('calc-route');
				const addressInput = document.getElementById('start-address');

				if (!routeButton || !addressInput) {
					return;
				}

				routeButton.addEventListener('click', function() {
					const address = addressInput.value.trim();

					if (!address) {
						alert('Inserisci un punto di partenza');
						return;
					}

					fetch(`https://api.openrouteservice.org/geocode/search?api_key=${apiKey}&text=${encodeURIComponent(address)}&size=1`)
						.then(res => res.json())
						.then(data => {
							if (data.features && data.features.length > 0) {
								const coords = data.features[0].geometry.coordinates;
								const start = L.latLng(coords[1], coords[0]);

								if (control) {
									map.removeControl(control);
								}

								control = L.Routing.control({
									waypoints: [start, destination],
									lineOptions: {
										styles: [{color: '#007bff', opacity: 0.8, weight: 5}]
									},
									router: L.Routing.osrmv1({
										serviceUrl: 'https://router.project-osrm.org/route/v1'
									}),
									routeWhileDragging: false,
									createMarker: function(i, wp) {
										return L.marker(wp.latLng, {draggable: false});
									},
									show: true
								}).addTo(map);
							} else {
								alert('Indirizzo non trovato. Riprova.');
							}
						})
						.catch(err => {
							console.error(err);
							alert('Errore nel calcolo del percorso');
						});
				});
			});
		</script>
	<?php endif; ?>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const selectionBox = document.querySelector('[data-cosplay-selection-box]');
			if (!selectionBox) {
				return;
			}
			const eventId = <?php echo json_encode((int) ($event['id'] ?? 0)); ?>;
			const eventUrl = <?php echo json_encode($eventUrl); ?>;

			const showToast = (message, success = true) => {
				let toast = document.getElementById('cosplay-event-toast');
				if (!toast) {
					toast = document.createElement('div');
					toast.id = 'cosplay-event-toast';
					toast.className = 'fixed bottom-5 right-5 z-50 rounded-xl px-4 py-3 text-sm font-bold shadow-xl opacity-0 transition-opacity';
					document.body.appendChild(toast);
				}

				toast.className = `fixed bottom-5 right-5 z-50 rounded-xl px-4 py-3 text-sm font-bold shadow-xl transition-opacity ${success ? 'bg-green-600 text-white' : 'bg-red-600 text-white'}`;
				toast.textContent = message;
				toast.style.opacity = '1';
				window.clearTimeout(toast._hideTimer);
				toast._hideTimer = window.setTimeout(() => {
					toast.style.opacity = '0';
				}, 2200);
			};

			const getSummary = () => {
				let summary = selectionBox.querySelector('[data-cosplay-selection-summary]');
				if (!summary) {
					summary = document.createElement('div');
					summary.className = 'mb-4 space-y-2';
					summary.dataset.cosplaySelectionSummary = '1';
					summary.innerHTML = '<p class="text-sm font-semibold text-fuchsia-900">Cosplay già associati</p>';
					selectionBox.prepend(summary);
				}
				return summary;
			};

			const renderSelections = (selections) => {
				const selected = Array.isArray(selections) ? selections : [];
				const selectedIds = new Set(selected.map((item) => String(item.portfolio_id || '')));

				selectionBox.querySelectorAll('[data-portfolio-card]').forEach((form) => {
					const portfolioId = String(form.dataset.portfolioId || '');
					form.classList.toggle('hidden', selectedIds.has(portfolioId));
				});

				let summary = selectionBox.querySelector('[data-cosplay-selection-summary]');
				if (selected.length === 0) {
					summary?.remove();
					return;
				}

				summary = getSummary();
				summary.innerHTML = '<p class="text-sm font-semibold text-fuchsia-900">Cosplay già associati</p>';

				selected.forEach((item) => {
					const portfolioId = String(item.portfolio_id || '');
					const label = item.custom_name || item.name_full || 'Cosplay';
					const statusLabel = item.status === 'forse' ? 'Forse lo porterò' : 'Porterò';
					const row = document.createElement('div');
					row.className = 'flex items-center justify-between gap-3 rounded-xl border border-fuchsia-200 bg-white p-3';
					row.dataset.cosplaySelectionRow = '1';
					row.dataset.portfolioId = portfolioId;

					const textWrap = document.createElement('div');
					textWrap.className = 'min-w-0';

					const text = document.createElement('p');
					text.className = 'truncate text-sm font-semibold text-gray-900';
					text.dataset.cosplaySelectionLabel = '1';
					text.textContent = label;

					const status = document.createElement('p');
					status.className = 'text-xs font-semibold uppercase tracking-wide text-fuchsia-900';
					status.dataset.cosplaySelectionStatus = '1';
					status.textContent = statusLabel;

					textWrap.appendChild(text);
					textWrap.appendChild(status);

					const removeForm = document.createElement('form');
					removeForm.method = 'post';
					removeForm.action = '/dashboard/cosplay/event/remove';
					removeForm.className = 'js-cosplay-event-remove';
					removeForm.dataset.confirm = 'Vuoi rimuovere questo collegamento?';

					removeForm.innerHTML = `
						<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
						<input type="hidden" name="event_id" value="${eventId}">
						<input type="hidden" name="portfolio_id" value="${portfolioId}">
						<input type="hidden" name="redirect_to" value="${eventUrl}">
						<button type="submit" class="inline-flex rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-800 hover:bg-red-100">Rimuovi</button>
					`;

					row.appendChild(textWrap);
					row.appendChild(removeForm);
					summary.appendChild(row);
				});
			};

			document.addEventListener('submit', async (event) => {
				const form = event.target;
				if (!(form instanceof HTMLFormElement) || (!form.classList.contains('js-cosplay-event-save') && !form.classList.contains('js-cosplay-event-remove'))) {
					return;
				}

				event.preventDefault();

				const formData = new FormData(form);
				if (event.submitter instanceof HTMLButtonElement && event.submitter.name) {
					formData.set(event.submitter.name, event.submitter.value);
				}

				const response = await fetch(form.action, {
					method: 'POST',
					headers: {
						'X-Requested-With': 'XMLHttpRequest',
						'Accept': 'application/json',
					},
					body: formData,
				});

				const data = await response.json().catch(() => ({}));
				if (!response.ok || !data.success) {
					showToast(data.message || 'Operazione non riuscita.', false);
					return;
				}

				renderSelections(Array.isArray(data.selections) ? data.selections : (Array.isArray(data.selection) ? data.selection : []));
				showToast(data.message || 'Operazione completata.');
			});
		});
	</script>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const description = document.getElementById('descrizione-evento-contenuto');
			const toggle = document.querySelector('[data-description-toggle]');
			const mobileQuery = window.matchMedia('(max-width: 767px)');

			if (!description || !toggle) {
				return;
			}

			let expandedScrollPosition = null;

			const syncDescriptionToggle = () => {
				if (!mobileQuery.matches) {
					description.classList.remove('is-collapsed');
					toggle.hidden = true;
					return;
				}

				toggle.hidden = description.scrollHeight <= description.clientHeight;
			};

			toggle.addEventListener('click', function() {
				const currentScrollPosition = window.scrollY;
				const wasExpanded = !description.classList.contains('is-collapsed');

				if (!wasExpanded) {
					expandedScrollPosition = currentScrollPosition;
				}

				const isExpanded = !description.classList.toggle('is-collapsed');
				toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
				toggle.textContent = isExpanded ? 'Mostra meno' : 'Mostra di più';

				// Mantiene l'utente sul punto di lettura quando il contenuto cambia altezza.
				window.requestAnimationFrame(() => window.requestAnimationFrame(() => {
					const targetScrollPosition = wasExpanded && expandedScrollPosition !== null
						? expandedScrollPosition
						: currentScrollPosition;

					toggle.blur();
					window.scrollTo(0, targetScrollPosition);

					if (wasExpanded) {
						expandedScrollPosition = null;
					}
				}));
			});

			syncDescriptionToggle();
			window.addEventListener('resize', syncDescriptionToggle);
		});
	</script>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const copyButton = document.querySelector('.js-copy-event-link');
			const feedback = document.querySelector('.js-copy-event-feedback');

			if (!copyButton) {
				return;
			}

			copyButton.addEventListener('click', async function() {
				const shareUrl = copyButton.getAttribute('data-share-url');

				try {
					if (navigator.clipboard && window.isSecureContext) {
						await navigator.clipboard.writeText(shareUrl);
					} else {
						const input = document.createElement('input');
						input.value = shareUrl;
						input.setAttribute('readonly', 'readonly');
						input.style.position = 'absolute';
						input.style.left = '-9999px';
						document.body.appendChild(input);
						input.select();
						document.execCommand('copy');
						document.body.removeChild(input);
					}

					if (feedback) {
						feedback.classList.remove('hidden');
						window.setTimeout(() => feedback.classList.add('hidden'), 2500);
					}
				} catch (error) {
					window.prompt('Copia il link evento:', shareUrl);
				}
			});
		});
	</script>
<?php else: ?>
	<main class="container mx-auto px-4 py-10 md:px-6">
		<section class="rounded-xl bg-white p-8 text-center text-gray-700 shadow-lg">
			<h1 class="mb-4 text-3xl font-bold text-gray-900">Evento non trovato</h1>
			<p class="mb-6">Spiacenti, non è stato possibile recuperare i dettagli dell'evento.</p>
			<a href="<?php echo htmlspecialchars($eventsBaseUrl, ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex items-center justify-center rounded-lg bg-green-800 px-4 py-3 font-bold text-white hover:bg-green-900">
				Torna agli eventi cosplay
			</a>
		</section>
	</main>
<?php endif; ?>
