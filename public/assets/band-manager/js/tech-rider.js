// Tech Rider: Zeilen hinzufügen / sortieren / entfernen, Vorlagen einfügen, Hinweis auf ungespeicherte Änderungen
(() => {
    const form = document.getElementById('rdForm');
    if (!form) return;

    const presets = window.RD_PRESETS || {};
    let counter = Date.now();
    let dirty = false;

    const listEl = name => form.querySelector(`[data-rd-list="${name}"]`);

    const markDirty = () => {
        dirty = true;
        document.getElementById('rdDirty')?.removeAttribute('hidden');
    };

    // Nummern (Kanal 1, 2, 3 …) und Hinweis „Noch keine Einträge“ aktualisieren
    const refresh = name => {
        const list = listEl(name);
        const rows = list.querySelectorAll(':scope > [data-rd-row]');
        rows.forEach((row, i) => {
            const num = row.querySelector('[data-rd-num]');
            if (num) num.textContent = i + 1;
        });
        form.querySelector(`[data-rd-empty="${name}"]`)?.toggleAttribute('hidden', rows.length > 0);
    };

    // Werte in eine Zeile schreiben – Felder heißen list[index][feld]
    const fill = (row, values) => {
        Object.entries(values || {}).forEach(([field, value]) => {
            row.querySelectorAll(`[name$="[${field}]"]`).forEach(input => {
                if (input.type === 'hidden') return;
                if (input.type === 'checkbox') input.checked = !!value;
                else input.value = value ?? '';
            });
        });
    };

    const addRow = (name, values, focus) => {
        const template = form.querySelector(`[data-rd-template="${name}"]`);
        const html = template.innerHTML.replaceAll('__i__', String(counter++));
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const row = wrapper.firstElementChild;
        fill(row, values);
        listEl(name).appendChild(row);
        if (focus) row.querySelector('input:not([type=hidden]), textarea, select')?.focus();
        return row;
    };

    const isEmptyRow = row => [...row.querySelectorAll('input[type=text], textarea')].every(input => input.value.trim() === '');

    form.addEventListener('click', e => {
        const add = e.target.closest('[data-rd-add]');
        if (add) {
            addRow(add.dataset.rdAdd, null, true);
            refresh(add.dataset.rdAdd);
            markDirty();
            return;
        }

        const preset = e.target.closest('[data-rd-preset]');
        if (preset) {
            const name = preset.dataset.rdPreset;
            const rows = [...listEl(name).querySelectorAll(':scope > [data-rd-row]')];
            const filled = rows.filter(row => !isEmptyRow(row));
            if (filled.length && !confirm('Die Vorlage wird unten an die vorhandenen Einträge angefügt. Weiter?')) return;
            rows.filter(isEmptyRow).forEach(row => row.remove());
            (presets[name] || []).forEach(values => addRow(name, values, false));
            refresh(name);
            markDirty();
            return;
        }

        const idea = e.target.closest('[data-rd-idea]');
        if (idea) {
            const row = addRow('custom_sections', { title: idea.dataset.rdIdea }, false);
            row.querySelector('textarea')?.focus();
            refresh('custom_sections');
            markDirty();
            return;
        }

        const row = e.target.closest('[data-rd-row]');
        if (!row) return;
        const name = row.parentElement.dataset.rdList;

        if (e.target.closest('[data-rd-remove]')) {
            if (!isEmptyRow(row) && !confirm('Zeile wirklich entfernen?')) return;
            row.remove();
        } else if (e.target.closest('[data-rd-up]') && row.previousElementSibling) {
            row.parentElement.insertBefore(row, row.previousElementSibling);
        } else if (e.target.closest('[data-rd-down]') && row.nextElementSibling) {
            row.parentElement.insertBefore(row.nextElementSibling, row);
        } else {
            return;
        }

        refresh(name);
        markDirty();
    });

    // Das PDF zeigt den gespeicherten Stand
    document.querySelectorAll('[data-rd-pdf]').forEach(link => link.addEventListener('click', e => {
        if (dirty && !confirm('Ihr habt ungespeicherte Änderungen – das PDF zeigt den zuletzt gespeicherten Stand. Trotzdem herunterladen?')) {
            e.preventDefault();
        }
    }));

    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    form.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', e => {
        if (dirty) e.preventDefault();
    });

    form.querySelectorAll('[data-rd-list]').forEach(list => refresh(list.dataset.rdList));

    // Vorschau des gewählten Bühnenplans
    document.getElementById('rdPlotInput')?.addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file) return;
        const img = new Image();
        img.src = URL.createObjectURL(file);
        document.getElementById('rdPlotPreview').replaceChildren(img);
    });
})();
