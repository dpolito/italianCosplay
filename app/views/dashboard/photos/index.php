<?php
$h = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
	<div>
		<h1 class="text-2xl font-bold text-gray-900">Le mie foto</h1>
		<p class="mt-1 text-sm text-gray-600">Carica le foto degli eventi cosplay e gestiscile da qui, evento per evento.</p>
	</div>
	<?php if ($groups): ?>
		<a href="/dashboard/photos/upload" class="inline-flex items-center justify-center rounded-lg bg-green-800 px-4 py-2 font-semibold text-white hover:bg-green-700">Carica foto</a>
	<?php endif; ?>
</header>

<section class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-5 text-blue-950">
	<h2 class="font-bold">Archivio foto eventi</h2>
	<p class="mt-2 text-sm leading-6">
		Qui trovi le fotografie che hai caricato su ItalianCosplay, organizzate per evento. Da ogni gruppo puoi aprire la gestione per associare cosplayer, indicare i cosplay presenti, aggiungere nuove foto o rimuovere immagini caricate per errore.
	</p>
</section>

<section class="mt-6 space-y-3">
	<?php foreach ($groups as $group): ?>
		<article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
			<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
				<div>
					<h2 class="text-lg font-bold text-gray-900"><?= $h($group['titolo']) ?></h2>
					<p class="text-sm text-gray-600">
						<?= (int) $group['photo_count'] ?> foto
						<?php if (!empty($group['comune_nome'])): ?> · <?= $h($group['comune_nome']) ?><?php endif; ?>
					</p>
					<?php if (!empty($group['is_pending_submission'])): ?>
						<p class="mt-2 inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-900">
							<?= ($group['submission_type'] ?? '') === 'edition' ? 'Edizione in verifica' : 'Evento in verifica' ?>
						</p>
						<p class="mt-2 text-sm text-gray-600">Le foto saranno pubblicate quando l'evento o l'edizione verranno verificati.</p>
					<?php endif; ?>
				</div>
				<div class="flex flex-wrap gap-2">
					<?php if (empty($group['is_pending_submission'])): ?>
						<a class="rounded-lg border border-green-800 px-3 py-2 text-sm font-semibold text-green-900 hover:bg-green-50" href="/eventi-cosplay/<?= $h($group['slug']) ?>/foto">Gallery pubblica</a>
						<a class="rounded-lg bg-green-800 px-3 py-2 text-sm font-semibold text-white hover:bg-green-700" href="/dashboard/photos/event/<?= (int) $group['event_id'] ?>">Gestisci</a>
					<?php else: ?>
						<a class="rounded-lg bg-green-800 px-3 py-2 text-sm font-semibold text-white hover:bg-green-700" href="/dashboard/photos/upload">Aggiungi foto</a>
					<?php endif; ?>
				</div>
			</div>
		</article>
	<?php endforeach; ?>
	<?php if (!$groups): ?>
		<div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 sm:p-8">
			<div class="mx-auto max-w-2xl text-center">
				<p class="text-lg font-bold text-gray-900">Non hai ancora caricato foto.</p>
				<p class="mt-3 text-sm leading-6 text-gray-600">
					Seleziona un evento, scegli le immagini dal telefono o dal computer e il sistema le caricherà una alla volta, convertendole in WebP e creando automaticamente le miniature.
				</p>
				<div class="mt-5 grid gap-3 text-left text-sm text-gray-700 sm:grid-cols-3">
					<div class="rounded-lg bg-white p-3 ring-1 ring-gray-200">
						<span class="block font-bold text-green-900">1. Scegli evento</span>
						Cerca Romics, Lucca Comics o qualunque evento già presente.
					</div>
					<div class="rounded-lg bg-white p-3 ring-1 ring-gray-200">
						<span class="block font-bold text-green-900">2. Seleziona foto</span>
						Puoi caricare molte foto senza inviarle tutte nella stessa richiesta.
					</div>
					<div class="rounded-lg bg-white p-3 ring-1 ring-gray-200">
						<span class="block font-bold text-green-900">3. Gestisci</span>
						Dopo l'upload potrai associare cosplayer e cosplay anche in blocco.
					</div>
				</div>
				<a href="/dashboard/photos/upload" class="mt-6 inline-flex rounded-lg bg-green-800 px-5 py-3 font-semibold text-white hover:bg-green-700">Carica le prime foto</a>
			</div>
		</div>
	<?php endif; ?>
</section>
