<?php

class Controller
{
	protected function view($path, $data = [])
	{
		// La variabile $content sarà usata nel layout principale
		// per inserire il contenuto specifico della vista.
		ob_start();
		extract($data); // Estrae gli elementi dell'array come variabili
		require_once APP_ROOT . '/app/views/' . $path . '.php';
		$content = ob_get_clean();

		// Carica il layout principale
		require_once APP_ROOT . '/app/views/layouts/default.php';
	}

	protected function redirect($url)
	{
		header("Location: " . $url);
		exit();
	}
}
