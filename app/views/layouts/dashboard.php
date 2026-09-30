<?php
// layouts/default.php
// Non chiudere mai il tag PHP per evitare output prematuro

use App\Models\User;
use App\Services\CookiePolicyService;
use App\Services\NotificationService;
use App\Services\SiteFeatureFlagService;
$user = $user ?? [];
$featureFlags = (new SiteFeatureFlagService())->getEnabledMap();
$cookiePolicy = (new CookiePolicyService())->getCurrentPolicy();
$cookiePolicySummary = 'Questo sito utilizza cookie tecnici e, con il tuo consenso, cookie di profilazione e di terze parti.';
if (!empty($cookiePolicy['content'])) {
	$plainCookieContent = trim(preg_replace('/\s+/', ' ', strip_tags((string) $cookiePolicy['content'])));
	if ($plainCookieContent !== '') {
		$cookiePolicySummary = mb_strlen($plainCookieContent) > 220
			? mb_substr($plainCookieContent, 0, 220) . '...'
			: $plainCookieContent;
	}
}
$notificationCount = 0;
if (isset($_SESSION['user_id'])) {
	$notificationCount = (new NotificationService())->getUnreadCount((int) $_SESSION['user_id']);
}

?><!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- Tailwind & Fonts -->
	<link rel="stylesheet" href="/public_assets/css/tailwind.css">
	<link href="/public_assets/vendor/fontawesome/css/all.min.css" rel="stylesheet">

	<!-- Favicon -->
	<link rel="icon" type="image/x-icon" href="/favicon.ico">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="192x192" href="/favicon-192x192.png">
	<link rel="apple-touch-icon" href="/apple-touch-icon.png">

	<link href="/public_assets/cookie-banner.css" rel="stylesheet">

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

		.flash-message {
			padding: 1rem;
			margin-bottom: 1.5rem;
			border-radius: .5rem;
			font-weight: 500;
		}

		.flash-message.success {
			background-color: #d4edda;
			color: #155724;
			border: 1px solid #c3e6cb;
		}

		.flash-message.error {
			background-color: #f8d7da;
			color: #721c24;
			border: 1px solid #f5c6cb;
		}

		.flash-message.info {
			background-color: #d1ecf1;
			color: #0c5460;
			border: 1px solid #bee5eb;
		}

		.mobile-menu {
			display: none;
			color: #333333;
		}

		.mobile-menu.active {
			display: block;
		}

		@media (max-width: 767px) {
			.desktop-menu {
				display: none;
			}
		}

		@media (min-width: 768px) {
			.hamburger-button {
				display: none;
			}

			.mobile-menu {
				display: none !important;
			}
		}
	</style>

