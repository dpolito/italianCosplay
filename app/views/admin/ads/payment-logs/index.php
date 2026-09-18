<?php
$adminList = [
	'id' => 'ads-payment-logs-list',
	'endpoint' => '/admin/ads/payments/logs/data',
	'csrfToken' => $_SESSION['csrf_token'] ?? '',
	'pageSize' => 25,
	'defaultSort' => 'created_at',
	'defaultDirection' => 'desc',
	'search' => true,
	'filters' => [
		[
			'name' => 'action_type',
			'label' => 'Tipo log',
			'type' => 'select',
			'options' => [
				['value' => '', 'label' => 'Tutti'],
				['value' => 'ad_payment_created', 'label' => 'Creati'],
				['value' => 'ad_payment_webhook_received', 'label' => 'Webhook ricevuti'],
				['value' => 'ad_payment_webhook_failed', 'label' => 'Webhook falliti'],
				['value' => 'ad_payment_success', 'label' => 'Pagati'],
				['value' => 'ad_payment_failed', 'label' => 'Falliti'],
				['value' => 'ad_payment_refunded', 'label' => 'Rimborsati'],
			],
		],
	],
	'columns' => [
		['key' => 'created_at', 'label' => 'Data', 'type' => 'date', 'sortable' => true, 'width' => '150px'],
		['key' => 'action_type', 'label' => 'Evento', 'sortable' => true, 'width' => '180px'],
		['key' => 'entity_id', 'label' => 'ID', 'sortable' => true, 'width' => '80px'],
		['key' => 'username', 'label' => 'Utente', 'sortable' => true, 'width' => '160px'],
		['key' => 'success', 'label' => 'Esito', 'formatter' => 'paymentLogSuccess', 'sortable' => true, 'width' => '120px'],
		['key' => 'http_method', 'label' => 'Metodo', 'sortable' => true, 'width' => '100px'],
		['key' => 'ip_address', 'label' => 'IP', 'sortable' => true, 'width' => '150px'],
		['key' => 'payload_summary', 'label' => 'Payload', 'sortable' => false, 'width' => '320px'],
	],
	'actions' => [],
	'emptyState' => [
		'title' => 'Nessun log pagamento presente',
		'message' => 'Non ci sono ancora eventi di pagamento registrati.',
	],
	'noResults' => [
		'title' => 'Nessun log trovato',
		'message' => 'Prova a cambiare filtro o ricerca.',
	],
];
?>

<div class="container mx-auto p-6">
	<div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-6">
		<div>
			<a href="/admin/dashboard" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
				</svg>
				Torna alla Dashboard
			</a>
			<h1 class="mt-4 text-3xl font-semibold text-gray-800">Log pagamenti advertising</h1>
			<p class="mt-2 text-gray-600">Consultazione rapida degli eventi tecnici dei pagamenti pubblicitari.</p>
		</div>
		<a href="/admin/ads/campaigns" class="inline-flex items-center px-4 py-2 bg-blue-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			Torna alle campagne
		</a>
	</div>

	<?php if ($message = \App\Core\Session::getFlash('error')): ?>
		<div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
			<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
		</div>
	<?php endif; ?>

	<?php require __DIR__ . '/../../components/admin-list/admin-list.php'; ?>
</div>
