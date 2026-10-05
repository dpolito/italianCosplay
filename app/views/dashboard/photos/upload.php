<?php
$h = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$session = is_array($uploadSession ?? null) ? $uploadSession : null;
$selected = $preselectedEvent ?? null;
if (!$selected && $session) {
	$selected = [
		'id' => (int) $session['event_id'],
		'titolo' => (string) ($session['event_title'] ?? ''),
		'data_inizio' => $session['data_inizio'] ?? null,
		'data_fine' => $session['data_fine'] ?? null,
	];
}
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

<?php if ($session && (int) ($session['completed_count'] ?? 0) > 0): ?>
	<div class="mt-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-950">
		<p class="font-bold">Hai un caricamento in corso</p>
		<p class="mt-1 text-sm"><?= (int) $session['completed_count'] ?> foto già caricate<?= !empty($session['event_title']) ? ' per ' . $h($session['event_title']) : '' ?>.</p>
	</div>
<?php endif; ?>

<section class="mt-6 space-y-6" data-photo-upload data-concurrency="<?= (int) $photoConfig->uploadConcurrency ?>" data-max-upload-bytes="<?= (int) $photoConfig->maxUploadBytes ?>">
	<input type="hidden" data-csrf value="<?= $h($csrf_token) ?>">
	<script type="application/json" data-upload-session-json><?= json_encode($session, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
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

	<div class="flex flex-col gap-3 rounded-xl border border-gray-200 p-4 lg:flex-row lg:items-center lg:justify-between">
		<div>
			<p class="font-semibold text-gray-900"><span data-total>0</span> fotografie nella sessione · <span data-completed>0</span> completate</p>
			<p class="mt-1 text-sm text-gray-600"><span data-uploading>0</span> in caricamento · <span data-waiting>0</span> in attesa · <span data-failed>0</span> con errore</p>
			<div class="mt-3 h-2 overflow-hidden rounded bg-gray-100"><div data-total-bar class="h-full w-0 bg-green-700"></div></div>
		</div>
		<div class="flex flex-wrap gap-2">
			<button data-start type="button" class="rounded-lg bg-green-800 px-5 py-2 font-semibold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-gray-400" disabled>Carica</button>
			<button data-confirm type="button" class="rounded-lg bg-blue-800 px-5 py-2 font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-400" disabled>Conferma pubblicazione</button>
			<button data-cancel type="button" class="rounded-lg border border-red-700 px-5 py-2 font-semibold text-red-700 hover:bg-red-50">Annulla caricamento</button>
		</div>
	</div>

	<div data-upload-summary class="hidden rounded-xl border p-4" role="status" aria-live="polite"></div>

	<ul data-file-list class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5"></ul>
</section>

<script src="/public_assets/js/photo-upload.js"></script>