</head>
<body class="flex flex-col min-h-screen">
<header class="bg-white text-white p-4 shadow-md">
	<div class="container mx-auto flex justify-between items-center">
		<button id="sidebar-open"
		        class="md:hidden mr-3 text-green-900 hover:text-green-700">
			<svg xmlns="http://www.w3.org/2000/svg"
			     class="h-7 w-7"
			     fill="none"
			     viewBox="0 0 24 24"
			     stroke="currentColor">
				<path stroke-linecap="round"
				      stroke-linejoin="round"
				      stroke-width="2"
				      d="M4 6h16M4 12h16M4 18h16"/>
			</svg>
		</button>
		<div class="flex items-center">
			<a href="/" class="text-black hover:text-gray-500 transition duration-300 ease-in-out" title="Italian Cosplay">
				<img src="/public_assets/images/logo_italian_cosplay.webp" alt="<?php echo APP_NAME?> Logo" class="h-8 w-auto inline-block">
			</a>
		</div>

		<nav class="desktop-menu" aria-label="Menu principale desktop">
			<ul class="flex space-x-4">
				<li><a href="/eventi-cosplay" class="text-black hover:text-gray-500 transition">Eventi Cosplay Italia</a></li>
				<?php
				if(isset($_SESSION['user_id'])):
					$userModel = new User();
					if($userModel->load($_SESSION['user_id'])){
						if($userModel->isAdmin()){
							?>

							<li><a href="/admin/dashboard" class="text-black hover:text-gray-500 transition">Dashboard Admin</a></li>
							<?php
						}else{ ?>
							<li><a href="/dashboard" class="text-black hover:text-gray-500 transition">La Mia Dashboard</a></li>
							<?php if (!empty($featureFlags['enable_notifications'])): ?>
								<li>
									<a href="/dashboard/notifications" class="relative inline-flex items-center text-black hover:text-gray-500 transition" aria-label="Notifiche">
										<i class="fa-solid fa-bell"></i>
										<?php if ($notificationCount > 0): ?>
											<span class="absolute -right-2 -top-2 inline-flex min-w-5 justify-center rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">
												<?php echo (int) $notificationCount; ?>
											</span>
										<?php endif; ?>
									</a>
								</li>
							<?php endif; ?>
						<?php }} ?>
					<li>
						<form action="/logout" method="POST" class="inline">
							<input type="hidden" name="csrf_token" value="<?php echo  $_SESSION['csrf_token'] ?>">
							<button type="submit" class="text-black hover:text-gray-500 transition bg-transparent border-none p-0 cursor-pointer">Logout</button>
						</form>
					</li>
				<?php else: ?>
					<li><a href="/login" class="text-black hover:text-gray-500 transition">Accedi</a></li>
					<li><a href="/register" class="text-black hover:text-gray-500 transition">Registrati</a></li>
				<?php endif; ?>
			</ul>
		</nav>

		<a href="/" class="flex items-center gap-2 text-green-900 hover:text-green-700">
			<svg xmlns="http://www.w3.org/2000/svg"
			     class="h-5 w-5"
			     fill="none"
			     viewBox="0 0 24 24"
			     stroke="currentColor">
				<path stroke-linecap="round"
				      stroke-linejoin="round"
				      stroke-width="2"
				      d="M15.75 19.5L8.25 12l7.5-7.5"/>
			</svg>
			Torna al sito
		</a>
	</div>


