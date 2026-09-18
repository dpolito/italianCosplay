<?php
$banners = $banners ?? [];
?>
<section class="space-y-6">
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
	<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
		<div>
			<p class="text-sm font-bold uppercase tracking-wide text-green-900">Sponsor</p>
			<h1 class="text-2xl font-bold text-gray-950">I miei banner</h1>
			<p class="mt-1 text-gray-600">Carica creatività, aggiorna i link di destinazione e tieni sotto controllo i banner attivi.</p>
		</div>
		<a href="/dashboard/ads/banners/create" class="inline-flex items-center justify-center rounded-lg bg-green-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-green-800">
			Nuovo banner
		</a>
	</div>

	<?php if (empty($banners)): ?>
		<div class="rounded-2xl border border-dashed border-green-300 bg-green-50 p-8 text-center">
			<h2 class="text-lg font-bold text-green-950">Non hai ancora banner</h2>
			<p class="mt-2 text-gray-700">Crea il primo banner per iniziare a vendere visibilità al tuo brand o al tuo evento.</p>
			<a href="/dashboard/ads/banners/create" class="mt-5 inline-flex rounded-lg bg-green-700 px-5 py-3 text-sm font-bold text-white hover:bg-green-800">Crea banner</a>
		</div>
	<?php else: ?>
		<div class="grid gap-4 lg:grid-cols-2">
			<?php foreach ($banners as $banner): ?>
				<article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
					<div class="grid gap-0 md:grid-cols-[220px_minmax(0,1fr)]">
						<div class="bg-gray-100">
							<img src="<?php echo htmlspecialchars($banner['image_path'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($banner['title'] ?? 'Banner sponsor', ENT_QUOTES, 'UTF-8'); ?>" class="h-full w-full object-cover" loading="lazy">
						</div>
						<div class="p-5">
							<div class="flex flex-wrap items-center gap-2">
								<span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold uppercase text-green-900"><?php echo htmlspecialchars($banner['status'] ?? 'draft', ENT_QUOTES, 'UTF-8'); ?></span>
								<span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700"><?php echo htmlspecialchars($banner['type'] ?? 'sponsor', ENT_QUOTES, 'UTF-8'); ?></span>
							</div>
							<h2 class="mt-3 text-xl font-bold text-gray-950"><?php echo htmlspecialchars($banner['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h2>
							<?php if (!empty($banner['description'])): ?>
								<p class="mt-2 text-sm leading-relaxed text-gray-600"><?php echo htmlspecialchars($banner['description'], ENT_QUOTES, 'UTF-8'); ?></p>
							<?php endif; ?>
							<p class="mt-3 text-sm text-gray-700">
								<strong>URL:</strong>
								<a href="<?php echo htmlspecialchars($banner['target_url'] ?? '#', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="text-green-700 hover:underline">
									<?php echo htmlspecialchars($banner['target_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
								</a>
							</p>
							<?php if (!empty($banner['created_at'])): ?>
								<p class="mt-2 text-xs text-gray-500">Creato il <?php echo date('d/m/Y H:i', strtotime($banner['created_at'])); ?></p>
							<?php endif; ?>
							<div class="mt-5 flex flex-wrap gap-3">
								<a href="/dashboard/ads/banners/edit/<?php echo (int)$banner['id']; ?>" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-800 hover:bg-gray-50">Modifica</a>
								<form action="/dashboard/ads/banners/delete/<?php echo (int)$banner['id']; ?>" method="POST" onsubmit="return confirm('Vuoi eliminare questo banner?');">
									<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
									<button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white hover:bg-red-700">Elimina</button>
								</form>
							</div>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
