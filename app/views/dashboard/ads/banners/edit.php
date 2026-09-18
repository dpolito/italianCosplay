<?php
$banner = $banner ?? [];
$csrf_token = $csrf_token ?? '';
?>
<section class="space-y-6">
	<div>
		<p class="text-sm font-bold uppercase tracking-wide text-green-900">Sponsor</p>
		<h1 class="text-2xl font-bold text-gray-950">Modifica banner</h1>
		<p class="mt-1 text-gray-600">Aggiorna titolo, creatività e link di destinazione del tuo banner.</p>
	</div>

	<form action="/dashboard/ads/banners/update/<?php echo (int)($banner['id'] ?? 0); ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
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
					<span class="mt-1 block text-xs text-gray-500">Se assente, su mobile viene usata l’immagine desktop.</span>
				</label>
				<label class="block">
					<input type="hidden" name="type" value="sponsor">
				</label>
			</div>
		</section>

		<?php if (!empty($banner['image_path'])): ?>
			<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
				<h2 class="text-lg font-bold text-gray-950">Anteprima corrente</h2>
				<img src="<?php echo htmlspecialchars($banner['image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($banner['title'] ?? 'Banner sponsor', ENT_QUOTES, 'UTF-8'); ?>" class="mt-4 max-h-72 w-full rounded-xl object-contain">
			</section>
		<?php endif; ?>
		<?php if (!empty($banner['mobile_image_path'])): ?>
			<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
				<h2 class="text-lg font-bold text-gray-950">Anteprima mobile</h2>
				<img src="<?php echo htmlspecialchars($banner['mobile_image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($banner['title'] ?? 'Banner mobile', ENT_QUOTES, 'UTF-8'); ?>" class="mt-4 max-h-72 w-full rounded-xl object-contain">
			</section>
		<?php endif; ?>

		<div class="flex flex-col gap-3 md:flex-row md:justify-end">
			<a href="/dashboard/ads/banners" class="rounded-lg border border-gray-300 px-5 py-3 text-center text-sm font-bold text-gray-800 hover:bg-gray-50">Annulla</a>
			<button class="rounded-lg bg-green-700 px-5 py-3 text-sm font-bold text-white hover:bg-green-800">Aggiorna banner</button>
		</div>
	</form>
</section>
