<h1>Eventi Cosplay</h1>

<a href="/events/create" class="btn primary">Segnala un nuovo evento</a>

<div class="events-list">
	<?php if (!empty($events)): ?>
		<?php foreach ($events as $event): ?>
			<div class="event-item">
				<h2><a href="/events/<?= htmlspecialchars($event['id']) ?>"><?= htmlspecialchars($event['titolo']) ?></a></h2>
				<p>Data: dal <?= htmlspecialchars(date('d/m/Y', strtotime($event['data_inizio']))) ?>
					<?php if ($event['data_fine']): ?>
						al <?= htmlspecialchars(date('d/m/Y', strtotime($event['data_fine']))) ?>
					<?php endif; ?>
				</p>
				<p>Luogo: <?= htmlspecialchars($event['luogo']) ?></p>
				<?php if ($event['immagine']): ?>
					<img src="/public_assets/images/<?= htmlspecialchars($event['immagine']) ?>" alt="<?= htmlspecialchars($event['titolo']) ?>" style="max-width: 200px; height: auto;">
				<?php endif; ?>
				<p><?= nl2br(htmlspecialchars(substr($event['descrizione'], 0, 150))) ?>...</p>
				<a href="/events/<?= htmlspecialchars($event['id']) ?>" class="btn">Dettagli</a>
			</div>
		<?php endforeach; ?>
	<?php else: ?>
		<p>Nessun evento approvato disponibile al momento. Sii il primo a <a href="/events/create">segnalarne uno</a>!</p>
	<?php endif; ?>
</div>

<style>
	/* Stili basilari per gli alert */
	.alert {
		padding: 10px;
		margin-bottom: 15px;
		border-radius: 5px;
		border: 1px solid transparent;
	}
	.alert.success {
		background-color: #d4edda;
		color: #155724;
		border-color: #c3e6cb;
	}
	.alert.error {
		background-color: #f8d7da;
		color: #721c24;
		border-color: #f5c6cb;
	}
	.alert.info {
		background-color: #d1ecf1;
		color: #0c5460;
		border-color: #bee5eb;
	}
</style>
