<?php

$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$maxMessageLength = (int) ($maxMessageLength ?? 4096);
$defaultMessage = (string) ($defaultMessage ?? '');
$isConfigured = (bool) ($isConfigured ?? false);

?>

<div class="container mx-auto max-w-5xl p-6">
	<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
		<div>
			<p class="text-sm font-semibold uppercase tracking-wide text-green-700">Telegram</p>
			<h1 class="text-3xl font-semibold text-gray-900">Crea messaggio</h1>
			<p class="mt-2 text-gray-600">Prepara un aggiornamento copy-safe, invialo subito o programmalo nel calendario.</p>
		</div>
		<a href="/admin/telegram" class="inline-flex items-center justify-center rounded-lg bg-gray-200 px-4 py-2 font-semibold text-gray-800 hover:bg-gray-300">
			Vedi calendario
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
			Telegram canale non risulta configurato. Imposta TELEGRAM_BOT_TOKEN e TELEGRAM_CHANNEL_CHAT_ID prima di usare l'invio.
		</div>
	<?php endif; ?>

	<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
		<section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
			<form method="POST" action="/admin/telegram/send" class="space-y-5" id="telegram-message-form">
				<input type="hidden" name="csrf_token" value="<?= $h($csrf_token ?? '') ?>">

				<div>
					<div class="mb-2 flex items-center justify-between gap-3">
						<label for="telegram-message" class="block text-sm font-semibold text-gray-800">Messaggio</label>
						<span class="text-sm font-semibold text-gray-500"><span id="telegram-char-count">0</span> / <?= $maxMessageLength ?></span>
					</div>
					<textarea id="telegram-message" name="message" required rows="16" maxlength="<?= $maxMessageLength ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-sm leading-6 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100"><?= $h($defaultMessage) ?></textarea>
				</div>

				<div class="grid gap-4 md:grid-cols-3">
					<div>
						<label for="parse-mode" class="block text-sm font-semibold text-gray-800">Formato</label>
						<select id="parse-mode" name="parse_mode" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2">
							<option value="" selected>Testo semplice</option>
							<option value="HTML">HTML Telegram</option>
							<option value="MarkdownV2">MarkdownV2</option>
						</select>
					</div>
					<label class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800">
						<input type="checkbox" name="disable_web_page_preview" value="1" checked class="h-5 w-5 rounded border-gray-300 text-green-700 focus:ring-green-300">
						Nascondi anteprima link
					</label>
					<label class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-800">
						<input type="checkbox" name="disable_notification" value="1" class="h-5 w-5 rounded border-gray-300 text-green-700 focus:ring-green-300">
						Invio silenzioso
					</label>
				</div>

				<div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
					<label for="scheduled-at" class="block text-sm font-semibold text-gray-800">Programma giorno e ora</label>
					<div class="mt-2 flex flex-col gap-3 sm:flex-row">
						<input id="scheduled-at" type="datetime-local" name="scheduled_at" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2">
						<button type="submit" formaction="/admin/telegram/schedule" <?= $isConfigured ? '' : 'disabled' ?> class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-5 py-2 font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:bg-gray-400">
							Programma
						</button>
					</div>
				</div>

				<div class="flex flex-col gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
					<p class="text-sm text-gray-600">Default consigliato: testo semplice, righe brevi, URL separato e niente grassetti copiati da editor esterni.</p>
					<button type="submit" <?= $isConfigured ? '' : 'disabled' ?> onclick="return confirm('Inviare subito questo messaggio sul canale Telegram?');" class="inline-flex items-center justify-center rounded-lg bg-green-700 px-5 py-3 font-semibold text-white shadow-sm hover:bg-green-800 disabled:cursor-not-allowed disabled:bg-gray-400">
						Invia ora
					</button>
				</div>
			</form>
		</section>

		<aside class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
			<h2 class="text-lg font-bold text-gray-900">Preview copy-safe</h2>
			<div id="telegram-preview" class="mt-4 min-h-64 whitespace-pre-wrap rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm leading-6 text-gray-900"></div>
			<div class="mt-5 rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-blue-900">
				<p class="font-bold">Formato rapido</p>
				<p class="mt-2">Titolo breve, 2-3 righe utili, elenco con emoji, URL su riga propria, firma finale.</p>
			</div>
		</aside>
	</div>
</div>

<script>
	(() => {
		const textarea = document.getElementById('telegram-message');
		const preview = document.getElementById('telegram-preview');
		const counter = document.getElementById('telegram-char-count');

		if (!textarea || !preview || !counter) {
			return;
		}

		const refresh = () => {
			counter.textContent = String(textarea.value.length);
			preview.textContent = textarea.value;
		};

		textarea.addEventListener('input', refresh);
		refresh();
	})();
</script>
