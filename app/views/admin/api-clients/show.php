<?php
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="space-y-6">
	<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
		<div>
			<a href="/admin/api-clients" class="text-sm font-semibold text-green-700 hover:underline">Torna ad API Management</a>
			<h1 class="mt-2 text-2xl font-bold text-gray-900"><?= $h($client['name']) ?></h1>
		</div>
		<div class="flex gap-3">
			<a href="/admin/api-clients/simulator?client_id=<?= (int) $client['id'] ?>" class="rounded-md border border-blue-300 px-4 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-50">Simula API</a>
			<a href="/admin/api-clients/<?= (int) $client['id'] ?>/edit" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">Modifica</a>
		</div>
	</div>

	<?php if ($message = \App\Core\Session::getFlash('success')): ?><div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?= $h($message) ?></div><?php endif; ?>
	<?php if ($message = \App\Core\Session::getFlash('error')): ?><div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= $h($message) ?></div><?php endif; ?>

	<?php if (!empty($newApiKey)): ?>
		<div class="rounded-md border border-amber-300 bg-amber-50 p-4">
			<p class="font-bold text-amber-950">Copia questa API key adesso.</p>
			<p class="mt-1 text-sm text-amber-900">Per motivi di sicurezza non sarà più possibile visualizzarla.</p>
			<div class="mt-3 flex gap-2">
				<input id="api-key-once" readonly value="<?= $h($newApiKey) ?>" class="w-full rounded-md border border-amber-300 bg-white px-3 py-2 font-mono text-sm">
				<button type="button" data-copy-api-key class="rounded-md bg-amber-700 px-4 py-2 text-sm font-semibold text-white">Copia</button>
			</div>
		</div>
		<script>
			document.querySelector('[data-copy-api-key]')?.addEventListener('click', async function () {
				const input = document.getElementById('api-key-once');
				await navigator.clipboard.writeText(input.value);
				this.textContent = 'Copiata';
			});
		</script>
	<?php endif; ?>

	<div class="grid gap-4 md:grid-cols-3">
		<div class="rounded-md border border-gray-200 bg-white p-4"><p class="text-sm font-semibold text-gray-500">Stato</p><p class="mt-2 font-bold text-gray-900"><?= $h($client['status']) ?></p></div>
		<div class="rounded-md border border-gray-200 bg-white p-4"><p class="text-sm font-semibold text-gray-500">Utilizzo oggi</p><p class="mt-2 font-bold text-gray-900"><?= (int) $usageToday ?> / <?= (int) $client['requests_per_day'] ?></p></div>
		<div class="rounded-md border border-gray-200 bg-white p-4"><p class="text-sm font-semibold text-gray-500">Prefix key</p><p class="mt-2 font-mono text-sm text-gray-900"><?= $h($client['key_prefix']) ?></p></div>
	</div>

	<div class="grid gap-6 lg:grid-cols-2">
		<div class="rounded-md border border-gray-200 bg-white p-5">
			<h2 class="text-lg font-bold text-gray-900">Dettagli</h2>
			<dl class="mt-4 space-y-3 text-sm">
				<div class="flex justify-between gap-4"><dt class="text-gray-500">Environment</dt><dd class="font-semibold"><?= $h($client['environment']) ?></dd></div>
				<div class="flex justify-between gap-4"><dt class="text-gray-500">Creata</dt><dd><?= $h($client['created_at']) ?></dd></div>
				<div class="flex justify-between gap-4"><dt class="text-gray-500">Ultimo utilizzo</dt><dd><?= $h($client['last_used_at'] ?? '-') ?></dd></div>
				<div class="flex justify-between gap-4"><dt class="text-gray-500">Ultimo IP</dt><dd><?= $h($client['last_ip'] ?? '-') ?></dd></div>
				<div class="flex justify-between gap-4"><dt class="text-gray-500">Rate/minuto</dt><dd><?= (int) $client['requests_per_minute'] ?></dd></div>
				<div class="flex justify-between gap-4"><dt class="text-gray-500">Rate/giorno</dt><dd><?= (int) $client['requests_per_day'] ?></dd></div>
				<div class="flex justify-between gap-4"><dt class="text-gray-500">Scadenza</dt><dd><?= $h($client['expires_at'] ?? '-') ?></dd></div>
			</dl>
		</div>
		<div class="rounded-md border border-gray-200 bg-white p-5">
			<h2 class="text-lg font-bold text-gray-900">Scopes</h2>
			<div class="mt-4 flex flex-wrap gap-2">
				<?php foreach ($scopes as $scope): ?><span class="rounded-md bg-gray-100 px-2 py-1 font-mono text-xs text-gray-800"><?= $h($scope) ?></span><?php endforeach; ?>
				<?php if (empty($scopes)): ?><span class="text-sm text-gray-500">Nessuno scope assegnato.</span><?php endif; ?>
			</div>
			<div class="mt-6 space-y-3">
				<form method="post" action="/admin/api-clients/<?= (int) $client['id'] ?>/rotate" onsubmit="return confirm('Ruotare la API key? La precedente non sarà più valida.');">
					<input type="hidden" name="csrf_token" value="<?= $h($csrf_token) ?>">
					<button class="w-full rounded-md bg-blue-700 px-4 py-2 font-semibold text-white hover:bg-blue-800">Ruota API key</button>
				</form>
				<form method="post" action="/admin/api-clients/<?= (int) $client['id'] ?>/status" onsubmit="return confirm('Confermi il cambio stato?');" class="grid grid-cols-3 gap-2">
					<input type="hidden" name="csrf_token" value="<?= $h($csrf_token) ?>">
					<button name="status" value="disabled" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold">Disabilita</button>
					<button name="status" value="active" class="rounded-md border border-green-300 px-3 py-2 text-sm font-semibold text-green-800">Riattiva</button>
					<button name="status" value="revoked" class="rounded-md border border-red-300 px-3 py-2 text-sm font-semibold text-red-800">Revoca</button>
				</form>
			</div>
		</div>
	</div>
</div>
