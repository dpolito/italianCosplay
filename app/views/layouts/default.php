<?php
// layouts/default.php
// Non chiudere mai il tag PHP per evitare output prematuro
use App\Core\Router;
use App\Models\User;
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
$guest = $data['guest'] ?? null;
$regione_nome = $data['selected_regione']['nome'] ?? null;
$provincia_nome = $data['selected_provincia']['nome'] ?? null;
$comune_nome = $data['selected_comune']['nome'] ?? null;
$weekend = $data['weekend'] ?? null;
$mese = $data['mese'] ?? null;
$post_seo = $data['post'] ?? null;
$blog_categoria = $data['categoria'] ?? null;

?><!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="max-image-preview:large">
	<?php Router::seoMetaTags($router, [
			'evento'         => $evento,
			'regione_nome'   => $regione_nome,
			'provincia_nome' => $provincia_nome,
			'comune_nome'    => $comune_nome,
			'weekend'    => $weekend,
			'mese'    => $mese,
			'guest' =>$guest,
			'blog' =>$post_seo,
			'blog_categoria' =>$blog_categoria,
	]); ?>

	<link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl ?? '', ENT_QUOTES, 'UTF-8')?>">
	<?php if(!empty($noindex)): ?>
		<meta name="robots" content="noindex, follow">
	<?php endif; ?>

	<!-- Tailwind & Fonts -->
	<link rel="stylesheet" href="/public_assets/css/tailwind.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" rel="stylesheet">

	<!-- Favicon -->
	<link rel="icon" type="image/x-icon" href="/favicon.ico">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="192x192" href="/favicon-192x192.png">
	<link rel="apple-touch-icon" href="/apple-touch-icon.png">

	<link href="/public_assets/cookie-banner.css" rel="stylesheet">

	<style>
		body {
			font-family: 'Inter', sans-serif;
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

	<!-- Google Analytics & Marketing (bloccato da consenso) -->
	<script type="text/plain" data-consent-category="analytics" src="https://www.googletagmanager.com/gtag/js?id=G-5KTCZYSJ32"></script>
	<script type="text/plain" data-consent-category="analytics">
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());
		gtag('config', 'G-5KTCZYSJ32');
	</script>
	<script type="text/plain" data-consent-category="marketing">
		console.log("Marketing scripts blocked until consent");
	</script>
</head>
<body class="flex flex-col min-h-screen">

<header class="bg-white text-white p-4 shadow-md">
	<div class="container mx-auto flex justify-between items-center">
		<div class="flex items-center">
			<a href="/" class="text-black hover:text-gray-500 transition duration-300 ease-in-out" title="Italian Cosplay">
				<img src="/public_assets/images/logo_italian_cosplay.webp" width="366" height="50footer" alt="<?php echo APP_NAME?> Logo" class="h-8 w-auto inline-block">
			</a>
		</div>

		<nav class="desktop-menu" aria-label="Menu principale desktop">
			<ul class="flex space-x-4">
				<li><a href="/eventi-cosplay" class="text-black hover:text-gray-500 transition">Eventi Cosplay Italia</a></li>
				<li><a href="/blog"  class="text-black hover:text-gray-500 transition">Blog</a></li>
				<li><a href="/segnala-evento-cosplay"  class="text-black hover:text-gray-500 transition">Segnala il tuo evento Cosplay</a></li>

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
					<?php }} ?>
					<li>
						<form action="/logout" method="POST" class="inline">
							<input type="hidden" name="csrf_token" value="<?php echo  $_SESSION['csrf_token'] ?>">
							<button type="submit" class="text-black hover:text-gray-500 transition bg-transparent border-none p-0 cursor-pointer">Logout</button>
						</form>
					</li>
				<?php else: ?>
<!--					<li><a href="/login" class="text-black hover:text-gray-500 transition">Accedi</a></li>-->
<!--					<li><a href="/register" class="text-black hover:text-gray-500 transition">Registrati</a></li>-->
				<?php endif; ?>
			</ul>
		</nav>

		<button id="hamburger-button" class="hamburger-button text-black focus:outline-none md:hidden" aria-label="Apri menu">
			<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
			</svg>
		</button>
	</div>

	<nav id="mobile-menu" class="mobile-menu md:hidden mt-4">
		<ul class="flex flex-col space-y-2">
			<li><a href="/eventi-cosplay"  class="text-black hover:text-gray-500 transition">Eventi Cosplay Italia</a></li>
			<li><a href="/blog"  class="text-black hover:text-gray-500 transition">Blog</a></li>
			<li><a href="/segnala-evento-cosplay"  class="text-black hover:text-gray-500 transition">Segnala il tuo evento Cosplay</a></li>
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
					<?php }} ?>
				<li>
					<form action="/logout" method="POST" class="inline">
						<input type="hidden" name="csrf_token" value="<?php echo  $_SESSION['csrf_token'] ?>">
						<button type="submit" class="text-black hover:text-gray-500 transition bg-transparent border-none p-0 cursor-pointer">Logout</button>
					</form>
				</li>
			<?php else: ?>
<!--				<li><a href="/login" class="text-black hover:text-gray-500 transition">Accedi</a></li>-->
<!--				<li><a href="/register" class="text-black hover:text-gray-500 transition">Registrati</a></li>-->
			<?php endif; ?>
		</ul>
	</nav>
</header>

<main class="flex-grow container mx-auto p-6">
	<?php foreach($flashMessages as $type => $message): ?>
		<div class="flash-message <?php echo htmlspecialchars($type)?>"><?php echo htmlspecialchars($message)?></div>
	<?php endforeach; ?>

	<?php echo $content ?>
</main>

<footer class="bg-gray-800 text-white p-4 text-center mt-8">
	<p>&copy; <?php echo date('Y')?> <?php echo APP_NAME?>. Tutti i diritti riservati.</p>
	<p><a href="/privacy" class="text-white">Privacy</a> | <a href="/cookies" class="text-white">Cookies</a></p>
	<a href="#" id="manage-consent" class="text-white">Modifica preferenze cookie</a>
</footer>

<div id="cookie-banner">
	<div class="cookie-banner-content">
		<h3>Informativa sui Cookie</h3>
		<p>Questo sito utilizza cookie tecnici e, con il tuo consenso, cookie di profilazione e di terze parti. <a href="/cookies">Leggi la policy</a>.</p>
		<div class="cookie-options">
			<h4>Personalizza</h4>
			<label><input type="checkbox" id="consent-necessary" checked disabled> Necessari</label>
			<label><input type="checkbox" id="consent-analytics" class="consent-choice"> Analitici</label>
			<label><input type="checkbox" id="consent-marketing" class="consent-choice"> Marketing</label>
		</div>
		<div class="cookie-actions">
			<button id="btn-accept-selected">Accetta Selezionati</button>
			<button id="btn-accept-all">Accetta Tutti</button>
		</div>
	</div>
</div>

<script src="/public_assets/cookie-consent.js"></script>
<script>
	document.addEventListener('DOMContentLoaded', function(){
		const hamburgerButton = document.getElementById('hamburger-button');
		const mobileMenu = document.getElementById('mobile-menu');
		if(hamburgerButton && mobileMenu){
			hamburgerButton.addEventListener('click', function(){
				mobileMenu.classList.toggle('active');
			});
		}
	});
</script>



</body>
</html>
