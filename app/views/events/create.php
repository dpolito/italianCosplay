<h1>Segnala un Nuovo Evento Cosplay</h1>

<form action="/events" method="POST" enctype="multipart/form-data">
	<div class="form-group">
		<label for="titolo">Titolo Evento:</label>
		<input type="text" id="titolo" name="titolo" class="form-control" required>
	</div>

	<div class="form-group">
		<label for="descrizione">Descrizione:</label>
		<textarea id="descrizione" name="descrizione" class="form-control" rows="5"></textarea>
	</div>

	<div class="form-group">
		<label for="data_inizio">Data Inizio:</label>
		<input type="date" id="data_inizio" name="data_inizio" class="form-control" required>
	</div>

	<div class="form-group">
		<label for="data_fine">Data Fine (opzionale):</label>
		<input type="date" id="data_fine" name="data_fine" class="form-control">
	</div>

	<div class="form-group">
		<label for="luogo">Luogo:</label>
		<input type="text" id="luogo" name="luogo" class="form-control" required>
	</div>

	<div class="form-group">
		<label for="regione_id">ID Regione:</label>
		<input type="number" id="regione_id" name="regione_id" class="form-control">
	</div>
	<div class="form-group">
		<label for="provincia_id">ID Provincia:</label>
		<input type="number" id="provincia_id" name="provincia_id" class="form-control">
	</div>
	<div class="form-group">
		<label for="comune_id">ID Comune:</label>
		<input type="number" id="comune_id" name="comune_id" class="form-control">
	</div>
	<div class="form-group">
		<label for="latitudine">Latitudine:</label>
		<input type="text" id="latitudine" name="latitudine" class="form-control">
	</div>
	<div class="form-group">
		<label for="longitudine">Longitudine:</label>
		<input type="text" id="longitudine" name="longitudine" class="form-control">
	</div>
	<div class="form-group">
		<label for="sito_web">Sito Web:</label>
		<input type="url" id="sito_web" name="sito_web" class="form-control">
	</div>
	<div class="form-group">
		<label for="social_facebook">Facebook:</label>
		<input type="url" id="social_facebook" name="social_facebook" class="form-control">
	</div>
	<div class="form-group">
		<label for="social_instagram">Instagram:</label>
		<input type="url" id="social_instagram" name="social_instagram" class="form-control">
	</div>
	<div class="form-group">
		<label for="social_tiktok">TikTok:</label>
		<input type="url" id="social_tiktok" name="social_tiktok" class="form-control">
	</div>
	<div class="form-group">
		<label for="social_youtube">YouTube:</label>
		<input type="url" id="social_youtube" name="social_youtube" class="form-control">
	</div>
	<div class="form-group">
		<label for="social_twitter">Twitter:</label>
		<input type="url" id="social_twitter" name="social_twitter" class="form-control">
	</div>
	<div class="form-group">
		<label for="tipo_evento_id">ID Tipo Evento:</label>
		<input type="number" id="tipo_evento_id" name="tipo_evento_id" class="form-control">
	</div>

	<div class="form-group">
		<label for="immagine">Immagine Evento:</label>
		<input type="file" id="immagine" name="immagine" class="form-control-file" accept="image/*">
	</div>

	<button type="submit" class="btn success">Segnala Evento</button>
</form>

<style>
	/* Stili CSS base per i form */
	.form-group {
		margin-bottom: 15px;
	}
	.form-group label {
		display: block;
		margin-bottom: 5px;
		font-weight: bold;
	}
	.form-control {
		width: 100%;
		padding: 8px;
		border: 1px solid #ccc;
		border-radius: 4px;
		box-sizing: border-box; /* Assicura che padding non allarghi l'input */
	}
	textarea.form-control {
		resize: vertical; /* Permette solo il ridimensionamento verticale */
	}
	.btn {
		padding: 10px 15px;
		border: none;
		border-radius: 4px;
		cursor: pointer;
		text-decoration: none;
		display: inline-block;
		font-size: 16px;
		margin-right: 5px;
	}
	.btn.success {
		background-color: #28a745;
		color: white;
	}
	.btn.primary {
		background-color: #007bff;
		color: white;
	}
	.btn.danger {
		background-color: #dc3545;
		color: white;
	}
</style>
