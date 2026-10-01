<?php
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="space-y-6">
	<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
		<div>
			<p class="text-sm font-semibold text-green-700">Admin</p>
			<h1 class="text-2xl font-bold text-gray-900">API Management</h1>
		</div>
		<div class="flex gap-3">
			<a href="/admin/api-clients/simulator" class="rounded-md border border-blue-300 px-4 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-50">Simulatore API</a>
			<a href="/admin/api-clients/logs" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">Log API</a>
			<a href="/admin/api-clients/create" class="rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">+ Crea API Client</a>
		</div>
	</div>

	<?php if ($message = \App\Core\Session::getFlash('success')): ?><div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?= $h($message) ?></div><?php endif; ?>
	<?php if ($message = \App\Core\Session::getFlash('error')): ?><div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= $h($message) ?></div><?php endif; ?>

	<div class="grid gap-4 md:grid-cols-4">
		<?php foreach (['today' => 'Oggi', 'last30' => 'Ultimi 30 giorni', 'http401' => '401', 'http429' => '429'] as $key => $label): ?>
			<div class="rounded-md border border-gray-200 bg-white p-4">
				<p class="text-sm font-semibold text-gray-500"><?= $h($label) ?></p>
				<p class="mt-2 text-2xl font-bold text-gray-900"><?= (int) ($summary[$key] ?? 0) ?></p>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="overflow-x-auto rounded-md border border-gray-200 bg-white">
		<table class="min-w-full divide-y divide-gray-200 text-sm">
			<thead class="bg-gray-50 text-left text-xs font-bold uppercase text-gray-500">
				<tr>
					<th class="px-4 py-3">Client</th>
					<th class="px-4 py-3">Stato</th>
					<th class="px-4 py-3">Oggi</th>
					<th class="px-4 py-3">Limite</th>
					<th class="px-4 py-3">Ultimo utilizzo</th>
					<th class="px-4 py-3"></th>
				</tr>
			</thead>
			<tbody class="divide-y divide-gray-100">
				<?php foreach ($clients as $client): ?>
					<tr>
						<td class="px-4 py-3"><div class="font-semibold text-gray-900"><?= $h($client['name']) ?></div><div class="text-xs text-gray-500"><?= $h($client['environment']) ?> · <?= $h($client['key_prefix']) ?></div></td>
						<td class="px-4 py-3"><?= $h($client['status']) ?></td>
						<td class="px-4 py-3"><?= (int) $client['requests_today'] ?></td>
						<td class="px-4 py-3"><?= (int) $client['requests_per_day'] ?>/g</td>
						<td class="px-4 py-3"><?= $h($client['last_used_at'] ?? '-') ?></td>
						<td class="px-4 py-3 text-right"><a class="font-semibold text-green-700 hover:underline" href="/admin/api-clients/<?= (int) $client['id'] ?>">Apri</a></td>
					</tr>
				<?php endforeach; ?>
				<?php if (empty($clients)): ?><tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Nessun client API creato.</td></tr><?php endif; ?>
			</tbody>
		</table>
	</div>

	<div class="rounded-md border border-gray-200 bg-white p-4">
		<h2 class="text-lg font-bold text-gray-900">Endpoint più usati</h2>
		<div class="mt-3 space-y-2">
			<?php foreach ($topEndpoints as $endpoint): ?>
				<div class="flex justify-between gap-4 text-sm"><span class="truncate text-gray-700"><?= $h($endpoint['endpoint']) ?></span><strong><?= (int) $endpoint['request_count'] ?></strong></div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
