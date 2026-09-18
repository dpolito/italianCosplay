<?php
$user = $data['user'] ?? [];
$portfolio = $data['portfolio'] ?? [];
$characters = $data['characters'] ?? [];
$eventSelections = $data['eventSelections'] ?? [];
$groupedEventSelections = $data['groupedEventSelections'] ?? [];
$characterSearch = $data['characterSearch'] ?? '';
$currentUsername = (string) ($user['username'] ?? $_SESSION['username'] ?? '');

if (!function_exists('cosplay_portfolio_label')) {
	function cosplay_portfolio_label(array $item): string
	{
		return trim((string) ($item['custom_name'] ?: ($item['name_full'] ?? 'Cosplay')));
	}
}
?>

<section class="mx-auto max-w-7xl space-y-6" aria-labelledby="cosplay-portfolio-title">
	<header class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
		<div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-fuchsia-900">Dashboard community</p>
				<h1 id="cosplay-portfolio-title" class="mt-2 text-3xl font-extrabold text-gray-950 md:text-4xl">Portfolio cosplay</h1>
				<p class="mt-3 max-w-2xl text-base leading-relaxed text-gray-700">
					Salva i personaggi che hai già preparato o che vuoi portare agli eventi. Da qui li colleghi agli appuntamenti della tua agenda.
				</p>
			</div>
			<div class="flex flex-wrap gap-3">
				<a href="/dashboard" class="inline-flex rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-800 hover:bg-gray-50">Torna alla dashboard</a>
				<a href="/eventi-cosplay" class="inline-flex rounded-lg bg-fuchsia-800 px-4 py-2 text-sm font-bold text-white hover:bg-fuchsia-900">Sfoglia eventi</a>
			</div>
		</div>
	</header>

	<div class="grid gap-4 md:grid-cols-3">
		<div class="rounded-xl border border-fuchsia-200 bg-fuchsia-50 p-4">
			<p class="text-sm font-semibold text-fuchsia-900">Cosplay salvati</p>
			<p class="mt-2 text-3xl font-bold text-fuchsia-950" data-cosplay-count><?php echo count($portfolio); ?></p>
		</div>
		<div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
			<p class="text-sm font-semibold text-blue-900">Uso rapido</p>
			<p class="mt-2 text-sm text-blue-950">Collega un cosplay a un evento in pochi secondi.</p>
		</div>
		<div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
			<p class="text-sm font-semibold text-gray-700">Portfolio pronto</p>
			<p class="mt-2 text-sm text-gray-700">Gestisci i cosplay da portare e quelli già collegati agli eventi.</p>
		</div>
	</div>

	<section class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
		<h2 class="text-2xl font-bold text-gray-950">Aggiungi un cosplay</h2>
		<p class="mt-2 text-gray-700">Scegli il personaggio, opzionalmente personalizza il nome e decidi se mostrarlo pubblicamente.</p>

		<form method="post" action="/dashboard/cosplay/save" enctype="multipart/form-data" class="js-cosplay-save mt-6 grid gap-4 md:grid-cols-2">
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
			<input type="hidden" name="id" value="">
			<input type="hidden" name="anilist_character_id" id="cosplay-character-id" value="">

			<div class="md:col-span-2">
				<label class="mb-2 block text-sm font-bold text-gray-900">Personaggio Anilist</label>
				<div class="relative">
					<input type="search" id="cosplay-character-search" autocomplete="off" placeholder="Scrivi il personaggio o la serie..." class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 focus:border-fuchsia-700 focus:outline-none focus:ring-2 focus:ring-fuchsia-700">
					<div id="cosplay-character-results" class="absolute z-30 mt-2 hidden max-h-80 w-full overflow-auto rounded-xl border border-gray-200 bg-white shadow-xl"></div>
				</div>
				<p id="cosplay-character-selected" class="mt-2 text-sm font-semibold text-fuchsia-900">Nessun personaggio selezionato. Scrivi il nome del personaggio o della serie qui sopra.</p>
			</div>

			<div>
				<label class="mb-2 block text-sm font-bold text-gray-900">Nome cosplay personalizzato</label>
				<input type="text" name="custom_name" placeholder="Opzionale" class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-fuchsia-700 focus:outline-none focus:ring-2 focus:ring-fuchsia-700">
			</div>

			<div>
				<label class="mb-2 block text-sm font-bold text-gray-900">Immagine di riferimento</label>
				<input type="file" name="reference_image_file" accept="image/jpeg,image/png,image/webp" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 focus:border-fuchsia-700 focus:outline-none focus:ring-2 focus:ring-fuchsia-700">
			</div>

			<div class="md:col-span-2">
				<label class="mb-2 block text-sm font-bold text-gray-900">Note</label>
				<textarea name="notes" rows="4" class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-fuchsia-700 focus:outline-none focus:ring-2 focus:ring-fuchsia-700" placeholder="Accessori, variante, stato del costume..."></textarea>
			</div>

			<div class="md:col-span-2 flex items-center gap-3">
				<label class="inline-flex items-center gap-2 rounded-full border border-gray-300 bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-800">
					<input type="checkbox" name="is_public" value="1" checked class="rounded border-gray-300 text-fuchsia-700 focus:ring-fuchsia-700">
					Mostra nel profilo pubblico
				</label>
				<button type="submit" class="inline-flex rounded-lg bg-fuchsia-800 px-5 py-3 text-sm font-bold text-white hover:bg-fuchsia-900">
					Salva cosplay
				</button>
			</div>
		</form>
	</section>

	<section class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
		<h2 class="text-2xl font-bold text-gray-950">Associa un cosplay a un evento</h2>
		<p class="mt-2 text-gray-700">Cerca un evento, poi scegli rapidamente quali cosplay porterai o terrai come forse.</p>

		<div class="mt-6 grid gap-4 lg:grid-cols-[1fr_1.2fr]">
			<div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
				<label class="mb-2 block text-sm font-bold text-gray-900">Cerca evento</label>
				<div class="relative">
					<input type="search" id="cosplay-event-search" autocomplete="off" placeholder="Es. Lucca Comics, Napoli Comicon..." class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 focus:border-fuchsia-700 focus:outline-none focus:ring-2 focus:ring-fuchsia-700">
					<div id="cosplay-event-results" class="absolute z-30 mt-2 hidden max-h-80 w-full overflow-auto rounded-xl border border-gray-200 bg-white shadow-xl"></div>
				</div>
				<p id="cosplay-event-selected" class="mt-2 text-sm font-semibold text-fuchsia-900">Nessun evento selezionato.</p>
				<div id="cosplay-event-current-selections" class="mt-4 space-y-2 hidden"></div>
			</div>

			<div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
				<p class="text-sm font-bold text-gray-900">Cosplay disponibili</p>
				<div id="cosplay-event-portfolio-list" class="mt-4 grid gap-3 md:grid-cols-2">
					<?php foreach ($portfolio as $item): ?>
						<form method="post" action="/dashboard/cosplay/event/save" class="js-cosplay-event-link rounded-xl border border-white bg-white p-4">
							<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
							<input type="hidden" name="event_id" value="">
							<input type="hidden" name="portfolio_id" value="<?php echo (int) $item['id']; ?>">
							<input type="hidden" name="redirect_to" value="/dashboard/cosplay">
							<div class="flex items-start justify-between gap-3">
								<div class="min-w-0">
									<p class="truncate text-sm font-semibold text-gray-900"><?php echo htmlspecialchars(cosplay_portfolio_label($item), ENT_QUOTES, 'UTF-8'); ?></p>
									<p class="text-xs font-semibold uppercase tracking-wide text-fuchsia-900"><?php echo htmlspecialchars(!empty($item['is_public']) ? 'Pubblico' : 'Privato', ENT_QUOTES, 'UTF-8'); ?></p>
								</div>
								<div class="flex flex-wrap gap-2">
									<button type="submit" name="status" value="porterò" class="inline-flex items-center justify-center rounded-lg bg-fuchsia-800 px-3 py-2 text-xs font-bold text-white hover:bg-fuchsia-900">
										Porterò
									</button>
									<button type="submit" name="status" value="forse" class="inline-flex items-center justify-center rounded-lg border border-fuchsia-300 bg-white px-3 py-2 text-xs font-bold text-fuchsia-900 hover:bg-fuchsia-100">
										Forse
									</button>
								</div>
							</div>
						</form>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>

	<section class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
		<h2 class="text-2xl font-bold text-gray-950">I tuoi cosplay</h2>
		<?php if (empty($portfolio)): ?>
			<div class="mt-4 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-gray-600">
				Non hai ancora aggiunto cosplay al portfolio.
			</div>
		<?php else: ?>
			<div id="cosplay-portfolio-list" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
				<?php foreach ($portfolio as $item): ?>
					<article class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 shadow-sm" data-portfolio-card data-portfolio-id="<?php echo (int) $item['id']; ?>">
						<?php if (!empty($item['reference_image'])): ?>
							<img src="<?php echo htmlspecialchars('/public_assets' . $item['reference_image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars(cosplay_portfolio_label($item), ENT_QUOTES, 'UTF-8'); ?>" class="h-44 w-full object-cover">
						<?php elseif (!empty($item['image_large'])): ?>
							<img src="<?php echo htmlspecialchars($item['image_large'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars(cosplay_portfolio_label($item), ENT_QUOTES, 'UTF-8'); ?>" class="h-44 w-full object-cover">
						<?php endif; ?>
						<div class="p-5">
							<p class="text-xs font-semibold uppercase tracking-wide text-fuchsia-900">Anilist</p>
							<h3 class="mt-2 text-lg font-bold text-gray-950"><?php echo htmlspecialchars(cosplay_portfolio_label($item), ENT_QUOTES, 'UTF-8'); ?></h3>
							<p class="mt-1 text-sm text-gray-600"><?php echo htmlspecialchars(trim((string) ($item['name_full'] ?? '') . (!empty($item['name_native']) ? ' · ' . $item['name_native'] : '')), ENT_QUOTES, 'UTF-8'); ?></p>
							<p class="mt-1 text-sm text-gray-600"><?php echo htmlspecialchars((string) ($item['anime_titles'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
							<?php if (!empty($item['notes'])): ?>
								<p class="mt-3 text-sm leading-relaxed text-gray-700"><?php echo htmlspecialchars((string) $item['notes'], ENT_QUOTES, 'UTF-8'); ?></p>
							<?php endif; ?>
							<div class="mt-4 flex flex-wrap items-center gap-2">
								<span data-visibility-badge class="rounded-full px-3 py-1 text-xs font-bold <?php echo !empty($item['is_public']) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'; ?>">
									<?php echo !empty($item['is_public']) ? 'Pubblico' : 'Privato'; ?>
								</span>
								<form method="post" action="/dashboard/cosplay/toggle-visibility" class="js-cosplay-visibility-toggle">
									<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
									<input type="hidden" name="portfolio_id" value="<?php echo (int) $item['id']; ?>">
									<input type="hidden" name="is_public" value="<?php echo !empty($item['is_public']) ? '0' : '1'; ?>">
									<button type="submit" class="js-cosplay-visibility-button inline-flex rounded-lg border border-fuchsia-200 bg-fuchsia-50 px-4 py-2 text-xs font-bold text-fuchsia-800 hover:bg-fuchsia-100" data-is-public="<?php echo !empty($item['is_public']) ? '1' : '0'; ?>">
										<?php echo !empty($item['is_public']) ? 'Rendi privato' : 'Rendi pubblico'; ?>
									</button>
								</form>
								<form method="post" action="/dashboard/cosplay/delete" class="js-cosplay-delete" data-confirm="Vuoi rimuovere questo cosplay dal portfolio?">
									<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
									<input type="hidden" name="portfolio_id" value="<?php echo (int) $item['id']; ?>">
									<button type="submit" class="inline-flex rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-xs font-bold text-red-800 hover:bg-red-100">
										Elimina
									</button>
								</form>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>

	<section class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
		<h2 class="text-2xl font-bold text-gray-950">Cosplay collegati agli eventi</h2>
		<p class="mt-2 text-gray-700">Qui vedi dove hai già dichiarato cosa porterai e puoi rimuovere l’associazione in ogni momento.</p>

		<?php if (empty($groupedEventSelections)): ?>
			<div class="mt-4 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-gray-600">
				Non hai ancora collegato nessun cosplay agli eventi.
			</div>
		<?php else: ?>
			<div id="cosplay-event-selection-list" class="mt-6 space-y-4">
				<?php foreach ($groupedEventSelections as $eventGroup): ?>
					<article class="rounded-2xl border border-gray-200 bg-gray-50 p-5 shadow-sm" data-event-selection-card data-event-id="<?php echo (int) ($eventGroup['event_id'] ?? 0); ?>">
						<div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
							<div>
								<p class="text-xs font-semibold uppercase tracking-wide text-fuchsia-900"><?php echo count($eventGroup['items'] ?? []); ?> cosplay associati</p>
								<h3 class="mt-2 text-lg font-bold text-gray-950">
									<a href="/eventi-cosplay/<?php echo htmlspecialchars((string) ($eventGroup['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="hover:text-fuchsia-800 hover:underline">
										<?php echo htmlspecialchars((string) ($eventGroup['titolo'] ?? 'Evento'), ENT_QUOTES, 'UTF-8'); ?>
									</a>
								</h3>
								<?php if (!empty($eventGroup['data_inizio'])): ?>
									<p class="mt-1 text-sm text-gray-600">
										<?php echo htmlspecialchars(date('d/m/Y', strtotime((string) $eventGroup['data_inizio'])), ENT_QUOTES, 'UTF-8'); ?>
										<?php if (!empty($eventGroup['data_fine']) && $eventGroup['data_fine'] !== $eventGroup['data_inizio']): ?>
											- <?php echo htmlspecialchars(date('d/m/Y', strtotime((string) $eventGroup['data_fine'])), ENT_QUOTES, 'UTF-8'); ?>
										<?php endif; ?>
									</p>
								<?php endif; ?>
							</div>
							<div class="flex flex-wrap gap-2">
								<button
									type="button"
									class="js-cosplay-banner-open inline-flex rounded-lg border border-fuchsia-200 bg-white px-4 py-2 text-sm font-bold text-fuchsia-900 hover:bg-fuchsia-50"
									data-banner-title="<?php echo htmlspecialchars((string) ($eventGroup['titolo'] ?? 'Evento'), ENT_QUOTES, 'UTF-8'); ?>"
									data-banner-slug="<?php echo htmlspecialchars((string) ($eventGroup['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
									data-banner-start="<?php echo htmlspecialchars((string) ($eventGroup['data_inizio'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
									data-banner-end="<?php echo htmlspecialchars((string) ($eventGroup['data_fine'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
									data-banner-items="<?php echo htmlspecialchars(json_encode(array_map(static function (array $selection): array {
										return [
											'label' => cosplay_portfolio_label($selection),
											'reference_image' => !empty($selection['reference_image']) ? '/public_assets' . $selection['reference_image'] : '',
											'status' => (string) ($selection['status'] ?? 'porterò'),
										];
									}, $eventGroup['items'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>"
								>
									Crea banner
								</button>
								<a href="/eventi-cosplay/<?php echo htmlspecialchars((string) ($eventGroup['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex rounded-lg bg-fuchsia-800 px-4 py-2 text-sm font-bold text-white hover:bg-fuchsia-900">
									Apri evento
								</a>
								<form method="post" action="/dashboard/cosplay/event/remove" class="js-cosplay-event-remove" data-confirm="Vuoi rimuovere tutte le associazioni di questo evento?">
									<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
									<input type="hidden" name="event_id" value="<?php echo (int) ($eventGroup['event_id'] ?? 0); ?>">
									<input type="hidden" name="redirect_to" value="/dashboard/cosplay">
									<button type="submit" class="inline-flex rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-bold text-red-800 hover:bg-red-100">
										Rimuovi tutto
									</button>
								</form>
							</div>
						</div>
						<div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
							<?php foreach (($eventGroup['items'] ?? []) as $selection): ?>
								<div class="rounded-xl border border-white bg-white p-4">
									<div class="flex items-start justify-between gap-3">
										<div>
											<p class="text-sm font-semibold text-gray-900">
												<?php echo htmlspecialchars(cosplay_portfolio_label($selection), ENT_QUOTES, 'UTF-8'); ?>
											</p>
											<p class="mt-1 text-xs font-semibold uppercase tracking-wide text-fuchsia-900">
												<?php echo htmlspecialchars(($selection['status'] ?? '') === 'forse' ? 'Forse lo porterò' : 'Porterò', ENT_QUOTES, 'UTF-8'); ?>
											</p>
										</div>
										<form method="post" action="/dashboard/cosplay/event/remove" class="js-cosplay-event-remove" data-confirm="Vuoi rimuovere questo cosplay da questo evento?">
											<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
											<input type="hidden" name="event_id" value="<?php echo (int) ($eventGroup['event_id'] ?? 0); ?>">
											<input type="hidden" name="portfolio_id" value="<?php echo (int) ($selection['portfolio_id'] ?? 0); ?>">
											<input type="hidden" name="redirect_to" value="/dashboard/cosplay">
											<button type="submit" class="inline-flex rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-800 hover:bg-red-100">
												Rimuovi
											</button>
										</form>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>

	<div id="cosplay-banner-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-950/80 px-4 py-6">
		<div class="max-h-[95vh] w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl">
			<div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 md:px-6">
				<div>
					<p class="text-xs font-bold uppercase tracking-wide text-fuchsia-900">Banner social</p>
					<h2 class="text-xl font-extrabold text-gray-950">Anteprima pronta per la condivisione</h2>
				</div>
				<button type="button" id="cosplay-banner-close" class="rounded-full border border-gray-300 px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">Chiudi</button>
			</div>
			<div class="grid gap-0 lg:grid-cols-[1.1fr_0.9fr]">
				<div class="bg-gray-950 p-4 md:p-6">
					<div class="mx-auto aspect-square w-full max-w-[680px] overflow-hidden rounded-[2rem] shadow-2xl">
						<canvas id="cosplay-banner-canvas" width="1080" height="1080" class="h-full w-full"></canvas>
					</div>
				</div>
				<div class="space-y-5 p-5 md:p-6">
					<div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
						<p class="text-sm font-bold text-gray-900">Cosplay inclusi</p>
						<div id="cosplay-banner-list" class="mt-3 space-y-2"></div>
					</div>
					<div class="flex flex-wrap gap-3">
						<button type="button" id="cosplay-banner-download" class="inline-flex rounded-lg bg-fuchsia-800 px-5 py-3 text-sm font-bold text-white hover:bg-fuchsia-900">Scarica PNG</button>
						<button type="button" id="cosplay-banner-regenerate" class="inline-flex rounded-lg border border-fuchsia-200 bg-white px-5 py-3 text-sm font-bold text-fuchsia-900 hover:bg-fuchsia-50">Rigenera anteprima</button>
					</div>
					<p class="text-sm text-gray-600">Il banner usa il logo del sito, il nome evento, la data e le immagini caricate nel portfolio per dare più impatto alla condivisione.</p>
				</div>
			</div>
		</div>
	</div>
</section>

<script>
	document.addEventListener('DOMContentLoaded', () => {
		const input = document.getElementById('cosplay-character-search');
		const results = document.getElementById('cosplay-character-results');
		const selectedLabel = document.getElementById('cosplay-character-selected');
		const hiddenId = document.getElementById('cosplay-character-id');
		const saveForm = document.querySelector('.js-cosplay-save');
		const eventSearchInput = document.getElementById('cosplay-event-search');
		const eventSearchResults = document.getElementById('cosplay-event-results');
		const eventSelectedLabel = document.getElementById('cosplay-event-selected');
		const eventCurrentSelections = document.getElementById('cosplay-event-current-selections');
		const eventLinkForms = document.querySelectorAll('.js-cosplay-event-link');
		const portfolioList = document.getElementById('cosplay-portfolio-list');
		const eventSelectionList = document.getElementById('cosplay-event-selection-list');
		const bannerModal = document.getElementById('cosplay-banner-modal');
		const bannerCanvas = document.getElementById('cosplay-banner-canvas');
		const bannerList = document.getElementById('cosplay-banner-list');
		const bannerDownload = document.getElementById('cosplay-banner-download');
		const bannerRegenerate = document.getElementById('cosplay-banner-regenerate');
		const bannerClose = document.getElementById('cosplay-banner-close');
		const cosplayCount = document.querySelector('[data-cosplay-count]');
		const csrfToken = <?php echo json_encode((string) ($_SESSION['csrf_token'] ?? '')); ?>;

		if (!input || !results || !selectedLabel || !hiddenId || !saveForm) {
			return;
		}

		let debounceTimer = null;
		let activeItems = [];
		let activeEventItems = [];
		let selectedEvent = null;
		let selectedCharacter = null;
		let activeBannerData = null;
		let toastTimer = null;

		const escapeHtml = (value) => String(value ?? '')
			.replaceAll('&', '&amp;')
			.replaceAll('<', '&lt;')
			.replaceAll('>', '&gt;')
			.replaceAll('"', '&quot;')
			.replaceAll("'", '&#039;');

		const showToast = (message, success = true) => {
			let toast = document.getElementById('cosplay-toast');
			if (!toast) {
				toast = document.createElement('div');
				toast.id = 'cosplay-toast';
				toast.className = 'fixed bottom-5 right-5 z-50 rounded-xl px-4 py-3 text-sm font-bold shadow-xl opacity-0 transition-opacity';
				document.body.appendChild(toast);
			}

			toast.className = `fixed bottom-5 right-5 z-50 rounded-xl px-4 py-3 text-sm font-bold shadow-xl transition-opacity ${success ? 'bg-green-600 text-white' : 'bg-red-600 text-white'}`;
			toast.textContent = message;
			toast.style.opacity = '1';
			clearTimeout(toastTimer);
			toastTimer = setTimeout(() => {
				toast.style.opacity = '0';
			}, 2200);
		};

		const updateCount = () => {
			if (cosplayCount) {
				cosplayCount.textContent = String(document.querySelectorAll('[data-portfolio-card]').length);
			}
		};

		const formatBannerDate = (value) => {
			if (!value) {
				return '';
			}

			const date = new Date(value);
			return Number.isNaN(date.getTime()) ? '' : date.toLocaleDateString('it-IT', { day: '2-digit', month: 'long', year: 'numeric' });
		};

		const wrapText = (context, text, x, y, maxWidth, lineHeight) => {
			const words = String(text || '').split(' ');
			let line = '';
			let cursorY = y;

			words.forEach((word) => {
				const testLine = line ? `${line} ${word}` : word;
				if (context.measureText(testLine).width > maxWidth && line) {
					context.fillText(line, x, cursorY);
					line = word;
					cursorY += lineHeight;
				} else {
					line = testLine;
				}
			});

			if (line) {
				context.fillText(line, x, cursorY);
			}
		};

		const loadImage = (src) => new Promise((resolve) => {
			if (!src) {
				resolve(null);
				return;
			}

			const image = new Image();
			image.crossOrigin = 'anonymous';
			image.onload = () => resolve(image);
			image.onerror = () => resolve(null);
			image.src = src;
		});

		const renderBanner = async () => {
			if (!bannerCanvas || !activeBannerData) {
				return;
			}

			const context = bannerCanvas.getContext('2d');
			if (!context) {
				return;
			}

			const items = Array.isArray(activeBannerData.items) ? activeBannerData.items.slice(0, 4) : [];
			const format = 'square';
			const sizes = {
				square: { width: 1080, height: 1080 },
			};
			const size = sizes[format] || sizes.square;
			bannerCanvas.width = size.width;
			bannerCanvas.height = size.height;
			const logo = await loadImage('/public_assets/images/logo_italian_cosplay.webp');
			const loadedImages = [];
			for (const item of items) {
				loadedImages.push(await loadImage(item.reference_image || ''));
			}

			const width = bannerCanvas.width;
			const height = bannerCanvas.height;
			context.clearRect(0, 0, width, height);

			const gradient = context.createLinearGradient(0, 0, width, height);
			gradient.addColorStop(0, '#f8fafc');
			gradient.addColorStop(0.55, '#ecfdf5');
			gradient.addColorStop(1, '#fdf2f8');
			context.fillStyle = gradient;
			context.fillRect(0, 0, width, height);

			context.fillStyle = 'rgba(22,163,74,0.10)';
			context.beginPath();
			context.arc(width * 0.78, height * 0.14, 220, 0, Math.PI * 2);
			context.fill();
			context.fillStyle = 'rgba(217,70,239,0.10)';
			context.beginPath();
			context.arc(width * 0.1, height * 0.88, 180, 0, Math.PI * 2);
			context.fill();

			const frameInset = Math.round(Math.min(width, height) * 0.05);
			const contentX = frameInset + 30;
			const contentY = frameInset + 30;
			const contentWidth = width - (frameInset * 2) - 60;
			const contentHeight = height - (frameInset * 2) - 60;

			context.fillStyle = 'rgba(255,255,255,0.95)';
			context.fillRect(frameInset, frameInset, width - frameInset * 2, height - frameInset * 2);
			context.strokeStyle = 'rgba(16,185,129,0.15)';
			context.lineWidth = 4;
			context.strokeRect(frameInset, frameInset, width - frameInset * 2, height - frameInset * 2);

			if (logo) {
				const logoWidth = Math.min(240, contentWidth * 0.24);
				const logoHeight = logoWidth * 0.17;
				context.drawImage(logo, contentX, contentY, logoWidth, logoHeight);
			}

			const formatKey = 'square';
			const title = activeBannerData.title || 'Evento cosplay';
			const dateLabel = [formatBannerDate(activeBannerData.start), formatBannerDate(activeBannerData.end)].filter(Boolean).join(' - ');
			const nickname = `@<?php echo htmlspecialchars($currentUsername, ENT_QUOTES, 'UTF-8'); ?>`;
			const visibleItems = items.slice(0, 4);
			if (formatKey === 'square') {
				const squareItems = items.slice(0, 4);
				const darkBg = context.createLinearGradient(frameInset, frameInset, width - frameInset, height - frameInset);
				darkBg.addColorStop(0, '#06071f');
				darkBg.addColorStop(0.45, '#112b84');
				darkBg.addColorStop(1, '#6b21a8');
				context.fillStyle = darkBg;
				context.fillRect(frameInset, frameInset, width - frameInset * 2, height - frameInset * 2);

				context.fillStyle = 'rgba(255,255,255,0.05)';
				context.beginPath();
				context.arc(width * 0.16, height * 0.22, 180, 0, Math.PI * 2);
				context.fill();
				context.beginPath();
				context.arc(width * 0.84, height * 0.82, 240, 0, Math.PI * 2);
				context.fill();

				context.strokeStyle = 'rgba(96,165,250,0.95)';
				context.lineWidth = 3;
				context.shadowColor = 'rgba(59,130,246,0.85)';
				context.shadowBlur = 30;
				context.beginPath();
				context.moveTo(frameInset + 30, frameInset + 128);
				context.bezierCurveTo(width * 0.2, frameInset + 72, width * 0.52, frameInset + 164, width * 0.82, frameInset + 92);
				context.stroke();
				context.shadowBlur = 0;

				const leftX = contentX + 8;
				const leftWidth = Math.round(width * 0.31);
				const heroTop = contentY + 48;
				context.textAlign = 'left';
				context.fillStyle = '#ffffff';
				context.font = '800 52px "Arial Black", "Trebuchet MS", Arial, sans-serif';
				context.fillText(title, leftX, heroTop);

				const displayDate = dateLabel || 'Data evento';
				const dateFontSize = displayDate.length > 24 ? 18 : 22;
				const datePillWidth = Math.min(Math.max(320, displayDate.length * (dateFontSize > 18 ? 11 : 10) + 72), 420);
				const datePillY = contentY + 150;
				context.fillStyle = 'rgba(255,255,255,0.12)';
				if (typeof context.roundRect === 'function') {
					context.beginPath();
					context.roundRect(leftX, datePillY, datePillWidth, 56, 999);
					context.fill();
					context.strokeStyle = 'rgba(255,255,255,0.20)';
					context.lineWidth = 1.5;
					context.stroke();
				}
				context.fillStyle = '#dbeafe';
				context.font = `600 ${dateFontSize}px "Trebuchet MS", Arial, sans-serif`;
				context.fillText(displayDate, leftX + 24, datePillY + 35);

				context.fillStyle = '#8b5cf6';
				context.font = '700 20px "Trebuchet MS", Arial, sans-serif';
				context.fillText(`Cosplay Plan di : ${nickname}`, leftX, datePillY + 104);

				const footerTextY = height - frameInset - 62;
				context.fillStyle = 'rgba(255,255,255,0.86)';
				context.font = '500 16px "Trebuchet MS", Arial, sans-serif';
				context.fillText('Cosplay plan creato su italiancosplay.it', leftX, footerTextY);
				context.fillStyle = 'rgba(255,255,255,0.68)';
				context.font = '500 13px "Trebuchet MS", Arial, sans-serif';
				context.fillText('Crea il tuo e condividilo', leftX, footerTextY + 18);

				if (bannerList) {
					bannerList.innerHTML = squareItems.map((item) => `
						<div class="flex items-center gap-3 rounded-xl bg-white p-3 shadow-sm">
							${item.reference_image ? `<img src="${escapeHtml(item.reference_image)}" alt="" class="h-16 w-16 rounded-lg object-cover">` : '<div class="h-16 w-16 rounded-lg bg-gray-200"></div>'}
							<div class="min-w-0">
								<p class="truncate text-sm font-bold text-gray-950">${escapeHtml(item.label || 'Cosplay')}</p>
								<p class="text-xs font-semibold uppercase tracking-wide text-fuchsia-900">${item.status === 'forse' ? 'Forse lo porterò' : 'Porterò'}</p>
							</div>
						</div>
					`).join('');
				}

				const gap = squareItems.length >= 4 ? 10 : 16;
				const availableWidth = width - (frameInset * 2) - 48;
				const cardWidth = Math.max(132, Math.floor((availableWidth - ((squareItems.length - 1) * gap)) / squareItems.length));
				const cardHeight = Math.max(228, Math.round(cardWidth * 1.48));
				const totalGridWidth = (squareItems.length * cardWidth) + ((squareItems.length - 1) * gap);
				const gridLeftEdge = frameInset + 24;
				const gridRightEdge = width - frameInset - 24;
				const startX = Math.max(gridLeftEdge, Math.round(gridRightEdge - totalGridWidth));
				const startY = contentY + 300;
				squareItems.forEach((item, index) => {
					const x = startX + index * (cardWidth + gap);
					const y = startY;
					context.shadowColor = index === 1 ? 'rgba(59,130,246,0.65)' : 'rgba(34,197,94,0.35)';
					context.shadowBlur = 24;
					context.fillStyle = 'rgba(255,255,255,0.08)';
					if (typeof context.roundRect === 'function') {
						context.beginPath();
						context.roundRect(x, y, cardWidth, cardHeight, 24);
						context.fill();
					} else {
						context.fillRect(x, y, cardWidth, cardHeight);
					}
					context.shadowBlur = 0;

					const imageSize = Math.max(
						112,
						Math.min(
							cardWidth - 24,
							Math.round(cardWidth * (squareItems.length <= 2 ? 0.88 : 0.80))
						)
					);
					const imageX = x + Math.round((cardWidth - imageSize) / 2);
					const imageY = y + 16;
					const image = loadedImages[index];
					if (image) {
						context.save();
						context.shadowColor = 'rgba(0,0,0,0.25)';
						context.shadowBlur = 14;
						context.beginPath();
						if (typeof context.roundRect === 'function') {
							context.roundRect(imageX, imageY, imageSize, imageSize, 24);
						} else {
							context.rect(imageX, imageY, imageSize, imageSize);
						}
						context.clip();
						context.drawImage(image, imageX, imageY, imageSize, imageSize);
						context.restore();
					} else {
						context.fillStyle = 'rgba(255,255,255,0.22)';
						context.fillRect(imageX, imageY, imageSize, imageSize);
					}

					context.fillStyle = '#ffffff';
					context.font = '800 14px "Trebuchet MS", Arial, sans-serif';
					wrapText(context, item.label || 'Cosplay', x + 10, y + imageSize + 34, cardWidth - 20, 26);
					const statusText = item.status === 'forse' ? 'Forse lo porterò' : 'Porterò';
					context.fillStyle = 'rgba(255,255,255,0.16)';
					if (typeof context.roundRect === 'function') {
						context.beginPath();
						context.roundRect(x + 16, y + cardHeight - 34, cardWidth - 32, 22, 999);
						context.fill();
					}
					context.fillStyle = '#dbeafe';
					context.font = '600 11px "Trebuchet MS", Arial, sans-serif';
					context.textAlign = 'center';
					context.fillText(statusText, x + cardWidth / 2, y + cardHeight - 18);
					context.textAlign = 'left';
				});

				const logoBadgeWidth = 210;
				const logoBadgeHeight = 56;
				const logoBadgeX = width - frameInset - 24 - logoBadgeWidth;
				const logoBadgeY = height - frameInset - 72;
				context.fillStyle = 'rgba(255,255,255,0.96)';
				if (typeof context.roundRect === 'function') {
					context.beginPath();
					context.roundRect(logoBadgeX, logoBadgeY, logoBadgeWidth, logoBadgeHeight, 18);
					context.fill();
				} else {
					context.fillRect(logoBadgeX, logoBadgeY, logoBadgeWidth, logoBadgeHeight);
				}
				if (logo) {
					context.drawImage(logo, logoBadgeX + 16, logoBadgeY + 12, 178, 30);
				}

				return;
			}

			const hero = {
				square: { imageSize: 214, titleY: 154, dateY: 220, nicknameY: 272, bandY: 92, bandH: 172, columns: 2, cardGap: 26, gridTop: 324, cardWidth: 302, cardHeight: 318, gridMaxItems: 4 },
			}[formatKey] || { imageSize: 214, titleY: 154, dateY: 220, nicknameY: 272, bandY: 92, bandH: 172, columns: 2, cardGap: 26, gridTop: 324, cardWidth: 302, cardHeight: 318, gridMaxItems: 4 };

			context.textAlign = 'center';
			context.fillStyle = '#111827';
			context.font = '800 52px "Arial Black", "Trebuchet MS", Arial, sans-serif';
			wrapText(context, title, width / 2, hero.titleY, Math.min(contentWidth * 0.80, 820), 52);

			const datePillWidth = Math.min(Math.max(330, dateLabel.length * 13 + 78), width - (contentX * 2));
			const datePillX = Math.round((width - datePillWidth) / 2);
			const datePillY = hero.dateY - 28;
			context.fillStyle = 'rgba(16,185,129,0.10)';
			if (typeof context.roundRect === 'function') {
				context.beginPath();
				context.roundRect(datePillX, datePillY, datePillWidth, 58, 999);
				context.fill();
				context.strokeStyle = 'rgba(16,185,129,0.18)';
				context.lineWidth = 2;
				context.stroke();
			} else {
				context.fillRect(datePillX, datePillY, datePillWidth, 58);
			}
			context.fillStyle = '#374151';
			context.font = '500 30px Arial, sans-serif';
			context.fillText(dateLabel || 'Data evento', width / 2, hero.dateY);

			context.fillStyle = '#16a34a';
			context.font = '700 25px Arial, sans-serif';
			context.fillText(nickname, width / 2, hero.nicknameY);
			context.textAlign = 'left';

			if (bannerList) {
				bannerList.innerHTML = visibleItems.map((item) => `
					<div class="flex items-center gap-3 rounded-xl bg-white p-3 shadow-sm">
						${item.reference_image ? `<img src="${escapeHtml(item.reference_image)}" alt="" class="h-16 w-16 rounded-lg object-cover">` : '<div class="h-16 w-16 rounded-lg bg-gray-200"></div>'}
						<div class="min-w-0">
							<p class="truncate text-sm font-bold text-gray-950">${escapeHtml(item.label || 'Cosplay')}</p>
							<p class="text-xs font-semibold uppercase tracking-wide text-fuchsia-900">${item.status === 'forse' ? 'Forse lo porterò' : 'Porterò'}</p>
						</div>
					</div>
				`).join('');
			}

			const bandX = frameInset + 24;
			const bandWidth = width - ((frameInset + 24) * 2);
			context.fillStyle = 'rgba(16,185,129,0.07)';
			if (typeof context.beginPath === 'function') {
				context.beginPath();
				context.moveTo(bandX, hero.bandY);
				context.lineTo(bandX + bandWidth, hero.bandY - 18);
				context.lineTo(bandX + bandWidth, hero.bandY + hero.bandH - 10);
				context.lineTo(bandX, hero.bandY + hero.bandH + 6);
				context.closePath();
				context.fill();
			}
			context.fillStyle = 'rgba(124,58,237,0.06)';
			if (typeof context.beginPath === 'function') {
				context.beginPath();
				context.moveTo(bandX, hero.bandY + 22);
				context.lineTo(bandX + bandWidth * 0.56, hero.bandY - 2);
				context.lineTo(bandX + bandWidth, hero.bandY + hero.bandH - 24);
				context.lineTo(bandX + bandWidth * 0.46, hero.bandY + hero.bandH + 4);
				context.closePath();
				context.fill();
			}

			const totalGridWidth = (hero.columns * hero.cardWidth) + ((hero.columns - 1) * hero.cardGap);
			const startX = Math.round((width - totalGridWidth) / 2);
			const startY = hero.gridTop;
			visibleItems.forEach((item, index) => {
				const col = index % hero.columns;
				const row = Math.floor(index / hero.columns);
				const x = startX + col * (hero.cardWidth + hero.cardGap);
				const y = startY + row * (hero.cardHeight + 22);

				context.fillStyle = 'rgba(255,255,255,0.86)';
				if (typeof context.roundRect === 'function') {
					context.beginPath();
					context.roundRect(x, y, hero.cardWidth, hero.cardHeight, 26);
					context.fill();
					context.strokeStyle = 'rgba(16,185,129,0.10)';
					context.lineWidth = 2;
					context.stroke();
				} else {
					context.fillRect(x, y, hero.cardWidth, hero.cardHeight);
				}

				const image = loadedImages[index];
				const imageSize = hero.imageSize;
				const imageX = x + Math.round((hero.cardWidth - imageSize) / 2);
				const imageY = y + 18;
				if (image) {
					context.save();
					context.beginPath();
					if (typeof context.roundRect === 'function') {
						context.roundRect(imageX, imageY, imageSize, imageSize, 30);
					} else {
						context.rect(imageX, imageY, imageSize, imageSize);
					}
					context.clip();
					context.drawImage(image, imageX, imageY, imageSize, imageSize);
					context.restore();
				} else {
					context.fillStyle = 'rgba(243,244,246,0.95)';
					context.fillRect(imageX, imageY, imageSize, imageSize);
				}

				const labelY = imageY + imageSize + 28;
				context.fillStyle = '#111827';
				context.font = hero.columns === 1 ? '700 28px Arial, sans-serif' : '700 22px Arial, sans-serif';
				wrapText(context, item.label || 'Cosplay', x + 18, labelY, hero.cardWidth - 36, hero.columns === 1 ? 30 : 24);

				const statusText = item.status === 'forse' ? 'Forse lo porterò' : 'Porterò';
				const badgeWidth = Math.min(hero.cardWidth - 52, Math.max(132, statusText.length * 8 + 40));
				const badgeX = Math.round(x + (hero.cardWidth - badgeWidth) / 2);
				const badgeY = y + hero.cardHeight - 42;
				context.fillStyle = item.status === 'forse' ? 'rgba(124,58,237,0.10)' : 'rgba(16,185,129,0.10)';
				if (typeof context.roundRect === 'function') {
					context.beginPath();
					context.roundRect(badgeX, badgeY, badgeWidth, 32, 999);
					context.fill();
				} else {
					context.fillRect(badgeX, badgeY, badgeWidth, 32);
				}
				context.fillStyle = item.status === 'forse' ? '#7c3aed' : '#16a34a';
				context.font = '600 15px Arial, sans-serif';
				context.textAlign = 'center';
				context.fillText(statusText, x + hero.cardWidth / 2, badgeY + 21);
				context.textAlign = 'left';
			});

			context.fillStyle = '#111827';
			context.font = '600 20px Arial, sans-serif';
			context.fillText('ItalianCosplay.it', contentX, height - (frameInset + 46));
			context.fillStyle = '#6b7280';
			context.font = '500 16px Arial, sans-serif';
			context.fillText('Condividi il tuo piano cosplay con stile', contentX, height - (frameInset + 20));
		};

		const openBannerModal = async (button) => {
			if (!bannerModal) {
				return;
			}

			try {
				activeBannerData = {
					title: button.dataset.bannerTitle || 'Evento cosplay',
					slug: button.dataset.bannerSlug || '',
					start: button.dataset.bannerStart || '',
					end: button.dataset.bannerEnd || '',
					items: JSON.parse(button.dataset.bannerItems || '[]'),
				};
			} catch (error) {
				activeBannerData = null;
			}

			if (!activeBannerData) {
				showToast('Impossibile preparare il banner.', false);
				return;
			}

			bannerModal.classList.remove('hidden');
			bannerModal.classList.add('flex');
			await renderBanner();
		};

		const renderPortfolioCard = (item) => {
			const title = escapeHtml(item.custom_name || item.name_full || 'Cosplay');
			const subtitle = escapeHtml([item.name_full, item.name_native].filter(Boolean).join(' · '));
			const animeTitles = escapeHtml(item.anime_titles || '');
			const notes = escapeHtml(item.notes || '');
			const image = item.reference_image ? `/public_assets${item.reference_image}` : (item.image_large || '');
			const isPublic = Number(item.is_public) === 1;
			const badgeClass = isPublic ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700';
			const actionLabel = isPublic ? 'Rendi privato' : 'Rendi pubblico';
			const nextValue = isPublic ? 0 : 1;

			return `
				<article class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 shadow-sm" data-portfolio-card data-portfolio-id="${item.id}">
					${image ? `<img src="${escapeHtml(image)}" alt="${title}" class="h-44 w-full object-cover">` : ''}
					<div class="p-5">
						<p class="text-xs font-semibold uppercase tracking-wide text-fuchsia-900">Anilist</p>
						<h3 class="mt-2 text-lg font-bold text-gray-950">${title}</h3>
						<p class="mt-1 text-sm text-gray-600">${subtitle}</p>
						<p class="mt-1 text-sm text-gray-600">${animeTitles}</p>
						${notes ? `<p class="mt-3 text-sm leading-relaxed text-gray-700">${notes}</p>` : ''}
						<div class="mt-4 flex flex-wrap items-center gap-2">
							<span data-visibility-badge class="rounded-full px-3 py-1 text-xs font-bold ${badgeClass}">${isPublic ? 'Pubblico' : 'Privato'}</span>
							<form method="post" action="/dashboard/cosplay/toggle-visibility" class="js-cosplay-visibility-toggle">
								<input type="hidden" name="csrf_token" value="${escapeHtml(csrfToken)}">
								<input type="hidden" name="portfolio_id" value="${item.id}">
								<input type="hidden" name="is_public" value="${nextValue}">
								<button type="submit" class="js-cosplay-visibility-button inline-flex rounded-lg border border-fuchsia-200 bg-fuchsia-50 px-4 py-2 text-xs font-bold text-fuchsia-800 hover:bg-fuchsia-100">
									${actionLabel}
								</button>
							</form>
							<form method="post" action="/dashboard/cosplay/delete" class="js-cosplay-delete" data-confirm="Vuoi rimuovere questo cosplay dal portfolio?">
								<input type="hidden" name="csrf_token" value="${escapeHtml(csrfToken)}">
								<input type="hidden" name="portfolio_id" value="${item.id}">
								<button type="submit" class="inline-flex rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-xs font-bold text-red-800 hover:bg-red-100">
									Elimina
								</button>
							</form>
						</div>
					</div>
				</article>
			`;
		};

		const renderEventSelectionCard = (selection) => {
			const title = escapeHtml(selection.titolo || 'Evento');
			const label = escapeHtml(selection.custom_name || selection.name_full || 'Cosplay');
			const status = escapeHtml(selection.status || '');
			const slug = escapeHtml(selection.slug || '');

			return `
				<article class="rounded-2xl border border-gray-200 bg-gray-50 p-5 shadow-sm" data-event-selection-card data-event-id="${selection.event_id}">
					<p class="text-xs font-semibold uppercase tracking-wide text-fuchsia-900">${status}</p>
					<h3 class="mt-2 text-lg font-bold text-gray-950">
						<a href="/eventi-cosplay/${slug}" class="hover:text-fuchsia-800 hover:underline">${title}</a>
					</h3>
					<p class="mt-2 text-sm text-gray-700">${label}</p>
					<div class="mt-4 flex flex-wrap items-center justify-between gap-3">
						<a href="/eventi-cosplay/${slug}" class="inline-flex rounded-lg bg-fuchsia-800 px-4 py-2 text-sm font-bold text-white hover:bg-fuchsia-900">Apri evento</a>
						<form method="post" action="/dashboard/cosplay/event/remove" class="js-cosplay-event-remove" data-confirm="Vuoi rimuovere questo collegamento?">
							<input type="hidden" name="csrf_token" value="${escapeHtml(csrfToken)}">
							<input type="hidden" name="event_id" value="${selection.event_id}">
							<input type="hidden" name="redirect_to" value="/dashboard/cosplay">
							<button type="submit" class="inline-flex rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-bold text-red-800 hover:bg-red-100">Rimuovi</button>
						</form>
					</div>
				</article>
			`;
		};

		const renderEventResults = (items) => {
			activeEventItems = items;
			if (!eventSearchResults || !eventSelectedLabel) {
				return;
			}

			if (!items.length) {
				eventSearchResults.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500">Nessun evento trovato.</div>';
				eventSearchResults.classList.remove('hidden');
				return;
			}

			eventSearchResults.innerHTML = items.map((item, index) => `
				<button type="button" data-index="${index}" class="cosplay-event-option flex w-full items-center gap-3 border-b border-gray-100 px-4 py-3 text-left hover:bg-fuchsia-50">
					<div class="min-w-0">
						<p class="font-semibold text-gray-950">${escapeHtml(item.titolo || 'Evento')}</p>
						<p class="truncate text-xs text-gray-500">${escapeHtml([item.data_label, item.location].filter(Boolean).join(' · '))}</p>
					</div>
				</button>
			`).join('');
			eventSearchResults.classList.remove('hidden');
		};

		const renderCurrentEventSelections = (selections) => {
			if (!eventCurrentSelections) {
				return;
			}

			const current = Array.isArray(selections) ? selections : [];
			if (!current.length) {
				eventCurrentSelections.innerHTML = '';
				eventCurrentSelections.classList.add('hidden');
				return;
			}

			eventCurrentSelections.classList.remove('hidden');
			eventCurrentSelections.innerHTML = current.map((selection) => `
				<div class="flex items-center justify-between gap-3 rounded-xl border border-fuchsia-200 bg-white p-3" data-event-selection-current-item>
					<div class="min-w-0">
						<p class="truncate text-sm font-semibold text-gray-900">${escapeHtml(selection.custom_name || selection.name_full || 'Cosplay')}</p>
						<p class="text-xs font-semibold uppercase tracking-wide text-fuchsia-900">${selection.status === 'forse' ? 'Forse lo porterò' : 'Porterò'}</p>
					</div>
					<form method="post" action="/dashboard/cosplay/event/remove" class="js-cosplay-event-remove-current" data-confirm="Vuoi rimuovere questo cosplay da questo evento?">
						<input type="hidden" name="csrf_token" value="${escapeHtml(csrfToken)}">
						<input type="hidden" name="event_id" value="${selectedEvent ? selectedEvent.id : ''}">
						<input type="hidden" name="portfolio_id" value="${selection.portfolio_id}">
						<input type="hidden" name="redirect_to" value="/dashboard/cosplay">
						<button type="submit" class="inline-flex rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-800 hover:bg-red-100">Rimuovi</button>
					</form>
				</div>
			`).join('');
		};

		const searchEvents = async (query) => {
			if (!eventSearchResults) {
				return;
			}

			if (query.trim().length < 2) {
				eventSearchResults.innerHTML = '';
				eventSearchResults.classList.add('hidden');
				return;
			}

			const response = await fetch(`/dashboard/cosplay/events?q=${encodeURIComponent(query)}`, {
				headers: { 'Accept': 'application/json' },
			});
			const data = await response.json().catch(() => ({}));
			renderEventResults(Array.isArray(data.items) ? data.items : []);
		};

		const renderGroupedEventSelections = (selections) => {
			if (!eventSelectionList) {
				return;
			}

			const grouped = new Map();
			(selections || []).forEach((selection) => {
				const eventId = String(selection.event_id || '');
				if (!eventId) {
					return;
				}

				if (!grouped.has(eventId)) {
					grouped.set(eventId, {
						event_id: selection.event_id,
						titolo: selection.titolo || 'Evento',
						slug: selection.slug || '',
						data_inizio: selection.data_inizio || '',
						data_fine: selection.data_fine || '',
						items: [],
					});
				}

				grouped.get(eventId).items.push(selection);
			});

			eventSelectionList.innerHTML = '';
			if (!grouped.size) {
				const emptyState = document.createElement('div');
				emptyState.className = 'mt-4 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-gray-600';
				emptyState.textContent = 'Non hai ancora collegato nessun cosplay agli eventi.';
				eventSelectionList.appendChild(emptyState);
				return;
			}

			for (const group of grouped.values()) {
				const card = document.createElement('article');
				card.className = 'rounded-2xl border border-gray-200 bg-gray-50 p-5 shadow-sm';
				card.dataset.eventSelectionCard = '1';
				card.dataset.eventId = String(group.event_id || '');

				const dateParts = [];
				if (group.data_inizio) {
					dateParts.push(new Date(group.data_inizio).toLocaleDateString('it-IT'));
				}
				if (group.data_fine && group.data_fine !== group.data_inizio) {
					dateParts.push(new Date(group.data_fine).toLocaleDateString('it-IT'));
				}

				card.innerHTML = `
					<div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
						<div>
							<p class="text-xs font-semibold uppercase tracking-wide text-fuchsia-900">${group.items.length} cosplay associati</p>
							<h3 class="mt-2 text-lg font-bold text-gray-950">
								<a href="/eventi-cosplay/${escapeHtml(group.slug)}" class="hover:text-fuchsia-800 hover:underline">${escapeHtml(group.titolo)}</a>
							</h3>
							${dateParts.length ? `<p class="mt-1 text-sm text-gray-600">${escapeHtml(dateParts.join(' - '))}</p>` : ''}
						</div>
						<div class="flex flex-wrap gap-2">
							<a href="/eventi-cosplay/${escapeHtml(group.slug)}" class="inline-flex rounded-lg bg-fuchsia-800 px-4 py-2 text-sm font-bold text-white hover:bg-fuchsia-900">Apri evento</a>
								<form method="post" action="/dashboard/cosplay/event/remove" class="js-cosplay-event-remove-group" data-confirm="Vuoi rimuovere tutte le associazioni di questo evento?">
								<input type="hidden" name="csrf_token" value="${escapeHtml(csrfToken)}">
								<input type="hidden" name="event_id" value="${group.event_id}">
								<input type="hidden" name="redirect_to" value="/dashboard/cosplay">
								<button type="submit" class="inline-flex rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-bold text-red-800 hover:bg-red-100">Rimuovi tutto</button>
							</form>
						</div>
					</div>
					<div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
						${group.items.map((selection) => `
							<div class="rounded-xl border border-white bg-white p-4">
								<div class="flex items-start justify-between gap-3">
									<div>
										<p class="text-sm font-semibold text-gray-900">${escapeHtml(selection.custom_name || selection.name_full || 'Cosplay')}</p>
										<p class="mt-1 text-xs font-semibold uppercase tracking-wide text-fuchsia-900">${selection.status === 'forse' ? 'Forse lo porterò' : 'Porterò'}</p>
									</div>
										<form method="post" action="/dashboard/cosplay/event/remove" class="js-cosplay-event-remove-current" data-confirm="Vuoi rimuovere questo cosplay da questo evento?">
										<input type="hidden" name="csrf_token" value="${escapeHtml(csrfToken)}">
										<input type="hidden" name="event_id" value="${group.event_id}">
										<input type="hidden" name="portfolio_id" value="${selection.portfolio_id}">
										<input type="hidden" name="redirect_to" value="/dashboard/cosplay">
										<button type="submit" class="inline-flex rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-800 hover:bg-red-100">Rimuovi</button>
									</form>
								</div>
							</div>
						`).join('')}
					</div>
				`;

				eventSelectionList.appendChild(card);
			}
		};

		if (eventSearchInput && eventSearchResults && eventSelectedLabel) {
			eventSearchInput.addEventListener('input', () => {
				selectedEvent = null;
				eventSelectedLabel.textContent = 'Nessun evento selezionato.';
				renderCurrentEventSelections([]);
				eventLinkForms.forEach((form) => {
					const hidden = form.querySelector('input[name="event_id"]');
					if (hidden) {
						hidden.value = '';
					}
				});
				clearTimeout(debounceTimer);
				debounceTimer = setTimeout(() => searchEvents(eventSearchInput.value), 250);
			});

			eventSearchResults.addEventListener('click', (event) => {
				const button = event.target.closest('.cosplay-event-option');
				if (!button) {
					return;
				}

				const index = Number(button.dataset.index ?? -1);
				const item = activeEventItems[index];
				if (!item) {
					return;
				}

				selectedEvent = item;
				eventSearchInput.value = item.titolo || '';
				eventSelectedLabel.textContent = `Evento selezionato: ${item.titolo || 'Evento'}${item.data_label ? ' - ' + item.data_label : ''}`;
				renderCurrentEventSelections([]);
				eventLinkForms.forEach((form) => {
					const hidden = form.querySelector('input[name="event_id"]');
					if (hidden) {
						hidden.value = item.id;
					}
				});
				eventSearchResults.classList.add('hidden');
			});

			document.addEventListener('click', (event) => {
				if (!eventSearchResults.contains(event.target) && event.target !== eventSearchInput) {
					eventSearchResults.classList.add('hidden');
				}
			});
		}

		document.addEventListener('click', async (event) => {
			const button = event.target.closest('.js-cosplay-banner-open');
			if (!button) {
				return;
			}

			event.preventDefault();
			await openBannerModal(button);
		});

		if (bannerClose && bannerModal) {
			bannerClose.addEventListener('click', () => {
				bannerModal.classList.add('hidden');
				bannerModal.classList.remove('flex');
			});
		}

		if (bannerModal) {
			bannerModal.addEventListener('click', (event) => {
				if (event.target === bannerModal) {
					bannerModal.classList.add('hidden');
					bannerModal.classList.remove('flex');
				}
			});
		}

		if (bannerRegenerate) {
			bannerRegenerate.addEventListener('click', renderBanner);
		}

		if (bannerDownload) {
			bannerDownload.addEventListener('click', () => {
				if (!bannerCanvas) {
					return;
				}

				const slug = activeBannerData?.slug || 'evento';
				const link = document.createElement('a');
				link.href = bannerCanvas.toDataURL('image/png');
				link.download = `banner-cosplay-${slug}.png`;
				link.click();
			});
		}

		const handleAjaxForm = async (form, onSuccess) => {
			const confirmText = form.dataset.confirm;
			if (confirmText && !window.confirm(confirmText)) {
				return;
			}

			const response = await fetch(form.action, {
				method: 'POST',
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					'Accept': 'application/json',
				},
				body: new FormData(form),
			});
			const data = await response.json().catch(() => ({}));
			if (!response.ok || !data.success) {
				showToast(data.message || 'Operazione non riuscita.', false);
				return;
			}

			onSuccess(data);
			showToast(data.message || 'Operazione completata.');
		};

		const renderResults = (items) => {
			activeItems = items;
			if (!items.length) {
				results.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500">Nessun personaggio trovato.</div>';
				results.classList.remove('hidden');
				return;
			}

			results.innerHTML = items.map((item, index) => {
				const name = `${item.name_full || 'Personaggio'}${item.name_native ? ' · ' + item.name_native : ''}`;
				const series = item.anime_titles || 'Serie non disponibile';
				const image = item.image_large || '';
				return `
					<button type="button" data-index="${index}" class="cosplay-character-option flex w-full items-center gap-3 border-b border-gray-100 px-4 py-3 text-left hover:bg-fuchsia-50">
						${image ? `<img src="${image}" alt="" class="h-12 w-12 rounded-lg object-cover">` : '<div class="h-12 w-12 rounded-lg bg-gray-200"></div>'}
						<div class="min-w-0">
							<p class="font-semibold text-gray-950">${name}</p>
							<p class="truncate text-xs text-gray-500">${series}</p>
						</div>
					</button>
				`;
			}).join('');
			results.classList.remove('hidden');
		};

		const searchCharacters = async (query) => {
			if (query.trim().length < 2) {
				results.innerHTML = '';
				results.classList.add('hidden');
				return;
			}

			const response = await fetch(`/dashboard/cosplay/characters?q=${encodeURIComponent(query)}`, {
				headers: { 'Accept': 'application/json' },
			});
			const data = await response.json();
			renderResults(Array.isArray(data.items) ? data.items : []);
		};

		input.addEventListener('input', () => {
			hiddenId.value = '';
			selectedLabel.textContent = 'Nessun personaggio selezionato.';
			clearTimeout(debounceTimer);
			debounceTimer = setTimeout(() => searchCharacters(input.value), 250);
		});

		results.addEventListener('click', (event) => {
			const button = event.target.closest('.cosplay-character-option');
			if (!button) {
				return;
			}
			const index = Number(button.dataset.index ?? -1);
			const item = activeItems[index];
			if (!item) {
				return;
			}

			selectedCharacter = item;
			hiddenId.value = item.id;
			input.value = `${item.name_full || 'Personaggio'}${item.name_native ? ' · ' + item.name_native : ''}`;
			selectedLabel.textContent = `${input.value} - ${item.anime_titles || 'Serie non disponibile'}`;
			results.classList.add('hidden');
		});

		document.addEventListener('click', (event) => {
			if (!results.contains(event.target) && event.target !== input) {
				results.classList.add('hidden');
			}
		});

		document.addEventListener('submit', async (event) => {
			const form = event.target;
			if (!(form instanceof HTMLFormElement)) {
				return;
			}

			if (form.classList.contains('js-cosplay-save')) {
				event.preventDefault();
				if (!selectedCharacter || !hiddenId.value) {
					showToast('Seleziona un personaggio prima di salvare.', false);
					return;
				}

				const response = await fetch(form.action, {
					method: 'POST',
					headers: {
						'X-Requested-With': 'XMLHttpRequest',
						'Accept': 'application/json',
					},
					body: new FormData(form),
				});
				const data = await response.json().catch(() => ({}));
				if (!response.ok || !data.success || !data.portfolioItem) {
					showToast(data.message || 'Impossibile salvare il cosplay.', false);
					return;
				}

				portfolioList?.insertAdjacentHTML('afterbegin', renderPortfolioCard(data.portfolioItem));
				updateCount();
				form.reset();
				hiddenId.value = '';
				selectedCharacter = null;
				selectedLabel.textContent = 'Nessun personaggio selezionato.';
				showToast(data.message || 'Cosplay salvato nel portfolio.');
				return;
			}

			if (form.classList.contains('js-cosplay-event-link')) {
				event.preventDefault();
				if (!selectedEvent || !form.querySelector('input[name="event_id"]')?.value) {
					showToast('Seleziona prima un evento.', false);
					return;
				}

				await handleAjaxForm(form, (data) => {
					renderGroupedEventSelections(Array.isArray(data.selections) ? data.selections : []);
					renderCurrentEventSelections(Array.isArray(data.selection) ? data.selection : []);
					eventSelectedLabel.textContent = `Evento selezionato: ${selectedEvent.titolo || 'Evento'} - Associazione salvata con successo.`;
				});
				return;
			}

			if (form.classList.contains('js-cosplay-event-remove-current')) {
				event.preventDefault();
				await handleAjaxForm(form, (data) => {
					renderCurrentEventSelections(Array.isArray(data.selection) ? data.selection : []);
					renderGroupedEventSelections(Array.isArray(data.selections) ? data.selections : []);
				});
				return;
			}

			if (form.classList.contains('js-cosplay-delete') || form.classList.contains('js-cosplay-event-remove-group')) {
				event.preventDefault();
				await handleAjaxForm(form, (data) => {
					if (form.classList.contains('js-cosplay-delete')) {
						form.closest('[data-portfolio-card]')?.remove();
						updateCount();
					}
					if (form.classList.contains('js-cosplay-event-remove-group')) {
						renderGroupedEventSelections(Array.isArray(data.selections) ? data.selections : []);
						renderCurrentEventSelections([]);
					}
				});
				return;
			}

			if (!form.classList.contains('js-cosplay-visibility-toggle')) {
				return;
			}

			event.preventDefault();
			const button = form.querySelector('.js-cosplay-visibility-button');
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
					throw new Error(result.message || 'Impossibile aggiornare la visibilità.');
				}

				const card = form.closest('[data-portfolio-card]');
				if (card && button) {
					const badge = card.querySelector('[data-visibility-badge]');
					if (badge) {
						const isPublic = Number(result.is_public) === 1;
						badge.textContent = isPublic ? 'Pubblico' : 'Privato';
						badge.className = `rounded-full px-3 py-1 text-xs font-bold ${isPublic ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'}`;
					}
					button.textContent = Number(result.is_public) === 1 ? 'Rendi privato' : 'Rendi pubblico';
					const hidden = form.querySelector('input[name="is_public"]');
					if (hidden) {
						hidden.value = Number(result.is_public) === 1 ? '0' : '1';
					}
				}
				showToast(result.message || 'Visibilità aggiornata.');
			} catch (error) {
				showToast(error.message || 'Impossibile aggiornare la visibilità.', false);
			} finally {
				if (button) {
					button.disabled = false;
				}
			}
		});

	});
</script>
