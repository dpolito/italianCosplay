<?php
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$formatDate = static function (?string $start, ?string $end = null): string {
	if (empty($start)) {
		return '';
	}
	$startTime = strtotime($start);
	if (!$startTime) {
		return (string) $start;
	}
	$label = date('d/m/Y', $startTime);
	if (!empty($end) && $end !== $start) {
		$endTime = strtotime($end);
		if ($endTime) {
			$label .= ' - ' . date('d/m/Y', $endTime);
		}
	}
	return $label;
};
$photoIndexPath = static function (array $currentFilters, int $targetPage = 1): string {
	$segments = [];
	if (!empty($currentFilters['event_id'])) {
		$segments[] = 'evento-' . (int) $currentFilters['event_id'];
	}
	if (!empty($currentFilters['year'])) {
		$segments[] = 'anno-' . (int) $currentFilters['year'];
	}
	if (!empty($currentFilters['uploader_id'])) {
		$segments[] = 'autore-' . (int) $currentFilters['uploader_id'];
	}
	if ($targetPage > 1) {
		$segments[] = 'pagina-' . $targetPage;
	}
	return '/foto-cosplay' . ($segments ? '/' . implode('/', $segments) : '');
};
$queryForPage = static function (int $targetPage) use ($filters, $photoIndexPath): string {
	if (!empty($filters['event_id'])) {
		$filters['event_id'] = (int) $filters['event_id'];
	}
	if (!empty($filters['year'])) {
		$filters['year'] = (int) $filters['year'];
	}
	if (!empty($filters['uploader_id'])) {
		$filters['uploader_id'] = (int) $filters['uploader_id'];
	}
	return $photoIndexPath($filters, $targetPage);
};
$collectionSchema = [
	'@context' => 'https://schema.org',
	'@type' => 'CollectionPage',
	'name' => 'Foto cosplay dalle fiere italiane',
	'description' => 'Fotografie cosplay pubblicate dalla community durante eventi, comics e fiere italiane.',
	'url' => rtrim(URL_ROOT_SITE, '/') . '/foto-cosplay',
];
$breadcrumbSchema = [
	'@context' => 'https://schema.org',
	'@type' => 'BreadcrumbList',
	'itemListElement' => [
		['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => rtrim(URL_ROOT_SITE, '/') . '/'],
		['@type' => 'ListItem', 'position' => 2, 'name' => 'Foto cosplay', 'item' => rtrim(URL_ROOT_SITE, '/') . '/foto-cosplay'],
	],
];
$selectedEventLabel = '';
foreach ($filterEvents as $eventOption) {
	if ((int) $filters['event_id'] === (int) $eventOption['id']) {
		$selectedEventLabel = (string) $eventOption['titolo'];
		if (!empty($eventOption['data_inizio'])) {
			$selectedEventLabel .= ' - ' . date('Y', strtotime((string) $eventOption['data_inizio']));
		}
		break;
	}
}
$selectedUploaderLabel = '';
foreach ($filterUploaders as $uploaderOption) {
	if ((int) $filters['uploader_id'] === (int) $uploaderOption['id']) {
		$selectedUploaderLabel = '@' . (string) $uploaderOption['username'];
		break;
	}
}
?>
<script type="application/ld+json"><?= json_encode($collectionSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script type="application/ld+json"><?= json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<main class="container mx-auto px-4 py-8">
	<nav class="mb-5 text-sm text-gray-600" aria-label="Breadcrumb">
		<a href="/" class="hover:text-green-800">Home</a>
		<span class="mx-2">/</span>
		<span class="font-semibold text-gray-900">Foto cosplay</span>
	</nav>

	<header class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
		<p class="text-sm font-bold uppercase tracking-wide text-green-800">Foto community</p>
		<h1 class="mt-2 text-3xl font-black text-gray-950 sm:text-4xl">Foto cosplay dalle fiere italiane</h1>
		<p class="mt-3 text-xl font-bold text-gray-800">Hai partecipato a una fiera? Trova le foto che ti hanno scattato.</p>
		<p class="mt-3 max-w-3xl text-sm leading-6 text-gray-600">
			ItalianCosplay raccoglie fotografie pubblicate dalla community durante eventi cosplay, comics e fiere nerd italiane, organizzandole per evento, edizione e autore.
		</p>
		<div class="mt-6 grid gap-3 sm:grid-cols-2">
			<a href="#filtri-foto" class="inline-flex min-h-12 items-center justify-center rounded-lg border border-green-800 px-4 py-3 text-center font-bold text-green-900 hover:bg-green-50">📸 Trova le foto del tuo evento</a>
			<a href="/dashboard/photos/upload" class="inline-flex min-h-12 items-center justify-center rounded-lg bg-green-800 px-4 py-3 text-center font-bold text-white hover:bg-green-700">Carica le tue foto</a>
		</div>
	</header>

	<section class="mt-8">
		<div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
			<div>
				<h2 class="text-2xl font-black text-gray-950">Ultimi eventi fotografati</h2>
				<p class="mt-1 text-sm text-gray-600">Edizioni evento con almeno una foto pubblicata.</p>
			</div>
		</div>
		<?php if (!empty($photographedEvents)): ?>
			<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
				<?php foreach ($photographedEvents as $event): ?>
					<?php
					$imageUrl = !empty($event['image_path']) ? '/public_assets/' . ltrim((string) $event['image_path'], '/') : '/public_assets/images/default_cover.png';
					$place = trim((string) ($event['comune_nome'] ?: ($event['luogo'] ?? '')));
					$year = (int) ($event['year'] ?: (!empty($event['data_inizio']) ? date('Y', strtotime((string) $event['data_inizio'])) : 0));
					?>
					<article class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-200">
						<div class="flex gap-3">
							<a href="/eventi-cosplay/<?= $h($event['slug']) ?>/foto" class="h-20 w-24 flex-none overflow-hidden rounded-lg bg-gray-100">
								<img src="<?= $h($imageUrl) ?>" alt="<?= $h($event['titolo']) ?>" width="<?= (int) ($event['image_width'] ?: 300) ?>" height="<?= (int) ($event['image_height'] ?: 200) ?>" loading="lazy" class="h-full w-full object-cover">
							</a>
							<div class="min-w-0 flex-1">
								<h3 class="line-clamp-1 font-black text-gray-950"><?= $h($event['titolo']) ?><?= $year > 0 ? ' ' . $year : '' ?></h3>
								<p class="mt-1 line-clamp-1 text-xs text-gray-600"><?= $h($formatDate($event['data_inizio'] ?? null, $event['data_fine'] ?? null)) ?><?= $place !== '' ? ' · ' . $h($place) : '' ?></p>
								<p class="mt-2 text-xs font-bold text-gray-800">📸 <?= (int) $event['photo_count'] ?> foto · <?= (int) $event['uploader_count'] ?> autori</p>
								<a href="/eventi-cosplay/<?= $h($event['slug']) ?>/foto" class="mt-2 inline-flex text-sm font-bold text-green-800 hover:underline">Guarda le foto</a>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php else: ?>
			<div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-gray-600">Non ci sono ancora eventi con foto pubblicate.</div>
		<?php endif; ?>
	</section>

	<section id="filtri-foto" class="mt-10 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
		<form method="get" action="/foto-cosplay" data-photo-filter-form class="flex flex-col gap-3 lg:flex-row lg:items-end">
			<label class="relative block lg:min-w-0 lg:flex-[1_1_42%]">
				<span class="text-sm font-bold text-gray-800">Evento</span>
				<input type="hidden" name="event" data-event-id value="<?= !empty($filters['event_id']) ? (int) $filters['event_id'] : '' ?>">
				<input data-event-typeahead value="<?= $h($selectedEventLabel) ?>" placeholder="Cerca evento fotografato" autocomplete="off" class="mt-1 min-h-12 w-full rounded-lg border border-gray-300 px-3 py-2" aria-autocomplete="list" aria-expanded="false">
				<div data-event-suggestions class="absolute left-0 right-0 z-20 mt-1 hidden max-h-72 overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg"></div>
				<div data-event-options class="hidden">
					<?php foreach ($filterEvents as $event): ?>
						<?php $eventLabel = (string) $event['titolo'] . (!empty($event['data_inizio']) ? ' - ' . date('Y', strtotime((string) $event['data_inizio'])) : ''); ?>
						<span data-id="<?= (int) $event['id'] ?>" data-label="<?= $h($eventLabel) ?>" data-meta="<?= (int) $event['photo_count'] ?> foto"></span>
					<?php endforeach; ?>
				</div>
			</label>
			<label class="block lg:w-32 lg:flex-none">
				<span class="text-sm font-bold text-gray-800">Anno</span>
				<select name="year" class="mt-1 min-h-12 w-full rounded-lg border border-gray-300 px-3 py-2">
					<option value="">Tutti</option>
					<?php foreach ($filterYears as $year): ?>
						<option value="<?= (int) $year ?>" <?= (int) $filters['year'] === (int) $year ? 'selected' : '' ?>><?= (int) $year ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label class="relative block lg:w-64 lg:flex-none">
				<span class="text-sm font-bold text-gray-800">Autore</span>
				<input type="hidden" name="uploader" data-uploader-id value="<?= !empty($filters['uploader_id']) ? (int) $filters['uploader_id'] : '' ?>">
				<input data-uploader-typeahead value="<?= $h($selectedUploaderLabel) ?>" placeholder="Cerca autore" autocomplete="off" class="mt-1 min-h-12 w-full rounded-lg border border-gray-300 px-3 py-2" aria-autocomplete="list" aria-expanded="false">
				<div data-uploader-suggestions class="absolute left-0 right-0 z-20 mt-1 hidden max-h-72 overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg"></div>
				<div data-uploader-options class="hidden">
					<?php foreach ($filterUploaders as $uploader): ?>
						<?php $uploaderLabel = '@' . (string) $uploader['username']; ?>
						<span data-id="<?= (int) $uploader['id'] ?>" data-label="<?= $h($uploaderLabel) ?>" data-meta="<?= (int) $uploader['photo_count'] ?> foto"></span>
					<?php endforeach; ?>
				</div>
			</label>
			<div class="flex gap-2 lg:flex-none">
				<button class="min-h-12 flex-1 rounded-lg bg-green-800 px-6 py-2 font-bold text-white hover:bg-green-700 lg:flex-none">Filtra</button>
				<a href="/foto-cosplay" class="inline-flex min-h-12 items-center rounded-lg border border-gray-300 px-4 py-2 font-bold text-gray-700 hover:bg-gray-50">Reset</a>
			</div>
		</form>
	</section>

	<section class="mt-10">
		<div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
			<div>
				<h2 class="text-2xl font-black text-gray-950">Ultime foto pubblicate</h2>
				<p class="mt-1 text-sm text-gray-600"><?= (int) $totalPhotos ?> foto trovate.</p>
			</div>
		</div>
		<?php if ($photos): ?>
			<div class="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-6">
				<?php foreach ($photos as $photo): ?>
					<article class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
						<a href="/eventi-cosplay/<?= $h($photo['event_slug']) ?>/foto/<?= (int) $photo['id'] ?>" class="group block bg-gray-100">
							<img src="<?= $h($photo['thumbnail_url']) ?>" alt="Foto cosplay di <?= $h($photo['event_title']) ?>" width="<?= (int) $photo['thumbnail_width'] ?>" height="<?= (int) $photo['thumbnail_height'] ?>" loading="lazy" class="aspect-square w-full object-cover transition group-hover:scale-105">
						</a>
						<div class="p-3">
							<a href="/eventi-cosplay/<?= $h($photo['event_slug']) ?>" class="line-clamp-2 text-sm font-bold text-gray-950 hover:text-green-800"><?= $h($photo['event_title']) ?></a>
							<a href="/u/<?= $h($photo['uploader_username']) ?>" class="mt-1 inline-flex text-xs font-semibold text-green-800 hover:underline">@<?= $h($photo['uploader_username']) ?></a>
							<p class="text-xs text-gray-500"><?= $h(date('d/m/Y', strtotime((string) $photo['created_at']))) ?></p>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
			<?php if ($totalPages > 1): ?>
				<nav class="mt-8 flex items-center justify-center gap-2">
					<?php if ($page > 1): ?><a class="rounded-lg border px-3 py-2 font-semibold" href="<?= $h($queryForPage($page - 1)) ?>">Precedenti</a><?php endif; ?>
					<span class="px-3 py-2 text-sm text-gray-600">Pagina <?= (int) $page ?> di <?= (int) $totalPages ?></span>
					<?php if ($page < $totalPages): ?><a class="rounded-lg border px-3 py-2 font-semibold" href="<?= $h($queryForPage($page + 1)) ?>">Successive</a><?php endif; ?>
				</nav>
			<?php endif; ?>
		<?php else: ?>
			<div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center">
				<p class="text-lg font-bold text-gray-900">Nessuna foto trovata con questi filtri.</p>
				<p class="mt-2 text-sm text-gray-600">Prova a cambiare evento, anno o autore.</p>
			</div>
		<?php endif; ?>
	</section>
</main>
<script>
document.querySelectorAll('[data-photo-filter-form]').forEach((form) => {
	const year = form.querySelector('select[name="year"]');
	const setupTypeahead = (inputSelector, hiddenSelector, suggestionsSelector, optionsSelector) => {
		const input = form.querySelector(inputSelector);
		const hidden = form.querySelector(hiddenSelector);
		const suggestions = form.querySelector(suggestionsSelector);
		const options = Array.from(form.querySelectorAll(optionsSelector + ' [data-id]')).map((option) => ({
			id: option.dataset.id || '',
			label: option.dataset.label || '',
			meta: option.dataset.meta || ''
		}));
		const closeSuggestions = () => {
			suggestions.classList.add('hidden');
			input.setAttribute('aria-expanded', 'false');
		};
		const choose = (option) => {
			input.value = option.label;
			hidden.value = option.id;
			closeSuggestions();
		};
		const renderSuggestions = () => {
			const query = input.value.trim().toLowerCase();
			hidden.value = '';
			if (query.length < 1) {
				closeSuggestions();
				return;
			}
			const matches = options.filter((option) => option.label.toLowerCase().includes(query)).slice(0, 8);
			suggestions.innerHTML = '';
			matches.forEach((option) => {
				const button = document.createElement('button');
				button.type = 'button';
				button.className = 'block w-full px-3 py-2 text-left hover:bg-green-50 focus:bg-green-50 focus:outline-none';
				button.innerHTML = '<span class="block text-sm font-bold text-gray-900"></span><span class="block text-xs text-gray-500"></span>';
				button.querySelector('span').textContent = option.label;
				button.querySelectorAll('span')[1].textContent = option.meta;
				button.addEventListener('click', () => choose(option));
				suggestions.appendChild(button);
			});
			suggestions.classList.toggle('hidden', matches.length === 0);
			input.setAttribute('aria-expanded', matches.length > 0 ? 'true' : 'false');
		};
		const sync = () => {
			const selected = options.find((option) => option.label === input.value);
			hidden.value = selected ? selected.id : '';
		};
		input.addEventListener('input', renderSuggestions);
		input.addEventListener('focus', renderSuggestions);
		input.addEventListener('blur', () => window.setTimeout(closeSuggestions, 150));
		return { hidden, sync };
	};
	const eventTypeahead = setupTypeahead('[data-event-typeahead]', '[data-event-id]', '[data-event-suggestions]', '[data-event-options]');
	const uploaderTypeahead = setupTypeahead('[data-uploader-typeahead]', '[data-uploader-id]', '[data-uploader-suggestions]', '[data-uploader-options]');
	const syncEventId = () => {
		eventTypeahead.sync();
		uploaderTypeahead.sync();
	};
	const hidden = eventTypeahead.hidden;
	const uploader = uploaderTypeahead.hidden;
	form.addEventListener('submit', syncEventId);
	form.addEventListener('submit', (event) => {
		event.preventDefault();
		syncEventId();
		const segments = [];
		if (hidden.value) segments.push('evento-' + encodeURIComponent(hidden.value));
		if (year.value) segments.push('anno-' + encodeURIComponent(year.value));
		if (uploader.value) segments.push('autore-' + encodeURIComponent(uploader.value));
		window.location.href = '/foto-cosplay' + (segments.length ? '/' + segments.join('/') : '');
	});
});
</script>
