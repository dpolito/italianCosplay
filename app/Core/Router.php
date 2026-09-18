<?php
namespace App\Core;
use App\Helpers\DateHelper;
use App\Middleware\AuthMiddleware;
use App\Middleware\PermissionMiddleware;
use Exception;
use function class_exists;
use function date;use function strtotime;use function var_dump;
use const PHP_URL_PATH;

class Router
{
	protected array $routes = [];
	protected $currentPageName = null;

	/**
	 * Aggiunge una rotta al router.
	 *
	 * @param string $method Il metodo HTTP (GET, POST, ecc.).
	 * @param string $uri Il pattern URI della rotta (es. '/', '/users/{id}').
	 * @param array $handler Un array contenente [NomeController, NomeMetodo].
	 */
	private function addRoute($method, $uri, $action, array $middlewares = [])
	{
		$this->routes[] = [
			'method' => $method,
			'uri' => $uri,
			'action' => $action,
			'middlewares' => $middlewares
		];
	}

	/**
	 * Aggiunge una rotta GET.
	 *
	 * @param string $uri
	 * @param        $action
	 * @param array  $middlewares
	 */
	public function get(string $uri, $handler, array $middlewares = []): void
	{
		if (is_array($handler) && isset($handler['uses'])) {
			// già nel formato nuovo
			$this->routes['GET'][$uri] = $handler;
		} else {
			// compatibilità legacy
			$this->routes['GET'][$uri] = [
				'uses' => $handler,
				'middlewares' => $middlewares
			];
		}
	}

	public function post(string $uri, $handler, array $middlewares = []): void
	{
		if (is_array($handler) && isset($handler['uses'])) {
			$this->routes['POST'][$uri] = $handler;
		} else {
			$this->routes['POST'][$uri] = [
				'uses' => $handler,
				'middlewares' => $middlewares
			];
		}
	}

	// Puoi aggiungere altri metodi come put(), delete() se necessario

	/**
	 * Dispatches la richiesta corrente al controller e metodo appropriati.
	 */
	public function dispatch(): void
	{
		$method = $_SERVER['REQUEST_METHOD'];


		// URI richiesta
		$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

		// Normalizza base path (se hai sottocartelle o localhost:porta)
		$baseUrlPath = parse_url('http://localhost:8080', PHP_URL_PATH) ?? '';

		if ($baseUrlPath !== '' && strpos($uri, $baseUrlPath) === 0) {
			$uri = substr($uri, strlen($baseUrlPath));
			if ($uri === '') $uri = '/';
		}


		// Nessuna rotta per il metodo
		if (!isset($this->routes[$method])) {
			$this->handleNotFound();
			return;
		}

		foreach ($this->routes[$method] as $route_pattern => $handler) {

			// 🔹 Normalizza handler (legacy + nuovo formato)
			if (isset($handler['uses'])) {
				$controllerName = $handler['uses'][0];
				$methodName     = $handler['uses'][1];
				$middlewares    = $handler['middlewares'] ?? [];
			} else {
				$controllerName = $handler[0];
				$methodName     = $handler[1];
				$middlewares    = $handler[2] ?? [];
			}

			// 🔹 Compila pattern route
			$pattern = $route_pattern;
			$pattern = preg_replace('/\{id\}/', '(\d+)', $pattern);
			$pattern = preg_replace('/\{masterId\}/', '(\d+)', $pattern);
			$pattern = preg_replace('/\{[a-zA-Z_]+\}/', '([^/]+)', $pattern);
			$pattern = '#^' . $pattern . '$#';

			if (preg_match($pattern, $uri, $matches)) {

				// 🔥 ESECUZIONE MIDDLEWARE
				foreach ($middlewares as $middleware) {

					if (is_string($middleware)) {

						//$middlewareClass = 'App\\Middleware\\' . $middleware;
						if (str_contains($middleware, '\\')) {
							$middlewareClass = $middleware; // già FQCN
						} else {
							$middlewareClass = 'App\\Middleware\\' . $middleware;
						}

						if (!class_exists($middlewareClass)) {
							throw new Exception("Middleware non trovato: {$middlewareClass}");
						}

						(new $middlewareClass())->handle();

					} elseif (is_array($middleware)) {
						$class = $middleware[0];
						$param = $middleware[1];

						if (!class_exists($class)) {
							throw new Exception("Middleware non trovato: {$class}");
						}

						(new $class($param))->handle();
					}
				}

				// 🔹 Namespace completo controller
				$controllerClass = 'App\\Controllers\\' . $controllerName;



				if (!class_exists($controllerClass)) {
					error_log("ERRORE Router: Classe controller non trovata: " . $controllerClass);
					$this->handleNotFound();
					return;
				}

				$controller = new $controllerClass();


				if (!method_exists($controller, $methodName)) {
					error_log("ERRORE Router: Metodo non trovato: " . $controllerClass . "::" . $methodName);
					$this->handleNotFound();
					return;
				}

				// Rimuove match completo (opzionale, ma consigliato)
				array_shift($matches);

				$controller->{$methodName}($matches);
				return;
			}
		}

		// Nessuna rotta trovata
		error_log("Router: Nessuna rotta per URI: {$uri} [{$method}]");
		$this->handleNotFound();
	}

