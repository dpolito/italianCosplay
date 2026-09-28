<?php
$user = $data['user'] ?? null;
$settings = $data['settings'] ?? [];

if (!$user) {
	return;
}

$siteBaseUrl = rtrim(URL_ROOT_SITE, '/');
$profileUrl = $data['canonicalUrl'] ?? ($siteBaseUrl . '/u/' . rawurlencode($user['username']));

$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$displayName = (!empty($settings['show_nome']))
	? ($fullName !== '' ? $fullName : $user['username'])
	: $user['username'];
$avatarUrl = !empty($user['avatar']) ? (str_starts_with($user['avatar'], 'http') ? $user['avatar'] : $siteBaseUrl . $user['avatar']) : $siteBaseUrl . '/public_assets/images/default_avatar.png';
$coverUrl = !empty($user['profile_cover']) ? (str_starts_with($user['profile_cover'], 'http') ? $user['profile_cover'] : $siteBaseUrl . $user['profile_cover']) : '';
$bio = (string) ($user['bio'] ?? '');
$website = trim((string) ($user['website'] ?? ''));
$location = !empty($user['comune_name']) ? $user['comune_name'] : 'Italia';
$joinedAt = null;
if (!empty($user['created_at'])) {
	$joinedTimestamp = strtotime($user['created_at']);
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

	if ($joinedTimestamp !== false) {
		$month = (int) date('n', $joinedTimestamp);
		$year = date('Y', $joinedTimestamp);
		$joinedAt = ($months[$month] ?? date('F', $joinedTimestamp)) . ' ' . $year;
	}
}
$socialLinks = $user['social_links'] ?? [];

$socialConfig = [
	'instagram' => ['label' => 'Instagram', 'icon' => 'fa-instagram', 'enabled' => !empty($settings['show_instagram'])],
	'tiktok' => ['label' => 'TikTok', 'icon' => 'fa-tiktok', 'enabled' => !empty($settings['show_tiktok'])],
	'youtube' => ['label' => 'YouTube', 'icon' => 'fa-youtube', 'enabled' => !empty($settings['show_youtube'])],
	'facebook' => ['label' => 'Facebook', 'icon' => 'fa-facebook-f', 'enabled' => !empty($settings['show_facebook'])],
];

$profileHighlights = [];
if (!empty($location) && !empty($settings['show_comune'])) {
	$profileHighlights[] = ['label' => 'Località', 'value' => $location];
}
if ($joinedAt) {
	$profileHighlights[] = ['label' => 'Iscritto', 'value' => $joinedAt];
}
if (!empty($website)) {
	$profileHighlights[] = ['label' => 'Sito', 'value' => parse_url($website, PHP_URL_HOST) ?: 'link esterno'];
}

$structuredData = [
	'@context' => 'https://schema.org',
	'@type' => 'Person',
	'name' => $displayName,
	'url' => $profileUrl,
	'image' => $avatarUrl,
	'description' => $bio !== '' ? strip_tags($bio) : ('Profilo pubblico di @' . $user['username'] . ' su ItalianCosplay'),
];
?>

<script type="application/ld+json">
<?php echo json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
</script>

<nav class="mb-5 text-sm text-gray-600" aria-label="Breadcrumb">
	<ol class="flex flex-wrap items-center gap-2">
		<li><a href="/" class="hover:text-green-900 hover:underline">Home</a></li>
		<li aria-hidden="true">/</li>
		<li><a href="/u" class="hover:text-green-900 hover:underline">Profili</a></li>
		<li aria-hidden="true">/</li>
		<li class="font-medium text-gray-900" aria-current="page">@<?php echo htmlspecialchars($user['username']); ?></li>
	</ol>
</nav>

