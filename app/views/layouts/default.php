<?php
// layouts/default.php
// Non chiudere mai il tag PHP per evitare output prematuro
use App\Core\Router;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\CookiePolicyService;
use App\Services\SiteFeatureFlagService;
global $router;

// Legge consenso cookie

$cookieConsent = isset($_COOKIE['user_cookie_consent']) ? json_decode($_COOKIE['user_cookie_consent'], true) : null;

// Contenuto flash
$flashMessages = $_SESSION['flash_messages'] ?? [];
unset($_SESSION['flash_messages']);

// Variabile principale della view
$content = $content_for_layout ?? '';

// SEO meta
$evento = $data['event'] ?? null;
$eventMaster = $data['eventMaster'] ?? ($data['event_master'] ?? null);
$eventMasterCount = $data['eventCount'] ?? ($data['event_master_count'] ?? null);
$guest = $data['guest'] ?? null;
$regione_nome = $data['selected_regione']['nome'] ?? null;
$provincia_nome = $data['selected_provincia']['nome'] ?? null;
$comune_nome = $data['selected_comune']['nome'] ?? null;
$weekend = $data['weekend'] ?? null;
$mese = $data['mese'] ?? null;
$post_seo = $data['post'] ?? null;
$blog_categoria = $data['categoria'] ?? null;
$featureFlags = (new SiteFeatureFlagService())->getEnabledMap();
$telegramChannelUrl = defined('TELEGRAM_CHANNEL_URL') ? trim((string) TELEGRAM_CHANNEL_URL) : '';
if ($telegramChannelUrl === '' && defined('TELEGRAM_CHANNEL_CHAT_ID')) {
	$telegramChannelId = trim((string) TELEGRAM_CHANNEL_CHAT_ID);
	if (str_starts_with($telegramChannelId, '@')) {
		$telegramChannelUrl = 'https://t.me/' . ltrim($telegramChannelId, '@');
	}
}
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
	<meta name="robots" content="max-image-preview:large">
			<?php Router::seoMetaTags($router, [
			'evento'         => $evento,
			'event_master'   => $eventMaster,
			'event_master_count' => $eventMasterCount,
			'regione_nome'   => $regione_nome,
			'provincia_nome' => $provincia_nome,
			'comune_nome'    => $comune_nome,
			'weekend'    => $weekend,
			'mese'    => $mese,
			'year'    => $year ?? null,
			'eventCount' => $eventCount ?? null,
			'guest' =>$guest,
			'blog' =>$post_seo,
			'blog_categoria' =>$blog_categoria,
			'organization' => $data['organization'] ?? null,
			'organization_meta_title' => $data['organization_meta_title'] ?? null,
			'organization_meta_description' => $data['organization_meta_description'] ?? null,
			'organization_image' => $data['organization_image'] ?? null,
			'canonicalUrl' => $canonicalUrl ?? '',
	]); ?>

	<link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl ?? '', ENT_QUOTES, 'UTF-8')?>">
	<?php if(!empty($noindex)): ?>
		<meta name="robots" content="noindex, follow">
	<?php endif; ?>


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
			color: #0f172a;
		}

		.mobile-menu.active {
			display: block;
		}

		.mobile-menu-panel {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 1rem;
			box-shadow: 0 18px 36px rgba(15, 23, 42, 0.14);
			margin-top: 1rem;
			padding: 0.75rem;
		}

		.mobile-menu-link,
		.mobile-menu-button {
			align-items: center;
			border-radius: 0.75rem;
			color: #0f172a;
			display: flex;
			font-size: 1rem;
			font-weight: 800;
			gap: 0.75rem;
			min-height: 3rem;
			padding: 0.75rem 0.875rem;
			text-decoration: none;
			width: 100%;
		}

		.mobile-menu-link:hover,
		.mobile-menu-link:focus,
		.mobile-menu-button:hover,
		.mobile-menu-button:focus {
			background: #f1f5f9;
			outline: none;
		}

		.mobile-menu-link-primary {
			background: #047857;
			color: #ffffff;
		}

		.mobile-menu-link-primary:hover,
		.mobile-menu-link-primary:focus {
			background: #065f46;
		}

		.mobile-menu-link-accent {
			background: #fffbeb;
			border: 1px solid #fde68a;
			color: #78350f;
		}

		.mobile-menu-icon {
			align-items: center;
			background: #ecfdf5;
			border-radius: 9999px;
			color: #047857;
			display: inline-flex;
			flex: 0 0 auto;
			height: 2rem;
			justify-content: center;
			width: 2rem;
		}

		.mobile-menu-link-primary .mobile-menu-icon {
			background: rgba(255, 255, 255, 0.18);
			color: #ffffff;
		}

		.mobile-menu-section {
			border-top: 1px solid #e2e8f0;
			margin-top: 0.5rem;
			padding-top: 0.5rem;
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

	<!-- Script opzionali bloccati fino al consenso -->
	<script type="text/plain" data-consent-category="marketing">
		console.log("Marketing scripts blocked until consent");
	</script>
</head>
<body class="flex flex-col min-h-screen">

<header class="bg-white text-white p-4 shadow-md">
	<?php
	$isLoggedIn = isset($_SESSION['user_id']);
	$currentUser = null;
	$displayUsername = $_SESSION['username'] ?? null;

	if ($isLoggedIn) {
		$userModel = new User();
		$userData = $userModel->find((int) $_SESSION['user_id']);

		if ($userData !== false && is_array($userData) && $userModel->load((int) $_SESSION['user_id'])) {
			$currentUser = $userModel;
			$isLoggedIn = true;
			if ($displayUsername === null && !empty($userData['username'])) {
				$displayUsername = $userData['username'];
			}
		} else {
			$isLoggedIn = false;
		}
	}
	?>
	<div class="container mx-auto flex justify-between items-center">
		<div class="flex items-center">
			<a href="/" class="text-black hover:text-gray-500 transition duration-300 ease-in-out" title="Italian Cosplay">
				<img src="/public_assets/images/logo_italian_cosplay.webp" width="366" height="50footer" alt="<?php echo APP_NAME?> Logo" class="h-8 w-auto inline-block">
			</a>
		</div>

		<nav class="desktop-menu" aria-label="Menu principale desktop">
			<ul class="flex space-x-4">
				<?php if (!empty($featureFlags['enable_events'])): ?>
					<li><a href="/eventi-cosplay" class="text-black hover:text-gray-500 transition">Eventi Cosplay Italia</a></li>
				<?php endif; ?>
				<?php if (!empty($featureFlags['enable_blog'])): ?>
					<li><a href="/blog"  class="text-black hover:text-gray-500 transition">Blog</a></li>
				<?php endif; ?>
				<li><a href="/faq" class="text-black hover:text-gray-500 transition">FAQ</a></li>
				<li><a href="/segnala-evento-cosplay"  class="text-black hover:text-gray-500 transition">Segnala il tuo evento Cosplay</a></li>

					<?php if ($isLoggedIn && $currentUser !== null): ?>
						<?php if ($currentUser->isAdmin()): ?>
							<li><a href="/admin/dashboard" class="text-black hover:text-gray-500 transition">Dashboard Admin</a></li>
						<?php else: ?>
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
						<?php endif; ?>
					<?php if (!empty($displayUsername)): ?>
						<li>
							<span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-900 ring-1 ring-amber-200">
								Ciao, @<?php echo htmlspecialchars((string) $displayUsername); ?>
							</span>
						</li>
					<?php endif; ?>
					<li>
						<form action="/logout" method="POST" class="inline">
							<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
							<button type="submit" class="text-black hover:text-gray-500 transition bg-transparent border-none p-0 cursor-pointer">Logout</button>
						</form>
					</li>
				<?php else: ?>
					<?php if (!empty($featureFlags['enable_user_registration'])): ?>
						<li><a href="/login" class="text-black hover:text-gray-500 transition">Accedi</a></li>
						<li><a href="/register" class="text-black hover:text-gray-500 transition">Registrati</a></li>
					<?php endif; ?>
				<?php endif; ?>
			</ul>
		</nav>

		<button id="hamburger-button" class="hamburger-button text-black focus:outline-none md:hidden" aria-label="Apri menu" aria-expanded="false" aria-controls="mobile-menu">
			<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
			</svg>
		</button>
	</div>

	<nav id="mobile-menu" class="mobile-menu md:hidden" aria-label="Menu principale mobile">
		<div class="mobile-menu-panel">
		<ul class="space-y-1">
			<?php if (!empty($featureFlags['enable_events'])): ?>
				<li>
					<a href="/eventi-cosplay" class="mobile-menu-link mobile-menu-link-primary">
						<span class="mobile-menu-icon"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i></span>
						<span>Eventi cosplay</span>
					</a>
				</li>
			<?php endif; ?>
			<?php if (!empty($featureFlags['enable_blog'])): ?>
				<li>
					<a href="/blog" class="mobile-menu-link">
						<span class="mobile-menu-icon"><i class="fa-solid fa-newspaper" aria-hidden="true"></i></span>
						<span>Blog</span>
					</a>
				</li>
			<?php endif; ?>
			<li>
				<a href="/faq" class="mobile-menu-link">
					<span class="mobile-menu-icon"><i class="fa-solid fa-circle-question" aria-hidden="true"></i></span>
					<span>FAQ</span>
				</a>
			</li>
			<li>
				<a href="/segnala-evento-cosplay" class="mobile-menu-link mobile-menu-link-accent">
					<span class="mobile-menu-icon"><i class="fa-solid fa-plus" aria-hidden="true"></i></span>
					<span>Segnala evento</span>
				</a>
			</li>
		</ul>
		<ul class="mobile-menu-section space-y-1">
				<?php if ($isLoggedIn && $currentUser !== null): ?>
					<?php if ($currentUser->isAdmin()): ?>
						<li>
							<a href="/admin/dashboard" class="mobile-menu-link">
								<span class="mobile-menu-icon"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i></span>
								<span>Dashboard Admin</span>
							</a>
						</li>
					<?php else: ?>
						<li>
							<a href="/dashboard" class="mobile-menu-link">
								<span class="mobile-menu-icon"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i></span>
								<span>La mia dashboard</span>
							</a>
						</li>
						<?php if (!empty($featureFlags['enable_notifications'])): ?>
							<li>
								<a href="/dashboard/notifications" class="mobile-menu-link relative" aria-label="Notifiche">
									<span class="mobile-menu-icon"><i class="fa-solid fa-bell" aria-hidden="true"></i></span>
									<span>Notifiche</span>
									<?php if ($notificationCount > 0): ?>
										<span class="ml-auto inline-flex min-w-5 justify-center rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">
											<?php echo (int) $notificationCount; ?>
										</span>
									<?php endif; ?>
								</a>
							</li>
						<?php endif; ?>
					<?php endif; ?>
				<?php if (!empty($displayUsername)): ?>
					<li>
						<span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-900 ring-1 ring-amber-200">
							Ciao, @<?php echo htmlspecialchars((string) $displayUsername); ?>
						</span>
					</li>
				<?php endif; ?>
				<li>
					<form action="/logout" method="POST">
						<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
						<button type="submit" class="mobile-menu-button bg-transparent border-none cursor-pointer">
							<span class="mobile-menu-icon"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i></span>
							<span>Logout</span>
						</button>
					</form>
				</li>
			<?php else: ?>
				<?php if (!empty($featureFlags['enable_user_registration'])): ?>
					<li>
						<a href="/login" class="mobile-menu-link">
							<span class="mobile-menu-icon"><i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i></span>
							<span>Accedi</span>
						</a>
					</li>
					<li>
						<a href="/register" class="mobile-menu-link mobile-menu-link-primary">
							<span class="mobile-menu-icon"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></span>
							<span>Registrati</span>
						</a>
					</li>
				<?php endif; ?>
			<?php endif; ?>
		</ul>
		</div>
	</nav>
</header>

<div id="favorite-toast" class="fixed right-4 top-4 z-50 hidden rounded-xl bg-green-900 px-4 py-3 text-sm font-semibold text-white shadow-lg" role="status" aria-live="polite"></div>
<main class="flex-grow container mx-auto p-6">
	<?php foreach($flashMessages as $type => $message): ?>
		<div class="flash-message <?php echo htmlspecialchars($type)?>"><?php echo htmlspecialchars($message)?></div>
	<?php endforeach; ?>

	<?php echo $content ?>
</main>

<footer class="bg-gray-800 text-white p-4 text-center mt-8">
	<p>&copy; <?php echo date('Y')?> <?php echo APP_NAME?>. Tutti i diritti riservati.</p>
	<p><a href="/privacy" class="text-white">Privacy</a> | <a href="/cookies" class="text-white">Cookies</a><?php if ($telegramChannelUrl !== ''): ?> | <?php $variant = 'footer'; $context = 'default'; require APP_ROOT . '/app/views/components/telegram-channel-cta.php'; ?><?php endif; ?></p>
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
			if (consentBanner) {
				consentBanner.style.display = 'block';
				document.body.classList.add('ic-cookie-banner-visible');
			}
		}

		function hideConsentBanner() {
			if (consentBanner) {
				consentBanner.style.display = 'none';
				document.body.classList.remove('ic-cookie-banner-visible');
			}
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
				const isOpen = mobileMenu.classList.toggle('active');
				hamburgerButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
				hamburgerButton.setAttribute('aria-label', isOpen ? 'Chiudi menu' : 'Apri menu');
			});
		}

		const favoriteToast = document.getElementById('favorite-toast');

		function showFavoriteToast(message, type = 'success') {
			if (!favoriteToast) {
				return;
			}

			favoriteToast.textContent = message;
			favoriteToast.className = `fixed right-4 top-4 z-50 rounded-xl px-4 py-3 text-sm font-semibold shadow-lg ${type === 'success' ? 'bg-green-900 text-white' : 'bg-red-900 text-white'}`;
			favoriteToast.classList.remove('hidden');
			clearTimeout(window.__favoriteToastTimer);
			window.__favoriteToastTimer = setTimeout(() => {
				favoriteToast.classList.add('hidden');
			}, 2200);
		}

		document.addEventListener('submit', async (event) => {
			const form = event.target;
			if (!(form instanceof HTMLFormElement) || !form.classList.contains('js-favorite-toggle')) {
				return;
			}

			event.preventDefault();
			const button = form.querySelector('.js-favorite-button');
			if (button) {
				button.disabled = true;
			}

			try {
				const response = await fetch(form.action, {
					method: 'POST',
					headers: {
						'X-Requested-With': 'XMLHttpRequest',
						'Accept': 'application/json',
					},
					body: new FormData(form),
				});

				if (response.redirected) {
					window.location.href = response.url;
					return;
				}

				const result = await response.json();
				if (!response.ok || !result.success) {
					throw new Error(result.message || 'Impossibile aggiornare il preferito.');
				}

				if (button) {
					const icon = button.querySelector('i');
					const label = button.querySelector('span');
					const active = !!result.isFavorited;
					button.dataset.active = active ? '1' : '0';
					button.classList.toggle('bg-amber-800', active);
					button.classList.toggle('text-white', active);
					button.classList.toggle('hover:bg-amber-900', active);
					button.classList.toggle('bg-white', !active);
					button.classList.toggle('text-amber-900', !active);
					button.classList.toggle('hover:bg-amber-100', !active);
					if (icon) {
						icon.className = `fa-solid ${active ? button.dataset.iconRemove : button.dataset.iconAdd}`;
					}
					if (label) {
						label.textContent = active ? button.dataset.labelRemove : button.dataset.labelAdd;
					}
				}

				if (form.dataset.removeCard === '1' && !result.isFavorited) {
					const card = form.closest('article');
					if (card) {
						card.remove();
					}
				}

				showFavoriteToast(result.message || 'Preferito aggiornato.');
			} catch (error) {
				showFavoriteToast(error.message || 'Impossibile aggiornare il preferito.', 'error');
			} finally {
				if (button) {
					button.disabled = false;
				}
			}
		});

		document.addEventListener('submit', async (event) => {
			const form = event.target;
			if (!(form instanceof HTMLFormElement) || !form.classList.contains('js-agenda-toggle')) {
				return;
			}

			event.preventDefault();
			const button = form.querySelector('.js-agenda-button');
			if (button) {
				button.disabled = true;
			}

			try {
				const response = await fetch(form.action, {
					method: 'POST',
					headers: {
						'X-Requested-With': 'XMLHttpRequest',
						'Accept': 'application/json',
					},
					body: new FormData(form),
				});

				if (response.redirected) {
					window.location.href = response.url;
					return;
				}

				const result = await response.json();
				if (!response.ok || !result.success) {
					throw new Error(result.message || 'Impossibile aggiornare l’agenda.');
				}

				if (button) {
					const card = form.closest('article');
					const currentStatus = form.querySelector('input[name="status"]')?.value || '';
					const statusLabel = {
						mi_interessa: 'mi interessa',
						ci_vado: 'ci vado',
						forse_vado: 'forse vado',
					}[currentStatus] || currentStatus.replaceAll('_', ' ');
					card?.querySelectorAll('.js-agenda-button').forEach((el) => {
						const active = el.dataset.agendaStatus === currentStatus;
						el.classList.toggle('border-green-700', active);
						el.classList.toggle('bg-green-700', active);
						el.classList.toggle('text-white', active);
						el.classList.toggle('border-gray-200', !active);
						el.classList.toggle('bg-white', !active);
						el.classList.toggle('text-gray-700', !active);
						el.disabled = false;
						el.dataset.active = active ? '1' : '0';
					});
					const badge = card?.querySelector('[data-agenda-badge]');
					if (badge) {
						const labels = {
							mi_interessa: { label: 'Mi interessa', className: 'bg-blue-100 text-blue-800' },
							ci_vado: { label: 'Ci vado', className: 'bg-emerald-100 text-emerald-800' },
							forse_vado: { label: 'Forse vado', className: 'bg-amber-100 text-amber-900' },
						};
						if (result.status && labels[result.status]) {
							badge.textContent = labels[result.status].label;
							badge.className = `absolute right-3 top-3 rounded-full px-3 py-1 text-xs font-bold ${labels[result.status].className}`;
						}
					}
					const currentLabel = card?.querySelector('[data-agenda-current]');
					if (currentLabel) {
						currentLabel.textContent = `Stato attuale: ${statusLabel}`;
					}
				}

				showFavoriteToast(result.message || 'Agenda aggiornata.');
			} catch (error) {
				showFavoriteToast(error.message || 'Impossibile aggiornare l’agenda.', 'error');
			} finally {
				if (button) {
					button.disabled = false;
				}
			}
		});
	});
</script>
<script src="/public_assets/js/accessibility/widget.js" defer></script>


</body>
</html>
