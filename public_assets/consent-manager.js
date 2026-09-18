// File: consent-manager.js
document.addEventListener('DOMContentLoaded', () => {
	function getCookie(name) {
		const value = `; ${document.cookie}`;
		const parts = value.split(`; ${name}=`);
		if (parts.length === 2) {
			return parts.pop().split(';').shift();
		}
		return null;
	}

	const consentBanner = document.getElementById('cookie-banner');
	const btnAcceptAll = document.getElementById('btn-accept-all');
	const btnAcceptSelected = document.getElementById('btn-accept-selected');
	const manageButton = document.getElementById('manage-consent');

	function showConsentBanner() {
		if (consentBanner) {
			consentBanner.style.display = 'block';
		}
	}

	function hideConsentBanner() {
		if (consentBanner) {
			consentBanner.style.display = 'none';
		}
	}

	async function saveConsent(details) {
		try {
			const response = await fetch('/save-consent.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(details),
			});

			const result = await response.json();

			if (result.status === 'success') {
				hideConsentBanner();
				activateScripts(details);
			}
		} catch (error) {
			console.error('Errore di rete:', error);
		}
	}

	function activateScripts(consentDetails) {
		document.querySelectorAll('script[data-consent-category]').forEach((script) => {
			const category = script.dataset.consentCategory;
			if (consentDetails[category]) {
				const newScript = document.createElement('script');
				for (let i = 0; i < script.attributes.length; i++) {
					const attr = script.attributes[i];
					newScript.setAttribute(attr.name, attr.value);
				}
				newScript.type = 'text/javascript';
				newScript.innerHTML = script.innerHTML;
				script.parentNode.replaceChild(newScript, script);
			}
		});
	}

	if (btnAcceptAll) {
		btnAcceptAll.addEventListener('click', () => {
			saveConsent({ analytics: true, marketing: true });
		});
	}

	if (btnAcceptSelected) {
		btnAcceptSelected.addEventListener('click', () => {
			saveConsent({
				analytics: document.getElementById('consent-analytics').checked,
				marketing: document.getElementById('consent-marketing').checked,
			});
		});
	}

	if (manageButton) {
		manageButton.addEventListener('click', (event) => {
			event.preventDefault();
			showConsentBanner();
		});
	}

	function initializeConsent() {
		const consentCookie = getCookie('user_cookie_consent');

		if (consentCookie) {
			try {
				const decodedConsent = decodeURIComponent(consentCookie);
				const consentDetails = JSON.parse(decodedConsent);
				activateScripts(consentDetails);
				hideConsentBanner();
			} catch (e) {
				console.error('Errore nel parsing del cookie di consenso:', e);
				showConsentBanner();
			}
		} else {
			showConsentBanner();
		}
	}

	initializeConsent();
});
