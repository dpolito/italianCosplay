<?php
$user = $data['user'] ?? ($user ?? []);
$currentAvatar = $user['avatar'] ?? '/public_assets/images/default_avatar.png';
$displayName = $user['username'] ?? $user['first_name'] ?? 'Cosplayer';
$hasAvatar = !empty($user['avatar']);
$completionSteps = 1 + ($hasAvatar ? 1 : 0) + (!empty($user['bio']) ? 1 : 0) + (!empty($user['social']) ? 1 : 0);
$completionPercent = min(100, (int)round(($completionSteps / 4) * 100));
?>

<section class="mx-auto max-w-6xl space-y-6" aria-labelledby="avatar-page-title">
	<header class="rounded-2xl bg-white p-5 shadow-sm md:p-8">
		<div class="grid gap-6 md:grid-cols-[minmax(0,1fr)_260px] md:items-center">
			<div>
				<p class="text-sm font-bold uppercase tracking-wide text-green-900">Profilo community</p>
				<h1 id="avatar-page-title" class="mt-2 text-3xl font-extrabold leading-tight text-gray-950 md:text-4xl">
					Aggiorna il tuo avatar
				</h1>
				<p class="mt-3 max-w-2xl text-base leading-relaxed text-gray-700">
					Scegli un'immagine riconoscibile: apparirà nella dashboard e renderà il tuo profilo più personale per la community cosplay.
				</p>
			</div>

			<aside class="rounded-xl border border-green-100 bg-green-50 p-4" aria-label="Completamento profilo">
				<div class="flex items-center justify-between gap-3">
					<p class="text-sm font-bold text-green-950">Profilo completato</p>
					<p class="text-lg font-extrabold text-green-950"><?php echo $completionPercent; ?>%</p>
				</div>
				<div class="mt-3 h-3 overflow-hidden rounded-full bg-white">
					<div class="h-full rounded-full bg-green-800" style="width: <?php echo $completionPercent; ?>%"></div>
				</div>
				<p class="mt-3 text-sm text-gray-700">
					Avatar, bio e social aiutano gli altri utenti a riconoscerti meglio.
				</p>
			</aside>
		</div>
	</header>

	<?php if (!empty($data['success'])): ?>
		<div class="rounded-xl border border-green-200 bg-green-50 p-4 text-green-900" role="status">
			<?php echo htmlspecialchars($data['success'], ENT_QUOTES, 'UTF-8'); ?>
		</div>
	<?php endif; ?>

	<?php if (!empty($data['error'])): ?>
		<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800" role="alert">
			<?php echo htmlspecialchars($data['error'], ENT_QUOTES, 'UTF-8'); ?>
		</div>
	<?php endif; ?>

	<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
		<form action="/dashboard/avatar" method="POST" enctype="multipart/form-data" class="rounded-2xl bg-white p-5 shadow-sm md:p-8" id="avatar-form">
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
			<div class="mb-6">
				<h2 class="text-xl font-bold text-gray-950">Carica una nuova immagine</h2>
				<p class="mt-2 text-sm leading-relaxed text-gray-600">
					Formati consigliati: JPG, PNG, GIF o WebP. Dimensione massima 5 MB. Dopo la scelta potrai centrare l'immagine nel cerchio prima di salvarla.
				</p>
			</div>

			<div
				id="avatar-upload"
				class="group relative flex min-h-[360px] w-full cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center transition hover:border-green-800 hover:bg-green-50 focus-within:border-green-800 focus-within:ring-2 focus-within:ring-green-700"
				role="button"
				tabindex="0"
				aria-controls="avatar-input"
				aria-describedby="avatar-help avatar-status"
				aria-label="Carica o trascina il nuovo avatar"
			>
				<input type="file" id="avatar-input" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only">

				<div id="avatar-crop-frame" class="relative h-36 w-36 touch-none overflow-hidden rounded-full border-4 border-white bg-gray-200 shadow-lg md:h-44 md:w-44" aria-label="Area di anteprima circolare">
					<img
						id="avatar-preview"
						src="<?php echo htmlspecialchars($currentAvatar, ENT_QUOTES, 'UTF-8'); ?>"
						alt="Anteprima avatar di <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>"
						class="absolute left-0 top-0 h-full w-full max-w-none select-none object-cover"
						width="176"
						height="176"
						draggable="false"
					>
					<canvas id="avatar-crop-canvas" class="absolute inset-0 hidden h-full w-full" width="512" height="512" aria-hidden="true"></canvas>
					<span class="absolute bottom-2 right-2 inline-flex h-11 w-11 items-center justify-center rounded-full bg-green-800 text-white shadow-md" aria-hidden="true">
						<i class="fa-solid fa-camera"></i>
					</span>
				</div>

				<div class="mt-6 max-w-sm">
					<p class="text-lg font-extrabold text-gray-950" id="avatar-drop-title">
						Tocca per scegliere una foto
					</p>
					<p class="mt-2 text-sm leading-relaxed text-gray-600" id="avatar-help">
						Da desktop puoi anche trascinare qui l'immagine. Dopo la selezione potrai spostarla e zoomarla.
					</p>
				</div>
			</div>

			<div id="avatar-crop-controls" class="mt-5 hidden rounded-xl border border-green-100 bg-green-50 p-4">
				<h3 class="font-bold text-gray-950">Centra l'immagine nel cerchio</h3>
				<p class="mt-1 text-sm text-gray-700">Trascina la foto nell'anteprima e regola lo zoom. Salva quando il risultato ti piace.</p>
				<label for="avatar-zoom" class="mt-4 block text-sm font-semibold text-gray-900">Zoom avatar</label>
				<input id="avatar-zoom" type="range" min="1" max="3" step="0.01" value="1" class="mt-2 w-full accent-green-800">
				<div class="mt-4 grid gap-3 sm:grid-cols-2">
					<button type="button" id="save-avatar-button" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg bg-green-800 px-5 py-3 font-bold text-white shadow transition hover:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2">
						<i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
						Salva avatar
					</button>
					<button type="button" id="center-avatar-button" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg border border-green-800 px-5 py-3 font-bold text-green-900 transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2">
						<i class="fa-solid fa-crosshairs" aria-hidden="true"></i>
						Ricentra
					</button>
				</div>
			</div>

			<div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4" aria-live="polite">
				<div class="flex items-start gap-3">
					<span id="avatar-status-icon" class="mt-0.5 inline-flex h-6 w-6 flex-none items-center justify-center rounded-full bg-gray-200 text-gray-700" aria-hidden="true">
						<i class="fa-solid fa-circle-info text-xs"></i>
					</span>
					<div>
						<p id="avatar-status" class="font-semibold text-gray-900">Nessun nuovo file selezionato.</p>
						<p id="avatar-file-details" class="mt-1 text-sm text-gray-600">Quando scegli un'immagine vedrai subito l'anteprima.</p>
					</div>
				</div>
			</div>

			<div class="mt-6 flex flex-col gap-3 sm:flex-row">
				<button type="button" id="choose-avatar-button" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg bg-green-800 px-5 py-3 font-bold text-white shadow transition hover:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2">
					<i class="fa-solid fa-image" aria-hidden="true"></i>
					Scegli immagine
				</button>
				<button type="button" id="reset-preview-button" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg border border-gray-300 px-5 py-3 font-bold text-gray-800 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2">
					<i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
					Annulla anteprima
				</button>
			</div>

			<noscript>
				<button type="submit" class="mt-4 inline-flex min-h-12 items-center justify-center rounded-lg bg-green-800 px-5 py-3 font-bold text-white">
					Aggiorna avatar
				</button>
			</noscript>
		</form>

		<aside class="space-y-6">
			<section class="rounded-2xl bg-white p-5 shadow-sm" aria-labelledby="public-preview-title">
				<h2 id="public-preview-title" class="text-xl font-bold text-gray-950">Anteprima profilo</h2>
				<div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-5 text-center">
					<img
						id="public-avatar-preview"
						src="<?php echo htmlspecialchars($currentAvatar, ENT_QUOTES, 'UTF-8'); ?>"
						alt="Anteprima pubblica avatar"
						class="mx-auto h-24 w-24 rounded-full object-cover shadow-md"
						width="96"
						height="96"
						loading="lazy"
					>
					<p class="mt-4 text-lg font-extrabold text-gray-950"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></p>
					<p class="mt-1 text-sm text-gray-600">Membro ItalianCosplay</p>
				</div>
			</section>

			<section class="rounded-2xl bg-white p-5 shadow-sm" aria-labelledby="tips-avatar-title">
				<h2 id="tips-avatar-title" class="text-xl font-bold text-gray-950">Consigli rapidi</h2>
				<ul class="mt-4 space-y-3 text-sm leading-relaxed text-gray-700">
					<li class="flex gap-3">
						<i class="fa-solid fa-check mt-1 text-green-800" aria-hidden="true"></i>
						<span>Usa una foto luminosa, centrata sul volto o sul tuo cosplay.</span>
					</li>
					<li class="flex gap-3">
						<i class="fa-solid fa-check mt-1 text-green-800" aria-hidden="true"></i>
						<span>Evita immagini con testo piccolo: l'avatar appare spesso in formato ridotto.</span>
					</li>
					<li class="flex gap-3">
						<i class="fa-solid fa-check mt-1 text-green-800" aria-hidden="true"></i>
						<span>Dopo l'avatar, completa bio e social per rendere il profilo più riconoscibile.</span>
					</li>
				</ul>
				<a href="/dashboard/profile" class="mt-5 inline-flex w-full items-center justify-center rounded-lg border border-green-800 px-4 py-3 font-bold text-green-900 hover:bg-green-50">
					Completa profilo
				</a>
			</section>
		</aside>
	</div>
