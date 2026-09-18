<?php
$data = $data ?? [];
?>
<section class="space-y-6">
	<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
		<div>
			<p class="text-sm font-bold uppercase tracking-wide text-green-900">Advertising</p>
			<h1 class="text-3xl font-bold text-gray-950">Panoramica statistiche</h1>
			<p class="mt-1 text-gray-600">Riepilogo delle tue campagne pubblicitarie.</p>
		</div>
		<div class="flex flex-wrap gap-3">
			<a href="/dashboard/ads/campaigns" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-800 hover:bg-gray-50">Campagne</a>
			<a href="/dashboard/ads/banners" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-800 hover:bg-gray-50">Banner</a>
		</div>
	</div>

	<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
		<div class="rounded-2xl bg-white p-5 shadow-sm">
			<p class="text-sm font-semibold text-gray-500">Campagne</p>
			<p class="mt-2 text-3xl font-bold text-gray-950"><?php echo (int)($data['campaigns'] ?? 0); ?></p>
		</div>
		<div class="rounded-2xl bg-white p-5 shadow-sm">
			<p class="text-sm font-semibold text-gray-500">Attive</p>
			<p class="mt-2 text-3xl font-bold text-gray-950"><?php echo (int)($data['active_campaigns'] ?? 0); ?></p>
		</div>
		<div class="rounded-2xl bg-white p-5 shadow-sm">
			<p class="text-sm font-semibold text-gray-500">Impression</p>
			<p class="mt-2 text-3xl font-bold text-gray-950"><?php echo number_format((int)($data['impressions'] ?? 0), 0, ',', '.'); ?></p>
		</div>
		<div class="rounded-2xl bg-white p-5 shadow-sm">
			<p class="text-sm font-semibold text-gray-500">Click</p>
			<p class="mt-2 text-3xl font-bold text-gray-950"><?php echo number_format((int)($data['clicks'] ?? 0), 0, ',', '.'); ?></p>
		</div>
	</div>
</section>
