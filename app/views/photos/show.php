<?php
$h = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$cosplayLabel = static function (array $row): string {
	return (string) ($row['custom_name'] ?: ($row['name_full'] ?? $row['name_native'] ?? 'Cosplay non specificato'));
};
$photoUrl = '/eventi-cosplay/' . rawurlencode((string) $photo['event_slug']) . '/foto/' . (int) $photo['id'];
$shareUrl = rtrim(URL_ROOT_SITE, '/') . $photoUrl;
$shareUrlEncoded = rawurlencode($shareUrl);
$shareText = 'Foto cosplay di ' . (string) ($photo['event_title'] ?? 'ItalianCosplay');
$shareTextEncoded = rawurlencode($shareText);
$successMessage = \App\Core\Session::getFlash('success');
$errorMessage = \App\Core\Session::getFlash('error');
$imageSchema = [
	'@context' => 'https://schema.org',
	'@type' => 'ImageObject',
	'name' => $shareText,
	'description' => 'Foto cosplay caricata da @' . (string) ($photo['uploader_username'] ?? 'utente') . ' per ' . (string) ($photo['event_title'] ?? 'ItalianCosplay') . '.',
	'contentUrl' => rtrim(URL_ROOT_SITE, '/') . (string) $photo['url'],
	'thumbnailUrl' => rtrim(URL_ROOT_SITE, '/') . (string) $photo['thumbnail_url'],
	'url' => $shareUrl,
	'width' => (int) $photo['width'],
	'height' => (int) $photo['height'],
	'uploadDate' => date('c', strtotime((string) $photo['created_at'])),
	'creditText' => '@' . (string) ($photo['uploader_username'] ?? 'utente'),
	'isPartOf' => [
		'@type' => 'ImageGallery',
		'name' => 'Foto di ' . (string) ($photo['event_title'] ?? 'evento cosplay'),
		'url' => rtrim(URL_ROOT_SITE, '/') . '/eventi-cosplay/' . rawurlencode((string) $photo['event_slug']) . '/foto',
	],
];
$breadcrumbSchema = [
	'@context' => 'https://schema.org',
	'@type' => 'BreadcrumbList',
	'itemListElement' => [
		['@type' => 'ListItem', 'position' => 1, 'name' => 'Eventi cosplay', 'item' => rtrim(URL_ROOT_SITE, '/') . '/eventi-cosplay'],
		['@type' => 'ListItem', 'position' => 2, 'name' => (string) $photo['event_title'], 'item' => rtrim(URL_ROOT_SITE, '/') . '/eventi-cosplay/' . rawurlencode((string) $photo['event_slug'])],
		['@type' => 'ListItem', 'position' => 3, 'name' => 'Foto', 'item' => rtrim(URL_ROOT_SITE, '/') . '/eventi-cosplay/' . rawurlencode((string) $photo['event_slug']) . '/foto'],
		['@type' => 'ListItem', 'position' => 4, 'name' => 'Foto #' . (int) $photo['id'], 'item' => $shareUrl],
	],
];
?>
<script type="application/ld+json"><?= json_encode($imageSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script type="application/ld+json"><?= json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<main class="container mx-auto px-4 py-8">
	<nav class="mb-4 text-sm text-gray-600" aria-label="Breadcrumb">
		<a href="/eventi-cosplay" class="hover:text-green-800">Eventi cosplay</a>
		<span class="mx-2">/</span>
		<a href="/eventi-cosplay/<?= $h($photo['event_slug']) ?>" class="hover:text-green-800"><?= $h($photo['event_title']) ?></a>
		<span class="mx-2">/</span>
		<a href="/eventi-cosplay/<?= $h($photo['event_slug']) ?>/foto" class="hover:text-green-800">Foto</a>
		<span class="mx-2">/</span>
		<span>Foto #<?= (int) $photo['id'] ?></span>
	</nav>

	<?php if ($successMessage): ?>
		<div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 text-sm font-semibold text-green-900" role="status"><?= $h($successMessage) ?></div>
	<?php endif; ?>
	<?php if ($errorMessage): ?>
		<div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-900" role="alert"><?= $h($errorMessage) ?></div>
	<?php endif; ?>

	<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
		<section class="overflow-hidden rounded-xl bg-black">
			<img src="<?= $h($photo['url']) ?>" alt="Foto cosplay di <?= $h($photo['event_title']) ?>" width="<?= (int) $photo['width'] ?>" height="<?= (int) $photo['height'] ?>" class="mx-auto max-h-[78vh] w-auto max-w-full object-contain">
		</section>

		<aside class="space-y-5">
			<section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
				<h1 class="text-xl font-bold text-gray-900"><?= $h($photo['event_title']) ?></h1>
				<p class="mt-2 text-sm text-gray-600">Caricata da @<?= $h($photo['uploader_username']) ?></p>
				<p class="text-sm text-gray-600"><?= $h(date('d/m/Y H:i', strtotime((string) $photo['created_at']))) ?></p>
				<div class="mt-4 flex flex-wrap gap-2">
					<form method="post" action="<?= $h($photoUrl) ?>/sono-io">
						<input type="hidden" name="csrf_token" value="<?= $h($csrf_token) ?>">
						<?php if ($userCosplays): ?>
							<select name="cosplay_id" class="mb-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
								<option value="">Cosplay non specificato</option>
								<?php foreach ($userCosplays as $cosplay): ?>
									<option value="<?= (int) $cosplay['id'] ?>"><?= $h($cosplay['custom_name'] ?: ($cosplay['name_full'] ?? $cosplay['name_native'] ?? 'Cosplay')) ?></option>
								<?php endforeach; ?>
							</select>
						<?php endif; ?>
						<button class="rounded-lg bg-green-800 px-4 py-2 font-semibold text-white hover:bg-green-700">Sono io</button>
					</form>
				</div>
			</section>

			<section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm" aria-labelledby="condividi-foto">
				<h2 id="condividi-foto" class="mb-4 text-xl font-bold text-gray-900">Condividi foto</h2>
				<div class="grid grid-cols-2 gap-3 text-sm">
					<a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrlEncoded ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 font-semibold text-gray-800 hover:border-green-800 hover:bg-green-50" aria-label="Condividi su Facebook">
						<i class="fa-brands fa-facebook-f text-blue-700" aria-hidden="true"></i>
						<span>Facebook</span>
					</a>
					<a href="https://api.whatsapp.com/send?text=<?= $shareTextEncoded ?>%20<?= $shareUrlEncoded ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 font-semibold text-gray-800 hover:border-green-800 hover:bg-green-50" aria-label="Condividi su WhatsApp">
						<i class="fa-brands fa-whatsapp text-green-700" aria-hidden="true"></i>
						<span>WhatsApp</span>
					</a>
					<a href="https://t.me/share/url?url=<?= $shareUrlEncoded ?>&text=<?= $shareTextEncoded ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 font-semibold text-gray-800 hover:border-green-800 hover:bg-green-50" aria-label="Condividi su Telegram">
						<i class="fa-brands fa-telegram text-sky-600" aria-hidden="true"></i>
						<span>Telegram</span>
					</a>
					<a href="https://twitter.com/intent/tweet?url=<?= $shareUrlEncoded ?>&text=<?= $shareTextEncoded ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 font-semibold text-gray-800 hover:border-green-800 hover:bg-green-50" aria-label="Condividi su X">
						<i class="fa-brands fa-x-twitter text-gray-900" aria-hidden="true"></i>
						<span>X</span>
					</a>
					<a href="mailto:?subject=<?= $shareTextEncoded ?>&body=<?= $shareTextEncoded ?>%0A<?= $shareUrlEncoded ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 font-semibold text-gray-800 hover:border-green-800 hover:bg-green-50" aria-label="Condividi via email">
						<i class="fa-solid fa-envelope text-red-700" aria-hidden="true"></i>
						<span>Email</span>
					</a>
					<button type="button" class="js-copy-photo-link inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 font-semibold text-gray-800 hover:border-green-800 hover:bg-green-50" data-share-url="<?= $h($shareUrl) ?>">
						<i class="fa-solid fa-link text-green-900" aria-hidden="true"></i>
						<span>Copia link</span>
					</button>
				</div>
				<p class="js-copy-photo-feedback mt-3 hidden text-sm font-semibold text-green-900" role="status">Link copiato negli appunti.</p>
			</section>

			<section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
				<h2 class="font-bold text-gray-900">Persone presenti</h2>
				<div class="mt-3 space-y-3">
					<?php foreach ($cosplayers as $row): ?>
						<div class="rounded-lg bg-gray-50 p-3">
							<p class="font-semibold">
								<?php if (!empty($row['username'])): ?>
									<a href="/u/<?= $h($row['username']) ?>" class="text-green-900 hover:underline">@<?= $h($row['username']) ?></a>
								<?php else: ?>
									<?= $h($row['display_name'] ?: 'Cosplayer') ?>
								<?php endif; ?>
							</p>
							<?php if (!empty($row['cosplay_id'])): ?><p class="text-sm text-gray-600">Cosplay: <?= $h($cosplayLabel($row)) ?></p><?php endif; ?>
							<?php if (!empty($row['instagram_username'])): ?><p class="text-sm text-gray-600">Instagram: <a class="text-green-900 hover:underline" href="https://www.instagram.com/<?= $h(ltrim((string) $row['instagram_username'], '@')) ?>/" target="_blank" rel="noopener noreferrer">@<?= $h(ltrim((string) $row['instagram_username'], '@')) ?></a></p><?php endif; ?>
						</div>
					<?php endforeach; ?>
					<?php if (!$cosplayers): ?><p class="text-sm text-gray-600">Nessun cosplayer associato.</p><?php endif; ?>
				</div>
			</section>

			<section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
				<h2 class="font-bold text-gray-900">Segnala foto</h2>
				<form method="post" action="<?= $h($photoUrl) ?>/segnala" class="mt-3 space-y-3">
					<input type="hidden" name="csrf_token" value="<?= $h($csrf_token) ?>">
					<input name="reason" maxlength="80" placeholder="Motivo" class="w-full rounded-lg border border-gray-300 px-3 py-2">
					<textarea name="message" maxlength="500" rows="3" placeholder="Dettagli opzionali" class="w-full rounded-lg border border-gray-300 px-3 py-2"></textarea>
					<button class="rounded-lg border border-red-700 px-4 py-2 font-semibold text-red-700 hover:bg-red-50">Invia segnalazione</button>
				</form>
			</section>
		</aside>
	</div>
</main>
<script>
document.querySelectorAll('.js-copy-photo-link').forEach((button) => {
	button.addEventListener('click', async () => {
		try {
			await navigator.clipboard.writeText(button.dataset.shareUrl || window.location.href);
			const feedback = button.closest('section')?.querySelector('.js-copy-photo-feedback');
			if (feedback) feedback.classList.remove('hidden');
		} catch (error) {}
	});
});
</script>
