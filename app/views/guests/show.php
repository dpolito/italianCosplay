<?php
$guestTitle = trim((string)($guest['name'] ?? 'Ospite cosplay'));
$guestBio = trim((string)($guest['bio'] ?? ''));
$siteBaseUrl = rtrim(URL_ROOT_SITE, '/');
$imageUrl = !empty($guest['immagine'])
	? '/public_assets/' . $guest['immagine']
	: '';

$guestUrl = URL_ROOT_SITE . '/ospiti/' . ($guest['slug'] ?? '');

$eventsCount = count($events ?? []);
?>

<main class="bg-gray-100">

	<div class="container mx-auto px-4 py-6 md:px-6">

		<!-- BREADCRUMB -->
		<nav class="mb-5 text-sm text-gray-700">
			<ol class="flex flex-wrap items-center gap-2">
				<li>
					<a href="/" class="hover:underline">Home</a>
				</li>
				<li>/</li>
				<li>
					<a href="/ospiti" class="hover:underline">Ospiti</a>
				</li>
				<li>/</li>
				<li class="font-semibold text-gray-900">
					<?= htmlspecialchars($guestTitle) ?>
				</li>
			</ol>
		</nav>

		<!-- ARTICLE -->
		<article class="overflow-hidden rounded-xl bg-white shadow-lg">

			<!-- HERO (STILE EVENTO) -->
			<header class="grid gap-0 lg:grid-cols-[minmax(0,1.35fr)_minmax(340px,0.65fr)]">

				<!-- IMAGE -->
				<div class="relative min-h-[320px] bg-gray-200 md:min-h-[460px]">

					<?php if (!empty($imageUrl)): ?>
						<img
								src="<?= htmlspecialchars($imageUrl) ?>"
								alt="<?= htmlspecialchars($guestTitle) ?>"
								class="absolute inset-0 h-full w-full object-cover"
								loading="eager"
						>
					<?php else: ?>
						<div class="absolute inset-0 flex items-center justify-center text-gray-500">
							Nessuna immagine disponibile
						</div>
					<?php endif; ?>

					<div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-6 text-white lg:hidden">
						<p class="text-sm uppercase tracking-wide">Ospite eventi cosplay</p>
					</div>

				</div>

				<!-- INFO -->
				<div class="flex flex-col justify-between gap-6 p-5 md:p-8">

					<div>

						<p class="hidden text-sm font-semibold uppercase text-green-900 lg:block">
							Ospite eventi cosplay
						</p>

						<h1 class="text-3xl font-extrabold text-gray-900 md:text-4xl">
							<?= htmlspecialchars($guestTitle) ?>
						</h1>

						<div class="mt-5 space-y-3 text-gray-700">

							<p>
								<span class="font-bold text-green-900">Eventi</span>
								<?= $eventsCount ?>
							</p>

							<?php if (!empty($guest['instagram'])): ?>
								<p>
									<a href="<?= htmlspecialchars($guest['instagram']) ?>" target="_blank"
									   class="text-green-900 font-semibold hover:underline">
										Instagram
									</a>
								</p>
							<?php endif; ?>
							<?php if (!empty($guest['tiktok'])): ?>
								<p>
									<a href="<?= htmlspecialchars($guest['tiktok']) ?>" target="_blank"
									   class="text-green-900 font-semibold hover:underline">
										Tiktok
									</a>
								</p>
							<?php endif; ?>
							<?php if (!empty($guest['youtube'])): ?>
								<p>
									<a href="<?= htmlspecialchars($guest['youtube']) ?>" target="_blank"
									   class="text-green-900 font-semibold hover:underline">
										Youtube
									</a>
								</p>
							<?php endif; ?>
							<?php if (!empty($guest['website'])): ?>
								<p>
									<a href="<?= htmlspecialchars($guest['website']) ?>" target="_blank"
									   class="text-green-900 font-semibold hover:underline">
										Sito ufficiale
									</a>
								</p>
							<?php endif; ?>

						</div>

					</div>

					<!-- CTA -->
					<div class="grid gap-3">
						<a href="/ospiti"
						   class="inline-flex items-center justify-center rounded-lg bg-green-800 px-4 py-3 font-bold text-white hover:bg-green-900">
							Tutti gli ospiti
						</a>
						<?php if (!empty($_SESSION['user_id'])): ?>
							<form method="post" action="/dashboard/favorites/toggle" class="js-favorite-toggle mt-2">
								<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
								<input type="hidden" name="entity_type" value="<?= htmlspecialchars($favoriteEntityType ?? 'guest') ?>">
								<input type="hidden" name="entity_id" value="<?= (int)($guest['id'] ?? 0) ?>">
								<input type="hidden" name="redirect_to" value="<?= htmlspecialchars($guestUrl) ?>">
								<button type="submit" class="js-favorite-button inline-flex w-full items-center justify-center gap-2 rounded-lg <?= !empty($isFavorited) ? 'bg-amber-800 text-white hover:bg-amber-900' : 'border border-amber-300 bg-amber-50 text-amber-900 hover:bg-amber-100' ?> px-4 py-3 font-bold transition" data-label-add="Salva tra i preferiti" data-label-remove="Rimuovi dai preferiti" data-icon-add="fa-bookmark" data-icon-remove="fa-bookmark-slash" data-active="<?= !empty($isFavorited) ? '1' : '0' ?>">
									<i class="fa-solid <?= !empty($isFavorited) ? 'fa-bookmark-slash' : 'fa-bookmark' ?>" aria-hidden="true"></i>
									<span><?= !empty($isFavorited) ? 'Rimuovi dai preferiti' : 'Salva tra i preferiti' ?></span>
								</button>
							</form>
						<?php else: ?>
							<a href="/login" class="inline-flex items-center justify-center rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 font-bold text-amber-900 hover:bg-amber-100">
								Accedi per salvare
							</a>
						<?php endif; ?>
					</div>

				</div>

			</header>

			<!-- BODY -->
			<div class="grid gap-8 p-5 md:p-8 lg:grid-cols-[minmax(0,1fr)_340px]">

				<!-- LEFT -->
				<div class="space-y-10">

					<!-- BIO -->
					<section>
						<h2 class="mb-4 text-2xl font-bold text-gray-900">
							Biografia
						</h2>

						<div class="text-gray-800 leading-relaxed">
							<?= html_entity_decode($guestBio) ?>
						</div>
					</section>

					<!-- EVENTS -->
					<section>
						<h2 class="mb-5 text-2xl font-bold text-gray-900">
							Eventi a cui ha partecipato
						</h2>

						<?php if (empty($events)): ?>
							<p class="text-gray-500">Nessun evento registrato.</p>
						<?php else: ?>

							<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">

								<?php foreach ($events as $event): ?>

									<article class="overflow-hidden rounded-lg border bg-white shadow-md hover:shadow-lg transition">

										<a href="/eventi/<?= htmlspecialchars($event['slug']) ?>">

											<?php if (!empty($event['immagine'])): ?>
												<img
														src="/public_assets/<?= htmlspecialchars($event['immagine']) ?>"
														class="h-40 w-full object-cover"
														loading="lazy"
														alt="<?= htmlspecialchars($event['titolo']) ?>"
												>
											<?php endif; ?>

											<div class="p-4">

												<h3 class="font-bold text-gray-900">
													<?= htmlspecialchars($event['titolo']) ?>
												</h3>

												<p class="text-sm text-gray-600 mt-1">
													<?= htmlspecialchars($event['data_inizio'] ?? '') ?>
												</p>

												<p class="text-sm text-gray-600">
													<?= htmlspecialchars($event['luogo'] ?? '') ?>
												</p>

											</div>

										</a>

									</article>

								<?php endforeach; ?>

							</div>

						<?php endif; ?>
					</section>

				</div>

				<!-- RIGHT SIDEBAR -->
				<aside class="space-y-6">

					<section class="rounded-xl border bg-gray-50 p-5">
						<h2 class="mb-4 text-xl font-bold text-gray-900">
							Info rapide
						</h2>

						<dl class="space-y-3 text-sm">

							<div>
								<dt class="font-bold text-gray-900">Nome</dt>
								<dd><?= htmlspecialchars($guestTitle) ?></dd>
							</div>

							<div>
								<dt class="font-bold text-gray-900">Eventi partecipati</dt>
								<dd><?= $eventsCount ?></dd>
							</div>

						</dl>
					</section>

					<section class="rounded-xl border bg-white p-5">
						<h2 class="mb-4 text-xl font-bold text-gray-900">
							Link utili
						</h2>

						<ul class="space-y-2 text-sm">
							<li><a class="text-green-900 hover:underline" href="/ospiti">Tutti gli ospiti</a></li>
							<li><a class="text-green-900 hover:underline" href="/eventi-cosplay">Eventi cosplay</a></li>
						</ul>
					</section>

				</aside>

			</div>

		</article>
	</div>
</main>
