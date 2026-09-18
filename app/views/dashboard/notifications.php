<?php
$notifications = $notifications ?? [];
$unreadCount = (int) ($unreadCount ?? 0);
$totalCount = count($notifications);

if (!function_exists('notification_italian_date')) {
	function notification_italian_date(?string $dateTime): string
	{
		if (empty($dateTime)) {
			return 'Data non disponibile';
		}

		$timestamp = strtotime($dateTime);
		if ($timestamp === false) {
			return 'Data non disponibile';
		}

		$months = [
			1 => 'gennaio',
			2 => 'febbraio',
			3 => 'marzo',
			4 => 'aprile',
			5 => 'maggio',
			6 => 'giugno',
			7 => 'luglio',
			8 => 'agosto',
			9 => 'settembre',
			10 => 'ottobre',
			11 => 'novembre',
			12 => 'dicembre',
		];

		$day = date('j', $timestamp);
		$month = $months[(int) date('n', $timestamp)] ?? '';
		$year = date('Y', $timestamp);
		$time = date('H:i', $timestamp);

		return trim(sprintf('%s %s %s, ore %s', $day, $month, $year, $time));
	}
}

?>
<section class="mx-auto max-w-7xl space-y-6" aria-labelledby="notifications-page-title">
	<header class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
		<div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-green-900">Dashboard community</p>
				<h1 id="notifications-page-title" class="mt-2 text-3xl font-extrabold text-gray-950 md:text-4xl">Notifiche</h1>
				<p class="mt-3 max-w-2xl text-base leading-relaxed text-gray-700">
					Ricevi qui gli aggiornamenti sugli eventi che hai salvato, senza uscire dalla tua area personale.
				</p>
			</div>
			<div class="flex flex-wrap gap-3">
				<a href="/dashboard" class="inline-flex rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-800 hover:bg-gray-50">Torna alla dashboard</a>
				<a href="/dashboard/events" class="inline-flex rounded-lg bg-blue-700 px-4 py-2 text-sm font-bold text-white hover:bg-blue-800">Apri agenda</a>
				<?php if ($unreadCount > 0): ?>
					<form method="post" action="/dashboard/notifications/read-all" class="inline js-notification-action">
						<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
						<button type="submit" class="inline-flex rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm font-bold text-green-800 hover:bg-green-100">
							Segna tutte lette
						</button>
					</form>
				<?php endif; ?>
			</div>
		</div>
	</header>

	<div class="grid gap-4 md:grid-cols-3">
		<div class="rounded-xl border border-green-200 bg-green-50 p-4">
			<p class="text-sm font-semibold text-green-900">Totale notifiche</p>
			<p class="mt-2 text-3xl font-bold text-green-950" data-notification-total><?php echo $totalCount; ?></p>
		</div>
		<div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
			<p class="text-sm font-semibold text-amber-900">Da leggere</p>
			<p class="mt-2 text-3xl font-bold text-amber-950" data-notification-unread><?php echo $unreadCount; ?></p>
		</div>
		<div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
			<p class="text-sm font-semibold text-blue-900">Aggiornamenti eventi</p>
			<p class="mt-2 text-3xl font-bold text-blue-950" data-notification-event-total><?php echo $totalCount; ?></p>
		</div>
	</div>

	<section class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
		<div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-green-900">Notifiche interne</p>
				<h2 class="mt-2 text-2xl font-bold text-gray-950">Aggiornamenti recenti</h2>
				<p class="mt-2 text-gray-700">Ogni card ti mostra cosa è cambiato negli eventi che hai salvato, così capisci subito se ti riguarda.</p>
			</div>
		</div>

		<div class="mt-6 space-y-4">
			<?php if (empty($notifications)): ?>
				<div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-gray-600">
					Non ci sono ancora notifiche. Quando un evento salvato cambia, comparirà qui un avviso interno.
				</div>
			<?php else: ?>
				<?php foreach ($notifications as $notification): ?>
					<?php
					$isRead = (int) ($notification['is_read'] ?? 0) === 1;
					$payload = [];
					if (!empty($notification['payload'])) {
						$decoded = json_decode((string) $notification['payload'], true);
						$payload = is_array($decoded) ? $decoded : [];
					}
					?>
					<article class="rounded-2xl border bg-white p-5 shadow-sm <?php echo $isRead ? 'border-gray-200' : 'border-green-300 ring-1 ring-green-100'; ?>">
						<div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
							<div class="flex-1">
								<div class="flex flex-wrap items-center gap-2">
									<span class="inline-flex h-10 w-10 items-center justify-center rounded-full <?php echo $isRead ? 'bg-gray-100 text-gray-500' : 'bg-green-100 text-green-900'; ?>">
										<i class="fa-solid fa-bell"></i>
									</span>
									<div>
										<h3 class="text-lg font-bold text-gray-950">
											<?php if (!empty($payload['event_slug'])): ?>
												<a href="/eventi-cosplay/<?php echo htmlspecialchars((string) $payload['event_slug'], ENT_QUOTES, 'UTF-8'); ?>" class="hover:text-green-800 hover:underline">
													<?php echo htmlspecialchars($notification['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
												</a>
											<?php else: ?>
												<?php echo htmlspecialchars($notification['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
											<?php endif; ?>
										</h3>
									</div>
									<?php if (!$isRead): ?>
										<span class="rounded-full bg-green-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-green-800">Nuova</span>
									<?php endif; ?>
								</div>

								<p class="mt-4 text-sm leading-relaxed text-gray-700">
									<?php if (!empty($payload['event_slug'])): ?>
										<a href="/eventi-cosplay/<?php echo htmlspecialchars((string) $payload['event_slug'], ENT_QUOTES, 'UTF-8'); ?>" class="font-semibold text-green-800 hover:underline">
											<?php echo htmlspecialchars($notification['message'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
										</a>
									<?php else: ?>
										<?php echo htmlspecialchars($notification['message'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
									<?php endif; ?>
								</p>
							</div>

							<div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 md:min-w-64 md:text-right">
								<p class="font-semibold text-gray-800"><?php echo htmlspecialchars(notification_italian_date($notification['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></p>
								<div class="mt-3 flex flex-wrap gap-2 md:justify-end">
									<?php if (!empty($payload['event_slug'])): ?>
										<a href="/eventi-cosplay/<?php echo htmlspecialchars((string) $payload['event_slug'], ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex rounded-lg bg-green-800 px-4 py-2 text-sm font-bold text-white hover:bg-green-900">
											Vai all'evento
										</a>
									<?php endif; ?>
									<?php if (!$isRead): ?>
										<form method="post" action="/dashboard/notifications/read" class="inline js-notification-action">
											<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
											<input type="hidden" name="notification_id" value="<?php echo (int) ($notification['id'] ?? 0); ?>">
											<button type="submit" class="inline-flex rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-bold text-blue-800 hover:bg-blue-100">
												Segna letta
											</button>
										</form>
									<?php endif; ?>
									<form method="post" action="/dashboard/notifications/delete" class="inline js-notification-action" onsubmit="return confirm('Vuoi eliminare questa notifica?');">
										<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
										<input type="hidden" name="notification_id" value="<?php echo (int) ($notification['id'] ?? 0); ?>">
										<button type="submit" class="inline-flex rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-bold text-red-800 hover:bg-red-100">
											Elimina
										</button>
									</form>
								</div>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</section>
</section>

<script>
	document.addEventListener('DOMContentLoaded', () => {
		const notificationsRoot = document.querySelector('[aria-labelledby="notifications-page-title"]');
		if (!notificationsRoot) {
			return;
		}

		const totalCounter = notificationsRoot.querySelector('[data-notification-total]');
		const unreadCounter = notificationsRoot.querySelector('[data-notification-unread]');
		const eventCounter = notificationsRoot.querySelector('[data-notification-event-total]');
		const notificationCards = () => Array.from(notificationsRoot.querySelectorAll('article'));

		const syncCounters = () => {
			if (totalCounter) {
				totalCounter.textContent = String(notificationCards().length);
			}
			if (eventCounter) {
				eventCounter.textContent = String(notificationCards().length);
			}
			if (unreadCounter) {
				const unread = notificationCards().filter((card) => !card.querySelector('.rounded-full.bg-green-100.px-2\\.5')).length;
				unreadCounter.textContent = String(unread);
			}
		};

		const refreshAfterAction = (form, result) => {
			if (!result || !result.success) {
				return;
			}

			if (form.action.includes('/read-all')) {
				window.location.reload();
				return;
			}

			const card = form.closest('article');
			if (!card) {
				window.location.reload();
				return;
			}

			if (form.action.includes('/delete')) {
				card.remove();
				syncCounters();
				return;
			}

			const readForm = form.closest('form');
			const newBadge = card.querySelector('.rounded-full.bg-green-100.px-2\\.5');
			if (newBadge) {
				newBadge.remove();
			}
			if (readForm && form.action.includes('/read')) {
				readForm.remove();
			}

			const bell = document.querySelector('a[href="/dashboard/notifications"] .fa-bell');
			if (bell) {
				const badge = bell.parentElement?.querySelector('span');
				if (badge) {
					const current = Math.max(0, (parseInt(badge.textContent || '0', 10) || 0) - 1);
					if (current > 0) {
						badge.textContent = String(current);
					} else {
						badge.remove();
					}
				}
			}

			syncCounters();
		};

		document.addEventListener('submit', async (event) => {
			const form = event.target;
			if (!(form instanceof HTMLFormElement) || !form.classList.contains('js-notification-action')) {
				return;
			}

			event.preventDefault();

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
					throw new Error(result.message || 'Impossibile aggiornare la notifica.');
				}

				refreshAfterAction(form, result);
			} catch (error) {
				alert(error.message || 'Impossibile aggiornare la notifica.');
			}
		});

		syncCounters();
	});
</script>
