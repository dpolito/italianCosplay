<?php
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$items = $logs['items'] ?? [];
$totalPages = max(1, (int) ceil(($logs['total'] ?? 0) / max(1, $logs['perPage'] ?? 50)));
?>
<div class="space-y-6">
	<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
		<div>
			<a href="/admin/api-clients" class="text-sm font-semibold text-green-700 hover:underline">Torna ad API Management</a>
			<h1 class="mt-2 text-2xl font-bold text-gray-900">Log API</h1>
		</div>
	</div>
	<div class="grid gap-4 md:grid-cols-5">
		<?php foreach (['today' => 'Oggi', 'last30' => '30 giorni', 'errors' => 'Errori', 'http403' => '403', 'http429' => '429'] as $key => $label): ?>
			<div class="rounded-md border border-gray-200 bg-white p-4"><p class="text-xs font-bold uppercase text-gray-500"><?= $h($label) ?></p><p class="mt-2 text-xl font-bold"><?= (int) ($summary[$key] ?? 0) ?></p></div>
		<?php endforeach; ?>
	</div>
	<form method="get" class="grid gap-3 rounded-md border border-gray-200 bg-white p-4 md:grid-cols-6">
		<select name="client_id" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
			<option value="">Tutti i client</option>
			<?php foreach ($clients as $client): ?><option value="<?= (int) $client['id'] ?>" <?= (string) ($_GET['client_id'] ?? '') === (string) $client['id'] ? 'selected' : '' ?>><?= $h($client['name']) ?></option><?php endforeach; ?>
		</select>
		<input name="endpoint" value="<?= $h($_GET['endpoint'] ?? '') ?>" placeholder="Endpoint" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
		<input name="status_code" value="<?= $h($_GET['status_code'] ?? '') ?>" placeholder="Status" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
		<input type="date" name="from" value="<?= $h($_GET['from'] ?? '') ?>" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
		<input type="date" name="to" value="<?= $h($_GET['to'] ?? '') ?>" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
		<button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white">Filtra</button>
	</form>
	<div class="overflow-x-auto rounded-md border border-gray-200 bg-white">
		<table class="min-w-full divide-y divide-gray-200 text-sm">
			<thead class="bg-gray-50 text-left text-xs font-bold uppercase text-gray-500">
				<tr><th class="px-4 py-3">Ora</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Metodo</th><th class="px-4 py-3">Endpoint</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Tempo</th><th class="px-4 py-3">IP</th></tr>
			</thead>
			<tbody class="divide-y divide-gray-100">
				<?php foreach ($items as $row): ?>
					<tr>
						<td class="px-4 py-3 whitespace-nowrap"><?= $h($row['created_at']) ?></td>
						<td class="px-4 py-3"><?= $h($row['client_name'] ?? 'UNKNOWN') ?></td>
						<td class="px-4 py-3"><?= $h($row['http_method']) ?></td>
						<td class="px-4 py-3 font-mono text-xs"><?= $h($row['endpoint']) ?></td>
						<td class="px-4 py-3"><?= (int) $row['status_code'] ?></td>
						<td class="px-4 py-3"><?= $h($row['response_time_ms'] ?? '-') ?>ms</td>
						<td class="px-4 py-3"><?= $h($row['ip_address'] ?? '-') ?></td>
					</tr>
				<?php endforeach; ?>
				<?php if (empty($items)): ?><tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Nessun log trovato.</td></tr><?php endif; ?>
			</tbody>
		</table>
	</div>
	<p class="text-sm text-gray-600">Pagina <?= (int) $logs['page'] ?> di <?= $totalPages ?>, <?= (int) $logs['total'] ?> record.</p>
</div>
