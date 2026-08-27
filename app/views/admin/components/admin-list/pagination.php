<?php
/**
 * AdminList Pagination Component
 *
 * La paginazione viene aggiornata dinamicamente
 * tramite admin-list.js
 *
 * Required:
 * $config['id']
 */

$listId = htmlspecialchars(
	$config['id'],
	ENT_QUOTES,
	'UTF-8'
);

?>

<div
	class="flex flex-col gap-4 border-t border-gray-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
	data-admin-list-pagination
>

	<div class="text-sm text-gray-600">

        <span data-admin-list-total>
            0
        </span>

		risultati

	</div>


	<div class="flex items-center justify-between gap-4">

		<div class="flex items-center gap-2">

			<label
				for="<?= $listId ?>-page-size"
				class="text-sm text-gray-600"
			>
				Mostra
			</label>


			<select
				id="<?= $listId ?>-page-size"
				data-admin-list-page-size
				class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
			>

				<option value="10">
					10
				</option>

				<option value="25" selected>
					25
				</option>

				<option value="50">
					50
				</option>

				<option value="100">
					100
				</option>

			</select>

		</div>


		<nav
			aria-label="Paginazione"
			class="inline-flex items-center rounded-lg shadow-sm"
			data-admin-list-pages
		>

			<button
				type="button"
				data-admin-list-page-prev
				class="inline-flex items-center rounded-l-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-500 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
			>
				←
				<span class="sr-only">
                    Pagina precedente
                </span>
			</button>


			<div
				class="flex"
				data-admin-list-page-numbers
			>

				<!-- Pagine generate da JavaScript -->

			</div>


			<button
				type="button"
				data-admin-list-page-next
				class="inline-flex items-center rounded-r-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-500 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
			>
				→
				<span class="sr-only">
                    Pagina successiva
                </span>
			</button>


		</nav>


	</div>

</div>
