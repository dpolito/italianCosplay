<?php
// app/routes.php
// La variabile $router è già un'istanza della classe Router.

// Rotte pubbliche
use App\Controllers\GuestController;
use App\Middleware\ApiAccessMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\PermissionMiddleware;

$router->get('/', ['uses' => ['HomeController', 'index']]);
$router->get('/login', ['uses' => ['AuthController', 'showLoginForm']]);
$router->post('/login', ['uses' => ['AuthController', 'login']]);
$router->get('/agenda-cosplay', ['uses' => ['AuthController', 'agendaLanding']]);
$router->get('/legacy-invitation/agenda', ['uses' => ['LegacyInvitationEmailController', 'agenda']]);
$router->get('/cosplan', ['uses' => ['AuthController', 'cosplanLanding']]);
$router->get('/register', ['uses' => ['AuthController', 'showRegisterForm']]);
$router->post('/register', ['uses' => ['AuthController', 'register']]);
$router->get('/inviti/accetta', ['uses' => ['InvitationController', 'accept']]);
$router->get('/inviti/blocca', ['uses' => ['InvitationController', 'block']]);

$router->post('/logout', [
	'uses' => ['AuthController', 'logout'],
	'middlewares' => [AuthMiddleware::class]
]);

$router->get('/dashboard/favorites', [
	'uses' => ['DashboardController', 'favorites'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->post('/dashboard/favorites/toggle', [
	'uses' => ['DashboardController', 'toggleFavorite']
]);
$router->post('/eventi-cosplay/agenda/update', [
	'uses' => ['EventController', 'updateAgendaStatus']
]);
$router->get('/privacy', ['uses' => ['HomeController', 'privacy']]);
$router->get('/cookies', ['uses' => ['HomeController', 'cookies']]);
$router->get('/organizzatori-eventi-cosplay', ['uses' => ['HomeController', 'organizersLanding']]);
$router->get('/faq', ['uses' => ['FaqController', 'index']]);
$router->get('/foto-cosplay', ['uses' => ['PhotoController', 'index']]);
$router->get('/foto-cosplay/{slug}', ['uses' => ['PhotoController', 'index']]);
$router->get('/foto-cosplay/{slug}/{slug}', ['uses' => ['PhotoController', 'index']]);
$router->get('/foto-cosplay/{slug}/{slug}/{slug}', ['uses' => ['PhotoController', 'index']]);
$router->get('/foto-cosplay/{slug}/{slug}/{slug}/{slug}', ['uses' => ['PhotoController', 'index']]);
$router->post('/analytics/photo-event', ['uses' => ['PhotoAnalyticsController', 'track']]);
$router->get('/sitemap.xml', ['uses' => ['SitemapController', 'index']]);
$router->get('/sitemap-static.xml', ['uses' => ['SitemapController', 'static']]);
$router->get('/sitemap-events.xml', ['uses' => ['SitemapController', 'events']]);
$router->get('/sitemap-events-master.xml', ['uses' => ['SitemapController', 'events_master']]);
$router->get('/sitemap-organizations.xml', ['uses' => ['SitemapController', 'organizations']]);
$router->get('/sitemap-locations.xml', ['uses' => ['SitemapController', 'locations']]);
$router->get('/sitemap-blog-categorie.xml', ['uses' => ['SitemapController', 'blog_categorie']]);
$router->get('/sitemap-blog-post.xml', ['uses' => ['SitemapController', 'blog_post']]);
$router->get('/sitemap-photos.xml', ['uses' => ['SitemapController', 'photos']]);

// Advertising pubblico
$router->get('/ads/click/{campaignId}', ['uses' => ['AdCampaignController', 'click']]);

// Eventi Cosplay
$router->get('/eventi-master', ['uses' => ['EventController', 'masterIndex']]);
$router->get('/eventi-master/{slug}', ['uses' => ['EventController', 'masterShow']]);
$router->get('/eventi-master/{slug}/riscatta', ['uses' => ['EventMasterClaimController', 'create']]);
$router->get('/eventi-master/{slug}/organizzazioni/search', ['uses' => ['EventMasterClaimController', 'organizationSearch']]);
$router->post('/eventi-master/{slug}/riscatta', ['uses' => ['EventMasterClaimController', 'store']]);
$router->get('/organizzazioni/{slug}', ['uses' => ['OrganizationController', 'show']]);
$router->get('/organizzazioni', ['uses' => ['OrganizationController', 'index']]);
$router->get('/eventi-cosplay', ['uses' => ['EventController', 'index']]);
$router->get('/eventi-cosplay-{year}', ['uses' => ['EventController', 'year']]);
$router->get('/eventi-cosplay/create', ['uses' => ['EventController', 'create']]);
$router->post('/eventi-cosplay/report/track', ['uses' => ['EventController', 'trackReportForm']]);
$router->post('/eventi-cosplay/store', ['uses' => ['EventController', 'store']]);
$router->get('/eventi-cosplay/{slug}/foto/{id}', ['uses' => ['PhotoController', 'show']]);
$router->post('/eventi-cosplay/{slug}/foto/{id}/sono-io', ['uses' => ['PhotoController', 'claimSelf']]);
$router->post('/eventi-cosplay/{slug}/foto/{id}/segnala', ['uses' => ['PhotoController', 'report']]);
$router->get('/eventi-cosplay/{slug}/foto', ['uses' => ['PhotoController', 'eventGallery']]);
$router->get('/foto/{id}', ['uses' => ['PhotoController', 'show']]);
$router->get('/api/photos/events/search', [
	'uses' => ['PhotoController', 'searchEvents'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->get('/api/photos/users/search', [
	'uses' => ['PhotoController', 'searchUsers'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->get('/api/photos/users/{id}/cosplays', [
	'uses' => ['PhotoController', 'userCosplays'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->post('/foto/{id}/sono-io', ['uses' => ['PhotoController', 'claimSelf']]);
$router->post('/foto/{id}/segnala', ['uses' => ['PhotoController', 'report']]);

$router->get('/dashboard/photos', [
	'uses' => ['PhotoController', 'dashboardIndex'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->get('/dashboard/photos/upload', [
	'uses' => ['PhotoController', 'uploadForm'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->get('/dashboard/photos/event/{id}', [
	'uses' => ['PhotoController', 'manageEvent'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->post('/dashboard/photos/upload', [
	'uses' => ['PhotoController', 'upload'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->post('/dashboard/photos/event-submissions', [
	'uses' => ['PhotoController', 'createEventSubmission'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->get('/dashboard/photos/upload/session', [
	'uses' => ['PhotoController', 'uploadSession'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->post('/dashboard/photos/upload/session/start', [
	'uses' => ['PhotoController', 'startUploadSession'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->post('/dashboard/photos/upload/session/confirm', [
	'uses' => ['PhotoController', 'confirmUploadSession'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->post('/dashboard/photos/upload/session/cancel', [
	'uses' => ['PhotoController', 'cancelUploadSession'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->post('/dashboard/photos/upload/item/remove', [
	'uses' => ['PhotoController', 'removeUploadItem'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->post('/dashboard/photos/delete', [
	'uses' => ['PhotoController', 'delete'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->post('/dashboard/photos/associate', [
	'uses' => ['PhotoController', 'associate'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->post('/dashboard/photos/associations/remove', [
	'uses' => ['PhotoController', 'removeAssociation'],
	'middlewares' => [AuthMiddleware::class]
]);

// Slug breve: disambigua tra dettaglio evento e filtro regione.
$router->get('/eventi-cosplay/{slug}', ['uses' => ['EventController', 'shortUrl']]);

// Filtri avanzati per eventi
$router->get('/eventi-cosplay/{regione_slug}/{provincia_slug}/{comune_slug}', ['uses' => ['EventController', 'index']]);
$router->get('/eventi-cosplay/{regione_slug}/{provincia_slug}', ['uses' => ['EventController', 'index']]);

// API pubblica eventi
$router->get('/api/eventi', ['uses' => ['ApiEventController', 'index']]);
$router->get('/api/eventi/search', ['uses' => ['ApiController', 'searchEvents']]);
$router->get('/api/eventi/{slug}', ['uses' => ['ApiEventController', 'show']]);

// API dropdown dinamiche
$router->get('/api/regioni', ['uses' => ['ApiController', 'getRegioni']]);
$router->get('/api/province/{id}', ['uses' => ['ApiController', 'getProvinceByRegione']]);
$router->get('/api/comuni/{id}', ['uses' => ['ApiController', 'getComuniByProvincia']]);
$router->get('/api/search/{q}', ['uses' => ['ApiController', 'searchComuni']]);

// MCP endpoint per tool AI read-only.
$router->post('/mcp', ['uses' => ['McpController', 'handle']]);

// API versionate per integrazioni esterne.
$router->get('/api/v1/events/search', [
	'uses' => ['ApiV1Controller', 'searchEvents'],
	'middlewares' => [[ApiAccessMiddleware::class, 'events:search']]
]);
$router->get('/api/v1/events/{slug}/photos', [
	'uses' => ['ApiV1Controller', 'eventPhotos'],
	'middlewares' => [[ApiAccessMiddleware::class, 'events:photos']]
]);
$router->get('/api/v1/events/{slug}', [
	'uses' => ['ApiV1Controller', 'showEvent'],
	'middlewares' => [[ApiAccessMiddleware::class, 'events:read']]
]);
$router->get('/api/v1/locations/{slug}', [
	'uses' => ['ApiV1Controller', 'locations'],
	'middlewares' => [[ApiAccessMiddleware::class, 'locations:read']]
]);

// Webhook Brevo per eventi email transazionali.
$router->post('/webhooks/brevo/email-events', ['uses' => ['BrevoWebhookController', 'receive']]);

$router->get('/admin/email-delivery-events', [
	'uses' => ['AdminEmailDeliveryEventController', 'index'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->get('/admin/email-delivery-events/data', [
	'uses' => ['AdminEmailDeliveryEventController', 'data'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->get('/admin/email-delivery-events/detail/{id}', [
	'uses' => ['AdminEmailDeliveryEventController', 'detail'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);

$apiAdminMiddleware = [AuthMiddleware::class, [PermissionMiddleware::class, 'view_admin_dashboard']];
$router->get('/admin/api-clients', ['uses' => ['AdminApiClientController', 'index'], 'middlewares' => $apiAdminMiddleware]);
$router->get('/admin/api-clients/create', ['uses' => ['AdminApiClientController', 'create'], 'middlewares' => $apiAdminMiddleware]);
$router->post('/admin/api-clients/store', ['uses' => ['AdminApiClientController', 'store'], 'middlewares' => $apiAdminMiddleware]);
$router->get('/admin/api-clients/logs', ['uses' => ['AdminApiClientController', 'logs'], 'middlewares' => $apiAdminMiddleware]);
$router->get('/admin/api-clients/simulator', ['uses' => ['AdminApiClientController', 'simulator'], 'middlewares' => $apiAdminMiddleware]);
$router->post('/admin/api-clients/simulator', ['uses' => ['AdminApiClientController', 'simulator'], 'middlewares' => $apiAdminMiddleware]);
$router->get('/admin/api-clients/{id}', ['uses' => ['AdminApiClientController', 'show'], 'middlewares' => $apiAdminMiddleware]);
$router->get('/admin/api-clients/{id}/edit', ['uses' => ['AdminApiClientController', 'edit'], 'middlewares' => $apiAdminMiddleware]);
$router->post('/admin/api-clients/{id}/update', ['uses' => ['AdminApiClientController', 'update'], 'middlewares' => $apiAdminMiddleware]);
$router->post('/admin/api-clients/{id}/rotate', ['uses' => ['AdminApiClientController', 'rotate'], 'middlewares' => $apiAdminMiddleware]);
$router->post('/admin/api-clients/{id}/status', ['uses' => ['AdminApiClientController', 'status'], 'middlewares' => $apiAdminMiddleware]);

// Area admin (middleware Auth + permesso)
$router->get('/admin/dashboard', [
	'uses' => ['AdminController', 'dashboard'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->get('/admin/setup', [
	'uses' => ['AdminController', 'setup'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->post('/admin/setup', [
	'uses' => ['AdminController', 'updateSetup'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->get('/admin/photos/reports', [
	'uses' => ['AdminPhotoReportController', 'index'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->get('/admin/photos/event-submissions', [
	'uses' => ['AdminPhotoEventSubmissionController', 'index'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->get('/admin/photos/analytics', [
	'uses' => ['PhotoAnalyticsController', 'admin'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->get('/admin/photos/import-legacy', [
	'uses' => ['AdminLegacyPhotoImportController', 'index'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->post('/admin/photos/import-legacy/analyze', [
	'uses' => ['AdminLegacyPhotoImportController', 'analyze'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->post('/admin/photos/import-legacy/{id}/mapping', [
	'uses' => ['AdminLegacyPhotoImportController', 'mapping'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->post('/admin/photos/import-legacy/{id}/process', [
	'uses' => ['AdminLegacyPhotoImportController', 'process'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->get('/admin/photos/import-legacy/events/search', [
	'uses' => ['AdminLegacyPhotoImportController', 'searchEvents'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->get('/admin/photos/import-legacy/{id}/status', [
	'uses' => ['AdminLegacyPhotoImportController', 'status'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->get('/admin/photos/import-legacy/{id}/report', [
	'uses' => ['AdminLegacyPhotoImportController', 'report'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->post('/admin/photos/reports/{id}/resolve', [
	'uses' => ['AdminPhotoReportController', 'resolve'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->post('/admin/photos/event-submissions/{id}/merge', [
	'uses' => ['AdminPhotoEventSubmissionController', 'merge'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->post('/admin/photos/event-submissions/{id}/reject', [
	'uses' => ['AdminPhotoEventSubmissionController', 'reject'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->post('/admin/photos/reports/{id}/dismiss', [
	'uses' => ['AdminPhotoReportController', 'dismiss'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->post('/admin/photos/reports/{id}/hide-photo', [
	'uses' => ['AdminPhotoReportController', 'hidePhoto'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'view_admin_dashboard']
	]
]);
$router->get('/admin/telegram', [
	'uses' => ['AdminTelegramController', 'index'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'manage_events']
	]
]);
$router->get('/admin/telegram/create', [
	'uses' => ['AdminTelegramController', 'create'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'manage_events']
	]
]);
$router->post('/admin/telegram/send', [
	'uses' => ['AdminTelegramController', 'send'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'manage_events']
	]
]);
$router->post('/admin/telegram/schedule', [
	'uses' => ['AdminTelegramController', 'schedule'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'manage_events']
	]
]);
$router->post('/admin/telegram/{id}/cancel', [
	'uses' => ['AdminTelegramController', 'cancel'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'manage_events']
	]
]);

// Gestione FAQ
$faqAdminMiddleware = [AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']];
$router->get('/admin/faq', ['uses' => ['AdminFaqController', 'index'], 'middlewares' => $faqAdminMiddleware]);
$router->get('/admin/faq/categories', ['uses' => ['AdminFaqController', 'categories'], 'middlewares' => $faqAdminMiddleware]);
$router->get('/admin/faq/categories/data', ['uses' => ['AdminFaqController', 'categoriesData'], 'middlewares' => $faqAdminMiddleware]);
$router->get('/admin/faq/items', ['uses' => ['AdminFaqController', 'items'], 'middlewares' => $faqAdminMiddleware]);
$router->get('/admin/faq/items/data', ['uses' => ['AdminFaqController', 'itemsData'], 'middlewares' => $faqAdminMiddleware]);
$router->get('/admin/faq/categories/create', ['uses' => ['AdminFaqController', 'categoryForm'], 'middlewares' => $faqAdminMiddleware]);
$router->get('/admin/faq/categories/{id}/edit', ['uses' => ['AdminFaqController', 'categoryForm'], 'middlewares' => $faqAdminMiddleware]);
$router->post('/admin/faq/categories/store', ['uses' => ['AdminFaqController', 'saveCategory'], 'middlewares' => $faqAdminMiddleware]);
$router->post('/admin/faq/categories/{id}/update', ['uses' => ['AdminFaqController', 'saveCategory'], 'middlewares' => $faqAdminMiddleware]);
$router->post('/admin/faq/categories/{id}/delete', ['uses' => ['AdminFaqController', 'deleteCategory'], 'middlewares' => $faqAdminMiddleware]);
$router->get('/admin/faq/items/create', ['uses' => ['AdminFaqController', 'itemForm'], 'middlewares' => $faqAdminMiddleware]);
$router->get('/admin/faq/items/{id}/edit', ['uses' => ['AdminFaqController', 'itemForm'], 'middlewares' => $faqAdminMiddleware]);
$router->post('/admin/faq/items/store', ['uses' => ['AdminFaqController', 'saveItem'], 'middlewares' => $faqAdminMiddleware]);
$router->post('/admin/faq/items/{id}/update', ['uses' => ['AdminFaqController', 'saveItem'], 'middlewares' => $faqAdminMiddleware]);
$router->post('/admin/faq/items/{id}/delete', ['uses' => ['AdminFaqController', 'deleteItem'], 'middlewares' => $faqAdminMiddleware]);

$router->get('/admin/privacy', [
	'uses' => ['AdminPrivacyPolicyController', 'index'],
	'middlewares' => [AuthMiddleware::class]
]);
	$router->get('/admin/cookies', [
	'uses' => ['AdminCookiePolicyController', 'index'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->get('/admin/cookies/data', [
	'uses' => ['AdminCookiePolicyController', 'data'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->get('/admin/cookies/create', [
	'uses' => ['AdminCookiePolicyController', 'create'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->post('/admin/cookies/store', [
	'uses' => ['AdminCookiePolicyController', 'store'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->get('/admin/cookies/edit/{id}', [
	'uses' => ['AdminCookiePolicyController', 'edit'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->post('/admin/cookies/update/{id}', [
	'uses' => ['AdminCookiePolicyController', 'update'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->post('/admin/cookies/activate/{id}', [
	'uses' => ['AdminCookiePolicyController', 'activate'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->get('/admin/privacy/data', [
	'uses' => ['AdminPrivacyPolicyController', 'data'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->get('/admin/privacy/create', [
	'uses' => ['AdminPrivacyPolicyController', 'create'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->post('/admin/privacy/store', [
	'uses' => ['AdminPrivacyPolicyController', 'store'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->get('/admin/privacy/edit/{id}', [
	'uses' => ['AdminPrivacyPolicyController', 'edit'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->post('/admin/privacy/update/{id}', [
	'uses' => ['AdminPrivacyPolicyController', 'update'],
	'middlewares' => [AuthMiddleware::class]
]);
$router->post('/admin/privacy/activate/{id}', [
	'uses' => ['AdminPrivacyPolicyController', 'activate'],
	'middlewares' => [AuthMiddleware::class]
]);

// Gestione Utenti (CRUD admin)
$router->get('/admin/users', ['uses' => ['AdminController', 'users'], 'middlewares' => [AuthMiddleware::class]]);
$router->get('/admin/users/data', ['uses' => ['AdminController', 'usersData'], 'middlewares' => [AuthMiddleware::class]]);
$router->get('/admin/users/detail/{id}', ['uses' => ['AdminController', 'userDetail'], 'middlewares' => [AuthMiddleware::class]]);
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
$router->post('/admin/users/deactivate/{id}', [
	'uses' => ['AdminController', 'deactivateUser'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'manage_users'] // esempio
	]
]);
$router->get('/admin/users/deactivate/{id}', [
	'uses' => ['AdminController', 'deactivateUserFallback'],
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
$router->get('/admin/events/pending/data', [
	'uses' => ['EventController', 'pendingData'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
	]
]);
$router->get('/admin/events/data', [
	'uses' => ['EventController', 'data'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
	]
]);
$router->get('/admin/blog/data', [
	'uses' => ['AdminBlogController', 'data'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
	]
]);
$router->get('/admin/blog/detail/{id}', [
	'uses' => ['AdminBlogController', 'detail'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
	]
]);
$router->get('/admin/blog-categories/data', [
	'uses' => ['AdminBlogCategoryController', 'data'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
	]
]);
$router->get('/admin/blog-categories/detail/{id}', [
	'uses' => ['AdminBlogCategoryController', 'detail'],
	'middlewares' => [
		AuthMiddleware::class,
		[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
	]
]);

$router->get('/admin/ads/campaigns/data', ['uses' => ['AdminAdCampaignController', 'data'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->get('/admin/ads/banners/{id}/edit', ['uses' => ['AdminAdBannerController', 'edit'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->post('/admin/ads/banners/{id}/update', ['uses' => ['AdminAdBannerController', 'update'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->get('/admin/ads/campaigns/detail/{id}', ['uses' => ['AdminAdCampaignController', 'detail'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->get('/admin/ads/campaigns', ['uses' => ['AdminAdCampaignController', 'index'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->get('/admin/ads/campaigns/{id}', ['uses' => ['AdminAdCampaignController', 'show'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->get('/admin/ads/payments/logs', ['uses' => ['AdminAdCampaignController', 'paymentLogs'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->get('/admin/ads/payments/logs/data', ['uses' => ['AdminAdCampaignController', 'paymentLogsData'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->post('/admin/ads/campaigns/{id}/approve', ['uses' => ['AdminAdCampaignController', 'approve'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->post('/admin/ads/campaigns/{id}/reject', ['uses' => ['AdminAdCampaignController', 'reject'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->post('/admin/ads/campaigns/{id}/request-changes', ['uses' => ['AdminAdCampaignController', 'requestChanges'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->get('/admin/ads/positions/data', ['uses' => ['AdPositionController', 'data'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->get('/admin/ads/positions/detail/{id}', ['uses' => ['AdPositionController', 'detail'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->get('/admin/ads/positions', ['uses' => ['AdPositionController', 'index'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->get('/admin/ads/positions/create', ['uses' => ['AdPositionController', 'create'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->post('/admin/ads/positions/store', ['uses' => ['AdPositionController', 'store'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->get('/admin/ads/positions/edit/{id}', ['uses' => ['AdPositionController', 'edit'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->post('/admin/ads/positions/update/{id}', ['uses' => ['AdPositionController', 'update'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->post('/admin/ads/positions/delete/{id}', ['uses' => ['AdPositionController', 'delete'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);

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
$router->get('/admin/events/detail/{id}', ['uses' => ['EventController', 'detail'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->post('/admin/events/copy/{id}', ['uses' => ['EventController', 'copy'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events']
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

$router->get('/admin/events-master', ['uses' => ['AdminEventMasterController', 'index'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events']
]]);
$router->get('/admin/events-master/data', ['uses' => ['AdminEventMasterController', 'data'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events']
]]);
$router->get('/admin/events-master/detail/{id}', ['uses' => ['AdminEventMasterController', 'detail'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events']
]]);
$router->get('/admin/events-master/create', ['uses' => ['AdminEventMasterController', 'create'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events']
]]);
$router->post('/admin/events-master/store', ['uses' => ['AdminEventMasterController', 'store'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events']
]]);
$router->get('/admin/events-master/edit/{id}', ['uses' => ['AdminEventMasterController', 'edit'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events']
]]);
$router->post('/admin/events-master/update/{id}', ['uses' => ['AdminEventMasterController', 'update'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events']
]]);
$router->post('/admin/events-master/delete/{id}', ['uses' => ['AdminEventMasterController', 'delete'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events']
]]);
$router->get('/admin/event-master-claims', ['uses' => ['AdminEventMasterClaimController', 'index'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events']
]]);
$router->get('/admin/event-master-claims/data', ['uses' => ['AdminEventMasterClaimController', 'data'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events']
]]);
$router->get('/admin/event-master-claims/{id}', ['uses' => ['AdminEventMasterClaimController', 'show'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events']
]]);
$router->post('/admin/event-master-claims/{id}/approve', ['uses' => ['AdminEventMasterClaimController', 'approve'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events']
]]);
$router->post('/admin/event-master-claims/{id}/reject', ['uses' => ['AdminEventMasterClaimController', 'reject'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events']
]]);
$router->get('/admin/organization-emails', ['uses' => ['AdminOrganizationEmailController', 'index'], 'middlewares' => [
	AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']
]]);
$router->post('/admin/organization-emails/send', ['uses' => ['AdminOrganizationEmailController', 'send'], 'middlewares' => [
	AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']
]]);
$router->post('/admin/organization-emails/test', ['uses' => ['AdminOrganizationEmailController', 'test'], 'middlewares' => [
	AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']
]]);
$router->get('/admin/legacy-invitation-emails', ['uses' => ['AdminLegacyInvitationEmailController', 'index'], 'middlewares' => [
	AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']
]]);
$router->get('/admin/legacy-invitation-emails/data', ['uses' => ['AdminLegacyInvitationEmailController', 'data'], 'middlewares' => [
	AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']
]]);
$router->post('/admin/legacy-invitation-emails/import', ['uses' => ['AdminLegacyInvitationEmailController', 'import'], 'middlewares' => [
	AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']
]]);
$router->post('/admin/legacy-invitation-emails/import-json', ['uses' => ['AdminLegacyInvitationEmailController', 'importJson'], 'middlewares' => [
	AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']
]]);
$router->post('/admin/legacy-invitation-emails/send', ['uses' => ['AdminLegacyInvitationEmailController', 'send'], 'middlewares' => [
	AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']
]]);
$router->post('/admin/legacy-invitation-emails/test', ['uses' => ['AdminLegacyInvitationEmailController', 'test'], 'middlewares' => [
	AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']
]]);
$router->get('/admin/organizations', ['uses' => ['AdminOrganizationController', 'index'], 'middlewares' => [
	AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']
]]);
$router->get('/admin/organizations/data', ['uses' => ['AdminOrganizationController', 'data'], 'middlewares' => [
	AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']
]]);
$router->get('/admin/organizations/create', ['uses' => ['AdminOrganizationController', 'create'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']]]);
$router->post('/admin/organizations/store', ['uses' => ['AdminOrganizationController', 'store'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']]]);
$router->get('/admin/organizations/{id}/users/search', ['uses' => ['AdminOrganizationController', 'userSearch'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']]]);
$router->get('/admin/organizations/{id}/masters/search', ['uses' => ['AdminOrganizationController', 'masterSearch'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']]]);
$router->get('/admin/organizations/{id}', ['uses' => ['AdminOrganizationController', 'show'], 'middlewares' => [
	AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']
]]);
$router->get('/admin/organizations/{id}/edit', ['uses' => ['AdminOrganizationController', 'edit'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']]]);
$router->post('/admin/organizations/{id}/update', ['uses' => ['AdminOrganizationController', 'update'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']]]);
$router->post('/admin/organizations/{id}/status', ['uses' => ['AdminOrganizationController', 'updateStatus'], 'middlewares' => [
	AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']
]]);
$router->post('/admin/organizations/{id}/members/add', ['uses' => ['AdminOrganizationController', 'addMember'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']]]);
$router->post('/admin/organizations/{id}/members/update', ['uses' => ['AdminOrganizationController', 'updateMember'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']]]);
$router->post('/admin/organizations/{id}/masters/add', ['uses' => ['AdminOrganizationController', 'addMaster'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']]]);
$router->post('/admin/organizations/{id}/masters/update', ['uses' => ['AdminOrganizationController', 'updateMaster'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']]]);
$router->post('/admin/organizations/{id}/masters/remove', ['uses' => ['AdminOrganizationController', 'removeMaster'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'manage_events']]]);

$router->get('/admin/regioni/all', ['uses' => ['RegionController', 'all'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_region'] // il permesso corretto dal DB
]]);
$router->get('/admin/regioni/data', ['uses' => ['RegionController', 'data'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_region'] // il permesso corretto dal DB
]]);
$router->get('/admin/regioni/detail/{id}', ['uses' => ['RegionController', 'detail'], 'middlewares' => [
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
$router->get('/dashboard/organizations', ['uses' => ['DashboardController', 'organizations'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/inviti', ['uses' => ['DashboardController', 'invitations'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->post('/dashboard/inviti', ['uses' => ['DashboardController', 'sendInvitation'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->get('/dashboard/inviti/accetta', ['uses' => ['DashboardController', 'acceptUserInvitation'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->get('/dashboard/organization-invitations', ['uses' => ['DashboardController', 'organizationInvitations'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->get('/dashboard/organization-invitations/accept', ['uses' => ['DashboardController', 'organizationInvitationPreview'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->post('/dashboard/organization-invitations/accept', ['uses' => ['DashboardController', 'acceptOrganizationInvitation'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->post('/dashboard/organization-invitations/decline', ['uses' => ['DashboardController', 'declineOrganizationInvitation'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->get('/dashboard/organizations/create', ['uses' => ['DashboardController', 'createOrganization'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->post('/dashboard/organizations/create', ['uses' => ['DashboardController', 'storeOrganization'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->get('/dashboard/organizations/{id}', ['uses' => ['DashboardController', 'organizationDetail'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/organizations/{organizationId}/masters/create', ['uses' => ['DashboardController', 'organizationMasterCreate'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->post('/dashboard/organizations/{organizationId}/masters/create', ['uses' => ['DashboardController', 'storeOrganizationMaster'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->get('/dashboard/organizations/{organizationId}/masters/{masterId}', ['uses' => ['DashboardController', 'organizationMasterDetail'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->get('/dashboard/organizations/{organizationId}/masters/{masterId}/events/create', ['uses' => ['DashboardController', 'organizationEventCreate'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->post('/dashboard/organizations/{organizationId}/masters/{masterId}/events/create', ['uses' => ['DashboardController', 'storeOrganizationEvent'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->get('/dashboard/organizations/{organizationId}/masters/{masterId}/edit', ['uses' => ['DashboardController', 'organizationMasterEdit'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->post('/dashboard/organizations/{organizationId}/masters/{masterId}/update', ['uses' => ['DashboardController', 'updateOrganizationMaster'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->get('/dashboard/organizations/{organizationId}/events/{eventId}/edit', ['uses' => ['DashboardController', 'organizationEventEdit'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->post('/dashboard/organizations/{organizationId}/events/{eventId}/update', ['uses' => ['DashboardController', 'updateOrganizationEvent'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->get('/dashboard/events/{eventId}/guests/search', ['uses' => ['DashboardController', 'organizationGuestSearch'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->post('/dashboard/events/{eventId}/guests/create', ['uses' => ['DashboardController', 'organizationGuestCreate'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->post('/dashboard/events/{eventId}/guests/{guestId}/remove', ['uses' => ['DashboardController', 'organizationGuestRemove'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->get('/dashboard/guests/search', ['uses' => ['DashboardController', 'dashboardGuestSearch'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->post('/dashboard/guests/create', ['uses' => ['DashboardController', 'dashboardGuestCreate'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->post('/dashboard/organizations/{id}/update', ['uses' => ['DashboardController', 'updateOrganization'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->post('/dashboard/organizations/{id}/withdraw', ['uses' => ['DashboardController', 'withdrawOrganization'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->post('/dashboard/organizations/{organizationId}/masters/{masterId}/withdraw', ['uses' => ['DashboardController', 'withdrawOrganizationMaster'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->post('/dashboard/organizations/{organizationId}/events/{eventId}/withdraw', ['uses' => ['DashboardController', 'withdrawOrganizationEvent'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->post('/dashboard/organizations/{id}/members', ['uses' => ['DashboardController', 'organizationMember'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->post('/dashboard/organizations/{id}/invitations/{invitationId}/revoke', ['uses' => ['DashboardController', 'revokeOrganizationInvitation'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->post('/dashboard/organizations/{id}/invitations/{invitationId}/resend', ['uses' => ['DashboardController', 'resendOrganizationInvitation'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->get('/dashboard/organizations/{id}/members/search', ['uses' => ['DashboardController', 'organizationMemberSearch'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/organizations/{id}/masters/search', ['uses' => ['DashboardController', 'organizationMasterSearch'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);
$router->post('/dashboard/organizations/{id}/masters', ['uses' => ['DashboardController', 'organizationMaster'], 'middlewares' => [AuthMiddleware::class, [PermissionMiddleware::class, 'access_dashboard']]]);


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
$router->get('/dashboard/cosplay', ['uses' => ['DashboardController', 'cosplay'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/cosplay/events', ['uses' => ['DashboardController', 'searchCosplayEvents'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/cosplay/characters', ['uses' => ['DashboardController', 'searchCosplayCharacters'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->post('/dashboard/cosplay/save', ['uses' => ['DashboardController', 'saveCosplayPortfolio'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->post('/dashboard/cosplay/delete', ['uses' => ['DashboardController', 'deleteCosplayPortfolio'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->post('/dashboard/cosplay/toggle-visibility', ['uses' => ['DashboardController', 'toggleCosplayPortfolioVisibility'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->post('/dashboard/cosplay/event/save', ['uses' => ['DashboardController', 'saveEventCosplaySelection'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->post('/dashboard/cosplay/event/remove', ['uses' => ['DashboardController', 'removeEventCosplaySelection'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/notifications', ['uses' => ['DashboardController', 'notifications'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->get('/dashboard/notifiche', ['uses' => ['DashboardController', 'notifications'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->post('/dashboard/notifications/read', ['uses' => ['DashboardController', 'markNotificationRead'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->post('/dashboard/notifications/delete', ['uses' => ['DashboardController', 'deleteNotification'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->post('/dashboard/notifications/read-all', ['uses' => ['DashboardController', 'markAllNotificationsRead'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/settings', ['uses' => ['DashboardController', 'settings'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);

$router->post('/dashboard/profile/update', ['uses' => ['DashboardController', 'updateProfile'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard'] // il permesso corretto dal DB
]]);
$router->post('/dashboard/delete-account', ['uses' => ['DashboardController', 'deleteAccount'], 'middlewares' => [
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

$router->get('/u', ['uses' => ['ProfileController', 'index']]);
$router->get('/u/{username}', ['uses' => ['ProfileController', 'publicProfile']]);

$router->get('/user/{username}', ['uses' => ['UserController', 'show']]);
$router->post('/event/scrape', ['uses' => ['EventController', 'scrape']]);
$router->get('/event/stats', ['uses' => ['AdminController', 'stats']]);
$router->get('/eventi-cosplay-weekend', ['uses' => ['EventController', 'weekend']]);
$router->get('/eventi-cosplay-weekend/{slug}', ['uses' => ['EventController', 'weekendSpecifico']]);
$router->get('/eventi-cosplay-mese', ['uses' => ['EventController', 'mese']]);
$router->get('/eventi-cosplay-mese/{slug}', ['uses' => ['EventController', 'meseSpecifico']]);
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

$router->get('/admin/ads/campaigns', ['uses' => ['AdminAdCampaignController', 'index'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->get('/admin/ads/campaigns/{id}', ['uses' => ['AdminAdCampaignController', 'show'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->post('/admin/ads/campaigns/{id}/approve', ['uses' => ['AdminAdCampaignController', 'approve'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->post('/admin/ads/campaigns/{id}/reject', ['uses' => ['AdminAdCampaignController', 'reject'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->post('/admin/ads/campaigns/{id}/request-changes', ['uses' => ['AdminAdCampaignController', 'requestChanges'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->get('/admin/ads/positions', ['uses' => ['AdPositionController', 'index'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->get('/admin/ads/positions/create', ['uses' => ['AdPositionController', 'create'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->post('/admin/ads/positions/store', ['uses' => ['AdPositionController', 'store'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->get('/admin/ads/positions/edit/{id}', ['uses' => ['AdPositionController', 'edit'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->post('/admin/ads/positions/update/{id}', ['uses' => ['AdPositionController', 'update'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
]]);
$router->post('/admin/ads/positions/delete/{id}', ['uses' => ['AdPositionController', 'delete'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'ads.manage']
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
$router->get('/admin/guests/data', ['uses' => ['AdminGuestController', 'data'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'manage_events'] // il permesso corretto dal DB
]]);
$router->get('/admin/guests/detail/{id}', ['uses' => ['AdminGuestController', 'detail'], 'middlewares' => [
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

$router->get('/dashboard/ads', ['uses' => ['AdCampaignController', 'index'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/ads/campaigns', ['uses' => ['AdCampaignController', 'index'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/ads/campaigns/index', ['uses' => ['AdCampaignController', 'index'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/ads/campaigns/create', ['uses' => ['AdCampaignController', 'create'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->post('/dashboard/ads/campaigns/store', ['uses' => ['AdCampaignController', 'store'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/ads/campaigns/{slug}/review', ['uses' => ['AdCampaignController', 'review'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/ads/campaigns/{slug}/stats', ['uses' => ['AdStatsController', 'campaign'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/ads/stats', ['uses' => ['AdStatsController', 'overview'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/ads/stats/{id}', ['uses' => ['AdStatsController', 'campaign'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/ads/campaigns/checkout', ['uses' => ['AdCampaignController', 'checkout'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->post('/dashboard/ads/campaigns/{slug}/cancel', ['uses' => ['AdCampaignController', 'cancel'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->post('/dashboard/ads/campaigns/{slug}/checkout', ['uses' => ['AdCampaignController', 'checkout'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/ads/payments/{slug}/status', ['uses' => ['AdPaymentController', 'status'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->post('/dashboard/ads/payments/{slug}/status', ['uses' => ['AdPaymentController', 'status'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/ads/campaigns/estimatePrice', ['uses' => ['AdCampaignController', 'estimatePrice'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/ads/banners', ['uses' => ['AdBannerController', 'index'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/ads/banners/create', ['uses' => ['AdBannerController', 'create'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->post('/dashboard/ads/banners/store', ['uses' => ['AdBannerController', 'store'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->get('/dashboard/ads/banners/edit/{id}', ['uses' => ['AdBannerController', 'edit'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->post('/dashboard/ads/banners/update/{id}', ['uses' => ['AdBannerController', 'update'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);
$router->post('/dashboard/ads/banners/delete/{id}', ['uses' => ['AdBannerController', 'delete'], 'middlewares' => [
	AuthMiddleware::class,
	[PermissionMiddleware::class, 'access_dashboard']
]]);


// Fine rotte
