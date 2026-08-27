<?php

/**
 * AdminList Table Component
 *
 * Render struttura HTML della tabella.
 *
 * I dati vengono caricati dinamicamente
 * tramite admin-list.js.
 *
 * Required:
 *
 * $adminList['columns']
 * $adminList['actions']
 *
 */


$columns =
	$adminList['columns'] ?? [];


$hasActions =
	!empty($adminList['actions']);

?>


<div class="overflow-x-auto">


	<table class="
	w-full
	table-fixed
	border-collapse
	divide-y
	divide-gray-200
">
		<colgroup>

			<?php foreach ($columns as $column): ?>

				<col
						style="width: <?= htmlspecialchars(
							$column['width'] ?? 'auto',
							ENT_QUOTES,
							'UTF-8'
						) ?>"
				>

			<?php endforeach; ?>


			<?php if ($hasActions): ?>

				<col style="width:90px">

			<?php endif; ?>

		</colgroup>


		<thead class="bg-gray-50">


		<tr>


			<?php foreach ($columns as $column): ?>



				<th
						scope="col"

						style="<?= !empty($column['width'])
							? 'width:' . htmlspecialchars(
								$column['width'],
								ENT_QUOTES,
								'UTF-8'
							)
							: ''
						?>"

						class="
        px-6
        py-3
        text-left
        text-xs
        font-medium
        text-gray-500
        uppercase
        tracking-wider
        whitespace-nowrap
        <?= !empty($column['sortable'])
							? 'cursor-pointer hover:bg-gray-100 select-none'
							: ''
						?>
    "

						data-column="<?= htmlspecialchars(
							$column['key'] ?? '',
							ENT_QUOTES,
							'UTF-8'
						) ?>"

					<?php if (!empty($column['sortable'])): ?>

						data-admin-list-sort="<?= htmlspecialchars(
							$column['key'],
							ENT_QUOTES,
							'UTF-8'
						) ?>"

						aria-sort="none"

					<?php endif; ?>

				>


					<div class="flex items-center gap-1">


	<span
			class="truncate"
			title="<?= htmlspecialchars(
				$column['label'] ?? '',
				ENT_QUOTES,
				'UTF-8'
			) ?>"
	>
		<?= htmlspecialchars(
			$column['label'] ?? '',
			ENT_QUOTES,
			'UTF-8'
		) ?>
	</span>



						<?php if (!empty($column['sortable'])): ?>

							<span
									class="flex flex-col text-[10px] leading-none text-gray-400"
									aria-hidden="true"
							>

			<span data-sort-asc>
				▲
			</span>

			<span data-sort-desc>
				▼
			</span>

		</span>

						<?php endif; ?>


					</div>


				</th>


			<?php endforeach; ?>



			<?php if ($hasActions): ?>


				<th
						scope="col"

						style="width:90px"

						class="
						px-6
						py-3
						text-right
						text-xs
						font-medium
						text-gray-500
						uppercase
						tracking-wider
						whitespace-nowrap
					"
				>

					Azioni

				</th>


			<?php endif; ?>


		</tr>


		</thead>



		<tbody
				data-admin-list-body
				class="
				bg-white
				divide-y
				divide-gray-200
			"
		>

		</tbody>


	</table>


</div>
