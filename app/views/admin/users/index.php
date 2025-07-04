<?php
// Questo file è un frammento di HTML e deve essere incluso in un layout admin.
// Non contiene i tag <html>, <head>, <body> completi.
?>

<div class="container mx-auto p-6">
	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Gestione Utenti</h1>

	<?php
	// Visualizza i messaggi flash
	if (isset($_SESSION['flash_messages'])) {
		foreach ($_SESSION['flash_messages'] as $type => $message) {
			echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
		}
		unset($_SESSION['flash_messages']); // Pulisci i messaggi flash dopo averli visualizzati
	}
	?>

	<div class="mb-6">
		<a href="/admin/users/create" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
			</svg>
			Crea Nuovo Utente
		</a>
	</div>

	<?php if (!empty($data['users'])): ?>
		<div class="bg-white rounded-lg shadow-lg overflow-hidden">
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
						Creato il
					</th>
					<th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
						Aggiornato il
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
                                    <span aria-hidden="true" class="absolute inset-0 <?php echo ($user['role'] === 'admin' ? 'bg-red-200' : 'bg-green-200'); ?> opacity-50 rounded-full"></span>
                                    <span class="relative text-xs <?php echo ($user['role'] === 'admin' ? 'text-red-900' : 'text-green-900'); ?>">
                                        <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                    </span>
                                </span>
						</td>
						<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
							<?php echo htmlspecialchars($user['created_at']); ?>
						</td>
						<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
							<?php echo htmlspecialchars($user['updated_at']); ?>
						</td>
						<td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
							<a href="/admin/users/edit/<?php echo htmlspecialchars($user['id']); ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Modifica</a>
							<form action="/admin/users/delete/<?php echo htmlspecialchars($user['id']); ?>" method="POST" class="inline-block" onsubmit="return confirm('Sei sicuro di voler eliminare questo utente?');">
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
