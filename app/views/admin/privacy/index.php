<div class="container mx-auto p-6">
	<div class="mb-6 flex justify-between items-center">
		<a href="/admin/dashboard" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
			</svg>
			Torna alla Dashboard
		</a>
		<a href="/admin/privacy/create" class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 transition duration-300 ease-in-out">
			Nuova versione
		</a>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">
		Gestione Privacy
	</h1>

	<?php
	$adminList = [
		'id' => 'privacy-list',
		'endpoint' => '/admin/privacy/data',
		'csrfToken' => $_SESSION['csrf_token'] ?? '',
		'rowEdit' => '/admin/privacy/edit/{id}',
		'pageSize' => 25,
		'defaultSort' => 'version_number',
		'defaultDirection' => 'desc',
		'search' => true,
		'columns' => [
			['key' => 'version_number', 'label' => 'Versione', 'sortable' => true, 'width' => '120px'],
			['key' => 'title', 'label' => 'Titolo', 'sortable' => true, 'width' => '360px'],
			['key' => 'published_at', 'label' => 'Pubblicata', 'sortable' => true, 'width' => '180px'],
			['key' => 'is_active', 'label' => 'Stato', 'sortable' => true, 'width' => '120px'],
			['key' => 'created_at', 'label' => 'Creata', 'sortable' => true, 'width' => '180px'],
		],
		'actions' => [
			['key' => 'edit', 'label' => 'Modifica'],
		],
		'emptyState' => [
			'title' => 'Nessuna versione privacy presente',
			'message' => 'Crea la prima versione dell\'informativa privacy.',
			'action' => [
				'url' => '/admin/privacy/create',
				'label' => 'Crea versione',
			],
		],
		'noResults' => [
			'title' => 'Nessuna versione trovata',
			'message' => 'Modifica i criteri di ricerca oppure rimuovi i filtri applicati.',
		],
	];
	?>

	<?php require __DIR__ . '/../components/admin-list/admin-list.php'; ?>
</div>
