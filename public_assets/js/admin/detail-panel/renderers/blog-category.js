/**
 * Admin Detail Renderer - Blog Category
 */

window.adminDetailRenderers = window.adminDetailRenderers || {};

window.adminDetailRenderers.blogCategory = function (category) {
	const content = document.querySelector('[data-admin-detail-content]');
	if (!content) {
		return;
	}

	content.innerHTML = `
		<div class="space-y-5">
			<div>
				<h2 class="text-xl font-bold text-gray-800">${escapeHtml(category.name ?? '')}</h2>
				${category.slug ? `<div class="text-sm text-gray-500 mt-1">${escapeHtml(category.slug)}</div>` : ''}
			</div>
			<div class="grid grid-cols-2 gap-4">
				<div><div class="text-xs text-gray-500">SEO Title</div><div class="font-medium">${escapeHtml(category.seo_title ?? '-')}</div></div>
				<div><div class="text-xs text-gray-500">Creata</div><div class="font-medium">${category.created_at ?? '-'}</div></div>
			</div>
			${category.description ? `<div><div class="text-xs text-gray-500">Descrizione</div><div class="mt-1 text-sm text-gray-700">${escapeHtml(category.description)}</div></div>` : ''}
			${category.seo_description ? `<div><div class="text-xs text-gray-500">SEO Description</div><div class="mt-1 text-sm text-gray-700">${escapeHtml(category.seo_description)}</div></div>` : ''}
			<div class="pt-4 border-t flex gap-3">
				<a href="/admin/blog-categories/edit/${category.id}" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Modifica categoria</a>
			</div>
		</div>
	`;
};
