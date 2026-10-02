<?php

use App\Services\BrevoEmailQuotaService;

$brevoEmailQuota = (new BrevoEmailQuotaService())->getTodayQuota();
$brevoQuotaClasses = [
	'ok' => 'bg-emerald-100 text-emerald-900 border-emerald-200',
	'warning' => 'bg-amber-100 text-amber-900 border-amber-200',
	'critical' => 'bg-red-100 text-red-900 border-red-200',
	'exhausted' => 'bg-gray-950 text-white border-gray-700',
];
$brevoQuotaClass = $brevoQuotaClasses[$brevoEmailQuota['status'] ?? 'ok'] ?? 'bg-gray-100 text-gray-700 border-gray-200';
?>
<!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= APP_NAME ?> - Dashboard Admin</title>

	<link rel="stylesheet" href="/public_assets/css/tailwind.css">
	<link href="/public_assets/vendor/fontawesome/css/all.min.css" rel="stylesheet">

	<style>
		@font-face {
			font-family: 'InterLocal';
			font-style: normal;
			font-weight: 300 700;
			font-display: swap;
			src: url('/public_assets/fonts/inter/Inter-Variable.ttf') format('truetype');
		}

		@font-face {
			font-family: 'InterLocal';
			font-style: italic;
			font-weight: 300 700;
			font-display: swap;
			src: url('/public_assets/fonts/inter/Inter-Variable.ttf') format('truetype');
		}

		body {
			font-family: 'InterLocal', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
			background-color: #f3f4f6;
			color: #333;
		}

		.sidebar {
			width: 250px;
			min-width: 250px;
			background-color: #2d3748;
			color: #cbd5e0;
			padding: 1rem;
		}

		.admin-nav {
			display: flex;
			flex-direction: column;
			gap: 12px;
		}

		.nav-group {
			list-style: none;
			padding: 0;
			margin: 0;
		}

		.group-title {
			font-size: 12px;
			text-transform: uppercase;
			font-weight: 700;
			padding: 8px 10px;
			color: #a0aec0;
		}

		.group-title.toggle {
			cursor: pointer;
			display: flex;
			justify-content: space-between;
			align-items: center;
			color: #cbd5e0;
		}

		.group-items {
			list-style: none;
			margin: 0;
			padding-left: 10px;
			display: block;
		}

		.group-items.closed {
			display: none;
		}

		.sidebar a {
			display: flex;
			padding: 8px 10px;
			border-radius: 6px;
			text-decoration: none;
			color: #cbd5e0;
			transition: 0.2s;
		}

		.sidebar a:hover {
			background: #4a5568;
			color: #fff;
		}

		.sidebar a.active {
			background: #4299e1;
			color: #fff;
		}
		.admin-table {
			table-layout: fixed;
			width:100%;
		}


		.admin-table th,
		.admin-table td {

			overflow:hidden;

			text-overflow:ellipsis;

			white-space:nowrap;

		}


		.admin-table .truncate {

			overflow:hidden;

			text-overflow:ellipsis;

			white-space:nowrap;

		}

		.admin-toast {
			position: fixed;
			right: 1.5rem;
			bottom: 1.5rem;
			z-index: 1000;
			min-width: 280px;
			max-width: 420px;
			padding: 0.875rem 1rem;
			border-radius: 0.75rem;
			box-shadow: 0 12px 30px rgba(15, 23, 42, 0.18);
			color: #fff;
			opacity: 0;
			transform: translateY(12px);
			transition: opacity 180ms ease, transform 180ms ease;
			pointer-events: none;
		}

		.admin-toast.visible {
			opacity: 1;
			transform: translateY(0);
		}

		.admin-toast.success {
			background: #16a34a;
		}

		.admin-toast.error {
			background: #dc2626;
		}

		.admin-toast.info {
			background: #2563eb;
		}

		.admin-content > .container,
		.admin-content > [class*="max-w-"] {
			width: 100%;
			max-width: none;
		}
	</style>
	<script src="/public_assets/js/wysiwyg-editor.js"></script>
</head>

<body class="flex flex-col min-h-screen">

<header class="bg-green-700 text-white p-4">
	<div class="container mx-auto flex justify-between items-center">
		<h1 class="text-xl font-bold">Dashboard Admin</h1>

		<div class="flex items-center gap-3">
			<?php if (!empty($brevoEmailQuota['configured'])): ?>
				<div
					class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-semibold <?= htmlspecialchars($brevoQuotaClass, ENT_QUOTES, 'UTF-8') ?>"
					title="Email transazionali Brevo. Inviate oggi: <?= (int) $brevoEmailQuota['sentToday'] ?>. Disponibili: <?= (int) $brevoEmailQuota['remaining'] ?>. Limite giornaliero: <?= (int) $brevoEmailQuota['dailyLimit'] ?>. Ultimo aggiornamento: <?= htmlspecialchars((string) $brevoEmailQuota['updatedAt'], ENT_QUOTES, 'UTF-8') ?>."
				>
					<i class="fa-solid fa-envelope" aria-hidden="true"></i>
					<span><?= (int) $brevoEmailQuota['remaining'] ?> / <?= (int) $brevoEmailQuota['dailyLimit'] ?></span>
				</div>
			<?php else: ?>
				<div
					class="inline-flex items-center gap-2 rounded-md border border-gray-200 bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700"
					title="Configura BREVO_API_KEY per mostrare la quota email transazionale."
				>
					<i class="fa-solid fa-envelope" aria-hidden="true"></i>
					<span>Brevo non configurato</span>
				</div>
			<?php endif; ?>

			<form action="/logout" method="POST">
				<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
				<button class="bg-red-600 px-4 py-2 rounded">
					Logout
				</button>
			</form>
		</div>
	</div>
