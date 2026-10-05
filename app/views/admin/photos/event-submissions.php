<?php
$h = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$statuses = [
	'pending' => 'In verifica',
	'merged' => 'Collegate',
	'approved' => 'Approvate',
	'rejected' => 'Rifiutate',
	'' => 'Tutte',
];
?>
<div class="container mx-auto px-4 py-8">
	<div class="mb-6">
		<h1 class="text-3xl font-semibold text-gray-800">Eventi/edizioni segnalati dalle foto</h1>
		<p class="mt-2 text-gray-600">Risolvi le segnalazioni create durante l'upload foto. Le immagini restano fuori dalle gallery pubbliche finché non colleghi una destinazione.</p>
	</div>

	<section class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-5 text-blue-950">
		<h2 class="text-lg font-bold">Come funziona questa pagina</h2>
		<div class="mt-3 grid gap-4 text-sm leading-6 lg:grid-cols-3">
			<div>
				<p class="font-semibold">1. Controlla la segnalazione</p>
				<p>L'utente ha caricato foto per un evento o un anno che non trovava. Le foto sono già salvate, ma non sono pubbliche.</p>
			</div>
			<div>
				<p class="font-semibold">2. Trova o crea l'evento corretto</p>
				<p>Se l'edizione esiste già, usa il suo ID. Se manca, creala prima dal normale CRUD eventi e poi torna qui.</p>
			</div>
			<div>
				<p class="font-semibold">3. Collega le foto</p>
				<p>Inserendo l'ID evento/edizione, tutte le foto della segnalazione vengono assegnate a quell'evento e pubblicate.</p>
			</div>
		</div>
		<p class="mt-4 rounded-lg bg-white px-3 py-2 text-sm text-blue-900 ring-1 ring-blue-100">
			Usa <strong>Rifiuta</strong> solo se la segnalazione non è risolvibile. Le foto non vengono cancellate: restano non pubbliche.
		</p>
	</section>

	<?php if ($message = \App\Core\Session::getFlash('success')): ?>
		<div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?= $h($message) ?></div>
	<?php endif; ?>
	<?php if ($message = \App\Core\Session::getFlash('error')): ?>
		<div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= $h($message) ?></div>
	<?php endif; ?>

	<div class="mb-5 flex flex-wrap gap-2">
		<?php foreach ($statuses as $key => $label): ?>
			<a href="/admin/photos/event-submissions?status=<?= rawurlencode((string) $key) ?>" class="rounded-lg px-3 py-2 text-sm font-semibold <?= (string) $status === (string) $key ? 'bg-green-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>"><?= $h($label) ?></a>
		<?php endforeach; ?>
	</div>

	<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
		<table class="min-w-full divide-y divide-gray-200 text-sm">
			<thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
				<tr>
					<th class="px-4 py-3">Segnalazione</th>
					<th class="px-4 py-3">Utente</th>
					<th class="px-4 py-3">Foto</th>
					<th class="px-4 py-3">Stato</th>
					<th class="px-4 py-3">Azioni</th>
				</tr>
			</thead>
			<tbody class="divide-y divide-gray-100">
				<?php foreach ($submissions as $submission): ?>
					<tr>
						<td class="px-4 py-4 align-top">
							<p class="font-bold text-gray-950"><?= $h($submission['event_name']) ?> <?= (int) $submission['year'] ?></p>
							<p class="mt-1 text-xs text-gray-500">
								<?= !empty($submission['event_id']) ? 'Edizione mancante di: ' . $h($submission['existing_event_title']) : 'Evento mancante' ?>
								<?php if (!empty($submission['location_name'])): ?> · <?= $h($submission['location_name']) ?><?php endif; ?>
								<?php if (!empty($submission['event_date'])): ?> · <?= $h($submission['event_date']) ?><?php endif; ?>
							</p>
							<p class="mt-1 text-xs text-gray-500">Segnalato il <?= $h($submission['created_at']) ?></p>
						</td>
						<td class="px-4 py-4 align-top">@<?= $h($submission['user_username'] ?: 'utente') ?></td>
						<td class="px-4 py-4 align-top font-semibold"><?= (int) $submission['photo_count'] ?></td>
						<td class="px-4 py-4 align-top">
							<span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-900"><?= $h($statuses[$submission['status']] ?? $submission['status']) ?></span>
							<?php if (!empty($submission['resolved_event_title'])): ?>
								<p class="mt-2 text-xs text-gray-500">→ <?= $h($submission['resolved_event_title']) ?></p>
							<?php endif; ?>
						</td>
						<td class="px-4 py-4 align-top">
							<?php if (($submission['status'] ?? '') === 'pending'): ?>
								<p class="mb-2 max-w-sm text-xs leading-5 text-gray-600">
									Inserisci l'ID dell'evento/edizione definitivo. Dopo il collegamento, le foto diventano pubbliche nella gallery di quell'evento.
								</p>
								<form method="post" action="/admin/photos/event-submissions/<?= (int) $submission['id'] ?>/merge?status=<?= rawurlencode((string) $status) ?>" class="mb-2 flex min-w-72 flex-wrap gap-2">
									<input type="hidden" name="csrf_token" value="<?= $h($csrf_token) ?>">
									<input type="number" min="1" name="resolved_event_id" required aria-label="ID evento o edizione definitiva" placeholder="Es. 123" class="w-36 rounded-lg border border-gray-300 px-3 py-2">
									<button class="rounded-lg bg-green-800 px-3 py-2 font-semibold text-white hover:bg-green-900">Collega</button>
								</form>
								<form method="post" action="/admin/photos/event-submissions/<?= (int) $submission['id'] ?>/reject?status=<?= rawurlencode((string) $status) ?>" onsubmit="return confirm('Rifiutare la segnalazione? Le foto resteranno non pubbliche.');">
									<input type="hidden" name="csrf_token" value="<?= $h($csrf_token) ?>">
									<button class="rounded-lg border border-red-700 px-3 py-2 font-semibold text-red-700 hover:bg-red-50">Rifiuta</button>
								</form>
							<?php else: ?>
								<span class="text-gray-500">Nessuna azione disponibile</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if (!$submissions): ?>
			<div class="p-8 text-center text-gray-500">Nessuna segnalazione trovata.</div>
		<?php endif; ?>
	</div>

	<div class="mt-4 flex justify-end gap-2">
		<a class="rounded-lg px-3 py-2 text-sm font-semibold <?= $page > 1 ? 'bg-gray-100 text-gray-800 hover:bg-gray-200' : 'pointer-events-none bg-gray-50 text-gray-300' ?>" href="/admin/photos/event-submissions?status=<?= rawurlencode((string) $status) ?>&page=<?= max(1, (int) $page - 1) ?>">Precedente</a>
		<a class="rounded-lg px-3 py-2 text-sm font-semibold <?= $page < $totalPages ? 'bg-gray-100 text-gray-800 hover:bg-gray-200' : 'pointer-events-none bg-gray-50 text-gray-300' ?>" href="/admin/photos/event-submissions?status=<?= rawurlencode((string) $status) ?>&page=<?= min((int) $totalPages, (int) $page + 1) ?>">Successiva</a>
	</div>
</div>
