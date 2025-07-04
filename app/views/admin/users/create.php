<?php
// Questo file è un frammento di HTML e deve essere incluso in un layout admin.
// Non contiene i tag <html>, <head>, <body> completi.
?>

<div class="container mx-auto p-6">
	<div class="mb-6">
		<a href="/admin/users" title="" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
			</svg>
			Torna agli Utenti
		</a>
	</div>
	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Crea Nuovo Utente</h1>

	<?php
	// Visualizza i messaggi flash
	if (isset($_SESSION['flash_messages'])) {
		foreach ($_SESSION['flash_messages'] as $type => $message) {
			echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
		}
		unset($_SESSION['flash_messages']); // Pulisci i messaggi flash dopo averli visualizzati
	}
	?>

	<div class="bg-white rounded-lg shadow-lg p-8">
		<form action="/admin/users/store" method="POST">
			<div class="mb-4">
				<label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username:</label>
				<input type="text" id="username" name="username" value="<?php echo htmlspecialchars($data['username'] ?? ''); ?>" required class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
				<input type="email" id="email" name="email" value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>" required class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-4">
				<label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password:</label>
				<input type="password" id="password" name="password" required class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
			</div>

			<div class="mb-6">
				<label for="role" class="block text-gray-700 text-sm font-bold mb-2">Ruolo:</label>
				<select id="role" name="role" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500">
					<option value="user" <?php echo (isset($data['role']) && $data['role'] == 'user') ? 'selected' : ''; ?>>Utente</option>
					<option value="admin" <?php echo (isset($data['role']) && $data['role'] == 'admin') ? 'selected' : ''; ?>>Amministratore</option>
				</select>
			</div>

			<div class="flex items-center justify-between">
				<button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md focus:outline-none focus:shadow-outline transition duration-300 ease-in-out">
					Crea Utente
				</button>
				<a title="" href="/admin/users" class="inline-block align-baseline font-semibold text-blue-600 hover:text-blue-800">
					Annulla
				</a>
			</div>
		</form>
	</div>
</div>
