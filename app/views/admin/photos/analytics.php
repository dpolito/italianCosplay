<?php
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$n = static fn ($value): string => number_format((int) $value, 0, ',', '.');
$overview = $report['overview'] ?? [];
$funnel = $report['funnel'] ?? [];
$filters = $report['filters'] ?? [];
$metric = static fn (array $data, string $key): int => (int) ($data[$key] ?? 0);
$periodUrl = static fn (int $targetDays) => '/admin/photos/analytics?days=' . $targetDays;
$sortUrl = static fn (string $targetSort) => '/admin/photos/analytics?days=' . (int) $days . '&sort=' . rawurlencode($targetSort);
$funnelRows = [
	['label' => 'Area foto vista', 'key' => 'photo_hub_view'],
	['label' => 'Filtri usati', 'key' => 'photo_filter_used'],
	['label' => 'Gallery evento viste', 'key' => 'photo_event_gallery_view'],
	['label' => 'Foto aperte dalla griglia', 'key' => 'photo_open'],
	['label' => 'Dettaglio foto visto', 'key' => 'photo_view'],
	['label' => 'Click profilo uploader', 'key' => 'photo_uploader_profile_click'],
	['label' => 'Click carica foto', 'key' => 'photo_upload_cta_click'],
	['label' => 'Upload riusciti', 'key' => 'photo_upload_success'],
	['label' => 'Sono io', 'key' => 'photo_self_claim'],
];
?>
<main class="space-y-6">
	<div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
		<div>
			<p class="text-sm font-bold uppercase tracking-wide text-green-800">Foto community</p>
			<h1 class="text-2xl font-black text-gray-950">Statistiche foto</h1>
			<p class="mt-1 text-sm text-gray-600">Tracking first-party su visite, aperture, upload, uploader e filtri.</p>
		</div>
		<div class="flex flex-wrap gap-2">
			<?php foreach ([1 => 'Oggi', 7 => '7 giorni', 30 => '30 giorni'] as $value => $label): ?>
				<a href="<?= $h($periodUrl($value)) ?>" class="rounded-lg border px-3 py-2 text-sm font-bold <?= (int) $days === (int) $value ? 'border-green-800 bg-green-800 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' ?>"><?= $h($label) ?></a>
			<?php endforeach; ?>
		</div>
	</div>

	<?php if (!empty($report['error'])): ?>
		<div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm font-semibold text-yellow-900"><?= $h($report['error']) ?></div>
	<?php endif; ?>

	<section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
		<?php foreach ([
			['Visite area Foto', 'hub_views'],
			['Visitatori unici', 'unique_visitors'],
			['Gallery evento viste', 'event_gallery_views'],
			['Foto visualizzate', 'photo_views'],
			['Foto pubblicate', 'photos_published'],
			['Uploader attivi', 'active_uploaders'],
			['Self-claim', 'self_claims'],
			['CTA upload', 'upload_cta_clicks'],
		] as [$label, $key]): ?>
			<article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
				<p class="text-xs font-bold uppercase tracking-wide text-gray-500"><?= $h($label) ?></p>
				<p class="mt-2 text-3xl font-black text-gray-950"><?= $n($metric($overview, $key)) ?></p>
			</article>
		<?php endforeach; ?>
	</section>

	<section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
		<article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
			<h2 class="text-lg font-black text-gray-950">Funnel foto</h2>
			<div class="mt-4 overflow-x-auto">
				<table class="min-w-full text-sm">
					<thead>
						<tr class="border-b text-left text-xs uppercase tracking-wide text-gray-500">
							<th class="py-2 pr-4">Evento</th>
							<th class="py-2 text-right">Totale</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-gray-100">
						<?php foreach ($funnelRows as $row): ?>
							<tr>
								<td class="py-2 pr-4 font-semibold text-gray-800"><?= $h($row['label']) ?></td>
								<td class="py-2 text-right font-black text-gray-950"><?= $n($metric($funnel, $row['key'])) ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</article>

		<article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
			<h2 class="text-lg font-black text-gray-950">Uso filtri</h2>
			<div class="mt-4 space-y-3 text-sm">
				<p class="flex justify-between"><span>Filtri totali</span><strong><?= $n($metric($filters, 'total')) ?></strong></p>
				<p class="flex justify-between"><span>Evento</span><strong><?= $n($metric($filters, 'event_filter_count')) ?></strong></p>
				<p class="flex justify-between"><span>Anno</span><strong><?= $n($metric($filters, 'year_filter_count')) ?></strong></p>
				<p class="flex justify-between"><span>Autore</span><strong><?= $n($metric($filters, 'uploader_filter_count')) ?></strong></p>
			</div>
			<?php if (!empty($report['topFilterEvents'])): ?>
				<div class="mt-5 border-t pt-4">
					<h3 class="text-sm font-bold text-gray-900">Eventi più filtrati</h3>
					<div class="mt-2 space-y-2">
						<?php foreach ($report['topFilterEvents'] as $event): ?>
							<a href="/eventi-cosplay/<?= $h($event['slug']) ?>/foto" class="flex justify-between gap-3 text-sm hover:text-green-800">
								<span class="line-clamp-1"><?= $h($event['titolo']) ?></span>
								<strong><?= $n($event['total']) ?></strong>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</article>
	</section>

	<section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
		<div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
			<h2 class="text-lg font-black text-gray-950">Eventi fotografati</h2>
			<div class="flex flex-wrap gap-2 text-sm">
				<a href="<?= $h($sortUrl('gallery_views')) ?>" class="<?= $sort === 'gallery_views' ? 'font-black text-green-900' : 'font-semibold text-gray-600 hover:text-green-800' ?>">Gallery</a>
				<a href="<?= $h($sortUrl('photo_views')) ?>" class="<?= $sort === 'photo_views' ? 'font-black text-green-900' : 'font-semibold text-gray-600 hover:text-green-800' ?>">Foto viste</a>
				<a href="<?= $h($sortUrl('photos')) ?>" class="<?= $sort === 'photos' ? 'font-black text-green-900' : 'font-semibold text-gray-600 hover:text-green-800' ?>">Foto</a>
			</div>
		</div>
		<div class="mt-4 overflow-x-auto">
			<table class="min-w-full text-sm">
				<thead>
					<tr class="border-b text-left text-xs uppercase tracking-wide text-gray-500">
						<th class="py-2 pr-4">Evento</th>
						<th class="py-2 text-right">Foto</th>
						<th class="py-2 text-right">Autori</th>
						<th class="py-2 text-right">Gallery evento viste</th>
						<th class="py-2 text-right">Foto viste</th>
						<th class="py-2 text-right">Unici</th>
						<th class="py-2 text-right">Sono io</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-gray-100">
					<?php foreach (($report['events'] ?? []) as $event): ?>
						<tr>
							<td class="py-3 pr-4">
								<a href="/eventi-cosplay/<?= $h($event['slug']) ?>/foto" class="font-bold text-gray-950 hover:text-green-800"><?= $h($event['titolo']) ?></a>
								<p class="mt-1 text-xs text-gray-500">Gallery evento viste di <?= $h($event['titolo']) ?>: <?= $n($event['gallery_views']) ?></p>
							</td>
							<td class="py-3 text-right"><?= $n($event['photo_count']) ?></td>
							<td class="py-3 text-right"><?= $n($event['uploader_count']) ?></td>
							<td class="py-3 text-right"><?= $n($event['gallery_views']) ?></td>
							<td class="py-3 text-right"><?= $n($event['photo_views']) ?></td>
							<td class="py-3 text-right"><?= $n($event['unique_visitors']) ?></td>
							<td class="py-3 text-right"><?= $n($event['self_claims']) ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</section>

	<section class="grid gap-6 xl:grid-cols-2">
		<article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
			<h2 class="text-lg font-black text-gray-950">Uploader</h2>
			<div class="mt-4 overflow-x-auto">
				<table class="min-w-full text-sm">
					<thead><tr class="border-b text-left text-xs uppercase tracking-wide text-gray-500"><th class="py-2 pr-4">Autore</th><th class="py-2 text-right">Foto</th><th class="py-2 text-right">Eventi</th><th class="py-2 text-right">View</th><th class="py-2 text-right">Profilo</th></tr></thead>
					<tbody class="divide-y divide-gray-100">
						<?php foreach (($report['uploaders'] ?? []) as $uploader): ?>
							<tr>
								<td class="py-3 pr-4"><a href="/u/<?= $h($uploader['username']) ?>" class="font-bold text-green-900 hover:underline">@<?= $h($uploader['username']) ?></a></td>
								<td class="py-3 text-right"><?= $n($uploader['photos_published']) ?></td>
								<td class="py-3 text-right"><?= $n($uploader['event_count']) ?></td>
								<td class="py-3 text-right"><?= $n($uploader['photo_views']) ?></td>
								<td class="py-3 text-right"><?= $n($uploader['profile_clicks']) ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</article>

		<article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
			<h2 class="text-lg font-black text-gray-950">Top foto</h2>
			<div class="mt-4 space-y-3">
				<?php foreach (($report['topPhotos'] ?? []) as $photo): ?>
					<a href="/eventi-cosplay/<?= $h($photo['event_slug']) ?>/foto/<?= (int) $photo['id'] ?>" class="flex gap-3 rounded-lg border border-gray-100 p-2 hover:border-green-200 hover:bg-green-50">
						<img src="/public_assets/uploads/photos/<?= $h($photo['thumbnail_storage_key']) ?>" alt="<?= $h($photo['event_title']) ?>" class="h-16 w-16 rounded-lg object-cover" loading="lazy">
						<span class="min-w-0 flex-1">
							<strong class="line-clamp-1 text-sm text-gray-950"><?= $h($photo['event_title']) ?></strong>
							<span class="block text-xs text-gray-600">@<?= $h($photo['uploader_username']) ?></span>
							<span class="block text-xs text-gray-500"><?= $n($photo['photo_views']) ?> view · <?= $n($photo['unique_visitors']) ?> unici · <?= $n($photo['self_claims']) ?> sono io</span>
						</span>
					</a>
				<?php endforeach; ?>
				<?php if (empty($report['topPhotos'])): ?><p class="text-sm text-gray-500">Nessuna foto visualizzata nel periodo.</p><?php endif; ?>
			</div>
		</article>
	</section>

	<section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
		<h2 class="text-lg font-black text-gray-950">Trend ultimi 30 giorni</h2>
		<div class="mt-4 overflow-x-auto">
			<table class="min-w-full text-sm">
				<thead><tr class="border-b text-left text-xs uppercase tracking-wide text-gray-500"><th class="py-2 pr-4">Giorno</th><th class="py-2 text-right">Hub</th><th class="py-2 text-right">Gallery</th><th class="py-2 text-right">Foto</th></tr></thead>
				<tbody class="divide-y divide-gray-100">
					<?php foreach (($report['trend'] ?? []) as $row): ?>
						<tr>
							<td class="py-2 pr-4 font-semibold"><?= $h(date('d/m/Y', strtotime((string) $row['day']))) ?></td>
							<td class="py-2 text-right"><?= $n($row['hub_views']) ?></td>
							<td class="py-2 text-right"><?= $n($row['gallery_views']) ?></td>
							<td class="py-2 text-right"><?= $n($row['photo_views']) ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</section>
</main>
