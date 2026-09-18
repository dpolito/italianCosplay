<?php
$campaign = $campaign ?? [];
$stats = $stats ?? [];
$regioni = $regioni ?? [];
$province = $province ?? [];
$daily = $stats['daily'] ?? ['impressions' => [], 'clicks' => []];
$italianMonths = [
	'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno',
	'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre',
];
$formatDailyDate = static function (string $day) use ($italianMonths): string {
	$date = DateTime::createFromFormat('Y-m-d', $day);
	if (!$date) {
		return $day;
	}

	return $date->format('d') . ' ' . $italianMonths[(int)$date->format('n') - 1] . ' ' . $date->format('Y');
};
$dailyByDay = [];
foreach (($daily['impressions'] ?? []) as $row) {
	$day = (string)($row['day'] ?? '');
	if ($day !== '') {
		$dailyByDay[$day]['impressions'] = (int)($row['impressions'] ?? 0);
	}
}
foreach (($daily['clicks'] ?? []) as $row) {
	$day = (string)($row['day'] ?? '');
	if ($day !== '') {
		$dailyByDay[$day]['clicks'] = (int)($row['clicks'] ?? 0);
	}
}
ksort($dailyByDay);
$chartDays = array_keys($dailyByDay);
$chartMax = 1;
foreach ($dailyByDay as $dayData) {
	$chartMax = max($chartMax, (int)($dayData['impressions'] ?? 0), (int)($dayData['clicks'] ?? 0));
}
$chartWidth = 800;
$chartHeight = 280;
$chartPaddingLeft = 42;
$chartPaddingRight = 20;
$chartPaddingTop = 20;
$chartPaddingBottom = 42;
$chartPlotWidth = $chartWidth - $chartPaddingLeft - $chartPaddingRight;
$chartPlotHeight = $chartHeight - $chartPaddingTop - $chartPaddingBottom;
$impressionPoints = [];
$clickPoints = [];
$impressionMarkers = [];
$clickMarkers = [];
$chartLabels = [];
foreach ($chartDays as $index => $day) {
	$x = $chartPaddingLeft + (count($chartDays) === 1 ? $chartPlotWidth / 2 : ($index / (count($chartDays) - 1)) * $chartPlotWidth);
	$impressionY = $chartPaddingTop + $chartPlotHeight - (((int)($dailyByDay[$day]['impressions'] ?? 0) / $chartMax) * $chartPlotHeight);
	$clickY = $chartPaddingTop + $chartPlotHeight - (((int)($dailyByDay[$day]['clicks'] ?? 0) / $chartMax) * $chartPlotHeight);
	$impressionPoints[] = round($x, 2) . ',' . round($impressionY, 2);
	$clickPoints[] = round($x, 2) . ',' . round($clickY, 2);
	$impressionMarkers[] = [
		'x' => round($x, 2),
		'y' => round($impressionY, 2),
		'value' => (int)($dailyByDay[$day]['impressions'] ?? 0),
	];
	$clickMarkers[] = [
		'x' => round($x, 2),
		'y' => round($clickY, 2),
		'value' => (int)($dailyByDay[$day]['clicks'] ?? 0),
	];
	$date = DateTime::createFromFormat('Y-m-d', $day);
	$chartLabels[] = [
		'x' => round($x, 2),
		'label' => $date
			? $date->format('d') . ' ' . substr($italianMonths[(int)$date->format('n') - 1], 0, 3)
			: $day,
	];
}

$regionNames = [];
foreach ($regioni as $regione) {
	$regionNames[(string)($regione['slug'] ?? '')] = (string)($regione['nome'] ?? '');
}

$provinceNames = [];
foreach ($province as $provincia) {
	$provinceNames[(string)($provincia['slug'] ?? '')] = (string)($provincia['nome'] ?? '');
}

if (($campaign['target_type'] ?? 'national') === 'region') {
	$campaignTargetLabel = 'Regione';
} elseif (($campaign['target_type'] ?? 'national') === 'province') {
	$campaignTargetLabel = 'Provincia';
} else {
	$campaignTargetLabel = 'Nazionale';
}
$campaignTargetValueRaw = !empty($campaign['target_value']) ? (string)$campaign['target_value'] : '';
$campaignTargetValue = $campaignTargetValueRaw !== ''
	? (($campaign['target_type'] ?? 'national') === 'region'
		? ($regionNames[$campaignTargetValueRaw] ?? $campaignTargetValueRaw)
		: (($campaign['target_type'] ?? 'national') === 'province'
			? ($provinceNames[$campaignTargetValueRaw] ?? $campaignTargetValueRaw)
			: $campaignTargetValueRaw))
	: '-';
