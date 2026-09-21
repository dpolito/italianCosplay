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
$searchQuery = trim((string) ($data['searchQuery'] ?? ''));
$availableYears = $data['availableYears'] ?? [];

$siteBaseUrl = rtrim(URL_ROOT_SITE, '/');
$eventsBasePath = $siteBaseUrl . '/eventi-cosplay';
$currentLabel = $breadcrumbs ? ($breadcrumbs[array_key_last($breadcrumbs)]['label'] ?? 'Eventi Cosplay Italia') : 'Eventi Cosplay Italia';
$locationName = $selectedComune['nome'] ?? $selectedProvincia['nome'] ?? $selectedRegione['nome'] ?? 'Italia';
$pageTitle = $currentLabel;
$eventCount = count($events);
$currentLocationFavorite = null;
$featureFlags = (new \App\Services\SiteFeatureFlagService())->getEnabledMap();
$telegramChannelUrl = defined('TELEGRAM_CHANNEL_URL') ? trim((string) TELEGRAM_CHANNEL_URL) : '';
if ($telegramChannelUrl === '' && defined('TELEGRAM_CHANNEL_CHAT_ID')) {
	$telegramChannelId = trim((string) TELEGRAM_CHANNEL_CHAT_ID);
	if (str_starts_with($telegramChannelId, '@')) {
		$telegramChannelUrl = 'https://t.me/' . ltrim($telegramChannelId, '@');
	}
}

