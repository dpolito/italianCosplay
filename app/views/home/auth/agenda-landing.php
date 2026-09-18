<?php
$canonicalUrl = $data['canonicalUrl'] ?? (URL_ROOT_SITE . '/agenda-cosplay');
$schema = [
	'@context' => 'https://schema.org',
	'@type' => 'WebPage',
	'name' => 'Agenda cosplay personale',
	'description' => 'Una pagina per creare un\'agenda cosplay personale, salvare eventi, seguire zone utili e ritrovare gli appuntamenti che interessano.',
	'url' => $canonicalUrl,
	'isPartOf' => [
		'@type' => 'WebSite',
		'name' => 'ItalianCosplay',
		'url' => URL_ROOT_SITE,
	],
];
?>

<script type="application/ld+json">
	<?php echo json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
</script>

<main class="bg-gray-50">
	<section class="mx-auto grid max-w-7xl gap-8 px-4 py-10 md:py-16 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
		<div>
			<p class="text-sm font-bold uppercase tracking-wide text-green-800">Agenda cosplay personale</p>
			<h1 class="mt-3 max-w-3xl text-4xl font-black leading-tight text-gray-950 md:text-6xl">
				Non perdere i prossimi eventi cosplay vicino a te
			</h1>
			<p class="mt-5 max-w-2xl text-lg leading-8 text-gray-700">
				Salva gli eventi che ti interessano, segui le zone che frequenti e ritrova tutto nella tua area personale quando devi decidere a quale fiera partecipare.
			</p>
			<div class="mt-7 flex flex-col gap-3 sm:flex-row">
				<a href="/register" class="inline-flex items-center justify-center rounded-lg bg-green-800 px-6 py-3 text-base font-bold text-white shadow hover:bg-green-900">
					Crea la tua agenda gratuita
				</a>
				<a href="/eventi-cosplay" class="inline-flex items-center justify-center rounded-lg border border-green-800 px-6 py-3 text-base font-bold text-green-900 hover:bg-green-50">
					Esplora eventi cosplay
				</a>
			</div>
			<p class="mt-3 text-sm font-semibold text-gray-600">Gratis, bastano pochi secondi per iniziare.</p>
		</div>

		<div class="rounded-lg border border-green-100 bg-white p-5 shadow-lg md:p-6">
			<p class="text-sm font-bold uppercase tracking-wide text-gray-500">Cosa puoi salvare</p>
			<div class="mt-5 space-y-4">
				<div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
					<p class="font-bold text-gray-950">Eventi cosplay</p>
					<p class="mt-1 text-sm leading-6 text-gray-700">Segnali se ci vai, se ti interessa o se forse parteciperai.</p>
				</div>
				<div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
					<p class="font-bold text-gray-950">Zone preferite</p>
					<p class="mt-1 text-sm leading-6 text-gray-700">Segui regioni, province e comuni per ritrovare velocemente gli appuntamenti vicini a te.</p>
				</div>
				<div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
					<p class="font-bold text-gray-950">Guide e contenuti utili</p>
					<p class="mt-1 text-sm leading-6 text-gray-700">Tieni da parte articoli e informazioni che vuoi consultare prima di partire.</p>
				</div>
			</div>
		</div>
	</section>

	<section class="bg-white py-10 md:py-14">
		<div class="mx-auto max-w-7xl px-4">
			<div class="max-w-3xl">
				<p class="text-sm font-bold uppercase tracking-wide text-green-800">Perché registrarsi</p>
				<h2 class="mt-2 text-3xl font-black text-gray-950">Dalla ricerca degli eventi alla tua lista personale</h2>
				<p class="mt-4 text-base leading-7 text-gray-700">
					ItalianCosplay raccoglie eventi e contenuti cosplay in tutta Italia. L’agenda personale serve a trasformare la ricerca in una lista chiara: meno pagine da ricordare, più appuntamenti utili da seguire.
				</p>
			</div>

			<div class="mt-8 grid gap-5 md:grid-cols-3">
				<article class="rounded-lg border border-gray-200 bg-gray-50 p-5">
					<h3 class="text-lg font-bold text-gray-950">Ritrovi ciò che conta</h3>
					<p class="mt-2 text-sm leading-6 text-gray-700">Eventi salvati, zone preferite e contenuti utili restano nella tua dashboard.</p>
				</article>
				<article class="rounded-lg border border-gray-200 bg-gray-50 p-5">
					<h3 class="text-lg font-bold text-gray-950">Segui il territorio</h3>
					<p class="mt-2 text-sm leading-6 text-gray-700">Le pagine regionali diventano scorciatoie per scoprire nuovi appuntamenti.</p>
				</article>
				<article class="rounded-lg border border-gray-200 bg-gray-50 p-5">
					<h3 class="text-lg font-bold text-gray-950">Decidi con più calma</h3>
					<p class="mt-2 text-sm leading-6 text-gray-700">Distingui gli eventi certi da quelli che vuoi valutare senza ripartire ogni volta da zero.</p>
				</article>
			</div>
		</div>
	</section>

	<section class="mx-auto max-w-7xl px-4 py-10 md:py-14">
		<div class="rounded-lg border border-green-100 bg-white p-6 shadow-lg md:p-8">
			<p class="text-sm font-bold uppercase tracking-wide text-green-800">Dopo la registrazione</p>
			<h2 class="mt-2 text-3xl font-black text-gray-950">Parti subito dagli eventi da salvare</h2>
			<p class="mt-4 max-w-3xl text-base leading-7 text-gray-700">
				Crea l’account, esplora il calendario e aggiungi alla tua agenda gli eventi che ti interessano. La tua area personale diventa il punto da cui ripartire quando pianifichi le prossime uscite.
			</p>
			<div class="mt-6 flex flex-col gap-3 sm:flex-row">
				<a href="/register" class="inline-flex justify-center rounded-lg bg-green-800 px-5 py-3 font-bold text-white hover:bg-green-900">
					Registrati gratis
				</a>
				<a href="/eventi-cosplay" class="inline-flex justify-center rounded-lg border border-gray-300 px-5 py-3 font-bold text-gray-950 hover:bg-gray-50">
					Vedi gli eventi
				</a>
			</div>
		</div>
	</section>

	<section class="mx-auto max-w-7xl px-4 py-10 md:py-14">
		<a href="/register" class="block overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg transition hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2" aria-label="Registrati gratis su ItalianCosplay">
			<picture>
				<source media="(min-width: 768px)" srcset="/public_assets/images/banner_iscrizione2.png">
				<img
					src="/public_assets/images/banner_registrazione_quadrato.png"
					alt="Registrati ora su ItalianCosplay: crea il tuo account, entra nella community e scopri tutte le funzioni"
					width="348"
					height="348"
					loading="lazy"
					class="h-auto w-full object-contain"
				>
			</picture>
		</a>
	</section>
</main>
