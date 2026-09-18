<?php
$user = $data['user'] ?? ($user ?? []);
$agenda = $data['agenda'] ?? [];
$groupedAgenda = $data['groupedAgenda'] ?? [];
$counts = $data['counts'] ?? [];

if (!function_exists('dashboard_events_date_label')) {
	function dashboard_events_date_label(?string $startDate, ?string $endDate = null): string
	{
		if (empty($startDate)) {
			return 'Data da confermare';
		}

		$label = date('d/m/Y', strtotime($startDate));
		if (!empty($endDate) && $endDate !== $startDate) {
			$label .= ' - ' . date('d/m/Y', strtotime($endDate));
		}
		return $label;
	}
}
?>

<section class="mx-auto max-w-7xl space-y-6" aria-labelledby="agenda-page-title">
	<header class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
		<div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-blue-800">Agenda personale</p>
				<h1 id="agenda-page-title" class="mt-2 text-3xl font-extrabold text-gray-950 md:text-4xl">I tuoi eventi segnati</h1>
				<p class="mt-3 max-w-2xl text-base leading-relaxed text-gray-700">
					Qui trovi gli eventi che hai salvato con uno stato personale. Puoi passare da "ci vado" a "mi interessa" o "forse vado" quando vuoi.
				</p>
			</div>
			<div class="flex flex-wrap gap-3">
				<a href="/eventi-cosplay" class="inline-flex rounded-lg bg-blue-700 px-4 py-2 text-sm font-bold text-white hover:bg-blue-800">Vai agli eventi</a>
				<a href="/dashboard" class="inline-flex rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-800 hover:bg-gray-50">Torna alla dashboard</a>
			</div>
		</div>
	</header>

	<div class="grid gap-4 md:grid-cols-3">
		<div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
			<p class="text-sm font-semibold text-blue-900">Mi interessa</p>
			<p class="mt-2 text-3xl font-bold text-blue-950"><?php echo (int)($counts['mi_interessa'] ?? 0); ?></p>
		</div>
		<div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
			<p class="text-sm font-semibold text-blue-900">Ci vado</p>
			<p class="mt-2 text-3xl font-bold text-blue-950"><?php echo (int)($counts['ci_vado'] ?? 0); ?></p>
		</div>
		<div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
			<p class="text-sm font-semibold text-blue-900">Forse vado</p>
			<p class="mt-2 text-3xl font-bold text-blue-950"><?php echo (int)($counts['forse_vado'] ?? 0); ?></p>
		</div>
	</div>

	<section class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
		<h2 class="text-xl font-bold text-gray-950">Eventi con stato “Ci vado”</h2>
		<?php $ciVado = $groupedAgenda['ci_vado'] ?? []; ?>
		<?php if (empty($ciVado)): ?>
			<div class="mt-4 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-gray-600">
				Non hai ancora segnato eventi con stato “Ci vado”.
			</div>
		<?php else: ?>
			<div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
				<?php foreach ($ciVado as $event): ?>
					<article class="rounded-2xl border border-gray-200 bg-gray-50 p-4 transition hover:bg-blue-50">
						<a href="/eventi-cosplay/<?php echo htmlspecialchars($event['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="block">
							<p class="text-sm font-semibold text-blue-900"><?php echo htmlspecialchars(dashboard_events_date_label($event['data_inizio'] ?? null, $event['data_fine'] ?? null), ENT_QUOTES, 'UTF-8'); ?></p>
							<h3 class="mt-2 text-lg font-bold text-gray-950"><?php echo htmlspecialchars($event['titolo'] ?? 'Evento', ENT_QUOTES, 'UTF-8'); ?></h3>
							<p class="mt-1 text-sm text-gray-700">
								<?php echo htmlspecialchars(trim(($event['comune_nome'] ?? '') . (!empty($event['provincia_nome']) ? ' · ' . $event['provincia_nome'] : '') . (!empty($event['regione_nome']) ? ' · ' . $event['regione_nome'] : '')), ENT_QUOTES, 'UTF-8'); ?>
							</p>
						</a>
						<div class="mt-4 rounded-xl border border-blue-200 bg-white p-3">
							<p class="text-xs font-bold uppercase tracking-wide text-blue-800">Cambia stato</p>
							<div class="mt-3 grid gap-2 sm:grid-cols-3">
								<?php foreach ([
									'mi_interessa' => 'Mi interessa',
									'ci_vado' => 'Ci vado',
									'forse_vado' => 'Forse vado',
								] as $status => $label): ?>
									<form method="post" action="/eventi-cosplay/agenda/update" class="js-agenda-toggle">
										<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
										<input type="hidden" name="event_id" value="<?php echo (int)($event['event_id'] ?? 0); ?>">
										<input type="hidden" name="status" value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>">
										<input type="hidden" name="redirect_to" value="/dashboard/events">
										<button type="submit" class="js-agenda-button w-full rounded-lg border px-3 py-2 text-xs font-bold transition <?php echo ($status === 'ci_vado') ? 'border-green-700 bg-green-700 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-blue-50'; ?>" data-agenda-status="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>" data-active="<?php echo ($status === 'ci_vado') ? '1' : '0'; ?>">
											<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
										</button>
									</form>
								<?php endforeach; ?>
							</div>
							<form method="post" action="/eventi-cosplay/agenda/update" class="js-agenda-toggle mt-3">
								<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
								<input type="hidden" name="event_id" value="<?php echo (int)($event['event_id'] ?? 0); ?>">
								<input type="hidden" name="status" value="remove">
								<input type="hidden" name="redirect_to" value="/dashboard/events">
								<button type="submit" class="js-agenda-button inline-flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-xs font-bold text-red-800 hover:bg-red-100" data-agenda-status="remove" data-active="0">
									<i class="fa-solid fa-trash-can" aria-hidden="true"></i>
									Rimuovi selezione
								</button>
							</form>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>

	<section class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
		<div class="grid gap-6 md:grid-cols-2">
			<div>
				<h2 class="text-xl font-bold text-gray-950">Mi interessa</h2>
				<?php $items = $groupedAgenda['mi_interessa'] ?? []; ?>
				<?php if (empty($items)): ?>
					<p class="mt-4 text-sm text-gray-600">Nessun evento segnato come “mi interessa”.</p>
				<?php else: ?>
					<div class="mt-4 space-y-3">
						<?php foreach ($items as $event): ?>
							<div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
								<a href="/eventi-cosplay/<?php echo htmlspecialchars($event['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="font-semibold text-blue-900 hover:underline"><?php echo htmlspecialchars($event['titolo'] ?? 'Evento', ENT_QUOTES, 'UTF-8'); ?></a>
								<div class="mt-3 flex flex-wrap gap-2">
									<?php foreach ([
										'ci_vado' => 'Ci vado',
										'forse_vado' => 'Forse vado',
										'mi_interessa' => 'Mi interessa',
									] as $status => $label): ?>
										<form method="post" action="/eventi-cosplay/agenda/update" class="js-agenda-toggle">
											<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
											<input type="hidden" name="event_id" value="<?php echo (int)($event['event_id'] ?? 0); ?>">
											<input type="hidden" name="status" value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>">
											<input type="hidden" name="redirect_to" value="/dashboard/events">
											<button type="submit" class="js-agenda-button rounded-full border px-3 py-2 text-xs font-bold transition <?php echo ($event['status'] ?? '') === $status ? 'border-green-700 bg-green-700 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-blue-50'; ?>" data-agenda-status="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>" data-active="<?php echo (($event['status'] ?? '') === $status) ? '1' : '0'; ?>">
												<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
											</button>
										</form>
									<?php endforeach; ?>
									<form method="post" action="/eventi-cosplay/agenda/update" class="js-agenda-toggle">
										<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
										<input type="hidden" name="event_id" value="<?php echo (int)($event['event_id'] ?? 0); ?>">
										<input type="hidden" name="status" value="remove">
										<input type="hidden" name="redirect_to" value="/dashboard/events">
										<button type="submit" class="js-agenda-button rounded-full border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-800 hover:bg-red-100" data-agenda-status="remove" data-active="0">
											Rimuovi
										</button>
									</form>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div>
				<h2 class="text-xl font-bold text-gray-950">Forse vado</h2>
				<?php $items = $groupedAgenda['forse_vado'] ?? []; ?>
				<?php if (empty($items)): ?>
					<p class="mt-4 text-sm text-gray-600">Nessun evento segnato come “forse vado”.</p>
				<?php else: ?>
					<div class="mt-4 space-y-3">
						<?php foreach ($items as $event): ?>
							<div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
								<a href="/eventi-cosplay/<?php echo htmlspecialchars($event['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="font-semibold text-blue-900 hover:underline"><?php echo htmlspecialchars($event['titolo'] ?? 'Evento', ENT_QUOTES, 'UTF-8'); ?></a>
								<div class="mt-3 flex flex-wrap gap-2">
									<?php foreach ([
										'ci_vado' => 'Ci vado',
										'forse_vado' => 'Forse vado',
										'mi_interessa' => 'Mi interessa',
									] as $status => $label): ?>
										<form method="post" action="/eventi-cosplay/agenda/update" class="js-agenda-toggle">
											<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
											<input type="hidden" name="event_id" value="<?php echo (int)($event['event_id'] ?? 0); ?>">
											<input type="hidden" name="status" value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>">
											<input type="hidden" name="redirect_to" value="/dashboard/events">
											<button type="submit" class="js-agenda-button rounded-full border px-3 py-2 text-xs font-bold transition <?php echo ($event['status'] ?? '') === $status ? 'border-green-700 bg-green-700 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-blue-50'; ?>" data-agenda-status="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>" data-active="<?php echo (($event['status'] ?? '') === $status) ? '1' : '0'; ?>">
												<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
											</button>
										</form>
									<?php endforeach; ?>
									<form method="post" action="/eventi-cosplay/agenda/update" class="js-agenda-toggle">
										<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
										<input type="hidden" name="event_id" value="<?php echo (int)($event['event_id'] ?? 0); ?>">
										<input type="hidden" name="status" value="remove">
										<input type="hidden" name="redirect_to" value="/dashboard/events">
										<button type="submit" class="js-agenda-button rounded-full border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-800 hover:bg-red-100" data-agenda-status="remove" data-active="0">
											Rimuovi
										</button>
									</form>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
</section>
