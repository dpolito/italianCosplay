<?php

namespace App\Helpers;

use function date;
use function htmlspecialchars;
use function strtotime;use function var_dump;

class EventCard
{
public static function render(array $event, string $variant = 'default', bool $lazy = false): string
{
$eventTitle = (string)($event['titolo'] ?? 'Evento cosplay');
$eventSlug = (string)($event['slug'] ?? '');
$eventStart = (string)($event['data_inizio'] ?? date('Y-m-d'));
$eventEnd = (string)($event['data_fine'] ?? '');
$eventCity = (string)($event['comune_nome'] ?? '');
$eventRegion = (string)($event['regione_nome'] ?? '');
$eventDescription = (string)($event['descrizione'] ?? '');
$imagePath = (string)($event['immagine'] ?? '');
$imageWidth = (string)($event['immagine_width'] ?? '1200');
$imageHeight = (string)($event['immagine_height'] ?? '800');

$img = $imagePath !== ''
? URL_ROOT_SITE . '/'.htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8')
: 'https://placehold.co/800x500';

$immagineWidth = htmlspecialchars($imageWidth, ENT_QUOTES, 'UTF-8');
$immagineHeight = htmlspecialchars($imageHeight, ENT_QUOTES, 'UTF-8');

$link = URL_ROOT_SITE . '/eventi-cosplay/' . htmlspecialchars($eventSlug, ENT_QUOTES, 'UTF-8');

//$title = htmlspecialchars($event['titolo'] . ' evento cosplay e giochi da tavolo a ' .$event['comune_nome']);
$title = htmlspecialchars($eventTitle . ' ' . date('Y', strtotime($eventStart)) . ' evento cosplay e fumetti a ' . $eventCity . ' ' . $eventRegion, ENT_QUOTES, 'UTF-8');
$titolo = htmlspecialchars($eventTitle, ENT_QUOTES, 'UTF-8') . ' ' . date('Y', strtotime($eventStart)) . ' a ' . htmlspecialchars($eventCity, ENT_QUOTES, 'UTF-8');
$place = 'Evento cosplay e fumetti in ' . htmlspecialchars($eventRegion, ENT_QUOTES, 'UTF-8');
$date = date('d/m/Y', strtotime($eventStart));

if ($eventEnd && $eventEnd != $eventStart):
	$date .= ' - '. date('d/m/Y', strtotime($eventEnd));
endif;



$desc = mb_strimwidth(strip_tags($eventDescription), 0, 140, '...');
if ($variant === 'new') {
	$desc = '';
}

$badge = '';
if ($variant === 'top') {
$badge = '<span class="absolute top-3 left-3 bg-amber-400 text-zinc-900 text-xs font-medium px-2 py-1 rounded">🔥 Top</span>';
}
if ($variant === 'new') {
$badge = '<span class="absolute top-3 left-3 bg-green-700 text-white text-xs font-medium px-2 py-1 rounded">🆕 New</span>';
}
$lazy_txt = 'loading="eager"';
if($lazy){
	$lazy_txt = 'loading="lazy"';
}

return "
<a href='{$link}' class='block'  title='{$title}'>
	<article class='relative bg-white rounded-2xl shadow-md overflow-hidden hover:shadow-xl transition'>
		<div class='relative'>
			<img src='{$img}' width='{$immagineWidth}' height='{$immagineHeight}' class='w-full h-72 md:h-80 object-cover' {$lazy_txt} alt='{$title}' title='{$title}' >
			{$badge}
		</div>

		<div class='p-4'>
			<h3 class='text-lg font-bold'>{$titolo}</h3>
			<p class='text-sm text-gray-600'>📍 {$place}</p>
			<p class='text-sm text-gray-500'>📅 {$date}</p>
			" . ($desc !== '' ? "<p class='text-sm text-gray-600 mt-2'>{$desc}</p>" : '') . "
		</div>
	</article>
</a>";
}
}
