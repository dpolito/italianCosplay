<?php
$adminList = [
	'id' => 'faq-items-list',
	'endpoint' => '/admin/faq/items/data',
	'csrfToken' => $csrf_token ?? '',
	'rowEdit' => '/admin/faq/items/{id}/edit',
	'pageSize' => 25,
	'defaultSort' => 'sort_order',
	'defaultDirection' => 'asc',
	'search' => true,
	'columns' => [
		['key' => 'id', 'label' => 'ID', 'sortable' => true, 'width' => '70px'],
		['key' => 'question', 'label' => 'Domanda', 'sortable' => true, 'width' => '360px'],
		['key' => 'category_name', 'label' => 'Categoria', 'sortable' => true, 'width' => '200px'],
		['key' => 'feature', 'label' => 'Visibilità funzione', 'sortable' => true, 'width' => '210px'],
		['key' => 'status', 'label' => 'Stato', 'sortable' => true, 'width' => '120px'],
		['key' => 'sort_order', 'label' => 'Ordine', 'sortable' => true, 'width' => '90px'],
	],
	'actions' => [
		['key' => 'edit', 'label' => 'Modifica'],
		['key' => 'delete', 'label' => 'Elimina', 'confirm' => 'Vuoi eliminare questa domanda e risposta?'],
	],
	'emptyState' => [
		'title' => 'Nessuna domanda e risposta',
		'message' => 'Crea la prima FAQ dopo aver configurato almeno una categoria.',
		'action' => ['url' => '/admin/faq/items/create', 'label' => 'Crea domanda e risposta'],
	],
	'noResults' => ['title' => 'Nessuna FAQ trovata', 'message' => 'Modifica la ricerca oppure azzera i filtri.'],
];
?>
<div class="container mx-auto p-6">
	<div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
		<div><h1 class="text-3xl font-semibold text-gray-800">Domande e risposte</h1><p class="mt-1 text-gray-600">Gestisci i contenuti mostrati nella sezione FAQ pubblica.</p></div>
		<a href="/admin/faq/items/create" class="inline-flex justify-center rounded-lg bg-green-700 px-4 py-2 font-semibold text-white hover:bg-green-800">Nuova domanda e risposta</a>
	</div>
	<?php if ($message = \App\Core\Session::getFlash('error')): ?><div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
	<?php if ($message = \App\Core\Session::getFlash('success')): ?><div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
	<?php require __DIR__ . '/../components/admin-list/admin-list.php'; ?>
</div>
