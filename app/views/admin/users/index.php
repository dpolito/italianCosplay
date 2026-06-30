<?php
// Questo file è un frammento di HTML e deve essere incluso in un layout admin.
// Non contiene i tag <html>, <head>, <body> completi.
?>

<div class="container mx-auto p-6">
	<div class="mb-6 flex justify-between items-center">
		<a href="/admin/dashboard" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
			</svg>
			Torna alla Dashboard
		</a>
		<a href="/admin/users/create" class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
			</svg>
			Crea Nuovo Utente
		</a>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Gestione Utenti</h1>

	<?php
	// Visualizza i messaggi flash
	if (isset($_SESSION['flash_messages'])) {
		foreach ($_SESSION['flash_messages'] as $type => $message) {
			echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
		}
		unset($_SESSION['flash_messages']);
	}
	?>

	<?php if (!empty($data['users'])): ?>
		<div class="bg-white rounded-lg shadow-lg overflow-hidden p-4">
			<table class="min-w-full leading-normal">
				<thead>
				<tr>
					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
						ID
					</th>
					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
						Username
					</th>
					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
						Email
					</th>
					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
						Ruolo
					</th>
					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
						Azioni
					</th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ($data['users'] as $user): ?>
					<tr>
						<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
							<?php echo htmlspecialchars($user['id']); ?>
						</td>
						<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
							<?php echo htmlspecialchars($user['username']); ?>
						</td>
						<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
							<?php echo htmlspecialchars($user['email']); ?>
						</td>
						<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <span class="relative inline-block px-3 py-1 font-semibold leading-tight">
                                    <span aria-hidden="true" class="absolute inset-0 opacity-50 rounded-full"></span>
                                    <span class="relative text-xs ">
	                                    <?php echo $data['roles'][$user['role_id']-1]['name']; ?>

                                    </span>
                                </span>
						</td>
						<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm whitespace-nowrap">
							<a href="/admin/users/edit/<?php echo htmlspecialchars($user['id']); ?>" class="text-green-600 hover:text-green-900 mr-3">Modifica</a>
							<form action="/admin/users/delete/<?php echo htmlspecialchars($user['id']); ?>" method="POST" class="inline-block" onsubmit="return confirm('Sei sicuro di voler eliminare questo utente?');">
								<!-- CSRF Token per il form di eliminazione -->
								<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">
								<button type="submit" class="text-red-600 hover:text-red-900 focus:outline-none focus:underline">Elimina</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php else: ?>
		<p class="text-gray-600">Nessun utente trovato.</p>
	<?php endif; ?>
</div>
