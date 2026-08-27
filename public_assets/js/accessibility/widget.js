/**
 * ItalianCosplay.it — Accessibility Widget
 * --------------------------------------------------
 * Plugin esterno, autonomo, "plug & play".
 * NON dipende da nessun file del progetto, NON tocca
 * Controllers/Models/Views: lavora solo sul DOM lato client.
 *
 * Installazione: includere a fine <body> (o in <head> con defer)
 *   <script src="/assets/js/widget-accessibility.js" defer></script>
 *
 * Nessuna altra modifica al codice esistente è richiesta.
 * --------------------------------------------------
 */
(function () {
	"use strict";

	// Evita doppia inizializzazione se lo script viene incluso più volte
	if (window.__icA11yWidgetLoaded) return;
	window.__icA11yWidgetLoaded = true;

	const STORAGE_KEY = "ic_a11y_prefs";
	const PREFIX = "ic-a11y";

	const DEFAULT_PREFS = {
		fontScale: 0,        // step: -2..+5 (ogni step = +/-10%)
		contrast: "none",    // none | high | grayscale
		dyslexiaFont: false,
		highlightLinks: false,
		pauseAnimations: false,
		bigCursor: false,
		readingGuide: false,
		darkMode: false,
		textToSpeech: false,
		keyboardNav: false,
	};

	let prefs = loadPrefs();

	// ---------------------------------------------------
	// Persistenza (localStorage)
	// ---------------------------------------------------
	function loadPrefs() {
		try {
			const raw = localStorage.getItem(STORAGE_KEY);
			if (!raw) return { ...DEFAULT_PREFS };
			return { ...DEFAULT_PREFS, ...JSON.parse(raw) };
		} catch (e) {
			return { ...DEFAULT_PREFS };
		}
	}

	function savePrefs() {
		try {
			localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
		} catch (e) {
			/* localStorage non disponibile: il widget funziona comunque,
			   semplicemente non persiste tra le pagine */
		}
	}

	// ---------------------------------------------------
	// CSS iniettato (namespaced con PREFIX per non collidere
	// con TailwindCSS o altri stili del sito)
	// ---------------------------------------------------
	const css = `
  #${PREFIX}-toggle {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 2147483000;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #1e40af;
    color: #fff;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 14px rgba(0,0,0,.25);
    font-size: 26px;
    transition: transform .15s ease;
  }
  #${PREFIX}-toggle:hover { transform: scale(1.07); }
  #${PREFIX}-toggle:focus-visible {
    outline: 3px solid #fbbf24;
    outline-offset: 2px;
  }

  #${PREFIX}-panel {
    position: fixed;
    bottom: 86px;
    right: 20px;
    z-index: 2147483000;
    width: 320px;
    max-width: calc(100vw - 32px);
    max-height: 75vh;
    overflow-y: auto;
    background: #ffffff;
    color: #111827;
    border-radius: 14px;
    box-shadow: 0 10px 40px rgba(0,0,0,.3);
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    font-size: 14px;
    display: none;
    border: 1px solid #e5e7eb;
  }
  #${PREFIX}-panel.${PREFIX}-open { display: block; }

  #${PREFIX}-panel header {
    padding: 16px 18px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 700;
    font-size: 15px;
  }
  #${PREFIX}-panel header button {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    color: #6b7280;
    line-height: 1;
    padding: 4px;
  }
  #${PREFIX}-panel header button:hover { color: #111827; }

  .${PREFIX}-body { padding: 14px 18px 18px; }

  .${PREFIX}-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid #f3f4f6;
  }
  .${PREFIX}-row:last-child { border-bottom: none; }
  .${PREFIX}-row span { display: flex; align-items: center; gap: 8px; }

  .${PREFIX}-switch {
    position: relative;
    width: 42px;
    height: 24px;
    flex-shrink: 0;
  }
  .${PREFIX}-switch input { opacity: 0; width: 0; height: 0; }
  .${PREFIX}-slider {
    position: absolute;
    inset: 0;
    background: #d1d5db;
    border-radius: 999px;
    cursor: pointer;
    transition: background .15s ease;
  }
  .${PREFIX}-slider::before {
    content: "";
    position: absolute;
    width: 18px;
    height: 18px;
    left: 3px;
    top: 3px;
    background: #fff;
    border-radius: 50%;
    transition: transform .15s ease;
  }
  .${PREFIX}-switch input:checked + .${PREFIX}-slider { background: #1e40af; }
  .${PREFIX}-switch input:checked + .${PREFIX}-slider::before { transform: translateX(18px); }
  .${PREFIX}-switch input:focus-visible + .${PREFIX}-slider { outline: 2px solid #1e40af; outline-offset: 2px; }

  .${PREFIX}-fontctrl {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .${PREFIX}-fontctrl button {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    background: #f9fafb;
    cursor: pointer;
    font-weight: 700;
  }
  .${PREFIX}-fontctrl button:hover { background: #eef2ff; }

  .${PREFIX}-reset {
    width: 100%;
    margin-top: 6px;
    padding: 10px;
    border-radius: 10px;
    border: none;
    background: #ef4444;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
  }
  .${PREFIX}-reset:hover { background: #dc2626; }

  /* ---- Effetti applicati a html/body ---- */
  html.${PREFIX}-contrast-high {
    filter: contrast(1.35) brightness(1.05);
  }
  html.${PREFIX}-contrast-grayscale {
    filter: grayscale(1);
  }
  html.${PREFIX}-dyslexia-font, html.${PREFIX}-dyslexia-font * {
    font-family: "OpenDyslexic", "Comic Sans MS", Verdana, sans-serif !important;
    letter-spacing: 0.4px !important;
    line-height: 1.6 !important;
  }
  html.${PREFIX}-highlight-links a {
    outline: 2px solid #f59e0b !important;
    background: #fef3c7 !important;
    color: #92400e !important;
  }
  html.${PREFIX}-highlight-links h1,
  html.${PREFIX}-highlight-links h2,
  html.${PREFIX}-highlight-links h3,
  html.${PREFIX}-highlight-links h4 {
    outline: 2px dashed #2563eb !important;
    outline-offset: 2px;
  }
  html.${PREFIX}-pause-anim *,
  html.${PREFIX}-pause-anim *::before,
  html.${PREFIX}-pause-anim *::after {
    animation-play-state: paused !important;
    transition: none !important;
    scroll-behavior: auto !important;
  }
  html.${PREFIX}-big-cursor, html.${PREFIX}-big-cursor * {
    cursor: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"><path fill="black" stroke="white" stroke-width="1" d="M3 2l18 9-8 1-3 8-7-18z"/></svg>') 0 0, auto !important;
  }
  html.${PREFIX}-dark-mode {
    filter: invert(1) hue-rotate(180deg);
  }
  html.${PREFIX}-dark-mode img,
  html.${PREFIX}-dark-mode video,
  html.${PREFIX}-dark-mode picture,
  html.${PREFIX}-dark-mode svg,
  html.${PREFIX}-dark-mode iframe {
    filter: invert(1) hue-rotate(180deg);
  }

  /* ---- Navigazione da tastiera ---- */
  html.${PREFIX}-keyboard-nav *:focus-visible {
    outline: 3px solid #2563eb !important;
    outline-offset: 3px !important;
    box-shadow: 0 0 0 5px rgba(37, 99, 235, .25) !important;
    border-radius: 2px;
  }
  #${PREFIX}-skip-link {
    position: fixed;
    top: -60px;
    left: 12px;
    z-index: 2147483100;
    background: #1e40af;
    color: #fff;
    padding: 10px 16px;
    border-radius: 8px;
    font-family: system-ui, sans-serif;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    transition: top .15s ease;
  }
  #${PREFIX}-skip-link:focus {
    top: 12px;
  }

  /* ---- Lettura testo (TTS) ---- */
  html.${PREFIX}-tts-active [data-${PREFIX}-readable]:hover {
    outline: 2px dashed #16a34a !important;
    outline-offset: 2px;
    background: rgba(22, 163, 74, .08) !important;
    cursor: pointer !important;
  }
  html.${PREFIX}-tts-active .${PREFIX}-tts-speaking {
    outline: 2px solid #16a34a !important;
    background: rgba(22, 163, 74, .15) !important;
  }
  #${PREFIX}-tts-banner {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 2147483050;
    background: #16a34a;
    color: #fff;
    text-align: center;
    font-family: system-ui, sans-serif;
    font-size: 13px;
    padding: 6px 10px;
    display: none;
  }
  #${PREFIX}-tts-banner.${PREFIX}-show { display: block; }
  #${PREFIX}-tts-banner button {
    background: rgba(255,255,255,.2);
    border: 1px solid rgba(255,255,255,.5);
    color: #fff;
    border-radius: 6px;
    padding: 2px 10px;
    margin-left: 10px;
    cursor: pointer;
    font-size: 12px;
  }

  #${PREFIX}-reading-guide {
    position: fixed;
    left: 0;
    width: 100%;
    height: 32px;
    background: rgba(251, 191, 36, 0.35);
    border-top: 2px solid #f59e0b;
    border-bottom: 2px solid #f59e0b;
    pointer-events: none;
    z-index: 2147482999;
    display: none;
  }

  @media (max-width: 480px) {
    #${PREFIX}-panel { right: 12px; bottom: 80px; width: calc(100vw - 24px); }
    #${PREFIX}-toggle { right: 12px; bottom: 12px; }
  }
  `;

	function injectCss() {
		const style = document.createElement("style");
		style.id = `${PREFIX}-styles`;
		style.textContent = css;
		document.head.appendChild(style);
	}

	// ---------------------------------------------------
	// Markup pannello
	// ---------------------------------------------------
	function buildUI() {
		const toggle = document.createElement("button");
		toggle.id = `${PREFIX}-toggle`;
		toggle.type = "button";
		toggle.setAttribute("aria-label", "Apri opzioni di accessibilità");
		toggle.setAttribute("aria-expanded", "false");
		toggle.innerHTML = "&#9881;"; // gear icon (unicode, nessuna dipendenza esterna)

		const panel = document.createElement("div");
		panel.id = `${PREFIX}-panel`;
		panel.setAttribute("role", "dialog");
		panel.setAttribute("aria-label", "Pannello accessibilità");

		panel.innerHTML = `
      <header>
        <span>Accessibilità</span>
        <button type="button" id="${PREFIX}-close" aria-label="Chiudi pannello">&times;</button>
      </header>
      <div class="${PREFIX}-body">

        <div class="${PREFIX}-row">
          <span>Dimensione testo</span>
          <div class="${PREFIX}-fontctrl">
            <button type="button" id="${PREFIX}-font-dec" aria-label="Diminuisci testo">A-</button>
            <button type="button" id="${PREFIX}-font-inc" aria-label="Aumenta testo">A+</button>
          </div>
        </div>

        ${switchRow("contrast-high", "Contrasto alto")}
        ${switchRow("contrast-grayscale", "Scala di grigi")}
        ${switchRow("dyslexiaFont", "Font per dislessia")}
        ${switchRow("highlightLinks", "Evidenzia link/titoli")}
        ${switchRow("pauseAnimations", "Pausa animazioni")}
        ${switchRow("bigCursor", "Cursore grande")}
        ${switchRow("readingGuide", "Guida di lettura")}
        ${switchRow("darkMode", "Dark mode")}
        ${switchRow("textToSpeech", "Lettura testo ad alta voce")}
        ${switchRow("keyboardNav", "Navigazione da tastiera potenziata")}

        <button type="button" class="${PREFIX}-reset" id="${PREFIX}-reset">
          Ripristina impostazioni
        </button>
      </div>
    `;

		function switchRow(key, label) {
			return `
        <div class="${PREFIX}-row">
          <span>${label}</span>
          <label class="${PREFIX}-switch">
            <input type="checkbox" data-key="${key}" id="${PREFIX}-${key}">
            <span class="${PREFIX}-slider"></span>
          </label>
        </div>
      `;
		}

		document.body.appendChild(toggle);
		document.body.appendChild(panel);

		const readingGuideEl = document.createElement("div");
		readingGuideEl.id = `${PREFIX}-reading-guide`;
		document.body.appendChild(readingGuideEl);

		const skipLink = document.createElement("a");
		skipLink.id = `${PREFIX}-skip-link`;
		skipLink.href = "#";
		skipLink.textContent = "Salta al contenuto principale";
		skipLink.addEventListener("click", (e) => {
			e.preventDefault();
			const main =
				document.querySelector("main") ||
				document.querySelector("#main") ||
				document.querySelector("[role='main']");
			if (main) {
				if (!main.hasAttribute("tabindex")) main.setAttribute("tabindex", "-1");
				main.focus();
				main.scrollIntoView({ behavior: "smooth", block: "start" });
			}
		});
		document.body.insertBefore(skipLink, document.body.firstChild);

		const ttsBanner = document.createElement("div");
		ttsBanner.id = `${PREFIX}-tts-banner`;
		ttsBanner.innerHTML = `
      <span>🔊 Modalità lettura attiva: clicca su un testo per ascoltarlo</span>
      <button type="button" id="${PREFIX}-tts-stop">Stop</button>
    `;
		document.body.appendChild(ttsBanner);

		return { toggle, panel, readingGuideEl, skipLink, ttsBanner };
	}

	// ---------------------------------------------------
	// Applicazione preferenze al DOM
	// ---------------------------------------------------
	const html = document.documentElement;

	function applyAll(els) {
		// font scale
		const pct = 100 + prefs.fontScale * 10;
		html.style.setProperty("font-size", pct + "%");

		// contrast / grayscale (mutuamente esclusivi)
		html.classList.toggle(`${PREFIX}-contrast-high`, prefs.contrast === "high");
		html.classList.toggle(`${PREFIX}-contrast-grayscale`, prefs.contrast === "grayscale");

		html.classList.toggle(`${PREFIX}-dyslexia-font`, prefs.dyslexiaFont);
		html.classList.toggle(`${PREFIX}-highlight-links`, prefs.highlightLinks);
		html.classList.toggle(`${PREFIX}-pause-anim`, prefs.pauseAnimations);
		html.classList.toggle(`${PREFIX}-big-cursor`, prefs.bigCursor);
		html.classList.toggle(`${PREFIX}-dark-mode`, prefs.darkMode);
		html.classList.toggle(`${PREFIX}-keyboard-nav`, prefs.keyboardNav);
		html.classList.toggle(`${PREFIX}-tts-active`, prefs.textToSpeech);

		els.ttsBanner.classList.toggle(`${PREFIX}-show`, prefs.textToSpeech);
		if (!prefs.textToSpeech && window.speechSynthesis) {
			window.speechSynthesis.cancel();
		}

		els.readingGuideEl.style.display = prefs.readingGuide ? "block" : "none";

		// sync UI controls
		els.panel.querySelectorAll("input[data-key]").forEach((input) => {
			const key = input.dataset.key;
			if (key === "contrast-high") input.checked = prefs.contrast === "high";
			else if (key === "contrast-grayscale") input.checked = prefs.contrast === "grayscale";
			else input.checked = !!prefs[key];
		});

		savePrefs();
	}

	function resetPrefs(els) {
		prefs = { ...DEFAULT_PREFS };
		applyAll(els);
	}

	// ---------------------------------------------------
	// Lettura testo (TTS) — Web Speech API nativa
	// ---------------------------------------------------
	const READABLE_SELECTOR =
		"p, h1, h2, h3, h4, h5, h6, li, a, button, span, blockquote, figcaption, label, td, th";

	function markReadableElements() {
		// Marca dinamicamente gli elementi testuali "leggibili" (non invasivo:
		// aggiunge solo un data-attribute, nessuna modifica strutturale)
		document.querySelectorAll(READABLE_SELECTOR).forEach((el) => {
			if (el.closest(`#${PREFIX}-panel, #${PREFIX}-toggle, #${PREFIX}-tts-banner, #${PREFIX}-skip-link`)) return;
			const text = el.textContent.trim();
			if (text.length > 1) el.setAttribute(`data-${PREFIX}-readable`, "true");
		});
	}

	function bindTextToSpeech(els) {
		let currentEl = null;

		function speak(el) {
			if (!("speechSynthesis" in window)) {
				alert("La lettura vocale non è supportata da questo browser.");
				return;
			}
			const text = el.textContent.trim();
			if (!text) return;

			window.speechSynthesis.cancel();
			if (currentEl) currentEl.classList.remove(`${PREFIX}-tts-speaking`);

			const utterance = new SpeechSynthesisUtterance(text);
			utterance.lang = "it-IT";
			utterance.rate = 1;

			el.classList.add(`${PREFIX}-tts-speaking`);
			currentEl = el;

			utterance.onend = () => {
				el.classList.remove(`${PREFIX}-tts-speaking`);
				if (currentEl === el) currentEl = null;
			};

			window.speechSynthesis.speak(utterance);
		}

		document.addEventListener("click", (e) => {
			if (!prefs.textToSpeech) return;
			const target = e.target.closest(`[data-${PREFIX}-readable]`);
			if (!target) return;
			// Evita di intercettare i click sui controlli del widget stesso
			if (target.closest(`#${PREFIX}-panel, #${PREFIX}-toggle, #${PREFIX}-tts-banner`)) return;
			e.preventDefault();
			e.stopPropagation();
			speak(target);
		}, true);

		els.ttsBanner.querySelector(`#${PREFIX}-tts-stop`).addEventListener("click", () => {
			if (window.speechSynthesis) window.speechSynthesis.cancel();
			if (currentEl) {
				currentEl.classList.remove(`${PREFIX}-tts-speaking`);
				currentEl = null;
			}
		});
	}

	// ---------------------------------------------------
	// Navigazione da tastiera: skip-link visibile al primo Tab
	// ---------------------------------------------------
	function bindKeyboardNavDetection(els) {
		document.addEventListener(
			"keydown",
			(e) => {
				if (e.key === "Tab" && prefs.keyboardNav) {
					els.skipLink.style.top = "12px";
				}
			},
			{ once: false }
		);
	}

	// ---------------------------------------------------
	// Reading guide: segue il mouse
	// ---------------------------------------------------
	function bindReadingGuide(readingGuideEl) {
		document.addEventListener("mousemove", (e) => {
			if (!prefs.readingGuide) return;
			readingGuideEl.style.top = e.clientY - 16 + "px";
		});
	}

	// ---------------------------------------------------
	// Bind eventi pannello
	// ---------------------------------------------------
	function bindUI(els) {
		const { toggle, panel } = els;

		toggle.addEventListener("click", () => {
			const isOpen = panel.classList.toggle(`${PREFIX}-open`);
			toggle.setAttribute("aria-expanded", String(isOpen));
		});

		panel.querySelector(`#${PREFIX}-close`).addEventListener("click", () => {
			panel.classList.remove(`${PREFIX}-open`);
			toggle.setAttribute("aria-expanded", "false");
		});

		// chiudi con ESC
		document.addEventListener("keydown", (e) => {
			if (e.key === "Escape" && panel.classList.contains(`${PREFIX}-open`)) {
				panel.classList.remove(`${PREFIX}-open`);
				toggle.setAttribute("aria-expanded", "false");
			}
		});

		panel.querySelector(`#${PREFIX}-font-inc`).addEventListener("click", () => {
			prefs.fontScale = Math.min(5, prefs.fontScale + 1);
			applyAll(els);
		});
		panel.querySelector(`#${PREFIX}-font-dec`).addEventListener("click", () => {
			prefs.fontScale = Math.max(-2, prefs.fontScale - 1);
			applyAll(els);
		});

		panel.querySelectorAll("input[data-key]").forEach((input) => {
			input.addEventListener("change", () => {
				const key = input.dataset.key;
				if (key === "contrast-high") {
					prefs.contrast = input.checked ? "high" : "none";
				} else if (key === "contrast-grayscale") {
					prefs.contrast = input.checked ? "grayscale" : "none";
				} else {
					prefs[key] = input.checked;
				}
				applyAll(els);
			});
		});

		panel.querySelector(`#${PREFIX}-reset`).addEventListener("click", () => {
			resetPrefs(els);
		});
	}

	// ---------------------------------------------------
	// Init
	// ---------------------------------------------------
	function init() {
		injectCss();
		const els = buildUI();
		bindUI(els);
		bindReadingGuide(els.readingGuideEl);
		markReadableElements();
		bindTextToSpeech(els);
		bindKeyboardNavDetection(els);
		applyAll(els);

		// Ri-marca gli elementi leggibili se il DOM cambia (es. contenuti
		// caricati via AJAX/fetch, comuni nel blog o nella lista eventi)
		const observer = new MutationObserver(() => markReadableElements());
		observer.observe(document.body, { childList: true, subtree: true });
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", init);
	} else {
		init();
	}
})();
