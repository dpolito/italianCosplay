window.adminDetailRenderers = window.adminDetailRenderers || {};

window.adminDetailRenderers.emailDeliveryEvent = function (event) {
	const content = document.querySelector('[data-admin-detail-content]');
	if (!content) {
		return;
	}

	const rawPayload = event.raw_payload ?? {};
	const tags = Array.isArray(event.tags) ? event.tags : [];

	content.innerHTML = `
		<div class="space-y-5">
			<div>
				<h2 class="text-xl font-bold text-gray-800">${escapeHtml(formatEmailEvent(event.event_name))}</h2>
				<div class="mt-1 text-sm text-gray-500">${escapeHtml(formatDateTime(event.event_date || event.created_at))}</div>
			</div>

			<div class="grid grid-cols-1 gap-3 text-sm">
				${detailRow('Email', event.email)}
				${detailRow('Oggetto', event.subject)}
				${detailRow('Tag', tags.length ? tags.join(', ') : (event.tag || 'senza_tag'))}
				${detailRow('Message ID', event.message_id)}
				${detailRow('Template ID', event.template_id)}
				${detailRow('IP invio', event.sending_ip)}
				${detailRow('Motivo', event.reason)}
			</div>

			<div>
				<div class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">Payload Brevo</div>
				<pre class="max-h-96 overflow-auto rounded-lg bg-gray-950 p-3 text-xs leading-relaxed text-gray-100">${escapeHtml(JSON.stringify(rawPayload, null, 2))}</pre>
			</div>
		</div>
	`;
};

function detailRow(label, value) {
	if (value === null || value === undefined || value === '') {
		value = '-';
	}

	return `
		<div>
			<div class="text-xs font-semibold uppercase tracking-wide text-gray-500">${escapeHtml(label)}</div>
			<div class="mt-1 break-words font-medium text-gray-900">${escapeHtml(String(value))}</div>
		</div>
	`;
}

function formatEmailEvent(value) {
	const labels = {
		request: 'Inviata',
		sent: 'Inviata',
		delivered: 'Consegnata',
		opened: 'Aperta',
		uniqueOpened: 'Prima apertura',
		unique_opened: 'Prima apertura',
		click: 'Click',
		clicked: 'Click',
		softBounce: 'Soft bounce',
		soft_bounce: 'Soft bounce',
		hardBounce: 'Hard bounce',
		hard_bounce: 'Hard bounce',
		blocked: 'Bloccata',
		spam: 'Spam',
		invalid: 'Non valida',
		deferred: 'Rimandata',
		unsubscribed: 'Disiscritta',
	};

	return labels[value] || String(value || 'N/D').replace(/_/g, ' ');
}

function formatDateTime(value) {
	if (!value) {
		return '-';
	}

	return new Date(String(value).replace(' ', 'T')).toLocaleString('it-IT', {
		day: '2-digit',
		month: '2-digit',
		year: 'numeric',
		hour: '2-digit',
		minute: '2-digit',
	});
}

function escapeHtml(value) {
	return String(value).replace(/[&<>"']/g, char => ({
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;',
	})[char]);
}
