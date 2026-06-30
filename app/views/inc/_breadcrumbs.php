<?php if (!empty($data['breadcrumbs'])) : ?>
	<nav class="bg-gray-50 py-3 px-4 rounded-lg shadow-sm text-sm text-gray-700 mt-4 mx-auto max-w-7xl">
		<ol class="list-none p-0 inline-flex">
			<?php foreach ($data['breadcrumbs'] as $index => $crumb) : ?>
				<li class="flex items-center">
					<?php if ($index > 0) : ?>
						<span class="mx-2 text-gray-400">/</span>
					<?php endif; ?>
					<?php if (isset($crumb['url']) && $index < count($data['breadcrumbs']) - 1) : ?>
						<a href="<?php echo htmlspecialchars($crumb['url']); ?>" class="text-indigo-600 hover:text-indigo-800 hover:underline">
							<?php echo htmlspecialchars($crumb['label']); ?>
						</a>
					<?php else : ?>
						<span class="text-gray-900 font-medium"><?php echo htmlspecialchars($crumb['label']); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
<?php endif; ?>
