<?php
$user = $data['user'] ?? [];
$breadcrumbs = $data['breadcrumbs'] ?? [];
?>
<nav class="text-sm text-gray-800 mb-4">
	<?php foreach ($breadcrumbs as $index => $crumb): ?>
		<?php if (isset($crumb['url'])): ?>
			<a href="<?php echo htmlspecialchars($crumb['url']); ?>" class="hover:underline hover:text-green-900"><?php echo htmlspecialchars($crumb['label']); ?></a>
			<?php if ($index < count($breadcrumbs) - 1): ?>
				<span class="mx-1">/</span>
			<?php endif; ?>
		<?php else: ?>
			<span><?php echo htmlspecialchars($crumb['label']); ?></span>
		<?php endif; ?>
	<?php endforeach; ?>
</nav>
<!-- HERO -->
<div class="w-full bg-gradient-to-br from-purple-900 via-fuchsia-800 to-indigo-900 text-white">

	<div class="max-w-6xl mx-auto px-6 py-16 text-center">

		<!-- AVATAR -->
		<div class="flex justify-center">
			<img
					src="<?php echo htmlspecialchars($user['avatar'] ?: '/assets/img/default-avatar.png') ?>"
					alt="avatar"
					class="w-36 h-36 rounded-full object-cover border-4 border-white/20 shadow-xl"
			>
		</div>

		<!-- USERNAME -->
		<h1 class="mt-6 text-3xl md:text-4xl font-bold">
			@<?php echo htmlspecialchars($user['username']) ?>
		</h1>

		<!-- NOME -->
		<?php if (!empty($user['first_name']) || !empty($user['last_name'])): ?>
			<p class="text-white/70 mt-1">
				<?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])) ?>
			</p>
		<?php endif; ?>

		<!-- SOCIAL -->
		<div class="flex justify-center flex-wrap gap-4 mt-6">

			<?php if (!empty($user['instagram'])): ?>
				<a href="<?php echo htmlspecialchars($user['instagram']) ?>" target="_blank"
				   class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-full transition text-sm">
					<i class="fab fa-instagram"></i> Instagram
				</a>
			<?php endif; ?>

			<?php if (!empty($user['tiktok'])): ?>
				<a href="<?php echo htmlspecialchars($user['tiktok']) ?>" target="_blank"
				   class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-full transition text-sm">
					<i class="fab fa-tiktok"></i> TikTok
				</a>
			<?php endif; ?>

			<?php if (!empty($user['facebook'])): ?>
				<a href="<?php echo htmlspecialchars($user['facebook']) ?>" target="_blank"
				   class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-full transition text-sm">
					<i class="fab fa-tiktok"></i> Facebook
				</a>
			<?php endif; ?>

			<?php if (!empty($user['website'])): ?>
				<a href="<?php echo htmlspecialchars($user['website']) ?>" target="_blank"
				   class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-full transition text-sm">
					<i class="fab fa-youtube"></i> Sito
				</a>
			<?php endif; ?>

		</div>

	</div>
</div>

<!-- MAIN CONTENT -->
<div class="mx-auto px-6 py-10">

	<div class="grid grid-cols-1 lg:grid-cols-4 gap-8">

		<!-- SIDEBAR -->
		<div class="lg:col-span-1">

			<div class="bg-white rounded-2xl shadow p-5">

				<h3 class="font-semibold text-gray-800 mb-3">
					Profilo
				</h3>

				<?php if (!empty($user['created_at'])): ?>
					<p class="text-sm text-gray-500">
						Iscritto da
					</p>
					<p class="font-medium text-gray-800 mb-3">
						<?php echo date('m/Y', strtotime($user['created_at'])) ?>
					</p>
				<?php endif; ?>

				<?php if (!empty($user['comune_id'])): ?>
					<p class="text-sm text-gray-500">
						Posizione
					</p>
					<p class="font-medium text-gray-800">
						📍 Italia
					</p>
				<?php endif; ?>

			</div>

		</div>

		<!-- MAIN -->
		<div class="lg:col-span-3 space-y-6">

			<!-- BIO -->
			<?php if (!empty($user['bio'])): ?>
				<div class="bg-white rounded-2xl shadow p-6">
					<h3 class="text-xl font-bold mb-3">Bio</h3>
					<p class="text-gray-700 leading-relaxed">
						<?php echo nl2br(htmlspecialchars($user['bio'])) ?>
					</p>
				</div>
			<?php endif; ?>

			<!-- PORTFOLIO -->
			<div class="bg-white rounded-2xl shadow p-6">

				<h3 class="text-xl font-bold mb-4">
					Portfolio
				</h3>

				<div class="grid grid-cols-2 md:grid-cols-3 gap-4">

					<?php for ($i = 0; $i < 6; $i++): ?>
						<div class="bg-gray-200 h-40 rounded-xl"></div>
					<?php endfor; ?>

				</div>

				<p class="text-center text-gray-400 mt-6 text-sm">
					Contenuti in arrivo 🚀
				</p>

			</div>

		</div>

	</div>

</div>
