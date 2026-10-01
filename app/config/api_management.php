<?php
declare(strict_types=1);

return [
	'environments' => ['test', 'live'],
	'default_environment' => app_env_value('API_ENVIRONMENT', 'live'),
	'scopes' => [
		'events:search' => 'Cercare eventi approvati',
		'events:read' => 'Leggere il dettaglio evento',
		'events:photos' => 'Leggere foto collegate a un evento',
		'locations:read' => 'Leggere regioni, province e comuni',
		'agenda:read' => 'Leggere dati Agenda',
		'agenda:write' => 'Scrivere dati Agenda',
		'photos:upload' => 'Caricare foto',
	],
	'defaults' => [
		'requests_per_minute' => 60,
		'requests_per_day' => 5000,
	],
	'security' => [
		'max_results_per_request' => 20,
		'max_search_date_range_days' => 90,
		'max_page' => 5,
		'log_retention_days' => 90,
	],
];