?>
<section class="space-y-6">
	<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
		<div>
			<p class="text-sm font-bold uppercase tracking-wide text-green-900">Advertising</p>
			<h1 class="text-3xl font-bold text-gray-950"><?php echo htmlspecialchars($campaign['banner_title'] ?? 'Statistiche campagna', ENT_QUOTES, 'UTF-8'); ?></h1>
			<p class="mt-1 text-gray-600"><?php echo htmlspecialchars($campaign['position_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
			<p class="mt-1 text-sm font-semibold text-gray-700">Target: <?php echo htmlspecialchars($campaignTargetLabel, ENT_QUOTES, 'UTF-8'); ?><?php echo $campaignTargetValue !== '-' ? ' · ' . htmlspecialchars($campaignTargetValue, ENT_QUOTES, 'UTF-8') : ''; ?></p>
		</div>
		<div class="flex flex-wrap gap-3">
			<a href="/dashboard/ads/campaigns" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-800 hover:bg-gray-50">Tutte le campagne</a>
			<a href="/dashboard/ads/campaigns/<?php echo (int)($campaign['id'] ?? 0); ?>/review" class="rounded-lg bg-green-700 px-4 py-2 text-sm font-bold text-white hover:bg-green-800">Dettagli campagna</a>
		</div>
	</div>

	<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
		<div class="rounded-2xl bg-white p-5 shadow-sm">
			<p class="text-sm font-semibold text-gray-500">Impression</p>
			<p class="mt-2 text-3xl font-bold text-gray-950"><?php echo number_format((int)($stats['impressions'] ?? 0), 0, ',', '.'); ?></p>
		</div>
		<div class="rounded-2xl bg-white p-5 shadow-sm">
			<p class="text-sm font-semibold text-gray-500">Click</p>
			<p class="mt-2 text-3xl font-bold text-gray-950"><?php echo number_format((int)($stats['clicks'] ?? 0), 0, ',', '.'); ?></p>
		</div>
		<div class="rounded-2xl bg-white p-5 shadow-sm">
			<p class="text-sm font-semibold text-gray-500">CTR</p>
			<p class="mt-2 text-3xl font-bold text-gray-950"><?php echo number_format((float)($stats['ctr'] ?? 0), 2, ',', '.'); ?>%</p>
		</div>
		<div class="rounded-2xl bg-white p-5 shadow-sm">
			<p class="text-sm font-semibold text-gray-500">Periodo</p>
			<p class="mt-2 text-lg font-bold text-gray-950"><?php echo !empty($campaign['start_date']) ? date('d/m/Y', strtotime($campaign['start_date'])) : '-'; ?> - <?php echo !empty($campaign['end_date']) ? date('d/m/Y', strtotime($campaign['end_date'])) : '-'; ?></p>
		</div>
	</div>

	<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
		<h2 class="text-lg font-bold text-gray-950">Target campagna</h2>
		<dl class="mt-4 grid gap-4 md:grid-cols-3">
			<div>
				<dt class="text-sm font-semibold text-gray-500">Tipo</dt>
				<dd class="text-gray-950"><?php echo htmlspecialchars($campaignTargetLabel, ENT_QUOTES, 'UTF-8'); ?></dd>
			</div>
			<div>
				<dt class="text-sm font-semibold text-gray-500">Valore</dt>
				<dd class="text-gray-950"><?php echo htmlspecialchars($campaignTargetValue, ENT_QUOTES, 'UTF-8'); ?></dd>
			</div>
			<div>
				<dt class="text-sm font-semibold text-gray-500">Pagina</dt>
				<dd class="text-gray-950"><?php echo htmlspecialchars($campaign['position_page'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></dd>
			</div>
		</dl>
	</div>

	<div class="rounded-2xl bg-white p-5 shadow-sm">
		<h2 class="text-xl font-bold text-gray-950">Andamento giornaliero</h2>
		<div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 p-3 sm:p-5">
			<div class="mb-4 flex flex-wrap items-center justify-between gap-3 text-slate-900">
				<div>
					<p class="text-sm font-semibold text-slate-700">Performance della campagna</p>
					<p class="mt-1 text-xs text-slate-500">Confronto giornaliero tra visibilità e interazioni</p>
				</div>
				<div class="flex gap-4 text-xs font-semibold">
					<span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full" style="background-color:#10b981"></span>Impression</span>
					<span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full" style="background-color:#ea580c"></span>Click</span>
				</div>
			</div>
			<?php if (empty($chartDays)): ?>
				<div class="flex min-h-[230px] items-center justify-center rounded-xl border border-dashed border-slate-300 px-5 text-center text-sm text-slate-500">Il grafico sarà disponibile quando la campagna inizierà a ricevere dati.</div>
			<?php else: ?>
				<svg viewBox="0 0 <?php echo $chartWidth; ?> <?php echo $chartHeight; ?>" class="h-auto w-full" role="img" aria-label="Grafico andamento giornaliero di impression e click">
					<?php for ($gridStep = 0; $gridStep <= 4; $gridStep++): ?>
						<?php $gridValue = (int)round($chartMax * ($gridStep / 4)); ?>
						<?php $gridY = $chartPaddingTop + $chartPlotHeight - (($gridValue / $chartMax) * $chartPlotHeight); ?>
						<line x1="<?php echo $chartPaddingLeft; ?>" y1="<?php echo round($gridY, 2); ?>" x2="<?php echo $chartWidth - $chartPaddingRight; ?>" y2="<?php echo round($gridY, 2); ?>" stroke="#cbd5e1" stroke-width="1"></line>
						<text x="<?php echo $chartPaddingLeft - 8; ?>" y="<?php echo round($gridY + 4, 2); ?>" fill="#64748b" font-size="11" text-anchor="end"><?php echo $gridValue; ?></text>
					<?php endfor; ?>
					<polyline points="<?php echo htmlspecialchars(implode(' ', $impressionPoints), ENT_QUOTES, 'UTF-8'); ?>" fill="none" stroke="#10b981" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></polyline>
					<polyline points="<?php echo htmlspecialchars(implode(' ', $clickPoints), ENT_QUOTES, 'UTF-8'); ?>" fill="none" stroke="#ea580c" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></polyline>
					<?php foreach ($impressionMarkers as $marker): ?>
						<circle cx="<?php echo $marker['x']; ?>" cy="<?php echo $marker['y']; ?>" r="6" fill="#10b981" stroke="#ffffff" stroke-width="3">
							<title>Impression: <?php echo $marker['value']; ?></title>
						</circle>
					<?php endforeach; ?>
					<?php foreach ($clickMarkers as $marker): ?>
						<circle cx="<?php echo $marker['x']; ?>" cy="<?php echo $marker['y']; ?>" r="7" fill="#ea580c" stroke="#ffffff" stroke-width="3">
							<title>Click: <?php echo $marker['value']; ?></title>
						</circle>
					<?php endforeach; ?>
					<?php foreach ($chartLabels as $label): ?>
						<text x="<?php echo $label['x']; ?>" y="<?php echo $chartHeight - 14; ?>" fill="#94a3b8" font-size="11" text-anchor="middle"><?php echo htmlspecialchars($label['label'], ENT_QUOTES, 'UTF-8'); ?></text>
					<?php endforeach; ?>
				</svg>
			<?php endif; ?>
		</div>
		<div class="mt-4 grid gap-4 md:grid-cols-2">
			<div>
				<p class="text-sm font-semibold text-gray-500">Impression per giorno</p>
				<div class="mt-2 space-y-2 text-sm text-gray-700">
					<?php foreach (($daily['impressions'] ?? []) as $row): ?>
						<div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
							<span><?php echo htmlspecialchars($formatDailyDate((string)($row['day'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></span>
							<span class="font-bold"><?php echo (int)($row['impressions'] ?? 0); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<div>
				<p class="text-sm font-semibold text-gray-500">Click per giorno</p>
				<div class="mt-2 space-y-2 text-sm text-gray-700">
					<?php foreach (($daily['clicks'] ?? []) as $row): ?>
						<div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
							<span><?php echo htmlspecialchars($formatDailyDate((string)($row['day'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></span>
							<span class="font-bold"><?php echo (int)($row['clicks'] ?? 0); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
</section>
