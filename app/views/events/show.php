<?php
// Questo file è un frammento di HTML e deve essere incluso in un layout pubblico.
// Non contiene i tag <html>, <head>, <body> completi.

$breadcrumbs = $data['breadcrumbs'] ?? [];
$event = $data['event'] ?? null;
$similarEvents = $data['similarEvents'] ?? ($similarEvents ?? []);
$eventsBaseUrl = rtrim(URL_ROOT_SITE, '/') . '/eventi-cosplay';
$siteBaseUrl = rtrim(URL_ROOT_SITE, '/');

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
	$shareText = trim($eventTitle . ($cityName ? ' a ' . $cityName : '') . ($eventYear ? ' ' . $eventYear : ''));
	$shareUrlEncoded = rawurlencode($eventUrl);
	$shareTextEncoded = rawurlencode($shareText);

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
		</style>
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
						</div>

						<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
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
							<div class="prose max-w-none text-gray-800">
								<?php echo $event['descrizione'] ?? ''; ?>
							</div>

							<p class="mt-5 text-gray-700">
								Consulta anche la lista degli
								<a href="<?php echo htmlspecialchars($eventsBaseUrl, ENT_QUOTES, 'UTF-8'); ?>" class="font-semibold text-green-900 hover:underline">eventi cosplay in Italia</a>
								e gli
								<a href="<?php echo htmlspecialchars($regionUrl, ENT_QUOTES, 'UTF-8'); ?>" class="font-semibold text-green-900 hover:underline">eventi cosplay in <?php echo htmlspecialchars($regionName ?: 'questa regione', ENT_QUOTES, 'UTF-8'); ?></a>.
							</p>
						</section>

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
										<article class="overflow-hidden rounded-lg border border-gray-100 bg-white shadow-md transition hover:shadow-lg">
											<a href="<?php echo htmlspecialchars($similarUrl, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($similarTitle . ' evento cosplay', ENT_QUOTES, 'UTF-8'); ?>">
												<?php if (!empty($similarImage)): ?>
													<img
															src="<?php echo htmlspecialchars($similarImage, ENT_QUOTES, 'UTF-8'); ?>"
															class="h-40 w-full object-cover"
															loading="lazy"
															alt="<?php echo htmlspecialchars($similarTitle . ' evento cosplay', ENT_QUOTES, 'UTF-8'); ?>"
													>
												<?php endif; ?>
												<div class="p-4">
													<h3 class="mb-2 text-lg font-bold text-gray-900">
														<?php echo htmlspecialchars($similarTitle, ENT_QUOTES, 'UTF-8'); ?>
													</h3>
													<?php if ($similarDate): ?>
														<p class="text-sm text-gray-600">Data: <?php echo htmlspecialchars($similarDate, ENT_QUOTES, 'UTF-8'); ?></p>
													<?php endif; ?>
													<?php if ($similarPlace): ?>
														<p class="mt-1 text-sm text-gray-600">Luogo: <?php echo htmlspecialchars($similarPlace, ENT_QUOTES, 'UTF-8'); ?></p>
													<?php endif; ?>
												</div>
											</a>
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
