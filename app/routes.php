<?php

// Home Page
$router->get('/', 'HomeController@index');

// Eventi Pubblici
$router->get('/events', 'EventController@index'); // Lista eventi approvati
$router->get('/events/create', 'EventController@create'); // Form per segnalare un evento
$router->post('/events', 'EventController@store'); // Salva nuovo evento
$router->get('/events/(\d+)', 'EventController@show'); // Dettaglio evento pubblico (ID numerico)

// Autenticazione (usando AuthController)
$router->get('/login', 'AuthController@showLoginForm');
$router->post('/login', 'AuthController@login'); // Gestisce l'invio del form di login
$router->get('/register', 'AuthController@showRegisterForm');
$router->post('/register', 'AuthController@register'); // Gestisce l'invio del form di registrazione
$router->post('/logout', 'AuthController@logout'); // Logout (POST è più sicuro per prevenire CSRF)

// Rotte per l'Area Amministrativa Generale
$router->get('/admin/dashboard', 'AdminController@dashboard'); // Dashboard Admin

// Gestione Utenti (CRUD per Admin)
$router->get('/admin/users', 'AdminController@users'); // Lista di tutti gli utenti
$router->get('/admin/users/create', 'AdminController@createUser'); // Mostra il form per creare un nuovo utente
$router->post('/admin/users/store', 'AdminController@storeUser'); // Salva i dati del nuovo utente
$router->get('/admin/users/edit/(\d+)', 'AdminController@editUser'); // Mostra il form per modificare un utente specifico (ID numerico)
$router->post('/admin/users/update/(\d+)', 'AdminController@updateUser'); // Aggiorna i dati di un utente specifico (ID numerico)
$router->post('/admin/users/delete/(\d+)', 'AdminController@deleteUser'); // Elimina un utente specifico (ID numerico)

// Gestione Eventi (CRUD per Admin)
$router->get('/admin/events/pending', 'EventController@pending'); // Lista eventi in attesa di approvazione (ADMIN)
$router->get('/admin/events/show/(\d+)', 'EventController@adminShow'); // Dettaglio evento per admin (ID numerico)
$router->get('/admin/events/edit/(\d+)', 'EventController@edit'); // Form per modificare evento (ADMIN)
$router->post('/admin/events/update/(\d+)', 'EventController@update'); // Aggiorna evento (ADMIN)
$router->post('/admin/events/delete/(\d+)', 'EventController@delete'); // Elimina evento (ADMIN)
$router->post('/admin/events/approve/(\d+)', 'EventController@approve'); // Approva evento (ADMIN)
$router->get('/admin/events/all', 'EventController@allEvents'); // Lista tutti gli eventi (approvati e non) per admin

// Rotte API per Regioni, Province, Comuni
$router->get('/api/regioni', 'ApiController@getRegioni');
$router->get('/api/province/(\d+)', 'ApiController@getProvinceByRegione');
$router->get('/api/comuni/(\d+)', 'ApiController@getComuniByProvincia');

//
