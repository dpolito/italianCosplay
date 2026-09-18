<?php
$canonicalUrl = $data['canonicalUrl'] ?? (URL_ROOT_SITE . '/organizzatori-eventi-cosplay');
$schema = [
	'@context' => 'https://schema.org',
	'@type' => 'WebPage',
	'name' => 'Organizzatori eventi cosplay',
	'description' => 'Pagina per organizzatori che vogliono richiedere la gestione gratuita della scheda del proprio evento cosplay su ItalianCosplay.',
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
			<p class="text-sm font-bold uppercase tracking-wide text-green-800">Per organizzatori cosplay</p>
			<h1 class="mt-3 max-w-3xl text-4xl font-black leading-tight text-gray-950 md:text-6xl">
				Gestisci la scheda del tuo evento cosplay
			</h1>
			<p class="mt-5 max-w-2xl text-lg leading-8 text-gray-700">
				Se rappresenti una fiera, un festival o un appuntamento cosplay, cerca la scheda già presente su ItalianCosplay e richiedi gratuitamente la gestione per mantenerla aggiornata.
			</p>
			<form action="/eventi-cosplay" method="get" class="mt-7 rounded-lg border border-green-100 bg-white p-4 shadow-lg" data-organizer-event-search-form>
				<label for="organizer-event-search" class="text-sm font-bold text-gray-950">Cerca la scheda del tuo evento</label>
				<div class="mt-3 grid gap-3 sm:grid-cols-[1fr_auto]">
					<div class="relative">
						<input
							id="organizer-event-search"
							name="q"
							type="search"
							placeholder="Nome evento, fiera o festival"
							autocomplete="off"
							role="combobox"
							aria-autocomplete="list"
							aria-expanded="false"
							aria-controls="organizer-event-search-results"
							class="min-h-12 w-full rounded-lg border border-gray-300 px-4 text-base text-gray-950 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-200"
							data-organizer-event-search-input
						>
						<div id="organizer-event-search-results" class="absolute left-0 right-0 top-full z-40 mt-2 hidden overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl" role="listbox" data-organizer-event-search-results></div>
					</div>
					<button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-lg bg-green-800 px-6 font-bold text-white shadow-sm hover:bg-green-900">
						Cerca evento
					</button>
				</div>
				<p class="mt-3 text-sm leading-6 text-gray-600">Seleziona un suggerimento per aprire direttamente la scheda. Se non trovi nulla, invia la ricerca completa.</p>
			</form>
		</div>

		<div class="rounded-lg border border-green-100 bg-white p-5 shadow-lg md:p-6">
			<p class="text-sm font-bold uppercase tracking-wide text-gray-500">Perché farlo</p>
			<div class="mt-5 space-y-4">
				<div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
					<p class="font-bold text-gray-950">Informazioni più affidabili</p>
					<p class="mt-1 text-sm leading-6 text-gray-700">Puoi aggiornare date, luogo, descrizione, sito ufficiale e canali social dopo la verifica dello staff.</p>
				</div>
				<div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
					<p class="font-bold text-gray-950">Scheda collegata a un referente</p>
					<p class="mt-1 text-sm leading-6 text-gray-700">La richiesta associa l’evento a chi può davvero gestirlo, senza cambiare i dati pubblici in automatico.</p>
				</div>
				<div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
					<p class="font-bold text-gray-950">Più visibilità per chi cerca eventi</p>
					<p class="mt-1 text-sm leading-6 text-gray-700">Una scheda completa entra meglio nel calendario, nelle pagine territoriali e nelle ricerche degli utenti.</p>
				</div>
			</div>
		</div>
	</section>

	<section class="bg-white py-10 md:py-14">
		<div class="mx-auto max-w-7xl px-4">
			<div class="max-w-3xl">
				<p class="text-sm font-bold uppercase tracking-wide text-green-800">Come funziona</p>
				<h2 class="mt-2 text-3xl font-black text-gray-950">Un percorso semplice per collegare evento e organizzatore</h2>
				<p class="mt-4 text-base leading-7 text-gray-700">
					Non devi conoscere relazioni o codici interni: parti dal nome dell’evento, scegli la scheda corretta e invii una richiesta verificabile.
				</p>
			</div>

			<div class="mt-8 grid gap-5 md:grid-cols-4">
				<article class="rounded-lg border border-gray-200 bg-gray-50 p-5">
					<span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-green-800 font-black text-white">1</span>
					<h3 class="mt-4 text-lg font-bold text-gray-950">Cerca l’evento</h3>
					<p class="mt-2 text-sm leading-6 text-gray-700">Usa il nome della fiera, del festival o dell’appuntamento che organizzi.</p>
				</article>
				<article class="rounded-lg border border-gray-200 bg-gray-50 p-5">
					<span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-green-800 font-black text-white">2</span>
					<h3 class="mt-4 text-lg font-bold text-gray-950">Apri la scheda</h3>
					<p class="mt-2 text-sm leading-6 text-gray-700">Controlla che sia quella giusta e usa il pulsante per richiedere la gestione.</p>
				</article>
				<article class="rounded-lg border border-gray-200 bg-gray-50 p-5">
					<span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-green-800 font-black text-white">3</span>
					<h3 class="mt-4 text-lg font-bold text-gray-950">Dimostra il ruolo</h3>
					<p class="mt-2 text-sm leading-6 text-gray-700">Indica organizzazione, sito, social ufficiali o riferimenti pubblici utili alla verifica.</p>
				</article>
				<article class="rounded-lg border border-gray-200 bg-gray-50 p-5">
					<span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-green-800 font-black text-white">4</span>
					<h3 class="mt-4 text-lg font-bold text-gray-950">Aggiorna i dati</h3>
					<p class="mt-2 text-sm leading-6 text-gray-700">Dopo l’approvazione puoi mantenere la scheda più precisa per i visitatori.</p>
				</article>
			</div>
		</div>
	</section>

	<section class="mx-auto max-w-7xl px-4 py-10 md:py-14">
		<div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
			<div class="rounded-lg border border-amber-200 bg-amber-50 p-6">
				<h2 class="text-2xl font-black text-gray-950">Se non trovi la scheda</h2>
				<ul class="mt-5 space-y-3 text-sm leading-6 text-gray-700">
					<li><strong>Prova con varianti del nome:</strong> alcune schede possono usare acronimi, sottotitoli o il nome completo della fiera.</li>
					<li><strong>Controlla il calendario eventi:</strong> è il punto più completo, anche quando la pagina master non è ancora pubblica.</li>
					<li><strong>Segnala l’evento:</strong> se manca davvero, puoi inviare le informazioni principali allo staff.</li>
				</ul>
				<a href="/segnala-evento-cosplay" class="mt-5 inline-flex rounded-lg border border-amber-300 bg-white px-5 py-3 font-bold text-gray-950 hover:bg-amber-100">
					Segnala evento mancante
				</a>
			</div>
			<div class="rounded-lg bg-green-900 p-6 text-white md:p-8">
				<h2 class="text-2xl font-black">Vai alla ricerca completa</h2>
				<p class="mt-3 max-w-2xl leading-7 text-green-50">
					L’azione importante non è leggere una guida: è trovare la scheda giusta. Da lì puoi verificare i dati pubblicati e, quando disponibile, inviare la richiesta gratuita di gestione.
				</p>
				<div class="mt-6 flex flex-col gap-3 sm:flex-row">
					<a href="/eventi-cosplay" class="inline-flex justify-center rounded-lg bg-white px-5 py-3 font-bold text-green-900 hover:bg-green-50">
						Cerca negli eventi
					</a>
					<a href="/register" class="inline-flex justify-center rounded-lg border border-white/40 px-5 py-3 font-bold text-white hover:bg-white/10">
						Registrati gratis
					</a>
				</div>
			</div>
		</div>
	</section>
</main>

<script>
(() => {
	const form = document.querySelector('[data-organizer-event-search-form]');
	if (!form) {
		return;
	}

	const input = form.querySelector('[data-organizer-event-search-input]');
	const results = form.querySelector('[data-organizer-event-search-results]');
	let activeIndex = -1;
	let items = [];
	let controller = null;
	let debounceTimer = null;

	const closeResults = () => {
		results.classList.add('hidden');
		results.innerHTML = '';
		input.setAttribute('aria-expanded', 'false');
		activeIndex = -1;
		items = [];
	};

	const openEvent = (url) => {
		if (url) {
			window.location.href = url;
		}
	};

	const renderResults = (events) => {
		items = events;
		activeIndex = -1;
		results.innerHTML = '';

		if (events.length === 0) {
			const empty = document.createElement('div');
			empty.className = 'px-4 py-3 text-sm text-gray-600';
			empty.textContent = 'Nessun evento trovato. Prova con una variante del nome.';
			results.appendChild(empty);
			results.classList.remove('hidden');
			input.setAttribute('aria-expanded', 'true');
			return;
		}

		events.forEach((event, index) => {
			const button = document.createElement('button');
			button.type = 'button';
			button.id = `organizer-event-option-${index}`;
			button.className = 'block w-full px-4 py-3 text-left hover:bg-green-50 focus:bg-green-50 focus:outline-none';
			button.setAttribute('role', 'option');
			button.dataset.index = String(index);

			const title = document.createElement('span');
			title.className = 'block font-bold text-gray-950';
			title.textContent = event.title || 'Evento cosplay';

			const meta = document.createElement('span');
			meta.className = 'mt-1 block text-sm text-gray-600';
			meta.textContent = [event.date, event.location].filter(Boolean).join(' · ');

			button.append(title, meta);
			button.addEventListener('click', () => openEvent(event.url));
			results.appendChild(button);
		});

		results.classList.remove('hidden');
		input.setAttribute('aria-expanded', 'true');
	};

	const setActive = (nextIndex) => {
		const options = [...results.querySelectorAll('[role="option"]')];
		options.forEach((option) => option.classList.remove('bg-green-50'));
		activeIndex = nextIndex;

		if (activeIndex >= 0 && options[activeIndex]) {
			options[activeIndex].classList.add('bg-green-50');
			input.setAttribute('aria-activedescendant', options[activeIndex].id);
		} else {
			input.removeAttribute('aria-activedescendant');
		}
	};

	const searchEvents = () => {
		const query = input.value.trim();
		if (query.length < 2) {
			closeResults();
			return;
		}

		if (controller) {
			controller.abort();
		}

		controller = new AbortController();
		fetch(`/api/eventi/search?q=${encodeURIComponent(query)}`, {
			headers: { 'Accept': 'application/json' },
			signal: controller.signal,
		})
			.then((response) => response.ok ? response.json() : Promise.reject())
			.then((payload) => renderResults(Array.isArray(payload.events) ? payload.events : []))
			.catch((error) => {
				if (error.name !== 'AbortError') {
					closeResults();
				}
			});
	};

	input.addEventListener('input', () => {
		window.clearTimeout(debounceTimer);
		debounceTimer = window.setTimeout(searchEvents, 180);
	});

	input.addEventListener('keydown', (event) => {
		if (results.classList.contains('hidden')) {
			return;
		}

		if (event.key === 'ArrowDown') {
			event.preventDefault();
			setActive(Math.min(activeIndex + 1, items.length - 1));
		}

		if (event.key === 'ArrowUp') {
			event.preventDefault();
			setActive(Math.max(activeIndex - 1, -1));
		}

		if (event.key === 'Enter' && activeIndex >= 0 && items[activeIndex]) {
			event.preventDefault();
			openEvent(items[activeIndex].url);
		}

		if (event.key === 'Escape') {
			closeResults();
		}
	});

	document.addEventListener('click', (event) => {
		if (!form.contains(event.target)) {
			closeResults();
		}
	});
})();
</script>
