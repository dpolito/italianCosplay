<?php
$yearEvents = $data['yearEvents'] ?? [];
$upcomingEvents = $data['upcomingEvents'] ?? [];
$counts = $data['counts'] ?? [];
$selectedYear = (int) ($data['selectedYear'] ?? date('Y'));
$availableYears = $data['availableYears'] ?? [$selectedYear];
$totalAgendaCount = (int) ($data['totalAgendaCount'] ?? 0);

$statusLabels = [
	'mi_interessa' => 'Mi interessa',
	'ci_vado' => 'Ci vado',
	'forse_vado' => 'Forse vado',
];

$statusClasses = [
	'mi_interessa' => 'border-blue-200 bg-blue-50 text-blue-800',
	'ci_vado' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
	'forse_vado' => 'border-amber-200 bg-amber-50 text-amber-900',
];

$months = [
	1 => 'Gennaio',
	2 => 'Febbraio',
	3 => 'Marzo',
	4 => 'Aprile',
	5 => 'Maggio',
	6 => 'Giugno',
	7 => 'Luglio',
	8 => 'Agosto',
	9 => 'Settembre',
	10 => 'Ottobre',
	11 => 'Novembre',
	12 => 'Dicembre',
];

$eventsByMonth = array_fill_keys(array_keys($months), []);
foreach ($yearEvents as $event) {
	$month = !empty($event['data_inizio']) ? (int) date('n', strtotime((string) $event['data_inizio'])) : 0;
	if ($month >= 1 && $month <= 12) {
		$eventsByMonth[$month][] = $event;
	}
}

$yearTotal = count($yearEvents);
$currentYear = (int) date('Y');
$previousYear = $selectedYear - 1;
$nextYear = $selectedYear + 1;

if (!function_exists('dashboard_events_date_label')) {
	function dashboard_events_date_label(?string $startDate, ?string $endDate = null): string
	{
		if (empty($startDate)) {
			return 'Data da confermare';
		}

		$startTimestamp = strtotime($startDate);
		$endTimestamp = !empty($endDate) ? strtotime($endDate) : null;

		if ($endTimestamp && date('Y-m-d', $startTimestamp) !== date('Y-m-d', $endTimestamp)) {
			if (date('mY', $startTimestamp) === date('mY', $endTimestamp)) {
				return date('j', $startTimestamp) . '-' . date('j/m/Y', $endTimestamp);
			}

			return date('j/m/Y', $startTimestamp) . ' - ' . date('j/m/Y', $endTimestamp);
		}

		return date('j/m/Y', $startTimestamp);
	}
}

if (!function_exists('dashboard_events_location_label')) {
	function dashboard_events_location_label(array $event): string
	{
		$parts = array_filter([
			$event['comune_nome'] ?? '',
			$event['provincia_nome'] ?? '',
			$event['regione_nome'] ?? '',
		]);

		return $parts ? implode(' · ', $parts) : (string) ($event['luogo'] ?? 'Località da confermare');
	}
}

if (!function_exists('dashboard_events_image_url')) {
	function dashboard_events_image_url(array $event): string
	{
		$image = trim((string) ($event['immagine'] ?? ''));

		return $image !== '' ? '/' . ltrim($image, '/') : '/public_assets/images/default_cover.png';
	}
}

if (!function_exists('dashboard_events_days_until')) {
	function dashboard_events_days_until(?string $startDate): string
	{
		if (empty($startDate)) {
			return 'Data da confermare';
		}

		$today = new DateTimeImmutable('today');
		$start = new DateTimeImmutable($startDate);
		$days = (int) $today->diff($start)->format('%r%a');

		if ($days === 0) {
			return 'Oggi';
		}

		if ($days === 1) {
			return 'Domani';
		}

		return 'Tra ' . $days . ' giorni';
	}
}

