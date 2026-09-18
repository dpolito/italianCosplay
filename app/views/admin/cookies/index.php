<div class="container mx-auto p-6">
	<div class="mb-6 flex justify-between items-center">
		<a href="/admin/dashboard" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">Torna alla Dashboard</a>
		<a href="/admin/cookies/create" class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 transition duration-300 ease-in-out">Nuova versione</a>
	</div>
	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Gestione Cookie Policy</h1>
	<?php
	$adminList = [
		'id' => 'cookie-list',
		'endpoint' => '/admin/cookies/data',
		'csrfToken' => $_SESSION['csrf_token'] ?? '',
		'rowEdit' => '/admin/cookies/edit/{id}',
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
			'title' => 'Nessuna versione cookie presente',
			'message' => 'Crea la prima versione della cookie policy.',
			'action' => ['url' => '/admin/cookies/create', 'label' => 'Crea versione'],
		],
	];
	?>
	<?php require __DIR__ . '/../components/admin-list/admin-list.php'; ?>
</div>
