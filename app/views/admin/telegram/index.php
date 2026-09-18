<?php

$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$messages = is_array($messages ?? null) ? $messages : [];
$isConfigured = (bool) ($isConfigured ?? false);

$monthParam = (string) ($_GET['month'] ?? '');
$currentMonth = DateTimeImmutable::createFromFormat('Y-m', $monthParam) ?: new DateTimeImmutable('first day of this month');
$currentMonth = $currentMonth->modify('first day of this month');
$previousMonth = $currentMonth->modify('-1 month')->format('Y-m');
$nextMonth = $currentMonth->modify('+1 month')->format('Y-m');
$monthStart = $currentMonth;
$monthEnd = $currentMonth->modify('last day of this month');
$calendarStart = $monthStart->modify('-' . ((int) $monthStart->format('N') - 1) . ' days');
$calendarEnd = $monthEnd->modify('+' . (7 - (int) $monthEnd->format('N')) . ' days');

$messagesByDate = [];
foreach ($messages as $message) {
	$scheduledAt = (string) ($message['scheduled_at'] ?? '');
	if ($scheduledAt === '') {
		continue;
	}
	$messagesByDate[substr($scheduledAt, 0, 10)][] = $message;
}

$statusClasses = [
	'scheduled' => 'bg-blue-100 text-blue-800 border-blue-200',
	'sent' => 'bg-green-100 text-green-800 border-green-200',
	'failed' => 'bg-red-100 text-red-800 border-red-200',
	'cancelled' => 'bg-gray-100 text-gray-700 border-gray-200',
];
$statusLabels = [
	'scheduled' => 'Programmato',
	'sent' => 'Inviato',
	'failed' => 'Fallito',
	'cancelled' => 'Annullato',
];
$monthFormatter = new IntlDateFormatter('it_IT', IntlDateFormatter::LONG, IntlDateFormatter::NONE, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, 'LLLL yyyy');

?>

