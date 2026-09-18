/**
 * Admin Detail Renderer - Event Master
 */

window.adminDetailRenderers = window.adminDetailRenderers || {};

window.adminDetailRenderers.eventMaster = function (eventMaster) {
	const content = document.querySelector('[data-admin-detail-content]');
	if (!content) {
		return;
	}

	content.innerHTML = `
		<div class="space-y-5">
			<div>
				<h2 class="text-xl font-bold text-gray-800">${escapeHtml(eventMaster.nome ?? '')}</h2>
				${eventMaster.slug ? `<div class="text-sm text-gray-500 mt-1">${escapeHtml(eventMaster.slug)}</div>` : ''}
			</div>

			${eventMaster.cover ? `
				<div>
					<div class="text-xs text-gray-500 mb-2">Immagine</div>
					<img src="${escapeHtml(eventMaster.cover)}" alt="${escapeHtml(eventMaster.nome ?? 'Evento master')}" class="w-full max-w-sm rounded-lg shadow">
				</div>
			` : ''}

			<div class="grid grid-cols-2 gap-4 text-sm">
				<div><div class="text-xs text-gray-500">Sito web</div><div class="font-medium">${eventMaster.sito_web ? `<a class="text-blue-600 hover:underline" href="${escapeHtml(eventMaster.sito_web)}" target="_blank" rel="noopener noreferrer">${escapeHtml(eventMaster.sito_web)}</a>` : '-'}</div></div>
				<div><div class="text-xs text-gray-500">Creato</div><div class="font-medium">${eventMaster.created_at ?? '-'}</div></div>
				<div><div class="text-xs text-gray-500">Aggiornato</div><div class="font-medium">${eventMaster.updated_at ?? '-'}</div></div>
			</div>

			${eventMaster.descrizione ? `<div><div class="text-xs text-gray-500">Descrizione</div><div class="prose prose-sm max-w-none mt-1 text-gray-700">${eventMaster.descrizione}</div></div>` : ''}

			<div class="grid grid-cols-1 gap-2 text-sm">
				${eventMaster.social_facebook ? `<div><span class="text-gray-500">Facebook:</span> <a class="text-blue-600 hover:underline" href="${escapeHtml(eventMaster.social_facebook)}" target="_blank" rel="noopener noreferrer">${escapeHtml(eventMaster.social_facebook)}</a></div>` : ''}
				${eventMaster.social_twitter ? `<div><span class="text-gray-500">Twitter:</span> <a class="text-blue-600 hover:underline" href="${escapeHtml(eventMaster.social_twitter)}" target="_blank" rel="noopener noreferrer">${escapeHtml(eventMaster.social_twitter)}</a></div>` : ''}
				${eventMaster.social_instagram ? `<div><span class="text-gray-500">Instagram:</span> <a class="text-blue-600 hover:underline" href="${escapeHtml(eventMaster.social_instagram)}" target="_blank" rel="noopener noreferrer">${escapeHtml(eventMaster.social_instagram)}</a></div>` : ''}
				${eventMaster.social_tiktok ? `<div><span class="text-gray-500">TikTok:</span> <a class="text-blue-600 hover:underline" href="${escapeHtml(eventMaster.social_tiktok)}" target="_blank" rel="noopener noreferrer">${escapeHtml(eventMaster.social_tiktok)}</a></div>` : ''}
				${eventMaster.social_youtube ? `<div><span class="text-gray-500">YouTube:</span> <a class="text-blue-600 hover:underline" href="${escapeHtml(eventMaster.social_youtube)}" target="_blank" rel="noopener noreferrer">${escapeHtml(eventMaster.social_youtube)}</a></div>` : ''}
			</div>

			<div class="pt-4 border-t flex gap-3">
				${eventMaster._links?.edit ? `<a href="${eventMaster._links.edit}" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Modifica master</a>` : ''}
			</div>
		</div>
	`;
};

function escapeHtml(value) {
	return String(value).replace(/[&<>"']/g, char => ({
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;',
	})[char]);
}
