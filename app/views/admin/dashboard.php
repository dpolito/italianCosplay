<?php
// Questo file è ora un frammento di HTML e deve essere incluso in un layout.
// Non contiene i tag <html>, <head>, <body> completi.
// Le risorse CSS (Tailwind) e i tag base devono essere nel layout che lo include.
$favoriteAnalytics = $data['favoriteAnalytics'] ?? [];
$agendaAnalytics = $data['agendaAnalytics'] ?? [];
$engagementAnalytics = $data['engagementAnalytics'] ?? [];
$userRegistrationAnalytics = $data['userRegistrationAnalytics'] ?? [];
$dashboardIntervals = $data['dashboardIntervals'] ?? [7, 30, 90, 180, 365];
$favoriteCounts = $favoriteAnalytics['counts'] ?? [];
$favoriteTrend = $favoriteAnalytics['trend'] ?? [];
$agendaCounts = $agendaAnalytics['counts'] ?? [];
$agendaTrend = $agendaAnalytics['trend'] ?? [];
$topEvents = $engagementAnalytics['topEvents'] ?? [];
$eventOpportunities = $engagementAnalytics['eventOpportunities'] ?? [];
$topBlogPosts = $engagementAnalytics['topBlogPosts'] ?? [];

if (!function_exists('admin_dashboard_label')) {
	function admin_dashboard_label(?string $value): string
	{
		return [
			'event' => 'Eventi',
			'blog_post' => 'Blog',
			'guest' => 'Ospiti',
			'regione' => 'Regioni',
			'provincia' => 'Province',
			'comune' => 'Comuni',
			'mi_interessa' => 'Mi interessa',
			'ci_vado' => 'Ci vado',
			'forse_vado' => 'Forse vado',
			'add' => 'Aggiunti',
			'remove' => 'Rimossi',
			'set' => 'Impostati',
		][$value ?? ''] ?? ucfirst(str_replace('_', ' ', (string) $value));
	}
}

if (!function_exists('admin_dashboard_favorite_rows')) {
	function admin_dashboard_favorite_rows(array $rows): array
	{
		$grouped = [];
		foreach ($rows as $row) {
			$type = (string) ($row['entity_type'] ?? '');
			$action = (string) ($row['action'] ?? '');
			if ($type === '' || $action === '') {
				continue;
			}
			$grouped[$type][$action] = (int) ($row['total'] ?? 0);
		}

		$result = [];
		foreach ($grouped as $type => $actions) {
			$adds = (int) ($actions['add'] ?? 0);
			$removes = (int) ($actions['remove'] ?? 0);
			$result[] = [
				'label' => admin_dashboard_label($type),
				'adds' => $adds,
				'removes' => $removes,
				'total' => $adds + $removes,
			];
		}

		usort($result, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);
		return $result;
	}
}

if (!function_exists('admin_dashboard_agenda_rows')) {
	function admin_dashboard_agenda_rows(array $rows): array
	{
		$grouped = [];
		foreach ($rows as $row) {
			$action = (string) ($row['action'] ?? '');
			$status = $row['status'] !== null ? (string) $row['status'] : 'remove';
			if ($action === '') {
				continue;
			}
			$grouped[$status][$action] = (int) ($row['total'] ?? 0);
		}

		$result = [];
		foreach ($grouped as $status => $actions) {
			$sets = (int) ($actions['set'] ?? 0);
			$removes = (int) ($actions['remove'] ?? 0);
			$result[] = [
				'label' => $status === 'remove' ? 'Rimozioni' : admin_dashboard_label($status),
				'sets' => $sets,
				'removes' => $removes,
				'total' => $sets + $removes,
			];
		}

		usort($result, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);
		return $result;
	}
}

if (!function_exists('admin_dashboard_daily_totals')) {
	function admin_dashboard_daily_totals(array $favoriteTrend, array $agendaTrend): array
	{
		$days = [];
		foreach ($favoriteTrend as $row) {
			$date = (string) ($row['event_date'] ?? '');
			if ($date !== '') {
				$days[$date]['favorites'] = ($days[$date]['favorites'] ?? 0) + (int) ($row['total'] ?? 0);
			}
		}
		foreach ($agendaTrend as $row) {
			$date = (string) ($row['event_date'] ?? '');
			if ($date !== '') {
				$days[$date]['agenda'] = ($days[$date]['agenda'] ?? 0) + (int) ($row['total'] ?? 0);
			}
		}

		ksort($days);
		return array_slice($days, -14, 14, true);
	}
}

