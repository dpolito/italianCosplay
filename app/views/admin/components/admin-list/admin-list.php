<?php
/**
 * Admin List Component
 *
 * Componente tabella amministrazione AJAX.
 *
 * Richiede:
 * $adminList
 */

$config = $adminList;

?>

<div
		id="<?= htmlspecialchars(
			$adminList['id'],
			ENT_QUOTES,
			'UTF-8'
		) ?>"
		data-admin-list
		data-config="<?= htmlspecialchars(
			json_encode(
				$adminList,
				JSON_HEX_APOS | JSON_HEX_QUOT
			),
			ENT_QUOTES,
			'UTF-8'
		) ?>"
		class="space-y-4"
>


	<!-- Toolbar -->

	<?php require __DIR__ . '/toolbar.php'; ?>


	<!-- Layout Lista + Dettaglio -->

	<div
			class="
			flex
			gap-4
			items-start
		"
			data-admin-layout
	>


		<!-- Lista -->

		<div
				class="
				flex-1
				transition-all
				duration-300
			"
				data-admin-table-wrapper
		>


			<!-- Table -->

			<?php require __DIR__ . '/table.php'; ?>


			<!-- Loading -->

			<?php require __DIR__ . '/loading.php'; ?>


			<!-- Empty -->

			<?php require __DIR__ . '/empty-state.php'; ?>


			<!-- No Results -->

			<?php require __DIR__ . '/no-results.php'; ?>


			<!-- Pagination -->

			<?php require __DIR__ . '/pagination.php'; ?>


		</div>



		<!-- Detail Panel -->

		<?php if (!empty($adminList['detailPanel'])): ?>

			<?php require __DIR__ . '/detail-panel/panel.php'; ?>

		<?php endif; ?>


	</div>


</div>
