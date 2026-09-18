<?php
$csrf_token = $csrf_token ?? '';
?>
<section class="space-y-6">
	<div>
		<p class="text-sm font-bold uppercase tracking-wide text-green-900">Sponsor</p>
		<h1 class="text-2xl font-bold text-gray-950">Nuovo banner</h1>
		<p class="mt-1 text-gray-600">Carica una creatività e indirizza gli utenti verso la tua landing page o il sito dell’inserzionista.</p>
	</div>

	<form action="/dashboard/ads/banners/store" method="POST" enctype="multipart/form-data" class="space-y-6">
		<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

		<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
			<div class="grid gap-4 md:grid-cols-2">
				<label class="block">
					<span class="text-sm font-bold text-gray-800">Titolo</span>
					<input name="title" required class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none" placeholder="Nome banner">
				</label>
				<label class="block">
					<span class="text-sm font-bold text-gray-800">URL di destinazione</span>
					<input name="target_url" type="url" required class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-green-600 focus:outline-none" placeholder="https://...">
				</label>
				<label class="block">
					<span class="text-sm font-bold text-gray-800">Immagine banner</span>
					<input name="image" type="file" accept="image/jpeg,image/png,image/webp" required class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3">
					<span class="mt-1 block text-xs text-gray-500">Sono supportati JPG, PNG e WebP. L’immagine viene convertita in WebP quando possibile.</span>
				</label>
				<label class="block">
					<span class="text-sm font-bold text-gray-800">Immagine mobile opzionale</span>
					<input name="mobile_image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-3">
					<span class="mt-1 block text-xs text-gray-500">Consigliata per smartphone: formato verticale o compatto, ad esempio 300x250.</span>
				</label>
				<label class="block">
					<input type="hidden" name="type" value="sponsor">
				</label>
			</div>
		</section>

		<div class="flex flex-col gap-3 md:flex-row md:justify-end">
			<a href="/dashboard/ads/banners" class="rounded-lg border border-gray-300 px-5 py-3 text-center text-sm font-bold text-gray-800 hover:bg-gray-50">Annulla</a>
			<button class="rounded-lg bg-green-700 px-5 py-3 text-sm font-bold text-white hover:bg-green-800">Salva banner</button>
		</div>
	</form>
</section>
