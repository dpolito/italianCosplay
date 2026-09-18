<?php
$canonicalUrl = $data['canonicalUrl'] ?? (URL_ROOT_SITE . '/cosplan');
$schema = [
	'@context' => 'https://schema.org',
	'@type' => 'WebPage',
	'name' => 'Cosplan gratis su ItalianCosplay',
	'description' => 'Una landing dedicata a chi vuole organizzare un cosplan gratuito, pianificare il costume e collegarlo agli eventi cosplay da seguire.',
	'url' => $canonicalUrl,
	'isPartOf' => [
		'@type' => 'WebSite',
		'name' => 'ItalianCosplay',
		'url' => URL_ROOT_SITE,
	],
];

$faqSchema = [
	'@context' => 'https://schema.org',
	'@type' => 'FAQPage',
	'mainEntity' => [
		[
			'@type' => 'Question',
			'name' => 'Che cos\'è un cosplan?',
			'acceptedAnswer' => [
				'@type' => 'Answer',
				'text' => 'Un cosplan è un piano cosplay: aiuta a organizzare personaggio, costume, materiali, lavorazioni, scadenze ed eventi a cui vuoi partecipare.',
			],
		],
		[
			'@type' => 'Question',
			'name' => 'Posso creare un cosplan gratis?',
			'acceptedAnswer' => [
				'@type' => 'Answer',
				'text' => 'Sì. Su ItalianCosplay puoi creare gratis un account e usare gli strumenti disponibili per organizzare i tuoi cosplay e gli eventi che vuoi seguire.',
			],
		],
	],
];
?>

<script type="application/ld+json">
	<?php echo json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
</script>
<script type="application/ld+json">
	<?php echo json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
</script>

