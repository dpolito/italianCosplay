<?php
$profiles = $data['profiles'] ?? [];
$filters = $data['filters'] ?? [];
$totalProfiles = (int) ($data['totalProfiles'] ?? 0);
$totalPages = (int) ($data['totalPages'] ?? 1);
$currentPage = (int) ($data['currentPage'] ?? 1);

$buildUrl = static function (array $override = []) use ($filters): string {
	$params = array_merge($filters, $override);
	unset($params['page']);
	$query = array_filter($params, static function ($value) {
		return $value !== '' && $value !== null && $value !== false;
	});
	$queryString = http_build_query($query);
	return '/u' . ($queryString ? '?' . $queryString : '');
};
?>

<main class="space-y-8">
	<section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-gray-200">
		<div class="bg-gradient-to-r from-emerald-900 via-green-800 to-lime-700 px-6 py-10 text-white md:px-8 md:py-14">
			<p class="text-sm font-semibold uppercase tracking-[0.22em] text-white/80">Profili community</p>
			<h1 class="mt-3 text-3xl font-black tracking-tight md:text-5xl">Profili cosplay pubblici</h1>
			<p class="mt-4 max-w-3xl text-base leading-7 text-white/85 md:text-lg">
				Esplora i profili pubblici della community cosplay italiana. Qui compaiono solo gli account con email verificata.
			</p>
			<div class="mt-6 flex flex-wrap gap-3">
				<a href="/u" class="inline-flex items-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-gray-900 transition hover:bg-gray-100">Reset filtri</a>
				<a href="/segnala-evento-cosplay" class="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">Segnala un evento</a>
			</div>
		</div>
		<div class="grid gap-4 px-6 py-6 md:grid-cols-3 md:px-8">
			<div class="rounded-2xl bg-gray-50 p-4 ring-1 ring-gray-200">
				<p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Profili trovati</p>
				<p class="mt-1 text-2xl font-black text-gray-900"><?php echo $totalProfiles; ?></p>
			</div>
			<div class="rounded-2xl bg-gray-50 p-4 ring-1 ring-gray-200">
				<p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Pagina</p>
				<p class="mt-1 text-2xl font-black text-gray-900"><?php echo $currentPage; ?> / <?php echo $totalPages; ?></p>
			</div>
			<div class="rounded-2xl bg-gray-50 p-4 ring-1 ring-gray-200">
				<p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Visibilità</p>
				<p class="mt-1 text-2xl font-black text-gray-900">Solo verificati</p>
			</div>
		</div>
	</section>

	<section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-gray-200 md:p-6">
		<form method="GET" action="/u" class="grid gap-4 lg:grid-cols-6">
			<div class="lg:col-span-2">
				<label for="q" class="mb-1 block text-sm font-semibold text-gray-700">Cerca profilo</label>
				<input type="text" id="q" name="q" value="<?php echo htmlspecialchars($filters['q'] ?? ''); ?>" placeholder="Username, nome o bio" class="w-full rounded-2xl border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-200">
			</div>
			<div class="lg:col-span-2">
				<label for="location" class="mb-1 block text-sm font-semibold text-gray-700">Località</label>
				<input type="text" id="location" name="location" value="<?php echo htmlspecialchars($filters['location'] ?? ''); ?>" placeholder="Comune o area" class="w-full rounded-2xl border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-200">
			</div>
			<div>
				<label for="sort" class="mb-1 block text-sm font-semibold text-gray-700">Ordina</label>
				<select id="sort" name="sort" class="w-full rounded-2xl border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-200">
					<option value="recent" <?php echo (($filters['sort'] ?? '') === 'recent') ? 'selected' : ''; ?>>Più recenti</option>
					<option value="created_old" <?php echo (($filters['sort'] ?? '') === 'created_old') ? 'selected' : ''; ?>>Più vecchi</option>
					<option value="username" <?php echo (($filters['sort'] ?? '') === 'username') ? 'selected' : ''; ?>>Username</option>
				</select>
			</div>
			<div class="flex items-end gap-3 lg:col-span-1">
				<button type="submit" class="w-full rounded-2xl bg-green-700 px-5 py-3 font-semibold text-white transition hover:bg-green-800">Filtra</button>
			</div>
			<div class="flex flex-wrap gap-4 lg:col-span-6">
				<label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
					<input type="checkbox" name="has_bio" value="1" <?php echo !empty($filters['has_bio']) ? 'checked' : ''; ?> class="rounded border-gray-300 text-green-700 focus:ring-green-500">
					Solo profili con bio
				</label>
				<label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
					<input type="hidden" name="direction" value="DESC">
					<a href="<?php echo htmlspecialchars($buildUrl(['direction' => (($filters['direction'] ?? 'DESC') === 'ASC' ? 'DESC' : 'ASC') ])); ?>" class="text-green-800 hover:underline">
						Ordine: <?php echo (($filters['direction'] ?? 'DESC') === 'ASC') ? 'crescente' : 'decrescente'; ?>
					</a>
				</label>
			</div>
		</form>
	</section>

	<section>
		<?php if (empty($profiles)): ?>
			<div class="rounded-3xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
				<h2 class="text-2xl font-black text-gray-900">Nessun profilo trovato</h2>
				<p class="mt-3 text-gray-600">Prova a rimuovere alcuni filtri o a cambiare il testo di ricerca.</p>
				<div class="mt-6">
					<a href="/u" class="inline-flex items-center rounded-full bg-green-700 px-5 py-3 font-semibold text-white transition hover:bg-green-800">Torna alla lista completa</a>
				</div>
			</div>
		<?php else: ?>
			<div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
				<?php foreach ($profiles as $profile): ?>
					<?php
					$profileAvatar = !empty($profile['avatar']) ? (str_starts_with($profile['avatar'], 'http') ? $profile['avatar'] : (rtrim(URL_ROOT_SITE, '/') . $profile['avatar'])) : '/public_assets/images/default_avatar.png';
					$profileName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
					$profileLabel = $profileName !== '' ? $profileName : $profile['username'];
					$bioPreview = trim((string) ($profile['bio'] ?? ''));
					$bioPreview = $bioPreview !== '' ? mb_substr(strip_tags($bioPreview), 0, 120) . (mb_strlen(strip_tags($bioPreview)) > 120 ? '...' : '') : 'Nessuna bio disponibile.';
					?>
					<article class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-gray-200 transition hover:-translate-y-0.5 hover:shadow-md">
						<a href="/u/<?php echo htmlspecialchars($profile['username']); ?>" class="block">
							<div class="relative h-32 bg-gradient-to-r from-emerald-900 via-green-800 to-lime-700">
								<div class="absolute inset-0 bg-black/15"></div>
								<div class="absolute -bottom-8 left-5 rounded-full bg-white p-1 shadow-lg">
									<img src="<?php echo htmlspecialchars($profileAvatar); ?>" alt="Avatar di <?php echo htmlspecialchars($profileLabel); ?>" class="h-16 w-16 rounded-full object-cover">
								</div>
							</div>
							<div class="px-5 pb-5 pt-10">
								<div class="flex items-start justify-between gap-4">
									<div>
										<h3 class="text-xl font-black text-gray-950"><?php echo htmlspecialchars($profileLabel); ?></h3>
										<p class="mt-1 text-sm text-gray-500">@<?php echo htmlspecialchars($profile['username']); ?></p>
									</div>
									<span class="rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-800">Profilo</span>
								</div>

								<p class="mt-4 text-sm leading-6 text-gray-700"><?php echo htmlspecialchars($bioPreview); ?></p>

								<div class="mt-5 flex flex-wrap gap-2">
									<?php if (!empty($profile['comune_name'])): ?>
										<span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700"><?php echo htmlspecialchars($profile['comune_name']); ?></span>
									<?php endif; ?>
									<?php if (!empty($profile['verified'])): ?>
										<span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Verificato</span>
									<?php endif; ?>
								</div>

								<div class="mt-5 inline-flex items-center font-semibold text-green-800">
									Apri profilo →
								</div>
							</div>
						</a>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>

	<?php if ($totalPages > 1): ?>
		<nav class="flex items-center justify-center gap-2 pb-4" aria-label="Paginazione profili">
			<?php if ($currentPage > 1): ?>
				<a href="<?php echo htmlspecialchars($buildUrl(['page' => $currentPage - 1])); ?>" class="rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:border-green-300 hover:text-green-800">Precedente</a>
			<?php endif; ?>
			<span class="rounded-full bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700">Pagina <?php echo $currentPage; ?> di <?php echo $totalPages; ?></span>
			<?php if ($currentPage < $totalPages): ?>
				<a href="<?php echo htmlspecialchars($buildUrl(['page' => $currentPage + 1])); ?>" class="rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:border-green-300 hover:text-green-800">Successiva</a>
			<?php endif; ?>
		</nav>
	<?php endif; ?>
</main>
