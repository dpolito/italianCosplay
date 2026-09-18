<?php
$campaigns = $campaigns ?? [];
?>
<section class="space-y-6">
	<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
		<div>
			<p class="text-sm font-bold uppercase tracking-wide text-green-900">Sponsor</p>
			<h1 class="text-2xl font-bold text-gray-900">Le mie campagne</h1>
			<p class="mt-1 text-gray-600">Gestisci banner, periodi acquistati e statistiche delle tue sponsorizzazioni.</p>
		</div>
		<a href="/dashboard/ads/campaigns/create" class="inline-flex items-center justify-center rounded-lg bg-green-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-green-800">
			Nuova campagna
		</a>
	</div>

	<?php if (empty($campaigns)): ?>
		<div class="rounded-2xl border border-dashed border-green-300 bg-green-50 p-8 text-center">
			<h2 class="text-lg font-bold text-green-950">Non hai ancora campagne</h2>
			<p class="mt-2 text-gray-700">Scegli una posizione, prenota il periodo e porta il tuo evento o brand davanti alla community.</p>
			<a href="/dashboard/ads/campaigns/create" class="mt-5 inline-flex rounded-lg bg-green-700 px-5 py-3 text-sm font-bold text-white hover:bg-green-800">Compra uno spazio</a>
		</div>
	<?php else: ?>
		<div class="grid gap-4">
			<?php foreach ($campaigns as $campaign): ?>
				<article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
					<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
						<div class="flex gap-4">
							<?php if (!empty($campaign['image_path'])): ?>
								<img src="<?php echo htmlspecialchars($campaign['image_path']); ?>" alt="" class="h-20 w-28 rounded-lg object-cover">
							<?php endif; ?>
							<div>
								<h2 class="text-lg font-bold text-gray-950"><?php echo htmlspecialchars($campaign['banner_title'] ?? 'Campagna sponsor'); ?></h2>
								<p class="text-sm text-gray-600"><?php echo htmlspecialchars($campaign['position_name'] ?? 'Posizione'); ?></p>
								<p class="mt-1 text-sm text-gray-500">
									Dal <?php echo date('d/m/Y', strtotime($campaign['start_date'])); ?> al <?php echo date('d/m/Y', strtotime($campaign['end_date'])); ?>
								</p>
							</div>
						</div>
						<div class="flex flex-wrap items-center gap-3">
							<span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold uppercase text-gray-700"><?php echo htmlspecialchars($campaign['status']); ?></span>
							<span class="text-sm font-bold text-gray-900"><?php echo number_format((float)$campaign['price'], 2, ',', '.'); ?> €</span>
							<a href="/dashboard/ads/campaigns/<?php echo (int)$campaign['id']; ?>/stats" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-800 hover:bg-gray-50">Statistiche</a>
							<a href="/dashboard/ads/campaigns/<?php echo (int)$campaign['id']; ?>/review" class="rounded-lg bg-green-700 px-4 py-2 text-sm font-bold text-white hover:bg-green-800">Dettagli</a>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
