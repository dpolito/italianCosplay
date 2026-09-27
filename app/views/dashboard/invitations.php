<?php
$invitations = $invitations ?? [];
$dailyUsage = $dailyUsage ?? ['sent' => 0, 'limit' => 5, 'remaining' => 5];
$statusLabels = [
	'pending' => 'In attesa',
	'accepted' => 'Accettato',
	'expired' => 'Scaduto',
	'blocked' => 'Bloccato',
];
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>

<section class="mx-auto max-w-6xl space-y-6" aria-labelledby="user-invitations-title">
	<header>
		<p class="text-sm font-bold uppercase tracking-wide text-green-900">Community</p>
		<h1 id="user-invitations-title" class="mt-2 text-3xl font-extrabold text-gray-950">Invita amici</h1>
		<p class="mt-3 max-w-2xl text-sm leading-relaxed text-gray-700">
			Invia un invito con il template ufficiale ItalianCosplay. L’indirizzo non viene iscritto a newsletter o comunicazioni marketing.
		</p>
	</header>

	<?php if ($message = \App\Core\Session::getFlash('success')): ?>
		<div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-900"><?= $h($message) ?></div>
	<?php endif; ?>
	<?php if ($message = \App\Core\Session::getFlash('error')): ?>
		<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800"><?= $h($message) ?></div>
	<?php endif; ?>

	<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
		<form method="post" action="/dashboard/inviti" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
			<input type="hidden" name="csrf_token" value="<?= $h($_SESSION['csrf_token'] ?? '') ?>">
			<label for="invitation-email" class="block text-sm font-bold text-gray-900">Email della persona da invitare</label>
			<div class="mt-2 flex flex-col gap-3 sm:flex-row">
				<input id="invitation-email" type="email" name="email" required maxlength="255" placeholder="nome@dominio.it" class="min-h-11 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
				<button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-green-800 px-5 py-2 text-sm font-bold text-white hover:bg-green-900">
					Invia invito
				</button>
			</div>
			<p class="mt-3 text-xs leading-relaxed text-gray-600">
				Se l’indirizzo è già registrato, invieremo una notifica interna invece di un’email.
			</p>
		</form>

		<aside class="rounded-xl border border-green-200 bg-green-50 p-5 shadow-sm" aria-label="Limite inviti giornaliero">
			<p class="text-sm font-bold uppercase tracking-wide text-green-900">Limite giornaliero</p>
			<p class="mt-2 text-3xl font-extrabold text-green-950"><?= (int) $dailyUsage['sent'] ?> / <?= (int) $dailyUsage['limit'] ?></p>
			<p class="mt-2 text-sm text-green-900">Inviti inviati nelle ultime 24 ore.</p>
			<div class="mt-4 h-3 overflow-hidden rounded-full bg-white">
				<?php $usagePercent = min(100, (int) round(((int) $dailyUsage['sent'] / max(1, (int) $dailyUsage['limit'])) * 100)); ?>
				<div class="h-full rounded-full bg-green-800" style="width: <?= $usagePercent ?>%"></div>
			</div>
			<p class="mt-3 text-xs font-semibold text-green-950">Disponibili ora: <?= (int) $dailyUsage['remaining'] ?></p>
		</aside>
	</div>

	<section class="grid gap-6 lg:grid-cols-2">
		<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
			<h2 class="text-xl font-extrabold text-gray-950">Come funziona</h2>
			<div class="mt-4 space-y-3 text-sm leading-relaxed text-gray-700">
				<p>Inserisci una sola email alla volta. Il sistema controlla se l’indirizzo appartiene già a un utente registrato.</p>
				<p>Se la persona non è registrata, riceve l’email qui sotto con link di invito e possibilità di bloccare futuri inviti.</p>
				<p>Se la persona è già registrata, riceve solo una notifica interna nella dashboard. Nessuna email esterna.</p>
				<p>Quando accetta, salviamo una relazione interna di tipo referral: non viene mostrata pubblicamente come amicizia.</p>
			</div>
		</div>

		<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
			<h2 class="text-xl font-extrabold text-gray-950">Testo email inviato</h2>
			<div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm leading-relaxed text-gray-700">
				<p class="font-bold text-gray-950">Oggetto: [Il tuo username] ti invita su ItalianCosplay.it</p>
				<p class="mt-3">Ciao,</p>
				<p class="mt-2">[Il tuo username] pensa che ItalianCosplay.it possa interessarti: è un portale dedicato agli eventi cosplay in Italia, dove puoi scoprire eventi, salvare quelli che ti interessano e partecipare alla community.</p>
				<p class="mt-2 font-semibold text-green-900">Accetta invito</p>
				<p class="mt-2">Hai ricevuto questa email perché un utente di ItalianCosplay.it ha indicato il tuo indirizzo per invitarti. Non sei stato iscritto a newsletter o comunicazioni marketing.</p>
				<p class="mt-2">Se non vuoi ricevere altri inviti, puoi bloccarli dal link presente nell’email.</p>
				<p class="mt-2">L’invito scade tra 30 giorni.</p>
			</div>
		</div>
	</section>

	<section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm" aria-labelledby="recent-user-invitations-title">
		<h2 id="recent-user-invitations-title" class="text-xl font-extrabold text-gray-950">Inviti recenti</h2>
		<?php if (!$invitations): ?>
			<p class="mt-4 text-sm text-gray-600">Non hai ancora inviato inviti.</p>
		<?php else: ?>
			<div class="mt-4 overflow-x-auto">
				<table class="min-w-full divide-y divide-gray-200 text-sm">
					<thead>
						<tr class="text-left text-xs font-bold uppercase tracking-wide text-gray-500">
							<th class="px-3 py-2">Email</th>
							<th class="px-3 py-2">Stato</th>
							<th class="px-3 py-2">Creato</th>
							<th class="px-3 py-2">Scadenza</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-gray-100">
						<?php foreach ($invitations as $invitation): ?>
							<?php
							$status = (string) ($invitation['status'] ?? '');
							if ($status === 'pending' && strtotime((string) ($invitation['expires_at'] ?? '')) <= time()) {
								$status = 'expired';
							}
							?>
							<tr>
								<td class="px-3 py-3 font-semibold text-gray-900"><?= $h($invitation['email'] ?? '') ?></td>
								<td class="px-3 py-3 text-gray-700"><?= $h($statusLabels[$status] ?? $status) ?></td>
								<td class="px-3 py-3 text-gray-600"><?= $h($invitation['created_at'] ?? '') ?></td>
								<td class="px-3 py-3 text-gray-600"><?= $h($invitation['expires_at'] ?? '') ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</section>
</section>
