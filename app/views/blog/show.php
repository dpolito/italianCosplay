<?php
$post = $data['post'] ?? null;
$cover = $data['cover'] ?? null;
$categoria = $data['categoria'] ?? null;
$wordCount = $data['wordCount'] ?? 0;
$breadcrumbs = $data['breadcrumbs'] ?? [];
$relatedPosts = $data['relatedPosts'] ?? [];
$relatedEvents = $data['relatedEvents'] ?? [];
if(!$post){
	return;
}
$siteUrl = rtrim(URL_ROOT_SITE, '/');
$postUrl = $siteUrl . '/blog/' . $post['slug'];
$imageUrl = !empty($cover['path'])
	? $siteUrl . '/public_assets/' . ltrim($cover['path'], '/')
	: '';
$description = !empty($post['excerpt'])
	? $post['excerpt']
	: mb_substr(strip_tags($post['contenuto']), 0, 160);
$shareText = trim($description);
$shareUrlEncoded = rawurlencode($postUrl);
$shareTextEncoded = rawurlencode($shareText);
$schema = [
	'@context'         => 'https://schema.org',
	'@type'            => 'BlogPosting',
	'headline'         => $post['meta_title'],
	'description'      => $description,
	'datePublished'    => date('c', strtotime($post['created_at'])),
	'dateModified'     => date('c', strtotime($post['updated_at'] ?? $post['created_at'])),
	'mainEntityOfPage' => $postUrl,
	'author'           => [
		'@type' => 'Person',
		'name'  => $post['author_name'] ?? 'Redazione ItalianCosplay',
	],
	'publisher'        => [
		'@type' => 'Organization',
		'name'  => 'ItalianCosplay',
	],
	'wordCount'=> $wordCount,
  'articleSection'=> ''.$categoria['name'].'',
  'inLanguage'=> 'it',
  'keywords'=> 'cosplay costi, quanto costa cosplay, cosplay budget'
];
if($imageUrl){
	$schema['image'] = [$imageUrl];
}
$breadcrumbs = $data['breadcrumbs'] ?? [];
$breadcrumbSchema = [
	'@context'        => 'https://schema.org',
	'@type'           => 'BreadcrumbList',
	'itemListElement' => [],
];
foreach($breadcrumbs as $index => $crumb){
	$breadcrumbSchema['itemListElement'][] = [
		'@type'    => 'ListItem',
		'position' => $index + 1,
		'name'     => $crumb['label'] ?? '',
		'item'     => $crumb['url'] ?? null,
	];
}
if(!function_exists('home_date_label')){
	function home_date_label(?string $startDate, ?string $endDate = null) : string{
		if(empty($startDate)){
			return 'Data da confermare';
		}
		$label = date('d/m/Y', strtotime($startDate));
		if(!empty($endDate) && $endDate !== $startDate){
			$label .= ' - ' . date('d/m/Y', strtotime($endDate));
		}

		return $label;
	}
}
?>
<script type="application/ld+json"><?php echo json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

<script type="application/ld+json">
<?=json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);?>


