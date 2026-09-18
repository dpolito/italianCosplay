<?php
$groupedFavorites = $data['groupedFavorites'] ?? [];
$summary = $data['summary'] ?? [];

if (!function_exists('favorite_short_date')) {
	function favorite_short_date(?string $date): string
	{
		if (empty($date)) {
			return 'Data non disponibile';
		}

		$timestamp = strtotime($date);
		return $timestamp ? date('d/m/Y', $timestamp) : 'Data non disponibile';
	}
}

$sections = [
	'event' => [
		'label' => 'Eventi',
		'icon' => 'fa-calendar-days',
		'empty' => 'Nessun evento salvato al momento.',
	],
	'guest' => [
		'label' => 'Guest',
		'icon' => 'fa-user-tie',
		'empty' => 'Nessun guest salvato al momento.',
	],
	'blog_post' => [
		'label' => 'Articoli',
		'icon' => 'fa-newspaper',
		'empty' => 'Nessun articolo salvato al momento.',
	],
	'regione' => [
		'label' => 'Regioni',
		'icon' => 'fa-map',
		'empty' => 'Nessuna regione salvata al momento.',
	],
	'provincia' => [
		'label' => 'Province',
		'icon' => 'fa-location-dot',
		'empty' => 'Nessuna provincia salvata al momento.',
	],
	'comune' => [
		'label' => 'Comuni',
		'icon' => 'fa-location-dot',
		'empty' => 'Nessuna location salvata al momento.',
	],
];
?>

