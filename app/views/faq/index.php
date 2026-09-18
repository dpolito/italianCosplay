<?php
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$faqEntities = [];
foreach ($categories as $category) {
	foreach ($category['items'] as $item) {
		$faqEntities[] = ['@type' => 'Question', 'name' => $item['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']]];
	}
}
?>

<section class="bg-gradient-to-b from-amber-50 to-gray-100 py-10 sm:py-14">
	<div class="container mx-auto px-4">
		<nav aria-label="Breadcrumb" class="mb-5 text-sm text-gray-600"><a href="/" class="hover:text-amber-700">Home</a> <span aria-hidden="true">/</span> <span>FAQ</span></nav>
		<header class="mb-8 text-center">
			<p class="mb-2 font-semibold uppercase tracking-wider text-amber-700">Centro assistenza</p>
			<h1 class="text-3xl font-bold text-gray-900 sm:text-4xl">Come possiamo aiutarti?</h1>
			<p class="mx-auto mt-3 max-w-2xl text-gray-600">Cerca una funzione del sito oppure esplora le domande divise per categoria.</p>
		</header>

		<form action="/faq" method="get" role="search" class="mx-auto mb-10 flex max-w-2xl gap-2">
			<label for="faq-search" class="sr-only">Cerca nelle domande frequenti</label>
			<input id="faq-search" name="q" type="search" value="<?= $escape($search) ?>" maxlength="100" placeholder="Es. Come salvo un evento?" class="min-w-0 flex-1 rounded-xl border border-gray-300 bg-white px-4 py-3 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
			<button class="rounded-xl bg-amber-600 px-5 py-3 font-semibold text-white hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-400">Cerca</button>
		</form>

		<?php if ($search !== ''): ?>
			<div class="mb-6 flex items-center justify-between gap-4 rounded-lg bg-white p-4 shadow-sm">
				<p>Risultati per <strong>“<?= $escape($search) ?>”</strong></p><a href="/faq" class="text-sm font-semibold text-amber-700 hover:underline">Azzera ricerca</a>
			</div>
		<?php endif; ?>

		<?php if (!$categories): ?>
			<section class="rounded-2xl bg-white p-8 text-center shadow-sm"><h2 class="text-xl font-bold text-gray-900">Nessuna risposta trovata</h2><p class="mt-2 text-gray-600">Prova con parole più semplici o consulta tutte le FAQ.</p><?php if ($search !== ''): ?><a href="/faq" class="mt-5 inline-block rounded-lg bg-amber-600 px-5 py-2.5 font-semibold text-white">Vedi tutte le FAQ</a><?php endif; ?></section>
		<?php else: ?>
			<div class="space-y-8">
				<?php foreach ($categories as $category): ?>
					<section id="<?= $escape($category['slug']) ?>" aria-labelledby="faq-category-<?= (int) $category['id'] ?>">
						<h2 id="faq-category-<?= (int) $category['id'] ?>" class="text-2xl font-bold text-gray-900"><?= $escape($category['name']) ?></h2>
						<?php if (!empty($category['description'])): ?><p class="mt-1 text-gray-600"><?= $escape($category['description']) ?></p><?php endif; ?>
						<div class="mt-4 space-y-3">
							<?php foreach ($category['items'] as $item): ?>
								<details class="group rounded-xl border border-gray-200 bg-white shadow-sm">
									<summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 font-semibold text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-400"><span><?= $escape($item['question']) ?></span><span aria-hidden="true" class="text-amber-700 transition group-open:rotate-45">+</span></summary>
									<div class="border-t border-gray-100 px-5 py-4 leading-7 text-gray-700"><?= nl2br($escape($item['answer'])) ?></div>
								</details>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php if ($search === '' && $faqEntities): ?>
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqEntities], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
