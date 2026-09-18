<?php

$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$overview = $overview ?? [];
$totals = $overview['totals'] ?? [];
$byTag = $overview['byTag'] ?? [];
$byEvent = $overview['byEvent'] ?? [];
$filters = $overview['filters'] ?? [];

$eventLabels = [
	'request' => 'Inviata',
	'sent' => 'Inviata',
	'delivered' => 'Consegnata',
	'opened' => 'Aperta',
	'uniqueOpened' => 'Prima apertura',
	'unique_opened' => 'Prima apertura',
	'click' => 'Click',
	'clicked' => 'Click',
	'softBounce' => 'Soft bounce',
	'soft_bounce' => 'Soft bounce',
	'hardBounce' => 'Hard bounce',
	'hard_bounce' => 'Hard bounce',
	'blocked' => 'Bloccata',
	'spam' => 'Spam',
	'invalid' => 'Non valida',
	'deferred' => 'Rimandata',
	'unsubscribed' => 'Disiscritta',
];

$labelEvent = static fn ($event): string => $eventLabels[(string) $event] ?? ucfirst(str_replace('_', ' ', (string) $event));
$tagOptions = [
	'registration' => 'Registrazione',
	'password_reset' => 'Reset password',
	'event_claim' => 'Riscatto evento',
	'organization_invitation' => 'Invito organizzazione',
	'event_approved' => 'Evento approvato',
	'event_report' => 'Segnalazione evento',
	'organization_email' => 'Email organizzazioni',
	'legacy_invitation' => 'Inviti vecchio sito',
	'advertising' => 'Advertising',
];

?>

<style>
	.email-metrics-row {
		display: flex;
		gap: 8px;
		width: 100%;
	}

	.email-metric-pill {
		align-items: center;
		background: #f9fafb;
		border-radius: 6px;
		display: flex;
		flex: 1 1 0;
		justify-content: space-between;
		min-width: 0;
		padding: 8px 12px;
	}

	.email-filters-row {
		display: flex;
		gap: 8px;
		margin-top: 12px;
		width: 100%;
	}

	.email-filters-row input,
	.email-filters-row select,
	.email-filters-row button {
		height: 36px;
	}

	.email-filter-tag {
		width: 140px;
	}

	.email-filter-event {
		width: 160px;
	}

	.email-filter-email {
		flex: 1 1 auto;
		min-width: 220px;
	}

	.email-filter-date {
		width: 160px;
	}

	.email-filter-submit {
		width: 96px;
	}

	@media (max-width: 1100px) {
		.email-metrics-row,
		.email-filters-row {
			flex-wrap: wrap;
		}

		.email-metric-pill {
			flex-basis: calc(50% - 4px);
		}
	}

	@media (max-width: 700px) {
		.email-metrics-row,
		.email-filters-row {
			flex-direction: column;
		}

		.email-metric-pill,
		.email-filter-tag,
		.email-filter-event,
		.email-filter-email,
		.email-filter-date,
		.email-filter-submit {
			width: 100%;
		}
	}
</style>