if (!empty($selectedComune)) {
	$currentLocationFavorite = [
		'label' => 'Segui comune',
		'entity_type' => 'comune',
		'entity_id' => (int) ($selectedComune['id'] ?? 0),
		'url' => $eventsBasePath . '/' . rawurlencode((string) ($selectedRegione['slug'] ?? '')) . '/' . rawurlencode((string) ($selectedProvincia['slug'] ?? '')) . '/' . rawurlencode((string) ($selectedComune['slug'] ?? '')),
		'description' => ($selectedComune['nome'] ?? 'Comune') . (!empty($selectedProvincia['nome']) ? ' · ' . $selectedProvincia['nome'] : ''),
	];
} elseif (!empty($selectedProvincia)) {
	$currentLocationFavorite = [
		'label' => 'Segui provincia',
		'entity_type' => 'provincia',
		'entity_id' => (int) ($selectedProvincia['id'] ?? 0),
		'url' => $eventsBasePath . '/' . rawurlencode((string) ($selectedRegione['slug'] ?? '')) . '/' . rawurlencode((string) ($selectedProvincia['slug'] ?? '')),
		'description' => ($selectedProvincia['nome'] ?? 'Provincia') . (!empty($selectedRegione['nome']) ? ' · ' . $selectedRegione['nome'] : ''),
	];
} elseif (!empty($selectedRegione)) {
	$currentLocationFavorite = [
		'label' => 'Segui regione',
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
$introText = !empty($data['testo_descrittivo'])
	? strip_tags((string) $data['testo_descrittivo'])
	: $introFallback;
$introShort = mb_strimwidth(trim(preg_replace('/\s+/', ' ', $introText)), 0, 210, '...');
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

<style>
	.events-mobile-list {
		display: grid;
	}

	.events-desktop-grid {
		display: none;
	}

	.events-mobile-actions {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 1rem;
	}

	.events-mobile-action {
		align-items: center;
		display: flex;
		justify-content: center;
		min-height: 3rem;
		white-space: normal;
	}

	.events-filter-select {
		-webkit-appearance: menulist;
		appearance: auto;
		background-color: #ffffff !important;
		border: 1px solid #cbd5e1;
		color: #0f172a !important;
		font-size: 1rem;
		font-weight: 600;
		line-height: 1.5;
	}

	.events-filter-select:disabled {
		background-color: #f1f5f9 !important;
		color: #64748b !important;
		opacity: 1;
	}

	.events-filter-select option {
		background-color: #ffffff;
		color: #0f172a;
		font-size: 1rem;
		font-weight: 600;
		line-height: 1.5;
		padding: 0.5rem 0.75rem;
	}

	.events-filter-select option:checked {
		background-color: #059669;
		color: #ffffff;
	}

	.events-filter-select.is-enhanced {
		display: none;
	}

	.events-custom-select {
		position: relative;
	}

	.events-custom-select-button {
		align-items: center;
		background: #ffffff;
		border: 1px solid #10b981;
		border-radius: 0.5rem;
		color: #0f172a;
		display: flex;
		font-size: 1.0625rem;
		font-weight: 700;
		justify-content: space-between;
		line-height: 1.35;
		min-height: 3.25rem;
		padding: 0.75rem 0.875rem;
		width: 100%;
	}

	.events-custom-select-button:disabled {
		background: #f1f5f9;
		border-color: #cbd5e1;
		color: #64748b;
	}

	.events-custom-select-menu {
		background: #ffffff;
		border: 1px solid #cbd5e1;
		border-radius: 0.75rem;
		box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
		display: none;
		left: 0;
		max-height: 18rem;
		overflow-y: auto;
		position: absolute;
		right: 0;
		top: calc(100% + 0.375rem);
		z-index: 60;
	}

	.events-custom-select.is-open .events-custom-select-menu {
		display: block;
	}

	.events-custom-select-option {
		background: #ffffff;
		color: #0f172a;
		display: block;
		font-size: 1.0625rem;
		font-weight: 700;
		line-height: 1.35;
		padding: 0.875rem 1rem;
		text-align: left;
		width: 100%;
	}

	.events-custom-select-option:hover,
	.events-custom-select-option:focus {
		background: #ecfdf5;
		outline: none;
	}

	.events-custom-select-option[aria-selected="true"] {
		background: #059669;
		color: #ffffff;
	}

	.events-description-full {
		color: #334155;
		font-size: 0.9375rem;
		line-height: 1.7;
	}

	.events-description-full h2,
	.events-description-full h3,
	.events-description-full h4 {
		color: #0f172a;
		font-size: 1rem;
		font-weight: 800;
		line-height: 1.35;
		margin-top: 1rem;
	}

	.events-description-full p,
	.events-description-full ul,
	.events-description-full ol {
		margin-top: 0.75rem;
	}

	.events-hero-summary-grid {
		display: grid;
		gap: 1rem;
	}

	.events-mobile-copy {
		display: inline;
	}

	.events-desktop-copy,
	.events-desktop-only {
		display: none;
	}

	@media (min-width: 768px) {
		.events-mobile-actions {
			display: none;
		}

		.events-mobile-list {
			display: none;
		}

		.events-desktop-grid {
			display: grid;
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}

		.events-desktop-only {
			display: block;
		}
	}

	@media (min-width: 1024px) {
		.events-mobile-copy {
			display: none;
		}

		.events-desktop-copy {
			display: inline;
		}

		.events-hero-summary-grid {
			grid-template-columns: minmax(0, 1fr) 16rem;
			align-items: stretch;
		}
	}

	@media (min-width: 1280px) {
		.events-desktop-grid {
			grid-template-columns: repeat(3, minmax(0, 1fr));
		}
	}
</style>

<main class="bg-slate-50 text-slate-900">
	<section class="bg-white">
		<div class="mx-auto max-w-7xl px-4 pt-4 md:px-6 md:pt-7">
			<nav class="mb-4 overflow-x-auto text-sm text-slate-600" aria-label="Breadcrumb">
			<ol class="flex flex-wrap items-center gap-2">
				<?php foreach ($breadcrumbs as $index => $crumb): ?>
					<li class="flex items-center gap-2">
						<?php if ($index > 0): ?>
							<span class="text-slate-300" aria-hidden="true">/</span>
						<?php endif; ?>
						<?php if (!empty($crumb['url'])): ?>
							<a href="<?php echo event_index_h($crumb['url']); ?>" class="font-bold text-emerald-800 hover:text-emerald-950">
								<?php echo event_index_h($crumb['label'] ?? ''); ?>
							</a>
						<?php else: ?>
							<span><?php echo event_index_h($crumb['label'] ?? ''); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>

			<header class="pb-5 md:pb-8">
				<div class="grid gap-5 lg:items-start">
				<div>
						<p class="text-sm font-bold uppercase tracking-wide text-emerald-700">
						Calendario eventi cosplay
					</p>
						<h1 class="mt-2 text-3xl font-black leading-tight tracking-tight text-slate-950 md:text-5xl">
						<?php echo event_index_h($pageTitle); ?>
					</h1>
						<nav class="events-mobile-actions mt-5 md:hidden" aria-label="Scorciatoie eventi cosplay">
							<a href="#eventi-cosplay" class="events-mobile-action rounded-xl bg-emerald-700 px-3 py-2 text-center text-sm font-black leading-tight text-white shadow-sm transition hover:bg-emerald-800">Lista eventi</a>
							<a href="#filtra-eventi" class="events-mobile-action rounded-xl border border-slate-200 bg-white px-3 py-2 text-center text-sm font-black leading-tight text-slate-900 transition hover:border-emerald-300 hover:bg-emerald-50">Filtra zona</a>
							<a href="<?php echo event_index_h($eventsBasePath . '-weekend'); ?>" class="events-mobile-action rounded-xl border border-slate-200 bg-white px-3 py-2 text-center text-sm font-black leading-tight text-slate-900 transition hover:border-emerald-300 hover:bg-emerald-50">Weekend</a>
							<a href="/segnala-evento-cosplay" class="events-mobile-action rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-center text-sm font-black leading-tight text-amber-950 transition hover:bg-amber-100">Segnala</a>
						</nav>

						<div class="events-desktop-only mt-6">
							<div class="flex flex-wrap gap-3">
								<a href="#eventi-cosplay" class="inline-flex rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-emerald-800">Vedi gli eventi</a>
								<a href="#filtra-eventi" class="inline-flex rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-900 transition hover:border-emerald-300 hover:bg-emerald-50">Filtra per localita</a>
								<a href="/segnala-evento-cosplay" class="inline-flex rounded-xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm font-black text-amber-950 transition hover:bg-amber-100">Segnala un evento</a>
								<?php if ($telegramChannelUrl !== ''): ?>
									<a href="<?php echo event_index_h($telegramChannelUrl); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-black text-emerald-950 transition hover:bg-emerald-100">
										<i class="fa-brands fa-telegram" aria-hidden="true"></i>
										<span>Telegram</span>
									</a>
								<?php endif; ?>
							</div>
						</div>

						<div class="events-hero-summary-grid mt-5">
							<div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700 md:text-base md:leading-7">
								<div id="eventi-descrizione-corta">
									<p><?php echo event_index_h($introShort); ?></p>
								</div>
								<div id="eventi-descrizione-completa" class="events-description-full hidden">
									<?php if (!empty($data['testo_descrittivo'])): ?>
										<?php echo $data['testo_descrittivo']; ?>
									<?php else: ?>
										<p><?php echo event_index_h($introFallback); ?></p>
									<?php endif; ?>
								</div>
								<button
									type="button"
									class="mt-3 inline-flex rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-emerald-800 hover:border-emerald-300 hover:bg-emerald-50"
									data-toggle-text
									data-label-show="Mostra tutto"
									data-label-hide="Mostra meno"
									aria-expanded="false"
									aria-controls="eventi-descrizione-completa"
								>
									Mostra tutto
								</button>
							</div>
							<aside class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 shadow-sm" aria-label="Riepilogo eventi">
								<p class="text-sm font-bold text-emerald-900">Eventi trovati</p>
								<p class="mt-1 text-4xl font-black text-slate-950"><?php echo (int)$eventCount; ?></p>
								<p class="mt-2 text-sm leading-6 text-slate-700">
									<?php echo event_index_h($locationName); ?>, aggiornati.
								</p>
								<a href="/segnala-evento-cosplay" class="mt-4 inline-flex w-full justify-center rounded-lg bg-emerald-700 px-4 py-3 text-sm font-black text-white shadow-sm hover:bg-emerald-800">
									Segnala
								</a>
							</aside>
						</div>
				</div>

					<?php if (!empty($featureFlags['enable_favorites']) && !empty($_SESSION['user_id']) && !empty($currentLocationFavorite)): ?>
							<aside class="rounded-xl border border-amber-100 bg-amber-50 p-4 shadow-sm lg:max-w-md" aria-label="Preferiti location">
								<p class="text-sm font-bold text-amber-900">Segui <?php echo event_index_h($locationName); ?></p>
								<p class="mt-1 text-sm leading-6 text-slate-700">Ritrova questa localita nella tua area personale.</p>
							<div class="mt-4">
								<form method="post" action="/dashboard/favorites/toggle" class="js-favorite-toggle">
									<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
									<input type="hidden" name="entity_type" value="<?php echo event_index_h($currentLocationFavorite['entity_type']); ?>">
									<input type="hidden" name="entity_id" value="<?php echo (int) $currentLocationFavorite['entity_id']; ?>">
									<input type="hidden" name="redirect_to" value="<?php echo event_index_h($currentLocationFavorite['url']); ?>">
										<button type="submit" class="js-favorite-button inline-flex w-full items-center justify-center gap-2 rounded-lg border border-amber-300 bg-white px-4 py-3 text-sm font-bold text-amber-900 transition hover:bg-amber-100" data-label-add="<?php echo event_index_h($currentLocationFavorite['label'] ?? 'Segui località'); ?>" data-label-remove="Rimuovi dai preferiti" data-icon-add="fa-bookmark" data-icon-remove="fa-bookmark-slash" data-active="0">
										<i class="fa-solid fa-bookmark" aria-hidden="true"></i>
										<span><?php echo event_index_h($currentLocationFavorite['label'] ?? 'Segui località'); ?></span>
									</button>
								</form>
							</div>
							</aside>
						<?php endif; ?>
			</div>
		</header>
		</div>
	</section>

	<div class="mx-auto max-w-7xl px-4 py-5 md:px-6 md:py-8">

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

			echo \App\Helpers\AdPlacement::render($eventsTopPlacement, 'events', 'mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm', '', $adContext);
		}
		?>

		<nav class="mb-8 grid grid-cols-2 gap-3 md:flex md:flex-wrap md:gap-3" aria-label="Navigazione rapida eventi">
			<a href="<?php echo event_index_h($eventsBasePath); ?>" class="events-mobile-action rounded-xl bg-emerald-700 px-3 py-3 text-center text-sm font-black leading-tight text-white shadow-sm transition hover:bg-emerald-800 md:min-h-0 md:px-5">
				Tutti gli eventi cosplay
			</a>
			<a href="<?php echo event_index_h($eventsBasePath . '-mese'); ?>" class="events-mobile-action rounded-xl border border-slate-200 bg-white px-3 py-3 text-center text-sm font-black leading-tight text-slate-900 transition hover:border-emerald-300 hover:bg-emerald-50 md:min-h-0 md:px-5">
				Eventi cosplay del mese
			</a>
			<a href="<?php echo event_index_h($eventsBasePath . '-weekend'); ?>" class="events-mobile-action rounded-xl border border-slate-200 bg-white px-3 py-3 text-center text-sm font-black leading-tight text-slate-900 transition hover:border-emerald-300 hover:bg-emerald-50 md:min-h-0 md:px-5">
				Eventi cosplay nel weekend
			</a>
			<?php if (!empty($availableYears)): ?>
				<?php $latestYear = end($availableYears); reset($availableYears); ?>
				<a href="<?php echo event_index_h($siteBaseUrl . '/eventi-cosplay-' . (int) ($latestYear['year'] ?? date('Y'))); ?>" class="events-mobile-action rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3 text-center text-sm font-black leading-tight text-emerald-950 transition hover:bg-emerald-100 md:min-h-0 md:px-5">
					Calendario <?php echo (int) ($latestYear['year'] ?? date('Y')); ?>
				</a>
			<?php endif; ?>
			<?php if ($selectedRegione): ?>
				<a href="<?php echo event_index_h(event_index_location_url($eventsBasePath, $selectedRegione)); ?>" class="events-mobile-action rounded-xl border border-slate-200 bg-white px-3 py-3 text-center text-sm font-black leading-tight text-slate-900 transition hover:border-emerald-300 hover:bg-emerald-50 md:min-h-0 md:px-5">
					Prossimi eventi in <?php echo event_index_h($selectedRegione['nome']); ?>
				</a>
			<?php else: ?>
				<a href="#eventi-cosplay" class="events-mobile-action rounded-xl border border-slate-200 bg-white px-3 py-3 text-center text-sm font-black leading-tight text-slate-900 transition hover:border-emerald-300 hover:bg-emerald-50 md:min-h-0 md:px-5">
					Vedi prossimi eventi
				</a>
			<?php endif; ?>
		</nav>

		<?php if (!empty($eventsInlinePlacement)): ?>
			<?php echo \App\Helpers\AdPlacement::render($eventsInlinePlacement, 'events', 'mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm', '', $adContext ?? ['page_type' => 'national']); ?>
		<?php endif; ?>

		<section id="filtra-eventi" class="relative z-30 mb-8 rounded-xl border border-emerald-200 bg-white p-4 shadow-sm md:sticky md:top-4 md:p-5">
			<div class="mb-4 flex items-end justify-between gap-4">
				<div>
					<p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Scegli zona</p>
					<h2 class="text-xl font-black text-slate-950">Filtra per localita</h2>
				</div>
				<a href="<?php echo event_index_h($eventsBasePath); ?>" class="text-sm font-bold text-emerald-800 hover:text-emerald-950">Reset</a>
			</div>
			<div class="grid grid-cols-1 gap-3 md:grid-cols-3">
				<div>
					<label for="filter_regione" class="mb-1 block text-sm font-semibold text-slate-800">Regione</label>
					<select id="filter_regione" name="regione_id" class="events-filter-select block w-full rounded-lg px-3 py-3 text-base shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500">
						<option value="">Tutte le regioni</option>
						<?php foreach ($allRegioni as $regione): ?>
							<option value="<?php echo event_index_h($regione['slug']); ?>" <?php echo ($selectedRegione && $selectedRegione['id'] == $regione['id']) ? 'selected' : ''; ?>>
								<?php echo event_index_h($regione['nome']); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label for="filter_provincia" class="mb-1 block text-sm font-semibold text-slate-800">Provincia</label>
					<select id="filter_provincia" name="provincia_id" class="events-filter-select block w-full rounded-lg px-3 py-3 text-base shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500" <?php echo (!$selectedRegione) ? 'disabled' : ''; ?>>
						<option value="">Tutte le province</option>
						<?php foreach ($allProvince as $provincia): ?>
							<option value="<?php echo event_index_h($provincia['slug']); ?>" <?php echo ($selectedProvincia && $selectedProvincia['id'] == $provincia['id']) ? 'selected' : ''; ?>>
								<?php echo event_index_h($provincia['nome']); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label for="filter_comune" class="mb-1 block text-sm font-semibold text-slate-800">Comune</label>
					<select id="filter_comune" name="comune_id" class="events-filter-select block w-full rounded-lg px-3 py-3 text-base shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500" <?php echo (!$selectedProvincia) ? 'disabled' : ''; ?>>
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
			<div class="mb-4 flex flex-col gap-2 md:mb-6 md:flex-row md:items-end md:justify-between">
				<div>
					<p class="text-sm font-bold uppercase tracking-wide text-emerald-700">Prossime date</p>
					<h2 id="lista-eventi" class="mt-1 text-2xl font-black leading-tight text-slate-950 md:text-4xl">
						<?php if ($searchQuery !== ''): ?>
							Eventi trovati per “<?php echo event_index_h($searchQuery); ?>”
						<?php else: ?>
							Prossimi eventi cosplay <?php echo $locationName !== 'Italia' ? 'in ' . event_index_h($locationName) : 'in Italia'; ?>
						<?php endif; ?>
					</h2>
					<p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 md:text-base">
						Apri una scheda evento per scoprire programma, date, indirizzo, social e altri eventi correlati.
					</p>
				</div>
			</div>

			<?php if (!empty($events)): ?>
				<div class="events-mobile-list gap-3">
					<?php foreach ($events as $index => $event): ?>
						<?php
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
						$loading = ($index < 2) ? 'eager' : 'lazy';
						$fetchPriority = ($index === 0) ? 'high' : 'auto';
						$agendaStatus = $agendaStates[$event['id']] ?? null;
						$agendaLabels = [
							'mi_interessa' => ['label' => 'Mi interessa', 'class' => 'bg-blue-100 text-blue-800'],
							'ci_vado' => ['label' => 'Ci vado', 'class' => 'bg-emerald-100 text-emerald-800'],
							'forse_vado' => ['label' => 'Forse vado', 'class' => 'bg-amber-100 text-amber-900'],
						];
						$locationLabel = implode(' · ', array_filter([$eventCity, $event['provincia_nome'] ?? '', $eventRegion]));
						?>
						<article class="group overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:border-emerald-300 hover:shadow-md focus-within:ring-2 focus-within:ring-emerald-700">
							<a href="<?php echo event_index_h($eventUrl); ?>" class="grid grid-cols-[6.25rem_1fr] gap-3 p-2" title="<?php echo event_index_h($cardTitle); ?>">
								<div class="relative aspect-[4/3] overflow-hidden rounded-lg bg-slate-100">
									<?php if ($imageUrl): ?>
										<img src="<?php echo event_index_h($imageUrl); ?>" width="<?php echo event_index_h($event['immagine_width'] ?? 800); ?>" height="<?php echo event_index_h($event['immagine_height'] ?? 500); ?>" loading="<?php echo $loading; ?>" fetchpriority="<?php echo $fetchPriority; ?>" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]" alt="<?php echo event_index_h($cardTitle . ' evento cosplay'); ?>">
									<?php else: ?>
										<div class="flex h-full w-full items-center justify-center px-2 text-center text-xs font-bold text-slate-500">Foto non disponibile</div>
									<?php endif; ?>
								</div>
								<div class="min-w-0 py-1">
									<div class="flex flex-wrap gap-1.5">
										<span class="rounded-full px-2 py-1 text-[0.68rem] font-bold leading-none <?php echo event_index_h($status['class']); ?>"><?php echo event_index_h($status['label']); ?></span>
										<?php if (!empty($agendaStatus) && isset($agendaLabels[$agendaStatus])): ?>
											<span data-agenda-badge class="rounded-full px-2 py-1 text-[0.68rem] font-bold leading-none <?php echo event_index_h($agendaLabels[$agendaStatus]['class']); ?>"><?php echo event_index_h($agendaLabels[$agendaStatus]['label']); ?></span>
										<?php endif; ?>
									</div>
									<p class="mt-2 text-xs font-bold uppercase tracking-wide text-emerald-700"><time datetime="<?php echo event_index_h($startDate ?? ''); ?>"><?php echo event_index_h($dateLabel); ?></time></p>
									<h3 class="mt-1 line-clamp-2 text-base font-black leading-tight text-slate-950 group-hover:text-emerald-900"><?php echo event_index_h($cardTitle); ?></h3>
									<?php if ($locationLabel !== ''): ?>
										<p class="mt-1 truncate text-sm text-slate-600"><?php echo event_index_h($locationLabel); ?></p>
									<?php endif; ?>
									<span class="mt-2 inline-flex text-sm font-bold text-emerald-800">Apri scheda</span>
								</div>
							</a>
							<?php if (!empty($featureFlags['enable_personal_agenda']) && !empty($_SESSION['user_id'])): ?>
								<div class="border-t border-slate-100 bg-slate-50 p-3">
									<div class="grid grid-cols-3 gap-2">
										<?php foreach ([
											'mi_interessa' => 'Interessa',
											'ci_vado' => 'Ci vado',
											'forse_vado' => 'Forse',
										] as $agendaValue => $agendaLabel): ?>
											<form method="post" action="/eventi-cosplay/agenda/update" class="js-agenda-toggle">
												<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
												<input type="hidden" name="event_id" value="<?php echo (int) $event['id']; ?>">
												<input type="hidden" name="status" value="<?php echo event_index_h($agendaValue); ?>">
												<input type="hidden" name="redirect_to" value="<?php echo event_index_h($eventUrl); ?>">
												<button type="submit" class="js-agenda-button w-full rounded-lg border px-2 py-2 text-xs font-bold transition <?php echo ($agendaStatus === $agendaValue) ? 'border-emerald-700 bg-emerald-700 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-emerald-50'; ?>" data-agenda-status="<?php echo event_index_h($agendaValue); ?>" data-active="<?php echo ($agendaStatus === $agendaValue) ? '1' : '0'; ?>">
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

				<div class="events-desktop-grid gap-5">
					<?php foreach ($events as $index => $event): ?>
						<?php
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
						<article class="group overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md focus-within:ring-2 focus-within:ring-emerald-700">
							<a href="<?php echo event_index_h($eventUrl); ?>" class="block" title="<?php echo event_index_h($cardTitle); ?>">
								<div class="relative aspect-[16/10] overflow-hidden bg-slate-100">
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
										<div class="flex h-full w-full items-center justify-center px-4 text-center text-sm font-semibold text-slate-500">
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
									<p class="mb-2 text-sm font-bold text-emerald-800">
										<time datetime="<?php echo event_index_h($startDate ?? ''); ?>"><?php echo event_index_h($dateLabel); ?></time>
									</p>
									<h3 class="text-xl font-black leading-snug text-slate-950 group-hover:text-emerald-900">
										<?php echo event_index_h($cardTitle); ?>
									</h3>
									<p class="mt-3 text-sm font-semibold text-slate-700">
										<?php echo event_index_h(implode(' · ', array_filter([$eventCity, $event['provincia_nome'] ?? '', $eventRegion]))); ?>
									</p>
									<?php if (!empty($event['tipo_evento_nome'])): ?>
										<p class="mt-2 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
											<?php echo event_index_h($event['tipo_evento_nome']); ?>
										</p>
									<?php endif; ?>
									<?php if ($description): ?>
										<p class="mt-3 text-sm leading-relaxed text-slate-600">
											<?php echo event_index_h($description); ?>
										</p>
									<?php endif; ?>
									<span class="mt-4 inline-flex font-bold text-emerald-800 group-hover:underline">
										Vedi dettagli evento
									</span>
								</div>
							</a>
							<?php if (!empty($featureFlags['enable_personal_agenda']) && !empty($_SESSION['user_id'])): ?>
								<div class="border-t border-slate-100 bg-slate-50 p-4">
									<p class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">Agenda personale</p>
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
												<button type="submit" class="js-agenda-button w-full rounded-lg border px-3 py-2 text-xs font-bold transition <?php echo ($agendaStatus === $agendaValue) ? 'border-emerald-700 bg-emerald-700 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-emerald-50'; ?>" data-agenda-status="<?php echo event_index_h($agendaValue); ?>" data-active="<?php echo ($agendaStatus === $agendaValue) ? '1' : '0'; ?>">
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
					<h2 class="text-2xl font-bold text-gray-950">
						<?php echo $searchQuery !== '' ? 'Nessun evento trovato con questa ricerca' : 'Nessun evento trovato'; ?>
					</h2>
					<p class="mx-auto mt-3 max-w-2xl text-gray-700">
						<?php if ($searchQuery !== ''): ?>
							Prova con una variante del nome, un acronimo, la città o il nome della location. Se l’evento manca davvero, puoi segnalarlo allo staff.
						<?php else: ?>
							Non ci sono eventi cosplay pubblicati per questa localita. Puoi esplorare tutti gli eventi in Italia oppure tornare a controllare nei prossimi giorni.
						<?php endif; ?>
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
	const enhancedSelects = new Map();

	function redirect(url) {
		window.location.href = url;
	}

	function closeCustomSelects(except = null) {
		enhancedSelects.forEach((enhanced) => {
			if (enhanced.wrapper !== except) {
				enhanced.wrapper.classList.remove('is-open');
				enhanced.button.setAttribute('aria-expanded', 'false');
			}
		});
	}

	function syncCustomSelect(select) {
		const enhanced = enhancedSelects.get(select);
		if (!enhanced) {
			return;
		}

		const selectedOption = select.options[select.selectedIndex] || select.options[0];
		enhanced.button.disabled = select.disabled;
		enhanced.button.querySelector('[data-custom-select-label]').textContent = selectedOption ? selectedOption.textContent : '';
		enhanced.menu.innerHTML = '';

		Array.from(select.options).forEach((option) => {
			const optionButton = document.createElement('button');
			optionButton.type = 'button';
			optionButton.className = 'events-custom-select-option';
			optionButton.textContent = option.textContent;
			optionButton.dataset.value = option.value;
			optionButton.setAttribute('role', 'option');
			optionButton.setAttribute('aria-selected', option.selected ? 'true' : 'false');
			optionButton.addEventListener('click', () => {
				select.value = option.value;
				closeCustomSelects();
				select.dispatchEvent(new Event('change', { bubbles: true }));
			});
			enhanced.menu.appendChild(optionButton);
		});
	}

	function syncCustomSelects() {
		enhancedSelects.forEach((_, select) => syncCustomSelect(select));
	}

	function enhanceSelect(select) {
		if (!select || enhancedSelects.has(select)) {
			return;
		}

		const wrapper = document.createElement('div');
		wrapper.className = 'events-custom-select';
		const button = document.createElement('button');
		button.type = 'button';
		button.className = 'events-custom-select-button';
		button.setAttribute('aria-haspopup', 'listbox');
		button.setAttribute('aria-expanded', 'false');
		button.innerHTML = '<span data-custom-select-label></span><span aria-hidden="true">⌄</span>';
		const menu = document.createElement('div');
		menu.className = 'events-custom-select-menu';
		menu.setAttribute('role', 'listbox');

		select.classList.add('is-enhanced');
		select.parentNode.insertBefore(wrapper, select.nextSibling);
		wrapper.appendChild(button);
		wrapper.appendChild(menu);
		enhancedSelects.set(select, { wrapper, button, menu });

		button.addEventListener('click', () => {
			if (button.disabled) {
				return;
			}

			const willOpen = !wrapper.classList.contains('is-open');
			closeCustomSelects(wrapper);
			wrapper.classList.toggle('is-open', willOpen);
			button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
		});

		select.addEventListener('change', () => syncCustomSelect(select));
		new MutationObserver(() => syncCustomSelect(select)).observe(select, {
			attributes: true,
			childList: true,
			subtree: true,
		});
		syncCustomSelect(select);
	}

	[filterRegione, filterProvincia, filterComune].forEach(enhanceSelect);

	document.addEventListener('click', (event) => {
		const clickedInside = Array.from(enhancedSelects.values()).some((enhanced) => enhanced.wrapper.contains(event.target));
		if (!clickedInside) {
			closeCustomSelects();
		}
	});

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
		syncCustomSelect(filterProvincia);
	}

	function resetComuni() {
		filterComune.innerHTML = '<option value="">Tutti i comuni</option>';
		filterComune.disabled = true;
		syncCustomSelect(filterComune);
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
		syncCustomSelect(filterProvincia);
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
		syncCustomSelect(filterComune);
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
