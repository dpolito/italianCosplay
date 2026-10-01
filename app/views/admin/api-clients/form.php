<?php
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$editing = !empty($client);
$action = $editing ? '/admin/api-clients/' . (int) $client['id'] . '/update' : '/admin/api-clients/store';
?>
<div class="max-w-4xl space-y-6">
	<a href="<?= $editing ? '/admin/api-clients/' . (int) $client['id'] : '/admin/api-clients' ?>" class="text-sm font-semibold text-green-700 hover:underline">Torna ad API Management</a>
	<h1 class="text-2xl font-bold text-gray-900"><?= $editing ? 'Modifica API Client' : 'Crea API Client' ?></h1>
	<?php if ($message = \App\Core\Session::getFlash('error')): ?><div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= $h($message) ?></div><?php endif; ?>
	<form method="post" action="<?= $h($action) ?>" class="space-y-5 rounded-md border border-gray-200 bg-white p-6">
		<input type="hidden" name="csrf_token" value="<?= $h($csrf_token) ?>">
		<div>
			<label class="text-sm font-semibold text-gray-700">Nome</label>
			<input name="name" required maxlength="120" value="<?= $h($client['name'] ?? '') ?>" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
		</div>
		<div>
			<label class="text-sm font-semibold text-gray-700">Descrizione</label>
			<textarea name="description" rows="3" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2"><?= $h($client['description'] ?? '') ?></textarea>
		</div>
		<div class="grid gap-4 md:grid-cols-3">
			<div>
				<label class="text-sm font-semibold text-gray-700">Environment</label>
				<select name="environment" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
					<?php foreach (($config['environments'] ?? ['test', 'live']) as $environment): ?>
						<option value="<?= $h($environment) ?>" <?= ($client['environment'] ?? 'test') === $environment ? 'selected' : '' ?>><?= $h($environment) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label class="text-sm font-semibold text-gray-700">Rate/minuto</label>
				<input type="number" min="1" max="600" name="requests_per_minute" value="<?= (int) ($client['requests_per_minute'] ?? 60) ?>" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
			</div>
			<div>
				<label class="text-sm font-semibold text-gray-700">Rate/giorno</label>
				<input type="number" min="1" max="100000" name="requests_per_day" value="<?= (int) ($client['requests_per_day'] ?? 5000) ?>" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
			</div>
		</div>
		<div>
			<label class="text-sm font-semibold text-gray-700">Scadenza opzionale</label>
			<input type="date" name="expires_at" value="<?= !empty($client['expires_at']) ? $h(substr((string) $client['expires_at'], 0, 10)) : '' ?>" class="mt-1 rounded-md border border-gray-300 px-3 py-2">
		</div>
		<fieldset>
			<legend class="text-sm font-semibold text-gray-700">Scopes</legend>
			<div class="mt-2 grid gap-2 md:grid-cols-2">
				<?php foreach ($availableScopes as $scope => $label): ?>
					<label class="flex items-start gap-2 rounded-md border border-gray-200 px-3 py-2">
						<input type="checkbox" name="scopes[]" value="<?= $h($scope) ?>" <?= in_array($scope, $selectedScopes, true) ? 'checked' : '' ?> class="mt-1">
						<span><span class="block font-mono text-sm text-gray-900"><?= $h($scope) ?></span><span class="text-xs text-gray-500"><?= $h($label) ?></span></span>
					</label>
				<?php endforeach; ?>
			</div>
		</fieldset>
		<div>
			<label class="text-sm font-semibold text-gray-700">Note amministrative</label>
			<textarea name="admin_notes" rows="3" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2"><?= $h($client['admin_notes'] ?? '') ?></textarea>
		</div>
		<button class="rounded-md bg-green-700 px-5 py-2 font-semibold text-white hover:bg-green-800"><?= $editing ? 'Salva modifiche' : 'Crea client' ?></button>
	</form>
</div>
