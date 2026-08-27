
<?php

/**
 * Admin Events List
 *
 * Gestione elenco eventi backend.
 *
 * La tabella viene caricata dinamicamente
 * tramite AdminList + EventController::data()
 */


$adminList = [

	'id' => 'events-list',

	'endpoint' => '/admin/events/data',
	'csrfToken' => $_SESSION['csrf_token'] ?? '',
	'rowEdit' => '/admin/events/edit/{id}',
	'detailPanel' => true,
	'detailEndpoint' => '/admin/events/detail/{id}',
	'detailRenderer' => 'event',

	'pageSize' => 25,


	'defaultSort' => 'data_inizio',

	'defaultDirection' => 'desc',


	'search' => true,



	'filters' => [

		[
			'name' => 'approvato',
			'label' => 'Stato',
			'type' => 'select',
			'options' => [
				[
					'value' => '',
					'label' => 'Tutti'
				],
					[
					'value' => '1',
					'label' => 'Approvati'
				],

				[
					'value' => '0',
					'label' => 'In attesa'
				]

			]

		],


		[
			'name' => 'regione_id',

			'label' => 'Regione',

			'type' => 'select',

			'options' =>
				$data['regions'] ?? []

		]

	],

	'columns' => [

		[
			'key' => 'id',

			'label' => 'ID',

			'sortable' => true,

			'width' => '70px'
		],


		[
			'key' => 'titolo',

			'label' => 'Evento',

			'sortable' => true,

			'width' => '320px'
		],


		[
			'key' => 'data_inizio',

			'label' => 'Data inizio',

			'type' => 'date',

			'sortable' => true,

			'width' => '120px'
		],


		[
			'key' => 'luogo',

			'label' => 'Luogo',

			'sortable' => true,

			'width' => '300px'
		],


		[
			'key' => 'event_size',

			'label' => 'Size',

			'sortable' => true,

			'width' => '80px'
		],


		[
			'key' => 'approvato',

			'label' => 'Stato',

			'formatter' => 'eventStatus',

			'sortable' => true,

			'width' => '120px'
		]

	],



	'actions' => [

		[
			'key' => 'view',

			'label' => 'Visualizza'
		],


		[
			'key' => 'edit',

			'label' => 'Modifica'
		],

		[
			'key' => 'copy',

			'label' => 'Copia',

			'confirm' => 'Vuoi creare una copia di questo evento? La nuova edizione sarà in attesa di approvazione.'
		],

		[
			'key' => 'delete',

			'label' => 'Elimina',

			'confirm' => 'Sei sicuro di voler eliminare questo evento?'
		]

	],



	'emptyState' => [

		'title' => 'Nessun evento presente',


		'message' =>
			'Non sono ancora stati inseriti eventi.',


		'action' => [

			'url' => '/admin/events/create',

			'label' => 'Crea evento'

		]

	],



	'noResults' => [

		'title' =>
			'Nessun evento trovato',


		'message' =>
			'Modifica i criteri di ricerca oppure rimuovi i filtri applicati.'

	]

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

		<a href="/admin/events/create"
		   class="inline-flex items-center px-4 py-2 bg-blue-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			Crea nuovo Evento
		</a>

	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">
		Tutti gli eventi
	</h1>

<?php
if (isset($_SESSION['flash_messages'])) {
	foreach ($_SESSION['flash_messages'] as $type => $message) {
		echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
	}
	unset($_SESSION['flash_messages']);
}
?>
<?php
require __DIR__ .
	'/../components/admin-list/admin-list.php';
