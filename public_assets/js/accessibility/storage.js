/**
 * Italian Accessibility
 * Storage Manager
 * Version 1.0
 */

export class Storage {

	static KEY = "italianAccessibility";

	static defaults = {
		profile: "",

		fontScale: 100,
		lineHeight: 1.5,
		letterSpacing: 0,

		contrast: "normal",
		darkMode: false,
		grayscale: false,
		invert: false,
		saturation: 100,

		cursor: "normal",
		highlightLinks: false,
		highlightTitles: false,

		readingGuide: false,
		readingMask: false,

		stopAnimations: false,
		stopVideos: false,

		dyslexicFont: false,

		speech: false
	};

	/**
	 * Restituisce tutte le impostazioni
	 */
	static get() {

		try {

			const data = localStorage.getItem(Storage.KEY);

			if (!data) {
				return { ...Storage.defaults };
			}

			return {
				...Storage.defaults,
				...JSON.parse(data)
			};

		} catch (e) {

			console.error("Accessibility Storage Error", e);

			return { ...Storage.defaults };

		}

	}

	/**
	 * Salva tutte le impostazioni
	 */
	static save(settings) {

		localStorage.setItem(
			Storage.KEY,
			JSON.stringify(settings)
		);

	}

	/**
	 * Legge una singola proprietà
	 */
	static getValue(key) {

		return Storage.get()[key];

	}

	/**
	 * Aggiorna una singola proprietà
	 */
	static setValue(key, value) {

		const settings = Storage.get();

		settings[key] = value;

		Storage.save(settings);

	}

	/**
	 * Ripristina impostazioni
	 */
	static reset() {

		localStorage.removeItem(Storage.KEY);

	}

	/**
	 * Esporta configurazione
	 */
	static export() {

		return JSON.stringify(
			Storage.get(),
			null,
			2
		);

	}

	/**
	 * Importa configurazione
	 */
	static import(json) {

		try {

			const settings = JSON.parse(json);

			Storage.save({
				...Storage.defaults,
				...settings
			});

			return true;

		} catch (e) {

			return false;

		}

	}

}
