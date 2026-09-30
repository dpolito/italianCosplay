<?php
declare(strict_types=1);

use App\Core\Session;

$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$statusLabels = [
	'' => 'Tutte',
	'open' => 'Aperte',
	'reviewed' => 'Archiviate',
	'closed' => 'Chiuse',
];
$statusClasses = [
	'open' => 'bg-red-100 text-red-800',
	'reviewed' => 'bg-gray-100 text-gray-700',
	'closed' => 'bg-green-100 text-green-800',
];
?>

<div class="container mx-auto p-6">
	<div class="mb-6 flex justify-between items-center">
		<a href="/admin/dashboard" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
			</svg>
			Torna alla Dashboard
		</a>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-2">Segnalazioni foto</h1>
	<p class="mb-6 text-gray-600">Controlla le foto segnalate dagli utenti, apri il dettaglio pubblico e decidi se chiudere la segnalazione o nascondere la foto dalla gallery.</p>

	<?php if ($message = Session::getFlash('error')): ?>
		<div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= $h($message) ?></div>
	<?php endif; ?>
	<?php if ($message = Session::getFlash('success')): ?>
		<div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?= $h($message) ?></div>
	<?php endif; ?>

	<div class="mb-6 flex flex-wrap items-center gap-2 rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
		<?php foreach ($statusLabels as $key => $label): ?>
			<a
				href="/admin/photos/reports?status=<?= rawurlencode((string) $key) ?>"
				class="rounded-lg px-3 py-2 text-sm font-semibold <?= $status === $key ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>"
			><?= $h($label) ?></a>
		<?php endforeach; ?>
		<span class="ml-auto text-sm font-semibold text-gray-500"><?= (int) $total ?> segnalazioni</span>
	</div>

	<div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
		<?php if ($reports): ?>
			<div class="overflow-x-auto">
				<table class="min-w-full divide-y divide-gray-200">
					<thead class="bg-gray-50">
						<tr>
							<th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Foto</th>
							<th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Segnalazione</th>
							<th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Evento</th>
							<th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Utenti</th>
							<th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-gray-500">Azioni</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-gray-100">
						<?php foreach ($reports as $report): ?>
							<?php
							$photoUrl = '/eventi-cosplay/' . rawurlencode((string) $report['event_slug']) . '/foto/' . (int) $report['photo_id'];
							$currentStatus = (string) ($report['status'] ?? 'open');
							?>
							<tr class="align-top">
								<td class="px-4 py-4">
									<a href="<?= $h($photoUrl) ?>" target="_blank" rel="noopener noreferrer" class="block w-32 overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
										<img src="<?= $h($report['thumbnail_url']) ?>" alt="Foto segnalata #<?= (int) $report['photo_id'] ?>" class="h-24 w-32 object-cover">
									</a>
									<p class="mt-2 text-xs text-gray-500">Foto #<?= (int) $report['photo_id'] ?> · <?= $h($report['photo_status']) ?></p>
								</td>
								<td class="max-w-md px-4 py-4">
									<div class="flex flex-wrap items-center gap-2">
										<span class="rounded-full px-2 py-1 text-xs font-bold <?= $statusClasses[$currentStatus] ?? 'bg-gray-100 text-gray-700' ?>"><?= $h($statusLabels[$currentStatus] ?? $currentStatus) ?></span>
										<span class="text-xs text-gray-500"><?= $h($report['created_at']) ?></span>
									</div>
									<p class="mt-2 font-semibold text-gray-900"><?= $h($report['reason']) ?></p>
									<?php if (!empty($report['message'])): ?>
										<p class="mt-1 whitespace-pre-line text-sm text-gray-700"><?= $h($report['message']) ?></p>
									<?php else: ?>
										<p class="mt-1 text-sm text-gray-500">Nessun messaggio aggiuntivo.</p>
									<?php endif; ?>
								</td>
								<td class="px-4 py-4">
									<a href="/eventi-cosplay/<?= $h($report['event_slug']) ?>" target="_blank" rel="noopener noreferrer" class="font-semibold text-green-800 hover:underline"><?= $h($report['event_title']) ?></a>
									<p class="mt-1 text-xs text-gray-500">Evento #<?= (int) $report['event_id'] ?></p>
								</td>
								<td class="px-4 py-4 text-sm text-gray-700">
									<p><span class="font-semibold">Uploader:</span> <?= $h($report['uploader_username'] ?: 'N/D') ?></p>
									<p class="mt-1"><span class="font-semibold">Segnalante:</span> <?= $h($report['reporter_username'] ?: 'Anonimo') ?></p>
								</td>
								<td class="px-4 py-4">
									<div class="flex flex-col items-stretch gap-2">
										<a href="<?= $h($photoUrl) ?>" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-gray-300 px-3 py-2 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">Apri foto</a>
										<?php if ($currentStatus === 'open'): ?>
											<form method="post" action="/admin/photos/reports/<?= (int) $report['id'] ?>/resolve?status=<?= rawurlencode((string) $status) ?>">
												<input type="hidden" name="csrf_token" value="<?= $h($csrf_token) ?>">
												<button class="w-full rounded-lg bg-green-700 px-3 py-2 text-sm font-semibold text-white hover:bg-green-800">Chiudi</button>
											</form>
											<form method="post" action="/admin/photos/reports/<?= (int) $report['id'] ?>/dismiss?status=<?= rawurlencode((string) $status) ?>">
												<input type="hidden" name="csrf_token" value="<?= $h($csrf_token) ?>">
												<button class="w-full rounded-lg bg-gray-700 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-800">Archivia</button>
											</form>
											<form method="post" action="/admin/photos/reports/<?= (int) $report['id'] ?>/hide-photo?status=<?= rawurlencode((string) $status) ?>" onsubmit="return confirm('Nascondere questa foto dalla gallery pubblica?');">
												<input type="hidden" name="csrf_token" value="<?= $h($csrf_token) ?>">
												<button class="w-full rounded-lg bg-red-700 px-3 py-2 text-sm font-semibold text-white hover:bg-red-800">Nascondi foto</button>
											</form>
										<?php endif; ?>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else: ?>
			<div class="p-10 text-center">
				<h2 class="text-xl font-bold text-gray-900">Nessuna segnalazione trovata</h2>
				<p class="mt-2 text-gray-600">Quando una foto viene segnalata, la vedrai qui e riceverai anche l’avviso Telegram se configurato.</p>
			</div>
		<?php endif; ?>
	</div>

	<?php if ($totalPages > 1): ?>
		<div class="flex items-center justify-between rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
			<a class="rounded-lg px-3 py-2 text-sm font-semibold <?= $page > 1 ? 'bg-gray-100 text-gray-800 hover:bg-gray-200' : 'pointer-events-none bg-gray-50 text-gray-300' ?>" href="/admin/photos/reports?status=<?= rawurlencode((string) $status) ?>&page=<?= max(1, (int) $page - 1) ?>">Precedente</a>
			<span class="text-sm font-semibold text-gray-600">Pagina <?= (int) $page ?> di <?= (int) $totalPages ?></span>
			<a class="rounded-lg px-3 py-2 text-sm font-semibold <?= $page < $totalPages ? 'bg-gray-100 text-gray-800 hover:bg-gray-200' : 'pointer-events-none bg-gray-50 text-gray-300' ?>" href="/admin/photos/reports?status=<?= rawurlencode((string) $status) ?>&page=<?= min((int) $totalPages, (int) $page + 1) ?>">Successiva</a>
		</div>
	<?php endif; ?>
</div>
