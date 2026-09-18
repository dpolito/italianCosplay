<?php
$masters = $data['masters'] ?? [];
$breadcrumbs = $data['breadcrumbs'] ?? [];
$canonicalUrl = $data['canonicalUrl'] ?? '';
$pageTitle = $data['pageTitle'] ?? 'Eventi e edizioni';
$siteBaseUrl = rtrim(URL_ROOT_SITE, '/');

if (!function_exists('event_master_index_h')) {
	function event_master_index_h($value): string
	{
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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

$schema = [
	'@context' => 'https://schema.org',
	'@type' => 'CollectionPage',
	'name' => $pageTitle,
	'url' => $canonicalUrl,
];
?>

<script type="application/ld+json"><?php echo json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<script type="application/ld+json"><?php echo json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

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
							<a href="<?php echo event_master_index_h($crumb['url']); ?>" class="font-medium text-green-900 hover:underline">
								<?php echo event_master_index_h($crumb['label'] ?? ''); ?>
							</a>
						<?php else: ?>
							<span><?php echo event_master_index_h($crumb['label'] ?? ''); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>

		<header class="rounded-xl bg-white p-6 shadow-md md:p-10">
			<p class="text-sm font-bold uppercase tracking-wide text-green-900">Archivio eventi e edizioni</p>
			<h1 class="mt-2 text-4xl font-extrabold text-gray-950">Eventi e edizioni</h1>
			<p class="mt-4 max-w-3xl text-lg leading-relaxed text-gray-700">
				Raccogliamo qui gli eventi con più edizioni, così puoi trovare in un unico posto tutte le varianti annuali e navigare rapidamente tra le schede collegate.
			</p>
		</header>

		<section class="mt-8">
			<?php if (!empty($masters)): ?>
				<div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
					<?php foreach ($masters as $master): ?>
						<?php
						$masterUrl = $siteBaseUrl . '/eventi-master/' . rawurlencode((string) ($master['slug'] ?? ''));
						$masterDesc = trim(strip_tags((string) ($master['descrizione'] ?? '')));
						?>
						<article class="overflow-hidden rounded-xl bg-white shadow-md transition hover:shadow-lg">
							<a href="<?php echo event_master_index_h($masterUrl); ?>">
								<?php if (!empty($master['cover'])): ?>
									<img src="<?php echo event_master_index_h($master['cover']); ?>" alt="<?php echo event_master_index_h($master['nome']); ?>" class="h-48 w-full object-cover">
								<?php else: ?>
									<div class="flex h-48 items-center justify-center bg-gray-200 text-gray-500">
										Nessuna immagine
									</div>
								<?php endif; ?>
							</a>
							<div class="p-5">
								<div class="mb-2 inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-900">
									<?php echo (int) $master['event_count']; ?> edizioni
								</div>
								<h2 class="text-xl font-bold text-gray-950">
									<a href="<?php echo event_master_index_h($masterUrl); ?>" class="hover:text-green-900">
										<?php echo event_master_index_h($master['nome']); ?>
									</a>
								</h2>
								<p class="mt-3 text-sm leading-relaxed text-gray-700">
									<?php echo event_master_index_h(mb_strimwidth($masterDesc, 0, 140, '...')); ?>
								</p>
								<a href="<?php echo event_master_index_h($masterUrl); ?>" class="mt-4 inline-flex font-semibold text-green-900 hover:underline">
									Apri pagina master
								</a>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else: ?>
				<div class="rounded-xl bg-white p-8 text-center shadow-md">
					<p class="text-lg font-bold text-gray-950">Nessun evento master disponibile al momento.</p>
					<p class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-gray-700">
						Se rappresenti un evento non ancora presente, puoi segnalarlo allo staff così da creare o completare la scheda.
					</p>
					<a href="/segnala-evento-cosplay" class="mt-5 inline-flex rounded-lg bg-green-800 px-5 py-3 font-bold text-white hover:bg-green-900">
						Segnala evento
					</a>
				</div>
			<?php endif; ?>
		</section>
	</div>
</main>