if (!function_exists('dashboard_events_render_card')) {
	function dashboard_events_render_card(array $event, array $statusLabels, array $statusClasses): void
	{
		$status = (string) ($event['status'] ?? 'mi_interessa');
		$statusLabel = $statusLabels[$status] ?? 'In agenda';
		$statusClass = $statusClasses[$status] ?? 'border-gray-200 bg-gray-50 text-gray-800';
		$eventId = (int) ($event['event_id'] ?? 0);
		$title = (string) ($event['titolo'] ?? 'Evento cosplay');
		$slug = (string) ($event['slug'] ?? '');
		?>
		<article class="js-agenda-card overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" data-event-id="<?php echo $eventId; ?>">
			<a href="/eventi-cosplay/<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>" class="block">
				<img
					src="<?php echo htmlspecialchars(dashboard_events_image_url($event), ENT_QUOTES, 'UTF-8'); ?>"
					alt="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
					class="h-28 w-full object-cover"
					loading="lazy"
				>
			</a>
			<div class="space-y-3 p-3">
				<div>
					<a href="/eventi-cosplay/<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>" class="text-sm font-bold leading-5 text-gray-950 hover:text-blue-800">
						<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
					</a>
					<p class="mt-1 text-xs font-semibold text-gray-700">
						<?php echo htmlspecialchars(dashboard_events_date_label($event['data_inizio'] ?? null, $event['data_fine'] ?? null), ENT_QUOTES, 'UTF-8'); ?>
					</p>
					<p class="mt-1 text-xs text-gray-600">
						<?php echo htmlspecialchars(dashboard_events_location_label($event), ENT_QUOTES, 'UTF-8'); ?>
					</p>
				</div>

				<div class="flex flex-wrap items-center gap-2">
					<span data-agenda-badge class="rounded-full border px-2.5 py-1 text-xs font-bold <?php echo htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>">
						<?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
					</span>
					<a href="/eventi-cosplay/<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>" class="text-xs font-bold text-blue-800 hover:underline">Scheda evento</a>
				</div>

				<div class="grid grid-cols-2 gap-2">
					<?php foreach ($statusLabels as $optionStatus => $optionLabel): ?>
						<form method="post" action="/eventi-cosplay/agenda/update" class="js-agenda-toggle">
							<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
							<input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
							<input type="hidden" name="status" value="<?php echo htmlspecialchars($optionStatus, ENT_QUOTES, 'UTF-8'); ?>">
							<input type="hidden" name="redirect_to" value="/dashboard/events?year=<?php echo (int) date('Y', strtotime((string) ($event['data_inizio'] ?? 'now'))); ?>">
							<button type="submit" class="js-agenda-button w-full rounded-lg border px-2 py-2 text-xs font-bold transition <?php echo $status === $optionStatus ? 'border-green-700 bg-green-700 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-blue-50'; ?>" data-agenda-status="<?php echo htmlspecialchars($optionStatus, ENT_QUOTES, 'UTF-8'); ?>" data-active="<?php echo $status === $optionStatus ? '1' : '0'; ?>">
								<?php echo htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8'); ?>
							</button>
						</form>
					<?php endforeach; ?>
					<form method="post" action="/eventi-cosplay/agenda/update" class="js-agenda-toggle" data-remove-card="1">
						<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
						<input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
						<input type="hidden" name="status" value="remove">
						<input type="hidden" name="redirect_to" value="/dashboard/events">
						<button type="submit" class="js-agenda-button w-full rounded-lg border border-red-200 bg-red-50 px-2 py-2 text-xs font-bold text-red-800 transition hover:bg-red-100" data-agenda-status="remove" data-active="0">
							Rimuovi
						</button>
					</form>
				</div>
			</div>
		</article>
		<?php
	}
}
?>

