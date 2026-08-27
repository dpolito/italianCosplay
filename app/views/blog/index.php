<?php
$posts = $data['posts'] ?? [];
$categories = $data['categories'] ?? [];
$totalPosts = $data['total_posts'] ?? 0;
$activeCategory = $data['active_category'] ?? null;
$total_pages = $data['total_pages'] ?? null;
$page = $data['page'] ?? null;

$siteBaseUrl = rtrim(URL_ROOT_SITE, '/');
$blogBasePath = $siteBaseUrl . '/blog';

if (!function_exists('blog_h')) {
	function blog_h($value): string {
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}
$breadcrumbs = $data['breadcrumbs'] ?? [];
$breadcrumbSchema = [
	'@context' => 'https://schema.org',
	'@type' => 'BreadcrumbList',
	'itemListElement' => [],
];

foreach ($breadcrumbs as $index => $crumb) {
	$breadcrumbSchema['itemListElement'][] = [
		'@type' => 'ListItem',
		'position' => $index + 1,
		'name' => $crumb['label'] ?? '',
		'item' => $crumb['url'] ?? null,
	];
}
?>
<script type="application/ld+json"><?php echo json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

<main class="bg-gray-100">
	<div class="container mx-auto px-4 py-6 md:px-6">

		<!-- ================= BREADCRUMB (EVENTI STYLE) ================= -->
		<nav class="mb-5 text-sm text-gray-700" aria-label="Breadcrumb">
			<ol class="flex flex-wrap items-center gap-2">
				<li class="flex items-center gap-2">
					<a href="/" class="font-medium text-green-900 hover:underline">Home</a>
					<span class="text-gray-400">/</span>
				</li>
				<li class="font-medium text-gray-700">Blog</li>
			</ol>
		</nav>

		<!-- ================= HEADER (EVENTI STYLE IDENTICO) ================= -->
		<header class="mb-8 rounded-xl bg-white p-5 shadow-md md:p-8">

			<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start">

				<div>
					<p class="mb-2 text-sm font-bold uppercase tracking-wide text-green-900">
						Blog Cosplay & Guide
					</p>

					<h1 class="text-3xl font-extrabold leading-tight text-gray-950 md:text-5xl">
						Guide, News e Articoli Cosplay
					</h1>

					<div class="mt-4 max-w-4xl text-lg leading-relaxed text-gray-700">
						<p>
							Tutorial cosplay, eventi, personaggi, make-up, armature, prop e guide pratiche
							dal mondo del cosplay e della cultura nerd.
						</p>
					</div>
				</div>

				<aside class="rounded-lg border border-green-100 bg-green-50 p-4" aria-label="Riepilogo articoli">
					<p class="text-sm font-semibold text-green-900">Articoli pubblicati</p>
					<p class="mt-1 text-4xl font-extrabold text-gray-950"><?php echo (int)$totalPosts; ?></p>
					<p class="mt-2 text-sm text-gray-700">
						Guide, news e contenuti aggiornati dal mondo cosplay.
					</p>
				</aside>

			</div>
		</header>

		<?php echo \App\Helpers\AdPlacement::render('blog_listing_top', 'blog', 'mb-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md'); ?>

		<!-- ================= NAV QUICK (EVENTI STYLE) ================= -->
		<nav class="mb-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Navigazione blog">

			<a href="/blog" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
				Tutti gli articoli
			</a>

			<a href="/blog?sort=latest" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
				Più recenti
			</a>

			<a href="/blog?sort=views" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
				Più letti
			</a>

			<a href="#blog-list" class="rounded-lg bg-white p-4 font-semibold text-green-900 shadow hover:bg-green-50">
				Vai agli articoli
			</a>

		</nav>

		<!-- ================= FILTER (EVENTI STYLE IDENTICO) ================= -->
		<section class="relative z-30 mb-8 rounded-xl bg-green-950 p-4 shadow-lg md:sticky md:top-20 md:p-6">

			<h2 class="mb-4 text-xl font-bold text-white">Filtra articoli per categoria</h2>

			<div class="flex flex-wrap gap-3">

				<a href="/blog"
				   class="rounded-full px-4 py-2 text-sm font-semibold border transition
				   <?= empty($activeCategory) ? 'bg-white text-green-900' : 'bg-green-800 text-white border-green-800 hover:bg-green-700' ?>">
					Tutte
				</a>

				<?php foreach ($categories as $cat): ?>
					<a href="/blog/categoria/<?php echo blog_h($cat['slug']); ?>"
					   class="rounded-full px-4 py-2 text-sm font-semibold border transition
					   <?= ($activeCategory === $cat['slug'])
						   ? 'bg-white text-green-900'
						   : 'bg-green-800 text-white border-green-800 hover:bg-green-700' ?>">
						<?php echo blog_h($cat['name']); ?>
					</a>
				<?php endforeach; ?>

			</div>

		</section>

		<?php echo \App\Helpers\AdPlacement::render('blog_listing_inline', 'blog', 'my-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md'); ?>

		<!-- ================= POSTS GRID (EVENTI CARDS STYLE IDENTICO) ================= -->
		<section id="blog-list" class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">

			<?php foreach ($posts as $post): ?>

				<?php
				$postUrl = $blogBasePath . '/' . ($post['slug'] ?? '');
				?>

				<article class="group overflow-hidden rounded-xl bg-white shadow-md transition hover:-translate-y-0.5 hover:shadow-xl">

					<a href="<?php echo blog_h($postUrl); ?>" class="block">

						<?php if (!empty($post['cover_image'])): ?>
							<div class="relative aspect-[16/10] bg-gray-200">
								<img
										src="/public_assets/<?php echo blog_h($post['cover_image']); ?>"
										class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
										loading="lazy"
										alt="<?php echo blog_h($post['titolo']); ?>"
								>
							</div>
						<?php endif; ?>

						<div class="p-5">

							<?php if (!empty($post['category_name'])): ?>
								<p class="mb-2 inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
									<?php echo blog_h($post['category_name']); ?>
								</p>
							<?php endif; ?>

							<h3 class="text-xl font-extrabold leading-snug text-gray-950 group-hover:text-green-900">
								<?php echo blog_h($post['titolo']); ?>
							</h3>

							<p class="mt-3 text-sm text-gray-600">
								<?php echo blog_h($post['excerpt']); ?>
							</p>

							<p class="mt-3 text-xs text-gray-500">
								📅 <?php echo date('d/m/Y', strtotime($post['created_at'])); ?>
								· 👁 <?php echo (int)$post['total_views']; ?> letture
							</p>

							<span class="mt-4 inline-flex font-bold text-green-900 group-hover:underline">
								Leggi articolo
							</span>

						</div>

					</a>

				</article>

			<?php endforeach; ?>

		</section>

		<!-- ================= EMPTY STATE ================= -->
		<?php if (empty($posts)): ?>
			<div class="rounded-xl bg-white p-8 text-center shadow-md mt-8">
				<h2 class="text-2xl font-bold text-gray-950">Nessun articolo trovato</h2>
				<p class="mt-3 text-gray-700">
					Prova a cambiare categoria o torna più tardi.
				</p>
			</div>
		<?php endif; ?>

		<!-- ================= PAGINATION (EVENTI STYLE) ================= -->
		<?php if ($total_pages > 1): ?>

			<nav class="flex flex-wrap items-center justify-center gap-2 mt-10">

				<!-- PREV -->
				<?php if ($page > 1): ?>
					<a href="<?= $page - 1 == 1 ? '/blog' : '/blog/pagina/' . ($page - 1) ?>"
					   class="flex items-center gap-1 px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white hover:bg-gray-50 transition"
					   aria-label="Pagina precedente">

						<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
						</svg>

						Precedente
					</a>
				<?php endif; ?>


				<?php
				$start = max(1, $page - 2);
				$end = min($total_pages, $page + 2);
				?>


				<!-- FIRST PAGE -->
				<?php if ($start > 1): ?>
					<a href="/blog"
					   class="px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white hover:bg-gray-50 transition">
						1
					</a>

					<?php if ($start > 2): ?>
						<span class="px-2 text-gray-400">...</span>
					<?php endif; ?>
				<?php endif; ?>


				<!-- PAGES -->
				<?php for ($i = $start; $i <= $end; $i++): ?>
					<a href="<?= $i == 1 ? '/blog' : '/blog/pagina/' . $i ?>"
					   class="px-3 py-2 text-sm rounded-lg border transition
           <?= $i == $page
						   ? 'bg-black text-white border-black'
						   : 'bg-white border-gray-200 hover:bg-gray-50' ?>">

						<?= $i ?>
					</a>
				<?php endfor; ?>


				<!-- LAST PAGE -->
				<?php if ($end < $total_pages): ?>
					<?php if ($end < $total_pages - 1): ?>
						<span class="px-2 text-gray-400">...</span>
					<?php endif; ?>

					<a href="/blog/pagina/<?= $total_pages ?>"
					   class="px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white hover:bg-gray-50 transition">
						<?= $total_pages ?>
					</a>
				<?php endif; ?>


				<!-- NEXT -->
				<?php if ($page < $total_pages): ?>
					<a href="/blog/pagina/<?= $page + 1 ?>"
					   class="flex items-center gap-1 px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white hover:bg-gray-50 transition"
					   aria-label="Pagina successiva">

						Successiva

						<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
						</svg>
					</a>
				<?php endif; ?>

			</nav>

		<?php endif; ?>

		<?php echo \App\Helpers\AdPlacement::render('blog_listing_bottom', 'blog', 'mt-10 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md'); ?>

	</div>
</main>
