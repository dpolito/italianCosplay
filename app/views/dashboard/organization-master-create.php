<?php use App\Core\Session; $organization = $organization ?? []; ?>
<main class="mx-auto max-w-6xl p-6">
	<a href="/dashboard/organizations/<?php echo (int) $organization['id']; ?>" class="font-semibold text-green-800 hover:underline">Torna all'organizzazione</a>
	<h1 class="mt-5 text-3xl font-bold">Proponi nuovo master</h1>
	<p class="mt-2 max-w-3xl text-slate-600">Il master sarà collegato a questa organizzazione e inviato allo staff per la verifica. Fino all'approvazione non sarà pubblico.</p>
	<?php if ($message = Session::getFlash('error')): ?><div class="mt-4 rounded-lg bg-red-100 p-4 text-red-800"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
	<div id="master-create-feedback" class="mt-4 hidden rounded-lg p-4 text-sm"></div>
	<form id="master-create-form" action="/dashboard/organizations/<?php echo (int) $organization['id']; ?>/masters/create" method="post" class="mt-6 grid gap-6 rounded-2xl bg-white p-6 shadow md:p-8 lg:grid-cols-2">
		<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
		<div class="lg:col-span-2"><label class="block text-sm font-semibold">Nome master<input required maxlength="255" name="nome" class="mt-1 w-full rounded-lg border px-3 py-3"></label></div>
		<div class="lg:col-span-2"><label for="master-create-description" class="block text-sm font-semibold">Descrizione</label><div id="master-create-description" class="mt-1 min-h-48 rounded-lg border bg-white"></div></div>
		<div><label class="block text-sm font-semibold">Sito web<input type="url" maxlength="500" name="sito_web" class="mt-1 w-full rounded-lg border px-3 py-3"></label></div>
		<?php foreach (['social_facebook' => 'Facebook', 'social_twitter' => 'Twitter/X', 'social_instagram' => 'Instagram', 'social_tiktok' => 'TikTok', 'social_youtube' => 'YouTube'] as $field => $label): ?>
			<div><label class="block text-sm font-semibold"><?php echo $label; ?><input type="url" maxlength="500" name="<?php echo $field; ?>" class="mt-1 w-full rounded-lg border px-3 py-3"></label></div>
		<?php endforeach; ?>
		<div class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900 lg:col-span-2">Stato iniziale: <strong>In revisione</strong>. Lo staff verificherà i dati prima di rendere pubblico il master.</div>
		<div class="flex justify-end lg:col-span-2"><button type="submit" class="rounded-lg bg-green-800 px-5 py-3 font-bold text-white">Invia master</button></div>
	</form>
</main>
<script src="/public_assets/js/wysiwyg-editor.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
	WysiwygEditor.init('#master-create-description', { content: '', name: 'descrizione', placeholder: 'Scrivi la descrizione del master...' });
});
</script>
<script>
document.getElementById('master-create-form').addEventListener('submit', async function (event) {
	event.preventDefault();
	const form = event.currentTarget, button = form.querySelector('button'), feedback = document.getElementById('master-create-feedback');
	button.disabled = true;
	try {
		const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
		const result = await response.json();
		if (!response.ok || !result.success) throw new Error(result.message || 'Master non inviato.');
		feedback.textContent = result.message;
		feedback.className = 'mt-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800';
		form.reset();
	} catch (error) {
		feedback.textContent = error.message || 'Master non inviato.';
		feedback.className = 'mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800';
	} finally { button.disabled = false; }
});
</script>
