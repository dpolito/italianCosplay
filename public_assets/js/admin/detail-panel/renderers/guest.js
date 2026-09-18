/**
 * Admin Detail Renderer - Guest
 */

window.adminDetailRenderers = window.adminDetailRenderers || {};

window.adminDetailRenderers.guest = function (guest) {
	const content = document.querySelector('[data-admin-detail-content]');
	if (!content) {
		return;
	}

	content.innerHTML = `
		<div class="space-y-5">
			<div>
				<h2 class="text-xl font-bold text-gray-800">${escapeHtml(guest.name ?? '')}</h2>
				${guest.slug ? `<div class="text-sm text-gray-500 mt-1">${escapeHtml(guest.slug)}</div>` : ''}
			</div>
			<div class="grid grid-cols-2 gap-4">
				<div><div class="text-xs text-gray-500">Creato</div><div class="font-medium">${guest.created_at ?? '-'}</div></div>
				<div><div class="text-xs text-gray-500">Aggiornato</div><div class="font-medium">${guest.updated_at ?? '-'}</div></div>
			</div>
			${guest.bio ? `<div><div class="text-xs text-gray-500">Bio</div><div class="mt-1 text-sm text-gray-700 whitespace-pre-wrap">${escapeHtml(guest.bio)}</div></div>` : ''}
			<div class="grid grid-cols-2 gap-4 text-sm">
				<div>${guest.website ? `<a class="text-blue-600 hover:underline" href="${escapeHtml(guest.website)}" target="_blank">Sito ufficiale</a>` : '<span class="text-gray-400">Nessun sito</span>'}</div>
				<div>${guest.instagram ? `<span class="text-gray-700">Instagram: ${escapeHtml(guest.instagram)}</span>` : '<span class="text-gray-400">Nessun Instagram</span>'}</div>
				<div>${guest.tiktok ? `<span class="text-gray-700">TikTok: ${escapeHtml(guest.tiktok)}</span>` : '<span class="text-gray-400">Nessun TikTok</span>'}</div>
				<div>${guest.youtube ? `<span class="text-gray-700">YouTube: ${escapeHtml(guest.youtube)}</span>` : '<span class="text-gray-400">Nessun YouTube</span>'}</div>
			</div>
			<div class="pt-4 border-t flex gap-3">
				<a href="/admin/guests/edit/${guest.id}" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Modifica guest</a>
			</div>
		</div>
	`;
};
