<?php
$h = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$associationLabel = static function (array $row): string {
	$name = !empty($row['username'])
		? '@' . (string) $row['username']
		: (string) ($row['display_name'] ?: 'Cosplayer');
	$cosplay = (string) ($row['custom_name'] ?: ($row['name_full'] ?? $row['name_native'] ?? ''));
	return $cosplay !== '' ? $name . ' · ' . $cosplay : $name;
};
?>
<header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
	<div>
		<h1 class="text-2xl font-bold text-gray-900"><?= $h($event['titolo']) ?></h1>
		<p class="mt-1 text-sm text-gray-600">Gestisci foto, associazioni e modifiche massive.</p>
	</div>
	<a href="/dashboard/photos/upload?event_id=<?= (int) $event['id'] ?>" class="rounded-lg bg-green-800 px-4 py-2 font-semibold text-white hover:bg-green-700">Carica altre foto</a>
</header>

<section data-photo-manage data-current-user-id="<?= (int) ($user['id'] ?? 0) ?>" data-current-username="<?= $h($user['username'] ?? '') ?>" class="mt-6">
	<input type="hidden" data-csrf value="<?= $h($csrf_token) ?>">
	<div class="mb-4 rounded-xl border border-blue-200 bg-blue-50 p-4 text-blue-950">
		<h2 class="font-bold">Come gestire queste foto</h2>
		<p class="mt-1 text-sm leading-6">Seleziona una o più foto, poi usa le azioni massive. Puoi cercare un utente registrato, inserire un nome non registrato oppure usare “Associami” per collegare il tuo profilo. Le associazioni salvate compaiono sotto ogni foto.</p>
	</div>
	<div data-manage-feedback class="mb-4 hidden rounded-xl border p-4 text-sm font-semibold" role="status" aria-live="polite"></div>
	<div class="mb-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
		<p class="font-semibold"><span data-selected-count>0</span> foto selezionate</p>
		<div class="mt-3 grid gap-3 md:grid-cols-4">
			<input data-user-id type="hidden">
			<input data-user-search type="search" placeholder="Cerca cosplayer" class="rounded-lg border border-gray-300 px-3 py-2">
			<select data-cosplay-id class="rounded-lg border border-gray-300 px-3 py-2">
				<option value="">Cosplay non specificato</option>
				<?php foreach ($userCosplays as $cosplay): ?>
					<option value="<?= (int) $cosplay['id'] ?>"><?= $h($cosplay['custom_name'] ?: ($cosplay['name_full'] ?? $cosplay['name_native'] ?? 'Cosplay')) ?></option>
				<?php endforeach; ?>
			</select>
			<input data-display-name type="text" placeholder="Nome non registrato" class="rounded-lg border border-gray-300 px-3 py-2">
			<input data-instagram type="text" placeholder="Instagram" class="rounded-lg border border-gray-300 px-3 py-2">
		</div>
		<div data-user-results class="mt-2 hidden rounded-lg border border-gray-200 bg-white"></div>
		<div class="mt-3 flex flex-wrap gap-2">
			<button data-associate type="button" class="rounded-lg bg-green-800 px-4 py-2 font-semibold text-white hover:bg-green-700">Associa cosplayer</button>
			<button data-self type="button" class="rounded-lg border border-green-800 px-4 py-2 font-semibold text-green-900 hover:bg-green-50">Associami</button>
			<button data-delete type="button" class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-white hover:bg-red-600">Elimina</button>
		</div>
	</div>

	<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
		<?php foreach ($photos as $photo): ?>
			<article data-photo-card data-photo-id="<?= (int) $photo['id'] ?>" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
				<label class="group relative block bg-gray-100">
					<input type="checkbox" data-photo-checkbox value="<?= (int) $photo['id'] ?>" class="absolute left-2 top-2 z-10 h-5 w-5">
					<img src="<?= $h($photo['thumbnail_url']) ?>" alt="Foto caricata" width="<?= (int) $photo['thumbnail_width'] ?>" height="<?= (int) $photo['thumbnail_height'] ?>" loading="lazy" class="aspect-square w-full object-cover">
				</label>
				<div class="p-3">
					<p class="text-xs font-bold uppercase tracking-wide text-gray-500">Associati</p>
					<div data-associations class="mt-2 flex flex-wrap gap-1.5">
						<?php foreach (($photoCosplayers[(int) $photo['id']] ?? []) as $association): ?>
							<span data-association-id="<?= (int) $association['id'] ?>" class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">
								<span><?= $h($associationLabel($association)) ?></span>
								<button type="button" data-remove-association="<?= (int) $association['id'] ?>" class="ml-1 rounded-full px-1 text-green-900 hover:bg-green-200" aria-label="Rimuovi associazione">×</button>
							</span>
						<?php endforeach; ?>
						<?php if (empty($photoCosplayers[(int) $photo['id']])): ?>
							<span data-empty-association class="text-xs text-gray-500">Nessuna associazione</span>
						<?php endif; ?>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
<script src="/public_assets/js/photo-manage.js"></script>
