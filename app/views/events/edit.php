<h1>Modifica Evento: <?= htmlspecialchars($event['titolo']) ?></h1>

<form action="/events/<?= htmlspecialchars($event['id']) ?>/update" method="POST" enctype="multipart/form-data">
	<div class="form-group">
		<label for="titolo">Titolo Evento:</label>
		<input type="text" id="titolo" name="titolo" class="form-control" value="<?= htmlspecialchars($event['titolo']) ?>" required>
	</div>

	<div class="form-group">
		<label for="descrizione">Descrizione:</label>
		<textarea id="descrizione" name="descrizione" class="form-control" rows="5"><?= htmlspecialchars($event['descrizione']) ?></textarea>
	</div>

	<div class="form-group">
		<label for="data_inizio">Data Inizio:</label>
		<input type="date" id="data_inizio" name="data_inizio" class="form-control" value="<?= htmlspecialchars($event['data_inizio']) ?>" required>
	</div>

	<div class="form-group">
		<label for="data_fine">Data Fine (opzionale):</label>
		<input type="date" id="data_fine" name="data_fine" class="form-control" value="<?= htmlspecialchars($event['data_fine']) ?>">
	</div>

	<div class="form-group">
		<label for="luogo">Luogo:</label>
		<input type="text" id="luogo" name="luogo" class="form-control" value="<?= htmlspecialchars($event['luogo']) ?>" required>
	</div>

	<div class="form-group">
		<label for="regione_id">ID Regione:</label>
		<input type="number" id="regione_id" name="regione_id" class="form-control" value="<?= htmlspecialchars($event['regione_id']) ?>">
	</div>
	<div class="form-group">
		<label for="provincia_id">ID Provincia:</label>
		<input type="number" id="provincia_id" name="provincia_id" class="form-control" value="<?= htmlspecialchars($event['provincia_id']) ?>">
	</div>
	<div class="form-group">
		<label for="comune_id">ID Comune:</label>
		<input type="number" id="comune_id" name="comune_id" class="form-control" value="<?= htmlspecialchars($event['comune_id']) ?>">
	</div>
	<div class="form-group">
		<label for="latitudine">Latitudine:</label>
		<input type="text" id="latitudine" name="latitudine" class="form-control" value="<?= htmlspecialchars($event['latitudine']) ?>">
	</div>
	<div class="form-group">
		<label for="longitudine">Longitudine:</label>
		<input type="text" id="longitudine" name="longitudine" class="form-control" value="<?= htmlspecialchars($event['longitudine']) ?>">
	</div>
	<div class="form-group">
		<label for="sito_web">Sito Web:</label>
		<input type="url" id="sito_web" name="sito_web" class="form-control" value="<?= htmlspecialchars($event['sito_web']) ?>">
	</div>
	<div class="form-group">
		<label for="social_facebook">Facebook:</label>
		<input type="url" id="social_facebook" name="social_facebook" class="form-control" value="<?= htmlspecialchars($event['social_facebook']) ?>">
	</div>
	<div class="form-group">
		<label for="social_instagram">Instagram:</label>
		<input type="url" id="social_instagram" name="social_instagram" class="form-control" value="<?= htmlspecialchars($event['social_instagram']) ?>">
	</div>
	<div class="form-group">
		<label for="social_tiktok">TikTok:</label>
		<input type="url" id="social_tiktok" name="social_tiktok" class="form-control" value="<?= htmlspecialchars($event['social_tiktok']) ?>">
	</div>
	<div class="form-group">
		<label for="social_youtube">YouTube:</label>
		<input type="url" id="social_youtube" name="social_youtube" class="form-control" value="<?= htmlspecialchars($event['social_youtube']) ?>">
	</div>
	<div class="form-group">
		<label for="social_twitter">Twitter:</label>
		<input type="url" id="social_twitter" name="social_twitter" class="form-control" value="<?= htmlspecialchars($event['social_twitter']) ?>">
	</div>
	<div class="form-group">
		<label for="tipo_evento_id">ID Tipo Evento:</label>
		<input type="number" id="tipo_evento_id" name="tipo_evento_id" class="form-control" value="<?= htmlspecialchars($event['tipo_evento_id']) ?>">
	</div>

	<div class="form-group">
		<label for="immagine">Immagine Evento:</label>
		<?php if ($event['immagine']): ?>
			<p>Immagine attuale: <img src="/public_assets/images/<?= htmlspecialchars($event['immagine']) ?>" alt="Immagine attuale" style="max-width: 100px; height: auto; display: block; margin-top: 5px;"></p>
			<input type="checkbox" id="remove_image" name="remove_image" value="1"> <label for="remove_image">Rimuovi immagine attuale</label><br>
		<?php endif; ?>
		<input type="file" id="immagine" name="immagine" class="form-control-file" accept="image/*">
	</div>

	<div class="form-group">
		<label for="approvato">Approvato (Solo Admin):</label>
		<input type="checkbox" id="approvato" name="approvato" value="1" <?= $event['approvato'] ? 'checked' : '' ?> <?= (Session::get('user_role') !== 'admin') ? 'disabled' : '' ?>>
	</div>

	<button type="submit" class="btn success">Aggiorna Evento</button>
	<a href="/events/<?= htmlspecialchars($event['id']) ?>" class="btn">Annulla</a>
</form>

<?php /* Gli stili CSS sono definiti in create.php o nel CSS globale */ ?>
