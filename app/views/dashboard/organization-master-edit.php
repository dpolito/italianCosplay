<?php use App\Core\Session; $master = $master ?? []; ?>
<div class="mx-auto max-w-6xl p-6">
	<a href="/dashboard/organizations/<?php echo (int) $organizationId; ?>/masters/<?php echo (int) $master['id']; ?>" class="font-semibold text-green-800">← Torna al master</a>
	<h1 class="mt-5 text-3xl font-bold">Modifica evento master</h1>
	<?php if ($message = Session::getFlash('error')): ?><div class="mt-4 rounded-lg bg-red-100 p-4 text-red-800"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
	<div id="dashboard-master-feedback" class="mt-4 hidden rounded-lg p-4 text-sm"></div>
	<form class="mt-6 space-y-5 rounded-2xl bg-white p-6 shadow" action="/dashboard/organizations/<?php echo (int) $organizationId; ?>/masters/<?php echo (int) $master['id']; ?>/update" method="post" data-ajax-submit="true">
		<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
		<div class="grid gap-5 md:grid-cols-2">
			<label class="font-semibold">Nome<input class="mt-1 w-full rounded-lg border p-3" required name="nome" value="<?php echo htmlspecialchars($master['nome'] ?? ''); ?>"></label>
			<label class="font-semibold md:col-span-2">Sito web<input type="url" class="mt-1 w-full rounded-lg border p-3" name="sito_web" value="<?php echo htmlspecialchars($master['sito_web'] ?? ''); ?>"></label>
		</div>
		<div>
			<label for="master-description-editor" class="font-semibold">Descrizione</label>
			<div id="master-description-editor" class="mt-1 min-h-48 rounded-lg border bg-white"></div>
		</div>
		<div class="grid gap-5 md:grid-cols-2"><?php foreach (['social_facebook'=>'Facebook','social_twitter'=>'Twitter/X','social_instagram'=>'Instagram','social_tiktok'=>'TikTok','social_youtube'=>'YouTube'] as $field => $label): ?><label class="font-semibold"><?php echo $label; ?><input type="url" class="mt-1 w-full rounded-lg border p-3" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($master[$field] ?? ''); ?>"></label><?php endforeach; ?></div>
		<div class="flex justify-end"><button class="rounded-lg bg-green-700 px-5 py-3 font-bold text-white" type="submit">Salva modifiche</button></div>
	</form>
</div>
<script src="/public_assets/js/wysiwyg-editor.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
	WysiwygEditor.init('#master-description-editor', {
		content: <?php echo json_encode($master['descrizione'] ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
		name: 'descrizione',
		placeholder: 'Scrivi la descrizione del master...',
	});
});
</script>
<script>
document.querySelector('form[data-ajax-submit]').addEventListener('submit', async function (event) {
	event.preventDefault();
	const form = event.currentTarget;
	const button = form.querySelector('button[type="submit"]');
	const feedback = document.getElementById('dashboard-master-feedback');
	button.disabled = true;
	try {
		const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
		const result = await response.json();
		if (!response.ok || !result.success) throw new Error(result.message || 'Salvataggio non riuscito.');
		feedback.textContent = result.message || 'Master aggiornato correttamente.';
		feedback.className = 'mt-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800';
	} catch (error) {
		feedback.textContent = error.message || 'Salvataggio non riuscito.';
		feedback.className = 'mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800';
	} finally {
		button.disabled = false;
	}
});
</script>