<main class="bg-gray-50">
	<section class="bg-white">
		<div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 md:py-16 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
			<div>
				<nav class="text-sm font-semibold text-gray-600" aria-label="Breadcrumb">
					<a href="/" class="hover:text-green-800">Home</a>
					<span class="mx-2 text-gray-400">/</span>
					<span class="text-gray-900">Cosplan</span>
				</nav>
				<p class="mt-6 text-sm font-bold uppercase tracking-wide text-green-800">Cosplan gratuito</p>
				<h1 class="mt-3 max-w-3xl text-4xl font-black leading-tight text-gray-950 md:text-6xl">
					Organizza il tuo prossimo cosplay e gli eventi dove portarlo
				</h1>
				<p class="mt-5 max-w-2xl text-lg leading-8 text-gray-700">
					Un cosplan ti aiuta a trasformare un'idea cosplay in un progetto concreto: personaggio, materiali, lavorazioni, prove e fiere a cui vuoi portarlo. Su ItalianCosplay puoi iniziare gratis creando il tuo account.
				</p>
				<div class="mt-7 flex flex-col gap-3 sm:flex-row">
					<a href="/register" class="inline-flex items-center justify-center rounded-lg bg-green-800 px-6 py-3 text-base font-bold text-white shadow hover:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2">
						Crea un cosplan gratis
					</a>
					<a href="/agenda-cosplay" class="inline-flex items-center justify-center rounded-lg border border-green-800 px-6 py-3 text-base font-bold text-green-900 hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2">
						Scopri l'agenda cosplay
					</a>
				</div>
			</div>

			<div class="rounded-lg border border-green-100 bg-gray-50 p-5 shadow-lg md:p-6">
				<p class="text-sm font-bold uppercase tracking-wide text-gray-500">Dal piano al prossimo evento</p>
				<div class="mt-5 space-y-4">
					<div class="rounded-lg border border-gray-200 bg-white p-4">
						<p class="font-bold text-gray-950">Personaggio e reference</p>
						<p class="mt-1 text-sm leading-6 text-gray-700">Tieni chiaro quale cosplay vuoi realizzare e quali dettagli non vuoi dimenticare.</p>
					</div>
					<div class="rounded-lg border border-gray-200 bg-white p-4">
						<p class="font-bold text-gray-950">Materiali e preparazione</p>
						<p class="mt-1 text-sm leading-6 text-gray-700">Organizza pezzi del costume, accessori, prove trucco, parrucca e priorità di lavorazione.</p>
					</div>
					<div class="rounded-lg border border-gray-200 bg-white p-4">
						<p class="font-bold text-gray-950">Eventi dove indossarlo</p>
						<p class="mt-1 text-sm leading-6 text-gray-700">Collega il tuo piano agli eventi cosplay italiani che vuoi seguire o salvare in agenda.</p>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="mx-auto max-w-7xl px-4 py-10 md:py-14">
		<div class="max-w-3xl">
			<p class="text-sm font-bold uppercase tracking-wide text-green-800">Cosplan, cosplay plan, piano cosplay</p>
			<h2 class="mt-2 text-3xl font-black text-gray-950">Perché usare un cosplan</h2>
			<p class="mt-4 text-base leading-7 text-gray-700">
				Chi cerca cosplan spesso vuole una cosa semplice: non perdere pezzi, idee e scadenze mentre prepara un costume. Un cosplay plan serve proprio a questo, soprattutto quando lavori su più personaggi o vuoi arrivare pronto a una fiera.
			</p>
		</div>

		<div class="mt-8 grid gap-5 md:grid-cols-3">
			<article class="rounded-lg border border-gray-200 bg-white p-5">
				<h3 class="text-lg font-bold text-gray-950">Meno caos nella preparazione</h3>
				<p class="mt-2 text-sm leading-6 text-gray-700">Raccogli in un punto unico le parti del costume, gli accessori e le cose ancora da completare.</p>
			</article>
			<article class="rounded-lg border border-gray-200 bg-white p-5">
				<h3 class="text-lg font-bold text-gray-950">Scadenze più realistiche</h3>
				<p class="mt-2 text-sm leading-6 text-gray-700">Pianifica il lavoro pensando alla data dell'evento e non solo all'entusiasmo iniziale.</p>
			</article>
			<article class="rounded-lg border border-gray-200 bg-white p-5">
				<h3 class="text-lg font-bold text-gray-950">Eventi collegati</h3>
				<p class="mt-2 text-sm leading-6 text-gray-700">Usa ItalianCosplay anche per trovare eventi cosplay in Italia e decidere dove portare il progetto.</p>
			</article>
		</div>
	</section>

	<section class="bg-white py-10 md:py-14">
		<div class="mx-auto grid max-w-7xl gap-8 px-4 lg:grid-cols-[0.9fr_1.1fr]">
			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-green-800">Domande frequenti</p>
				<h2 class="mt-2 text-3xl font-black text-gray-950">Cosplan gratis: cosa sapere</h2>
				<p class="mt-4 text-base leading-7 text-gray-700">
					La registrazione avviene nella pagina dedicata: questa landing ti spiega il valore del cosplan e ti porta direttamente alla creazione gratuita dell'account.
				</p>
			</div>
			<div class="space-y-4">
				<article class="rounded-lg border border-gray-200 bg-gray-50 p-5">
					<h3 class="text-lg font-bold text-gray-950">Che cos'è un cosplan?</h3>
					<p class="mt-2 text-sm leading-6 text-gray-700">È un piano cosplay: uno spazio pratico per organizzare personaggio, costume, materiali, tempi ed eventi.</p>
				</article>
				<article class="rounded-lg border border-gray-200 bg-gray-50 p-5">
					<h3 class="text-lg font-bold text-gray-950">Su ItalianCosplay si può iniziare gratis?</h3>
					<p class="mt-2 text-sm leading-6 text-gray-700">Sì, puoi registrarti gratis e usare il sito per organizzare i tuoi cosplay, salvare eventi e costruire la tua agenda personale.</p>
				</article>
			</div>
		</div>
	</section>

	<section class="mx-auto max-w-7xl px-4 py-10 md:py-14">
		<div class="rounded-lg bg-green-900 p-6 text-white md:p-8">
			<div class="grid gap-6 md:grid-cols-[1fr_auto] md:items-center">
				<div>
					<p class="text-sm font-bold uppercase tracking-wide text-green-100">Inizia ora</p>
					<h2 class="mt-2 text-3xl font-black">Porta il tuo prossimo cosplay fuori dalla lista delle idee</h2>
					<p class="mt-3 max-w-2xl text-base leading-7 text-green-50">Crea gratis il tuo account ItalianCosplay e usa il sito come base per cosplan, eventi salvati e organizzazione personale.</p>
				</div>
				<a href="/register" class="inline-flex items-center justify-center rounded-lg bg-white px-6 py-3 text-base font-bold text-green-900 shadow hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-green-900">
					Registrati gratis
				</a>
			</div>
		</div>
	</section>
</main>
