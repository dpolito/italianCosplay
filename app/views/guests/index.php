<?php
$guests = $data['guests'] ?? [];
$eventCount = count($guests);

$siteBaseUrl = rtrim(URL_ROOT_SITE, '/');
$pageTitle = "Ospiti Eventi Cosplay Italia";

if (!function_exists('guest_index_h')) {
	function guest_index_h($value, int $maxLength = 150): string {

		$value = (string) $value;

		if ($maxLength > 0 && mb_strlen(strip_tags($value)) > $maxLength) {
			$value = mb_substr(strip_tags($value), 0, $maxLength) . '...';
		}

		return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
	}
}
?>

<main class="bg-gray-100">
	<div class="container mx-auto px-4 py-6 md:px-6">

		<!-- BREADCRUMB (opzionale futuro) -->
		<nav class="mb-5 text-sm text-gray-700">
			<a href="/" class="hover:underline text-green-900">Home</a>
			<span class="mx-2">/</span>
			<span>Ospiti</span>
		</nav>

		<!-- HERO -->
		<header class="mb-8 rounded-xl bg-white p-5 shadow-md md:p-8">
			<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">

				<div>
					<p class="mb-2 text-sm font-bold uppercase tracking-wide text-green-900">
						Guest & Creator Hub
					</p>

					<h1 class="text-3xl font-extrabold text-gray-950 md:text-5xl">
						Ospiti eventi cosplay in Italia
					</h1>

					<p class="mt-4 text-lg text-gray-700 max-w-3xl">
						Scopri doppiatori, cosplayer, creator, artisti e ospiti speciali presenti nei festival cosplay italiani.
					</p>
				</div>

				<!-- BOX COUNT + CTA -->
				<aside class="rounded-lg border border-green-100 bg-green-50 p-5">
					<p class="text-sm font-semibold text-green-900">Ospiti registrati</p>
					<p class="mt-1 text-4xl font-extrabold text-gray-950">
						<?= (int)$eventCount ?>
					</p>

					<p class="mt-2 text-sm text-gray-700">
						Profili collegati agli eventi cosplay italiani.
					</p>
				</aside>

			</div>
		</header>

		<!-- GRID -->
		<section aria-labelledby="lista-ospiti">
			<div class="mb-5">
				<h2 id="lista-ospiti" class="text-2xl font-bold text-gray-950">
					Tutti gli ospiti
				</h2>
				<p class="text-gray-600 mt-1">
					Clicca un profilo per scoprire biografia, eventi e apparizioni.
				</p>
			</div>

			<?php if (!empty($guests)): ?>
				<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">

					<?php foreach ($guests as $guest): ?>
						<?php
						$guestUrl = "/ospiti/" . ($guest['slug'] ?? '');
						$eventCounter = $guest['event_count'] ?? null; // se lo aggiungi dopo
						?>

						<article class="group overflow-hidden rounded-xl bg-white shadow-md transition hover:-translate-y-1 hover:shadow-xl">

							<a href="<?= guest_index_h($guestUrl) ?>" class="block">

								<!-- IMAGE -->
								<div class="relative aspect-square bg-gray-200">
									<?php if (!empty($guest['immagine'])): ?>
										<img
												src="/public_assets/<?= guest_index_h($guest['immagine']) ?>"
												class="h-full w-full object-cover group-hover:scale-105 transition"
												alt="<?= guest_index_h($guest['name']) ?>"
												loading="lazy"
										>
									<?php else: ?>
										<div class="flex h-full items-center justify-center text-gray-500">
											No image
										</div>
									<?php endif; ?>

									<!-- badge -->
									<span class="absolute top-3 left-3 rounded-full bg-black/70 px-3 py-1 text-xs font-bold text-white">
										Ospite
									</span>

									<?php if ($eventCounter): ?>
										<span class="absolute top-3 right-3 rounded-full bg-green-800 px-3 py-1 text-xs font-bold text-white">
											<?= (int)$eventCounter ?> eventi
										</span>
									<?php endif; ?>
								</div>

								<!-- BODY -->
								<div class="p-5">
									<h3 class="text-lg font-extrabold text-gray-950 group-hover:text-green-900">
										<?= guest_index_h($guest['name']) ?>
									</h3>

									<p class="mt-2 text-sm text-gray-600 line-clamp-2">
										<?= guest_index_h($guest['bio'] ?? 'Ospite eventi cosplay e cultura pop.') ?>
									</p>

									<div class="mt-4 text-sm font-bold text-green-900">
										Scopri profilo →
									</div>
								</div>

							</a>
						</article>

					<?php endforeach; ?>

				</div>
			<?php else: ?>
				<div class="rounded-xl bg-white p-10 text-center shadow-md">
					<h2 class="text-2xl font-bold">Nessun ospite trovato</h2>
					<p class="mt-2 text-gray-600">Non ci sono ospiti registrati al momento.</p>
				</div>
			<?php endif; ?>
		</section>

	</div>
</main>