</script>



	<div class="container mx-auto px-4 py-6 md:px-6">

		<!-- BREADCRUMB (stile events/shop.php) -->
		<nav class="mb-5 text-sm text-gray-700" aria-label="Breadcrumb">
			<ol class="flex flex-wrap items-center gap-2">
				<?php foreach($breadcrumbs as $index => $crumb): ?>
					<li class="flex items-center gap-2">
						<?php if($index > 0): ?>
							<span class="text-gray-400">/</span>
						<?php endif; ?>

						<?php if(!empty($crumb['url'])): ?>
							<a href="<?=htmlspecialchars($crumb['url'])?>" class="font-medium text-green-900 hover:underline">
								<?=htmlspecialchars($crumb['label'])?>
							</a>
						<?php else: ?>
							<span class="text-gray-600">
								<?=htmlspecialchars($crumb['label'])?>
							</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>

		<article class="overflow-hidden rounded-xl bg-white shadow-lg">

			<!-- HERO -->
			<header class="grid lg:grid-cols-[minmax(0,1.35fr)_minmax(340px,0.65fr)]">

				<div class="relative min-h-[320px] md:min-h-[460px] bg-gray-200">

					<?php if($imageUrl): ?>
						<img
								src="<?=htmlspecialchars($imageUrl)?>"
								alt="<?=htmlspecialchars($post['titolo'])?>"
								class="absolute inset-0 h-full w-full object-cover"
								loading="eager"
								fetchpriority="high"
						>
					<?php else: ?>
						<div class="absolute inset-0 flex items-center justify-center text-gray-500">
							Immagine non disponibile
						</div>
					<?php endif; ?>

					<div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent p-6 text-white lg:hidden">
						<p class="text-sm font-semibold uppercase">Blog ItalianCosplay</p>
					</div>

				</div>

				<div class="flex flex-col justify-between gap-6 p-5 md:p-8">

					<div>
						<?php if(!empty($categoria['name'])): ?>
							<span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-800 mb-4">
								<?=htmlspecialchars($categoria['name'])?>
							</span>
						<?php endif; ?>

						<h1 class="text-3xl md:text-4xl font-extrabold text-gray-900">
							<?=htmlspecialchars($post['titolo'])?>
						</h1>

						<div class="mt-5 text-sm text-gray-600 space-y-2">
							<p>📅 <?=date('d/m/Y', strtotime($post['created_at']))?></p>
							<p>✍️ <?=htmlspecialchars($post['author_name'] ?? 'Redazione')?></p>

							<?php if(!empty($post['reading_time'])): ?>
								<p>⏱️ <?=(int) $post['reading_time']?> min lettura</p>
							<?php endif; ?>
						</div>

						<?php if(!empty($post['excerpt'])): ?>
							<div class="mt-6 border-l-4 border-green-700 pl-4 italic text-gray-700">
								<?=htmlspecialchars($post['excerpt'])?>
							</div>
						<?php endif; ?>
					</div>

					<a href="/blog"
					   class="inline-flex items-center justify-center rounded-lg bg-green-800 px-4 py-3 font-bold text-white hover:bg-green-900">
						Torna al blog
					</a>

				</div>
			</header>

			<!-- BODY -->
			<div class="grid gap-8 p-5 md:p-8 lg:grid-cols-[minmax(0,1fr)_340px]">

				<div class="space-y-10">

					<section>
						<h2 class="text-2xl font-bold mb-4 text-gray-900">Contenuto</h2>

						<div class="prose max-w-none">
							<?=$post['contenuto']?>
						</div>
					</section>

				</div>

				<!-- ASIDE -->
				<aside class="space-y-6">

					<section class="rounded-xl border bg-gray-50 p-5">
						<h2 class="text-xl font-bold mb-4">Info articolo</h2>

						<dl class="text-sm space-y-3">
							<div>
								<dt class="font-bold">Autore</dt>
								<dd><?=htmlspecialchars($post['author_name'] ?? 'Redazione')?></dd>
							</div>

							<div>
								<dt class="font-bold">Data</dt>
								<dd><?=date('d/m/Y', strtotime($post['created_at']))?></dd>
							</div>

							<?php if(!empty($post['reading_time'])): ?>
								<div>
									<dt class="font-bold">Lettura</dt>
									<dd><?=(int) $post['reading_time']?> min</dd>
								</div>
							<?php endif; ?>
						</dl>
					</section>

					<!-- SHARE STANDARD (stile eventi) -->
					<section class="rounded-xl border bg-white p-5">
						<h2 class="text-xl font-bold mb-4">Condividi</h2>

						<div class="grid grid-cols-2 gap-3 text-sm">

							<a href="https://www.facebook.com/sharer/sharer.php?u=<?=$shareUrlEncoded?>"
							   target="_blank"
							   class="rounded-lg border px-3 py-2 text-center font-semibold">
								<i class="fa-brands fa-facebook-f text-blue-700" aria-hidden="true"></i>Facebook
							</a>

							<a href="https://api.whatsapp.com/send?text=<?=$shareTextEncoded?>%20<?=$shareUrlEncoded?>"
							   target="_blank"
							   class="rounded-lg border px-3 py-2 text-center font-semibold">
								<i class="fa-brands fa-whatsapp text-green-700" aria-hidden="true"></i>WhatsApp
							</a>

							<a href="https://t.me/share/url?url=<?=$shareUrlEncoded?>&text=<?=$shareTextEncoded?>"
							   target="_blank"
							   class="rounded-lg border px-3 py-2 text-center font-semibold">
								<i class="fa-brands fa-telegram text-sky-600" aria-hidden="true"></i>Telegram
							</a>

							<a href="https://twitter.com/intent/tweet?url=<?php echo $shareUrlEncoded; ?>&text=<?php echo $shareTextEncoded; ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 font-semibold text-gray-800 hover:border-green-800 hover:bg-green-50" aria-label="Condividi su X">
								<i class="fa-brands fa-x-twitter text-gray-900" aria-hidden="true"></i>
								<span>X</span>
							</a>
							<a href="mailto:?subject=<?php echo $shareTextEncoded; ?>&body=<?php echo $shareTextEncoded; ?>%0A<?php echo $shareUrlEncoded; ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 font-semibold text-gray-800 hover:border-green-800 hover:bg-green-50" aria-label="Condividi via email">
								<i class="fa-solid fa-envelope text-red-700" aria-hidden="true"></i>
								<span>Email</span>
							</a>
							<button type="button" class="js-copy-event-link inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 font-semibold text-gray-800 hover:border-green-800 hover:bg-green-50" data-share-url="<?php echo htmlspecialchars($shareUrlEncoded, ENT_QUOTES, 'UTF-8'); ?>">
								<i class="fa-solid fa-link text-green-900" aria-hidden="true"></i>
								<span>Copia link</span>
							</button>

						</div>
					</section>
					<!-- RELATED POSTS -->
					<?php if(!empty($relatedPosts)): ?>
						<section>
							<h2 class="text-2xl font-bold mb-4">Articoli correlati</h2>

							<div class="grid gap-5">
								<?php foreach($relatedPosts as $related): ?>
									<article class="rounded-xl bg-white shadow hover:shadow-lg overflow-hidden">
										<a href="/blog/<?=htmlspecialchars($related['slug'])?>">

											<?php if(!empty($related['featured_image'])): ?>
												<img
														src="/public_assets/<?=htmlspecialchars($related['featured_image'])?>"
														class="h-40 w-full object-cover"
														alt="<?=htmlspecialchars($related['titolo'])?>"
												>
											<?php endif; ?>

											<div class="p-4">
												<h3 class="font-bold mb-2">
													<?=htmlspecialchars($related['titolo'])?>
												</h3>

												<p class="text-sm text-gray-600">
													<?=htmlspecialchars($related['excerpt'])?>
												</p>
											</div>

										</a>
									</article>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endif; ?>
					<!-- RELATED EVENTS -->
					<?php if(!empty($relatedEvents)): ?>
						<section>
							<h2 class="text-2xl font-bold mb-4">Eventi del WeekEnd</h2>

							<div class="grid gap-5">
								<?php foreach($relatedEvents as $event): //var_dump($event); ?>
									<?php
									$startDate = $event['data_inizio'] ?? null;
									$endDate = $event['data_fine'] ?? null;
									$imageUrl = $event['immagine'];
									if(empty($startDate)){
										return 'Data da confermare';
									}
									$label = date('d/m/Y', strtotime($startDate));
									if(!empty($endDate) && $endDate !== $startDate){
										$label .= ' - ' . date('d/m/Y', strtotime($endDate));
									}
									?>
									<a href="<?php echo '/eventi-cosplay/' . $event['slug']; ?>" class="flex gap-3 rounded-lg p-2 transition hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-800" title="<?php echo $event['titolo']; ?>">
										<?php if($imageUrl): ?>
											<img
													src="<?php echo $imageUrl; ?>"
													width="<?php echo $event['immagine_width'] ?? 96; ?>"
													height="<?php echo $event['immagine_height'] ?? 96; ?>"
													class="h-16 w-16 flex-none rounded-lg object-cover"
													loading="lazy"
													alt="<?php echo $event['titolo'] . ' evento cosplay'; ?>"
											>
										<?php endif; ?>
										<span class="min-w-0">
				<span class="block text-sm font-bold leading-snug text-gray-950"><?php echo $event['titolo']; ?></span>
				<span class="mt-1 block text-xs text-gray-600"><?php echo $label ?? ''; ?>
					<br/>
					<?php echo $event['comune_nome'] ?? ''; ?></span>
			</span>
									</a>
								<?php endforeach; ?>
							</div>

						</section>
						<nav class="mb-8 grid gap-3" aria-label="Navigazione rapida eventi">
							<a href="https://www.italiancosplay.it/eventi-cosplay" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
								Tutti gli eventi cosplay
							</a>
							<a href="https://www.italiancosplay.it/eventi-cosplay-mese" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
								Eventi cosplay del mese
							</a>
							<a href="https://www.italiancosplay.it/eventi-cosplay-weekend" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
								Eventi cosplay nel weekend
							</a>
							<a href="#eventi-cosplay" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
								Vedi prossimi eventi
							</a>
						</nav>
					<?php endif; ?>

				</aside>

			</div>
		</article>
	</div>
