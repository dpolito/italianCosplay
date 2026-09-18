<?php

$adminList = [
	'id' => 'event-master-claims-list',
	'endpoint' => '/admin/event-master-claims/data',
	'csrfToken' => $_SESSION['csrf_token'] ?? '',
	'rowEdit' => '/admin/event-master-claims/{id}',
	'pageSize' => 25,
	'defaultSort' => 'created_at',
	'defaultDirection' => 'asc',
	'search' => true,
	'columns' => [
		['key' => 'id', 'label' => 'ID', 'sortable' => true, 'width' => '70px'],
		['key' => 'event_master_name', 'label' => 'Master', 'sortable' => true, 'width' => '280px'],
		['key' => 'organization_name', 'label' => 'Organizzazione', 'sortable' => true, 'width' => '240px'],
		['key' => 'requester_username', 'label' => 'Richiedente', 'sortable' => true, 'width' => '180px'],
		['key' => 'role', 'label' => 'Ruolo', 'sortable' => true, 'width' => '140px'],
		['key' => 'created_at', 'label' => 'Data', 'type' => 'date', 'sortable' => true, 'width' => '140px'],
	],
	'actions' => [
		['key' => 'view', 'label' => 'Apri richiesta'],
	],
	'emptyState' => [
		'title' => 'Nessuna richiesta pendente',
		'message' => 'Non ci sono richieste di riscatto da verificare.',
	],
	'noResults' => [
		'title' => 'Nessuna richiesta trovata',
		'message' => 'Modifica il criterio di ricerca.',
	],
];

?>

<div class="container mx-auto p-6">
	<div class="mb-6 flex items-center justify-between">
		<a href="/admin/dashboard" class="inline-flex items-center rounded-lg bg-gray-200 px-4 py-2 font-semibold text-gray-800 hover:bg-gray-300">
			Torna alla Dashboard
		</a>
	</div>

	<h1 class="mb-2 text-3xl font-semibold text-gray-800">Richieste di riscatto</h1>
	<p class="mb-6 text-gray-600">Verifica le richieste degli utenti che vogliono gestire un evento master per conto di un’organizzazione.</p>

	<?php if ($message = \App\Core\Session::getFlash('error')): ?>
		<div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
	<?php endif; ?>
	<?php if ($message = \App\Core\Session::getFlash('success')): ?>
		<div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
	<?php endif; ?>

	<?php require __DIR__ . '/../components/admin-list/admin-list.php'; ?>
</div>
