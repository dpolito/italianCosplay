<?php
$position = $position ?? [];
$prices = $prices ?? [];
$action = $action ?? '';
$csrf_token = $csrf_token ?? '';
?>
<div class="container mx-auto p-6 space-y-6">
	<div class="flex justify-between items-center mb-6">
		<a href="/admin/ads/positions" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
			</svg>
			Torna alle posizioni
		</a>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-2"><?php echo !empty($position['id']) ? 'Modifica posizione' : 'Nuova posizione'; ?></h1>
	<p class="text-gray-600 mb-6">Configura spazi, dimensioni e prezzi per la vendita banner.</p>

	<?php
	if (isset($_SESSION['flash_messages'])) {
		foreach ($_SESSION['flash_messages'] as $type => $message) {
			echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
		}
		unset($_SESSION['flash_messages']);
	}
	?>

	<form action="<?php echo htmlspecialchars($action, ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="space-y-6">
		<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
		<div class="grid gap-6 lg:grid-cols-2">
			<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm space-y-4">
				<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
					<p class="font-semibold">Banner unico responsive</p>
					<p class="mt-1">
						Carichi un solo file banner. Le dimensioni desktop e mobile servono solo come riferimento di resa
						e aiutano a definire come la creatività si adatta nei diversi dispositivi.
					</p>
				</div>
				<div class="grid gap-4 md:grid-cols-2">
					<label class="block"><span class="text-sm font-bold text-gray-800">Nome</span><input name="name" required value="<?php echo htmlspecialchars($position['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3"></label>
					<label class="block"><span class="text-sm font-bold text-gray-800">Codice</span><input name="code" required value="<?php echo htmlspecialchars($position['code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3" placeholder="homepage_top"></label>
					<label class="block md:col-span-2"><span class="text-sm font-bold text-gray-800">Pagina</span><input name="page" required value="<?php echo htmlspecialchars($position['page'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3" placeholder="homepage, eventi, blog"></label>
					<label class="block md:col-span-2"><span class="text-sm font-bold text-gray-800">Descrizione</span><textarea name="description" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3"><?php echo htmlspecialchars($position['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></label>
				</div>
			</section>
			<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm space-y-4">
				<p class="text-sm text-gray-600">
					Inserisci le dimensioni previste per desktop e mobile della stessa posizione pubblicitaria.
					Non sono richiesti due banner diversi: il sistema gestisce un solo file creativo in modalità responsive.
				</p>
				<div class="grid gap-4 md:grid-cols-2">
					<label class="block"><span class="text-sm font-bold text-gray-800">Desktop - larghezza</span><input name="width" type="number" min="1" value="<?php echo (int)($position['width'] ?? 0); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3"></label>
					<label class="block"><span class="text-sm font-bold text-gray-800">Desktop - altezza</span><input name="height" type="number" min="1" value="<?php echo (int)($position['height'] ?? 0); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3"></label>
					<label class="block"><span class="text-sm font-bold text-gray-800">Mobile - larghezza</span><input name="mobile_width" type="number" min="1" value="<?php echo (int)($position['mobile_width'] ?? 0); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3"></label>
					<label class="block"><span class="text-sm font-bold text-gray-800">Mobile - altezza</span><input name="mobile_height" type="number" min="1" value="<?php echo (int)($position['mobile_height'] ?? 0); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3"></label>
				</div>
			</section>
		</div>
		<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
			<div class="grid gap-4 md:grid-cols-4">
				<label class="block"><span class="text-sm font-bold text-gray-800">Slot max</span><input name="max_slots" type="number" min="1" value="<?php echo (int)($position['max_slots'] ?? 1); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3"></label>
				<label class="block"><span class="text-sm font-bold text-gray-800">Rotazione</span><select name="rotation_type" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3"><option value="random" <?php echo (($position['rotation_type'] ?? '') === 'random') ? 'selected' : ''; ?>>Random</option><option value="weighted" <?php echo (($position['rotation_type'] ?? '') === 'weighted') ? 'selected' : ''; ?>>Pesata</option><option value="fixed" <?php echo (($position['rotation_type'] ?? '') === 'fixed') ? 'selected' : ''; ?>>Fissa</option></select></label>
				<label class="block"><span class="text-sm font-bold text-gray-800">Prezzo base</span><input name="base_price" type="number" step="0.01" min="0" value="<?php echo htmlspecialchars((string)($position['base_price'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3"></label>
				<label class="block"><span class="text-sm font-bold text-gray-800">Ordine</span><input name="sort_order" type="number" value="<?php echo (int)($position['sort_order'] ?? 0); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3"></label>
			</div>
		</section>
		<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
			<h2 class="text-lg font-bold text-gray-950">Prezzi per durata</h2>
			<div class="mt-4 grid gap-4 md:grid-cols-4">
				<?php foreach ([7, 15, 30, 60] as $days): ?>
					<label class="block"><span class="text-sm font-bold text-gray-800"><?php echo $days; ?> giorni</span><input name="price_<?php echo $days; ?>" type="number" step="0.01" min="0" value="<?php echo htmlspecialchars((string)($prices[$days] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3"></label>
				<?php endforeach; ?>
			</div>
		</section>
		<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
			<div class="grid gap-4 md:grid-cols-4">
				<label class="block"><span class="text-sm font-bold text-gray-800">Impression stimate/mese</span><input name="estimated_monthly_impressions" type="number" min="0" value="<?php echo (int)($position['estimated_monthly_impressions'] ?? 0); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3"></label>
				<label class="block"><span class="text-sm font-bold text-gray-800">CTR medio %</span><input name="average_ctr" type="number" step="0.01" min="0" value="<?php echo htmlspecialchars((string)($position['average_ctr'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3"></label>
				<label class="block"><span class="text-sm font-bold text-gray-800">Min giorni</span><input name="min_days" type="number" min="1" value="<?php echo (int)($position['min_days'] ?? 7); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3"></label>
				<label class="block"><span class="text-sm font-bold text-gray-800">Max giorni</span><input name="max_days" type="number" min="1" value="<?php echo (int)($position['max_days'] ?? 60); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3"></label>
			</div>
			<div class="mt-4 flex flex-wrap gap-4">
				<label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-800"><input type="checkbox" name="is_sponsored_rel_required" value="1" <?php echo !empty($position['is_sponsored_rel_required']) ? 'checked' : ''; ?>>rel="sponsored" obbligatorio</label>
				<label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-800"><input type="checkbox" name="is_active" value="1" <?php echo !isset($position['is_active']) || !empty($position['is_active']) ? 'checked' : ''; ?>>Posizione attiva</label>
			</div>
		</section>
		<div class="flex flex-col gap-3 md:flex-row md:justify-end">
			<a href="/admin/ads/positions" class="rounded-lg border border-gray-300 px-5 py-3 text-center text-sm font-bold text-gray-800 hover:bg-gray-50">Annulla</a>
			<button class="rounded-lg bg-green-700 px-5 py-3 text-sm font-bold text-white hover:bg-green-800">Salva posizione</button>
		</div>
	</form>
</div>
