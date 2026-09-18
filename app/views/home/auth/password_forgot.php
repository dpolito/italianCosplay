<div class="min-h-[70vh] flex items-center justify-center px-4 py-12">
	<div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">
		<h1 class="text-3xl font-bold text-center text-gray-900 mb-1">Recupera Password</h1>
		<p class="text-center text-gray-500 text-sm mb-6">
			Inserisci la tua email per ricevere il link di reset
		</p>

		<?php if (!empty($data['success'])): ?>
			<div class="mb-5 rounded-lg border border-green-200 bg-green-50 text-green-700 px-4 py-3 text-sm">
				<?php echo htmlspecialchars($data['success']); ?>
			</div>
		<?php endif; ?>

		<?php if (!empty($data['error'])): ?>
			<div class="mb-5 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
				<?php echo htmlspecialchars($data['error']); ?>
			</div>
		<?php endif; ?>

		<form action="/password/forgot" method="POST" class="space-y-5">
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token']); ?>">

			<div>
				<label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
				<input type="email" name="email" id="email" required
				       value="<?php echo htmlspecialchars($data['old_email'] ?? ''); ?>"
				       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-green-800 focus:border-green-800">
			</div>

			<button type="submit"
			        class="w-full bg-green-800 hover:bg-green-900 text-white font-semibold py-2.5 rounded-lg transition duration-200">
				Invia link di reset
			</button>

			<div class="text-center text-sm text-gray-600 pt-2">
				<a href="/login" class="font-medium text-green-800 hover:text-green-900 hover:underline">
					Torna al login
				</a>
			</div>
		</form>
	</div>
</div>
