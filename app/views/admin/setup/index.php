<?php
$flags = is_array($flags ?? null) ? $flags : [];
?>

<div class="container mx-auto p-6">
	<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
		<div>
			<p class="text-sm font-semibold uppercase tracking-wide text-green-700">Setup sito</p>
			<h1 class="text-3xl font-semibold text-gray-800">Flag funzionalità</h1>
			<p class="mt-2 text-gray-600">Accendi o spegni rapidamente i moduli principali del sito.</p>
		</div>
		<a href="/admin/dashboard" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			Torna alla Dashboard
		</a>
	</div>

	<?php
	if (isset($_SESSION['flash_messages'])) {
		foreach ($_SESSION['flash_messages'] as $type => $message) {
			echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
		}
		unset($_SESSION['flash_messages']);
	}
	?>

	<div class="rounded-2xl bg-white p-6 shadow-lg">
		<form method="POST" action="/admin/setup" class="space-y-5">
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8'); ?>">

			<div class="grid gap-4 md:grid-cols-2">
				<?php foreach ($flags as $flag): ?>
					<?php
					$flagKey = (string) ($flag['flag_key'] ?? '');
					$isEnabled = (int) ($flag['is_enabled'] ?? 0) === 1;
					?>
					<label class="flex h-full cursor-pointer flex-col rounded-2xl border border-gray-200 bg-gray-50 p-5 transition hover:border-green-300 hover:bg-green-50">
						<div class="flex items-start justify-between gap-4">
							<div>
								<span class="text-base font-bold text-gray-900"><?php echo htmlspecialchars((string) ($flag['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
								<?php if (!empty($flag['description'])): ?>
									<p class="mt-2 text-sm leading-6 text-gray-600"><?php echo htmlspecialchars((string) $flag['description'], ENT_QUOTES, 'UTF-8'); ?></p>
								<?php endif; ?>
							</div>
							<span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?php echo $isEnabled ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'; ?>">
								<?php echo $isEnabled ? 'Attivo' : 'Disattivo'; ?>
							</span>
						</div>
						<div class="mt-4 flex items-center gap-3">
							<input type="checkbox" name="flags[<?php echo htmlspecialchars($flagKey, ENT_QUOTES, 'UTF-8'); ?>]" value="1" <?php echo $isEnabled ? 'checked' : ''; ?> class="h-5 w-5 rounded border-gray-300 text-green-800 focus:ring-green-300">
							<span class="text-sm font-medium text-gray-700">Abilita questa funzione</span>
						</div>
					</label>
				<?php endforeach; ?>
			</div>

			<div class="flex flex-col gap-3 border-t border-gray-200 pt-5 md:flex-row md:items-center md:justify-between">
				<p class="text-sm text-gray-600">Le modifiche vengono salvate subito e tracciate nell'audit log admin.</p>
				<button type="submit" class="inline-flex items-center justify-center rounded-lg bg-green-700 px-5 py-3 font-semibold text-white shadow-md transition hover:bg-green-800">
					Salva setup
				</button>
			</div>
		</form>
	</div>
</div>
