/**
 * Admin Detail Renderer - Blog
 */

window.adminDetailRenderers = window.adminDetailRenderers || {};

window.adminDetailRenderers.blog = function (post) {
	const content = document.querySelector('[data-admin-detail-content]');
	if (!content) {
		return;
	}

	content.innerHTML = `
		<div class="space-y-5">
			<div>
				<h2 class="text-xl font-bold text-gray-800">${escapeHtml(post.titolo ?? '')}</h2>
				${post.slug ? `<div class="text-sm text-gray-500 mt-1">${escapeHtml(post.slug)}</div>` : ''}
			</div>
			<div class="grid grid-cols-2 gap-4">
				<div><div class="text-xs text-gray-500">Status</div><div class="font-medium">${escapeHtml(post.status ?? '-')}</div></div>
				<div><div class="text-xs text-gray-500">Views</div><div class="font-medium">${post.views ?? 0}</div></div>
				<div><div class="text-xs text-gray-500">Creato</div><div class="font-medium">${post.created_at ?? '-'}</div></div>
				<div><div class="text-xs text-gray-500">Aggiornato</div><div class="font-medium">${post.updated_at ?? '-'}</div></div>
			</div>
			${post.excerpt ? `<div><div class="text-xs text-gray-500">Excerpt</div><div class="mt-1 text-sm text-gray-700">${escapeHtml(post.excerpt)}</div></div>` : ''}
			<div class="pt-4 border-t flex gap-3">
				<a href="${post._links?.edit ?? '#'}" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Modifica articolo</a>
				${post._links?.view ? `<a href="${post._links.view}" target="_blank" class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-white hover:bg-gray-900">Apri pubblico</a>` : ''}
			</div>
		</div>
	`;
};