</header>

<div class="flex flex-grow">

	<!-- SIDEBAR -->
	<aside class="sidebar">

		<nav class="admin-nav">

			<!-- GENERALE -->
			<ul class="nav-group">
				<li class="group-title">Generale</li>

				<li>
					<a href="/admin/dashboard" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/dashboard') !== false) ? 'active' : '' ?>">
						Dashboard
					</a>
				</li>

				<li>
					<a href="/admin/users" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/users') !== false) ? 'active' : '' ?>">
						Utenti
					</a>
				</li>
				<li>
					<a href="/admin/privacy" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/privacy') !== false) ? 'active' : '' ?>">
						Privacy
					</a>
				</li>
				<li>
					<a href="/admin/cookies" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/cookies') !== false) ? 'active' : '' ?>">
						Cookie Policy
					</a>
				</li>
				<li>
					<a href="/admin/email-delivery-events" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/email-delivery-events') !== false) ? 'active' : '' ?>">
						Email Brevo
					</a>
				</li>
				<li>
					<a href="/admin/api-clients" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/api-clients') !== false) ? 'active' : '' ?>">
						API Management
					</a>
				</li>
				</ul>
			<ul class="nav-group">
				<li class="group-title toggle" data-target="moderation-admin">
					Moderazione
					<span>▾</span>
				</li>
				<ul id="moderation-admin" class="group-items <?= strpos($_SERVER['REQUEST_URI'], '/admin/photos') !== false ? '' : 'closed' ?>">
					<li>
						<a href="/admin/photos/reports" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/photos/reports') !== false) ? 'active' : '' ?>">
							Segnalazioni foto
						</a>
					</li>
					<li>
						<a href="/admin/photos/analytics" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/photos/analytics') !== false) ? 'active' : '' ?>">
							Statistiche foto
						</a>
					</li>
				</ul>
			</ul>
			<ul class="nav-group">
				<li class="group-title toggle" data-target="telegram-admin">
					Telegram
					<span>▾</span>
				</li>
				<ul id="telegram-admin" class="group-items <?= strpos($_SERVER['REQUEST_URI'], '/admin/telegram') !== false ? '' : 'closed' ?>">
					<li>
						<a href="/admin/telegram" class="<?= ($_SERVER['REQUEST_URI'] === '/admin/telegram' || strpos($_SERVER['REQUEST_URI'], '/admin/telegram?') === 0) ? 'active' : '' ?>">
							Calendario
						</a>
					</li>
					<li>
						<a href="/admin/telegram/create" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/telegram/create') !== false) ? 'active' : '' ?>">
							Crea messaggio
						</a>
					</li>
				</ul>
			</ul>
				<!-- EVENTI -->

				<ul class="nav-group">

				<li class="group-title toggle" data-target="events">
					Eventi
					<span>▾</span>
				</li>

				<ul id="events" class="group-items closed">
					<li>
						<a href="/admin/events/pending" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/events/pending') !== false) ? 'active' : '' ?>">
							Eventi in attesa
						</a>
					</li>

					<li>
						<a href="/admin/events/all" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/events/all') !== false) ? 'active' : '' ?>">
							Tutti gli eventi
						</a>
					</li>
					<li>
						<a href="/admin/events-master" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/events-master') !== false) ? 'active' : '' ?>">
							Eventi master
						</a>
					</li>
					<li>
						<a href="/admin/event-master-claims" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/event-master-claims') !== false) ? 'active' : '' ?>">
							Richieste di riscatto
						</a>
					</li>
					<li>
						<a href="/admin/organizations" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/organizations') !== false) ? 'active' : '' ?>">
							Organizzazioni
						</a>
					</li>
					<li>
						<a href="/admin/organization-emails" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/organization-emails') !== false) ? 'active' : '' ?>">
							Email organizzazioni
						</a>
					</li>
					<li>
						<a href="/admin/legacy-invitation-emails" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/legacy-invitation-emails') !== false) ? 'active' : '' ?>">
							Email vecchio sito
						</a>
					</li>
				</ul>
			</ul>
			<ul class="nav-group">

				<li class="group-title toggle" data-target="blog">
					Blog
					<span>▾</span>
				</li>

				<ul id="blog" class="group-items closed">
					<li>
						<a href="/admin/blog-categories/all" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/blog-categories/all') !== false) ? 'active' : '' ?>">
							Blog Categorie
						</a>
					</li>
					<li>
						<a href="/admin/blog/all" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/blog/all') !== false) ? 'active' : '' ?>">
							Blog
						</a>
					</li>
				</ul>
			</ul>

				<ul class="nav-group">

					<li class="group-title toggle" data-target="ads">
						Advertising
						<span>▾</span>
					</li>

					<ul id="ads" class="group-items closed">
						<li>
							<a href="/admin/ads/campaigns" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/ads/campaigns') !== false) ? 'active' : '' ?>">
								Campagne
							</a>
						</li>
						<li>
							<a href="/admin/ads/payments/logs" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/ads/payments/logs') !== false) ? 'active' : '' ?>">
								Log pagamenti
							</a>
						</li>
						<li>
							<a href="/admin/ads/positions" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/ads/positions') !== false) ? 'active' : '' ?>">
								Posizioni
							</a>
						</li>
					</ul>
				</ul>

			<ul class="nav-group">
				<li class="group-title toggle" data-target="faq-admin">
					FAQ
					<span>▾</span>
				</li>
				<ul id="faq-admin" class="group-items <?= strpos($_SERVER['REQUEST_URI'], '/admin/faq') !== false ? '' : 'closed' ?>">
					<li><a href="/admin/faq/categories" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/faq/categories') !== false) ? 'active' : '' ?>">Categorie</a></li>
					<li><a href="/admin/faq/items" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/faq/items') !== false) ? 'active' : '' ?>">Domande/risposte</a></li>
				</ul>
			</ul>

			<!-- CONTENUTI -->
			<ul class="nav-group">

				<li class="group-title toggle" data-target="content">
					Contenuti
					<span>▾</span>
				</li>

				<ul id="content" class="group-items closed">
					<li>
						<a href="/admin/guests/all" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/guests/all') !== false) ? 'active' : '' ?>">
							Guest
						</a>
					</li>
					<li>
						<a href="/admin/regioni/all" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/regioni/all') !== false) ? 'active' : '' ?>">
							Regioni
						</a>
					</li>
				</ul>
			</ul>

		</nav>

	</aside>

	<!-- CONTENT -->
	<main class="admin-content flex-grow p-6">
		<?= $content_for_layout ?? '' ?>
	</main>

