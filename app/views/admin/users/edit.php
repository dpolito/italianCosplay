<h1>Modifica Utente: <?php echo htmlspecialchars($data['user']['username'] ?? ''); ?></h1>

<?php
// Visualizza i messaggi flash o errori passati direttamente
if (isset($_SESSION['flash_messages'])) {
	foreach ($_SESSION['flash_messages'] as $type => $message) {
		echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
	}
	unset($_SESSION['flash_messages']);
} elseif (isset($data['error'])) {
	echo '<div class="flash-message error">' . htmlspecialchars($data['error']) . '</div>';
}
?>

<form action="/admin/users/update/<?php echo htmlspecialchars($data['user']['id'] ?? ''); ?>" method="POST">
	<input type="hidden" name="id" value="<?php echo htmlspecialchars($data['user']['id'] ?? ''); ?>">

	<label for="username">Username:</label><br>
	<input type="text" id="username" name="username" value="<?php echo htmlspecialchars($data['user']['username'] ?? ''); ?>" required><br><br>

	<label for="email">Email:</label><br>
	<input type="email" id="email" name="email" value="<?php echo htmlspecialchars($data['user']['email'] ?? ''); ?>" required><br><br>

	<label for="password">Nuova Password (lascia vuoto per non cambiare):</label><br>
	<input type="password" id="password" name="password"><br><br>

	<label for="role">Ruolo:</label><br>
	<select id="role" name="role">
		<option value="user" <?php echo (isset($data['user']['role']) && $data['user']['role'] == 'user') ? 'selected' : ''; ?>>Utente</option>
		<option value="admin" <?php echo (isset($data['user']['role']) && $data['user']['role'] == 'admin') ? 'selected' : ''; ?>>Amministratore</option>
	</select><br><br>

	<button type="submit">Aggiorna Utente</button>
	<a href="/admin/users">Annulla</a>
</form>

<style>
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