</header>
<div id="favorite-toast" class="fixed right-4 top-4 z-50 hidden rounded-xl bg-green-900 px-4 py-3 text-sm font-semibold text-white shadow-lg" role="status" aria-live="polite"></div>
<div class="container mx-auto p-4 md:flex gap-6 relative">

	<!-- SIDEBAR -->
	<!-- OVERLAY MOBILE -->
	<div id="sidebar-overlay"
	     class="fixed inset-0 bg-black/50 z-40 hidden md:hidden"></div>

	<!-- SIDEBAR -->
	<aside id="dashboard-sidebar"
	       class="
       fixed md:static
       top-0 left-0
       h-full md:h-auto
       w-72 md:w-64
       bg-green-900 text-white
       flex flex-col
       rounded-none md:rounded-2xl
       overflow-hidden
       z-50
       transform -translate-x-full md:translate-x-0
       transition-transform duration-300 ease-in-out
       ">

		<!-- HEADER -->
		<div class="p-6 text-center border-b border-green-800 relative">

			<!-- CLOSE MOBILE -->
			<button id="sidebar-close"
			        class="absolute right-3 top-3 md:hidden text-green-200 hover:text-white">
				✕
			</button>

			<div class="mx-auto w-20 h-20 rounded-full bg-green-800 flex items-center justify-center text-xl font-bold">
				<?php if($user['avatar'] ?? ''){ ?>
					<img id="avatar-preview_sidebar" src="<?php echo  $user['avatar'] ?>" alt="Avatar"  class="w-24 h-24 rounded-full mb-2" width="80" height="80">
				<?php }else{
					echo  strtoupper(substr($user['username'] ?? 'U', 0, 1));
				} ?>
			</div>

			<h2 class="mt-2 text-lg font-semibold">
				<?php echo  htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))); ?>
			</h2>

			<p class="text-sm text-green-200">
				@<?php echo  htmlspecialchars($user['username'] ?? ''); ?>
			</p>
		</div>

		<nav class="flex-1 p-4 space-y-2">
			<a href="/dashboard" class="block px-4 py-2 rounded hover:bg-green-800 transition">Panoramica</a>
			<a href="/dashboard/profile" class="block px-4 py-2 rounded hover:bg-green-800 transition">Profilo</a>
			<a href="/dashboard/change_password" class="block px-4 py-2 rounded hover:bg-green-800 transition">Cambio Password</a>
			<a href="/dashboard/avatar" class="block px-4 py-2 rounded hover:bg-green-800 transition">Cambio avatar</a>
			<a href="/dashboard/cover" class="block px-4 py-2 rounded hover:bg-green-800 transition">Cambio Cover</a>
			<?php if (!empty($featureFlags['enable_favorites'])): ?>
				<a href="/dashboard/favorites" class="block px-4 py-2 rounded hover:bg-green-800 transition">Preferiti</a>
			<?php endif; ?>
			<?php if (!empty($featureFlags['enable_cosplay_portfolio'])): ?>
				<a href="/dashboard/cosplay" class="block px-4 py-2 rounded hover:bg-green-800 transition">Portfolio cosplay</a>
			<?php endif; ?>
			<?php if (!empty($featureFlags['enable_personal_agenda'])): ?>
				<a href="/dashboard/events" class="block px-4 py-2 rounded hover:bg-green-800 transition">Agenda Cosplay</a>
			<?php endif; ?>
			<a href="/dashboard/photos" class="block px-4 py-2 rounded hover:bg-green-800 transition">Le mie foto</a>
			<?php if (!empty($featureFlags['enable_notifications'])): ?>
				<a href="/dashboard/notifications" class="block px-4 py-2 rounded hover:bg-green-800 transition">Notifiche</a>
			<?php endif; ?>
			<a href="/dashboard/inviti" class="block px-4 py-2 rounded hover:bg-green-800 transition">Invita amici</a>
			<?php if (!empty($featureFlags['enable_advertising'])): ?>
				<div class="mt-4 pt-4 border-t border-green-800">
					<p class="px-4 text-xs font-bold uppercase tracking-wide text-green-200">Advertising</p>
					<a href="/dashboard/ads" class="mt-2 block px-4 py-2 rounded hover:bg-green-800 transition">Panoramica adv</a>
					<a href="/dashboard/ads/campaigns" class="block px-4 py-2 rounded hover:bg-green-800 transition">Campagne</a>
					<a href="/dashboard/ads/banners" class="block px-4 py-2 rounded hover:bg-green-800 transition">Banner</a>
					<a href="/dashboard/ads/campaigns/create" class="block px-4 py-2 rounded hover:bg-green-800 transition">Compra spazio</a>
				</div>
			<?php endif; ?>
			<a href="/dashboard/settings" class="block px-4 py-2 rounded hover:bg-green-800 transition">Impostazioni</a>
		</nav>

		<div class="p-4 border-t border-green-800">
			<form action="/logout" method="POST">
				<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
				<button type="submit"
				        class="w-full bg-green-800 hover:bg-green-700 py-2 rounded transition">
					Logout
				</button>
			</form>
		</div>
	</aside>

	<!-- CONTENUTO -->
	<main class="flex-1 min-w-0 bg-white rounded-2xl p-6 shadow">
		<?php echo $content_for_layout ?>
	</main>

</div>
<footer class="bg-gray-800 text-white p-4 text-center mt-8">
	<p>&copy; <?php echo date('Y')?> <?php echo APP_NAME?>. Tutti i diritti riservati.</p>
	<p><a href="/privacy" class="text-white">Privacy</a> | <a href="/cookies" class="text-white">Cookies</a></p>
	<a href="#" id="manage-consent" class="text-white">Modifica preferenze cookie</a>
</footer>

<div id="cookie-banner">
	<div class="cookie-banner-content">
		<h3>Informativa sui Cookie</h3>
		<p><?php echo htmlspecialchars($cookiePolicySummary, ENT_QUOTES, 'UTF-8'); ?> <a href="/cookies">Leggi la policy</a>.</p>
		<div class="cookie-options">
			<h4>Personalizza</h4>
			<label><input type="checkbox" id="consent-necessary" checked disabled> Necessari</label>
			<label><input type="checkbox" id="consent-analytics" class="consent-choice"> Analitici</label>
			<?php if (!empty($featureFlags['enable_marketing'])): ?>
				<label><input type="checkbox" id="consent-marketing" class="consent-choice"> Marketing</label>
			<?php endif; ?>
		</div>
		<div class="cookie-actions">
			<button id="btn-accept-selected">Accetta Selezionati</button>
			<button id="btn-accept-all">Accetta Tutti</button>
		</div>
	</div>
</div>

