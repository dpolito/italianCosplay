<?php $h = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>
<main class="container mx-auto px-4 py-8">
	<nav class="mb-4 text-sm text-gray-600">
		<a href="/eventi-cosplay" class="hover:text-green-800">Eventi cosplay</a>
		<span class="mx-2">/</span>
		<a href="/eventi-cosplay/<?= $h($event['slug']) ?>" class="hover:text-green-800"><?= $h($event['titolo']) ?></a>
		<span class="mx-2">/</span>
		<span>Foto</span>
	</nav>

	<header class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
		<div>
			<h1 class="text-3xl font-bold text-gray-900">Foto di <?= $h($event['titolo']) ?></h1>
			<p class="mt-2 text-gray-600"><?= (int) $totalPhotos ?> foto · <?= (int) $uploaderCount ?> utenti hanno pubblicato foto</p>
		</div>
		<?php if ($photos): ?>
			<a href="/dashboard/photos/upload?event_id=<?= (int) $event['id'] ?>" class="inline-flex rounded-lg bg-green-800 px-4 py-2 font-semibold text-white hover:bg-green-700" data-photo-analytics data-event-type="photo_upload_cta_click" data-event-id="<?= (int) $event['id'] ?>" data-source="event_gallery">Carica foto</a>
		<?php endif; ?>
	</header>

	<section class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-5 text-blue-950">
		<h2 class="font-bold">Gallery fotografica evento</h2>
		<p class="mt-2 text-sm leading-6">
			Questa pagina raccoglie le foto pubblicate dagli utenti per <?= $h($event['titolo']) ?>. Puoi sfogliare la gallery, aprire una foto in grande e, se sei presente nell'immagine, usare “Sono io” per richiedere l'associazione al tuo profilo e al cosplay che indossavi.
		</p>
	</section>

	<?php if ($photos): ?>
		<section class="mt-8 grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-6">
			<?php foreach ($photos as $photo): ?>
				<a href="/eventi-cosplay/<?= $h($event['slug']) ?>/foto/<?= (int) $photo['id'] ?>" class="group overflow-hidden rounded-lg bg-gray-100 ring-1 ring-gray-200 focus:outline-none focus:ring-2 focus:ring-green-800" data-photo-analytics data-event-type="photo_open" data-photo-id="<?= (int) $photo['id'] ?>" data-event-id="<?= (int) $photo['event_id'] ?>" data-uploaded-by-user-id="<?= (int) $photo['uploaded_by_user_id'] ?>" data-source="event_gallery">
					<img src="<?= $h($photo['thumbnail_url']) ?>" alt="Foto cosplay di <?= $h($event['titolo']) ?>" width="<?= (int) $photo['thumbnail_width'] ?>" height="<?= (int) $photo['thumbnail_height'] ?>" loading="lazy" class="aspect-square w-full object-cover transition group-hover:scale-105">
				</a>
			<?php endforeach; ?>
		</section>
		<?php if ($totalPages > 1): ?>
			<nav class="mt-8 flex items-center justify-center gap-2">
				<?php if ($page > 1): ?><a class="rounded-lg border px-3 py-2 font-semibold" href="?page=<?= $page - 1 ?>">Precedenti</a><?php endif; ?>
				<span class="px-3 py-2 text-sm text-gray-600">Pagina <?= (int) $page ?> di <?= (int) $totalPages ?></span>
				<?php if ($page < $totalPages): ?><a class="rounded-lg border px-3 py-2 font-semibold" href="?page=<?= $page + 1 ?>">Successive</a><?php endif; ?>
			</nav>
		<?php endif; ?>
	<?php else: ?>
		<section class="mt-8 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center">
			<div class="mx-auto max-w-2xl">
				<p class="text-lg font-bold text-gray-900">Nessuna foto ancora pubblicata per questo evento.</p>
				<p class="mt-3 text-sm leading-6 text-gray-600">
					Se hai partecipato a <?= $h($event['titolo']) ?> puoi aiutare la community caricando le prime immagini. Le foto saranno convertite in WebP, organizzate nella gallery dell'evento e potranno essere associate a cosplayer e cosplay.
				</p>
				<a href="/dashboard/photos/upload?event_id=<?= (int) $event['id'] ?>" class="mt-5 inline-flex rounded-lg bg-green-800 px-4 py-2 font-semibold text-white hover:bg-green-700" data-photo-analytics data-event-type="photo_upload_cta_click" data-event-id="<?= (int) $event['id'] ?>" data-source="event_gallery_empty">Carica le prime foto</a>
			</div>
		</section>
	<?php endif; ?>
</main>
<script>
const trackPhotoAnalytics = (element) => {
	const payload = {
		event_type: element.dataset.eventType || '',
		photo_id: element.dataset.photoId || '',
		event_id: element.dataset.eventId || '',
		uploaded_by_user_id: element.dataset.uploadedByUserId || '',
		source: element.dataset.source || ''
	};
	const body = JSON.stringify(payload);
	if (navigator.sendBeacon) {
		navigator.sendBeacon('/analytics/photo-event', new Blob([body], { type: 'application/json' }));
		return;
	}
	fetch('/analytics/photo-event', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body, keepalive: true }).catch(() => {});
};
document.querySelectorAll('[data-photo-analytics]').forEach((element) => {
	element.addEventListener('click', () => trackPhotoAnalytics(element));
});
</script>
