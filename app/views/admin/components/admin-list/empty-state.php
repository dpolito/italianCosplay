<?php
/**
 * AdminList Empty State Component
 *
 * Visualizzato quando non esistono record.
 *
 * Configuration:
 *
 * $config['emptyState'] = [
 *     'title' => '',
 *     'message' => '',
 *     'action' => [
 *          'url' => '',
 *          'label' => ''
 *     ]
 * ];
 *
 */


$emptyState = $config['emptyState'] ?? [];


$title =
	$emptyState['title']
	?? 'Nessun elemento presente';


$message =
	$emptyState['message']
	?? 'Non sono ancora presenti elementi da visualizzare.';


$action =
	$emptyState['action']
	?? null;


?>


<div
		class="
		hidden
		px-6
		py-12
		text-center
	"
		data-admin-list-empty-state
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
						d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0H4"
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



		<?php if ($action): ?>


			<a
					href="<?= htmlspecialchars(
						$action['url'] ?? '#',
						ENT_QUOTES,
						'UTF-8'
					) ?>"
					class="
					mt-6
					inline-flex
					items-center
					rounded-lg
					bg-primary-600
					px-4
					py-2
					text-sm
					font-medium
					text-white
					hover:bg-primary-700
					focus:outline-none
					focus:ring-2
					focus:ring-primary-500
				"
			>

				<?= htmlspecialchars(
					$action['label'] ?? 'Aggiungi',
					ENT_QUOTES,
					'UTF-8'
				) ?>

			</a>


		<?php endif; ?>


	</div>


</div>
