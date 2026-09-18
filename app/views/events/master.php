<?php
$eventMaster = $data['eventMaster'] ?? [];
$events = $data['events'] ?? [];
$breadcrumbs = $data['breadcrumbs'] ?? [];
$canonicalUrl = $data['canonicalUrl'] ?? '';
$coverUrl = $data['coverUrl'] ?? '';
$eventCount = (int) ($data['eventCount'] ?? count($events));
$organizations = $data['organizations'] ?? [];
$hasOrganizationAssociation = !empty($data['hasOrganizationAssociation']);
$claimUrl = $data['claimUrl'] ?? '';
$siteBaseUrl = rtrim(URL_ROOT_SITE, '/');

if (!function_exists('event_master_h')) {
	function event_master_h($value): string
	{
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('event_master_abs')) {
	function event_master_abs(?string $url, string $siteBaseUrl): string
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

$plainDescription = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($eventMaster['descrizione'] ?? '', ENT_QUOTES, 'UTF-8'))));
$shortDescription = mb_strimwidth($plainDescription, 0, 220, '...');
$pageTitle = trim(($eventMaster['nome'] ?? 'Evento e edizioni') . ($eventCount > 0 ? ' - ' . $eventCount . ' edizioni' : ''));
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

$schema = [
	'@context' => 'https://schema.org',
	'@type' => 'CollectionPage',
	'name' => $pageTitle,
	'description' => $shortDescription ?: ($eventMaster['nome'] ?? 'Evento e edizioni'),
	'url' => $canonicalUrl,
];

if (!empty($coverUrl)) {
	$schema['image'] = [$coverUrl];
}
?>

<script type="application/ld+json"><?php echo json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<script type="application/ld+json"><?php echo json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

