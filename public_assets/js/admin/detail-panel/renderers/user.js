/**
 * Admin Detail Renderer - User
 */

window.adminDetailRenderers = window.adminDetailRenderers || {};

window.adminDetailRenderers.user = function (user) {
	const content = document.querySelector('[data-admin-detail-content]');
	if (!content) {
		return;
	}

	content.innerHTML = `
		<div class="space-y-5">
			<div>
				<h2 class="text-xl font-bold text-gray-800">${escapeHtml(user.username ?? '')}</h2>
				<div class="text-sm text-gray-500 mt-1">${escapeHtml(user.email ?? '')}</div>
			</div>
			<div class="grid grid-cols-2 gap-4">
				<div><div class="text-xs text-gray-500">Ruolo</div><div class="font-medium">${escapeHtml(user.role_name ?? 'N/D')}</div></div>
				<div><div class="text-xs text-gray-500">Verificato</div><div class="font-medium">${Number(user.verified) === 1 ? 'Sì' : 'No'}</div></div>
				<div><div class="text-xs text-gray-500">Dichiara 18+</div><div class="font-medium">${Number(user.age_declared_adult) === 1 ? 'Sì' : 'No'}</div></div>
				<div><div class="text-xs text-gray-500">Creato</div><div class="font-medium">${user.created_at ?? '-'}</div></div>
				<div><div class="text-xs text-gray-500">Aggiornato</div><div class="font-medium">${user.updated_at ?? '-'}</div></div>
			</div>
			${user.age_declared_at ? `<div><div class="text-xs text-gray-500">Dichiarazione 18+</div><div class="font-medium">${escapeHtml(user.age_declared_at)}</div></div>` : ''}
			${user.comune_name ? `<div><div class="text-xs text-gray-500">Comune</div><div class="font-medium">${escapeHtml(user.comune_name)}</div></div>` : ''}
			${user.first_name || user.last_name ? `<div><div class="text-xs text-gray-500">Nome completo</div><div class="font-medium">${escapeHtml((user.first_name ?? '') + ' ' + (user.last_name ?? '')).trim()}</div></div>` : ''}
			${user.website ? `<div><div class="text-xs text-gray-500">Website</div><div class="font-medium"><a class="text-blue-600 hover:underline" href="${escapeHtml(user.website)}" target="_blank">${escapeHtml(user.website)}</a></div></div>` : ''}
			${user.bio ? `<div><div class="text-xs text-gray-500">Bio</div><div class="mt-1 text-sm text-gray-700 whitespace-pre-wrap">${escapeHtml(user.bio)}</div></div>` : ''}
			${user.social ? `<div><div class="text-xs text-gray-500">Social</div><div class="mt-1 text-sm text-gray-700 whitespace-pre-wrap">${escapeHtml(user.social)}</div></div>` : ''}
			<div class="pt-4 border-t flex gap-3">
				<a href="/admin/users/edit/${user.id}" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Modifica utente</a>
			</div>
		</div>
	`;
};
