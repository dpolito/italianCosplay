<?php $policy = $policy ?? null; ?>
<?php
$rawContent = (string) ($policy['content'] ?? '');
$allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><a>';
$content = $rawContent !== '' ? strip_tags($rawContent, $allowedTags) : '';
?>

<section class="py-12 bg-gray-50">
	<div class="container mx-auto px-6">
		<div class="mb-6">
			<h1 class="text-3xl font-bold text-gray-800">Privacy Policy - ItalianCosplay.it</h1>
			<p class="mt-2 text-sm text-gray-600">Versione <?= htmlspecialchars((string) ($policy['version_number'] ?? '-')) ?><?= !empty($policy['published_at']) ? ' - Aggiornata il ' . htmlspecialchars(date('d/m/Y', strtotime((string) $policy['published_at']))) : '' ?></p>
		</div>

		<div class="rounded-2xl bg-white p-6 shadow-sm leading-7 text-gray-800">
			<?php if ($content !== ''): ?>
				<div class="prose max-w-none">
					<?= $content ?>
				</div>
			<?php else: ?>
				<p>Informativa non ancora disponibile.</p>
			<?php endif; ?>
		</div>
	</div>
</section>