<main class="bg-gray-100">
	<div class="container mx-auto px-4 py-6 md:px-6">
		<?php if ($message = \App\Core\Session::getFlash('error')): ?>
			<div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?php echo event_master_h($message); ?></div>
		<?php endif; ?>
		<?php if ($message = \App\Core\Session::getFlash('success')): ?>
			<div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?php echo event_master_h($message); ?></div>
		<?php endif; ?>
		<nav class="mb-5 text-sm text-gray-700" aria-label="Breadcrumb">
			<ol class="flex flex-wrap items-center gap-2">
				<?php foreach ($breadcrumbs as $index => $crumb): ?>
					<li class="flex items-center gap-2">
						<?php if ($index > 0): ?>
							<span class="text-gray-400" aria-hidden="true">/</span>
						<?php endif; ?>
						<?php if (!empty($crumb['url'])): ?>
							<a href="<?php echo event_master_h($crumb['url']); ?>" class="font-medium text-green-900 hover:underline">
								<?php echo event_master_h($crumb['label'] ?? ''); ?>
							</a>
						<?php else: ?>
							<span><?php echo event_master_h($crumb['label'] ?? ''); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>

		<header class="overflow-hidden rounded-2xl bg-white shadow-xl">
			<div class="grid gap-0 lg:grid-cols-[minmax(0,1.1fr)_minmax(340px,0.9fr)]">
				<div class="p-5 md:p-8">
					<p class="mb-2 text-sm font-bold uppercase tracking-[0.25em] text-green-900">
						Evento e edizioni
					</p>
					<h1 class="text-3xl font-extrabold leading-tight text-gray-950 md:text-5xl">
						<?php echo event_master_h($eventMaster['nome'] ?? 'Evento e edizioni'); ?>
					</h1>
					<?php if (!empty($organizations)): ?>
						<div class="mt-4 flex flex-wrap items-center gap-2 text-sm text-gray-600">
							<span>Organizzato da</span>
							<?php foreach ($organizations as $organization): ?>
								<a href="/organizzazioni/<?php echo rawurlencode((string) $organization['slug']); ?>" class="font-bold text-green-900 hover:underline">
									<?php echo event_master_h($organization['name']); ?>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<div class="mt-6 flex flex-wrap gap-3">
						<?php if (!$hasOrganizationAssociation && !empty($_SESSION['user_id']) && $claimUrl !== ''): ?>
							<a href="<?php echo event_master_h($claimUrl); ?>" class="inline-flex rounded-lg bg-amber-500 px-5 py-3 font-bold text-gray-950 shadow hover:bg-amber-400">Riscatta evento</a>
						<?php elseif (!$hasOrganizationAssociation): ?>
							<a href="/register" class="inline-flex rounded-lg bg-amber-500 px-5 py-3 font-bold text-gray-950 shadow hover:bg-amber-400">Registrati per riscattare l’evento</a>
						<?php endif; ?>
						<?php if (!empty($eventMaster['sito_web'])): ?>
							<a href="<?php echo event_master_h(event_master_abs($eventMaster['sito_web'], $siteBaseUrl)); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-lg bg-green-800 px-5 py-3 font-bold text-white shadow hover:bg-green-900">
								Sito ufficiale
							</a>
						<?php endif; ?>
						<?php if (!empty($events)): ?>
							<a href="#edizioni" class="inline-flex rounded-lg border border-green-200 bg-white px-5 py-3 font-bold text-green-900 hover:border-green-300 hover:bg-green-50">
								Vedi edizioni
							</a>
						<?php endif; ?>
					</div>
				</div>

				<div class="bg-slate-950 text-white">
					<?php if (!empty($coverUrl)): ?>
						<div class="relative h-64 w-full md:h-80">
							<img src="<?php echo event_master_h($coverUrl); ?>" alt="<?php echo event_master_h($eventMaster['nome'] ?? 'Evento e edizioni'); ?>" class="h-full w-full object-cover">
							<div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/30 to-transparent"></div>
						</div>
					<?php else: ?>
						<div class="flex h-64 items-center justify-center bg-gradient-to-br from-green-900 via-green-950 to-slate-950 md:h-80">
							<div class="px-6 text-center">
								<p class="text-sm font-semibold uppercase tracking-[0.25em] text-green-100">Evento e edizioni</p>
								<p class="mt-3 text-2xl font-bold text-white">
									<?php echo event_master_h($eventMaster['nome'] ?? 'Evento e edizioni'); ?>
								</p>
							</div>
						</div>
					<?php endif; ?>

					<div class="p-5 md:p-8">
						<div class="grid grid-cols-2 gap-3">
							<div class="rounded-xl bg-white/10 p-4">
								<p class="text-xs uppercase tracking-[0.2em] text-green-100">Edizioni</p>
								<p class="mt-2 text-3xl font-extrabold"><?php echo (int) $eventCount; ?></p>
							</div>
							<div class="rounded-xl bg-white/10 p-4">
								<p class="text-xs uppercase tracking-[0.2em] text-green-100">Tipo pagina</p>
								<p class="mt-2 text-lg font-bold">Hub evento</p>
							</div>
						</div>
						<p class="mt-4 text-sm leading-relaxed text-slate-200">
							<?php echo $eventCount > 1 ? 'Questa pagina raccoglie le edizioni dello stesso evento e le rende più facili da esplorare.' : 'Al momento è disponibile una sola edizione collegata.'; ?>
						</p>
					</div>
				</div>
			</div>
		</header>

		<?php if (!empty($eventMaster['descrizione'])): ?>
			<section class="mt-8 rounded-2xl bg-white p-6 shadow-md md:p-8">
				<h2 class="text-2xl font-bold text-gray-950">Informazioni sull'evento</h2>
				<div class="mt-5 space-y-4 text-base leading-7 text-gray-700">
					<?php echo $eventMaster['descrizione']; ?>
				</div>
			</section>
		<?php endif; ?>

		<section id="edizioni" class="mt-8 rounded-2xl bg-white p-6 shadow-md md:p-8">
			<h2 class="mb-5 text-2xl font-bold text-gray-950">
				Edizioni disponibili
			</h2>

			<?php if (!empty($events)): ?>
				<div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
					<?php foreach ($events as $event): ?>
						<?php
						$eventUrl = $siteBaseUrl . '/eventi-cosplay/' . rawurlencode((string) ($event['slug'] ?? ''));
						$eventDate = !empty($event['data_inizio']) ? date('d/m/Y', strtotime($event['data_inizio'])) : '';
						$eventYear = !empty($event['year']) ? (string) $event['year'] : '';
						?>
						<article class="rounded-lg border border-gray-100 bg-gray-50 p-5 shadow-sm">
							<h3 class="text-lg font-bold text-gray-900">
								<a href="<?php echo event_master_h($eventUrl); ?>" class="hover:text-green-900">
									<?php echo event_master_h($event['titolo'] ?? 'Evento cosplay'); ?>
								</a>
							</h3>
							<p class="mt-2 text-sm text-gray-600">
								<?php echo event_master_h(trim($eventDate . ($eventYear ? ' - ' . $eventYear : ''))); ?>
							</p>
							<?php if (!empty($event['luogo'])): ?>
								<p class="mt-1 text-sm text-gray-600">
									<?php echo event_master_h($event['luogo']); ?>
								</p>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else: ?>
				<p class="text-gray-700">
					Nessuna edizione collegata è disponibile al momento.
				</p>
			<?php endif; ?>
		</section>
	</div>
</main>
