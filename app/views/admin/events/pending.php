<?php

$adminList = [
	'id' => 'events-pending-list',
	'endpoint' => '/admin/events/pending/data',
	'csrfToken' => $_SESSION['csrf_token'] ?? '',
	'detailPanel' => true,
	'detailEndpoint' => '/admin/events/detail/{id}',
	'detailRenderer' => 'event',
	'rowEdit' => '/admin/events/edit/{id}',
	'pageSize' => 25,
	'defaultSort' => 'data_inizio',
	'defaultDirection' => 'desc',
	'search' => true,
	'columns' => [
		['key' => 'id', 'label' => 'ID', 'sortable' => true, 'width' => '70px'],
		['key' => 'titolo', 'label' => 'Titolo', 'sortable' => true, 'width' => '320px'],
		['key' => 'data_inizio', 'label' => 'Data inizio', 'type' => 'date', 'sortable' => true, 'width' => '120px'],
		['key' => 'data_fine', 'label' => 'Data fine', 'type' => 'date', 'sortable' => true, 'width' => '120px'],
		['key' => 'luogo', 'label' => 'Luogo', 'sortable' => true, 'width' => '300px'],
		['key' => 'approvato', 'label' => 'Stato', 'formatter' => 'eventStatus', 'sortable' => true, 'width' => '120px'],
	],
	'actions' => [
		['key' => 'edit', 'label' => 'Modifica'],
		['key' => 'view', 'label' => 'Visualizza'],
		['key' => 'delete', 'label' => 'Elimina', 'confirm' => 'Sei sicuro di voler eliminare questo evento?'],
	],
	'emptyState' => [
		'title' => 'Nessun evento in attesa',
		'message' => 'Non ci sono eventi in attesa di approvazione.',
		'action' => [
			'url' => '/admin/events/create',
			'label' => 'Crea evento',
		],
	],
	'noResults' => [
		'title' => 'Nessun evento trovato',
		'message' => 'Modifica i criteri di ricerca oppure rimuovi i filtri applicati.',
	],
];

?>

<div class="container mx-auto p-6">
	<div class="mb-6">
		<a href="/admin/dashboard" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
			</svg>
			Torna alla Dashboard
		</a>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Eventi in Attesa di Approvazione</h1>

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
