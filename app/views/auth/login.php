<h1>Login</h1>

<form action="/login" method="POST">
	<div class="form-group">
		<label for="username">Username:</label>
		<input type="text" id="username" name="username" class="form-control" required>
	</div>
	<div class="form-group">
		<label for="password">Password:</label>
		<input type="password" id="password" name="password" class="form-control" required>
	</div>
	<button type="submit" class="btn primary">Accedi</button>
</form>

<p>Non hai un account? <a href="/register">Registrati qui</a></p>
