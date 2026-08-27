<?php
/**
 * AdminList No Results Component
 *
 * Visualizzato quando ricerca o filtri
 * non restituiscono risultati.
 *
 * Configuration:
 *
 * $config['noResults'] = [
 *     'title' => '',
 *     'message' => ''
 * ];
 *
 */


$noResults = $config['noResults'] ?? [];


$title =
	$noResults['title']
	?? 'Nessun risultato trovato';


$message =
	$noResults['message']
	?? 'Prova a modificare i criteri di ricerca o rimuovere alcuni filtri.';


?>


<div
		class="
		hidden
		px-6
		py-12
		text-center
	"
		data-admin-list-no-results
		role="status"
		aria-live="polite"
>


	<div class="mx-auto flex max-w-sm flex-col items-center">


		<div
				class="
				mb-4
				flex
				h-12
				w-12
				items-center
				justify-center
				rounded-full
				bg-gray-100
			"
		>


			<svg
					class="h-6 w-6 text-gray-400"
					xmlns="http://www.w3.org/2000/svg"
					fill="none"
					viewBox="0 0 24 24"
					stroke="currentColor"
					aria-hidden="true"
			>

				<path
						stroke-linecap="round"
						stroke-linejoin="round"
						stroke-width="2"
						d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z"
				/>

			</svg>


		</div>



		<h3
				class="
				text-base
				font-semibold
				text-gray-900
			"
		>

			<?= htmlspecialchars(
				$title,
				ENT_QUOTES,
				'UTF-8'
			) ?>

		</h3>



		<p
				class="
				mt-2
				text-sm
				text-gray-500
			"
		>

			<?= htmlspecialchars(
				$message,
				ENT_QUOTES,
				'UTF-8'
			) ?>

		</p>



		<button
				type="button"
				data-admin-list-clear-filters
				class="
				mt-6
				inline-flex
				items-center
				rounded-lg
				border
				border-gray-300
				bg-white
				px-4
				py-2
				text-sm
				font-medium
				text-gray-700
				hover:bg-gray-50
				focus:outline-none
				focus:ring-2
				focus:ring-primary-500
			"
		>

			Rimuovi filtri

		</button>


	</div>


</div>
