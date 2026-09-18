/**
 * Admin Detail Renderer - Advertising Position
 */

window.adminDetailRenderers = window.adminDetailRenderers || {};

window.adminDetailRenderers.adPosition = function (position) {
	const content = document.querySelector('[data-admin-detail-content]');
	if (!content) {
		return;
	}

	content.innerHTML = `
		<div class="space-y-5">
			<div>
				<h2 class="text-xl font-bold text-gray-800">${escapeHtml(position.name ?? '')}</h2>
				${position.code ? `<div class="text-sm text-gray-500 mt-1">${escapeHtml(position.code)}</div>` : ''}
			</div>
			<div class="grid grid-cols-2 gap-4">
				<div><div class="text-xs text-gray-500">Pagina</div><div class="font-medium">${escapeHtml(position.page ?? '-')}</div></div>
				<div><div class="text-xs text-gray-500">Stato</div><div class="font-medium">${String(position.is_active) === '1' ? 'Attiva' : 'Disattivata'}</div></div>
				<div><div class="text-xs text-gray-500">Formato desktop</div><div class="font-medium">${position.width ?? 0}x${position.height ?? 0}</div></div>
				<div><div class="text-xs text-gray-500">Formato mobile</div><div class="font-medium">${position.mobile_width ?? 0}x${position.mobile_height ?? 0}</div></div>
				<div><div class="text-xs text-gray-500">Slot max</div><div class="font-medium">${position.max_slots ?? 1}</div></div>
				<div><div class="text-xs text-gray-500">Prezzo base</div><div class="font-medium">${escapeHtml(String(position.base_price ?? 0))} EUR</div></div>
			</div>
			<div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
				<div class="font-semibold">Banner unico responsive</div>
				<div class="mt-1">Per questa posizione è previsto un solo file banner. Le misure desktop e mobile sono solo un riferimento di resa nei diversi dispositivi.</div>
			</div>
			${position.description ? `<div><div class="text-xs text-gray-500">Descrizione</div><div class="mt-1 text-sm text-gray-700 whitespace-pre-wrap">${escapeHtml(position.description)}</div></div>` : ''}
			${Array.isArray(position.prices) && position.prices.length ? `
				<div>
					<div class="text-xs text-gray-500 mb-2">Prezzi per durata</div>
					<div class="flex flex-wrap gap-2">
						${position.prices.map(price => `<span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">${escapeHtml(String(price.duration_days ?? ''))} giorni · ${escapeHtml(String(price.price ?? ''))} EUR</span>`).join('')}
					</div>
				</div>
			` : ''}
			<div class="pt-4 border-t flex gap-3">
				${position._links?.edit ? `<a href="${position._links.edit}" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Modifica posizione</a>` : ''}
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