<section class="mx-auto max-w-7xl space-y-6" aria-labelledby="favorites-page-title">
	<header class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
		<div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-amber-800">Dashboard community</p>
				<h1 id="favorites-page-title" class="mt-2 text-3xl font-extrabold text-gray-950 md:text-4xl">I tuoi preferiti</h1>
				<p class="mt-3 max-w-2xl text-base leading-relaxed text-gray-700">
					Ritrova in un posto unico eventi, guest, articoli e location che hai deciso di tenere a portata di mano.
				</p>
			</div>
			<div class="flex flex-wrap gap-3">
				<a href="/dashboard" class="inline-flex rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-800 hover:bg-gray-50">Torna alla dashboard</a>
				<a href="/eventi-cosplay" class="inline-flex rounded-lg bg-amber-700 px-4 py-2 text-sm font-bold text-white hover:bg-amber-800">Sfoglia eventi</a>
			</div>
		</div>
	</header>

	<div class="grid gap-4 md:grid-cols-4">
		<div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
			<p class="text-sm font-semibold text-amber-900">Totale salvati</p>
			<p class="mt-2 text-3xl font-bold text-amber-950"><?php echo (int)($summary['total'] ?? 0); ?></p>
		</div>
		<div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
			<p class="text-sm font-semibold text-amber-900">Eventi</p>
			<p class="mt-2 text-3xl font-bold text-amber-950"><?php echo (int)($summary['event'] ?? 0); ?></p>
		</div>
		<div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
			<p class="text-sm font-semibold text-amber-900">Guest</p>
			<p class="mt-2 text-3xl font-bold text-amber-950"><?php echo (int)($summary['guest'] ?? 0); ?></p>
		</div>
		<div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
			<p class="text-sm font-semibold text-amber-900">Articoli</p>
			<p class="mt-2 text-3xl font-bold text-amber-950"><?php echo (int)($summary['blog_post'] ?? 0); ?></p>
		</div>
		<div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
			<p class="text-sm font-semibold text-amber-900">Regioni</p>
			<p class="mt-2 text-3xl font-bold text-amber-950"><?php echo (int)($summary['regione'] ?? 0); ?></p>
		</div>
		<div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
			<p class="text-sm font-semibold text-amber-900">Province</p>
			<p class="mt-2 text-3xl font-bold text-amber-950"><?php echo (int)($summary['provincia'] ?? 0); ?></p>
		</div>
	</div>

	<nav class="flex flex-wrap gap-3" aria-label="Filtri preferiti">
		<?php foreach ($sections as $type => $meta): ?>
			<a href="#favorites-<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex items-center gap-2 rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-800 hover:border-amber-400 hover:text-amber-900">
				<i class="fa-solid <?php echo htmlspecialchars($meta['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
				<span><?php echo htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8'); ?></span>
			</a>
		<?php endforeach; ?>
	</nav>

	<?php foreach ($sections as $type => $meta): ?>
		<section id="favorites-<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>" class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
			<div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
				<div>
					<p class="text-sm font-bold uppercase tracking-wide text-amber-800"><?php echo htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8'); ?></p>
					<h2 class="mt-2 text-2xl font-bold text-gray-950"><?php echo htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8'); ?> salvati</h2>
					<p class="mt-2 text-gray-700"><?php echo htmlspecialchars($meta['empty'], ENT_QUOTES, 'UTF-8'); ?></p>
				</div>
				<div class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700">
					<span><?php echo count($groupedFavorites[$type] ?? []); ?></span>
					<span>elementi</span>
				</div>
			</div>

			<?php $items = $groupedFavorites[$type] ?? []; ?>
			<?php if (empty($items)): ?>
				<div class="mt-6 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-gray-600">
					<?php echo htmlspecialchars($meta['empty'], ENT_QUOTES, 'UTF-8'); ?>
				</div>
			<?php else: ?>
				<div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
					<?php foreach ($items as $item): ?>
						<article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
							<?php if (!empty($item['image'])): ?>
								<a href="<?php echo htmlspecialchars($item['url'] ?? '#', ENT_QUOTES, 'UTF-8'); ?>">
									<img src="<?php echo htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['title'] ?? 'Preferito', ENT_QUOTES, 'UTF-8'); ?>" class="h-44 w-full object-cover">
								</a>
							<?php endif; ?>
							<div class="p-5">
								<div class="flex items-start justify-between gap-3">
									<div>
										<h3 class="text-lg font-bold text-gray-950"><?php echo htmlspecialchars($item['title'] ?? 'Preferito', ENT_QUOTES, 'UTF-8'); ?></h3>
										<p class="mt-1 text-sm text-gray-600"><?php echo htmlspecialchars($item['subtitle'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
									</div>
									<form method="post" action="/dashboard/favorites/toggle" class="js-favorite-toggle" data-remove-card="1">
										<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
										<input type="hidden" name="entity_type" value="<?php echo htmlspecialchars((string)($item['type'] ?? $type), ENT_QUOTES, 'UTF-8'); ?>">
										<input type="hidden" name="entity_id" value="<?php echo (int)($item['id'] ?? 0); ?>">
										<input type="hidden" name="redirect_to" value="/dashboard/favorites">
										<button type="submit" class="js-favorite-button inline-flex items-center gap-2 rounded-full border border-red-200 bg-red-50 px-4 py-2 text-sm font-bold text-red-800 shadow-sm transition hover:bg-red-100 hover:border-red-300 focus:outline-none focus:ring-2 focus:ring-red-400" title="Rimuovi dai preferiti" data-label-add="Salva tra i preferiti" data-label-remove="Rimuovi dai preferiti" data-icon-add="fa-bookmark" data-icon-remove="fa-bookmark-slash" data-active="1">
											<i class="fa-solid fa-bookmark-slash" aria-hidden="true"></i>
											<span class="hidden sm:inline">Rimuovi</span>
										</button>
									</form>
								</div>

								<div class="mt-4 flex items-center justify-between gap-3">
									<p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
										Aggiunto il <?php echo htmlspecialchars(favorite_short_date($item['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?>
									</p>
									<a href="<?php echo htmlspecialchars($item['url'] ?? '#', ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex items-center justify-center rounded-lg bg-green-800 px-4 py-2 text-sm font-bold text-white hover:bg-green-900">
										Apri
									</a>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
	<?php endforeach; ?>
</section>
