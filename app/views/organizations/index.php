<?php
$organizations = $data['organizations'] ?? [];
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$assetUrl = static function (?string $path): string {
    if (!$path) return '';
    return preg_match('/^https?:\\/\\//i', $path) ? $path : '/public_assets/' . ltrim($path, '/');
};
?>
<main class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 md:px-6">
        <nav class="mb-5 text-sm text-gray-700" aria-label="Breadcrumb">
            <ol class="flex flex-wrap items-center gap-2">
                <li><a href="<?= $h(URL_ROOT_SITE . '/') ?>" class="font-medium text-green-900 hover:underline">Home</a></li>
                <li class="flex items-center gap-2"><span class="text-gray-400" aria-hidden="true">/</span><span>Organizzazioni</span></li>
            </ol>
        </nav>
        <header class="rounded-2xl bg-white p-6 shadow-md md:p-10">
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-green-900">Community e organizzatori</p>
            <h1 class="mt-2 text-3xl font-extrabold text-gray-950 md:text-5xl">Organizzazioni cosplay in Italia</h1>
            <p class="mt-4 max-w-3xl text-lg leading-relaxed text-gray-700">Scopri le organizzazioni che curano eventi master, fiere e appuntamenti cosplay pubblicati su ItalianCosplay.</p>
        </header>
        <?php if (!$organizations): ?><div class="mt-8 rounded-2xl bg-white p-8 text-center text-gray-600 shadow-md">Non ci sono ancora organizzazioni pubbliche disponibili.</div><?php else: ?><section class="mt-8 grid gap-6 md:grid-cols-2 xl:grid-cols-3" aria-label="Organizzazioni pubbliche"><?php foreach ($organizations as $organization): ?><a href="/organizzazioni/<?= rawurlencode((string) $organization['slug']) ?>" class="group block rounded-[2rem] bg-white shadow-md transition hover:-translate-y-1 hover:shadow-xl"><?php $cover = $assetUrl($organization['cover_path'] ?? null); $logo = $assetUrl($organization['logo_path'] ?? null); $description = trim(preg_replace('/\\s+/', ' ', strip_tags((string) ($organization['description'] ?? '')))); $description = mb_strlen($description) > 250 ? mb_substr($description, 0, 250) . '…' : $description; ?><?php if ($cover): ?><div class="h-40 overflow-hidden rounded-t-[2rem] bg-slate-900 md:h-48"><img src="<?= $h($cover) ?>" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-105"></div><?php else: ?><div class="h-40 rounded-t-[2rem] bg-gradient-to-br from-green-900 to-slate-950 md:h-48"></div><?php endif; ?><div class="px-5 pb-12 md:px-6 md:pb-16"><?php if ($logo): ?><img src="<?= $h($logo) ?>" alt="Logo <?= $h($organization['name']) ?>" class="relative z-10 h-24 w-24 rounded-2xl border-4 border-white bg-white object-cover shadow-lg md:h-28 md:w-28" style="margin-top: -3rem;"><?php endif; ?><h2 class="mt-4 text-2xl font-extrabold text-gray-950 group-hover:text-green-800"><?= $h($organization['name']) ?></h2><?php if ($description !== ''): ?><p class="mt-3 text-base leading-relaxed text-gray-600"><?= $h($description) ?></p><?php endif; ?><span class="mt-5 inline-block text-base font-bold text-green-900">Scopri l’organizzazione <span aria-hidden="true">→</span></span></div></a><?php endforeach; ?></section><?php endif; ?>
    </div>
</main>