</section>

<script>
	document.addEventListener('DOMContentLoaded', function() {
		const dropzone = document.getElementById('avatar-upload');
		const cropFrame = document.getElementById('avatar-crop-frame');
		const cropControls = document.getElementById('avatar-crop-controls');
		const zoomInput = document.getElementById('avatar-zoom');
		const fileInput = document.getElementById('avatar-input');
		const preview = document.getElementById('avatar-preview');
		const cropCanvas = document.getElementById('avatar-crop-canvas');
		const cropContext = cropCanvas.getContext('2d');
		const publicPreview = document.getElementById('public-avatar-preview');
		const sidebarPreview = document.getElementById('avatar-preview_sidebar');
		const chooseButton = document.getElementById('choose-avatar-button');
		const resetButton = document.getElementById('reset-preview-button');
		const saveButton = document.getElementById('save-avatar-button');
		const centerButton = document.getElementById('center-avatar-button');
		const statusText = document.getElementById('avatar-status');
		const fileDetails = document.getElementById('avatar-file-details');
		const statusIcon = document.getElementById('avatar-status-icon');
		const originalAvatar = <?php echo json_encode($currentAvatar); ?>;
		const csrfToken = <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>;
		const maxFileSize = 5 * 1024 * 1024;
		const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
		const cropSize = 512;
		let selectedFile = null;
		let selectedImage = null;
		let selectedImageUrl = '';
		let baseScale = 1;
		let cropState = {
			scale: 1,
			offsetX: 0,
			offsetY: 0,
			isDragging: false,
			startX: 0,
			startY: 0,
			startOffsetX: 0,
			startOffsetY: 0
		};

		function openFilePicker() {
			fileInput.click();
		}

		function setStatus(type, message, details) {
			statusText.textContent = message;
			fileDetails.textContent = details || '';
			statusIcon.className = 'mt-0.5 inline-flex h-6 w-6 flex-none items-center justify-center rounded-full';

			if (type === 'success') {
				statusIcon.classList.add('bg-green-100', 'text-green-800');
				statusIcon.innerHTML = '<i class="fa-solid fa-check text-xs" aria-hidden="true"></i>';
			} else if (type === 'error') {
				statusIcon.classList.add('bg-red-100', 'text-red-700');
				statusIcon.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-xs" aria-hidden="true"></i>';
			} else if (type === 'loading') {
				statusIcon.classList.add('bg-amber-100', 'text-amber-800');
				statusIcon.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs" aria-hidden="true"></i>';
			} else {
				statusIcon.classList.add('bg-gray-200', 'text-gray-700');
				statusIcon.innerHTML = '<i class="fa-solid fa-circle-info text-xs" aria-hidden="true"></i>';
			}
		}

		function updatePreviews(url) {
			preview.src = url;
			preview.classList.remove('hidden');
			cropCanvas.classList.add('hidden');
			publicPreview.src = url;
			if (sidebarPreview) {
				sidebarPreview.src = url;
			}
		}

		function setCropMode(enabled) {
			cropControls.classList.toggle('hidden', !enabled);
			saveButton.disabled = !enabled;
		}

		function validateFile(file) {
			if (!file) {
				return 'Nessun file selezionato.';
			}

			if (!allowedTypes.includes(file.type)) {
				return 'Formato non supportato. Usa JPG, PNG, GIF o WebP.';
			}

			if (file.size > maxFileSize) {
				return 'File troppo grande. La dimensione massima è 5 MB.';
			}

			return '';
		}

		function humanFileSize(size) {
			return size >= 1024 * 1024
				? (size / (1024 * 1024)).toFixed(1) + ' MB'
				: Math.max(1, Math.round(size / 1024)) + ' KB';
		}

		function resetCropState() {
			cropState.scale = 1;
			cropState.offsetX = 0;
			cropState.offsetY = 0;
			zoomInput.value = '1';
		}

		function getFrameRect() {
			const rect = cropFrame.getBoundingClientRect();
			return {
				width: rect.width || 176,
				height: rect.height || 176
			};
		}

		function constrainOffsets() {
			if (!selectedImage) {
				return;
			}

			const frame = getFrameRect();
			const renderedWidth = selectedImage.naturalWidth * baseScale * cropState.scale;
			const renderedHeight = selectedImage.naturalHeight * baseScale * cropState.scale;
			const maxX = Math.max(0, (renderedWidth - frame.width) / 2);
			const maxY = Math.max(0, (renderedHeight - frame.height) / 2);

			cropState.offsetX = Math.min(maxX, Math.max(-maxX, cropState.offsetX));
			cropState.offsetY = Math.min(maxY, Math.max(-maxY, cropState.offsetY));
		}

		function drawCrop(targetCanvas) {
			if (!selectedImage) {
				return;
			}

			const frame = getFrameRect();
			const renderedWidth = selectedImage.naturalWidth * baseScale * cropState.scale;
			const renderedHeight = selectedImage.naturalHeight * baseScale * cropState.scale;
			constrainOffsets();
			const imageLeft = (frame.width - renderedWidth) / 2 + cropState.offsetX;
			const imageTop = (frame.height - renderedHeight) / 2 + cropState.offsetY;
			const targetContext = targetCanvas.getContext('2d');
			const scaleX = targetCanvas.width / frame.width;
			const scaleY = targetCanvas.height / frame.height;

			targetContext.clearRect(0, 0, targetCanvas.width, targetCanvas.height);
			targetContext.fillStyle = '#ffffff';
			targetContext.fillRect(0, 0, targetCanvas.width, targetCanvas.height);
			targetContext.drawImage(
				selectedImage,
				imageLeft * scaleX,
				imageTop * scaleY,
				renderedWidth * scaleX,
				renderedHeight * scaleY
			);
		}

		function renderCropPreview() {
			if (!selectedImage) {
				return;
			}

			drawCrop(cropCanvas);
			preview.classList.add('hidden');
			cropCanvas.classList.remove('hidden');
		}

		function loadCropEditor(file) {
			const error = validateFile(file);
			if (error) {
				setStatus('error', error, 'Scegli un file immagine valido e riprova.');
				return;
			}

			selectedFile = file;
			if (selectedImageUrl) {
				URL.revokeObjectURL(selectedImageUrl);
			}

			selectedImageUrl = URL.createObjectURL(file);
			selectedImage = new Image();
			selectedImage.onload = function() {
				const frame = getFrameRect();
				baseScale = Math.max(
					frame.width / selectedImage.naturalWidth,
					frame.height / selectedImage.naturalHeight
				);
				resetCropState();
				renderCropPreview();
				setCropMode(true);
				setStatus('info', 'Immagine pronta da centrare.', 'Trascina la foto nel cerchio, regola lo zoom e poi premi Salva avatar.');
			};
			selectedImage.src = selectedImageUrl;
			fileDetails.textContent = file.name + ' · ' + humanFileSize(file.size);
		}

		function buildCroppedAvatar(callback) {
			if (!selectedImage) {
				setStatus('error', 'Nessuna immagine da salvare.', 'Scegli una foto prima di salvare.');
				return;
			}

			const canvas = document.createElement('canvas');
			canvas.width = cropSize;
			canvas.height = cropSize;
			drawCrop(canvas);

			canvas.toBlob(function(blob) {
				if (!blob) {
					setStatus('error', 'Non riesco a preparare l’avatar.', 'Prova con un’altra immagine.');
					return;
				}
				callback(blob);
			}, 'image/png', 0.92);
		}

		function uploadAvatarBlob(blob) {
			const fileName = selectedFile ? selectedFile.name.replace(/\.[^.]+$/, '') + '-avatar.png' : 'avatar.png';
			const reader = new FileReader();
			reader.onload = function(event) {
				updatePreviews(event.target.result);
			};
			reader.readAsDataURL(blob);

			setStatus('loading', 'Salvataggio avatar in corso...', fileName + ' · ' + humanFileSize(blob.size));

			const formData = new FormData();
			formData.append('avatar', blob, fileName);
			formData.append('csrf_token', csrfToken);

			fetch('/dashboard/updateAvatar', {
				method: 'POST',
				body: formData,
				headers: {
					'Accept': 'application/json'
				}
			})
				.then(function(response) {
					if (!response.ok) {
						throw new Error('Upload non riuscito');
					}
					return response.json();
				})
				.then(function(data) {
					if (data.success && data.avatarUrl) {
						updatePreviews(data.avatarUrl);
						setStatus('success', 'Avatar aggiornato correttamente.', 'La nuova immagine è già visibile nella dashboard.');
						setCropMode(false);
						selectedFile = null;
						return;
					}

					setStatus('error', data.message || 'Errore nel caricamento.', 'Controlla formato e dimensione del file.');
				})
				.catch(function() {
					setStatus('error', 'Errore di rete durante il caricamento.', 'Riprova tra qualche secondo.');
				});
		}

		dropzone.addEventListener('click', function(event) {
			if (event.target === fileInput || cropControls.contains(event.target)) {
				return;
			}
			if (selectedImage && cropFrame.contains(event.target)) {
				return;
			}
			openFilePicker();
		});

		dropzone.addEventListener('keydown', function(event) {
			if (event.key === 'Enter' || event.key === ' ') {
				event.preventDefault();
				openFilePicker();
			}
		});

		dropzone.addEventListener('dragover', function(event) {
			event.preventDefault();
			dropzone.classList.add('border-green-800', 'bg-green-50');
		});

		dropzone.addEventListener('dragleave', function(event) {
			event.preventDefault();
			dropzone.classList.remove('border-green-800', 'bg-green-50');
		});

		dropzone.addEventListener('drop', function(event) {
			event.preventDefault();
			dropzone.classList.remove('border-green-800', 'bg-green-50');
			loadCropEditor(event.dataTransfer.files[0]);
		});

		fileInput.addEventListener('change', function(event) {
			loadCropEditor(event.target.files[0]);
		});

		chooseButton.addEventListener('click', openFilePicker);

		zoomInput.addEventListener('input', function() {
			cropState.scale = parseFloat(this.value);
			renderCropPreview();
		});

		centerButton.addEventListener('click', function() {
			resetCropState();
			renderCropPreview();
			setStatus('info', 'Immagine ricentrata.', 'Regola zoom o posizione prima di salvare.');
		});

		saveButton.addEventListener('click', function() {
			buildCroppedAvatar(uploadAvatarBlob);
		});

		cropFrame.addEventListener('pointerdown', function(event) {
			if (!selectedImage) {
				return;
			}

			event.preventDefault();
			cropFrame.setPointerCapture(event.pointerId);
			cropState.isDragging = true;
			cropState.startX = event.clientX;
			cropState.startY = event.clientY;
			cropState.startOffsetX = cropState.offsetX;
			cropState.startOffsetY = cropState.offsetY;
		});

		cropFrame.addEventListener('pointermove', function(event) {
			if (!cropState.isDragging) {
				return;
			}

			cropState.offsetX = cropState.startOffsetX + (event.clientX - cropState.startX);
			cropState.offsetY = cropState.startOffsetY + (event.clientY - cropState.startY);
			renderCropPreview();
		});

		cropFrame.addEventListener('pointerup', function(event) {
			cropState.isDragging = false;
			if (cropFrame.hasPointerCapture(event.pointerId)) {
				cropFrame.releasePointerCapture(event.pointerId);
			}
		});

		resetButton.addEventListener('click', function() {
			fileInput.value = '';
			selectedFile = null;
			selectedImage = null;
			setCropMode(false);
			cropCanvas.classList.add('hidden');
			preview.removeAttribute('style');
			preview.classList.remove('hidden');
			preview.classList.add('h-full', 'w-full', 'object-cover');
			updatePreviews(originalAvatar);
			setStatus('info', 'Anteprima ripristinata.', 'Scegli una nuova immagine quando vuoi.');
		});
	});
</script>
