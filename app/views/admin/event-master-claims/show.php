<div class="container mx-auto max-w-4xl p-6">
	<a href="/admin/event-master-claims" class="mb-6 inline-flex rounded-lg bg-gray-200 px-4 py-2 font-semibold text-gray-800 hover:bg-gray-300">Torna alle richieste</a>
	<h1 class="mb-6 text-3xl font-semibold text-gray-800">Dettaglio richiesta di riscatto</h1>

	<div class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
		<div><span class="font-semibold">Master:</span> <?= htmlspecialchars($claim['event_master_name'], ENT_QUOTES, 'UTF-8') ?></div>
		<div><span class="font-semibold">Organizzazione:</span> <?= htmlspecialchars($claim['organization_name'], ENT_QUOTES, 'UTF-8') ?></div>
		<div><span class="font-semibold">Richiedente:</span> <?= htmlspecialchars($claim['requester_username'] . ' (' . $claim['requester_email'] . ')', ENT_QUOTES, 'UTF-8') ?></div>
		<div><span class="font-semibold">Ruolo richiesto:</span> <?= htmlspecialchars($claim['role'], ENT_QUOTES, 'UTF-8') ?></div>
		<div><span class="font-semibold">Data richiesta:</span> <?= htmlspecialchars((string) $claim['created_at'], ENT_QUOTES, 'UTF-8') ?></div>
		<div>
			<div class="mb-1 font-semibold">Prove fornite</div>
			<div class="whitespace-pre-wrap rounded-lg bg-gray-50 p-4 text-gray-700"><?= htmlspecialchars((string) ($claim['evidence'] ?: 'Nessuna prova allegata.'), ENT_QUOTES, 'UTF-8') ?></div>
		</div>

		<?php if ($claim['status'] === 'pending'): ?>
			<div class="flex flex-wrap gap-3 border-t border-gray-200 pt-5">
				<form method="POST" action="/admin/event-master-claims/<?= (int) $claim['id'] ?>/approve" onsubmit="return confirm('Approvare questa richiesta?');">
					<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
					<button type="submit" class="rounded-lg bg-green-600 px-5 py-2 font-semibold text-white hover:bg-green-700">Approva richiesta</button>
				</form>
				<form method="POST" action="/admin/event-master-claims/<?= (int) $claim['id'] ?>/reject" class="flex flex-1 gap-2" onsubmit="return confirm('Rifiutare questa richiesta?');">
					<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
					<input type="text" name="review_notes" maxlength="1000" placeholder="Motivo del rifiuto (facoltativo)" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2">
					<button type="submit" class="rounded-lg bg-red-600 px-5 py-2 font-semibold text-white hover:bg-red-700">Rifiuta</button>
				</form>
			</div>
		<?php else: ?>
			<div class="border-t border-gray-200 pt-4 font-semibold text-gray-700">Stato: <?= htmlspecialchars($claim['status'], ENT_QUOTES, 'UTF-8') ?></div>
		<?php endif; ?>
	</div>
</div>
