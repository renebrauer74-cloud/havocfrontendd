/* Website Creator: Live-Vorschau (ungespeicherte Änderungen per POST → iframe srcdoc), Theme-Vorschläge,
   Akzentfarbe, Sortieren der Abschnitte, Gerätegrößen, Hinweis auf ungespeicherte Änderungen. */
(() => {
    const form = document.getElementById('wcForm');
    if (!form) return;

    const frame = form.querySelector('[data-wc-frame]');
    const frameWrap = form.querySelector('[data-wc-frame-wrap]');
    const state = form.querySelector('[data-wc-state]');
    const dirty = form.querySelector('[data-wc-dirty]');
    const previewUrl = form.dataset.wcPreview;
    let isDirty = false;
    let isSubmitting = false;
    let timer = null;
    let controller = null;
    let firstRender = true;

    // Panel und Vorschau passen unter Überschrift & Hinweise ins Fenster – „Speichern“ bleibt sichtbar
    const fitToWindow = () => form.style.setProperty('--wc-offset', Math.max(16, Math.round(form.getBoundingClientRect().top + window.scrollY + 16)) + 'px');
    fitToWindow();
    window.addEventListener('resize', fitToWindow);

    const setState = (kind, html) => {
        state.className = 'wc-stage-state' + (kind ? ' is-' + kind : '');
        state.innerHTML = html;
    };

    // ---------- Live-Vorschau ----------
    const refresh = async () => {
        if (!previewUrl) return;
        controller?.abort();
        controller = new AbortController();
        setState('loading', '<i class="bi bi-arrow-repeat"></i> Aktualisiere …');

        const data = new FormData(form);
        data.delete('_method');   // Vorschau ist POST, Speichern PUT

        try {
            const response = await fetch(previewUrl, {
                method: 'POST',
                body: data,
                headers: { 'Accept': 'application/json, text/html', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });

            if (response.status === 422) {
                const json = await response.json();
                const first = Object.values(json.errors || {})[0];
                setState('error', '<i class="bi bi-exclamation-triangle"></i> ' + escapeHtml(first ? first[0] : 'Eingabe prüfen'));
                return;
            }
            if (!response.ok) throw new Error(response.status);

            const html = await response.text();
            const scrollY = frame.contentWindow?.scrollY || 0;
            frame.addEventListener('load', () => {
                const doc = frame.contentDocument;
                // Beim Aktualisieren nicht erneut einblenden lassen und an derselben Stelle bleiben
                if (!firstRender) doc?.querySelectorAll('[data-bw-reveal]').forEach(el => el.classList.add('is-visible'));
                frame.contentWindow?.scrollTo({ top: scrollY, behavior: 'instant' });
                firstRender = false;
            }, { once: true });
            frame.srcdoc = html;
            setState('', '<i class="bi bi-check-circle"></i> Live');
        } catch (error) {
            if (error.name !== 'AbortError') setState('error', '<i class="bi bi-wifi-off"></i> Vorschau nicht erreichbar');
        }
    };

    const escapeHtml = text => String(text).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

    const changed = (delay = 350) => {
        isDirty = true;
        dirty?.removeAttribute('hidden');
        clearTimeout(timer);
        timer = setTimeout(refresh, delay);
    };

    form.addEventListener('input', event => changed(event.target.matches('textarea, input[type=text], input[type=url], input[type=email]') ? 600 : 150));
    form.addEventListener('change', () => changed(150));
    form.addEventListener('submit', () => { isSubmitting = true; });
    window.addEventListener('beforeunload', event => {
        if (isDirty && !isSubmitting) event.preventDefault();
    });

    // ---------- Theme: Schrift, Kopfbereich und Farbe passend vorschlagen ----------
    const accentInput = form.querySelector('[data-wc-accent]');
    const colorButtons = [...form.querySelectorAll('[data-wc-color]')];
    const picker = form.querySelector('[data-wc-color-picker]');

    const markColor = () => colorButtons.forEach(button => button.classList.toggle('active', button.dataset.wcColor === accentInput.value.toLowerCase()));
    const setAccent = color => {
        accentInput.value = color;
        if (color) picker.value = color;
        markColor();
        changed(100);
    };

    form.querySelectorAll('[data-wc-theme]').forEach(radio => radio.addEventListener('change', () => {
        form.querySelector(`input[name="font"][value="${radio.dataset.font}"]`).checked = true;
        form.querySelector('input[name="body_font"][value=""]').checked = true;
        form.querySelector(`input[name="hero_style"][value="${radio.dataset.hero}"]`).checked = true;
        accentInput.value = '';
        picker.value = radio.dataset.accent;
        markColor();
    }));
    colorButtons.forEach(button => button.addEventListener('click', () => setAccent(button.dataset.wcColor)));
    picker?.addEventListener('input', event => {
        event.stopPropagation();
        setAccent(picker.value);
    });
    markColor();

    // ---------- Abschnitte sortieren (Ziehen oder Pfeile) ----------
    const list = form.querySelector('[data-wc-sections]');
    const renumber = () => {
        list.querySelectorAll('[data-wc-section]').forEach((item, index) => {
            item.querySelector('[data-wc-section-key]').name = `sections[${index}][key]`;
            item.querySelector('[data-wc-section-off]').name = `sections[${index}][enabled]`;
            item.querySelector('[data-wc-section-on]').name = `sections[${index}][enabled]`;
        });
        changed(100);
    };
    let dragged = null;

    // Nur am Griff ziehbar – sonst lassen sich Überschrift und Einleitung nicht markieren
    list?.addEventListener('pointerdown', event => {
        const handle = event.target.closest('.wc-section-handle');
        if (handle) handle.closest('[data-wc-section]').draggable = true;
    });
    list?.addEventListener('pointerup', () => list.querySelectorAll('[draggable="true"]').forEach(item => { item.draggable = false; }));

    list?.addEventListener('dragstart', event => {
        dragged = event.target.closest('[data-wc-section]');
        dragged?.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
    });
    list?.addEventListener('dragover', event => {
        event.preventDefault();
        const over = event.target.closest('[data-wc-section]');
        list.querySelectorAll('.is-over').forEach(item => item.classList.remove('is-over'));
        if (over && over !== dragged) over.classList.add('is-over');
    });
    list?.addEventListener('drop', event => {
        event.preventDefault();
        const over = event.target.closest('[data-wc-section]');
        if (over && dragged && over !== dragged) {
            const after = event.clientY > over.getBoundingClientRect().top + over.offsetHeight / 2;
            over.parentNode.insertBefore(dragged, after ? over.nextSibling : over);
            renumber();
        }
    });
    list?.addEventListener('dragend', () => {
        dragged?.classList.remove('is-dragging');
        if (dragged) dragged.draggable = false;
        list.querySelectorAll('.is-over').forEach(item => item.classList.remove('is-over'));
        dragged = null;
    });
    list?.addEventListener('click', event => {
        const button = event.target.closest('[data-wc-move]');
        if (!button) return;
        const item = button.closest('[data-wc-section]');
        const sibling = button.dataset.wcMove === '-1' ? item.previousElementSibling : item.nextElementSibling;
        if (!sibling) return;
        button.dataset.wcMove === '-1' ? list.insertBefore(item, sibling) : list.insertBefore(sibling, item);
        renumber();
    });

    // ---------- Adresse & Online-Schalter ----------
    const slug = form.querySelector('[data-wc-slug]');
    const urlLabel = form.querySelector('[data-wc-url]');
    const target = form.querySelector('[data-wc-target]');
    const savedSlug = slug?.defaultValue;
    slug?.addEventListener('input', () => {
        slug.value = slug.value.toLowerCase().replace(/[äöüß]/g, c => ({ 'ä': 'ae', 'ö': 'oe', 'ü': 'ue', 'ß': 'ss' })[c]).replace(/[^a-z0-9-]+/g, '-').replace(/-{2,}/g, '-');
        urlLabel.textContent = urlLabel.textContent.replace(/[^/]*$/, slug.value);
        // Weiterleitungsziel für die eigene Domain mitziehen
        target.textContent = target.textContent.replace(/[^/]*$/, slug.value);
        target.href = target.textContent;
        form.querySelector('[data-wc-domain-changed]').hidden = slug.value === savedSlug;
    });
    const published = form.querySelector('#is_published');
    published?.addEventListener('change', () => {
        form.querySelector('[data-wc-publish]').classList.toggle('is-online', published.checked);
        form.querySelector('[data-wc-domain-offline]').hidden = published.checked;
    });

    form.querySelector('[data-wc-copy]')?.addEventListener('click', async event => {
        const button = event.currentTarget;
        await navigator.clipboard?.writeText(target.textContent.trim());
        button.innerHTML = '<i class="bi bi-check2"></i> Kopiert';
        setTimeout(() => { button.innerHTML = '<i class="bi bi-clipboard"></i> Kopieren'; }, 2000);
    });

    // ---------- Gerätegröße ----------
    form.querySelectorAll('[data-wc-device]').forEach(button => button.addEventListener('click', () => {
        form.querySelectorAll('[data-wc-device]').forEach(other => other.classList.toggle('active', other === button));
        frameWrap.classList.toggle('is-tablet', button.dataset.wcDevice === 'tablet');
        frameWrap.classList.toggle('is-mobile', button.dataset.wcDevice === 'mobile');
    }));
})();