<section class="mx-auto max-w-7xl space-y-6" aria-labelledby="agenda-page-title" data-agenda-page>
	<header class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
		<div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-blue-800">Agenda personale</p>
				<h1 id="agenda-page-title" class="mt-2 text-3xl font-extrabold text-gray-950 md:text-4xl">La mia Agenda Cosplay</h1>
				<p class="mt-3 max-w-2xl text-base leading-relaxed text-gray-700">
					Tutti gli eventi che hai salvato, organizzati durante l'anno.
				</p>
			</div>
			<div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
				<p class="text-sm font-bold text-blue-950"><?php echo $selectedYear; ?></p>
				<p class="mt-1 text-2xl font-extrabold text-blue-950" data-agenda-year-total><?php echo $yearTotal; ?> eventi salvati</p>
				<div class="mt-3 flex flex-wrap gap-2 text-xs font-bold">
					<span class="rounded-full bg-white px-3 py-1 text-blue-800"><?php echo (int) ($counts['mi_interessa'] ?? 0); ?> Mi interessano</span>
					<span class="rounded-full bg-white px-3 py-1 text-emerald-800"><?php echo (int) ($counts['ci_vado'] ?? 0); ?> Ci vado</span>
					<span class="rounded-full bg-white px-3 py-1 text-amber-900"><?php echo (int) ($counts['forse_vado'] ?? 0); ?> Forse vado</span>
				</div>
			</div>
		</div>

		<div class="mt-6 flex flex-col gap-4 border-t border-gray-100 pt-5 md:flex-row md:items-center md:justify-between">
			<nav class="flex flex-wrap items-center gap-2 text-sm font-bold" aria-label="Navigazione anno agenda">
				<a href="/dashboard/events?year=<?php echo $previousYear; ?>" class="rounded-lg border border-gray-300 px-3 py-2 text-gray-700 hover:bg-gray-50">&larr; <?php echo $previousYear; ?></a>
				<span class="rounded-lg bg-gray-950 px-4 py-2 text-white"><?php echo $selectedYear; ?></span>
				<a href="/dashboard/events?year=<?php echo $nextYear; ?>" class="rounded-lg border border-gray-300 px-3 py-2 text-gray-700 hover:bg-gray-50"><?php echo $nextYear; ?> &rarr;</a>
			</nav>
			<form method="get" action="/dashboard/events" class="flex items-center gap-2">
				<label for="agenda-year" class="text-sm font-semibold text-gray-700">Anno</label>
				<select id="agenda-year" name="year" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900" onchange="this.form.submit()">
					<?php foreach ($availableYears as $year): ?>
						<option value="<?php echo (int) $year; ?>" <?php echo (int) $year === $selectedYear ? 'selected' : ''; ?>><?php echo (int) $year; ?></option>
					<?php endforeach; ?>
				</select>
			</form>
		</div>
	</header>

	<?php if ($totalAgendaCount === 0): ?>
		<section class="rounded-2xl bg-white p-6 text-center shadow-sm md:p-10">
			<h2 class="text-2xl font-extrabold text-gray-950">La tua Agenda Cosplay è ancora vuota.</h2>
			<p class="mx-auto mt-3 max-w-xl text-gray-700">Scopri gli eventi cosplay e salva quelli che non vuoi perdere.</p>
			<a href="/eventi-cosplay" class="mt-6 inline-flex rounded-lg bg-blue-700 px-5 py-3 text-sm font-bold text-white hover:bg-blue-800">Scopri gli eventi</a>
		</section>
	<?php else: ?>
		<div class="rounded-2xl bg-white p-2 shadow-sm">
			<div class="grid grid-cols-2 gap-2" role="tablist" aria-label="Modalità agenda">
				<button type="button" class="js-agenda-tab rounded-xl bg-blue-700 px-4 py-3 text-sm font-bold text-white" data-target="year" role="tab" aria-selected="true">Anno</button>
				<button type="button" class="js-agenda-tab rounded-xl px-4 py-3 text-sm font-bold text-gray-700 hover:bg-gray-50" data-target="upcoming" role="tab" aria-selected="false">Prossimi eventi</button>
			</div>
		</div>

		<section data-agenda-panel="year" class="space-y-4">
			<?php if ($yearTotal === 0): ?>
				<div class="rounded-2xl bg-white p-6 text-center shadow-sm">
					<h2 class="text-xl font-bold text-gray-950">Nessun evento salvato nel <?php echo $selectedYear; ?>.</h2>
					<p class="mt-2 text-gray-700">Hai eventi in altri anni: usa la navigazione sopra per consultarli.</p>
				</div>
			<?php else: ?>
				<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
					<?php foreach ($months as $monthNumber => $monthName): ?>
						<?php $monthEvents = $eventsByMonth[$monthNumber] ?? []; ?>
						<section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm" aria-labelledby="month-<?php echo $monthNumber; ?>">
							<div class="flex items-baseline justify-between gap-3">
								<h2 id="month-<?php echo $monthNumber; ?>" class="text-sm font-extrabold uppercase tracking-wide text-gray-950"><?php echo htmlspecialchars($monthName, ENT_QUOTES, 'UTF-8'); ?></h2>
								<span class="text-xs font-bold text-gray-500"><?php echo count($monthEvents); ?> eventi</span>
							</div>
							<?php if (empty($monthEvents)): ?>
								<p class="mt-4 rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-sm text-gray-500">Nessun evento salvato</p>
							<?php else: ?>
								<div class="mt-4 space-y-3">
									<?php foreach ($monthEvents as $event): ?>
										<?php dashboard_events_render_card($event, $statusLabels, $statusClasses); ?>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</section>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>

		<section data-agenda-panel="upcoming" class="hidden rounded-2xl bg-white p-5 shadow-sm md:p-8">
			<div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
				<div>
					<p class="text-sm font-bold uppercase tracking-wide text-blue-800">Agenda</p>
					<h2 class="mt-1 text-2xl font-extrabold text-gray-950">I tuoi prossimi eventi</h2>
				</div>
				<a href="/eventi-cosplay" class="text-sm font-bold text-blue-800 hover:underline">Scopri altri eventi</a>
			</div>
			<?php if (empty($upcomingEvents)): ?>
				<p class="mt-6 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-gray-600">Non hai eventi futuri salvati.</p>
			<?php else: ?>
				<div class="mt-6 space-y-5">
					<?php foreach ($upcomingEvents as $event): ?>
						<div class="grid gap-3 border-t border-gray-100 pt-5 md:grid-cols-[9rem_1fr]">
							<p class="text-sm font-extrabold uppercase tracking-wide text-blue-800"><?php echo htmlspecialchars(dashboard_events_days_until($event['data_inizio'] ?? null), ENT_QUOTES, 'UTF-8'); ?></p>
							<?php dashboard_events_render_card($event, $statusLabels, $statusClasses); ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
	<?php endif; ?>