<main class="space-y-8">
	<section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-gray-200">
		<div class="relative">
			<div class="h-40 bg-gradient-to-r from-emerald-900 via-green-800 to-lime-700 md:h-56">
				<?php if (!empty($coverUrl)): ?>
					<img src="<?php echo htmlspecialchars($coverUrl); ?>" alt="Cover di <?php echo htmlspecialchars($displayName); ?>" class="h-full w-full object-cover" style="object-position: center <?php echo (int) ($user['cover_position_y'] ?? 50); ?>%;">
				<?php endif; ?>
			</div>
			<div class="absolute inset-0 bg-gradient-to-t from-black/45 via-black/10 to-transparent"></div>
			<div class="absolute inset-x-0 bottom-0 px-5 pb-5 md:px-8 md:pb-8">
				<div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
					<div class="flex items-end gap-4">
						<div class="-mb-10 rounded-full bg-white p-1 shadow-xl md:-mb-12">
							<img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar di <?php echo htmlspecialchars($displayName); ?>" class="h-24 w-24 rounded-full object-cover md:h-32 md:w-32">
						</div>
						<div class="text-white">
							<p class="text-sm font-semibold uppercase tracking-[0.2em] text-white/80">Profilo pubblico</p>
							<h1 class="mt-1 text-3xl font-black tracking-tight md:text-4xl">
								<?php echo htmlspecialchars($displayName); ?>
							</h1>
							<p class="mt-1 text-white/85">@<?php echo htmlspecialchars($user['username']); ?></p>
						</div>
					</div>
					<div class="flex flex-wrap gap-3">
						<?php if (!empty($website)): ?>
							<a href="<?php echo htmlspecialchars($website); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-full bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm transition hover:bg-gray-100">
								<i class="fa-solid fa-globe mr-2 text-green-700"></i>Sito web
							</a>
						<?php endif; ?>
						<a href="#bio" class="inline-flex items-center justify-center rounded-full border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">
							<i class="fa-solid fa-circle-user mr-2"></i>Vai alla bio
						</a>
					</div>
				</div>
			</div>
		</div>
		<div class="grid gap-4 px-5 pb-5 pt-14 md:grid-cols-3 md:px-8 md:pb-8">
			<?php foreach ($profileHighlights as $highlight): ?>
				<div class="rounded-2xl bg-gray-50 p-4 ring-1 ring-gray-200">
					<p class="text-xs font-semibold uppercase tracking-wider text-gray-500"><?php echo htmlspecialchars($highlight['label']); ?></p>
					<p class="mt-1 text-sm font-bold text-gray-900"><?php echo htmlspecialchars($highlight['value']); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<div class="grid gap-8 lg:grid-cols-[340px_1fr]">
		<aside class="space-y-6">
			<section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
				<h2 class="text-lg font-bold text-gray-900">Info profilo</h2>
				<div class="mt-4 space-y-4">
					<div>
						<p class="text-sm text-gray-500">Username</p>
						<p class="font-semibold text-gray-900">@<?php echo htmlspecialchars($user['username']); ?></p>
					</div>
					<?php if ($joinedAt): ?>
						<div>
							<p class="text-sm text-gray-500">Iscritto da</p>
							<p class="font-semibold text-gray-900"><?php echo htmlspecialchars($joinedAt); ?></p>
						</div>
					<?php endif; ?>
					<?php if (!empty($settings['show_comune']) && !empty($user['comune_name'])): ?>
						<div>
							<p class="text-sm text-gray-500">Località</p>
							<p class="font-semibold text-gray-900"><?php echo htmlspecialchars($user['comune_name']); ?></p>
						</div>
					<?php endif; ?>
				</div>
			</section>

			<?php if (!empty($socialLinks)): ?>
				<section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
					<h2 class="text-lg font-bold text-gray-900">Social</h2>
					<div class="mt-4 space-y-3">
						<?php foreach ($socialConfig as $key => $config): ?>
							<?php if (empty($config['enabled']) || empty($socialLinks[$key])) {
								continue;
							} ?>
							<a href="<?php echo htmlspecialchars($socialLinks[$key]); ?>" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between rounded-2xl border border-gray-200 px-4 py-3 transition hover:border-green-300 hover:bg-green-50">
								<span class="flex items-center gap-3">
									<span class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-900 text-white">
										<i class="fa-brands <?php echo htmlspecialchars($config['icon']); ?>"></i>
									</span>
									<span class="font-semibold text-gray-900"><?php echo htmlspecialchars($config['label']); ?></span>
								</span>
								<i class="fa-solid fa-arrow-up-right-from-square text-gray-400"></i>
							</a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>
		</aside>

		<section class="space-y-6">
			<article id="bio" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
				<div class="flex items-center justify-between gap-4">
					<h2 class="text-2xl font-black tracking-tight text-gray-950">Bio</h2>
					<span class="rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-800">Community profile</span>
				</div>

				<div class="mt-4">
					<?php if ($settings['show_bio'] ?? false): ?>
						<?php if (!empty($bio)): ?>
							<div class="prose max-w-none prose-gray leading-relaxed break-words" style="overflow-wrap:anywhere; word-break:break-word;">
								<?php echo nl2br(htmlspecialchars($bio, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')); ?>
							</div>
						<?php else: ?>
							<p class="text-gray-500">Questo profilo non ha ancora inserito una bio.</p>
						<?php endif; ?>
					<?php else: ?>
						<p class="text-gray-500">La bio è stata nascosta dal profilo.</p>
					<?php endif; ?>
				</div>
			</article>

<section class="rounded-3xl bg-gradient-to-br from-gray-950 to-gray-800 p-6 text-white shadow-sm ring-1 ring-gray-800">
				<div class="max-w-2xl">
					<h2 class="text-2xl font-black tracking-tight">Contenuti e attività</h2>
					<p class="mt-3 text-white/75">
						Qui trovi i cosplay caricati e gli eventi preferiti di questo profilo.
					</p>
				</div>

				<div class="mt-6 grid gap-6 lg:grid-cols-2">
					<section class="rounded-2xl bg-white/8 p-5 ring-1 ring-white/10">
						<div class="flex items-center justify-between gap-3">
							<h3 class="text-lg font-bold text-white">Cosplay caricati</h3>
							<span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white/75"><?php echo (int) count($publicCosplayItems ?? []); ?></span>
						</div>
						<?php if (!empty($publicCosplayItems)): ?>
							<div class="mt-4 grid gap-3 sm:grid-cols-2">
								<?php foreach (array_slice($publicCosplayItems, 0, 4) as $cosplayItem): ?>
									<?php
									$cosplayLabel = trim((string) ($cosplayItem['custom_name'] ?? ''));
									if ($cosplayLabel === '') {
										$cosplayLabel = trim((string) ($cosplayItem['name_full'] ?? 'Cosplay'));
									}
									$cosplayImage = trim((string) ($cosplayItem['reference_image'] ?? ''));
									if ($cosplayImage === '') {
										$cosplayImage = trim((string) ($cosplayItem['image_large'] ?? ''));
									}
									$cosplayImageUrl = $cosplayImage !== '' ? (preg_match('/^https?:\\/\\//i', $cosplayImage) ? $cosplayImage : ($siteBaseUrl . '/public_assets/' . ltrim($cosplayImage, '/'))) : '';
									?>
									<div class="overflow-hidden rounded-2xl bg-white/10 ring-1 ring-white/10">
										<div class="aspect-[4/3] bg-black/10">
											<?php if (!empty($cosplayImageUrl)): ?>
												<img src="<?php echo htmlspecialchars($cosplayImageUrl); ?>" alt="<?php echo htmlspecialchars($cosplayLabel); ?>" class="h-full w-full object-cover">
											<?php endif; ?>
										</div>
										<div class="p-3">
											<p class="text-sm font-semibold text-white"><?php echo htmlspecialchars($cosplayLabel); ?></p>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php else: ?>
							<p class="mt-4 text-sm text-white/70">Nessun cosplay pubblico mostrabile al momento.</p>
						<?php endif; ?>
					</section>

					<section class="rounded-2xl bg-white/8 p-5 ring-1 ring-white/10">
						<div class="flex items-center justify-between gap-3">
							<h3 class="text-lg font-bold text-white">Eventi preferiti</h3>
							<span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white/75"><?php echo (int) count($favoriteEvents ?? []); ?></span>
						</div>
						<?php if (!empty($favoriteEvents)): ?>
							<div class="mt-4 space-y-3">
								<?php foreach ($favoriteEvents as $favoriteEvent): ?>
									<?php
									$favoriteUrl = $siteBaseUrl . '/eventi-cosplay/' . rawurlencode((string) ($favoriteEvent['slug'] ?? ''));
									$favoriteDate = !empty($favoriteEvent['data_inizio']) ? date('d/m/Y', strtotime($favoriteEvent['data_inizio'])) : '';
									?>
									<a href="<?php echo htmlspecialchars($favoriteUrl); ?>" class="block rounded-2xl bg-white/10 p-4 ring-1 ring-white/10 transition hover:bg-white/15">
										<p class="font-semibold text-white"><?php echo htmlspecialchars((string) ($favoriteEvent['titolo'] ?? 'Evento cosplay')); ?></p>
										<p class="mt-1 text-sm text-white/70">
											<?php echo htmlspecialchars(trim($favoriteDate . (!empty($favoriteEvent['luogo']) ? ' · ' . $favoriteEvent['luogo'] : ''))); ?>
										</p>
									</a>
								<?php endforeach; ?>
							</div>
						<?php else: ?>
							<p class="mt-4 text-sm text-white/70">Nessun evento preferito mostrabile al momento.</p>
						<?php endif; ?>
					</section>
				</div>
			</section>
		</section>
	</div>
</main>
