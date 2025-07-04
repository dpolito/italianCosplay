<!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= APP_NAME ?></title>
	<link rel="stylesheet" href="/public_assets/css/style.css">
</head>
<body>
<header>
	<nav>
		<a href="/">Home</a>
		<a href="/events">Eventi</a>
		<a href="/events/create">Segnala Evento</a>
		<?php if (Session::get('user_role') === 'admin'): ?>
			<a href="/admin/events/pending">Eventi in Attesa</a>
		<?php endif; ?>
		<?php if (Session::has('user_id')): ?>
			<form action="/logout" method="POST" style="display:inline;">
				<button type="submit">Logout</button>
			</form>
		<?php else: ?>
			<a href="/login">Login</a>
			<a href="/register">Registrati</a>
		<?php endif; ?>
	</nav>
</header>

<main>
	<?php
	// Gestione dei messaggi flash
	if (Session::hasFlash('success')) {
		echo '<div class="alert success">' . htmlspecialchars(Session::getFlash('success')) . '</div>';
	}
	if (Session::hasFlash('error')) {
		echo '<div class="alert error">' . htmlspecialchars(Session::getFlash('error')) . '</div>';
	}
	if (Session::hasFlash('info')) {
		echo '<div class="alert info">' . htmlspecialchars(Session::getFlash('info')) . '</div>';
	}
	?>
	<?= $content ?? '' ?>
</main>

<footer>
	<p>&copy; <?= date('Y') ?> Italian Cosplay. Tutti i diritti riservati.</p>
</footer>

<script src="/public_assets/js/main.js"></script>
</body>
</html>
