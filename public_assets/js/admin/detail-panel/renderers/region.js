/**
 * Admin Detail Renderer - Region
 */

window.adminDetailRenderers = window.adminDetailRenderers || {};

window.adminDetailRenderers.region = function (region) {
	const content = document.querySelector('[data-admin-detail-content]');
	if (!content) {
		return;
	}

	content.innerHTML = `
		<div class="space-y-5">
			<div>
				<h2 class="text-xl font-bold text-gray-800">${escapeHtml(region.nome ?? '')}</h2>
				${region.slug ? `<div class="text-sm text-gray-500 mt-1">${escapeHtml(region.slug)}</div>` : ''}
			</div>
			<div class="grid grid-cols-1 gap-4">
				<div><div class="text-xs text-gray-500">SEO Title</div><div class="font-medium">${escapeHtml(region.seo_title ?? '-')}</div></div>
				<div><div class="text-xs text-gray-500">SEO Description</div><div class="font-medium">${escapeHtml(region.seo_description ?? '-')}</div></div>
			</div>
			${region.intro_html ? `<div><div class="text-xs text-gray-500">Intro HTML</div><div class="mt-1 text-sm text-gray-700 whitespace-pre-wrap">${escapeHtml(region.intro_html)}</div></div>` : ''}
			<div class="pt-4 border-t flex gap-3">
				<a href="/admin/regioni/edit/${region.id}" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Modifica regione</a>
			</div>
		</div>
	`;
};
