<?php
$adminList = [
	'id' => 'ads-campaigns-list',
	'endpoint' => '/admin/ads/campaigns/data',
	'csrfToken' => $_SESSION['csrf_token'] ?? '',
	'detailPanel' => true,
	'detailEndpoint' => '/admin/ads/campaigns/detail/{id}',
	'detailRenderer' => 'adCampaign',
	'rowEdit' => '/admin/ads/campaigns/{id}',
	'pageSize' => 25,
	'defaultSort' => 'id',
	'defaultDirection' => 'desc',
	'search' => true,
	'columns' => [
		['key' => 'id', 'label' => 'ID', 'sortable' => true, 'width' => '70px'],
		['key' => 'banner_title', 'label' => 'Campagna', 'sortable' => true, 'width' => '320px'],
		['key' => 'position_name', 'label' => 'Posizione', 'sortable' => true, 'width' => '220px'],
		['key' => 'approval_status', 'label' => 'Stato', 'sortable' => true, 'width' => '130px'],
		['key' => 'start_date', 'label' => 'Inizio', 'type' => 'date', 'sortable' => true, 'width' => '120px'],
		['key' => 'end_date', 'label' => 'Fine', 'type' => 'date', 'sortable' => true, 'width' => '120px'],
		['key' => 'impressions', 'label' => 'Impression', 'sortable' => true, 'width' => '120px'],
		['key' => 'clicks', 'label' => 'Click', 'sortable' => true, 'width' => '100px'],
		['key' => 'ctr', 'label' => 'CTR', 'sortable' => true, 'width' => '90px'],
	],
	'actions' => [
		['key' => 'view', 'label' => 'Apri'],
	],
	'emptyState' => [
		'title' => 'Nessuna campagna presente',
		'message' => 'Non ci sono ancora campagne pubblicitarie da moderare.',
		'action' => [
			'url' => '/dashboard/ads/campaigns/create',
			'label' => 'Vai al dashboard sponsor',
		],
	],
	'noResults' => [
		'title' => 'Nessuna campagna trovata',
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
		<a href="/admin/ads/positions" class="inline-flex items-center px-4 py-2 bg-blue-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			Gestisci posizioni
		</a>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Campagne pubblicitarie</h1>

	<?php if ($message = \App\Core\Session::getFlash('error')): ?>
		<div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
			<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
		</div>
	<?php endif; ?>
	<?php if ($message = \App\Core\Session::getFlash('success')): ?>
		<div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
			<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
		</div>
	<?php endif; ?>

	<?php require __DIR__ . '/../../components/admin-list/admin-list.php'; ?>
</div>
