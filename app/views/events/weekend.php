<?php
use App\Helpers\AdPlacement;
$events = $data['events'] ?? [];
$weekendCorrente = $data['weekendCorrente'] ?? [];
$prossimiWeekend = $data['prossimiWeekend'] ?? [];
$breadcrumbs = $data['breadcrumbs'] ?? [];
$testoDescrittivo = $data['testo_descrittivo'] ?? '';
$siteBaseUrl = rtrim(URL_ROOT_SITE, '/');
$weekendBaseUrl = $siteBaseUrl . '/eventi-cosplay-weekend';
if(!function_exists('event_weekend_h')){
	function event_weekend_h($value) : string{
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	}
}
/**
 * Schema Breadcrumb
 */
$breadcrumbSchema = [
	'@context'        => 'https://schema.org',
	'@type'           => 'BreadcrumbList',
	'itemListElement' => [],
];
foreach($breadcrumbs as $index => $crumb){
	$breadcrumbSchema['itemListElement'][] = [
		'@type'    => 'ListItem',
		'position' => $index + 1,
		'name'     => $crumb['label'] ?? '',
		'item'     => $crumb['url'] ?? '',
	];
}
/**
 * Schema CollectionPage
 */
$collectionSchema = [
	'@context'    => 'https://schema.org',
	'@type'       => 'CollectionPage',
	'name'        => 'Eventi cosplay nel weekend',
	'description' => $testoDescrittivo,
	'url'         => $weekendBaseUrl,
];
?>

<script type="application/ld+json">
<?=json_encode(
		$breadcrumbSchema,
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	)?>

</script>

<script type="application/ld+json">
<?=json_encode(
		$collectionSchema,
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	)?>

</script>

