<h1>Accedi</h1>

<?php
// Questo blocco PHP controlla se c'è un messaggio di errore passato alla vista
// e lo visualizza.
if (isset($data['error'])): ?>
	<p style="color: red; margin-bottom: 15px; padding: 10px; border: 1px solid red; background-color: #ffe6e6; border-radius: 5px;">
		<?php echo htmlspecialchars($data['error']); ?>
	</p>
<?php endif; ?>

<form action="/login" method="POST">
	<label for="username_email">Username o Email:</label><br>
	<input type="text" id="username_email" name="username_email" value="<?php echo htmlspecialchars($data['old_identifier'] ?? ''); ?>" required><br><br>

	<label for="password">Password:</label><br>
	<input type="password" id="password" name="password" required><br><br>

	<button type="submit">Accedi</button>
</form>