<script>
	document.addEventListener('DOMContentLoaded', () => {
		function getCookie(name) {
			const value = `; ${document.cookie}`;
			const parts = value.split(`; ${name}=`);
			if (parts.length === 2) {
				return parts.pop().split(';').shift();
			}
			return null;
		}

		const consentBanner = document.getElementById('cookie-banner');
		const btnAcceptAll = document.getElementById('btn-accept-all');
		const btnAcceptSelected = document.getElementById('btn-accept-selected');
		const manageButton = document.getElementById('manage-consent');

		function showConsentBanner() {
			if (consentBanner) consentBanner.style.display = 'block';
		}

		function hideConsentBanner() {
			if (consentBanner) consentBanner.style.display = 'none';
		}

		async function saveConsent(details) {
			try {
				const response = await fetch('/save-consent.php', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(details),
				});

				const result = await response.json();
				if (result.status === 'success') {
					hideConsentBanner();
					activateScripts(details);
				}
			} catch (error) {
				console.error('Errore di rete:', error);
			}
		}

		function activateScripts(consentDetails) {
			document.querySelectorAll('script[data-consent-category]').forEach((script) => {
				const category = script.dataset.consentCategory;
				if (consentDetails[category]) {
					const newScript = document.createElement('script');
					for (let i = 0; i < script.attributes.length; i++) {
						const attr = script.attributes[i];
						newScript.setAttribute(attr.name, attr.value);
					}
					newScript.type = 'text/javascript';
					newScript.innerHTML = script.innerHTML;
					script.parentNode.replaceChild(newScript, script);
				}
			});
		}

		const marketingEnabled = <?php echo !empty($featureFlags['enable_marketing']) ? 'true' : 'false'; ?>;

		if (btnAcceptAll) {
			btnAcceptAll.addEventListener('click', () => {
				saveConsent({ analytics: true, marketing: marketingEnabled });
			});
		}

		if (btnAcceptSelected) {
			btnAcceptSelected.addEventListener('click', () => {
				const consentDetails = {
					analytics: document.getElementById('consent-analytics').checked,
					marketing: marketingEnabled ? document.getElementById('consent-marketing').checked : false,
				};
				saveConsent(consentDetails);
			});
		}

		if (manageButton) {
			manageButton.addEventListener('click', (event) => {
				event.preventDefault();
				showConsentBanner();
			});
		}

		const consentCookie = getCookie('user_cookie_consent');
		if (consentCookie) {
			try {
				const decodedConsent = decodeURIComponent(consentCookie);
				const consentDetails = JSON.parse(decodedConsent);
				activateScripts(consentDetails);
				hideConsentBanner();
			} catch (e) {
				console.error('Errore nel parsing del cookie di consenso:', e);
				showConsentBanner();
			}
		} else {
			showConsentBanner();
		}

		const hamburgerButton = document.getElementById('hamburger-button');
		const mobileMenu = document.getElementById('mobile-menu');
		if (hamburgerButton && mobileMenu) {
			hamburgerButton.addEventListener('click', () => {
				mobileMenu.classList.toggle('active');
			});
		}
	});
</script>

<script>

	document.addEventListener('DOMContentLoaded', () => {

		const sidebar = document.getElementById('dashboard-sidebar');
		const overlay = document.getElementById('sidebar-overlay');
		const openBtn = document.getElementById('sidebar-open');
		const closeBtn = document.getElementById('sidebar-close');

		if (!sidebar) return;

		function openSidebar() {
			sidebar.classList.remove('-translate-x-full');
			overlay.classList.remove('hidden');
			document.body.classList.add('overflow-hidden');
		}

		function closeSidebar() {
			sidebar.classList.add('-translate-x-full');
			overlay.classList.add('hidden');
			document.body.classList.remove('overflow-hidden');
		}

		openBtn?.addEventListener('click', openSidebar);
		closeBtn?.addEventListener('click', closeSidebar);
		overlay?.addEventListener('click', closeSidebar);

		// ✅ safety resize
		window.addEventListener('resize', () => {
			if (window.innerWidth >= 768) {
				overlay.classList.add('hidden');
				sidebar.classList.remove('-translate-x-full');
				document.body.classList.remove('overflow-hidden');
			}
		});

	});
</script>
</body>
</html>
