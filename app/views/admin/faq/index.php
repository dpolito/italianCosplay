<?php $e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); ?>
<div class="container mx-auto space-y-10 p-6">
	<div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
		<div><h1 class="text-3xl font-semibold text-gray-800">FAQ</h1><p class="mt-1 text-gray-600">Gestisci categorie, domande, risposte e visibilità per funzionalità.</p></div>
		<div class="flex gap-2"><a href="/admin/faq/categories/create" class="rounded-lg bg-gray-700 px-4 py-2 font-semibold text-white hover:bg-gray-800">Nuova categoria</a><a href="/admin/faq/items/create" class="rounded-lg bg-green-600 px-4 py-2 font-semibold text-white hover:bg-green-700">Nuova FAQ</a></div>
	</div>

	<section class="overflow-hidden rounded-xl bg-white shadow"><div class="border-b px-5 py-4"><h2 class="text-xl font-bold">Categorie</h2></div>
		<?php if (!$categories): ?><p class="p-6 text-gray-600">Nessuna categoria presente.</p><?php else: ?><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-gray-50"><tr><th class="p-4">Nome</th><th class="p-4">Feature</th><th class="p-4">FAQ</th><th class="p-4">Stato</th><th class="p-4 text-right">Azioni</th></tr></thead><tbody class="divide-y">
		<?php foreach ($categories as $category): ?><tr><td class="p-4 font-semibold"><?= $e($category['name']) ?></td><td class="p-4"><?= $e($category['feature_flag_key'] ?: 'Sempre visibile') ?></td><td class="p-4"><?= (int) $category['item_count'] ?></td><td class="p-4"><?= (int) $category['is_active'] === 1 ? 'Pubblicata' : 'Nascosta' ?></td><td class="p-4"><div class="flex justify-end gap-3"><a class="font-semibold text-blue-700" href="/admin/faq/categories/<?= (int) $category['id'] ?>/edit">Modifica</a><form method="post" action="/admin/faq/categories/<?= (int) $category['id'] ?>/delete" onsubmit="return confirm('Nascondere ed eliminare questa categoria?');"><input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>"><button class="font-semibold text-red-700">Elimina</button></form></div></td></tr><?php endforeach; ?>
		</tbody></table></div><?php endif; ?>
	</section>

	<section class="overflow-hidden rounded-xl bg-white shadow"><div class="border-b px-5 py-4"><h2 class="text-xl font-bold">Domande e risposte</h2></div>
		<?php if (!$items): ?><p class="p-6 text-gray-600">Nessuna FAQ presente.</p><?php else: ?><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-gray-50"><tr><th class="p-4">Domanda</th><th class="p-4">Categoria</th><th class="p-4">Feature</th><th class="p-4">Stato</th><th class="p-4 text-right">Azioni</th></tr></thead><tbody class="divide-y">
		<?php foreach ($items as $item): ?><tr><td class="max-w-md p-4 font-semibold"><?= $e($item['question']) ?></td><td class="p-4"><?= $e($item['category_name']) ?></td><td class="p-4"><?= $e($item['feature_flag_key'] ?: 'Eredita categoria') ?></td><td class="p-4"><?= (int) $item['is_active'] === 1 ? 'Pubblicata' : 'Nascosta' ?></td><td class="p-4"><div class="flex justify-end gap-3"><a class="font-semibold text-blue-700" href="/admin/faq/items/<?= (int) $item['id'] ?>/edit">Modifica</a><form method="post" action="/admin/faq/items/<?= (int) $item['id'] ?>/delete" onsubmit="return confirm('Eliminare questa FAQ?');"><input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>"><button class="font-semibold text-red-700">Elimina</button></form></div></td></tr><?php endforeach; ?>
		</tbody></table></div><?php endif; ?>
	</section>
</div>

