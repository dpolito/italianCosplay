<?php
$campaign = $campaign ?? [];
$stats = $stats ?? [];
$payment = $payment ?? null;
$csrf_token = $csrf_token ?? '';

$campaignStatus = $campaign['approval_status'] ?? $campaign['status'] ?? 'pending';
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
} elseif ($campaignStatus === 'changes_requested') {
	$campaignStatusLabel = 'Modifiche richieste';
} else {
	$campaignStatusLabel = ucfirst(str_replace('_', ' ', (string)$campaignStatus));
}

$paymentStatus = $payment['status'] ?? 'missing';
if ($paymentStatus === 'pending') {
	$paymentStatusLabel = 'In attesa di pagamento';
	$paymentStatusClass = 'bg-amber-50 text-amber-900 ring-amber-200';
} elseif ($paymentStatus === 'paid') {
	$paymentStatusLabel = 'Pagato';
	$paymentStatusClass = 'bg-green-50 text-green-900 ring-green-200';
} elseif ($paymentStatus === 'failed') {
	$paymentStatusLabel = 'Fallito';
	$paymentStatusClass = 'bg-red-50 text-red-900 ring-red-200';
} elseif ($paymentStatus === 'refunded') {
	$paymentStatusLabel = 'Rimborsato';
	$paymentStatusClass = 'bg-blue-50 text-blue-900 ring-blue-200';
} else {
	$paymentStatusLabel = 'Nessun pagamento registrato';
	$paymentStatusClass = 'bg-gray-50 text-gray-700 ring-gray-200';
}

