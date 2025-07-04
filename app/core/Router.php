<?php

class Router
{
	protected $routes = [];

	public function addRoute($method, $uri, $action)
	{
		// Converti URI con parametri dinamici in regex
		$uri = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_]+)', $uri);
		$this->routes[$method][$uri] = $action;
	}

	public function get($uri, $action)
	{
		$this->addRoute('GET', $uri, $action);
	}

	public function post($uri, $action)
	{
		$this->addRoute('POST', $uri, $action);
	}

	public function dispatch($requestUri, $requestMethod)
	{
		// Rimuovi la query string dalla URI
		$requestUri = strtok($requestUri, '?');

		foreach ($this->routes[$requestMethod] as $uriPattern => $action) {
			// Aggiungi delimitatori regex se non già presenti
			$pattern = str_replace('/', '\/', $uriPattern);
			if (preg_match("/^$pattern$/", $requestUri, $matches)) {
				array_shift($matches); // Rimuovi il match completo, lascia solo i parametri

				list($controllerName, $methodName) = explode('@', $action);

				$controllerFile = APP_ROOT . '/app/controllers/' . $controllerName . '.php';

				if (file_exists($controllerFile)) {
					require_once $controllerFile;
					$controller = new $controllerName();

					if (method_exists($controller, $methodName)) {
						call_user_func_array([$controller, $methodName], [$matches]);
						return;
					}
				}
			}
		}

		// Se nessuna rotta corrisponde, mostra una pagina 404
		header("HTTP/1.0 404 Not Found");
		require_once APP_ROOT . '/app/views/errors/404.php';
		exit();
	}
}
