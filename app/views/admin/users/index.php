<?php

$adminList = [
	'id' => 'users-list',
	'endpoint' => '/admin/users/data',
	'csrfToken' => $_SESSION['csrf_token'] ?? '',
	'detailPanel' => true,
	'detailEndpoint' => '/admin/users/detail/{id}',
	'detailRenderer' => 'user',
	'rowEdit' => '/admin/users/edit/{id}',
	'pageSize' => 25,
	'defaultSort' => 'id',
	'defaultDirection' => 'desc',
	'search' => true,
	'columns' => [
		['key' => 'id', 'label' => 'ID', 'sortable' => true, 'width' => '70px'],
		['key' => 'username', 'label' => 'Username', 'sortable' => true, 'width' => '220px'],
		['key' => 'email', 'label' => 'Email', 'sortable' => true, 'width' => '280px'],
		['key' => 'role_name', 'label' => 'Ruolo', 'sortable' => true, 'width' => '140px'],
		['key' => 'verified', 'label' => 'Verificato', 'sortable' => true, 'width' => '120px', 'formatter' => 'userVerified'],
		['key' => 'privacy_accepted_at', 'label' => 'Privacy', 'sortable' => true, 'width' => '130px', 'formatter' => 'userPrivacyConsent'],
		['key' => 'age_declared_adult', 'label' => '18+', 'sortable' => true, 'width' => '90px', 'formatter' => 'userAgeDeclaration'],
		['key' => 'marketing_opt_in', 'label' => 'Newsletter', 'sortable' => true, 'width' => '130px', 'formatter' => 'userMarketingConsent'],
	],
	'actions' => [
		['key' => 'edit', 'label' => 'Modifica'],
		['key' => 'delete', 'label' => 'Elimina', 'confirm' => 'Sei sicuro di voler eliminare questo utente?'],
	],
	'emptyState' => [
		'title' => 'Nessun utente presente',
		'message' => 'Non ci sono ancora utenti registrati.',
		'action' => [
			'url' => '/admin/users/create',
			'label' => 'Crea utente',
		],
	],
	'noResults' => [
		'title' => 'Nessun utente trovato',
		'message' => 'Modifica i criteri di ricerca oppure rimuovi i filtri applicati.',
	],
];

?>

<div class="container mx-auto p-6">
	<div class="mb-6 flex justify-between items-center">
		<a href="/admin/dashboard" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
			</svg>
			Torna alla Dashboard
		</a>
		<a href="/admin/users/create" class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
			</svg>
			Crea Nuovo Utente
		</a>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Gestione Utenti</h1>

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
