// Formatierungs-Legende unter jedes <textarea data-md> + Klick / Tastenkürzel zum Formatieren
(() => {
    const tpl = document.getElementById('hvMdLegend');
    if (!tpl) return;

    function wrap(ta, marker) {
        const v = ta.value;
        const n = marker.length;
        let s = ta.selectionStart, e = ta.selectionEnd;
        // Leerzeichen am Rand nicht mit einschließen (Doppelklick unter Windows)
        while (s < e && /\s/.test(v[s])) s++;
        while (e > s && /\s/.test(v[e - 1])) e--;

        if (s >= n && v.slice(s - n, s) === marker && v.slice(e, e + n) === marker) {
            // Schon formatiert → Zeichen wieder entfernen
            ta.setRangeText(v.slice(s, e), s - n, e + n, 'select');
        } else {
            const text = v.slice(s, e) || 'Text';   // nichts markiert → Platzhalter zum Überschreiben
            ta.setRangeText(marker + text + marker, s, e, 'end');
            ta.setSelectionRange(s + n, s + n + text.length);
        }
        ta.focus();
        ta.dispatchEvent(new Event('input', { bubbles: true }));   // z. B. Zeichenzähler aktualisieren
    }

    document.querySelectorAll('textarea[data-md]').forEach(ta => {
        const legend = tpl.content.firstElementChild.cloneNode(true);
        ta.after(legend);

        // Fokus im Textfeld lassen, damit die Markierung erhalten bleibt
        legend.addEventListener('mousedown', e => { if (e.target.closest('[data-md-marker]')) e.preventDefault(); });
        legend.addEventListener('click', e => {
            const btn = e.target.closest('[data-md-marker]');
            if (btn) wrap(ta, btn.dataset.mdMarker);
        });

        ta.addEventListener('keydown', e => {
            if (!(e.ctrlKey || e.metaKey) || e.altKey || e.shiftKey || !/^[a-z0-9]$/i.test(e.key)) return;
            const btn = legend.querySelector(`[data-md-key="${e.key.toLowerCase()}"]`);
            if (!btn) return;
            e.preventDefault();
            wrap(ta, btn.dataset.mdMarker);
        });
    });
})();