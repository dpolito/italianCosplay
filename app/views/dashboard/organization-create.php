<?php use App\Core\Session; ?>
<?php $h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>
<main class="mx-auto max-w-6xl p-6">
	<a href="/dashboard/organizations" class="font-semibold text-green-800 hover:underline">Torna alle organizzazioni</a>
	<div class="mt-5">
		<p class="text-sm font-bold uppercase tracking-wide text-green-900">Nuova organizzazione</p>
		<h1 class="mt-2 text-3xl font-bold">Crea nuova organizzazione</h1>
		<p class="mt-2 max-w-3xl text-slate-600">Inserisci tutti i dati disponibili. La richiesta sarà verificata dallo staff prima della pubblicazione.</p>
	</div>
	<?php if ($message = Session::getFlash('error')): ?><div class="mt-4 rounded-lg bg-red-100 p-4 text-red-800"><?php echo $h($message); ?></div><?php endif; ?>
	<div id="organization-create-feedback" class="mt-4 hidden rounded-lg p-4 text-sm"></div>
	<form id="organization-create-form" action="/dashboard/organizations/create" method="post" enctype="multipart/form-data" class="mt-6 grid gap-6 rounded-2xl bg-white p-6 shadow md:p-8 lg:grid-cols-2">
		<input type="hidden" name="csrf_token" value="<?php echo $h($_SESSION['csrf_token'] ?? ''); ?>">
		<div><label class="block text-sm font-semibold" for="organization-create-name">Nome organizzazione</label><input id="organization-create-name" required maxlength="180" name="name" class="mt-1 w-full rounded-lg border px-3 py-3"></div>
		<div><label class="block text-sm font-semibold" for="organization-create-legal-name">Nome legale</label><input id="organization-create-legal-name" maxlength="220" name="legal_name" class="mt-1 w-full rounded-lg border px-3 py-3"></div>
		<div><label class="block text-sm font-semibold" for="organization-create-email">Email</label><input id="organization-create-email" type="email" maxlength="255" name="email" class="mt-1 w-full rounded-lg border px-3 py-3"></div>
		<div><label class="block text-sm font-semibold" for="organization-create-phone">Telefono</label><input id="organization-create-phone" maxlength="50" name="phone" class="mt-1 w-full rounded-lg border px-3 py-3"></div>
		<div><label class="block text-sm font-semibold" for="organization-create-website">Sito web</label><input id="organization-create-website" type="url" maxlength="500" name="website_url" class="mt-1 w-full rounded-lg border px-3 py-3"></div>
		<div><label class="block text-sm font-semibold" for="organization-create-facebook">Facebook</label><input id="organization-create-facebook" type="url" maxlength="500" name="facebook_url" class="mt-1 w-full rounded-lg border px-3 py-3"></div>
		<div><label class="block text-sm font-semibold" for="organization-create-instagram">Instagram</label><input id="organization-create-instagram" type="url" maxlength="500" name="instagram_url" class="mt-1 w-full rounded-lg border px-3 py-3"></div>
		<div><label class="block text-sm font-semibold" for="organization-create-tiktok">TikTok</label><input id="organization-create-tiktok" type="url" maxlength="500" name="tiktok_url" class="mt-1 w-full rounded-lg border px-3 py-3"></div>
		<div><label class="block text-sm font-semibold" for="organization-create-youtube">YouTube</label><input id="organization-create-youtube" type="url" maxlength="500" name="youtube_url" class="mt-1 w-full rounded-lg border px-3 py-3"></div>
		<div class="lg:col-span-2"><label class="block text-sm font-semibold" for="organization-create-description">Descrizione</label><textarea id="organization-create-description" name="description" rows="7" class="mt-1 w-full rounded-lg border px-3 py-3"></textarea></div>
		<div><label class="block text-sm font-semibold" for="organization-create-logo">Logo</label><input id="organization-create-logo" type="file" name="logo" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full rounded-lg border px-3 py-3"><p class="mt-1 text-xs text-slate-500">JPG, PNG o WebP.</p></div>
		<div><label class="block text-sm font-semibold" for="organization-create-cover">Cover</label><input id="organization-create-cover" type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full rounded-lg border px-3 py-3"><p class="mt-1 text-xs text-slate-500">JPG, PNG o WebP.</p></div>
		<div class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900 lg:col-span-2">L’organizzazione sarà creata come <strong>In revisione</strong> e resterà non pubblica finché lo staff non avrà verificato i dati.</div>
		<div class="flex justify-end lg:col-span-2"><button type="submit" class="rounded-lg bg-green-800 px-5 py-3 font-bold text-white">Invia richiesta</button></div>
	</form>
</main>
<script>
document.getElementById('organization-create-form').addEventListener('submit', async function (event) {
	event.preventDefault();
	const form = event.currentTarget;
	const button = form.querySelector('button');
	const feedback = document.getElementById('organization-create-feedback');
	button.disabled = true;
	try {
		const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
		const result = await response.json();
		if (!response.ok || !result.success) throw new Error(result.message || 'Richiesta non inviata.');
		feedback.textContent = result.message;
		feedback.className = 'mt-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800';
		form.reset();
	} catch (error) {
		feedback.textContent = error.message || 'Richiesta non inviata.';
		feedback.className = 'mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800';
	} finally {
		button.disabled = false;
	}
});
</script>
