<?php
$campaign = $campaign ?? [];
$regioni = $regioni ?? [];
$province = $province ?? [];
$csrf_token = $csrf_token ?? '';

$regionNames = [];
foreach ($regioni as $regione) {
	$regionNames[(string)($regione['slug'] ?? '')] = (string)($regione['nome'] ?? '');
}

$provinceNames = [];
foreach ($province as $provincia) {
	$provinceNames[(string)($provincia['slug'] ?? '')] = (string)($provincia['nome'] ?? '');
}

$campaignStatus = $campaign['status'] ?? 'pending';
if (($campaign['target_type'] ?? 'national') === 'region') {
	$campaignTargetLabel = 'Regione';
} elseif (($campaign['target_type'] ?? 'national') === 'province') {
	$campaignTargetLabel = 'Provincia';
} else {
	$campaignTargetLabel = 'Nazionale';
}
$campaignTargetValueRaw = !empty($campaign['target_value']) ? (string)$campaign['target_value'] : '';
$campaignTargetValue = $campaignTargetValueRaw !== ''
	? (($campaign['target_type'] ?? 'national') === 'region'
		? ($regionNames[$campaignTargetValueRaw] ?? $campaignTargetValueRaw)
		: (($campaign['target_type'] ?? 'national') === 'province'
			? ($provinceNames[$campaignTargetValueRaw] ?? $campaignTargetValueRaw)
			: $campaignTargetValueRaw))
	: '-';
