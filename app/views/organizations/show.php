<?php
$organization = $data['organization'] ?? [];
$breadcrumbs = $data['breadcrumbs'] ?? [];
$canonicalUrl = $data['canonicalUrl'] ?? '';
$siteBaseUrl = rtrim(URL_ROOT_SITE, '/');
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$assetUrl = static function (?string $path) use ($siteBaseUrl): string {
    if (!$path) return '';
    return preg_match('/^https?:\\/\\//i', $path) ? $path : '/public_assets/' . ltrim($path, '/');
};
$plainDescription = trim(preg_replace('/\\s+/', ' ', strip_tags(html_entity_decode((string) ($organization['description'] ?? ''), ENT_QUOTES, 'UTF-8'))));
$shortDescription = mb_strimwidth($plainDescription, 0, 220, '...');
$publicDescription = strip_tags((string) ($organization['description'] ?? ''), '<p><br><strong><em><ul><ol><li><h2><h3>');
$logoUrl = $assetUrl($organization['logo_path'] ?? null);
$coverUrl = $assetUrl($organization['cover_path'] ?? null);
$organizationSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => $organization['name'] ?? '',
    'url' => $canonicalUrl,
    'description' => $shortDescription,
];
if ($logoUrl) $organizationSchema['logo'] = $logoUrl;
if ($organization['email'] ?? '') $organizationSchema['email'] = $organization['email'];
if ($organization['website_url'] ?? '') $organizationSchema['sameAs'][] = $organization['website_url'];
foreach (['facebook_url', 'instagram_url', 'tiktok_url', 'youtube_url'] as $social) {
    if (!empty($organization[$social])) $organizationSchema['sameAs'][] = $organization[$social];
}
?>
<script type="application/ld+json"><?php echo json_encode($organizationSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<main class="bg-gray-100">
    <div class="container mx-auto px-4 py-6 md:px-6">
        <nav class="mb-5 text-sm text-gray-700" aria-label="Breadcrumb"><ol class="flex flex-wrap items-center gap-2"><?php foreach ($breadcrumbs as $index => $crumb): ?><li class="flex items-center gap-2"><?php if ($index > 0): ?><span class="text-gray-400">/</span><?php endif; ?><a href="<?= $h($crumb['url']) ?>" class="font-medium text-green-900 hover:underline"><?= $h($crumb['label']) ?></a></li><?php endforeach; ?></ol></nav>
        <header class="rounded-2xl bg-white shadow-xl">
            <?php if ($coverUrl): ?><div class="h-64 overflow-hidden rounded-t-2xl bg-slate-950 md:h-96"><img src="<?= $h($coverUrl) ?>" alt="Cover di <?= $h($organization['name']) ?>" class="h-full w-full object-cover"></div><?php else: ?><div class="h-48 rounded-t-2xl bg-gradient-to-br from-green-900 via-green-950 to-slate-950 md:h-64"></div><?php endif; ?>
            <div class="relative px-5 pb-6 md:px-8 md:pb-8">
                <?php if ($logoUrl): ?><img src="<?= $h($logoUrl) ?>" alt="Logo <?= $h($organization['name']) ?>" class="relative z-10 h-24 w-24 rounded-2xl border-4 border-white bg-white object-cover shadow-lg md:h-32 md:w-32" style="margin-top: -4rem;"><?php endif; ?>
                <div class="<?= $logoUrl ? 'mt-4' : 'pt-6' ?>"><p class="text-sm font-bold uppercase tracking-[0.2em] text-green-900">Organizzazione</p><h1 class="mt-2 text-3xl font-extrabold text-gray-950 md:text-5xl"><?= $h($organization['name']) ?></h1><?php if (!empty($organization['legal_name'])): ?><p class="mt-2 text-sm text-gray-500"><?= $h($organization['legal_name']) ?></p><?php endif; ?></div>
                <div class="mt-5 flex flex-wrap gap-3"><?php if (!empty($organization['website_url'])): ?><a href="<?= $h($organization['website_url']) ?>" target="_blank" rel="noopener noreferrer" class="rounded-lg bg-green-800 px-4 py-2 font-bold text-white hover:bg-green-900">Sito ufficiale</a><?php endif; ?><?php foreach (['facebook_url' => 'Facebook', 'instagram_url' => 'Instagram', 'tiktok_url' => 'TikTok', 'youtube_url' => 'YouTube'] as $field => $label): ?><?php if (!empty($organization[$field])): ?><a href="<?= $h($organization[$field]) ?>" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-green-200 px-4 py-2 text-sm font-bold text-green-900 hover:bg-green-50"><?= $label ?></a><?php endif; ?><?php endforeach; ?></div>
            </div>
        </header>
        <?php if ($publicDescription !== ''): ?><section class="mt-8 rounded-2xl bg-white p-6 shadow-md md:p-8"><h2 class="text-2xl font-bold text-gray-950">Chi è <?= $h($organization['name']) ?></h2><div class="prose mt-5 max-w-none text-gray-700"><?= $publicDescription ?></div></section><?php endif; ?>
        <section class="mt-8 rounded-2xl bg-white p-6 shadow-md md:p-8" aria-labelledby="organization-masters-title"><div class="flex flex-wrap items-center justify-between gap-3"><h2 id="organization-masters-title" class="text-2xl font-bold text-gray-950">Master ed eventi organizzati</h2><span class="rounded-full bg-green-100 px-3 py-1 text-sm font-bold text-green-900"><?= count($organization['masters'] ?? []) ?> master</span></div>
            <?php if (empty($organization['masters'])): ?><p class="mt-5 text-gray-600">Non sono ancora disponibili master pubblici collegati.</p><?php else: ?><div class="mt-6 space-y-6"><?php foreach ($organization['masters'] as $master): ?><article class="rounded-2xl border border-gray-200 p-5"><div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-sm font-bold uppercase tracking-wide text-green-900">Evento master</p><h3 class="mt-1 text-xl font-extrabold text-gray-950"><a href="/eventi-master/<?= rawurlencode((string) $master['slug']) ?>" class="hover:text-green-800 hover:underline"><?= $h($master['nome']) ?></a></h3></div><a href="/eventi-master/<?= rawurlencode((string) $master['slug']) ?>" class="text-sm font-bold text-green-900 hover:underline">Vedi master</a></div><?php if (!empty($master['events'])): ?><div class="mt-5 grid gap-3 md:grid-cols-2"><?php foreach ($master['events'] as $event): ?><a href="/eventi-cosplay/<?= rawurlencode((string) $event['slug']) ?>" class="rounded-xl bg-gray-50 p-4 transition hover:bg-green-50"><p class="text-sm font-bold text-green-900"><?= $h(!empty($event['year']) ? $event['year'] : (!empty($event['data_inizio']) ? date('Y', strtotime($event['data_inizio'])) : '')) ?></p><h4 class="mt-1 font-bold text-gray-950"><?= $h($event['titolo']) ?></h4><p class="mt-1 text-sm text-gray-600"><?= $h(trim(($event['comune_nome'] ?? '') . (!empty($event['provincia_nome']) ? ' · ' . $event['provincia_nome'] : '') . (!empty($event['regione_nome']) ? ' · ' . $event['regione_nome'] : ''))) ?></p></a><?php endforeach; ?></div><?php else: ?><p class="mt-4 text-sm text-gray-600">Nessuna edizione pubblica disponibile.</p><?php endif; ?></article><?php endforeach; ?></div><?php endif; ?>
        </section>
    </div>
</main>
