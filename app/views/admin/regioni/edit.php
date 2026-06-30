<?php
// Questo file è un frammento di HTML e deve essere incluso in un layout admin.
// Non contiene i tag <html>, <head>, <body> completi.
?>

<div class="container mx-auto p-6">
	<div class="mb-6 flex justify-between items-center">
		<a href="/admin/regioni/all" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 ease-in-out">
			<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
			</svg>
			Torna a Tutte le regioni
		</a>
		<div>
			<?php use App\Core\Session;

			if(isset($data['regione']['id'])): ?>
				<form action="/admin/regioni/delete/<?php echo htmlspecialchars($data['regione']['id']); ?>" method="POST" class="inline-block" onsubmit="return confirm('Sei sicuro di voler eliminare questa regione?');">
					<!-- CSRF Token per il form di eliminazione -->
					<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">
					<button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-semibold rounded-lg shadow-md hover:bg-red-700 transition duration-300 ease-in-out">
						<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
							<path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
						</svg>
						Elimina
					</button>
				</form>
			<?php endif; ?>
		</div>
	</div>

	<h1 class="text-3xl font-semibold text-gray-800 mb-6">Modifica Regione: <?php echo htmlspecialchars($data['regione']['nome'] ?? ''); ?></h1>

	<?php if($message = Session::getFlash('success')): ?>
		<div class="mb-4 p-3 rounded-lg shadow bg-green-100 text-green-800 border border-green-300">
			<?php echo htmlspecialchars($message) ?>
		</div>
	<?php endif; ?>

	<?php if($message = Session::getFlash('error')): ?>
		<div class="mb-4 p-3 rounded-lg shadow bg-red-100 text-red-800 border border-red-300">
			<?php echo htmlspecialchars($message) ?>
		</div>
	<?php endif; ?>

	<div class="bg-white rounded-lg shadow-lg p-8">
		<form id="eventForm" action="/admin/regioni/update/<?php echo htmlspecialchars($data['regione']['id'] ?? ''); ?>" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="id" value="<?php echo htmlspecialchars($data['regione']['id'] ?? ''); ?>">
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">

			<!-- Titolo -->

				<div class="mb-4">
					<label for="nome" class="block text-gray-700 text-sm font-bold mb-2">Titolo:</label>
					<input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($data['regione']['nome'] ?? ''); ?>" required class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline">
				</div>


			<!-- Descrizione -->
			<div class="mb-4">
				<label for="seo_description" class="block text-gray-700 text-sm font-bold mb-2">Introduzione :</label>
				<style>
					.toolbar {
						margin-bottom: 10px;
					}

					.toolbar button {
						padding: 6px 12px;
						cursor: pointer;
					}

					#htmlBox {
						width: 100%;
						height: 250px;
						margin-top: 15px;
						font-family: monospace;
						display: none;
					}
				</style>
				<div class="toolbar">
					<button type="button" onclick="format('bold')"><b>B</b></button>

					<button type="button" id="toggleHtml">
						HTML
					</button>
				</div>

				<div id="editor" contenteditable="true" class="block h-[300px] overflow-y-auto border rounded p-4 bg-white shadow-sm focus:outline-none">
					<?php echo $data['regione']['intro_html'] ?? ''; ?>
				</div>

				<textarea id="htmlBox"></textarea>
				<textarea name="intro_html" id="intro_html" hidden></textarea>

			</div>

				<div>
					<label for="seo_title" class="block text-gray-700 text-sm font-bold mb-2">Seo Title:</label>
					<input type="text" id="seo_title" name="seo_title" value="<?php echo htmlspecialchars($data['regione']['seo_title'] ?? ''); ?>" required class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500 focus:shadow-outline">
				</div>

			<!-- Luogo -->
			<div class="mb-4">
				<label for="seo_description" class="block text-gray-700 text-sm font-bold mb-2">Seo Descrizione:</label>
				<input type="text" id="seo_description" name="seo_description" value="<?php echo htmlspecialchars($data['regione']['seo_description'] ?? ''); ?>" required class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:border-green-500">
			</div>
			<!-- Pulsanti -->
			<div class="flex items-center justify-between">
				<button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300">Aggiorna Regione</button>
				<a href="/admin/regioni/all" class="font-semibold text-green-600 hover:text-green-800">Annulla</a>
			</div>
		</form>
	</div>

</div>

<script>
	// --- Logica per le Dropdown Dinamiche (Regioni, Province, Comuni) ---
	document.addEventListener('DOMContentLoaded', function(){

		const form = document.getElementById('eventForm');
		const editor = document.getElementById('editor');
		const htmlBox = document.getElementById('htmlBox');
		const toggleBtn = document.getElementById('toggleHtml');
		const textarea = document.getElementById('intro_html');

		function format(command){
			document.execCommand(command, false, null);
		}

		let htmlMode = false;

		toggleBtn.addEventListener('click', function(){

			htmlMode = !htmlMode;

			if(htmlMode){

				htmlBox.value = editor.innerHTML;

				editor.style.display = 'none';
				htmlBox.style.display = 'block';

				toggleBtn.textContent = 'Preview';

			} else{

				editor.innerHTML = htmlBox.value;

				editor.style.display = 'block';
				htmlBox.style.display = 'none';

				toggleBtn.textContent = 'HTML';
			}
		});
		form.addEventListener('submit', function(){

			textarea.value = editor.innerHTML;

		});

	});
</script>