if (!function_exists('admin_dashboard_metric_rows')) {
	function admin_dashboard_metric_rows(array $rows, string $type): void
	{
		if (empty($rows)) {
			echo '<p class="text-sm text-gray-600">Nessun dato disponibile. Verifica migration analytics e tracking view.</p>';
			return;
		}

		echo '<div class="overflow-x-auto">';
		echo '<table class="min-w-full divide-y divide-gray-200 text-sm">';
		echo '<thead><tr class="text-left text-xs font-bold uppercase tracking-wide text-gray-500">';
		echo '<th class="py-2 pr-3">Contenuto</th>';
		echo '<th class="px-3 py-2 text-right">View 30gg</th>';
		echo '<th class="px-3 py-2 text-right">Preferiti</th>';
		if ($type === 'event') {
			echo '<th class="px-3 py-2 text-right">Agenda</th>';
		}
		echo '<th class="py-2 pl-3 text-right">Conv.</th>';
		echo '</tr></thead><tbody class="divide-y divide-gray-100">';

		foreach ($rows as $row) {
			$title = htmlspecialchars((string) ($row['titolo'] ?? 'Senza titolo'), ENT_QUOTES, 'UTF-8');
			$views = number_format((int) ($row['views'] ?? 0), 0, ',', '.');
			$favorites = number_format((int) ($row['favorites'] ?? 0), 0, ',', '.');
			$agenda = number_format((int) ($row['agenda_actions'] ?? 0), 0, ',', '.');
			$conversion = number_format((float) ($row['conversion_rate'] ?? 0), 2, ',', '.');
			echo '<tr>';
			echo '<td class="max-w-xs py-3 pr-3 font-semibold text-gray-800">' . $title . '</td>';
			echo '<td class="px-3 py-3 text-right text-gray-700">' . $views . '</td>';
			echo '<td class="px-3 py-3 text-right text-gray-700">' . $favorites . '</td>';
			if ($type === 'event') {
				echo '<td class="px-3 py-3 text-right text-gray-700">' . $agenda . '</td>';
			}
			echo '<td class="py-3 pl-3 text-right font-bold text-gray-900">' . $conversion . '%</td>';
			echo '</tr>';
		}

		echo '</tbody></table></div>';
	}
}

$favoriteRows = admin_dashboard_favorite_rows($favoriteCounts);
$agendaRows = admin_dashboard_agenda_rows($agendaCounts);
$dailyRows = admin_dashboard_daily_totals($favoriteTrend, $agendaTrend);
$maxFavoriteTotal = max(array_column($favoriteRows ?: [['total' => 0]], 'total'));
$maxAgendaTotal = max(array_column($agendaRows ?: [['total' => 0]], 'total'));
$maxDailyTotal = 0;
foreach ($dailyRows as $day) {
	$maxDailyTotal = max($maxDailyTotal, (int) ($day['favorites'] ?? 0), (int) ($day['agenda'] ?? 0));
}
$registrationTotal = (int) ($userRegistrationAnalytics['total'] ?? 0);
$registrationActivated = (int) ($userRegistrationAnalytics['activated'] ?? 0);
$registrationNotActivated = (int) ($userRegistrationAnalytics['not_activated'] ?? 0);
$registrationActivationRate = (float) ($userRegistrationAnalytics['activation_rate'] ?? 0);
$registrationDays = (int) ($userRegistrationAnalytics['days'] ?? 30);
$registrationActivatedWidth = $registrationTotal > 0 ? round(($registrationActivated / $registrationTotal) * 100) : 0;
$registrationNotActivatedWidth = $registrationTotal > 0 ? round(($registrationNotActivated / $registrationTotal) * 100) : 0;
?>