if ($campaignStatus === 'pending_payment') {
	$campaignStatusLabel = 'In attesa di pagamento';
} elseif ($campaignStatus === 'pending_approval') {
	$campaignStatusLabel = 'In attesa di approvazione';
} elseif ($campaignStatus === 'scheduled') {
	$campaignStatusLabel = 'Programmata';
} elseif ($campaignStatus === 'active') {
	$campaignStatusLabel = 'Attiva';
} elseif ($campaignStatus === 'paused') {
	$campaignStatusLabel = 'In pausa';
} elseif ($campaignStatus === 'expired') {
	$campaignStatusLabel = 'Scaduta';
} elseif ($campaignStatus === 'cancelled') {
	$campaignStatusLabel = 'Annullata';
} elseif ($campaignStatus === 'rejected') {
	$campaignStatusLabel = 'Rifiutata';
} else {
	$campaignStatusLabel = ucfirst(str_replace('_', ' ', (string)$campaignStatus));
}
?>
<section class="space-y-6">
	<div>
		<p class="text-sm font-bold uppercase tracking-wide text-green-900">Riepilogo ordine</p>
		<h1 class="text-2xl font-bold text-gray-950">Controlla la campagna prima del pagamento</h1>
		<p class="mt-1 text-gray-600">Verifica posizione, date, creatività e importo. Dopo il pagamento la campagna passerà in approvazione.</p>
	</div>

	<div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-950">
		<p class="font-bold">Nota importante</p>
		<p class="mt-2 leading-relaxed">
			La campagna, una volta creata, resta fissa nei suoi dati commerciali: posizione, periodo e importo non si modificano da qui.
			Se invece vuoi aggiornare immagine, testo o URL di destinazione, puoi intervenire sul banner collegato.
		</p>
		<?php if (!empty($campaign['banner_id'])): ?>
			<a href="/dashboard/ads/banners/edit/<?php echo (int)$campaign['banner_id']; ?>" class="mt-4 inline-flex rounded-lg bg-amber-900 px-4 py-2 text-sm font-bold text-white hover:bg-amber-800">
				Modifica banner associato
			</a>
		<?php endif; ?>
	</div>

	<div class="grid gap-6 lg:grid-cols-[1fr_360px]">
		<div class="space-y-4">
			<article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
				<h2 class="text-lg font-bold text-gray-950">Anteprima</h2>
				<div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
					<?php if (!empty($campaign['image_path'])): ?>
						<img src="<?php echo htmlspecialchars($campaign['image_path']); ?>" alt="<?php echo htmlspecialchars($campaign['banner_title'] ?? 'Banner sponsor'); ?>" class="h-56 w-full object-cover">
					<?php else: ?>
						<div class="p-6">
							<p class="text-sm font-bold uppercase tracking-wide text-green-800">Sponsor</p>
							<h3 class="mt-2 text-2xl font-bold text-gray-950"><?php echo htmlspecialchars($campaign['sponsor_name'] ?: ($campaign['banner_title'] ?? 'Sponsor')); ?></h3>
							<p class="mt-2 text-gray-700"><?php echo htmlspecialchars($campaign['banner_description'] ?? ''); ?></p>
							<span class="mt-4 inline-flex rounded-lg bg-green-700 px-4 py-2 text-sm font-bold text-white">Scopri di più</span>
						</div>
					<?php endif; ?>
				</div>
			</article>

			<article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
				<h2 class="text-lg font-bold text-gray-950">Dettagli campagna</h2>
				<p class="mt-2 text-sm text-gray-600">Questa sezione mostra i dati bloccati dell'ordine. Le modifiche future vanno fatte sul banner associato.</p>
				<dl class="mt-4 grid gap-4 md:grid-cols-2">
					<div>
						<dt class="text-sm font-bold text-gray-500">Posizione</dt>
						<dd class="text-gray-950"><?php echo htmlspecialchars($campaign['position_name'] ?? ''); ?></dd>
					</div>
					<div>
						<dt class="text-sm font-bold text-gray-500">Target</dt>
						<dd class="text-gray-950"><?php echo htmlspecialchars($campaignTargetLabel); ?><?php echo $campaignTargetValue !== '-' ? ' · ' . htmlspecialchars($campaignTargetValue) : ''; ?></dd>
					</div>
					<div>
						<dt class="text-sm font-bold text-gray-500">Periodo</dt>
						<dd class="text-gray-950"><?php echo date('d/m/Y', strtotime($campaign['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($campaign['end_date'])); ?></dd>
					</div>
					<div>
						<dt class="text-sm font-bold text-gray-500">URL</dt>
						<dd class="break-all text-gray-950"><?php echo htmlspecialchars($campaign['target_url'] ?? ''); ?></dd>
					</div>
					<div>
						<dt class="text-sm font-bold text-gray-500">Stato</dt>
						<dd class="text-gray-950"><?php echo htmlspecialchars($campaignStatusLabel); ?></dd>
					</div>
				</dl>
				<?php if (!empty($campaign['banner_id'])): ?>
					<div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4">
						<p class="text-sm font-bold text-gray-900">Banner collegato</p>
						<p class="mt-1 text-sm text-gray-600">Puoi aggiornare creatività, contenuti e link senza toccare i dati della campagna.</p>
						<a href="/dashboard/ads/banners/edit/<?php echo (int)$campaign['banner_id']; ?>" class="mt-3 inline-flex rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-800 hover:bg-white">
							Apri modifica banner
						</a>
					</div>
				<?php endif; ?>
			</article>
		</div>

		<aside class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:sticky lg:top-6 lg:self-start">
			<h2 class="text-lg font-bold text-gray-950">Totale ordine</h2>
			<div class="mt-4 space-y-3 text-sm">
				<div class="flex justify-between">
					<span class="text-gray-600">Spazio pubblicitario</span>
					<span class="font-bold"><?php echo number_format((float)$campaign['price'], 2, ',', '.'); ?> €</span>
				</div>
				<div class="flex justify-between">
					<span class="text-gray-600">IVA</span>
					<span class="font-bold">Da gestire in fatturazione</span>
				</div>
				<div class="border-t border-gray-200 pt-3">
					<div class="flex justify-between text-lg font-bold">
						<span>Totale</span>
						<span><?php echo number_format((float)$campaign['price'], 2, ',', '.'); ?> €</span>
					</div>
				</div>
			</div>

			<form action="/dashboard/ads/campaigns/<?php echo (int)$campaign['id']; ?>/checkout" method="POST" class="mt-5">
				<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
				<button class="w-full rounded-lg bg-green-700 px-5 py-3 text-sm font-bold text-white hover:bg-green-800">Procedi al pagamento</button>
			</form>
			<form action="/dashboard/ads/campaigns/<?php echo (int)$campaign['id']; ?>/cancel" method="POST" class="mt-3">
				<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
				<button class="w-full rounded-lg border border-gray-300 px-5 py-3 text-sm font-bold text-gray-800 hover:bg-gray-50">Annulla campagna</button>
			</form>
		</aside>
	</div>
</section>