	/**
	 * Gestisce le richieste non trovate (404).
	 */
	protected function handleNotFound(): void
	{
		header("HTTP/1.0 404 Not Found");
		require_once APP_ROOT . '/app/views/404.php'; // Assicurati di avere un file 404.php
		exit();
	}

	/**
	 * Restituisce il nome logico della pagina corrente in base alle rotte definite.
	 */
	public function getCurrentPageName(): ?string
	{
		$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		$method = $_SERVER['REQUEST_METHOD'];

		if (!isset($this->routes[$method])) {
			return null;
		}

		foreach ($this->routes[$method] as $route_pattern => $handler) {
			// Converte i placeholder in regex come già fai nel dispatch
			$pattern = preg_replace('/\{id\}/', '(\d+)', $route_pattern);
			$pattern = preg_replace('/\{masterId\}/', '(\d+)', $pattern);
			$pattern = preg_replace('/\{provincia_slug\}/', '([a-zA-Z0-9-]+)', $pattern);
			$pattern = preg_replace('/\{regione_slug\}/', '([a-zA-Z0-9-]+)', $pattern);
			$pattern = preg_replace('/\{comune_slug\}/', '([a-zA-Z0-9-]+)', $pattern);
			$pattern = preg_replace('/\{slug\}/', '([a-zA-Z0-9-]+)', $pattern);
			$pattern = '#^' . $pattern . '$#';

			if (preg_match($pattern, $uri)) {
				// Restituisci un nome logico basato sul controller e metodo
				if (isset($handler['uses'])) {
					$controller = $handler['uses'][0];
					$methodName = $handler['uses'][1];
				} else {
					// compatibilità vecchia
					$controller = $handler[0];
					$methodName = $handler[1];
				}
				$currentPage = $controller . '_' . $methodName;
				$pageNames = [
					'HomeController_index' => 'home',
					'EventController_index' => 'lista-eventi',
					'EventController_show' => 'dettaglio-evento',
					'AuthController_showLoginForm' => 'login',
					'AuthController_showRegisterForm' => 'register',
					'AuthController_agendaLanding' => 'agenda-cosplay',
					'AuthController_cosplanLanding' => 'cosplan',
					'EventController_create' => 'segnalazione-evento',
					'EventController_weekend' => 'weekend',
					'EventController_weekendSpecifico' => 'weekend_specifico',
					'EventController_mese' => 'mese',
					'EventController_meseSpecifico' => 'mese_specifico',
					'EventController_masterIndex' => 'eventi-master-lista',
					'EventController_masterShow' => 'eventi-master-dettaglio',
					'EventController_segnalaEvento' => 'segnalaevento',
					'GuestController_index' => 'ospiti_lista',
					'GuestController_show' => 'ospiti_dettaglio',
					'BlogController_index' => 'blog_lista',
					'BlogController_show' => 'blog_dettaglio',
					'BlogController_category' => 'blog_categoria',
					'ProfileController_index' => 'profili-pubblici',
					'ProfileController_publicProfile' => 'profilo-pubblico',
					'OrganizationController_show' => 'organizzazione-dettaglio',
					'OrganizationController_index' => 'organizzazioni-lista',
					'HomeController_organizersLanding' => 'organizzatori-eventi-cosplay',
					'FaqController_index' => 'faq',
				];
				if (isset($this->controllerInstance) && method_exists($this->controllerInstance, 'getPageNameOverride')) {
					$override = $this->controllerInstance->getPageNameOverride();
					if ($override) {
						return $override;
					}
				}
				$logicalPage = $pageNames[$currentPage] ?? 'pagina-sconosciuta';

				return strtolower($logicalPage);
			}
		}
		// 👇 controllo override dinamico


		return null; // Nessuna corrispondenza
	}
	public static function seoMetaTags($router, $data = []) {
		$page = $router->getCurrentPageName();
		$meta_fb = '';
		switch ($page) {
			case 'faq':
				$title = 'FAQ e guida alle funzionalità | ItalianCosplay';
				$description = 'Trova risposte e guide per usare eventi, profilo, preferiti e tutte le funzionalità disponibili su ItalianCosplay.';
				$meta_fb = '<meta property="og:type" content="website">' . "\n"
					. '<meta property="og:site_name" content="ItalianCosplay">' . "\n"
					. '<meta property="og:title" content="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">' . "\n"
					. '<meta property="og:description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '">' . "\n"
					. '<meta property="og:url" content="' . htmlspecialchars($data['canonicalUrl'] ?? '', ENT_QUOTES, 'UTF-8') . '">';
				break;
			case 'lista-eventi':
				if (!empty($data['comune_nome'])) {
					$title = "Eventi Cosplay a " . $data['comune_nome'] . " | ItalianCosplay";
					$description = "Scopri tutti gli eventi cosplay a " . $data['comune_nome'] . ": fiere, raduni e comics aggiornati su ItalianCosplay.";
				} elseif (!empty($data['provincia_nome'])) {
					$title = "Eventi Cosplay in provincia di " . $data['provincia_nome'] . " | ItalianCosplay";
					$description = "Consulta il calendario degli eventi cosplay in provincia di " . $data['provincia_nome'] . ": raduni, fiere e comics 2025.";
				} elseif (!empty($data['regione_nome'])) {
					$title = "Eventi Cosplay in " . $data['regione_nome'] . " | ItalianCosplay";
					$description = "Trova tutti gli eventi cosplay in " . $data['regione_nome'] . ": fiere del fumetto, raduni e convention cosplay aggiornate.";
				} else {
					$title = "Eventi Cosplay Italia 2026 – Calendario Fiere e Comics | ItalianCosplay";
					$description = "Consulta il calendario completo degli eventi cosplay in Italia: raduni, comics e fiere 2025 aggiornati su ItalianCosplay.";
				}
				break;
			case 'weekend_specifico':
				$title = "Eventi cosplay weekend ".$data['weekend']." : tutte le fiere in Italia | ItalianCosplay";
				$description = "Scopri gli eventi cosplay del weekend ".$data['weekend'].": fiere del fumetto, comics, manga e cosplay in tutta Italia. Date, città ed eventi aggiornati.";
				break;
			case 'weekend':
				$title = "Eventi Cosplay nel Weekend in Italia | Fiere e Raduni | ItalianCosplay";
				$description = "Scopri gli eventi cosplay nel weekend in Italia: fiere del fumetto, convention, raduni, contest e appuntamenti nerd. Trova il prossimo weekend cosplay.";
				break;
			case 'blog_lista':
				$title = "Notizie Cosplay e Guide per Cosplayer | ItalianCosplay";
				$description = "Leggi articoli su cosplay, eventi in Italia, fiere, guide per costumi, personaggi e tutorial. Il blog ufficiale di ItalianCosplay.";
				break;
			case 'blog_categoria':
				$title = $data['blog_categoria']['seo_title'];
				$description = $data['blog_categoria']['seo_description'];
				break;
			case 'blog_dettaglio':
				$title = $data['blog']['meta_title'];
				$description = $data['blog']['meta_description'];
				break;
			case 'ospiti_lista':
				$title = "Ospiti Eventi Cosplay in Italia | Doppiatori, Cosplayer e Creator | ItalianCosplay";
				$description = "Scopri tutti gli ospiti degli eventi cosplay in Italia: cosplayer, doppiatori, creator e artisti presenti a fiere e festival. Schede aggiornate e profili ufficiali.";
				break;
			case 'ospiti_dettaglio':
				$title = $data['guest']['name']." ospite eventi cosplay | biografia e fiere in Italia | ItalianCosplay";
				$description = "Scheda dedicata a ".$data['guest']['name']." con biografia, profili social ed eventi cosplay in Italia a cui ha partecipato tra fiere, festival e convention.";
				break;
			case 'segnalaevento':
				$title = "Segnala un Evento Cosplay | Aggiungilo al Calendario ItalianCosplay";
				$description = "Hai un evento cosplay da pubblicare? Segnalalo ora e aggiungilo al calendario di ItalianCosplay. È veloce, gratuito e aperto a tutti gli organizzatori.";
				break;
			case 'mese':
				$title = "Eventi Cosplay Mese per Mese in Italia | Calendario 2026";
				$description = "Scopri il calendario degli eventi cosplay in Italia mese per mese: fiere del fumetto, festival anime, raduni e appuntamenti nerd aggiornati.";
				break;
			case 'mese_specifico':
				$title = "Eventi Cosplay ".$data['mese'].": i migliori eventi da non perdere in Italia";
				$description = "Quali sono i migliori eventi cosplay di ".$data['mese']."? Scopri fiere, festival e raduni in tutta Italia: ecco i top eventi del mese e le novità più interessanti.";
				break;
			case 'eventi-master-lista':
				$title = "Eventi e edizioni cosplay in Italia | ItalianCosplay";
				$description = "Scopri gli eventi e le edizioni cosplay in Italia con tutte le varianti annuali collegate e le informazioni principali raccolte in un unico posto.";
				break;
			case 'eventi-master-dettaglio':
				if (!empty($data['event_master'])) {
					$eventMaster = $data['event_master'];
					$editionsCount = (int) ($data['event_master_count'] ?? 0);
					$title = ($eventMaster['nome'] ?? 'Evento e edizioni') . ($editionsCount > 0 ? " - " . $editionsCount . " edizioni" : '') . " | ItalianCosplay";
					$description = "Scopri " . ($eventMaster['nome'] ?? 'questo evento') . ": edizioni collegate, informazioni utili, sito ufficiale e pagina dedicata.";
				} else {
					$title = "Evento e edizioni cosplay | ItalianCosplay";
					$description = "Scopri un evento e tutte le sue edizioni collegate con informazioni principali e link utili.";
				}
				break;
			case 'organizzazione-dettaglio':
				$organization = $data['organization'] ?? [];
				$organizationName = $organization['name'] ?? 'Organizzazione';
				$title = $data['organization_meta_title'] ?? ($organizationName . " | Organizzazione cosplay | ItalianCosplay");
				$description = $data['organization_meta_description'] ?? ("Scopri " . $organizationName . ": organizzazione, eventi master ed edizioni cosplay pubblicate su ItalianCosplay.");
				$organizationImage = $data['organization_image'] ?? '';
				$meta_fb = '<meta property="og:type" content="profile">' . "\n"
					. '<meta property="og:site_name" content="ItalianCosplay">' . "\n"
					. '<meta property="og:title" content="' . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta property="og:description" content="' . htmlspecialchars($description, ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta property="og:url" content="' . htmlspecialchars($data['canonicalUrl'] ?? '', ENT_QUOTES, "UTF-8") . '">' . "\n"
					. ($organizationImage !== '' ? '<meta property="og:image" content="' . htmlspecialchars($organizationImage, ENT_QUOTES, "UTF-8") . '">' . "\n" : '')
					. '<meta name="twitter:card" content="summary_large_image">' . "\n"
					. '<meta name="twitter:title" content="' . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta name="twitter:description" content="' . htmlspecialchars($description, ENT_QUOTES, "UTF-8") . '">' . "\n"
					. ($organizationImage !== '' ? '<meta name="twitter:image" content="' . htmlspecialchars($organizationImage, ENT_QUOTES, "UTF-8") . '">' : '');
				break;
			case 'organizzazioni-lista':
				$title = "Organizzazioni cosplay in Italia | ItalianCosplay";
				$description = "Scopri le organizzazioni che curano eventi, fiere e appuntamenti cosplay pubblicati su ItalianCosplay.";
				break;
			case 'pagina-sconosciuta':
				if (!empty($data['evento'])) {
					$evento = $data['evento'];
					$defaultTitle = $evento['titolo'] . " "
						. date("Y", strtotime($evento['data_inizio']))
						. ": date, programma, biglietti e info"
						. " | ItalianCosplay";
					$title = trim((string) ($evento['seo_title'] ?? '')) ?: $defaultTitle;

					//Teramo Comix 2026: date 8-10 maggio, location, programma cosplay, stand e info utili. Scopri cosa fare e come partecipare.
					$dataDescription = DateHelper::formatEventoPeriodo(
						$evento['data_inizio'],
						$evento['data_fine']
					);

					/*$description = "Scopri " . $evento['titolo'] . " a " . $evento['comune_nome']
						. ": date, luogo, cosplay, fumetti e tanto divertimento. "
						. "Dettagli e info su ItalianCosplay!";*/
					$defaultDescription = $evento['titolo'] . " "
						. date("Y", strtotime($evento['data_inizio']))
						. ": ".$dataDescription.", location, programma cosplay, stand e info utili. Scopri cosa fare e come partecipare.";
					$description = trim((string) ($evento['seo_description'] ?? '')) ?: $defaultDescription;
					$meta_fb = '<!-- Open Graph (Facebook / WhatsApp / Messenger) -->
							    <meta property="og:type" content="website">
							    <meta property="og:site_name" content="ItalianCosplay">
							    <meta property="og:title" content="'.$title.'">
							    <meta property="og:description" content="'.$description.'">
							    <meta property="og:url" content="https://www.italiancosplay.it/eventi-cosplay/' . $evento['slug']. '">
							    <meta property="og:image" content="https://www.italiancosplay.it'.$evento['immagine'].'">
							
							    <!-- Twitter Card (opzionale ma utile) -->
							    <meta name="twitter:card" content="summary_large_image">
							    <meta name="twitter:title" content="'.$title.'">
							    <meta name="twitter:description" content="'.$description.'">
							    <meta name="twitter:image" content="https://www.italiancosplay.it'.$evento['immagine'].'">';

				}elseif (!empty($data['regione_nome'])) {
					$title = "Eventi Cosplay in " . $data['regione_nome'] . " | ItalianCosplay";
					$description = "Trova tutti gli eventi cosplay in " . $data['regione_nome'] . ": fiere del fumetto, raduni e convention cosplay aggiornate.";
				}else {
					$title = "Evento Cosplay | ItalianCosplay";
					$description = "Dettagli evento cosplay su ItalianCosplay.";
				}


				break;

			case 'dettaglio-evento':
				if (!empty($data['evento'])) {
					$evento = $data['evento'];
					$defaultTitle = $evento['titolo'] . " "
						. date("Y", strtotime($evento['data_inizio']))
						. " a " . $evento['comune_nome']
						. " | ItalianCosplay";
					$title = trim((string) ($evento['seo_title'] ?? '')) ?: $defaultTitle;

					$defaultDescription = "Scopri " . $evento['titolo'] . " a " . $evento['comune_nome']
						. ": date, luogo, cosplay, fumetti e tanto divertimento. "
						. "Dettagli e info su ItalianCosplay!";
					$description = trim((string) ($evento['seo_description'] ?? '')) ?: $defaultDescription;
					$meta_fb = '<!-- Open Graph (Facebook / WhatsApp / Messenger) -->
							    <meta property="og:type" content="website">
							    <meta property="og:site_name" content="ItalianCosplay">
							    <meta property="og:title" content="'.$title.'">
							    <meta property="og:description" content="'.$description.'">
							    <meta property="og:url" content="https://www.italiancosplay.it/eventi-cosplay/' . $evento['slug']. '">
							    <meta property="og:image" content="https://www.italiancosplay.it'.$evento['immagine'].'">
							
							    <!-- Twitter Card (opzionale ma utile) -->
							    <meta name="twitter:card" content="summary_large_image">
							    <meta name="twitter:title" content="'.$title.'">
							    <meta name="twitter:description" content="'.$description.'">
							    <meta name="twitter:image" content="https://www.italiancosplay.it'.$evento['immagine'].'">';

				} else {
					$title = "Evento Cosplay | ItalianCosplay";
					$description = "Dettagli evento cosplay su ItalianCosplay.";
				}
				break;

			case 'home':
				$title = "ItalianCosplay | Eventi e Fiere Cosplay in Italia";
				$description = "Scopri tutti gli eventi cosplay in Italia: fiere, raduni, comics e tanto altro. Segnala un evento e crea il tuo calendario cosplay!";
				break;

			case 'segnalazione-evento':
				$title = "Segnala un Evento Cosplay | ItalianCosplay";
				$description = "Aiuta la community segnalando un nuovo evento cosplay in Italia. Aggiungi il tuo evento al calendario di ItalianCosplay!";
				break;

			case 'login':
				$title = "Login | ItalianCosplay";
				$description = "Accedi al tuo account ItalianCosplay per salvare eventi e segnalare nuove fiere cosplay.";
				break;

			case 'register':
				$title = "Registrati | ItalianCosplay";
				$description = "Crea il tuo account su ItalianCosplay per segnalare eventi, salvare fiere e partecipare alla community cosplay.";
				break;

			case 'organizzatori-eventi-cosplay':
				$title = "Organizzatori eventi cosplay: gestisci la scheda | ItalianCosplay";
				$description = "Richiedi gratis la gestione della scheda del tuo evento cosplay su ItalianCosplay e aggiorna date, luogo, immagini e link ufficiali.";
				$meta_fb = '<meta property="og:type" content="website">' . "\n"
					. '<meta property="og:site_name" content="ItalianCosplay">' . "\n"
					. '<meta property="og:title" content="' . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta property="og:description" content="' . htmlspecialchars($description, ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta property="og:url" content="' . htmlspecialchars($data['canonicalUrl'] ?? '', ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta name="twitter:card" content="summary">' . "\n"
					. '<meta name="twitter:title" content="' . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta name="twitter:description" content="' . htmlspecialchars($description, ENT_QUOTES, "UTF-8") . '">';
				break;

			case 'agenda-cosplay':
				$title = "Agenda cosplay personale: salva eventi vicini | ItalianCosplay";
				$description = "Crea la tua agenda cosplay gratuita: salva eventi, segui le zone che frequenti e ritrova gli appuntamenti che non vuoi perdere.";
				$meta_fb = '<meta property="og:type" content="website">' . "\n"
					. '<meta property="og:site_name" content="ItalianCosplay">' . "\n"
					. '<meta property="og:title" content="' . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta property="og:description" content="' . htmlspecialchars($description, ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta property="og:url" content="' . htmlspecialchars($data['canonicalUrl'] ?? '', ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta name="twitter:card" content="summary">' . "\n"
					. '<meta name="twitter:title" content="' . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta name="twitter:description" content="' . htmlspecialchars($description, ENT_QUOTES, "UTF-8") . '">';
				break;

			case 'cosplan':
				$title = "Cosplan gratis: organizza cosplay ed eventi | ItalianCosplay";
				$description = "Organizza gratis il tuo cosplan su ItalianCosplay: pianifica personaggio, costume, preparazione ed eventi cosplay dove portarlo.";
				$meta_fb = '<meta property="og:type" content="website">' . "\n"
					. '<meta property="og:site_name" content="ItalianCosplay">' . "\n"
					. '<meta property="og:title" content="' . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta property="og:description" content="' . htmlspecialchars($description, ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta property="og:url" content="' . htmlspecialchars($data['canonicalUrl'] ?? '', ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta name="twitter:card" content="summary">' . "\n"
					. '<meta name="twitter:title" content="' . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta name="twitter:description" content="' . htmlspecialchars($description, ENT_QUOTES, "UTF-8") . '">';
				break;

			case 'profili-pubblici':
				$title = "Profili cosplay pubblici in Italia | ItalianCosplay";
				$description = "Scopri i profili pubblici della community cosplay italiana e filtra per nome, località, bio e visibilità.";
				break;

			case 'profilo-pubblico':
				$profileUser = $data['user'] ?? [];
				$profileHandle = $profileUser['username'] ?? 'cosplayer';
				$profileName = trim(($profileUser['first_name'] ?? '') . ' ' . ($profileUser['last_name'] ?? ''));
				$title = ($profileName !== '' ? $profileName . ' (@' . $profileHandle . ')' : '@' . $profileHandle) . ' | ItalianCosplay';
				$description = 'Profilo pubblico di @' . $profileHandle . ' su ItalianCosplay: bio, social e informazioni della community cosplay italiana.';

				$profileImage = $profileUser['profile_cover'] ?? ($profileUser['avatar'] ?? '');
				if ($profileImage === '') {
					$profileImage = 'https://www.italiancosplay.it/assets/img/default-avatar.png';
				} elseif (!str_starts_with($profileImage, 'http')) {
					$profileImage = 'https://www.italiancosplay.it' . $profileImage;
				}

				$meta_fb = '<meta property="og:type" content="profile">' . "\n"
					. '<meta property="og:site_name" content="ItalianCosplay">' . "\n"
					. '<meta property="og:title" content="' . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta property="og:description" content="' . htmlspecialchars($description, ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta property="og:url" content="' . htmlspecialchars($data['canonicalUrl'] ?? '', ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta property="og:image" content="' . htmlspecialchars($profileImage, ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta name="twitter:card" content="summary_large_image">' . "\n"
					. '<meta name="twitter:title" content="' . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta name="twitter:description" content="' . htmlspecialchars($description, ENT_QUOTES, "UTF-8") . '">' . "\n"
					. '<meta name="twitter:image" content="' . htmlspecialchars($profileImage, ENT_QUOTES, "UTF-8") . '">';
				break;

			default:
				$title = "ItalianCosplay";
				$description = "Scopri eventi, fiere e raduni cosplay in Italia.";
				break;
		}

		echo "<title>" . htmlspecialchars($title) . "</title>\n";
		echo '<meta name="description" content="' . htmlspecialchars($description) . '">' . "\n";
		echo $meta_fb . "\n";
	}

}
