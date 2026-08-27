/**
 * WYSIWYG Editor - editor di testo semplice e autonomo
 * =====================================================
 * Nessuna dipendenza esterna. Basta includere questo file in una pagina HTML
 * e chiamare `WysiwygEditor.init(target)` per creare un editor.
 *
 * USO RAPIDO
 * ----------
 * <div id="editor"></div>
 * <script src="wysiwyg-editor.js"></script>
 * <script>
 *   const editor = WysiwygEditor.init('#editor', {
 *     placeholder: 'Scrivi qui...',
 *     content: '<p>Testo iniziale</p>'
 *   });
 *
 *   // Per leggere il contenuto (es. prima di inviare un form):
 *   const html = editor.getContent();
 * </script>
 *
 * In alternativa, basta aggiungere data-wysiwyg a un elemento e verrà
 * inizializzato automaticamente al caricamento della pagina:
 * <div data-wysiwyg></div>
 */

(function (global) {
  'use strict';

  const STYLE_ID = 'wysiwyg-editor-styles';

  const CSS = `
.wys-editor {
  --wys-border: #d8d8dc;
  --wys-bg: #ffffff;
  --wys-toolbar-bg: #f7f7f9;
  --wys-accent: #3f6fb4;
  --wys-text: #1f2328;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  border: 1px solid var(--wys-border);
  border-radius: 8px;
  overflow: hidden;
  background: var(--wys-bg);
  color: var(--wys-text);
}
.wys-toolbar {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 8px;
  background: var(--wys-toolbar-bg);
  border-bottom: 1px solid var(--wys-border);
  flex-wrap: wrap;
}
.wys-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 32px;
  height: 32px;
  padding: 0 8px;
  border: 1px solid transparent;
  border-radius: 6px;
  background: transparent;
  color: var(--wys-text);
  font-size: 14px;
  cursor: pointer;
  user-select: none;
  transition: background 0.12s ease, border-color 0.12s ease;
}
.wys-btn:hover { background: #e9e9ee; }
.wys-btn.active { background: #dfe8f7; border-color: var(--wys-accent); color: var(--wys-accent); }
.wys-btn-bold { font-weight: 700; }
.wys-btn-italic { font-style: italic; }
.wys-btn-underline { text-decoration: underline; }
.wys-sep {
  width: 1px;
  height: 22px;
  background: var(--wys-border);
  margin: 0 4px;
}
.wys-btn-source {
  margin-left: auto;
  font-size: 13px;
  font-weight: 500;
  gap: 6px;
  padding: 0 10px;
}
.wys-body {
  position: relative;
  min-height: 160px;
}
.wys-content {
  min-height: 160px;
  max-height: 480px;
  overflow-y: auto;
  padding: 12px 14px;
  outline: none;
  line-height: 1.5;
  font-size: 15px;
}
.wys-content:empty:before {
  content: attr(data-placeholder);
  color: #9a9aa2;
  pointer-events: none;
}
.wys-source {
  display: none;
  width: 100%;
  min-height: 160px;
  max-height: 480px;
  box-sizing: border-box;
  border: none;
  outline: none;
  resize: vertical;
  padding: 12px 14px;
  font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
  font-size: 13px;
  line-height: 1.5;
  color: var(--wys-text);
  background: #fbfbfc;
}
.wys-editor.wys-source-mode .wys-content { display: none; }
.wys-editor.wys-source-mode .wys-source { display: block; }
`;

  function injectStylesOnce() {
    if (document.getElementById(STYLE_ID)) return;
    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = CSS;
    document.head.appendChild(style);
  }

  // Formattazione leggibile del sorgente HTML (indentazione semplice a scopo di lettura)
  function prettyPrintHtml(html) {
    const noTags = html.replace(/></g, '>\n<');
    const lines = noTags.split('\n');
    let indent = 0;
    const inline = new Set(['b', 'strong', 'i', 'em', 'u', 'br', 'span', 'a']);
    return lines
      .map((line) => {
        const tagMatch = line.match(/^<\/?([a-zA-Z0-9]+)/);
        const tagName = tagMatch ? tagMatch[1].toLowerCase() : '';
        const isClosing = /^<\//.test(line);
        const isSelfClosing = /\/>$/.test(line) || tagName === 'br' || tagName === 'img';
        const isInline = inline.has(tagName);

        if (isClosing && !isInline) indent = Math.max(0, indent - 1);
        const result = '  '.repeat(indent) + line;
        if (!isClosing && !isSelfClosing && !isInline && tagName) indent++;
        return result;
      })
      .join('\n');
  }

  class WysiwygEditor {
    constructor(target, options = {}) {
      this.target = typeof target === 'string' ? document.querySelector(target) : target;
      if (!this.target) throw new Error('WysiwygEditor: elemento target non trovato');

      this.options = Object.assign(
        { placeholder: 'Scrivi qui...', content: '', name: null },
        options
      );

      injectStylesOnce();
      this._build();
    }

    _build() {
      const wrapper = document.createElement('div');
      wrapper.className = 'wys-editor';

      wrapper.innerHTML = `
        <div class="wys-toolbar">
          <button type="button" class="wys-btn wys-btn-bold" data-cmd="bold" title="Grassetto">B</button>
          <button type="button" class="wys-btn wys-btn-italic" data-cmd="italic" title="Corsivo">I</button>
          <button type="button" class="wys-btn wys-btn-underline" data-cmd="underline" title="Sottolineato">U</button>
          <span class="wys-sep"></span>
          <button type="button" class="wys-btn wys-btn-source" data-action="toggle-source" title="Visualizza codice sorgente">&lt;/&gt; Sorgente</button>
        </div>
        <div class="wys-body">
          <div class="wys-content" contenteditable="true" data-placeholder="${this.options.placeholder}"></div>
          <textarea class="wys-source" spellcheck="false"></textarea>
        </div>
      `;

      this.target.innerHTML = '';
      this.target.appendChild(wrapper);

      this.el = wrapper;
      this.contentEl = wrapper.querySelector('.wys-content');
      this.sourceEl = wrapper.querySelector('.wys-source');
      this.toolbar = wrapper.querySelector('.wys-toolbar');
      this.sourceBtn = wrapper.querySelector('[data-action="toggle-source"]');

      this.contentEl.innerHTML = this.options.content || '';
      this._sourceMode = false;

      // Campo nascosto reale (textarea) che viene inviato col form, dato che
      // un div contenteditable NON fa parte dei dati trasmessi in un submit.
      if (this.options.name) {
        this.hiddenField = document.createElement('textarea');
        this.hiddenField.name = this.options.name;
        this.hiddenField.style.display = 'none';
        this.hiddenField.value = this.contentEl.innerHTML;
        wrapper.appendChild(this.hiddenField);
      }

      this._bindEvents();
      this._syncHiddenField();
    }

    _bindEvents() {
      // Pulsanti di formattazione (bold / italic / underline)
      this.toolbar.querySelectorAll('[data-cmd]').forEach((btn) => {
        btn.addEventListener('mousedown', (e) => e.preventDefault()); // evita perdita selezione
        btn.addEventListener('click', () => {
          document.execCommand(btn.dataset.cmd, false, null);
          this.contentEl.focus();
          this._updateToolbarState();
        });
      });

      // Toggle vista sorgente
      this.sourceBtn.addEventListener('click', () => this.toggleSource());

      // Aggiorna stato bottoni (attivo/non attivo) mentre si scrive o si seleziona
      this.contentEl.addEventListener('keyup', () => this._updateToolbarState());
      this.contentEl.addEventListener('mouseup', () => this._updateToolbarState());
      this.contentEl.addEventListener('focus', () => this._updateToolbarState());

      // Mantiene sincronizzato il campo nascosto ad ogni modifica del contenuto
      this.contentEl.addEventListener('input', () => this._syncHiddenField());
      this.sourceEl.addEventListener('input', () => this._syncHiddenField());

      // Sicurezza extra: risincronizza appena prima dell'invio del form
      const form = this.target.closest('form');
      if (form) {
        form.addEventListener('submit', () => this._syncHiddenField());
      }
    }

    /** Copia il contenuto corrente nel campo nascosto usato per il submit del form */
    _syncHiddenField() {
      if (!this.hiddenField) return;
      this.hiddenField.value = this._sourceMode ? this.sourceEl.value : this.contentEl.innerHTML;
    }

    _updateToolbarState() {
      const map = { bold: 'wys-btn-bold', italic: 'wys-btn-italic', underline: 'wys-btn-underline' };
      Object.entries(map).forEach(([cmd, cls]) => {
        const btn = this.toolbar.querySelector('.' + cls);
        try {
          btn.classList.toggle('active', document.queryCommandState(cmd));
        } catch (e) {
          /* alcuni browser possono sollevare errori se non c'è selezione */
        }
      });
    }

    /** Passa dalla vista visiva a quella del codice sorgente e viceversa */
    toggleSource() {
      if (!this._sourceMode) {
        // da visuale -> sorgente
        this.sourceEl.value = prettyPrintHtml(this.contentEl.innerHTML.trim());
      } else {
        // da sorgente -> visuale
        this.contentEl.innerHTML = this.sourceEl.value;
      }
      this._sourceMode = !this._sourceMode;
      this.el.classList.toggle('wys-source-mode', this._sourceMode);
      this.sourceBtn.classList.toggle('active', this._sourceMode);
      this._syncHiddenField();
    }

    /** Ritorna il contenuto HTML corrente dell'editor */
    getContent() {
      if (this._sourceMode) return this.sourceEl.value;
      return this.contentEl.innerHTML;
    }

    /** Imposta il contenuto HTML dell'editor */
    setContent(html) {
      this.contentEl.innerHTML = html;
      if (this._sourceMode) this.sourceEl.value = prettyPrintHtml(html);
      this._syncHiddenField();
    }
  }

  // API pubblica
  const api = {
    init(target, options) {
      return new WysiwygEditor(target, options);
    },
    WysiwygEditor,
  };

  global.WysiwygEditor = api;

  // Auto-init per elementi con attributo data-wysiwyg
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-wysiwyg]').forEach((el) => {
      if (!el.dataset.wysInitialized) {
        api.init(el);
        el.dataset.wysInitialized = 'true';
      }
    });
  });
})(window);
