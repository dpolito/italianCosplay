<?php use App\Core\Session; $master = $master ?? []; $events = $events ?? []; ?>
<div class="mx-auto max-w-6xl space-y-6 p-6">
	<a href="/dashboard/organizations/<?php echo (int) $organizationId; ?>" class="font-semibold text-green-800">← Torna all'organizzazione</a>
	<?php if ($message = Session::getFlash('success')): ?><div class="rounded-lg bg-green-100 p-4 text-green-800"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
	<?php if ($message = Session::getFlash('error')): ?><div class="rounded-lg bg-red-100 p-4 text-red-800"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
	<div class="rounded-2xl bg-white p-6 shadow">
		<div class="flex flex-wrap items-start justify-between gap-4">
			<div><p class="text-sm font-semibold uppercase tracking-wide text-green-700">Evento master</p><h1 class="mt-1 text-3xl font-bold"><?php echo htmlspecialchars($master['nome'] ?? ''); ?></h1><p class="mt-1 text-slate-500">Organizzazione: <?php echo htmlspecialchars($master['organization_name'] ?? ''); ?></p></div>
			<?php if ($canManage): ?><div class="flex flex-wrap gap-2"><a href="/dashboard/organizations/<?php echo (int) $organizationId; ?>/masters/<?php echo (int) $master['id']; ?>/edit" class="rounded-lg bg-green-700 px-4 py-2 font-semibold text-white">Modifica master</a><?php if (($master['status'] ?? '') !== 'pending_review'): ?><form data-withdraw-form method="post" action="/dashboard/organizations/<?php echo (int) $organizationId; ?>/masters/<?php echo (int) $master['id']; ?>/withdraw"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>"><button class="rounded-lg border border-amber-700 px-4 py-2 font-semibold text-amber-950">Ritira master</button></form><?php endif; ?></div><?php endif; ?>
		</div>
		<div class="mt-6 prose max-w-none"><?php echo $master['descrizione'] ?? '<p>Nessuna descrizione.</p>'; ?></div>
	</div>
	<div class="rounded-2xl bg-white p-6 shadow">
		<div class="mb-4 flex flex-wrap items-center justify-between gap-3"><div class="flex items-center gap-3"><h2 class="text-xl font-bold">Edizioni</h2><span class="rounded-full bg-slate-100 px-3 py-1 text-sm"><?php echo count($events); ?></span></div><?php if ($canEdit): ?><a href="/dashboard/organizations/<?php echo (int) $organizationId; ?>/masters/<?php echo (int) $master['id']; ?>/events/create" class="rounded-lg bg-green-700 px-4 py-2 font-semibold text-white">Crea nuova edizione</a><?php endif; ?></div>
		<?php if (!$events): ?><p class="text-slate-500">Non ci sono ancora edizioni collegate a questo master.</p><?php else: ?>
			<div class="space-y-3"><?php foreach ($events as $event): ?><div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-4"><div><h3 class="font-bold"><?php echo htmlspecialchars($event['titolo'] ?? ''); ?></h3><p class="text-sm text-slate-500"><?php echo htmlspecialchars((string) ($event['year'] ?? '')); ?> · <?php echo htmlspecialchars($event['luogo'] ?? ''); ?></p></div><a href="/dashboard/organizations/<?php echo (int) $organizationId; ?>/events/<?php echo (int) $event['id']; ?>/edit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold">Modifica edizione</a></div><?php endforeach; ?></div>
		<?php endif; ?>
	</div>
</div>
<script>
document.querySelectorAll('[data-withdraw-form]').forEach((form) => {
	form.addEventListener('submit', async (event) => {
		event.preventDefault();
		if (!window.confirm('Ritirare questo master dalla pubblicazione?')) return;
		const button = form.querySelector('button');
		if (button) button.disabled = true;
		try {
			const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
			const result = await response.json();
			if (!response.ok || !result.success) throw new Error(result.message || 'Operazione non riuscita.');
			form.replaceWith(document.createTextNode(result.message));
		} catch (error) {
			window.alert(error.message || 'Operazione non riuscita.');
			if (button) button.disabled = false;
		}
	});
});
</script>
