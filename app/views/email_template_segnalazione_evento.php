<!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Email ItalianCosplay</title>
	<style>
		/* Reset di base */
		body, table, td, p {
			margin: 0;
			padding: 0;
		}
		body {
			background-color: #f4f4f4;
			font-family: 'Helvetica', Arial, sans-serif;
			color: #333333;
		}
		.email-container {
			max-width: 600px;
			margin: 30px auto;
			background-color: #ffffff;
			border-radius: 8px;
			overflow: hidden;
			box-shadow: 0 2px 6px rgba(0,0,0,0.1);
		}
		.email-header {
			background-color: #f2c512;
			color: #000;
			text-align: center;
			padding: 20px;
		}
		.email-header h1 {
			margin: 0;
			font-size: 24px;
			letter-spacing: 1px;
		}
		.email-body {
			padding: 30px 20px;
			line-height: 1.6;
		}
		.email-body h2 {
			color: #222;
			font-size: 20px;
			margin-bottom: 10px;
		}
		.email-body p {
			margin-bottom: 15px;
		}
		.btn {
			display: inline-block;
			padding: 12px 20px;
			background-color: #f2c512;
			color: #000;
			text-decoration: none;
			border-radius: 6px;
			font-weight: bold;
			transition: background 0.3s;
		}
		.btn:hover {
			background-color: #ffd84a;
		}
		.email-footer {
			background-color: #fafafa;
			color: #888;
			text-align: center;
			font-size: 13px;
			padding: 20px;
		}
		.email-footer a {
			color: #f2c512;
			text-decoration: none;
		}
	</style>
</head>
<body>
<div class="email-container">
	<div class="email-header">
		<h1>ItalianCosplay</h1>
	</div>

	<div class="email-body">
		<h2>Ciao {{nome}},</h2>
		<p>Benvenuto nella community di <strong>ItalianCosplay</strong>! 🎉</p>
		<p>Siamo felici che tu abbia deciso di unirti a noi. Da oggi potrai scoprire tutti gli eventi cosplay in Italia e creare la tua agenda personale.</p>

		<p>Per completare la registrazione, conferma la tua email cliccando sul pulsante qui sotto:</p>

		<p style="text-align:center;">
			<a href="{{link_conferma}}" class="btn">Conferma la tua email</a>
		</p>

		<p>Se non hai richiesto la registrazione, puoi ignorare questa email.</p>

		<p>— Il team di <strong>ItalianCosplay</strong></p>
	</div>

	<div class="email-footer">
		<p>© 2025 ItalianCosplay • Tutti i diritti riservati</p>
		<p><a href="https://italiancosplay.com">italiancosplay.com</a></p>
	</div>
</div>
</body>
</html>
