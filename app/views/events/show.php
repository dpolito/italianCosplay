<h1><?= htmlspecialchars($event['titolo']) ?></h1>

<p><strong>Data:</strong> dal <?= htmlspecialchars(date('d/m/Y', strtotime($event['data_inizio']))) ?>
	<?php if ($event['data_fine']): ?>
		al <?= htmlspecialchars(date('d/m/Y', strtotime($event['data_fine']))) ?>
	<?php endif; ?>
</p>
<p><strong>Luogo:</strong> <?= htmlspecialchars($event['luogo']) ?></p>

<?php if ($event['immagine']): ?>
	<img src="/public_assets/images/<?= htmlspecialchars($event['immagine']) ?>" alt="<?= htmlspecialchars($event['titolo']) ?>" style="max-width: 400px; height: auto; margin-top: 15px;">
<?php endif; ?>

<p><strong>Descrizione:</strong></p>
<p><?= nl2br(htmlspecialchars($event['descrizione'])) ?></p>

<?php if ($event['sito_web']): ?>
	<p>Sito Web: <a href="<?= htmlspecialchars($event['sito_web']) ?>" target="_blank"><?= htmlspecialchars($event['sito_web']) ?></a></p>
<?php endif; ?>

<p><a href="/events">Torna alla lista eventi</a></p>

<?php if (Session::get('user_role') === 'admin'): // Solo admin può vedere questi link ?>
	<hr>
	<h3>Azioni Amministrative:</h3>
	<a href="/events/<?= htmlspecialchars($event['id']) ?>/edit" class="btn">Modifica Evento</a>
	<form action="/events/<?= htmlspecialchars($event['id']) ?>/delete" method="POST" style="display:inline-block;">
		<button type="submit" class="btn danger" onclick="return confirm('Sei sicuro di voler eliminare questo evento?');">Elimina Evento</button>
	</form>
	<?php if ($event['approvato'] == 0): ?>
		<form action="/events/<?= htmlspecialchars($event['id']) ?>/approve" method="POST" style="display:inline-block;">
			<button type="submit" class="btn success">Approva Evento</button>
		</form>
	<?php endif; ?>
<?php endif; ?>
