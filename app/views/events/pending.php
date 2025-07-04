<h1>Eventi in Attesa di Approvazione</h1>

<?php if (!empty($events)): ?>
	<table border="1" style="width: 100%; border-collapse: collapse;">
		<thead>
		<tr>
			<th>ID</th>
			<th>Titolo</th>
			<th>Data Inizio</th>
			<th>Luogo</th>
			<th>Creato il</th>
			<th>Azioni</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($events as $event): ?>
			<tr>
				<td><?= htmlspecialchars($event['id']) ?></td>
				<td><?= htmlspecialchars($event['titolo']) ?></td>
				<td><?= htmlspecialchars(date('d/m/Y', strtotime($event['data_inizio']))) ?></td>
				<td><?= htmlspecialchars($event['luogo']) ?></td>
				<td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($event['created_at']))) ?></td>
				<td>
					<a href="/events/<?= htmlspecialchars($event['id']) ?>" class="btn">Dettagli</a>
					<a href="/events/<?= htmlspecialchars($event['id']) ?>/edit" class="btn">Modifica</a>
					<form action="/events/<?= htmlspecialchars($event['id']) ?>/approve" method="POST" style="display:inline-block;">
						<button type="submit" class="btn success">Approva</button>
					</form>
					<form action="/events/<?= htmlspecialchars($event['id']) ?>/delete" method="POST" style="display:inline-block;">
						<button type="submit" class="btn danger" onclick="return confirm('Sei sicuro di voler eliminare questo evento?');">Elimina</button>
					</form>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
<?php else: ?>
	<p>Nessun evento in attesa di approvazione.</p>
<?php endif; ?>

<?php /* Gli stili CSS sono definiti in create.php o nel CSS globale */ ?>
