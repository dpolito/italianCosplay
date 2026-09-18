/**
 * Admin Detail Renderer - Advertising Campaign
 */

window.adminDetailRenderers = window.adminDetailRenderers || {};

window.adminDetailRenderers.adCampaign = function (campaign) {
	const content = document.querySelector('[data-admin-detail-content]');
	if (!content) {
		return;
	}

	const csrfToken = window.adminList?.config?.csrfToken ?? '';

	content.innerHTML = `
		<div class="space-y-5">
			<div>
				<h2 class="text-xl font-bold text-gray-800">${escapeHtml(campaign.banner_title ?? 'Campagna sponsor')}</h2>
				${campaign.position_name ? `<div class="text-sm text-gray-500 mt-1">${escapeHtml(campaign.position_name)}</div>` : ''}
			</div>

			<div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
				<p class="font-bold">Campagna bloccata, banner aggiornabile</p>
				<p class="mt-2 leading-relaxed">
					I dati commerciali della campagna non vanno modificati qui. Se serve cambiare creatività, testo o link,
					l'intervento corretto è sul banner collegato.
				</p>
				${campaign.banner_id ? `
					<a href="/admin/ads/banners/${campaign.banner_id}/edit" class="mt-3 inline-flex rounded-md bg-amber-900 px-4 py-2 font-semibold text-white hover:bg-amber-800">
						Apri banner associato
					</a>
				` : ''}
			</div>

			<div class="grid grid-cols-2 gap-4">
				<div>
					<div class="text-xs text-gray-500">Utente</div>
					<div class="font-medium">
						${campaign.user_id ? `<a href="/admin/users/edit/${campaign.user_id}" class="text-green-700 hover:underline">${escapeHtml(campaign.username ?? '-')}</a>` : escapeHtml(campaign.username ?? '-')}
					</div>
				</div>
				<div><div class="text-xs text-gray-500">Stato</div><div class="font-medium">${escapeHtml(formatCampaignStatus(campaign.approval_status ?? campaign.status ?? '-'))}</div></div>
				<div><div class="text-xs text-gray-500">Inizio</div><div class="font-medium">${campaign.start_date ?? '-'}</div></div>
				<div><div class="text-xs text-gray-500">Fine</div><div class="font-medium">${campaign.end_date ?? '-'}</div></div>
				<div><div class="text-xs text-gray-500">Prezzo</div><div class="font-medium">${escapeHtml(String(campaign.price ?? 0))} ${escapeHtml(campaign.currency ?? 'EUR')}</div></div>
				<div><div class="text-xs text-gray-500">CTR</div><div class="font-medium">${campaign.ctr ?? '-'}%</div></div>
				<div><div class="text-xs text-gray-500">Impression</div><div class="font-medium">${formatNumber(campaign.impressions ?? 0)}</div></div>
				<div><div class="text-xs text-gray-500">Click</div><div class="font-medium">${formatNumber(campaign.clicks ?? 0)}</div></div>
				<div><div class="text-xs text-gray-500">CTR</div><div class="font-medium">${formatNumber(campaign.ctr ?? 0)}%</div></div>
			</div>

			${campaign.banner_description ? `<div><div class="text-xs text-gray-500">Descrizione</div><div class="mt-1 text-sm text-gray-700 whitespace-pre-wrap">${escapeHtml(campaign.banner_description)}</div></div>` : ''}
			${campaign.notes ? `<div><div class="text-xs text-gray-500">Note</div><div class="mt-1 text-sm text-gray-700 whitespace-pre-wrap">${escapeHtml(campaign.notes)}</div></div>` : ''}
			${campaign.image_path ? `<div><img src="${escapeHtml(campaign.image_path)}" alt="${escapeHtml(campaign.banner_title ?? 'Banner')}" class="w-full rounded-lg object-cover"></div>` : ''}

			<div class="pt-4 border-t flex flex-wrap gap-3">
				${campaign._links?.edit ? `<a href="${campaign._links.edit}" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Apri dettaglio admin</a>` : ''}
				${campaign.banner_id ? `<a href="/admin/ads/banners/${campaign.banner_id}/edit" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-gray-800 hover:bg-gray-50">Modifica banner</a>` : ''}
				${campaign._links?.approve ? `<button type="button" data-ad-campaign-action="approve" data-url="${campaign._links.approve}" data-csrf="${csrfToken}" class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-white hover:bg-green-700">Approva</button>` : ''}
				${campaign._links?.reject ? `<button type="button" data-ad-campaign-action="reject" data-url="${campaign._links.reject}" data-csrf="${csrfToken}" class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-white hover:bg-red-700">Rifiuta</button>` : ''}
				${campaign._links?.request_changes ? `<button type="button" data-ad-campaign-action="request_changes" data-url="${campaign._links.request_changes}" data-csrf="${csrfToken}" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-gray-800 hover:bg-gray-50">Richiedi modifiche</button>` : ''}
			</div>
		</div>
	`;

	content.querySelectorAll('[data-ad-campaign-action]').forEach(button => {
		button.addEventListener('click', async () => {
			const url = button.dataset.url;
			const csrf = button.dataset.csrf || '';
			const note = window.prompt('Inserisci una nota opzionale', '') ?? '';
			const formData = new FormData();
			formData.append('csrf_token', csrf);
			formData.append('admin_notes', note);
			formData.append('ajax', '1');

			try {
				const response = await fetch(url, {
					method: 'POST',
					body: formData,
					headers: { 'Accept': 'application/json' },
				});

				const json = await response.json();
				if (!json.success) {
					throw new Error(json.message || 'Operazione non riuscita');
				}

				document.dispatchEvent(new CustomEvent('admin-list:reload'));
				window.alert(json.message || 'Operazione completata.');
			} catch (error) {
				window.alert(error.message || 'Operazione non riuscita');
			}
		});
	});
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

function formatCampaignStatus(status) {
	switch (String(status)) {
		case 'pending_payment':
			return 'In attesa di pagamento';
		case 'pending_approval':
			return 'In attesa di approvazione';
		case 'scheduled':
			return 'Programmata';
		case 'active':
			return 'Attiva';
		case 'paused':
			return 'In pausa';
		case 'expired':
			return 'Scaduta';
		case 'cancelled':
			return 'Annullata';
		case 'rejected':
			return 'Rifiutata';
		case 'changes_requested':
			return 'Modifiche richieste';
		default:
			return String(status).replace(/_/g, ' ');
	}
}

function formatNumber(value) {
	return new Intl.NumberFormat('it-IT').format(Number(value) || 0);
}
