<?php

// Home Page
$router->get('/', 'HomeController@index');

// Eventi
$router->get('/events', 'EventController@index'); // Lista eventi approvati
$router->get('/events/create', 'EventController@create'); // Form per segnalare un evento
$router->get('/events/{id}', 'EventController@show'); // Dettaglio evento
$router->post('/events', 'EventController@store'); // Salva nuovo evento

// Rotte per l'amministrazione (richiederebbe autenticazione e ruolo admin)
$router->get('/admin/events/pending', 'EventController@pending'); // Lista eventi in attesa di approvazione
$router->get('/events/{id}/edit', 'EventController@edit'); // Form per modificare evento
$router->post('/events/{id}/update', 'EventController@update'); // Aggiorna evento
$router->post('/events/{id}/delete', 'EventController@delete'); // Elimina evento (per admin)
$router->post('/events/{id}/approve', 'EventController@approve'); // Approva evento (per admin)

// Autenticazione (esempi, implementa User Model e AuthController)
$router->get('/login', 'AuthController@showLoginForm');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@showRegisterForm');
$router->post('/register', 'AuthController@register');
$router->post('/logout', 'AuthController@logout');
