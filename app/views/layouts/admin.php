<!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo APP_NAME; ?> - Dashboard Admin</title>
	<!-- Inclusione di Tailwind CSS via CDN -->
	<script src="https://cdn.tailwindcss.com"></script>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<style>
		body {
			font-family: 'Inter', sans-serif;
			background-color: #f3f4f6; /* Sfondo leggermente grigio */
			color: #333;
		}
		/* Stili per i messaggi flash (copiati da admin-dashboard-ui) */
		.flash-message {
			padding: 1rem;
			margin-bottom: 1.5rem;
			border-radius: 0.5rem;
			font-weight: 500;
		}
		.flash-message.success {
			background-color: #d4edda; /* Verde chiaro */
			color: #155724; /* Verde scuro */
			border: 1px solid #c3e6cb;
		}
		.flash-message.error {
			background-color: #f8d7da; /* Rosso chiaro */
			color: #721c24; /* Rosso scuro */
			border: 1px solid #f5c6cb;
		}
		.flash-message.info {
			background-color: #d1ecf1; /* Blu chiaro */
			color: #0c5460; /* Blu scuro */
			border: 1px solid #bee5eb;
		}
		.sidebar {
			width: 250px; /* Larghezza fissa per la sidebar */
			min-width: 250px;
			background-color: #2d3748; /* Grigio scuro per la sidebar */
			color: #cbd5e0; /* Testo chiaro */
			padding: 1rem;
			box-shadow: 2px 0 5px rgba(0,0,0,0.1);
		}
		.sidebar a {
			display: flex;
			align-items: center;
			padding: 0.75rem 1rem;
			margin-bottom: 0.5rem;
			border-radius: 0.5rem;
			transition: background-color 0.2s ease-in-out;
		}
		.sidebar a:hover {
			background-color: #4a5568; /* Grigio più chiaro all'hover */
			color: #fff;
		}
		.sidebar a.active {
			background-color: #4299e1; /* Blu per l'elemento attivo */
			color: #fff;
			font-weight: 600;
		}
		.sidebar svg {
			margin-right: 0.75rem;
		}
	</style>
</head>
<body class="flex flex-col min-h-screen">
<header class="bg-blue-600 text-white p-4 shadow-md">
	<div class="container mx-auto flex justify-between items-center">
		<h1 class="text-2xl font-bold">Dashboard Amministratore</h1>
		<nav>
			<a href="/logout" class="bg-blue-700 hover:bg-blue-800 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out">Logout</a>
		</nav>
	</div>
</header>

<div class="flex flex-grow">
	<!-- Sidebar di Navigazione -->
	<aside class="sidebar">
		<nav>
			<ul>
				<li>
					<a href="/admin/dashboard" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/dashboard') !== false) ? 'active' : ''; ?>">
						<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
							<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m0 0v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
						</svg>
						Dashboard
					</a>
				</li>
				<li>
					<a href="/admin/users" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/users') !== false) ? 'active' : ''; ?>">
						<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M12 20.5V10m0 0a4 4 0 01-4-4v-1a4 4 0 014-4h0a4 4 0 014 4v1a4 4 0 01-4 4zM12 14c-4.418 0-8 2.239-8 5v1h16v-1c0-2.761-3.582-5-8-5z" />
						</svg>
						Utenti
					</a>
				</li>
				<li>
					<a href="/admin/events/pending" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/events/pending') !== false) ? 'active' : ''; ?>">
						<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
						</svg>
						Eventi in Attesa
					</a>
				</li>
				<li>
					<a href="/admin/events/all" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/events/all') !== false) ? 'active' : ''; ?>">
						<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
							<path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
						</svg>
						Tutti gli Eventi
					</a>
				</li>
			</ul>
		</nav>
	</aside>

	<!-- Contenuto Principale -->
	<main class="flex-grow p-6">
		<?php
		if (isset($content_for_layout)) {
			echo $content_for_layout;
		}
		?>
	</main>
</div>

<footer class="bg-gray-800 text-white p-4 text-center mt-8">
	<p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Tutti i diritti riservati.</p>
</footer>
</body>
</html>
