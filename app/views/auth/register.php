<h1>Registrazione</h1>

<form action="/register" method="POST">
	<div class="form-group">
		<label for="new_username">Username:</label>
		<input type="text" id="new_username" name="new_username" class="form-control" required>
	</div>
	<div class="form-group">
		<label for="new_email">Email:</label>
		<input type="email" id="new_email" name="new_email" class="form-control" required>
	</div>
	<div class="form-group">
		<label for="new_password">Password:</label>
		<input type="password" id="new_password" name="new_password" class="form-control" required>
	</div>
	<div class="form-group">
		<label for="confirm_password">Conferma Password:</label>
		<input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
	</div>
	<button type="submit" class="btn primary">Registrati</button>
</form>

<p>Hai già un account? <a href="/login">Accedi qui</a></p>
