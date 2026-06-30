<?php
$guest = $data['guest'];
?>

<div class="container mx-auto p-6">

	<!-- Back -->
	<div class="mb-6">
		<a href="/admin/guests/all"
		   class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition">
			<svg xmlns="http://www.w3.org/2000/svg"
			     class="h-5 w-5 mr-2"
			     fill="none"
			     viewBox="0 0 24 24"
			     stroke="currentColor">
				<path stroke-linecap="round"
				      stroke-linejoin="round"
				      stroke-width="2"
				      d="M11 17l-5-5m0 0l5-5m5 5h12"/>
			</svg>
			Torna ai Guests
		</a>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">
		Modifica Guest
	</h1>

	<?php if (isset($_SESSION['flash_messages'])): ?>
		<?php foreach ($_SESSION['flash_messages'] as $type => $message): ?>
			<div class="flash-message <?= htmlspecialchars($type) ?>">
				<?= $message ?>
			</div>
		<?php endforeach; unset($_SESSION['flash_messages']); ?>
	<?php elseif (!empty($data['error'])): ?>
		<div class="flash-message error">
			<?= $data['error'] ?>
		</div>
	<?php endif; ?>

	<div class="bg-white rounded-lg shadow-lg p-8">

		<form action="/admin/guests/update/<?= (int)$guest['id'] ?>"
		      method="POST"
		      enctype="multipart/form-data"
		      id="guestForm">

			<input type="hidden"
			       name="csrf_token"
			       value="<?= htmlspecialchars($data['csrf_token']) ?>">

			<input type="hidden"
			       name="id"
			       value="<?= (int)$guest['id'] ?>">

			<!-- Nome + slug -->
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">
						Nome e Cognome *
					</label>

					<input type="text"
					       name="name"
					       id="name"
					       class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500"
					       value="<?= htmlspecialchars($guest['name']) ?>"
					       required>
				</div>

				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">
						Slug SEO
					</label>

					<input type="text"
					       name="slug"
					       id="slug"
					       class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500"
					       value="<?= htmlspecialchars($guest['slug']) ?>">

					<p class="text-xs text-gray-500 mt-1">
						URL finale: /guest/<?= htmlspecialchars($guest['slug']) ?>
					</p>
				</div>

			</div>

			<!-- Website + Instagram -->
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">
						Sito
					</label>

					<input type="text"
					       name="website"
					       class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500"
					       value="<?= htmlspecialchars($guest['website'] ?? '') ?>">
				</div>

				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">
						Instagram
					</label>

					<input type="text"
					       name="instagram"
					       class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500"
					       value="<?= htmlspecialchars($guest['instagram'] ?? '') ?>">
				</div>

			</div>

			<!-- TikTok + Youtube -->
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">
						TikTok
					</label>

					<input type="text"
					       name="tiktok"
					       class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500"
					       value="<?= htmlspecialchars($guest['tiktok'] ?? '') ?>">
				</div>

				<div>
					<label class="block text-gray-700 text-sm font-bold mb-2">
						Youtube
					</label>

					<input type="text"
					       name="youtube"
					       class="shadow border rounded-lg w-full py-2 px-3 focus:border-green-500"
					       value="<?= htmlspecialchars($guest['youtube'] ?? '') ?>">
				</div>

			</div>

			<!-- Cover -->
			<div class="mb-6">

				<label class="block text-gray-700 text-sm font-bold mb-2">
					Immagine Cover SEO
				</label>

				<?php if (!empty($guest['immagine'])): ?>

					<div class="mb-4">
						<img
								src="<?= htmlspecialchars($guest['immagine']) ?>"
								alt="<?= htmlspecialchars($guest['name']) ?>"
								class="max-w-sm rounded-lg shadow border">
					</div>

				<?php endif; ?>

				<input type="file"
				       name="cover_image"
				       accept="image/*"
				       class="shadow border rounded-lg w-full py-2 px-3">

				<p class="text-xs text-gray-500 mt-1">
					Lascia vuoto per mantenere l'immagine esistente
				</p>

			</div>

			<!-- Bio -->
			<div class="mb-6">

				<label class="block text-gray-700 text-sm font-bold mb-2">
					Bio *
				</label>

				<div class="bg-gray-100 p-2 rounded mb-2 flex gap-2">
					<button type="button" onclick="format('bold')" class="px-2 py-1 bg-white border rounded">B</button>
					<button type="button" onclick="format('italic')" class="px-2 py-1 bg-white border rounded">I</button>
					<button type="button" onclick="format('underline')" class="px-2 py-1 bg-white border rounded">U</button>
					<!-- 🔥 NUOVO -->
					<button type="button"
					        id="toggleHtml"
					        class="px-2 py-1 bg-black text-white rounded ml-auto">
						HTML
					</button>
				</div>

				<div id="editor"
				     contenteditable="true"
				     class="border rounded-lg p-4 h-[350px] overflow-y-auto bg-white">
					<?= $guest['bio'] ?>
				</div>
				<textarea
						id="htmlBox"
						class="hidden border rounded-lg p-4 w-full h-[350px] font-mono text-sm"
				></textarea>

				<textarea name="bio"
				          id="contenuto"
				          hidden><?= htmlspecialchars($guest['bio']) ?></textarea>

			</div>

			<!-- Submit -->
			<div class="flex items-center justify-between">

				<button type="submit"
				        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg">
					Salva Modifiche
				</button>

				<a href="/admin/guest/all"
				   class="text-gray-600 hover:text-gray-900">
					Annulla
				</a>

			</div>

		</form>

	</div>

</div>

<script>

	function format(cmd) {
		document.execCommand(cmd, false, null);
	}

	document.addEventListener('DOMContentLoaded', function () {

		const titolo = document.getElementById('name');
		const slug = document.getElementById('slug');
		const form = document.getElementById('guestForm');

		const editor = document.getElementById('editor');
		const textarea = document.getElementById('contenuto');
		const htmlBox = document.getElementById('htmlBox');
		const toggleBtn = document.getElementById('toggleHtml');

		function makeSlug(str) {
			return str
				.toLowerCase()
				.replace(/[^a-z0-9]+/g, '-')
				.replace(/(^-|-$)/g, '');
		}

		let slugEdited = true;

		slug.addEventListener('input', function () {
			slugEdited = true;
		});

		titolo.addEventListener('input', function () {

			if (!slugEdited) {
				slug.value = makeSlug(titolo.value);
			}

		});



		let htmlMode = false;

		function syncToTextarea() {
			textarea.value = editor.innerHTML;
		}

		toggleBtn.addEventListener('click', function () {

			htmlMode = !htmlMode;

			if (htmlMode) {

				// visual → html
				htmlBox.value = editor.innerHTML;

				editor.classList.add('hidden');
				htmlBox.classList.remove('hidden');

				toggleBtn.textContent = 'Preview';

			} else {

				// html → visual
				editor.innerHTML = htmlBox.value;

				htmlBox.classList.add('hidden');
				editor.classList.remove('hidden');

				toggleBtn.textContent = 'HTML';

			}
		});

		form.addEventListener('submit', function () {

			if (htmlMode) {
				editor.innerHTML = htmlBox.value;
			}

			syncToTextarea();
		});

	});

</script>