<div class="container mx-auto max-w-7xl p-4">
	<div class="mb-4 flex items-center justify-between gap-4">
		<div>
			<h1 class="text-2xl font-semibold text-gray-900">Eventi email Brevo</h1>
			<p class="mt-1 text-sm text-gray-600">Consegne, aperture, click, bounce e tag transazionali.</p>
		</div>
		<a href="/admin/dashboard" class="shrink-0 rounded-md bg-gray-200 px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-300">Dashboard</a>
	</div>

	<div class="mb-4 rounded-lg bg-white p-3 shadow-sm ring-1 ring-gray-200">
		<div class="email-metrics-row">
			<?php foreach ([
				'Eventi' => 'total',
				'Messaggi' => 'messages',
				'Consegnate' => 'delivered',
				'Aperture' => 'opened',
				'Problemi' => 'problems',
			] as $label => $key): ?>
				<div class="email-metric-pill">
					<span class="text-xs font-semibold uppercase tracking-wide text-gray-500"><?= $h($label) ?></span>
					<span class="text-lg font-bold text-gray-950"><?= number_format((int) ($totals[$key] ?? 0), 0, ',', '.') ?></span>
				</div>
			<?php endforeach; ?>
		</div>

		<form method="GET" action="/admin/email-delivery-events" class="email-filters-row text-sm">
			<select name="tag" class="email-filter-tag rounded-md border border-gray-300 px-2">
				<option value="">Tag</option>
				<?php foreach ($tagOptions as $value => $label): ?>
					<option value="<?= $h($value) ?>" <?= ($filters['tag'] ?? '') === $value ? 'selected' : '' ?>><?= $h($label) ?></option>
				<?php endforeach; ?>
			</select>
			<select name="event_name" class="email-filter-event rounded-md border border-gray-300 px-2">
				<option value="">Evento</option>
				<?php foreach ($eventLabels as $value => $label): ?>
					<option value="<?= $h($value) ?>" <?= ($filters['event_name'] ?? '') === $value ? 'selected' : '' ?>><?= $h($label) ?></option>
				<?php endforeach; ?>
			</select>
			<input name="email" value="<?= $h($filters['email'] ?? '') ?>" placeholder="email" class="email-filter-email rounded-md border border-gray-300 px-2">
			<input type="date" name="from" value="<?= $h($filters['from'] ?? '') ?>" class="email-filter-date rounded-md border border-gray-300 px-2">
			<input type="date" name="to" value="<?= $h($filters['to'] ?? '') ?>" class="email-filter-date rounded-md border border-gray-300 px-2">
			<button class="email-filter-submit rounded-md bg-green-700 px-4 text-sm font-semibold text-white hover:bg-green-800">Filtra</button>
		</form>
	</div>

	<div class="grid gap-4 lg:grid-cols-2">
		<section class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
			<h2 class="text-base font-bold text-gray-900">Per tag</h2>
			<div class="mt-2 overflow-x-auto">
				<table class="min-w-full divide-y divide-gray-200 text-sm">
					<tbody class="divide-y divide-gray-100">
						<?php foreach ($byTag as $row): ?>
							<tr>
								<td class="py-2 pr-3 font-semibold text-gray-900"><?= $h($row['label'] ?? '') ?></td>
								<td class="px-3 py-2 text-right text-gray-700"><?= number_format((int) ($row['messages'] ?? 0), 0, ',', '.') ?> messaggi</td>
								<td class="py-2 pl-3 text-right font-bold text-gray-900"><?= number_format((int) ($row['total'] ?? 0), 0, ',', '.') ?></td>
							</tr>
						<?php endforeach; ?>
						<?php if (empty($byTag)): ?>
							<tr><td class="py-4 text-center text-gray-500">Nessun dato ancora ricevuto.</td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</section>

		<section class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
			<h2 class="text-base font-bold text-gray-900">Per evento</h2>
			<div class="mt-2 overflow-x-auto">
				<table class="min-w-full divide-y divide-gray-200 text-sm">
					<tbody class="divide-y divide-gray-100">
						<?php foreach ($byEvent as $row): ?>
							<tr>
								<td class="py-2 pr-3 font-semibold text-gray-900"><?= $h($labelEvent($row['label'] ?? '')) ?></td>
								<td class="px-3 py-2 text-right text-gray-700"><?= number_format((int) ($row['messages'] ?? 0), 0, ',', '.') ?> messaggi</td>
								<td class="py-2 pl-3 text-right font-bold text-gray-900"><?= number_format((int) ($row['total'] ?? 0), 0, ',', '.') ?></td>
							</tr>
						<?php endforeach; ?>
						<?php if (empty($byEvent)): ?>
							<tr><td class="py-4 text-center text-gray-500">Nessun dato ancora ricevuto.</td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</section>
	</div>

	<section class="mt-4 rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
		<h2 class="mb-3 text-base font-bold text-gray-900">Ultimi eventi</h2>
		<?php
		$adminList = [
			'id' => 'email-delivery-events-list',
			'endpoint' => '/admin/email-delivery-events/data',
			'detailPanel' => true,
			'detailEndpoint' => '/admin/email-delivery-events/detail/{id}',
			'detailRenderer' => 'emailDeliveryEvent',
			'initialFilters' => [
				'tag' => $filters['tag'] ?? '',
				'event_name' => $filters['event_name'] ?? '',
				'email' => $filters['email'] ?? '',
				'from' => $filters['from'] ?? '',
				'to' => $filters['to'] ?? '',
			],
			'pageSize' => 25,
			'defaultSort' => 'event_date',
			'defaultDirection' => 'desc',
			'search' => true,
			'filters' => [
				[
					'name' => 'tag',
					'label' => 'Tag',
					'type' => 'select',
					'options' => array_map(
						static fn (string $value, string $label): array => ['value' => $value, 'label' => $label],
						array_keys($tagOptions),
						array_values($tagOptions)
					),
				],
				[
					'name' => 'event_name',
					'label' => 'Evento',
					'type' => 'select',
					'options' => array_map(
						static fn (string $value, string $label): array => ['value' => $value, 'label' => $label],
						array_keys($eventLabels),
						array_values($eventLabels)
					),
				],
				[
					'name' => 'email',
					'label' => 'Email',
					'type' => 'text',
				],
			],
			'columns' => [
				[
					'key' => 'event_date_label',
					'label' => 'Data',
					'sortable' => true,
					'width' => '175px',
				],
				[
					'key' => 'event_name',
					'label' => 'Evento',
					'formatter' => 'emailEvent',
					'sortable' => true,
					'width' => '140px',
				],
				[
					'key' => 'tag',
					'label' => 'Tag',
					'formatter' => 'emailTag',
					'sortable' => true,
					'width' => '160px',
				],
				[
					'key' => 'email',
					'label' => 'Email',
					'sortable' => true,
					'width' => '240px',
				],
				[
					'key' => 'subject',
					'label' => 'Oggetto',
					'sortable' => true,
					'width' => '300px',
				],
				[
					'key' => 'reason',
					'label' => 'Motivo',
					'width' => '240px',
				],
			],
		];
		require __DIR__ . '/../components/admin-list/admin-list.php';
		?>
	</section>
</div>
