<?php
$banner = $banner ?? [];
$csrf_token = $csrf_token ?? '';
?>
<div class="container mx-auto p-6 space-y-6">
	<div>
		<a href="/admin/ads/campaigns" class="inline-flex items-center text-sm font-semibold text-green-700 hover:underline">
			<svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
			</svg>
			Torna alle campagne
		</a>
		<h1 class="mt-2 text-3xl font-bold text-gray-900">Modifica banner</h1>
		<p class="mt-1 text-gray-600">Aggiorna creatività e URL senza cambiare i dati della campagna collegata.</p>
	</div>

	<?php if ($message = \App\Core\Session::getFlash('error')): ?>
		<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
			<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
		</div>
	<?php endif; ?>
	<?php if ($message = \App\Core\Session::getFlash('success')): ?>
		<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
			<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
		</div>
	<?php endif; ?>

	<form action="/admin/ads/banners/<?php echo (int)($banner['id'] ?? 0); ?>/update" method="POST" enctype="multipart/form-data" class="space-y-6">
		<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

		<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
			<div class="grid gap-4 md:grid-cols-2">
				<label class="block">
					<span class="text-sm font-bold text-gray-800">Titolo</span>
					<input name="title" required value="<?php echo htmlspecialchars($banner['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none">
				</label>
				<label class="block">
					<span class="text-sm font-bold text-gray-800">URL di destinazione</span>
					<input name="target_url" type="url" required value="<?php echo htmlspecialchars($banner['target_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none">
				</label>
				<label class="block">
					<span class="text-sm font-bold text-gray-800">Nuova immagine opzionale</span>
					<input name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3">
				</label>
				<label class="block">
					<span class="text-sm font-bold text-gray-800">Nuova immagine mobile opzionale</span>
					<input name="mobile_image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3">
				</label>
				<div class="block">
					<span class="text-sm font-bold text-gray-800">Tipo</span>
					<input type="hidden" name="type" value="sponsor">
					<p class="mt-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">Sponsor</p>
				</div>
			</div>
		</section>

		<?php if (!empty($banner['image_path'])): ?>
			<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
				<h2 class="text-lg font-bold text-gray-950">Anteprima corrente</h2>
				<img src="<?php echo htmlspecialchars($banner['image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($banner['title'] ?? 'Banner sponsor', ENT_QUOTES, 'UTF-8'); ?>" class="mt-4 max-h-72 w-full rounded-xl object-contain">
				<p class="mt-3 text-xs text-gray-500 break-all">Percorso salvato: <?php echo htmlspecialchars($banner['image_path'], ENT_QUOTES, 'UTF-8'); ?></p>
			</section>
		<?php endif; ?>
		<?php if (!empty($banner['mobile_image_path'])): ?>
			<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
				<h2 class="text-lg font-bold text-gray-950">Anteprima mobile</h2>
				<img src="<?php echo htmlspecialchars($banner['mobile_image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($banner['title'] ?? 'Banner mobile', ENT_QUOTES, 'UTF-8'); ?>" class="mt-4 max-h-72 w-full rounded-xl object-contain">
			</section>
		<?php endif; ?>

		<div class="flex flex-col gap-3 md:flex-row md:justify-end">
			<a href="/admin/ads/campaigns" class="rounded-lg border border-gray-300 px-5 py-3 text-center text-sm font-bold text-gray-800 hover:bg-gray-50">Annulla</a>
			<button class="rounded-lg bg-green-700 px-5 py-3 text-sm font-bold text-white hover:bg-green-800">Aggiorna banner</button>
		</div>
	</form>
</div>
