<?php
?>
<div class="container mx-auto p-6">
	<div class="flex items-center justify-between mb-6">
		<h1 class="text-3xl font-semibold text-gray-800">Eventi Master</h1>
		<a href="/admin/events-master/create" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700">
			Nuovo evento master
		</a>
	</div>

	<div class="bg-white rounded-lg shadow overflow-hidden">
		<table class="min-w-full divide-y divide-gray-200">
			<thead class="bg-gray-50">
				<tr>
					<th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">ID</th>
					<th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Nome</th>
					<th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Slug</th>
					<th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Azioni</th>
				</tr>
			</thead>
			<tbody class="divide-y divide-gray-100">
			<?php foreach (($data['eventMasters'] ?? []) as $eventMaster): ?>
				<tr>
					<td class="px-4 py-3"><?php echo htmlspecialchars((string) $eventMaster['id']); ?></td>
					<td class="px-4 py-3"><?php echo htmlspecialchars($eventMaster['titolo'] ?? $eventMaster['name'] ?? ''); ?></td>
					<td class="px-4 py-3"><?php echo htmlspecialchars($eventMaster['slug'] ?? ''); ?></td>
					<td class="px-4 py-3 text-right">
						<a href="/admin/events-master/edit/<?php echo htmlspecialchars((string) $eventMaster['id']); ?>" class="text-indigo-600 hover:text-indigo-800 mr-3">Modifica</a>
						<form action="/admin/events-master/delete/<?php echo htmlspecialchars((string) $eventMaster['id']); ?>" method="POST" class="inline-block" onsubmit="return confirm('Eliminare questo evento master?');">
							<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">
							<button type="submit" class="text-red-600 hover:text-red-800">Elimina</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