</div>

<div id="admin-toast" class="admin-toast" role="status" aria-live="polite"></div>

<footer class="bg-gray-800 text-white text-center p-4">
	© <?= date('Y') ?> <?= APP_NAME ?>
</footer>

<script>
	document.querySelectorAll('.group-title.toggle').forEach(el => {
		el.addEventListener('click', () => {
			const target = document.getElementById(el.dataset.target);
			if (!target) return;
			target.classList.toggle('closed');
		});
	});
</script>
<?php
$adminAssetVersion = static function (string $path): string {
	$fullPath = APP_ROOT . $path;
	return file_exists($fullPath) ? (string) filemtime($fullPath) : (string) time();
};
?>
<script src="/public_assets/js/admin/admin-list.js?v=<?= htmlspecialchars($adminAssetVersion('/public_assets/js/admin/admin-list.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="/public_assets/js/admin/detail-panel/renderers/event.js?v=<?= htmlspecialchars($adminAssetVersion('/public_assets/js/admin/detail-panel/renderers/event.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="/public_assets/js/admin/detail-panel/renderers/event-master.js?v=<?= htmlspecialchars($adminAssetVersion('/public_assets/js/admin/detail-panel/renderers/event-master.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="/public_assets/js/admin/detail-panel/renderers/blog.js?v=<?= htmlspecialchars($adminAssetVersion('/public_assets/js/admin/detail-panel/renderers/blog.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="/public_assets/js/admin/detail-panel/renderers/blog-category.js?v=<?= htmlspecialchars($adminAssetVersion('/public_assets/js/admin/detail-panel/renderers/blog-category.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="/public_assets/js/admin/detail-panel/renderers/guest.js?v=<?= htmlspecialchars($adminAssetVersion('/public_assets/js/admin/detail-panel/renderers/guest.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="/public_assets/js/admin/detail-panel/renderers/region.js?v=<?= htmlspecialchars($adminAssetVersion('/public_assets/js/admin/detail-panel/renderers/region.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="/public_assets/js/admin/detail-panel/renderers/user.js?v=<?= htmlspecialchars($adminAssetVersion('/public_assets/js/admin/detail-panel/renderers/user.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="/public_assets/js/admin/detail-panel/renderers/ad-campaign.js?v=<?= htmlspecialchars($adminAssetVersion('/public_assets/js/admin/detail-panel/renderers/ad-campaign.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="/public_assets/js/admin/detail-panel/renderers/ad-position.js?v=<?= htmlspecialchars($adminAssetVersion('/public_assets/js/admin/detail-panel/renderers/ad-position.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="/public_assets/js/admin/detail-panel/renderers/email-delivery-event.js?v=<?= htmlspecialchars($adminAssetVersion('/public_assets/js/admin/detail-panel/renderers/email-delivery-event.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="/public_assets/js/admin/detail-panel/panel.js?v=<?= htmlspecialchars($adminAssetVersion('/public_assets/js/admin/detail-panel/panel.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
