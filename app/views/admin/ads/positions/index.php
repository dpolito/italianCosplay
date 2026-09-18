<?php
$adminList = [
	'id' => 'ads-positions-list',
	'endpoint' => '/admin/ads/positions/data',
	'csrfToken' => $_SESSION['csrf_token'] ?? '',
	'detailPanel' => true,
	'detailEndpoint' => '/admin/ads/positions/detail/{id}',
	'detailRenderer' => 'adPosition',
	'rowEdit' => '/admin/ads/positions/edit/{id}',
	'pageSize' => 25,
	'defaultSort' => 'name',
	'defaultDirection' => 'asc',
	'search' => true,
	'columns' => [
		['key' => 'id', 'label' => 'ID', 'sortable' => true, 'width' => '70px'],
		['key' => 'code', 'label' => 'Codice', 'sortable' => true, 'width' => '180px'],
		['key' => 'name', 'label' => 'Nome', 'sortable' => true, 'width' => '280px'],
		['key' => 'page', 'label' => 'Pagina', 'sortable' => true, 'width' => '180px'],
		['key' => 'max_slots', 'label' => 'Slot', 'sortable' => true, 'width' => '80px'],
		['key' => 'base_price', 'label' => 'Base', 'sortable' => true, 'width' => '110px'],
	],
	'actions' => [
		['key' => 'view', 'label' => 'Apri'],
		['key' => 'edit', 'label' => 'Modifica'],
		['key' => 'delete', 'label' => 'Disattiva', 'confirm' => 'Sei sicuro di voler disattivare questa posizione?'],
	],
	'emptyState' => [
		'title' => 'Nessuna posizione presente',
		'message' => 'Non ci sono ancora posizioni pubblicitarie configurate.',
		'action' => [
			'url' => '/admin/ads/positions/create',
			'label' => 'Crea posizione',
		],
	],
	'noResults' => [
		'title' => 'Nessuna posizione trovata',
		'message' => 'Modifica i criteri di ricerca oppure rimuovi i filtri applicati.',
	],
];
?>

<div class="container mx-auto p-6">
	<div class="flex justify-between items-center mb-6">
		<a href="/admin/dashboard" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
			</svg>
			Torna alla Dashboard
		</a>
		<a href="/admin/ads/positions/create" class="inline-flex items-center px-4 py-2 bg-blue-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			Nuova posizione
		</a>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Posizioni pubblicitarie</h1>

	<?php
	if (isset($_SESSION['flash_messages'])) {
		foreach ($_SESSION['flash_messages'] as $type => $message) {
			echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
		}
		unset($_SESSION['flash_messages']);
	}
	?>

	<?php require __DIR__ . '/../../components/admin-list/admin-list.php'; ?>
</div>