<div class="container mx-auto max-w-7xl p-6">
	<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
		<div>
			<p class="text-sm font-semibold uppercase tracking-wide text-green-700">Telegram</p>
			<h1 class="text-3xl font-semibold text-gray-900">Calendario messaggi</h1>
			<p class="mt-2 text-gray-600">Controlla i post programmati, inviati, falliti o annullati per il canale Telegram.</p>
		</div>
		<a href="/admin/telegram/create" class="inline-flex items-center justify-center rounded-lg bg-green-700 px-5 py-3 font-semibold text-white shadow-sm hover:bg-green-800">
			Crea messaggio
		</a>
	</div>

	<?php if ($message = \App\Core\Session::getFlash('error')): ?>
		<div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= $h($message) ?></div>
	<?php endif; ?>
	<?php if ($message = \App\Core\Session::getFlash('success')): ?>
		<div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?= $h($message) ?></div>
	<?php endif; ?>

	<?php if (!$isConfigured): ?>
		<div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
			Telegram canale non risulta configurato. Imposta TELEGRAM_BOT_TOKEN e TELEGRAM_CHANNEL_CHAT_ID prima di usare la programmazione.
		</div>
	<?php endif; ?>

	<section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
		<div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
			<div>
				<h2 class="text-2xl font-bold capitalize text-gray-950"><?= $h($monthFormatter->format($currentMonth)) ?></h2>
				<p class="mt-1 text-sm text-gray-600">Vista mensile degli aggiornamenti Telegram.</p>
			</div>
			<div class="flex flex-wrap gap-2">
				<a href="/admin/telegram?month=<?= $h($previousMonth) ?>" class="rounded-lg border border-gray-200 bg-white px-4 py-2 font-semibold text-gray-800 hover:bg-gray-50">Mese precedente</a>
				<a href="/admin/telegram?month=<?= $h(date('Y-m')) ?>" class="rounded-lg border border-gray-200 bg-white px-4 py-2 font-semibold text-gray-800 hover:bg-gray-50">Oggi</a>
				<a href="/admin/telegram?month=<?= $h($nextMonth) ?>" class="rounded-lg border border-gray-200 bg-white px-4 py-2 font-semibold text-gray-800 hover:bg-gray-50">Mese successivo</a>
			</div>
		</div>

		<div class="overflow-x-auto">
			<div class="min-w-[980px]">
				<div class="grid border-l border-t border-gray-200 text-xs font-bold uppercase tracking-wide text-gray-500" style="grid-template-columns: repeat(7, minmax(0, 1fr));">
					<?php foreach (['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'] as $dayLabel): ?>
						<div class="border-b border-r border-gray-200 bg-gray-50 px-2 py-2"><?= $dayLabel ?></div>
					<?php endforeach; ?>
				</div>

				<div class="grid border-l border-gray-200" style="grid-template-columns: repeat(7, minmax(0, 1fr));">
					<?php for ($day = $calendarStart; $day <= $calendarEnd; $day = $day->modify('+1 day')): ?>
						<?php
						$dateKey = $day->format('Y-m-d');
						$dayMessages = $messagesByDate[$dateKey] ?? [];
						$isCurrentMonth = $day->format('Y-m') === $currentMonth->format('Y-m');
						$isToday = $dateKey === date('Y-m-d');
						?>
						<div class="min-h-36 border-b border-r border-gray-200 p-2 <?= $isCurrentMonth ? 'bg-white' : 'bg-gray-50 text-gray-400' ?>">
							<div class="mb-2 flex items-center justify-between">
								<span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-sm font-bold <?= $isToday ? 'bg-green-700 text-white' : 'text-gray-800' ?>">
									<?= $h($day->format('j')) ?>
								</span>
								<?php if ($dayMessages !== []): ?>
									<span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-bold text-gray-700"><?= count($dayMessages) ?></span>
								<?php endif; ?>
							</div>
							<div class="space-y-2">
								<?php foreach (array_slice($dayMessages, 0, 3) as $message): ?>
									<?php
									$status = (string) ($message['status'] ?? 'scheduled');
									$statusClass = $statusClasses[$status] ?? $statusClasses['scheduled'];
									$time = !empty($message['scheduled_at']) ? date('H:i', strtotime((string) $message['scheduled_at'])) : '';
									$preview = mb_substr(trim((string) ($message['message'] ?? '')), 0, 54);
									?>
									<div class="rounded-md border px-2 py-2 <?= $statusClass ?>">
										<div class="text-xs font-black"><?= $h($time) ?> · <?= $h($statusLabels[$status] ?? $status) ?></div>
										<div class="mt-1 overflow-hidden text-xs normal-case leading-4"><?= $h($preview) ?><?= mb_strlen((string) ($message['message'] ?? '')) > 54 ? '...' : '' ?></div>
									</div>
								<?php endforeach; ?>
								<?php if (count($dayMessages) > 3): ?>
									<div class="text-xs font-semibold text-gray-500">+<?= count($dayMessages) - 3 ?> altri</div>
								<?php endif; ?>
							</div>
						</div>
					<?php endfor; ?>
				</div>
			</div>
		</div>
	</section>

	<section class="mt-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
		<h2 class="text-xl font-bold text-gray-900">Lista messaggi</h2>
		<div class="mt-4 overflow-x-auto">
			<table class="min-w-full divide-y divide-gray-200 text-sm">
				<thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
					<tr>
						<th class="px-3 py-3">Quando</th>
						<th class="px-3 py-3">Messaggio</th>
						<th class="px-3 py-3">Stato</th>
						<th class="px-3 py-3">Creato da</th>
						<th class="px-3 py-3">Azioni</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-gray-100">
					<?php if ($messages === []): ?>
						<tr><td colspan="5" class="px-3 py-8 text-center text-gray-500">Nessun messaggio presente.</td></tr>
					<?php endif; ?>
					<?php foreach ($messages as $message): ?>
						<?php
						$status = (string) ($message['status'] ?? 'scheduled');
						$messagePreview = mb_substr((string) ($message['message'] ?? ''), 0, 180);
						?>
						<tr>
							<td class="whitespace-nowrap px-3 py-3 font-semibold text-gray-900">
								<?= $h($message['scheduled_at_label'] ?? $message['scheduled_at'] ?? '') ?>
								<?php if (!empty($message['sent_at_label'])): ?>
									<div class="mt-1 text-xs font-normal text-gray-500">Inviato: <?= $h($message['sent_at_label']) ?></div>
								<?php endif; ?>
							</td>
							<td class="max-w-xl px-3 py-3 text-gray-700">
								<div class="whitespace-pre-wrap"><?= $h($messagePreview) ?><?= mb_strlen((string) ($message['message'] ?? '')) > 180 ? '...' : '' ?></div>
								<?php if (!empty($message['error_message'])): ?>
									<div class="mt-2 rounded border border-red-100 bg-red-50 px-2 py-1 text-xs text-red-700"><?= $h($message['error_message']) ?></div>
								<?php endif; ?>
							</td>
							<td class="px-3 py-3">
								<span class="rounded-full border px-2 py-1 text-xs font-bold <?= $statusClasses[$status] ?? $statusClasses['scheduled'] ?>"><?= $h($statusLabels[$status] ?? $status) ?></span>
							</td>
							<td class="px-3 py-3 text-gray-600"><?= $h($message['created_by_username'] ?? '-') ?></td>
							<td class="px-3 py-3">
								<?php if ($status === 'scheduled'): ?>
									<form method="POST" action="/admin/telegram/<?= (int) $message['id'] ?>/cancel" onsubmit="return confirm('Annullare questo messaggio programmato?');">
										<input type="hidden" name="csrf_token" value="<?= $h($csrf_token ?? '') ?>">
										<button type="submit" class="rounded-lg bg-gray-200 px-3 py-2 font-semibold text-gray-800 hover:bg-gray-300">Annulla</button>
									</form>
								<?php else: ?>
									<span class="text-gray-400">-</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</section>
</div>
