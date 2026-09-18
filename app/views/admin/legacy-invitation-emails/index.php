<?php

$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$overview = $overview ?? [];
$pendingCount = (int) ($overview['pending_count'] ?? 0);
$sentCount = (int) ($overview['sent_count'] ?? 0);
$failedCount = (int) ($overview['failed_count'] ?? 0);
$clickedCount = (int) ($overview['clicked_count'] ?? 0);
$sendableCount = $pendingCount + $failedCount;
$adminList = [
	'id' => 'legacy-invitation-emails-list',
	'endpoint' => '/admin/legacy-invitation-emails/data',
	'csrfToken' => $_SESSION['csrf_token'] ?? '',
	'pageSize' => 25,
	'defaultSort' => 'id',
	'defaultDirection' => 'desc',
	'search' => true,
	'columns' => [
		['key' => 'email', 'label' => 'Email', 'sortable' => true, 'width' => '320px'],
		['key' => 'status', 'label' => 'Stato', 'sortable' => true, 'width' => '130px', 'formatter' => 'legacyInvitationStatus'],
		['key' => 'sent_at', 'label' => 'Data invio', 'type' => 'datetime', 'sortable' => true, 'width' => '220px'],
		['key' => 'click_count', 'label' => 'Click', 'sortable' => true, 'width' => '90px'],
		['key' => 'last_clicked_at', 'label' => 'Ultimo click', 'type' => 'datetime', 'sortable' => true, 'width' => '220px'],
		['key' => 'error_message', 'label' => 'Errore', 'sortable' => true, 'width' => '360px'],
	],
	'actions' => [],
	'emptyState' => [
		'title' => 'Nessun indirizzo importato',
		'message' => 'Importa una lista email o un file JSON per iniziare.',
	],
	'noResults' => [
		'title' => 'Nessun indirizzo trovato',
		'message' => 'Modifica la ricerca oppure rimuovi i filtri applicati.',
	],
];

?>

<div class="container mx-auto p-6">
	<div class="mb-6 flex items-center justify-between">
		<a href="/admin/dashboard" class="inline-flex items-center rounded-lg bg-gray-200 px-4 py-2 font-semibold text-gray-800 hover:bg-gray-300">
			Torna alla Dashboard
		</a>
	</div>

	<h1 class="mb-2 text-3xl font-semibold text-gray-800">Email vecchio sito</h1>
	<p class="mb-6 text-gray-600">Importa gli indirizzi del vecchio sito, invia l'invito al nuovo sito migliorato e monitora chi clicca verso l'agenda cosplay.</p>

	<?php if ($message = \App\Core\Session::getFlash('error')): ?>
		<div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= $h($message) ?></div>
	<?php endif; ?>
	<?php if ($message = \App\Core\Session::getFlash('success')): ?>
		<div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?= $h($message) ?></div>
	<?php endif; ?>

	<div class="grid gap-4 md:grid-cols-4">
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
		<div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
			<div class="text-sm font-semibold text-gray-500">Email con click</div>
			<div class="mt-1 text-3xl font-bold text-blue-700"><?= $clickedCount ?></div>
		</div>
	</div>

	<section class="mt-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
		<h2 class="text-xl font-bold text-gray-900">Import indirizzi</h2>
		<div class="mt-5 grid gap-6 lg:grid-cols-2">
		<form method="POST" action="/admin/legacy-invitation-emails/import" class="space-y-4">
			<input type="hidden" name="csrf_token" value="<?= $h($csrf_token ?? '') ?>">
			<div>
				<label for="source_label" class="block text-sm font-semibold text-gray-800">Etichetta origine</label>
				<input id="source_label" name="source_label" maxlength="120" placeholder="Vecchio sito, export settembre 2026" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2">
			</div>
			<div>
				<label for="emails" class="block text-sm font-semibold text-gray-800">Email da importare</label>
				<textarea id="emails" name="emails" rows="5" placeholder="una email per riga, oppure separate da virgola o punto e virgola" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2"></textarea>
			</div>
			<button type="submit" class="rounded-lg bg-gray-900 px-5 py-3 font-semibold text-white hover:bg-gray-800">
				Importa email
			</button>
		</form>
		<form method="POST" action="/admin/legacy-invitation-emails/import-json" enctype="multipart/form-data" class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
			<input type="hidden" name="csrf_token" value="<?= $h($csrf_token ?? '') ?>">
			<div>
				<label for="json_source_label" class="block text-sm font-semibold text-gray-800">Etichetta origine JSON</label>
				<input id="json_source_label" name="json_source_label" maxlength="120" value="utenti_email_ultima_visita.json" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2">
			</div>
			<div>
				<label for="json_file" class="block text-sm font-semibold text-gray-800">File JSON</label>
				<input id="json_file" type="file" name="json_file" accept="application/json,.json" required class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
			</div>
			<button type="submit" class="rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">
				Importa JSON
			</button>
		</form>
		</div>
	</section>

	<section class="mt-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
		<h2 class="text-xl font-bold text-gray-900">Nuovo invio</h2>
		<form method="POST" action="/admin/legacy-invitation-emails/send" class="mt-5 space-y-4">
			<input type="hidden" name="csrf_token" value="<?= $h($csrf_token ?? '') ?>">
			<div>
				<label for="subject" class="block text-sm font-semibold text-gray-800">Oggetto</label>
				<input id="subject" name="subject" required maxlength="255" value="ItalianCosplay.it è tornato: ritrova gli eventi cosplay in Italia" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2">
			</div>
			<div>
				<label for="body" class="block text-sm font-semibold text-gray-800">Testo email</label>
				<textarea id="body" name="body" required rows="14" maxlength="10000" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2">Ciao,

