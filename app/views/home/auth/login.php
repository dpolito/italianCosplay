<div class="min-h-[70vh] flex items-center justify-center px-4 py-12">
	<div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">

		<h1 class="text-3xl font-bold text-center text-gray-900 mb-1">
			Accedi
		</h1>
		<p class="text-center text-gray-500 text-sm mb-6">
			Bentornato su ItalianCosplay
		</p>
		<?php if (!empty($data['pendingActionContext'])): ?>
			<div class="mb-5 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
				<p class="font-bold"><?php echo htmlspecialchars($data['pendingActionContext']['title'] ?? 'Completa la tua azione', ENT_QUOTES, 'UTF-8'); ?></p>
				<p class="mt-1"><?php echo htmlspecialchars($data['pendingActionContext']['description'] ?? 'Accedi o crea un account per salvare la tua scelta.', ENT_QUOTES, 'UTF-8'); ?></p>
			</div>
		<?php endif; ?>
		<?php if (!empty($data['success'])): ?>
			<div class="mb-5 rounded-lg border border-green-200 bg-green-50 text-green-700 px-4 py-3 text-sm">
				<?php echo htmlspecialchars($data['success']); ?>
			</div>
		<?php endif; ?>
		<?php if (isset($data['error'])): ?>
			<div class="mb-5 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
				<?php echo htmlspecialchars($data['error']); ?>
			</div>
		<?php endif; ?>

		<form action="/login" method="POST" class="space-y-5">

			<!-- CSRF -->
			<input type="hidden" name="csrf_token"
			       value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">

			<div>
				<label for="username_email" class="block text-sm font-semibold text-gray-700 mb-1">
					Username o Email
				</label>
				<input
						type="text"
						id="username_email"
						name="username_email"
						value="<?php echo htmlspecialchars($data['old_identifier'] ?? ''); ?>"
						required
						autocomplete="username"
						class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                           focus:outline-none focus:ring-2 focus:ring-green-800 focus:border-green-800"
				>
			</div>

			<div class="relative">
				<label for="password" class="block text-sm font-semibold text-gray-700 mb-1">
					Password
				</label>
				<input
						type="password"
						id="password"
						name="password"
						required
						autocomplete="current-password"
						class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                           focus:outline-none focus:ring-2 focus:ring-green-800 focus:border-green-800"
				>
				<button type="button"
				        id="togglePassword"
				        class="absolute right-2 top-9 text-gray-500 hover:text-gray-700 focus:outline-none">
					Mostra
				</button>
			</div>

			<button
					type="submit"
					class="w-full bg-green-800 hover:bg-green-900
                       text-white font-semibold py-2.5 rounded-lg
                       transition duration-200"
			>
				Accedi
			</button>

			<div class="text-center text-sm text-gray-600 pt-2">
				<a href="/password/forgot"
				   class="font-medium text-green-800 hover:text-green-900 hover:underline">
					Password dimenticata?
				</a>
				<span class="mx-2">•</span>
				<a href="/register"
				   class="font-medium text-green-800 hover:text-green-900 hover:underline">
					Registrati
				</a>
			</div>

		</form>
	</div>
</div>

<script>
	// Toggle mostra/nascondi password
	const togglePassword = document.getElementById('togglePassword');
	const passwordInput = document.getElementById('password');

	togglePassword.addEventListener('click', () => {
		const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
		passwordInput.setAttribute('type', type);
		togglePassword.textContent = type === 'password' ? 'Mostra' : 'Nascondi';
	});
</script>
