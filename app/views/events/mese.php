<?php
use App\Helpers\AdPlacement;
$breadcrumbs = $data['breadcrumbs'] ?? [];
$mesiPerAnno = $data['mesiPerAnno'] ?? [];
$pageTitle = $data['pageTitle'] ?? '';
$testoDescrittivo = $data['testo_descrittivo'] ?? '';
$siteBaseUrl = rtrim(URL_ROOT_SITE, '/');
function event_months_h($value) : string{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
$breadcrumbSchema = [
	'@context'        => 'https://schema.org',
	'@type'           => 'BreadcrumbList',
	'itemListElement' => [],
];
foreach($breadcrumbs as $index => $crumb){
	$breadcrumbSchema['itemListElement'][] = [
		'@type'    => 'ListItem',
		'position' => $index + 1,
		'name'     => $crumb['label'],
		'item'     => $crumb['url'],
	];
}
$itemList = [
	'@context'        => 'https://schema.org',
	'@type'           => 'ItemList',
	'name'            => $pageTitle,
	'itemListElement' => [],
];
$position = 1;
foreach($mesiPerAnno as $anno => $mesi){
	foreach($mesi as $mese){
		$itemList['itemListElement'][] = [
			'@type'    => 'ListItem',
			'position' => $position++,
			'url'      => $siteBaseUrl . '/eventi-cosplay-mese/' . $mese['slug'],
			'name'     => 'Eventi cosplay ' . $mese['label'],
		];
	}
}
?>

<script type="application/ld+json">
<?php echo json_encode(
		$breadcrumbSchema,
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	); ?>

</script>

<script type="application/ld+json">
<?php echo json_encode(
		$itemList,
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	); ?>

</script>


	<div class="container mx-auto px-4 py-6 md:px-6">

		<nav class="mb-5 text-sm text-gray-700">

			<ol class="flex flex-wrap gap-2">

				<?php foreach($breadcrumbs as $index => $crumb): ?>

					<li>

						<?php if($index > 0): ?>
							<span class="text-gray-400">/</span>
						<?php endif; ?>

						<a href="<?php echo event_months_h($crumb['url']); ?>"
						   class="font-medium text-green-900 hover:underline">

							<?php echo event_months_h($crumb['label']); ?>

						</a>

					</li>

				<?php endforeach; ?>

			</ol>

		</nav>

		<header class="mb-8 rounded-xl bg-white p-6 shadow-md md:p-8">

			<p class="mb-2 text-sm font-bold uppercase tracking-wide text-green-900">
				Calendario cosplay
			</p>

			<h1 class="text-3xl font-extrabold text-gray-950 md:text-5xl">

				<?php echo event_months_h($pageTitle); ?>

			</h1>

			<div class="mt-5 text-lg leading-relaxed text-gray-700">

				<?php echo $testoDescrittivo; ?>

			</div>

		</header>

		<?php echo AdPlacement::render('events_month_top', 'events_month', 'mb-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md'); ?>

		<?php foreach($mesiPerAnno as $anno => $mesi): ?>

			<section class="mb-10">

				<h2 class="mb-5 text-2xl font-bold text-gray-950">

					Eventi cosplay <?php echo event_months_h($anno); ?>

				</h2>

				<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

					<?php foreach($mesi as $mese): ?>

						<a href="<?php echo $siteBaseUrl; ?>/eventi-cosplay-mese/<?php echo event_months_h($mese['slug']); ?>"
						   class="rounded-xl bg-white p-5 shadow-md transition hover:-translate-y-1 hover:shadow-lg">

							<h3 class="text-xl font-bold text-green-900">

								<?php echo event_months_h($mese['label']); ?>

							</h3>
							<p class="mt-2 text-sm text-gray-600">
								<?php echo $mese['total']; ?> eventi cosplay disponibili
							</p>

							<p class="mt-2 text-sm text-gray-600">

								Scopri fiere, raduni e appuntamenti cosplay

							</p>

							<span class="mt-4 inline-block font-bold text-green-900">
Vedi eventi →
</span>

						</a>

					<?php endforeach; ?>

				</div>

			</section>

		<?php endforeach; ?>

		<?php echo AdPlacement::render('events_month_inline', 'events_month', 'mb-10 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md'); ?>

		<section class="rounded-xl bg-white p-6 shadow-md mb-10">

			<h2 class="text-2xl font-bold text-gray-950">
				Come trovare gli eventi cosplay del periodo giusto
			</h2>

			<div class="mt-4 text-gray-700 space-y-3">

				<p>
					Se stai cercando una fiera cosplay vicino a casa oppure vuoi organizzare una
					trasferta, puoi scegliere il mese interessato e consultare tutti gli eventi
					disponibili.
				</p>

				<p>
					Ogni scheda evento contiene informazioni utili come date, città, immagini,
					social ufficiali, sito dell'organizzazione e dettagli della manifestazione.
				</p>

			</div>

		</section>
		<section class="rounded-xl bg-green-50 p-6 shadow-md mb-10">

			<h2 class="text-2xl font-bold text-gray-950">
				Cerchi gli eventi più vicini?
			</h2>

			<p class="mt-3 text-gray-700">
				Se vuoi sapere quali eventi cosplay si svolgono nei prossimi giorni,
				consulta la pagina dedicata ai weekend.
			</p>

			<a href="/eventi-cosplay-weekend"
			   class="mt-4 inline-flex rounded-lg bg-green-800 px-5 py-3 font-bold text-white">
				Eventi cosplay del weekend
			</a>

		</section>

		<?php echo AdPlacement::render('events_month_bottom', 'events_month', 'mb-10 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md'); ?>

		<section class="rounded-xl bg-white p-6 shadow-md">

			<h2 class="mb-5 text-2xl font-bold text-gray-950">
				Domande frequenti sugli eventi cosplay mensili
			</h2>

			<div class="grid gap-5 md:grid-cols-3">

				<div>
					<h3 class="font-bold">
						Come trovo gli eventi cosplay di un mese specifico?
					</h3>

					<p class="mt-2 text-sm text-gray-700">
						Seleziona il mese dal calendario per vedere tutte le fiere,
						i festival e i raduni cosplay in programma.
					</p>

				</div>

				<div>
					<h3 class="font-bold">
						Gli eventi vengono aggiornati?
					</h3>

					<p class="mt-2 text-sm text-gray-700">
						Il calendario viene aggiornato con nuovi eventi e modifiche
						alle manifestazioni pubblicate.
					</p>

				</div>

				<div>
					<h3 class="font-bold">
						Posso segnalare un evento cosplay?
					</h3>

					<p class="mt-2 text-sm text-gray-700">
						Puoi proporre nuovi eventi cosplay tramite il modulo dedicato.
					</p>

				</div>

			</div>

		</section>

	</div>
