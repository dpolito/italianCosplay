<?php
// app/views/404.php
// Questo file non ha bisogno di un layout completo, può essere una pagina standalone.
// Tuttavia, se vuoi che abbia l'aspetto del tuo sito, potresti includere un header/footer
// o un layout minimale. Per semplicità, qui è una pagina HTML base.
?>
<!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>404 - Pagina Non Trovata</title>
	<!-- Includi Tailwind CSS se vuoi stilizzare questa pagina -->
	<script src="https://cdn.tailwindcss.com"></script>
	<style>
		body {
			font-family: 'Inter', sans-serif;
			background-color: #f3f4f6;
			color: #333;
			display: flex;
			justify-content: center;
			align-items: center;
			min-height: 100vh;
			text-align: center;
		}
		.container-404 {
			background-color: #fff;
			padding: 3rem;
			border-radius: 0.75rem;
			box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
		}
		h1 {
			font-size: 4rem;
			font-weight: bold;
			color: #ef4444; /* Rosso */
			margin-bottom: 1rem;
		}
		h2 {
			font-size: 2.25rem;
			font-weight: 600;
			color: #1f2937; /* Grigio scuro */
			margin-bottom: 1.5rem;
		}
		p {
			font-size: 1.125rem;
			color: #4b5563; /* Grigio medio */
			margin-bottom: 2rem;
		}
		a {
			display: inline-block;
			background-color: #22c55e; /* Verde */
			color: #fff;
			padding: 0.75rem 1.5rem;
			border-radius: 0.5rem;
			text-decoration: none;
			font-weight: 600;
			transition: background-color 0.2s ease-in-out;
		}
		a:hover {
			background-color: #16a34a; /* Verde più scuro */
		}
	</style>
</head>
<body>
<div class="container-404">
	<h1>404</h1>
	<h2>Pagina Non Trovata</h2>
	<p>Siamo spiacenti, la pagina che stai cercando non esiste.</p>
	<a href="/">Torna alla Homepage</a>
</div>
</body>
</html>
