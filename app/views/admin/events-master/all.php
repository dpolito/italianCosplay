<?php

$adminList = [
	'id' => 'event-masters-list',
	'endpoint' => '/admin/events-master/data',
	'csrfToken' => $_SESSION['csrf_token'] ?? '',
	'rowEdit' => '/admin/events-master/edit/{id}',
	'detailPanel' => true,
	'detailEndpoint' => '/admin/events-master/detail/{id}',
	'detailRenderer' => 'eventMaster',
	'pageSize' => 25,
	'defaultSort' => 'nome',
	'defaultDirection' => 'asc',
	'search' => true,
	'columns' => [
		['key' => 'id', 'label' => 'ID', 'sortable' => true, 'width' => '70px'],
		['key' => 'nome', 'label' => 'Evento master', 'sortable' => true, 'width' => '320px'],
		['key' => 'status', 'label' => 'Stato', 'sortable' => true, 'width' => '150px'],
		['key' => 'slug', 'label' => 'Slug', 'sortable' => true, 'width' => '240px'],
		['key' => 'sito_web', 'label' => 'Sito web', 'sortable' => true, 'width' => '260px'],
		['key' => 'created_at', 'label' => 'Data', 'type' => 'date', 'sortable' => true, 'width' => '120px'],
	],
	'actions' => [
		['key' => 'edit', 'label' => 'Modifica'],
		['key' => 'delete', 'label' => 'Elimina', 'confirm' => 'Sei sicuro di voler eliminare questo evento master?'],
	],
	'emptyState' => [
		'title' => 'Nessun evento master presente',
		'message' => 'Non sono ancora stati inseriti eventi master.',
		'action' => [
			'url' => '/admin/events-master/create',
			'label' => 'Crea evento master',
		],
	],
	'noResults' => [
		'title' => 'Nessun evento master trovato',
		'message' => 'Modifica i criteri di ricerca oppure rimuovi i filtri applicati.',
	],
];

?>

<div class="container mx-auto p-6">
	<div class="flex justify-between items-center mb-6">
		<a href="/admin/dashboard"
		   class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
			</svg>
			Torna alla Dashboard
		</a>

		<a href="/admin/events-master/create"
		   class="inline-flex items-center px-4 py-2 bg-blue-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			Crea nuovo Evento Master
		</a>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">
		Tutti gli eventi master
	</h1>

	<?php
	if (isset($_SESSION['flash_messages'])) {
		foreach ($_SESSION['flash_messages'] as $type => $message) {
			echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
		}
		unset($_SESSION['flash_messages']);
	}
	?>

	<?php require __DIR__ . '/../components/admin-list/admin-list.php'; ?>
</div>
