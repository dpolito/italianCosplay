<?php
$h = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$selected = $preselectedEvent ?? null;
$formatItalianDate = static function (?string $date): string {
	if (empty($date)) {
		return '';
	}
	$months = [
		1 => 'gennaio',
		2 => 'febbraio',
		3 => 'marzo',
		4 => 'aprile',
		5 => 'maggio',
		6 => 'giugno',
		7 => 'luglio',
		8 => 'agosto',
		9 => 'settembre',
		10 => 'ottobre',
		11 => 'novembre',
		12 => 'dicembre',
	];
	$timestamp = strtotime($date);
	if (!$timestamp) {
		return $date;
	}
	return (int) date('j', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
};
?>
<header>
	<h1 class="text-2xl font-bold text-gray-900">Carica foto</h1>
	<p class="mt-1 text-sm text-gray-600">Seleziona l'evento, trascina le immagini e lascia lavorare la coda.</p>
</header>

<section class="mt-6 space-y-6" data-photo-upload data-concurrency="<?= (int) $photoConfig->uploadConcurrency ?>" data-max-upload-bytes="<?= (int) $photoConfig->maxUploadBytes ?>">
	<input type="hidden" data-csrf value="<?= $h($csrf_token) ?>">
	<div class="rounded-xl border border-gray-200 p-4">
		<label for="event-search" class="block text-sm font-semibold text-gray-800">Evento</label>
		<input id="event-search" data-event-search type="search" autocomplete="off" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="Cerca Romics, Lucca Comics...">
		<input type="hidden" data-event-id value="<?= $selected ? (int) $selected['id'] : '' ?>">
		<div data-event-results class="mt-2 hidden overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm"></div>
		<p data-event-selected class="mt-2 text-sm font-semibold text-green-800">
			<?php if ($selected): ?>
				Evento selezionato: <?= $h($selected['titolo']) ?>
				<?php if (!empty($selected['data_inizio'])): ?>
					· <?= $h($formatItalianDate((string) $selected['data_inizio'])) ?><?= !empty($selected['data_fine']) && $selected['data_fine'] !== $selected['data_inizio'] ? ' - ' . $h($formatItalianDate((string) $selected['data_fine'])) : '' ?>
				<?php endif; ?>
			<?php endif; ?>
		</p>
	</div>

	<div data-dropzone class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-8 text-center">
		<p class="text-lg font-bold text-gray-900">Trascina qui le fotografie</p>
		<p class="mt-1 text-sm text-gray-600">JPEG, PNG, WebP o HEIC se supportato dal server. Max <?= (int) ($photoConfig->maxUploadBytes / 1024 / 1024) ?> MB per file.</p>
		<label class="mt-4 inline-flex cursor-pointer rounded-lg bg-green-800 px-4 py-2 font-semibold text-white hover:bg-green-700">
			Seleziona foto
			<input data-file-input type="file" accept="image/jpeg,image/png,image/webp,image/heic,image/heif" multiple class="sr-only">
		</label>
	</div>

	<div class="flex flex-col gap-3 rounded-xl border border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between">
		<p class="font-semibold text-gray-900"><span data-total>0</span> fotografie selezionate · <span data-completed>0</span> completate</p>
		<button data-start type="button" class="rounded-lg bg-green-800 px-5 py-2 font-semibold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-gray-400" disabled>Carica</button>
	</div>

	<div data-upload-summary class="hidden rounded-xl border p-4" role="status" aria-live="polite"></div>

	<ul data-file-list class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5"></ul>
</section>

<script src="/public_assets/js/photo-upload.js"></script>