$paymentMethodLabels = [
	'adyen' => 'Adyen',
	'stripe' => 'Stripe',
	'paypal' => 'PayPal',
];
$paymentMethodLabel = $paymentMethodLabels[strtolower((string) ($payment['provider'] ?? ''))] ?? ucfirst((string) ($payment['provider'] ?? ''));
?>
<div class="container mx-auto p-6 space-y-6">
	<div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
		<div>
			<a href="/admin/ads/campaigns" class="inline-flex items-center text-sm font-semibold text-green-700 hover:underline">
				<svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
				</svg>
				Torna alle campagne
			</a>
			<h1 class="mt-2 text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($campaign['banner_title'] ?? 'Campagna', ENT_QUOTES, 'UTF-8'); ?></h1>
			<p class="mt-1 text-gray-600">Dettaglio e moderazione della campagna pubblicitaria.</p>
		</div>
		<a href="/admin/ads/positions" class="inline-flex items-center px-4 py-2 bg-blue-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">Posizioni</a>
	</div>
	<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
		<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
			<div class="mb-5 grid gap-3 sm:grid-cols-3">
				<div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Impression</p><p class="mt-1 text-2xl font-bold text-slate-950"><?php echo number_format((int)($stats['impressions'] ?? 0), 0, ',', '.'); ?></p></div>
				<div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Click</p><p class="mt-1 text-2xl font-bold text-slate-950"><?php echo number_format((int)($stats['clicks'] ?? 0), 0, ',', '.'); ?></p></div>
				<div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">CTR</p><p class="mt-1 text-2xl font-bold text-slate-950"><?php echo number_format((float)($stats['ctr'] ?? 0), 2, ',', '.'); ?>%</p></div>
			</div>
			<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
				<p class="font-bold">Campagna fissa, banner modificabile</p>
				<p class="mt-2 leading-relaxed">
					I dati dell'ordine non vanno cambiati da qui. Se serve un aggiornamento creativo, intervieni sul banner collegato.
				</p>
				<?php if (!empty($campaign['banner_id'])): ?>
					<a href="/admin/ads/banners/<?php echo (int)$campaign['banner_id']; ?>/edit" class="mt-3 inline-flex rounded-lg bg-amber-900 px-4 py-2 text-sm font-bold text-white hover:bg-amber-800">
						Apri banner associato
					</a>
				<?php endif; ?>
			</div>
			<div class="grid gap-4 md:grid-cols-2">
				<div>
					<p class="text-sm font-bold text-gray-500">Utente</p>
					<p class="mt-1 text-lg font-semibold text-gray-950">
						<?php if (!empty($campaign['user_id'])): ?>
							<a href="/admin/users/edit/<?php echo (int)$campaign['user_id']; ?>" class="text-green-700 hover:underline">
								<?php echo htmlspecialchars($campaign['username'] ?? 'N/D', ENT_QUOTES, 'UTF-8'); ?>
							</a>
						<?php else: ?>
							<?php echo htmlspecialchars($campaign['username'] ?? 'N/D', ENT_QUOTES, 'UTF-8'); ?>
						<?php endif; ?>
					</p>
				</div>
				<div><p class="text-sm font-bold text-gray-500">Posizione</p><p class="mt-1 text-lg font-semibold text-gray-950"><?php echo htmlspecialchars($campaign['position_name'] ?? 'N/D', ENT_QUOTES, 'UTF-8'); ?></p></div>
				<div><p class="text-sm font-bold text-gray-500">Periodo</p><p class="mt-1 text-gray-950"><?php echo !empty($campaign['start_date']) ? date('d/m/Y', strtotime($campaign['start_date'])) : '-'; ?> - <?php echo !empty($campaign['end_date']) ? date('d/m/Y', strtotime($campaign['end_date'])) : '-'; ?></p></div>
				<div><p class="text-sm font-bold text-gray-500">Stato</p><p class="mt-1 text-gray-950"><?php echo htmlspecialchars($campaignStatusLabel, ENT_QUOTES, 'UTF-8'); ?></p></div>
			</div>
			<div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4">
				<div class="flex flex-wrap items-center justify-between gap-3">
					<p class="text-sm font-bold text-gray-900">Pagamento campagna</p>
					<span class="rounded-full px-3 py-1 text-xs font-bold ring-1 <?php echo htmlspecialchars($paymentStatusClass, ENT_QUOTES, 'UTF-8'); ?>">
						<?php echo htmlspecialchars($paymentStatusLabel, ENT_QUOTES, 'UTF-8'); ?>
					</span>
				</div>
				<div class="mt-3 grid gap-3 md:grid-cols-3">
					<div>
						<p class="text-xs font-bold uppercase tracking-wide text-gray-500">Metodo</p>
						<p class="mt-1 font-semibold text-gray-900">
							<?php echo htmlspecialchars($paymentMethodLabel !== '' ? $paymentMethodLabel : 'N/D', ENT_QUOTES, 'UTF-8'); ?>
						</p>
					</div>
					<div>
						<p class="text-xs font-bold uppercase tracking-wide text-gray-500">Importo</p>
						<p class="mt-1 font-semibold text-gray-900">
							<?php if (!empty($payment['amount'])): ?>
								<?php echo number_format((float) $payment['amount'], 2, ',', '.'); ?> <?php echo htmlspecialchars($payment['currency'] ?? 'EUR', ENT_QUOTES, 'UTF-8'); ?>
							<?php else: ?>
								-
							<?php endif; ?>
						</p>
					</div>
					<div>
						<p class="text-xs font-bold uppercase tracking-wide text-gray-500">Data pagamento</p>
						<p class="mt-1 font-semibold text-gray-900">
							<?php echo !empty($payment['paid_at']) ? date('d/m/Y H:i', strtotime($payment['paid_at'])) : '-'; ?>
						</p>
					</div>
				</div>
				<?php if (!empty($payment['provider_reference'])): ?>
					<p class="mt-3 text-sm text-gray-600">
						Ref. provider: <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($payment['provider_reference'], ENT_QUOTES, 'UTF-8'); ?></span>
					</p>
				<?php endif; ?>
				<?php if (empty($payment)): ?>
					<p class="mt-3 text-sm text-gray-600">Questa campagna non ha ancora un pagamento collegato.</p>
				<?php endif; ?>
			</div>
			<?php if (!empty($campaign['banner_description'])): ?><div class="mt-5"><p class="text-sm font-bold text-gray-500">Descrizione</p><p class="mt-2 leading-relaxed text-gray-700"><?php echo htmlspecialchars($campaign['banner_description'], ENT_QUOTES, 'UTF-8'); ?></p></div><?php endif; ?>
			<?php if (!empty($campaign['banner_id'])): ?><div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4"><p class="text-sm font-bold text-gray-900">Istruzioni</p><p class="mt-1 text-sm text-gray-600">La campagna è bloccata; se vuoi correggere creatività, destinazione o testo del banner, usa la modifica banner in admin.</p></div><?php endif; ?>
			<?php if (!empty($campaign['image_path'])): ?><div class="mt-5"><img src="<?php echo htmlspecialchars($campaign['image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($campaign['banner_title'] ?? 'Banner', ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-xl object-cover"></div><?php endif; ?>
		</section>
		<aside class="space-y-4">
			<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
				<h2 class="text-lg font-bold text-gray-950">Azioni moderazione</h2>
				<form action="/admin/ads/campaigns/<?php echo (int)$campaign['id']; ?>/approve" method="POST" class="mt-4 space-y-3">
					<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
					<textarea name="admin_notes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none" placeholder="Note amministrative"></textarea>
					<button class="w-full rounded-lg bg-green-700 px-4 py-3 text-sm font-bold text-white hover:bg-green-800">Approva</button>
				</form>
				<form action="/admin/ads/campaigns/<?php echo (int)$campaign['id']; ?>/reject" method="POST" class="mt-3 space-y-3">
					<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
					<textarea name="admin_notes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none" placeholder="Motivo rifiuto"></textarea>
					<button class="w-full rounded-lg bg-red-600 px-4 py-3 text-sm font-bold text-white hover:bg-red-700">Rifiuta</button>
				</form>
				<form action="/admin/ads/campaigns/<?php echo (int)$campaign['id']; ?>/request-changes" method="POST" class="mt-3 space-y-3">
					<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
					<textarea name="admin_notes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none" placeholder="Cosa correggere"></textarea>
					<button class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm font-bold text-gray-800 hover:bg-gray-50">Richiedi modifiche</button>
				</form>
			</div>
			<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
				<h2 class="text-lg font-bold text-gray-950">Riepilogo economico</h2>
				<p class="mt-2 text-sm text-gray-600">Prezzo: <span class="font-bold text-gray-950"><?php echo number_format((float)($campaign['price'] ?? 0), 2, ',', '.'); ?> €</span></p>
				<p class="mt-1 text-sm text-gray-600">Valuta: <?php echo htmlspecialchars($campaign['currency'] ?? 'EUR', ENT_QUOTES, 'UTF-8'); ?></p>
			</div>
		</aside>
	</div>
</div>
