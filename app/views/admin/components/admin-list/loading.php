<?php
/**
 * AdminList Loading Component
 *
 * Stato visualizzato durante il caricamento AJAX.
 *
 * Il contenuto viene mostrato/nascosto
 * tramite admin-list.js.
 */

?>

<div
	class="hidden rounded-lg border border-gray-200 bg-white p-6"
	data-admin-list-loading-state
>

	<div class="space-y-4">

		<div class="flex items-center justify-center py-4">

			<svg
				class="h-6 w-6 animate-spin text-gray-400"
				xmlns="http://www.w3.org/2000/svg"
				fill="none"
				viewBox="0 0 24 24"
				aria-hidden="true"
			>

				<circle
					class="opacity-25"
					cx="12"
					cy="12"
					r="10"
					stroke="currentColor"
					stroke-width="4"
				></circle>

				<path
					class="opacity-75"
					fill="currentColor"
					d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
				></path>

			</svg>

		</div>


		<div class="space-y-3">

			<div class="h-4 w-3/4 animate-pulse rounded bg-gray-200"></div>

			<div class="h-4 w-1/2 animate-pulse rounded bg-gray-200"></div>

			<div class="h-4 w-2/3 animate-pulse rounded bg-gray-200"></div>

		</div>

	</div>

</div>
