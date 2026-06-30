<?php
// app/routes.php
// La variabile $router è già un'istanza della classe Router.

// Rotte pubbliche
use App\Controllers\GuestController;
use App\Middleware\AuthMiddleware;
use App\Middleware\PermissionMiddleware;

$router->get('/', ['uses' => ['HomeController', 'index']]);
$router->get('/login', ['uses' => ['AuthController', 'showLoginForm']]);
$router->post('/login', ['uses' => ['AuthController', 'login']]);
$router->get('/register', ['uses' => ['AuthController', 'showRegisterForm']]);
$router->post('/register', ['uses' => ['AuthController', 'register']]);

$router->post('/logout', [
	'uses' => ['AuthController', 'logout'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->get('/privacy', ['uses' => ['HomeController', 'privacy']]);
$router->get('/cookies', ['uses' => ['HomeController', 'cookies']]);
$router->get('/sitemap.xml', ['uses' => ['SitemapController', 'index']]);
$router->get('/sitemap-static.xml', ['uses' => ['SitemapController', 'static']]);
$router->get('/sitemap-events.xml', ['uses' => ['SitemapController', 'events']]);
$router->get('/sitemap-locations.xml', ['uses' => ['SitemapController', 'locations']]);
$router->get('/sitemap-blog-categorie.xml', ['uses' => ['SitemapController', 'blog_categorie']]);
$router->get('/sitemap-blog-post.xml', ['uses' => ['SitemapController', 'blog_post']]);

// Eventi Cosplay
$router->get('/eventi-cosplay', ['uses' => ['EventController', 'index']]);
$router->get('/eventi-cosplay/create', ['uses' => ['EventController', 'create']]);
$router->post('/eventi-cosplay/store', ['uses' => ['EventController', 'store']]);

// Filtri avanzati per eventi
$router->get('/eventi-cosplay/{regione_slug}/{provincia_slug}/{comune_slug}', ['uses' => ['EventController', 'index']]);
$router->get('/eventi-cosplay/{regione_slug}/{provincia_slug}', ['uses' => ['EventController', 'index']]);
// $router->get('/eventi-cosplay/{regione_slug}', ['uses' => ['EventController', 'index']]); // se servisse Regione sola

// Dettaglio evento con slug (deve venire dopo i filtri)
$router->get('/eventi-cosplay/{slug}', ['uses' => ['EventController', 'shortUrl']]);

// API pubblica eventi
$router->get('/api/eventi', ['uses' => ['ApiEventController', 'index']]);
$router->get('/api/eventi/{slug}', ['uses' => ['ApiEventController', 'show']]);

// API dropdown dinamiche
$router->get('/api/regioni', ['uses' => ['ApiController', 'getRegioni']]);
$router->get('/api/province/{id}', ['uses' => ['ApiController', 'getProvinceByRegione']]);
$router->get('/api/comuni/{id}', ['uses' => ['ApiController', 'getComuniByProvincia']]);
$router->get('/api/search/{q}', ['uses' => ['ApiController', 'searchComuni']]);

// Area admin (middleware Auth + permesso)
$router->get('/admin/dashboard', [
	'uses' => ['AdminController', 'dashboard'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);

// Gestione Utenti (CRUD admin)
$router->get('/admin/users', ['uses' => ['AdminController', 'users'], 'middlewares' => [AuthMiddleware::class]]);
$router->get('/admin/users/create', ['uses' => ['AdminController', 'createUser'], 'middlewares' => [AuthMiddleware::class]]);
$router->post('/admin/users/store', ['uses' => ['AdminController', 'storeUser'], 'middlewares' => [AuthMiddleware::class]]);
$router->get('/admin/users/edit/{id}',
	[
		'uses' => ['AdminController', 'editUser'],
		'middlewares' => [
			AuthMiddleware::class,
			[PermissionMiddleware::class, 'manage_users'] // esempio
		]
	]);
$router->post('/admin/users/update/{id}', [
	'uses' => ['AdminController', 'updateUser'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'manage_users'] // esempio
	]
]);
$router->post('/admin/users/delete/{id}', [
	'uses' => ['AdminController', 'deleteUser'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'manage_users'] // esempio
	]
]);

// Gestione Eventi Admin
$router->get('/admin/events/pending', [
	'uses' => ['EventController', 'pending'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
	]
]);
$router->get('/admin/events/all', ['uses' => ['EventController', 'allEvents'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->get('/admin/events/create', ['uses' => ['EventController', 'adminCreate'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->post('/admin/events/store', ['uses' => ['EventController', 'adminStore'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->get('/admin/events/edit/{id}', ['uses' => ['EventController', 'edit'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->post('/admin/events/update/{id}', ['uses' => ['EventController', 'update'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->post('/admin/events/delete/{id}', ['uses' => ['EventController', 'delete'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->post('/admin/events/approve/{id}', ['uses' => ['EventController', 'approve'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->get('/admin/events/show/{id}', ['uses' => ['EventController', 'adminShow'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);

$router->get('/admin/regioni/all', ['uses' => ['RegionController', 'all'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_region'] // il permesso corretto dal DB
]]);
$router->get('/admin/regioni/edit/{id}', ['uses' => ['RegionController', 'edit'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_region'] // il permesso corretto dal DB
]]);
$router->post('/admin/regioni/update/{id}', ['uses' => ['RegionController', 'update'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_region'] // il permesso corretto dal DB
]]);

$router->get('/password/forgot' , ['uses' => ['PasswordController', 'forgot']]);
$router->post('/password/forgot', ['uses' => ['PasswordController', 'forgot']]);

$router->get('/password/reset/{token}', ['uses' => ['PasswordController', 'reset']]);
$router->post('/password/reset/{token}', ['uses' => ['PasswordController', 'reset']]);

$router->get('/verify/{token}', ['uses' => ['AuthController', 'verify']]);


$router->get('/dashboard',
['uses' => ['DashboardController', 'overview'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);


$router->get('/dashboard/profile', ['uses' => ['DashboardController', 'profile'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->get('/dashboard/change_password', ['uses' => ['DashboardController', 'changePassword'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->post('/dashboard/change_password', ['uses' => ['DashboardController', 'changePassword'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->get('/dashboard/avatar', ['uses' => ['DashboardController', 'avatar'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->post('/dashboard/updateAvatar', ['uses' => ['DashboardController', 'updateAvatar'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->get('/dashboard/events', ['uses' => ['DashboardController', 'events'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->get('/dashboard/settings', ['uses' => ['DashboardController', 'settings'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);

$router->post('/dashboard/profile/update', ['uses' => ['DashboardController', 'updateProfile'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->get('/dashboard/cover', ['uses' => ['DashboardController', 'cover'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->post('/dashboard/updateCover', ['uses' => ['DashboardController', 'updateCover'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->get('/dashboard/settings', ['uses' => ['DashboardController', 'settings'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->post('/dashboard/updatesettings', ['uses' => ['DashboardController', 'updateSettings'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);

$router->get('/u/{username}', ['uses' => ['ProfileController', 'publicProfile']]);

$router->get('/user/{username}', ['uses' => ['UserController', 'show']]);
$router->post('/event/scrape', ['uses' => ['EventController', 'scrape']]);
$router->get('/event/stats', ['uses' => ['AdminController', 'stats']]);
$router->get('/eventi-cosplay-weekend', ['uses' => ['EventController', 'weekend']]);
$router->get('/eventi-cosplay-mese', ['uses' => ['EventController', 'mese']]);
$router->get('/aggiornamento_ranking', ['uses' => ['EventController', 'updateAllScores']]);
$router->get('/eventImageMigrationService', ['uses' => ['EventController', 'eventImageMigrationService']]);

$router->get('/segnala-evento-cosplay', ['uses' => ['EventController', 'segnalaEvento']]);
$router->post('/salva-evento', ['uses' => ['EventController', 'salvaEventoSegnalato']]);

$router->get('/blog', ['BlogController', 'index']);
$router->get('/blog/{slug}', ['BlogController', 'show']);
$router->get('/blog/categoria/{slug}', ['BlogController', 'category']);
$router->get('/blog/categoria/{slug}/pagina/{slug}', ['BlogController', 'category']);
$router->get('/blog/pagina/{slug}', ['BlogController', 'index']);

$router->get('/admin/blog/all', ['uses' => ['AdminBlogController', 'all'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->get('/admin/blog/create', ['uses' => ['AdminBlogController', 'create'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->post('/admin/blog/store', ['uses' => ['AdminBlogController', 'store'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->get('/admin/blog/edit/{id}', ['uses' => ['AdminBlogController', 'edit'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->post('/admin/blog/update/{id}', ['uses' => ['AdminBlogController', 'update'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->post('/admin/blog/delete/{id}', ['uses' => ['AdminBlogController', 'delete'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->get('/admin/blog/show/{id}', ['uses' => ['AdminBlogController', 'show'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);

$router->get('/admin/blog-categories/all', ['uses' => ['AdminBlogCategoryController', 'all'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->get('/admin/blog-categories/create', ['uses' => ['AdminBlogCategoryController', 'create'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->post('/admin/blog-categories/store', ['uses' => ['AdminBlogCategoryController', 'store'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->get('/admin/blog-categories/edit/{id}', ['uses' => ['AdminBlogCategoryController', 'edit'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->post('/admin/blog-categories/update/{id}', ['uses' => ['AdminBlogCategoryController', 'update'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->post('/admin/blog-categories/delete/{id}', ['uses' => ['AdminBlogCategoryController', 'delete'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->get('/admin/blog-categories/show/{id}', ['uses' => ['AdminBlogCategoryController', 'show'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);

$router->get('/admin/guests/search', ['uses' => ['AdminGuestController', 'search']]);
$router->post('/admin/guests/create', ['uses' => ['AdminGuestController', 'createSoft']]);


$router->get('/admin/guests/all', ['uses' => ['AdminGuestController', 'all'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->get('/admin/guests/create', ['uses' => ['AdminGuestController', 'create'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->post('/admin/guests/store', ['uses' => ['AdminGuestController', 'store'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->get('/admin/guests/edit/{id}', ['uses' => ['AdminGuestController', 'edit'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->post('/admin/guests/update/{id}', ['uses' => ['AdminGuestController', 'update'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->post('/admin/guests/delete/{id}', ['uses' => ['AdminGuestController', 'delete'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->get('/ospiti', ['GuestController', 'index']);
$router->get('/ospiti/{slug}', ['GuestController', 'show']);


$router->get('/dashboard/ads/campaigns/index', ['uses' => ['AdCampaignController', 'index'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->get('/dashboard/ads/campaigns/create', ['uses' => ['AdCampaignController', 'create'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->post('/dashboard/ads/campaigns/store', ['uses' => ['AdCampaignController', 'store'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->get('/dashboard/ads/campaigns/{slug}/review', ['uses' => ['AdCampaignController', 'review'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->get('/dashboard/ads/campaigns/{slug}/stats', ['uses' => ['AdCampaignController', 'stats'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->get('/dashboard/ads/campaigns/checkout', ['uses' => ['AdCampaignController', 'checkout'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->post('/dashboard/ads/campaigns/{slug}/cancel', ['uses' => ['AdCampaignController', 'cancel'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->post('/dashboard/ads/campaigns/{slug}/checkout', ['uses' => ['AdCampaignController', 'checkout'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->post('/dashboard/ads/payments/{slug}/status', ['uses' => ['AdCampaignController', 'checkout'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->get('/dashboard/ads/campaigns/estimatePrice', ['uses' => ['AdCampaignController', 'estimatePrice'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->get('/ashboard/ads/campaigns/estimatePrice', ['uses' => ['AdCampaignController', 'estimatePrice'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);

// Fine rotte
