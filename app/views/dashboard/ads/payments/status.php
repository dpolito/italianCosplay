<?php
$campaign = $campaign ?? [];
$payment = $payment ?? [];

$paymentStatus = $payment['status'] ?? 'pending';
if ($paymentStatus === 'pending') {
	$paymentStatusLabel = 'In attesa di pagamento';
} elseif ($paymentStatus === 'paid') {
	$paymentStatusLabel = 'Pagato';
} elseif ($paymentStatus === 'failed') {
	$paymentStatusLabel = 'Fallito';
} elseif ($paymentStatus === 'refunded') {
	$paymentStatusLabel = 'Rimborsato';
} else {
	$paymentStatusLabel = ucfirst(str_replace('_', ' ', (string)$paymentStatus));
}
?>
<section class="space-y-6">
	<div>
		<p class="text-sm font-bold uppercase tracking-wide text-green-900">Pagamenti Advertising</p>
		<h1 class="text-2xl font-bold text-gray-950">Stato pagamento</h1>
		<p class="mt-1 text-gray-600">Qui vedi il pagamento collegato alla campagna selezionata.</p>
	</div>

	<div class="grid gap-4 md:grid-cols-2">
		<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
			<p class="text-sm font-bold text-gray-500">Campagna</p>
			<p class="mt-2 text-lg font-semibold text-gray-950"><?php echo htmlspecialchars($campaign['banner_title'] ?? 'Campagna', ENT_QUOTES, 'UTF-8'); ?></p>
			<p class="mt-1 text-sm text-gray-600"><?php echo htmlspecialchars($campaign['position_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
		</div>
		<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
			<p class="text-sm font-bold text-gray-500">Stato</p>
			<p class="mt-2 text-lg font-semibold text-gray-950"><?php echo htmlspecialchars($paymentStatusLabel, ENT_QUOTES, 'UTF-8'); ?></p>
			<p class="mt-1 text-sm text-gray-600">ID pagamento: <?php echo (int)($payment['id'] ?? 0); ?></p>
		</div>
		<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
			<p class="text-sm font-bold text-gray-500">Importo</p>
			<p class="mt-2 text-lg font-semibold text-gray-950"><?php echo number_format((float)($payment['amount'] ?? 0), 2, ',', '.'); ?> €</p>
		</div>
		<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
			<p class="text-sm font-bold text-gray-500">Provider</p>
			<p class="mt-2 text-lg font-semibold text-gray-950"><?php echo htmlspecialchars($payment['provider'] ?? 'adyen', ENT_QUOTES, 'UTF-8'); ?></p>
		</div>
	</div>

	<div class="flex flex-wrap gap-3">
		<a href="/dashboard/ads/campaigns/<?php echo (int)($campaign['id'] ?? 0); ?>/review" class="rounded-lg bg-green-700 px-5 py-3 text-sm font-bold text-white hover:bg-green-800">Torna al riepilogo</a>
		<a href="/dashboard/ads/campaigns" class="rounded-lg border border-gray-300 px-5 py-3 text-sm font-bold text-gray-800 hover:bg-gray-50">Tutte le campagne</a>
	</div>
</section>
