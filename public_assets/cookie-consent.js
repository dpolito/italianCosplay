// File: cookie-consent.js
document.addEventListener('DOMContentLoaded', () => {

	// --- UTILITY FUNCTIONS ---
	// Funzione per leggere un cookie per nome
	function getCookie(name) {
		const value = `; ${document.cookie}`;
		const parts = value.split(`; ${name}=`);
		if (parts.length === 2) {
			return parts.pop().split(';').shift();
		}
		return null;
	}

	// --- DOM ELEMENTS ---
	const consentBanner = document.getElementById('cookie-banner');
	const btnAcceptAll = document.getElementById('btn-accept-all');
	const btnAcceptSelected = document.getElementById('btn-accept-selected');
	const manageButton = document.getElementById('manage-consent');

	// --- CORE LOGIC FUNCTIONS ---
	// Funzione per mostrare il banner del consenso
	function showConsentBanner() {
		if (consentBanner) {
			consentBanner.style.display = 'block';
		}
	}

	// Funzione per nascondere il banner del consenso
	function hideConsentBanner() {
		if (consentBanner) {
			consentBanner.style.display = 'none';
		}
	}

	// Funzione per salvare il consenso
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
				console.log('Consenso salvato con successo.');
				hideConsentBanner();
				activateScripts(details);
			} else {
				console.error('Errore nel salvataggio del consenso:', result.message);
			}
		} catch (error) {
			console.error('Errore di rete:', error);
		}
	}

	// Funzione per attivare gli script
	function activateScripts(consentDetails) {
		document.querySelectorAll('script[data-consent-category]').forEach(script => {
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

	// --- EVENT LISTENERS ---
	if (btnAcceptAll) {
		btnAcceptAll.addEventListener('click', () => {
			const details = { analytics: true, marketing: true };
			saveConsent(details);
		});
	}

	if (btnAcceptSelected) {
		btnAcceptSelected.addEventListener('click', () => {
			const details = {
				analytics: document.getElementById('consent-analytics').checked,
				marketing: document.getElementById('consent-marketing').checked
			};
			saveConsent(details);
		});
	}

	if (manageButton) {
		manageButton.addEventListener('click', (event) => {
			event.preventDefault();
			showConsentBanner();
		});
	}

	// --- INITIALIZATION ---
	// --- INITIALIZATION ---
	function initializeConsent() {
		const consentCookie = getCookie('user_cookie_consent');

		if (consentCookie) {
			// Se il cookie esiste...
			try {
				const decodedConsent = decodeURIComponent(consentCookie);
				const consentDetails = JSON.parse(decodedConsent);

				// 1. Attiva gli script
				activateScripts(consentDetails);

				// 2. NASCONDI il banner (nuova riga aggiunta)
				hideConsentBanner();

			} catch (e) {
				// Se il cookie è corrotto, mostra il banner per chiedere il consenso di nuovo
				console.error("Errore nel parsing del cookie di consenso:", e);
				showConsentBanner();
			}
		} else {
			// Se il cookie non esiste, mostra il banner
			showConsentBanner();
		}
	}

	initializeConsent();
});
