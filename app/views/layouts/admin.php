<!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= APP_NAME ?> - Dashboard Admin</title>

	<link rel="stylesheet" href="/public_assets/css/tailwind.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

	<style>
		body {
			font-family: 'Inter', sans-serif;
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
	</style>
</head>

<body class="flex flex-col min-h-screen">

<header class="bg-green-700 text-white p-4">
	<div class="container mx-auto flex justify-between items-center">
		<h1 class="text-xl font-bold">Dashboard Admin</h1>

		<form action="/logout" method="POST">
			<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
			<button class="bg-red-600 px-4 py-2 rounded">
				Logout
			</button>
		</form>
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
	<main class="flex-grow p-6">
		<?= $content_for_layout ?? '' ?>
	</main>

</div>

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

</body>
</html>