se in passato hai usato ItalianCosplay.it, abbiamo una buona notizia: il sito è tornato online in una versione rinnovata.

Abbiamo ricostruito il progetto con un obiettivo semplice: rendere più facile trovare eventi cosplay, fiere comics, festival nerd e appuntamenti pop culture in Italia, senza dover cercare informazioni sparse ovunque.

Ora puoi:

- scoprire gli eventi cosplay in calendario;
- filtrare gli appuntamenti per zona e periodo;
- salvare gli eventi che ti interessano nella tua agenda personale;
- ritrovare più facilmente fiere, raduni e manifestazioni vicine a te.

Se ti era utile il vecchio ItalianCosplay.it, questa nuova versione è pensata proprio per riportare quell'idea in modo più ordinato, veloce e aggiornato.

Dai un'occhiata alla nuova agenda cosplay: potresti trovare subito il prossimo evento da segnare.

Grazie,
ItalianCosplay.it</textarea>
			</div>
			<div>
				<label for="batch_limit" class="block text-sm font-semibold text-gray-800">Limite invio per pacchetto</label>
				<input id="batch_limit" type="number" name="batch_limit" min="1" max="200" value="50" class="mt-2 w-40 rounded-lg border border-gray-300 px-3 py-2">
				<p class="mt-1 text-sm text-gray-500">Consigliato: 50. Massimo consentito: 200.</p>
			</div>
			<div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
				<label for="test_email" class="block text-sm font-semibold text-gray-800">Email di test</label>
				<div class="mt-2 flex flex-col gap-3 sm:flex-row">
					<input id="test_email" type="email" name="test_email" placeholder="nome@dominio.it" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2">
					<button type="submit" formaction="/admin/legacy-invitation-emails/test" formnovalidate class="rounded-lg bg-blue-700 px-5 py-2 font-semibold text-white hover:bg-blue-800">
						Invia test
					</button>
				</div>
			</div>
			<div class="flex flex-wrap gap-3">
				<button type="submit" onclick="return confirm('Inviare questa email a tutti gli indirizzi non ancora inviati o falliti?');" class="rounded-lg bg-green-700 px-5 py-3 font-semibold text-white hover:bg-green-800 disabled:cursor-not-allowed disabled:bg-gray-400" <?= $sendableCount < 1 ? 'disabled' : '' ?>>
					Invia prossimo pacchetto
				</button>
				<span class="self-center text-sm text-gray-600"><?= $sendableCount ?> indirizzi ancora inviabili</span>
			</div>
		</form>
	</section>

	<section class="mt-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
		<h2 class="text-xl font-bold text-gray-900">Stato invii e click</h2>
		<div class="mt-4">
			<?php require __DIR__ . '/../components/admin-list/admin-list.php'; ?>
		</div>
	</section>
</div>
