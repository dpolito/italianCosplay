<?php

class Controller
{
	protected function view($viewName, $data = [])
	{
		// Estrai i dati per renderli disponibili nella vista
		extract($data);

		// Costruisci il percorso completo della vista
		$viewPath = APP_ROOT . '/app/views/' . $viewName . '.php';

		if (file_exists($viewPath)) {
			// Inizia l'output buffering per catturare il contenuto della vista
			ob_start();
			require_once $viewPath;
			$content = ob_get_clean(); // Cattura il contenuto della vista

			// Determina quale layout usare
			$layoutPath = APP_ROOT . '/app/views/layouts/default.php'; // Layout predefinito

			// Se la vista è nell'area admin, usa il layout admin.php
			if (strpos($viewName, 'admin/') === 0) {
				$layoutPath = APP_ROOT . '/app/views/layouts/admin.php';
			}

			// Includi il layout, passando il contenuto della vista
			// Il layout userà la variabile $content_for_layout per includere il contenuto.
			$content_for_layout = $content; // Rende il contenuto disponibile al layout
			require_once $layoutPath;

		} else {
			// Gestisci il caso in cui la vista non esista
			die("Vista '$viewName' non trovata.");
		}
	}

	protected function redirect($url)
	{
		header("Location: " . $url);
		exit();
	}
}
