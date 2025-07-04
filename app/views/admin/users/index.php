<h1>Gestione Utenti</h1>

<?php
// Visualizza i messaggi flash
if (isset($_SESSION['flash_messages'])) {
	foreach ($_SESSION['flash_messages'] as $type => $message) {
		echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
	}
	unset($_SESSION['flash_messages']); // Pulisci i messaggi flash dopo averli visualizzati
}
?>

<p><a href="/admin/users/create">Crea Nuovo Utente</a></p>

<?php if (!empty($data['users'])): ?>
	<table border="1" cellpadding="5" cellspacing="0">
		<thead>
		<tr>
			<th>ID</th>
			<th>Username</th>
			<th>Email</th>
			<th>Ruolo</th>
			<th>Creato il</th>
			<th>Aggiornato il</th>
			<th>Azioni</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($data['users'] as $user): ?>
			<tr>
				<td><?php echo htmlspecialchars($user['id']); ?></td>
				<td><?php echo htmlspecialchars($user['username']); ?></td>
				<td><?php echo htmlspecialchars($user['email']); ?></td>
				<td><?php echo htmlspecialchars($user['role']); ?></td>
				<td><?php echo htmlspecialchars($user['created_at']); ?></td>
				<td><?php echo htmlspecialchars($user['updated_at']); ?></td>
				<td>
					<a href="/admin/users/edit/<?php echo htmlspecialchars($user['id']); ?>">Modifica</a> |
					<form action="/admin/users/delete/<?php echo htmlspecialchars($user['id']); ?>" method="POST" style="display:inline;" onsubmit="return confirm('Sei sicuro di voler eliminare questo utente?');">
						<button type="submit" style="color: red; background: none; border: none; cursor: pointer; padding: 0;">Elimina</button>
					</form>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
<?php else: ?>
	<p>Nessun utente trovato.</p>
<?php endif; ?>

<style>
	table {
		width: 100%;
		border-collapse: collapse;
		margin-top: 20px;
	}
	th, td {
		padding: 8px;
		text-align: left;
		border-bottom: 1px solid #ddd;
	}
	th {
		background-color: #f2f2f2;
	}
	tr:hover {
		background-color: #f5f5f5;
	}
	.flash-message {
		padding: 10px;
		margin-bottom: 15px;
		border-radius: 5px;
	}
	.flash-message.success {
		background-color: #d4edda;
		color: #155724;
		border: 1px solid #c3e6cb;
	}
	.flash-message.error {
		background-color: #f8d7da;
		color: #721c24;
		border: 1px solid #f5c6cb;
	}
	.flash-message.info {
		background-color: #d1ecf1;
		color: #0c5460;
		border: 1px solid #bee5eb;
	}
</style>
