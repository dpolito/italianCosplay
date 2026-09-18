<?php

$variant = $variant ?? 'card';
$context = $context ?? 'default';
$channelUrl = defined('TELEGRAM_CHANNEL_URL') ? trim((string) TELEGRAM_CHANNEL_URL) : '';

if ($channelUrl === '' && defined('TELEGRAM_CHANNEL_CHAT_ID')) {
	$channelId = trim((string) TELEGRAM_CHANNEL_CHAT_ID);
	if (str_starts_with($channelId, '@')) {
		$channelUrl = 'https://t.me/' . ltrim($channelId, '@');
	}
}

if ($channelUrl === '') {
	return;
}

$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$copy = [
	'default' => [
		'title' => 'Seguici su Telegram',
		'body' => 'Ricevi aggiornamenti sugli eventi cosplay, guide pratiche e riepiloghi del weekend.',
		'button' => 'Apri il canale',
	],
	'weekend' => [
		'title' => 'Eventi del weekend anche su Telegram',
		'body' => 'Ogni settimana pubblichiamo una selezione rapida degli appuntamenti cosplay da salvare e consultare al volo.',
		'button' => 'Iscriviti al canale',
	],
	'blog' => [
		'title' => 'Vuoi altri aggiornamenti cosplay?',
		'body' => 'Segui il canale Telegram per guide, promemoria e novità sugli eventi in Italia.',
		'button' => 'Segui su Telegram',
	],
];

$text = $copy[$context] ?? $copy['default'];
?>

<?php if ($variant === 'footer'): ?>
	<a href="<?= $h($channelUrl) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 text-white underline-offset-4 hover:underline">
		<i class="fa-brands fa-telegram" aria-hidden="true"></i>
		<span>Telegram</span>
	</a>
<?php else: ?>
	<section class="rounded-xl border border-green-100 bg-green-50 p-5 shadow-sm" aria-label="<?= $h($text['title']) ?>">
		<div class="flex flex-col gap-4">
			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-green-900">Canale Telegram</p>
				<h2 class="mt-1 text-xl font-extrabold text-gray-950"><?= $h($text['title']) ?></h2>
				<p class="mt-2 text-sm leading-6 text-gray-700"><?= $h($text['body']) ?></p>
			</div>
			<a href="<?= $h($channelUrl) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-green-800 px-4 py-3 text-center font-bold text-white shadow-sm hover:bg-green-900">
				<i class="fa-brands fa-telegram" aria-hidden="true"></i>
				<span><?= $h($text['button']) ?></span>
			</a>
		</div>
	</section>
<?php endif; ?>