</section>

<script>
	document.addEventListener('DOMContentLoaded', () => {
		const page = document.querySelector('[data-agenda-page]');
		if (!page) {
			return;
		}

		const showToast = (message, type = 'success') => {
			let toast = document.getElementById('dashboard-agenda-toast');
			if (!toast) {
				toast = document.createElement('div');
				toast.id = 'dashboard-agenda-toast';
				document.body.appendChild(toast);
			}

			toast.textContent = message;
			toast.className = `fixed right-4 top-4 z-50 rounded-xl px-4 py-3 text-sm font-semibold shadow-lg ${type === 'success' ? 'bg-green-900 text-white' : 'bg-red-900 text-white'}`;
			clearTimeout(window.__dashboardAgendaToastTimer);
			window.__dashboardAgendaToastTimer = setTimeout(() => toast.remove(), 2200);
		};

		page.querySelectorAll('.js-agenda-tab').forEach((button) => {
			button.addEventListener('click', () => {
				const target = button.dataset.target;
				page.querySelectorAll('.js-agenda-tab').forEach((tab) => {
					const active = tab === button;
					tab.setAttribute('aria-selected', active ? 'true' : 'false');
					tab.classList.toggle('bg-blue-700', active);
					tab.classList.toggle('text-white', active);
					tab.classList.toggle('text-gray-700', !active);
					tab.classList.toggle('hover:bg-gray-50', !active);
				});
				page.querySelectorAll('[data-agenda-panel]').forEach((panel) => {
					panel.classList.toggle('hidden', panel.dataset.agendaPanel !== target);
				});
			});
		});

		page.addEventListener('submit', async (event) => {
			const form = event.target;
			if (!(form instanceof HTMLFormElement) || !form.classList.contains('js-agenda-toggle')) {
				return;
			}

			event.preventDefault();
			const button = form.querySelector('.js-agenda-button');
			if (button) {
				button.disabled = true;
			}

			try {
				const response = await fetch(form.action, {
					method: 'POST',
					headers: {
						'X-Requested-With': 'XMLHttpRequest',
						'Accept': 'application/json',
					},
					body: new FormData(form),
				});
				const result = await response.json();
				if (!response.ok || !result.success) {
					throw new Error(result.message || 'Impossibile aggiornare l’agenda.');
				}

				const card = form.closest('.js-agenda-card');
				const status = form.querySelector('input[name="status"]')?.value || '';
				if (status === 'remove') {
					card?.remove();
					showToast(result.message || 'Evento rimosso dall’agenda.');
					return;
				}

				const labels = {
					mi_interessa: { label: 'Mi interessa', className: 'rounded-full border px-2.5 py-1 text-xs font-bold border-blue-200 bg-blue-50 text-blue-800' },
					ci_vado: { label: 'Ci vado', className: 'rounded-full border px-2.5 py-1 text-xs font-bold border-emerald-200 bg-emerald-50 text-emerald-800' },
					forse_vado: { label: 'Forse vado', className: 'rounded-full border px-2.5 py-1 text-xs font-bold border-amber-200 bg-amber-50 text-amber-900' },
				};

				card?.querySelectorAll('.js-agenda-button').forEach((item) => {
					const active = item.dataset.agendaStatus === status;
					item.classList.toggle('border-green-700', active);
					item.classList.toggle('bg-green-700', active);
					item.classList.toggle('text-white', active);
					item.classList.toggle('border-gray-200', !active);
					item.classList.toggle('bg-white', !active);
					item.classList.toggle('text-gray-700', !active);
					item.dataset.active = active ? '1' : '0';
					item.disabled = false;
				});

				const badge = card?.querySelector('[data-agenda-badge]');
				if (badge && labels[status]) {
					badge.textContent = labels[status].label;
					badge.className = labels[status].className;
				}

				showToast(result.message || 'Agenda aggiornata.');
			} catch (error) {
				showToast(error.message || 'Impossibile aggiornare l’agenda.', 'error');
			} finally {
				if (button) {
					button.disabled = false;
				}
			}
		});
	});
</script>