<main class="bg-gray-100">

	<div class="container mx-auto px-4 py-6 md:px-6">

		<nav class="mb-5 text-sm text-gray-700" aria-label="Breadcrumb">

			<ol class="flex flex-wrap items-center gap-2">

				<?php foreach($breadcrumbs as $index => $crumb): ?>

					<li class="flex items-center gap-2">

						<?php if($index > 0): ?>
							<span class="text-gray-400">/</span>
						<?php endif; ?>

						<a
								href="<?=event_weekend_h($crumb['url'])?>"
								class="font-medium text-green-900 hover:underline"
						>
							<?=event_weekend_h($crumb['label'])?>
						</a>

					</li>

				<?php endforeach; ?>

			</ol>

		</nav>

		<header class="rounded-xl bg-white p-6 shadow-md md:p-10">

			<p class="text-sm font-bold uppercase tracking-wide text-green-900">
				Agenda cosplay weekend
			</p>

			<h1 class="mt-2 text-4xl font-extrabold text-gray-950">
				Eventi cosplay nel weekend
			</h1>

			<div class="mt-5 text-lg leading-relaxed text-gray-700">

				<p>
					<?=nl2br(event_weekend_h($testoDescrittivo))?>
				</p>

			</div>

		</header>

		<?php echo AdPlacement::render('events_weekend_top', 'events_weekend', 'mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md'); ?>

		<?php if(!empty($weekendCorrente)): ?>

			<section class="mt-8 rounded-xl bg-white p-6 shadow-md">

				<h2 class="text-2xl font-bold text-gray-950">
					Prossimo weekend cosplay
				</h2>

				<p class="mt-2 text-gray-700">

					<?=event_weekend_h(
						$weekendCorrente['slug']
					)?>

				</p>

				<?php if(!empty($events)): ?>

					<div class="mt-6 grid gap-5 md:grid-cols-3">

						<?php foreach(array_slice($events, 0, 3) as $event): ?>

							<article class="rounded-lg border bg-gray-50 p-5">

								<h3 class="font-bold text-lg">

									<a
											href="<?=event_weekend_h(
												$siteBaseUrl . '/eventi-cosplay/' . $event['slug']
											)?>"
											class="hover:text-green-900"
									>

										<?=event_weekend_h(
											$event['titolo']
										)?>

									</a>

								</h3>

								<p class="mt-2 text-sm text-gray-700">

									<?=event_weekend_h(
										$event['comune_nome'] ?? ''
									)?>

								</p>

							</article>

						<?php endforeach; ?>

					</div>

				<?php endif; ?>

				<a
						href="<?=event_weekend_h(
							$weekendBaseUrl . '/' . $weekendCorrente['slug']
						)?>"
						class="mt-6 inline-flex rounded-lg bg-green-800 px-5 py-3 font-bold text-white hover:bg-green-900"
				>

					Vedi tutti gli eventi del weekend

				</a>

			</section>

		<?php endif; ?>

		<section class="mt-10">

			<h2 class="mb-5 text-2xl font-bold text-gray-950">
				Prossimi weekend cosplay
			</h2>

			<div class="grid gap-5 md:grid-cols-3">

				<?php foreach($prossimiWeekend as $weekend): ?>

					<a
							href="<?=event_weekend_h(
								$weekendBaseUrl . '/' . $weekend['slug']
							)?>"
							class="rounded-xl bg-white p-5 shadow hover:bg-green-50"
					>

						<h3 class="font-bold text-xl text-green-900">

							<?=event_weekend_h(
								$weekend['slug']
							)?>

						</h3>

						<p class="mt-2 text-gray-700">
							Scopri tutti gli eventi cosplay in programma
						</p>

					</a>

				<?php endforeach; ?>

			</div>

		</section>

		<section class="mt-12 rounded-xl bg-white p-6 shadow-md md:p-8">

			<div class="prose prose-lg max-w-none text-gray-700">

				<h2 class="text-2xl font-bold text-gray-950">
					Eventi cosplay nel weekend in Italia
				</h2>

				<p>
					Scopri gli eventi cosplay organizzati nei weekend in Italia:
					fiere del fumetto, festival anime, raduni cosplay, contest e
					appuntamenti dedicati alla cultura nerd.
				</p>

				<p>
					Ogni fine settimana in diverse città italiane si svolgono eventi
					dedicati al mondo cosplay, con occasioni per incontrare altri
					appassionati, partecipare a sfilate, contest, attività tematiche
					e vivere dal vivo le proprie passioni.
				</p>

				<p>
					ItalianCosplay raccoglie gli eventi cosplay più interessanti
					organizzati in tutta Italia, aiutandoti a trovare rapidamente
					cosa fare nel prossimo weekend.
				</p>

				<h2 class="mt-8 text-2xl font-bold text-gray-950">
					Come trovare gli eventi cosplay del weekend
				</h2>

				<p>
					La pagina raccoglie gli appuntamenti cosplay suddivisi per weekend,
					così puoi scegliere facilmente la data più adatta per partecipare
					a una fiera, una convention o un raduno.
				</p>

				<p>
					Ogni evento contiene informazioni utili come luogo, date,
					immagini, collegamenti ufficiali e dettagli organizzativi per
					preparare al meglio la tua visita.
				</p>

				<p>
					Che tu sia un cosplayer alla ricerca del prossimo contest, un
					appassionato di fumetti e anime oppure semplicemente curioso di
					scoprire nuovi eventi nella tua zona, puoi trovare gli
					appuntamenti più vicini alle tue esigenze.
				</p>

				<h2 class="mt-8 text-2xl font-bold text-gray-950">
					Fiere cosplay, fumetti e cultura nerd ogni fine settimana
				</h2>

				<p>
					Gli eventi cosplay non sono solo manifestazioni dedicate ai
					costumi: sono punti di incontro per community, artisti, fotografi,
					creator e appassionati di videogiochi, manga, anime e cultura pop.
				</p>

				<p>
					Durante i weekend puoi trovare grandi fiere nazionali, eventi
					locali, raduni a tema, incontri con ospiti, aree gaming,
					spettacoli e attività dedicate a tutte le età.
				</p>

				<p>
					Il calendario viene aggiornato con nuovi appuntamenti per aiutarti
					a scoprire gli eventi cosplay in programma nei prossimi mesi.
				</p>

				<h2 class="mt-8 text-2xl font-bold text-gray-950">
					Scegli il prossimo weekend cosplay
				</h2>

				<p>
					Consulta i prossimi weekend disponibili per scoprire quali eventi
					sono organizzati in Italia.
				</p>

				<p>
					Ogni fine settimana ha una pagina dedicata con l'elenco completo
					degli appuntamenti, le informazioni sulle fiere e le manifestazioni
					in programma.
				</p>

				<h2 class="mt-8 text-2xl font-bold text-gray-950">
					Eventi cosplay in tutta Italia
				</h2>

				<p>
					Gli eventi cosplay sono disponibili in numerose regioni e città
					italiane.
				</p>

				<p>
					Puoi esplorare il calendario anche per area geografica e trovare
					eventi cosplay in Toscana, Lombardia, Lazio, Emilia-Romagna,
					Piemonte e nelle principali città italiane.
				</p>

				<p>
					La ricerca per località permette di individuare rapidamente le
					manifestazioni più vicine e pianificare la propria partecipazione.
				</p>

			</div>

		</section>

		<section class="mt-12 rounded-xl bg-white p-6 shadow-md">

			<h2 class="text-2xl font-bold text-gray-950">
				Domande frequenti sugli eventi cosplay nel weekend
			</h2>

			<div class="mt-5 grid gap-5 md:grid-cols-3">

				<article>

					<h3 class="font-bold">
						Quali eventi cosplay ci sono questo weekend?
					</h3>

					<p class="mt-2 text-sm text-gray-700">
						Consulta gli eventi disponibili per il prossimo fine settimana
						o scegli un weekend specifico dal calendario.
					</p>

				</article>

				<article>

					<h3 class="font-bold">
						Gli eventi cosplay sono aggiornati?
					</h3>

					<p class="mt-2 text-sm text-gray-700">
						Gli eventi pubblicati vengono verificati prima della loro presenza
						nel calendario.
					</p>

				</article>

				<article>

					<h3 class="font-bold">
						Come segnalo un evento cosplay?
					</h3>

					<p class="mt-2 text-sm text-gray-700">
						Puoi proporre un nuovo evento tramite il modulo di segnalazione.
					</p>

				</article>

			</div>

		</section>

		</div>

		<?php echo AdPlacement::render('events_weekend_bottom', 'events_weekend', 'mt-12 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md'); ?>

</main>
