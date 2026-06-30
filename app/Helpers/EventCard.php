<?php

namespace App\Helpers;

use function date;
use function htmlspecialchars;
use function strtotime;use function var_dump;

class EventCard
{
public static function render(array $event, string $variant = 'default', bool $lazy = false): string
{
$img = $event['immagine']
? URL_ROOT_SITE . htmlspecialchars($event['immagine'])
: 'https://placehold.co/800x500';

$immagineWidth = htmlspecialchars($event['immagine_width']);
$immagineHeight = htmlspecialchars($event['immagine_height']);

$link = URL_ROOT_SITE . '/eventi-cosplay/' . htmlspecialchars($event['slug']);

//$title = htmlspecialchars($event['titolo'] . ' evento cosplay e giochi da tavolo a ' .$event['comune_nome']);
$title = htmlspecialchars($event['titolo']. ' '.date('Y', strtotime($event['data_inizio'])). ' evento cosplay e fumetti a ' .$event['comune_nome']. ' ' . $event['regione_nome']);
$titolo = htmlspecialchars($event['titolo']) . ' '.date('Y', strtotime($event['data_inizio'])). ' a ' .$event['comune_nome'];
$place = 'Evento cosplay e fumetti in ' . htmlspecialchars( $event['regione_nome']);
$date = date('d/m/Y', strtotime($event['data_inizio']));

if ($event['data_fine'] && $event['data_fine'] != $event['data_inizio']):
	$date .= ' - '. date('d/m/Y', strtotime($event['data_fine']));
endif;



$desc = mb_strimwidth(strip_tags($event['descrizione'] ?? ''), 0, 140, '...');

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
			<p class='text-sm text-gray-600 mt-2'>{$desc}</p>
		</div>
	</article>
</a>";
}
}
