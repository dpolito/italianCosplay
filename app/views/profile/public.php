<?php
$user = $data['user'] ?? null;

if (!$user) {
	return;
}

$siteBaseUrl = rtrim(URL_ROOT_SITE, '/');
$profileUrl = $siteBaseUrl . '/u/' . rawurlencode($user['username']);

$cover = $user['profile_cover'] ?? null;
$avatar = $user['avatar'] ?? null;

$coverUrl = $cover ? $siteBaseUrl . $cover : '';
$avatarUrl = $avatar ? $siteBaseUrl . $avatar : $siteBaseUrl . '/assets/img/default_avatar.jpg';

$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$bio = $user['bio'] ?? '';

$website = $user['website'] ?? '';

$social = !empty($user['social']) ? json_decode($user['social'], true) : [];
$social = is_array($social) ? array_filter($social) : [];

$show_bio = $data['settings']['show_bio'] ?? false;
$show_email = $data['settings']['show_email'] ?? false;
$show_comune = $data['settings']['show_comune'] ?? false;
$show_tiktok = $data['settings']['show_tiktok'] ?? false;
$show_youtube = $data['settings']['show_youtube'] ?? false;
$show_facebook = $data['settings']['show_facebook'] ?? false;
$show_instagram = $data['settings']['show_instagram'] ?? false;
$show_nome = $data['settings']['show_nome'] ?? false;

?>

<main class="bg-gray-100">

	<!-- HERO COVER -->
	<section class="relative h-[320px] md:h-[420px] bg-gray-300 overflow-hidden">

		<?php if ($coverUrl): ?>
			<img
				src="<?php echo htmlspecialchars($coverUrl); ?>"
				class="absolute inset-0 w-full h-full object-cover"
				style="object-position: center <?php echo (int)($user['cover_position_y'] ?? 50); ?>%;"
				alt="Cover profilo"
			>
		<?php else: ?>
			<div class="absolute inset-0 bg-gradient-to-r from-gray-800 to-gray-600"></div>
		<?php endif; ?>

		<div class="absolute inset-0 bg-black/40"></div>

		<div class="absolute bottom-0 left-0 right-0 p-6 md:p-10 text-white">
			<p class="text-sm md:text-base opacity-90">
				@<?php echo htmlspecialchars($user['username']); ?>
			</p>
		</div>
	</section>

	<!-- CONTENT -->
	<div class="container mx-auto px-4 md:px-6 -mt-16 relative z-10">

		<div class="grid lg:grid-cols-[320px_1fr] gap-8">

			<!-- SIDEBAR -->
			<aside class="space-y-6">

				<div class="bg-white rounded-xl shadow-lg p-5 text-center">

					<img
						src="<?php echo htmlspecialchars($avatarUrl); ?>"
						class="w-28 h-28 rounded-full mx-auto object-cover border-4 border-white shadow"
						alt="Avatar"
					>

					<h2 class="mt-4 text-xl font-bold text-gray-900">
						<?php
						if($show_nome){
							echo htmlspecialchars($fullName ?: $user['username']);
						}
						?>
					</h2>

					<p class="text-gray-500 text-sm">
						@<?php echo htmlspecialchars($user['username']); ?>
					</p>

					<?php if (!empty($website)): ?>
						<a href="<?php echo htmlspecialchars($website); ?>"
						   target="_blank"
						   class="mt-3 inline-block text-green-800 font-semibold hover:underline">
							Sito web
						</a>
					<?php endif; ?>
				</div>

				<!-- INFO -->
				<div class="bg-white rounded-xl shadow-lg p-5">
					<h3 class="font-bold text-gray-900 mb-3">Info</h3>
					<?php if($show_comune){ ?>
					<?php if (!empty($user['comune_name'])): ?>
						<p class="text-sm text-gray-700">
							📍 <?php echo htmlspecialchars($user['comune_name']); ?>
						</p>
					<?php endif; ?>
					<?php } ?>
					<p class="text-sm text-gray-700 mt-2">
						🗓️ Iscritto dal <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
					</p>
				</div>

				<!-- SOCIAL -->
				<?php if (!empty($social)): ?>
					<div class="bg-white rounded-xl shadow-lg p-5">
						<h3 class="font-bold text-gray-900 mb-3">Social</h3>

						<ul class="space-y-2 text-sm">
							<?php foreach ($social as $key => $url): ?>
								<li>
									<?php if($show_tiktok){
										if($key == 'tiktok'){
										?>

									<a href="<?php echo htmlspecialchars($url); ?>"
									   target="_blank"
									   class="text-blue-800 hover:underline font-semibold">
										<?php echo htmlspecialchars(ucfirst($key)); ?>
									</a>
									<?php } } ?>
									<?php if($show_instagram){
										if($key == 'instagram'){
											?>

											<a href="<?php echo htmlspecialchars($url); ?>"
											   target="_blank"
											   class="text-blue-800 hover:underline font-semibold">
												<?php echo htmlspecialchars(ucfirst($key)); ?>
											</a>
										<?php } } ?>
									<?php if($show_youtube){
										if($key == 'youtube'){
											?>

											<a href="<?php echo htmlspecialchars($url); ?>"
											   target="_blank"
											   class="text-blue-800 hover:underline font-semibold">
												<?php echo htmlspecialchars(ucfirst($key)); ?>
											</a>
										<?php } } ?>
									<?php if($show_facebook){
										if($key == 'facebook'){
											?>

											<a href="<?php echo htmlspecialchars($url); ?>"
											   target="_blank"
											   class="text-blue-800 hover:underline font-semibold">
												<?php echo htmlspecialchars(ucfirst($key)); ?>
											</a>
										<?php } } ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

			</aside>

			<!-- MAIN -->
			<section class="space-y-6">

				<!-- BIO -->
				<div class="bg-white rounded-xl shadow-lg p-6">
					<h2 class="text-xl font-bold text-gray-900 mb-4">Bio</h2>
					<?php if ($show_bio){ ?>
						<?php if ($bio){ ?>
							<div class="prose max-w-none text-gray-700">
								<?php echo nl2br(htmlspecialchars($bio)); ?>
							</div>
						<?php }else{ ?>
							<p class="text-gray-500">Nessuna bio disponibile.</p>
						<?php } ?>
					<?php }else{ ?>
						<p class="text-gray-500">Bio nascosta.</p>
					<?php } ?>
				</div>

				<!-- ATTIVITÀ (placeholder futuro) -->
				<div class="bg-white rounded-xl shadow-lg p-6">
					<h2 class="text-xl font-bold text-gray-900 mb-4">Attività</h2>

					<p class="text-gray-500">
						Qui potrai mostrare eventi salvati, segnalazioni, contributi ecc.
					</p>
				</div>

			</section>

		</div>
	</div>
</main>
