<?php
/**
 * Configurazione API pubbliche ItalianCosplay
 * NON versionare questo file su repository pubblici
 */

return [

	// Chiavi API abilitate
	'api_keys' => [

		// Chiave principale (sito partner)
		'f8G$kP1z@Qw7!eR4#Ty9&Lm2*Xs0^Vb6%Ua3(Hz5)Nd8Jc' => [
			'name'        => 'Partner ufficiale',
			'active'      => true,
			'rate_limit'  => 60, // richieste al minuto
		],


		// Altra chiave (es. app mobile)
		'MOBILE_APP_KEY_987654' => [
			'name'        => 'App Mobile',
			'active'      => false,
			'rate_limit'  => 120,
		],

		// Chiave disabilitata (esempio)
		'OLD_KEY_DISABLED' => [
			'name'        => 'Vecchio client',
			'active'      => false,
			'rate_limit'  => 0,
		],
	],

	// Impostazioni generali API
	'default_rate_limit' => 30,
];
