<?php

$adminList = [
	'id' => 'guests-list',
	'endpoint' => '/admin/guests/data',
	'csrfToken' => $_SESSION['csrf_token'] ?? '',
	'detailPanel' => true,
	'detailEndpoint' => '/admin/guests/detail/{id}',
	'detailRenderer' => 'guest',
	'rowEdit' => '/admin/guests/edit/{id}',
	'pageSize' => 25,
	'defaultSort' => 'created_at',
	'defaultDirection' => 'desc',
	'search' => true,
	'columns' => [
		['key' => 'id', 'label' => 'ID', 'sortable' => true, 'width' => '70px'],
		['key' => 'name', 'label' => 'Nome e Cognome', 'sortable' => true, 'width' => '320px'],
		['key' => 'slug', 'label' => 'Slug', 'sortable' => true, 'width' => '240px'],
		['key' => 'created_at', 'label' => 'Data', 'type' => 'date', 'sortable' => true, 'width' => '120px'],
	],
	'actions' => [
		['key' => 'edit', 'label' => 'Modifica'],
		['key' => 'delete', 'label' => 'Elimina', 'confirm' => 'Sei sicuro di voler eliminare questo guest?'],
	],
	'emptyState' => [
		'title' => 'Nessun guest presente',
		'message' => 'Non sono ancora stati inseriti guest.',
		'action' => [
			'url' => '/admin/guests/create',
			'label' => 'Crea guest',
		],
	],
	'noResults' => [
		'title' => 'Nessun guest trovato',
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
		<a href="/admin/guests/create"
		   class="inline-flex items-center px-4 py-2 bg-blue-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			Crea nuovo Guest
		</a>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">
		Tutti i Guest
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
