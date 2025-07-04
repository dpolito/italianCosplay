<?php

// Home Page
$router->get('/', 'HomeController@index');

// Eventi
$router->get('/events', 'EventController@index'); // Lista eventi approvati
$router->get('/events/create', 'EventController@create'); // Form per segnalare un evento
$router->get('/events/{id}', 'EventController@show'); // Dettaglio evento
$router->post('/events', 'EventController@store'); // Salva nuovo evento

// Rotte per l'amministrazione degli Eventi (richiederebbe autenticazione e ruolo admin)
// Nota: queste rotte puntano ancora a EventController. Assicurati che EventController
// abbia i controlli di ruolo appropriati o sposta la logica in AdminController se preferisci.
$router->get('/admin/events/pending', 'EventController@pending'); // Lista eventi in attesa di approvazione
$router->get('/events/{id}/edit', 'EventController@edit'); // Form per modificare evento
$router->post('/events/{id}/update', 'EventController@update'); // Aggiorna evento
$router->post('/events/{id}/delete', 'EventController@delete'); // Elimina evento (per admin)
$router->post('/events/{id}/approve', 'EventController@approve'); // Approva evento (per admin)

// Autenticazione (usando AuthController)
$router->get('/login', 'AuthController@showLoginForm');
$router->post('/login', 'AuthController@login'); // Gestisce l'invio del form di login
$router->get('/register', 'AuthController@showRegisterForm');
$router->post('/register', 'AuthController@register'); // Gestisce l'invio del form di registrazione
$router->post('/logout', 'AuthController@logout'); // Logout (POST è più sicuro per prevenire CSRF)

// Rotte per l'area amministrativa generale e Gestione Utenti (usando AdminController)
$router->get('/admin/dashboard', 'AdminController@dashboard'); // Dashboard Admin

// Gestione Utenti (CRUD)
$router->get('/admin/users', 'AdminController@users'); // Lista di tutti gli utenti
$router->get('/admin/users/create', 'AdminController@createUser'); // Mostra il form per creare un nuovo utente
$router->post('/admin/users/store', 'AdminController@storeUser'); // Salva i dati del nuovo utente
$router->get('/admin/users/edit/(\d+)', 'AdminController@editUser'); // Mostra il form per modificare un utente specifico (ID numerico)
$router->post('/admin/users/update/(\d+)', 'AdminController@updateUser'); // Aggiorna i dati di un utente specifico (ID numerico)
$router->post('/admin/users/delete/(\d+)', 'AdminController@deleteUser'); // Elimina un utente specifico (ID numerico)
