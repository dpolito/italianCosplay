<?php
$blocked = !empty($blocked);
?>

<section class="mx-auto max-w-2xl px-4 py-16 text-center">
	<h1 class="text-3xl font-extrabold text-gray-950">Preferenze inviti aggiornate</h1>
	<p class="mt-4 text-gray-700">
		<?php if ($blocked): ?>
			Non riceverai altri inviti personali da ItalianCosplay a questo indirizzo.
		<?php else: ?>
			Il link non è valido o è già stato utilizzato.
		<?php endif; ?>
	</p>
	<a href="/" class="mt-8 inline-flex rounded-lg bg-green-800 px-5 py-3 text-sm font-bold text-white hover:bg-green-900">Torna a ItalianCosplay</a>
</section>