<main class="flex-grow container mx-auto p-6">
	<div class="bg-white rounded-lg shadow-lg p-8 mb-6">
		<h2 class="text-3xl font-semibold text-gray-800 mb-4">Benvenuto, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>!</h2>
		<p class="text-gray-600">Da qui puoi gestire le diverse sezioni del tuo sito.</p>
	</div>

	<?php
	// Visualizza i messaggi flash
	if (isset($_SESSION['flash_messages'])) {
		foreach ($_SESSION['flash_messages'] as $type => $message) {
			echo '<div class="flash-message ' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
		}
		unset($_SESSION['flash_messages']); // Pulisci i messaggi flash dopo averli visualizzati
	}
	?>

	<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
		<!-- Card Gestione Utenti -->
		<div class="bg-white rounded-lg shadow-lg p-6 flex flex-col items-center text-center">
			<div class="text-green-500 mb-4">
				<!-- Icona di esempio per gli utenti (potresti usare Font Awesome o Lucide React) -->
				<svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-3-3H5a3 3 0 00-3 3v2h5m0 0l3-3m-3 3l-3-3m3 3v-2.5M17 11V9a2 2 0 00-2-2H9a2 2 0 00-2 2v2m9 6h-6v-3h6v3z" />
				</svg>
			</div>
			<h3 class="text-xl font-semibold text-gray-700 mb-3">Gestione Utenti</h3>
			<p class="text-gray-600 mb-4">Visualizza, crea, modifica ed elimina gli utenti del sistema.</p>
			<a href="/admin/users" class="mt-auto bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out">Vai a Gestione Utenti</a>
		</div>

		<!-- Card Gestione Eventi -->
		<div class="bg-white rounded-lg shadow-lg p-6 flex flex-col items-center text-center">
			<div class="text-green-500 mb-4">
				<!-- Icona di esempio per gli eventi -->
				<svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
				</svg>
			</div>
			<h3 class="text-xl font-semibold text-gray-700 mb-3">Gestione Eventi</h3>
			<p class="text-gray-600 mb-4">Approva, modifica ed elimina gli eventi segnalati dagli utenti.</p>
			<a href="/admin/events/pending" class="mt-auto bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out">Vai a Gestione Eventi</a>
		</div>

		<!-- Card Altre Funzionalità (Esempio) -->
		<div class="bg-white rounded-lg shadow-lg p-6 flex flex-col items-center text-center">
			<div class="text-green-500 mb-4">
				<!-- Icona di esempio per altre funzionalità -->
				<svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
					<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
				</svg>
			</div>
			<h3 class="text-xl font-semibold text-gray-700 mb-3">Impostazioni</h3>
			<p class="text-gray-600 mb-4">Configura le impostazioni generali del sito e altre opzioni.</p>
			<a href="/admin/setup" class="mt-auto bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out">Vai a Impostazioni</a>
		</div>

		<?php if (!empty((new \App\Services\SiteFeatureFlagService())->getEnabledMap()['enable_advertising'] ?? false)): ?>
		<div class="bg-white rounded-lg shadow-lg p-6 flex flex-col items-center text-center">
			<div class="text-green-500 mb-4">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h10M4 17h16" />
				</svg>
			</div>
			<h3 class="text-xl font-semibold text-gray-700 mb-3">Advertising</h3>
			<p class="text-gray-600 mb-4">Gestisci posizioni, campagne e approvazioni pubblicitarie.</p>
			<a href="/admin/ads/campaigns" class="mt-auto bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out">Vai ad Advertising</a>
		</div>
		<?php endif; ?>
	</div>

	<?php if (!empty((new \App\Services\SiteFeatureFlagService())->getEnabledMap()['enable_advertising'] ?? false)): ?>
	<div class="mt-6 bg-white rounded-lg shadow-lg p-6">
		<div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
			<div>
				<p class="text-sm font-semibold uppercase tracking-wide text-green-700">Advertising</p>
				<h3 class="mt-1 text-2xl font-semibold text-gray-800">Panoramica monetizzazione</h3>
				<p class="mt-2 text-gray-600">Campagne, posizioni e performance commerciali in un unico colpo d'occhio.</p>
			</div>
			<div class="flex flex-wrap gap-3">
				<a href="/admin/ads/campaigns" class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 transition duration-300 ease-in-out">Campagne</a>
				<a href="/admin/ads/positions" class="inline-flex items-center px-4 py-2 bg-blue-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">Posizioni</a>
			</div>
		</div>

		<?php $adStats = $data['adStats'] ?? []; ?>
		<div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
			<div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
				<p class="text-sm font-semibold text-gray-500">Campagne totali</p>
				<p class="mt-2 text-3xl font-bold text-gray-900"><?php echo (int)($adStats['campaigns'] ?? 0); ?></p>
			</div>
			<div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
				<p class="text-sm font-semibold text-gray-500">Campagne attive</p>
				<p class="mt-2 text-3xl font-bold text-gray-900"><?php echo (int)($adStats['active_campaigns'] ?? 0); ?></p>
			</div>
			<div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
				<p class="text-sm font-semibold text-gray-500">Impression</p>
				<p class="mt-2 text-3xl font-bold text-gray-900"><?php echo number_format((int)($adStats['impressions'] ?? 0), 0, ',', '.'); ?></p>
			</div>
			<div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
				<p class="text-sm font-semibold text-gray-500">CTR medio</p>
				<p class="mt-2 text-3xl font-bold text-gray-900"><?php echo number_format((float)($adStats['ctr'] ?? 0), 2, ',', '.'); ?>%</p>
			</div>
		</div>

		<div class="mt-4 grid gap-4 md:grid-cols-3">
			<div class="rounded-xl border border-gray-200 bg-white p-4">
				<p class="text-sm font-semibold text-gray-500">Posizioni attive</p>
				<p class="mt-2 text-2xl font-bold text-gray-900"><?php echo (int)($adStats['positions'] ?? 0); ?></p>
			</div>
			<div class="rounded-xl border border-gray-200 bg-white p-4">
				<p class="text-sm font-semibold text-gray-500">Click</p>
				<p class="mt-2 text-2xl font-bold text-gray-900"><?php echo number_format((int)($adStats['clicks'] ?? 0), 0, ',', '.'); ?></p>
			</div>
			<div class="rounded-xl border border-gray-200 bg-white p-4">
				<p class="text-sm font-semibold text-gray-500">Ricavi stimati</p>
				<p class="mt-2 text-2xl font-bold text-gray-900"><?php echo number_format((float)($adStats['revenue'] ?? 0), 2, ',', '.'); ?> €</p>
			</div>
		</div>
	</div>
	<?php endif; ?>
	<section class="mt-6 rounded-lg bg-white p-6 shadow-lg" aria-labelledby="admin-growth-title">
		<div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
			<div>
				<p class="text-sm font-semibold uppercase tracking-wide text-green-700">Crescita utenti</p>
				<h3 id="admin-growth-title" class="mt-1 text-2xl font-semibold text-gray-800">Segui e agenda personale</h3>
				<p class="mt-2 text-gray-600">Azioni realmente utili per capire cosa spinge iscrizione, ritorno e interesse sugli eventi.</p>
			</div>
			<p class="text-sm font-semibold text-gray-500">Ultimi dati disponibili</p>
		</div>

		<div class="mt-6 grid gap-6 xl:grid-cols-4">
			<div class="rounded-lg border border-gray-200 bg-gray-50 p-5">
				<div class="flex items-center justify-between gap-3">
					<h4 class="font-bold text-gray-900">Segui / preferiti per tipo</h4>
					<span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-800">add/remove</span>
				</div>
				<div class="mt-5 space-y-4">
					<?php if (empty($favoriteRows)): ?>
						<p class="text-sm text-gray-600">Nessun dato ancora disponibile. Applica la migration e attendi le prime azioni utente.</p>
					<?php else: ?>
						<?php foreach ($favoriteRows as $row): ?>
							<?php $width = $maxFavoriteTotal > 0 ? max(4, round(((int) $row['total'] / $maxFavoriteTotal) * 100)) : 0; ?>
							<div>
								<div class="mb-1 flex items-center justify-between gap-3 text-sm">
									<span class="font-semibold text-gray-800"><?php echo htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?></span>
									<span class="text-gray-600"><?php echo (int) $row['adds']; ?> aggiunti · <?php echo (int) $row['removes']; ?> rimossi</span>
								</div>
								<div class="h-3 overflow-hidden rounded-full bg-white">
									<div class="h-full rounded-full bg-green-700" style="width: <?php echo (int) $width; ?>%"></div>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="rounded-lg border border-gray-200 bg-gray-50 p-5">
				<div class="flex items-center justify-between gap-3">
					<h4 class="font-bold text-gray-900">Agenda per intenzione</h4>
					<span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-800">eventi</span>
				</div>
				<div class="mt-5 space-y-4">
					<?php if (empty($agendaRows)): ?>
						<p class="text-sm text-gray-600">Nessun dato agenda ancora disponibile. Le azioni saranno tracciate dopo la migration.</p>
					<?php else: ?>
						<?php foreach ($agendaRows as $row): ?>
							<?php $width = $maxAgendaTotal > 0 ? max(4, round(((int) $row['total'] / $maxAgendaTotal) * 100)) : 0; ?>
							<div>
								<div class="mb-1 flex items-center justify-between gap-3 text-sm">
									<span class="font-semibold text-gray-800"><?php echo htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?></span>
									<span class="text-gray-600"><?php echo (int) $row['sets']; ?> scelte · <?php echo (int) $row['removes']; ?> rimosse</span>
								</div>
								<div class="h-3 overflow-hidden rounded-full bg-white">
									<div class="h-full rounded-full bg-blue-700" style="width: <?php echo (int) $width; ?>%"></div>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="rounded-lg border border-gray-200 bg-gray-50 p-5">
				<div class="flex items-center justify-between gap-3">
					<h4 class="font-bold text-gray-900">Trend ultimi 14 giorni</h4>
					<span class="rounded-full bg-gray-200 px-3 py-1 text-xs font-bold text-gray-700">azioni</span>
				</div>
				<div class="mt-5 space-y-3">
					<?php if (empty($dailyRows)): ?>
						<p class="text-sm text-gray-600">Il trend comparirà appena ci saranno azioni tracciate.</p>
					<?php else: ?>
						<?php foreach ($dailyRows as $date => $row): ?>
							<?php
								$favoriteWidth = $maxDailyTotal > 0 ? round(((int) ($row['favorites'] ?? 0) / $maxDailyTotal) * 100) : 0;
								$agendaWidth = $maxDailyTotal > 0 ? round(((int) ($row['agenda'] ?? 0) / $maxDailyTotal) * 100) : 0;
							?>
							<div class="grid grid-cols-[5rem_1fr] items-center gap-3 text-xs">
								<span class="font-semibold text-gray-600"><?php echo htmlspecialchars(date('d/m', strtotime($date)), ENT_QUOTES, 'UTF-8'); ?></span>
								<div class="space-y-1">
									<div class="h-2 overflow-hidden rounded-full bg-white">
										<div class="h-full rounded-full bg-green-700" style="width: <?php echo (int) $favoriteWidth; ?>%"></div>
									</div>
									<div class="h-2 overflow-hidden rounded-full bg-white">
										<div class="h-full rounded-full bg-blue-700" style="width: <?php echo (int) $agendaWidth; ?>%"></div>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
						<div class="mt-4 flex gap-4 text-xs font-semibold text-gray-600">
							<span><span class="mr-1 inline-block h-2 w-4 rounded bg-green-700"></span>Segui</span>
							<span><span class="mr-1 inline-block h-2 w-4 rounded bg-blue-700"></span>Agenda</span>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<div class="rounded-lg border border-gray-200 bg-gray-50 p-5">
				<div class="flex items-start justify-between gap-3">
					<div>
						<h4 class="font-bold text-gray-900">Registrazioni utenti</h4>
						<p class="mt-1 text-xs font-semibold uppercase tracking-wide text-gray-500">Ultimi <?php echo (int) $registrationDays; ?> giorni</p>
					</div>
					<form method="get">
						<label for="registration_days" class="sr-only">Intervallo registrazioni</label>
						<select id="registration_days" name="registration_days" class="rounded-lg border border-gray-300 bg-white px-2 py-1 text-xs font-bold text-gray-700" onchange="this.form.submit()">
							<?php foreach ($dashboardIntervals as $interval): ?>
								<option value="<?php echo (int) $interval; ?>" <?php echo (int) $interval === $registrationDays ? 'selected' : ''; ?>>
									<?php echo (int) $interval; ?>g
								</option>
							<?php endforeach; ?>
						</select>
					</form>
				</div>

				<div class="mt-5">
					<p class="text-sm font-semibold text-gray-500">Nuovi iscritti</p>
					<p class="mt-1 text-4xl font-extrabold text-gray-950"><?php echo number_format($registrationTotal, 0, ',', '.'); ?></p>
					<p class="mt-2 text-sm font-semibold text-gray-700">Attivazione: <?php echo number_format($registrationActivationRate, 2, ',', '.'); ?>%</p>
				</div>

				<div class="mt-5 space-y-4">
					<div>
						<div class="mb-1 flex items-center justify-between gap-3 text-sm">
							<span class="font-semibold text-gray-800">Attivati</span>
							<span class="text-gray-600"><?php echo number_format($registrationActivated, 0, ',', '.'); ?></span>
						</div>
						<div class="h-3 overflow-hidden rounded-full bg-white">
							<div class="h-full rounded-full bg-green-700" style="width: <?php echo (int) $registrationActivatedWidth; ?>%"></div>
						</div>
					</div>
					<div>
						<div class="mb-1 flex items-center justify-between gap-3 text-sm">
							<span class="font-semibold text-gray-800">Non attivati</span>
							<span class="text-gray-600"><?php echo number_format($registrationNotActivated, 0, ',', '.'); ?></span>
						</div>
						<div class="h-3 overflow-hidden rounded-full bg-white">
							<div class="h-full rounded-full bg-amber-600" style="width: <?php echo (int) $registrationNotActivatedWidth; ?>%"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="mt-6 rounded-lg bg-white p-6 shadow-lg" aria-labelledby="admin-content-performance-title">
		<div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
			<div>
				<p class="text-sm font-semibold uppercase tracking-wide text-green-700">Performance contenuti</p>
				<h3 id="admin-content-performance-title" class="mt-1 text-2xl font-semibold text-gray-800">View collegate alle azioni</h3>
				<p class="mt-2 text-gray-600">Le view servono di più quando mostrano quali contenuti generano salvataggi, agenda e intenzione reale.</p>
			</div>
			<p class="text-sm font-semibold text-gray-500">Finestra: 30 giorni</p>
		</div>

		<div class="mt-6 grid gap-6 xl:grid-cols-2">
			<div class="rounded-lg border border-gray-200 bg-gray-50 p-5">
				<h4 class="font-bold text-gray-900">Top eventi per interesse reale</h4>
				<p class="mt-1 text-sm text-gray-600">Ordinati per preferiti + azioni agenda, con conversione sulle view.</p>
				<div class="mt-4">
					<?php admin_dashboard_metric_rows($topEvents, 'event'); ?>
				</div>
			</div>

			<div class="rounded-lg border border-gray-200 bg-gray-50 p-5">
				<h4 class="font-bold text-gray-900">Eventi da migliorare</h4>
				<p class="mt-1 text-sm text-gray-600">Molte view, poche azioni: schede da rendere più convincenti o più complete.</p>
				<div class="mt-4">
					<?php admin_dashboard_metric_rows($eventOpportunities, 'event'); ?>
				</div>
			</div>

			<div class="rounded-lg border border-gray-200 bg-gray-50 p-5 xl:col-span-2">
				<h4 class="font-bold text-gray-900">Blog che fidelizza</h4>
				<p class="mt-1 text-sm text-gray-600">Articoli letti e salvati: buoni candidati per aggiornamenti, internal linking e contenuti correlati.</p>
				<div class="mt-4">
					<?php admin_dashboard_metric_rows($topBlogPosts, 'blog'); ?>
				</div>
			</div>
		</div>
	</section>
</main>
