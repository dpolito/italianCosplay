<?php

$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$overview = $overview ?? [];
$organizations = $overview['organizations'] ?? [];
$pendingCount = (int) ($overview['pending_count'] ?? 0);
$sentCount = (int) ($overview['sent_count'] ?? 0);
$failedCount = (int) ($overview['failed_count'] ?? 0);

?>

<div class="container mx-auto max-w-6xl p-6">
	<div class="mb-6 flex items-center justify-between">
		<a href="/admin/dashboard" class="inline-flex items-center rounded-lg bg-gray-200 px-4 py-2 font-semibold text-gray-800 hover:bg-gray-300">
			Torna alla Dashboard
		</a>
	</div>

	<h1 class="mb-2 text-3xl font-semibold text-gray-800">Email organizzazioni</h1>
	<p class="mb-6 text-gray-600">Invia una comunicazione alle email censite nelle organizzazioni. Ogni organizzazione/email viene marcata dopo il primo tentativo e non viene reinviata.</p>

	<?php if ($message = \App\Core\Session::getFlash('error')): ?>
		<div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= $h($message) ?></div>
	<?php endif; ?>
	<?php if ($message = \App\Core\Session::getFlash('success')): ?>
		<div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?= $h($message) ?></div>
	<?php endif; ?>

	<div class="grid gap-4 md:grid-cols-3">
		<div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
			<div class="text-sm font-semibold text-gray-500">Da inviare</div>
			<div class="mt-1 text-3xl font-bold text-gray-950"><?= $pendingCount ?></div>
		</div>
		<div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
			<div class="text-sm font-semibold text-gray-500">Inviate</div>
			<div class="mt-1 text-3xl font-bold text-green-700"><?= $sentCount ?></div>
		</div>
		<div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
			<div class="text-sm font-semibold text-gray-500">Fallite</div>
			<div class="mt-1 text-3xl font-bold text-red-700"><?= $failedCount ?></div>
		</div>
	</div>

	<section class="mt-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
		<h2 class="text-xl font-bold text-gray-900">Nuovo invio</h2>
		<form method="POST" action="/admin/organization-emails/send" class="mt-5 space-y-4">
			<input type="hidden" name="csrf_token" value="<?= $h($csrf_token ?? '') ?>">
			<div>
				<label for="subject" class="block text-sm font-semibold text-gray-800">Oggetto</label>
				<input id="subject" name="subject" required maxlength="255" value="Il tuo evento è su ItalianCosplay.it: puoi gestirlo direttamente" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2">
			</div>
			<div>
				<label for="body" class="block text-sm font-semibold text-gray-800">Testo email</label>
				<textarea id="body" name="body" required rows="14" maxlength="10000" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2">Sul nostro sito è già presente una scheda dedicata al tuo evento cosplay.

ItalianCosplay.it raccoglie eventi cosplay, fiere comics, festival nerd e appuntamenti dedicati ad anime, manga, videogiochi e cultura pop in Italia, aiutando appassionati e visitatori a trovare informazioni aggiornate in un unico calendario.

Se fai parte dell'organizzazione, puoi registrarti gratuitamente e richiedere il riscatto della scheda evento. Dopo la verifica dello staff potrai:

- mantenere aggiornate date, luogo, descrizione e informazioni utili;
- correggere o completare link ufficiali, sito web e canali social;
- collegare nuove edizioni future dello stesso evento;
- migliorare la visibilità dell'evento nelle pagine del calendario;
- offrire ai visitatori informazioni più affidabili e sempre verificabili.

Il riscatto non comporta costi: serve solo ad associare la scheda a un referente autorizzato, così che le informazioni pubblicate siano più precise e facili da aggiornare nel tempo.

Per capire meglio come funziona la gestione degli eventi da parte degli organizzatori puoi leggere questa pagina:
https://www.italiancosplay.it/organizzatori-eventi-cosplay

Puoi partire dalla scheda del tuo evento su ItalianCosplay.it e usare il pulsante di riscatto per inviare la richiesta.

Grazie,
ItalianCosplay.it</textarea>
			</div>
			<div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
				<label for="test_email" class="block text-sm font-semibold text-gray-800">Email di test</label>
				<div class="mt-2 flex flex-col gap-3 sm:flex-row">
					<input id="test_email" type="email" name="test_email" placeholder="nome@dominio.it" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2">
					<button type="submit" formaction="/admin/organization-emails/test" formnovalidate class="rounded-lg bg-blue-700 px-5 py-2 font-semibold text-white hover:bg-blue-800">
						Invia test
					</button>
				</div>
			</div>
			<div class="flex flex-wrap gap-3">
				<button type="submit" onclick="return confirm('Inviare questa email a tutte le organizzazioni non ancora contattate?');" class="rounded-lg bg-green-700 px-5 py-3 font-semibold text-white hover:bg-green-800 disabled:cursor-not-allowed disabled:bg-gray-400" <?= $pendingCount < 1 ? 'disabled' : '' ?>>
					Invia a <?= $pendingCount ?> organizzazioni
				</button>
			</div>
		</form>
	</section>

	<section class="mt-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
		<h2 class="text-xl font-bold text-gray-900">Stato invii</h2>
		<div class="mt-4 overflow-x-auto">
			<table class="min-w-full divide-y divide-gray-200 text-sm">
				<thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
					<tr>
						<th class="px-3 py-3">Organizzazione</th>
						<th class="px-3 py-3">Email</th>
						<th class="px-3 py-3">Stato</th>
						<th class="px-3 py-3">Data invio</th>
						<th class="px-3 py-3">Ultimo tentativo</th>
						<th class="px-3 py-3">Errore</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-gray-100">
					<?php foreach ($organizations as $organization): ?>
						<?php $status = (string) ($organization['delivery_status'] ?? 'pending'); ?>
						<tr>
							<td class="px-3 py-3 font-semibold text-gray-900"><?= $h($organization['name'] ?? '') ?></td>
							<td class="px-3 py-3 text-gray-700"><?= $h($organization['email'] ?? '') ?></td>
							<td class="px-3 py-3">
								<span class="rounded-full px-2 py-1 text-xs font-bold <?= $status === 'sent' ? 'bg-green-100 text-green-800' : ($status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700') ?>">
									<?= $status === 'sent' ? 'Inviata' : ($status === 'failed' ? 'Fallita' : 'Da inviare') ?>
								</span>
							</td>
							<td class="px-3 py-3 text-gray-600"><?= $h($organization['sent_at'] ?? '-') ?></td>
							<td class="px-3 py-3 text-gray-600"><?= $h($organization['last_attempted_at'] ?? '-') ?></td>
							<td class="px-3 py-3 text-gray-600"><?= $h($organization['error_message'] ?? '') ?></td>
						</tr>
					<?php endforeach; ?>
					<?php if (empty($organizations)): ?>
						<tr>
							<td colspan="6" class="px-3 py-8 text-center text-gray-500">Nessuna organizzazione con email censita.</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</section>
</div>
