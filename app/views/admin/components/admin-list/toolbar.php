<div class="flex flex-wrap items-center gap-3 mb-4">


	<?php if (!empty($adminList['search'])): ?>

		<input
				type="search"
				data-admin-list-search
				placeholder="Cerca..."
				class="
                rounded-md
                border
                border-gray-300
                px-3
                py-2
                text-sm
                w-64
                focus:border-blue-500
                focus:ring-blue-500
            "
		>

	<?php endif; ?>



	<?php foreach ($adminList['filters'] ?? [] as $filter): ?>


		<?php if($filter['type'] === 'select'): ?>


			<select
					data-admin-list-filter="<?= htmlspecialchars($filter['name']) ?>"
					class="
                    rounded-md
                    border
                    border-gray-300
                    px-3
                    py-2
                    text-sm
                "
			>

				<option value="">
					<?= htmlspecialchars($filter['label']) ?>
				</option>


				<?php foreach($filter['options'] as $option): ?>

					<option value="<?= htmlspecialchars($option['value']) ?>">

						<?= htmlspecialchars($option['label']) ?>

					</option>

				<?php endforeach; ?>


			</select>


		<?php endif; ?>


	<?php endforeach; ?>



	<button
			type="button"
			data-admin-list-clear-filters
			class="
            rounded-md
            border
            px-3
            py-2
            text-sm
            hover:bg-gray-50
        "
	>

		Reset

	</button>


</div>
