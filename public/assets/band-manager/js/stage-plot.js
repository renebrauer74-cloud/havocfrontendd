// Rider Creator → Stage Plot Designer: Symbole auf die Bühne ziehen, verschieben, drehen, beschriften – beim Speichern als PNG zeichnen.
// Koordinaten in cm ab der linken hinteren Bühnenecke (Draufsicht, Publikum unten), x/y = Mitte des Elements.
(() => {
    const form = document.getElementById('spForm');
    const svg = document.getElementById('spStage');
    if (!form || !svg) return;

    const cfg = window.SP_CONFIG || {};
    const NS = 'http://www.w3.org/2000/svg';
    const INK = '#1d1d1b';
    const MARGIN = { side: 80, top: 80, bottom: 110 };
    const SNAP = 5;
    const EXPORT_WIDTH = 2400;

    const state = {
        width: 800,
        depth: 600,
        items: (cfg.items || []).map(item => ({ ...item })),
        selected: null,
        grid: true,
    };

    let dirty = false;
    let drag = null;
    let addCount = 0;

    const $ = id => document.getElementById(id);
    const props = $('spProps');
    const defs = svg.querySelector('defs');

    // ---------- Hilfsfunktionen ----------
    const el = (name, attrs = {}, parent = null) => {
        const node = document.createElementNS(NS, name);
        Object.entries(attrs).forEach(([key, value]) => node.setAttribute(key, value));
        if (parent) parent.appendChild(node);
        return node;
    };
    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
    const snap = value => Math.round(value / SNAP) * SNAP;
    const meters = cm => (Math.round(cm / 10) / 10).toLocaleString('de-DE');
    const element = type => (cfg.elements || {})[type] || { label: type, w: 60, h: 60 };
    const selectedItem = () => (state.selected !== null ? state.items[state.selected] : null);

    const markDirty = () => {
        dirty = true;
        $('spDirty')?.removeAttribute('hidden');
    };

    // Halbe Höhe der gedrehten Box – dort beginnt die Beschriftung
    const halfExtent = item => {
        const rad = (item.rotation || 0) * Math.PI / 180;
        return (Math.abs(item.w * Math.sin(rad)) + Math.abs(item.h * Math.cos(rad))) / 2;
    };

    const toStage = event => {
        const point = new DOMPoint(event.clientX, event.clientY).matrixTransform(svg.getScreenCTM().inverse());
        return { x: point.x, y: point.y };
    };

    const readStageSize = () => {
        const toCm = (input, fallback) => clamp(parseFloat(input?.value) || fallback, cfg.minStage || 2, cfg.maxStage || 30) * 100;
        state.width = toCm($('stage_width'), 8);
        state.depth = toCm($('stage_depth'), 6);
        state.items.forEach(item => {
            item.x = clamp(item.x, 0, state.width);
            item.y = clamp(item.y, 0, state.depth);
        });
    };

    // ---------- Zeichnen ----------
    const text = (parent, content, attrs) => {
        const node = el('text', { 'font-family': 'Arial, Helvetica, sans-serif', fill: INK, ...attrs }, parent);
        node.textContent = content;
        return node;
    };

    const drawItem = (layer, item, index) => {
        const group = el('g', { class: 'sp-item', 'data-index': index, transform: `translate(${item.x} ${item.y}) rotate(${item.rotation || 0})` }, layer);

        if (item.type === 'text') {
            text(group, item.label || 'Text', { 'font-size': item.h, 'font-weight': 'bold', 'text-anchor': 'middle', 'dominant-baseline': 'central' });
        } else if (item.type === 'riser') {
            el('rect', { x: -item.w / 2, y: -item.h / 2, width: item.w, height: item.h, fill: 'url(#sp-hatch)', stroke: INK, 'stroke-width': 3, 'stroke-dasharray': '10 5' }, group);
            el('rect', { x: -44, y: -15, width: 88, height: 30, fill: '#fff' }, group);
            text(group, 'RISER', { 'font-size': 20, 'font-weight': 'bold', 'text-anchor': 'middle', 'dominant-baseline': 'central' });
        } else {
            el('use', { href: `#sp-${item.type}`, x: -item.w / 2, y: -item.h / 2, width: item.w, height: item.h }, group);
            el('rect', { x: -item.w / 2, y: -item.h / 2, width: item.w, height: item.h, fill: 'transparent' }, group);
        }

        if (index === state.selected) {
            const box = item.type === 'text' ? null : { w: item.w, h: item.h };
            const pad = 6;
            if (box) {
                el('rect', { class: 'sp-noexport', x: -box.w / 2 - pad, y: -box.h / 2 - pad, width: box.w + pad * 2, height: box.h + pad * 2, fill: 'none', stroke: '#dc2f32', 'stroke-width': 2, 'stroke-dasharray': '6 4' }, group);
            }
        }

        if (item.type !== 'text' && item.label) {
            text(layer, item.label, {
                x: item.x, y: item.y + halfExtent(item) + 20,
                'font-size': 17, 'font-weight': 'bold', 'text-anchor': 'middle',
                stroke: '#fff', 'stroke-width': 5, 'paint-order': 'stroke', 'stroke-linejoin': 'round', class: 'sp-label',
            });
        }

        // Textfelder bekommen die Auswahl-Markierung erst, wenn ihre Größe bekannt ist
        if (index === state.selected && item.type === 'text') {
            const box = group.getBBox();
            el('rect', { class: 'sp-noexport', x: box.x - 6, y: box.y - 4, width: box.width + 12, height: box.height + 8, fill: 'none', stroke: '#dc2f32', 'stroke-width': 2, 'stroke-dasharray': '6 4' }, group);
        }
    };

    const render = () => {
        const { width: w, depth: d } = state;
        const vbWidth = w + MARGIN.side * 2;
        const vbHeight = d + MARGIN.top + MARGIN.bottom;
        svg.setAttribute('viewBox', `${-MARGIN.side} ${-MARGIN.top} ${vbWidth} ${vbHeight}`);

        [...svg.children].forEach(child => { if (child !== defs) child.remove(); });

        el('rect', { x: -MARGIN.side, y: -MARGIN.top, width: vbWidth, height: vbHeight, fill: '#fff' }, svg);

        // Kopf: Titel und Bühnengröße
        text(svg, `STAGE PLOT – ${cfg.band || ''}`.toUpperCase(), { x: 0, y: -32, 'font-size': 26, 'font-weight': 'bold' });
        text(svg, `${meters(w)} × ${meters(d)} m`, { x: w, y: -32, 'font-size': 18, 'text-anchor': 'end' });

        // Raster im Meterabstand (nur im Designer, nicht im Bild)
        if (state.grid) {
            const grid = el('g', { class: 'sp-noexport', stroke: '#e3e3e3', 'stroke-width': 1 }, svg);
            for (let x = 100; x < w; x += 100) el('line', { x1: x, y1: 0, x2: x, y2: d }, grid);
            for (let y = 100; y < d; y += 100) el('line', { x1: 0, y1: y, x2: w, y2: y }, grid);
        }

        // Bühne mit dicker Vorderkante
        el('rect', { x: 0, y: 0, width: w, height: d, fill: 'none', stroke: INK, 'stroke-width': 3 }, svg);
        el('line', { x1: 0, y1: d, x2: w, y2: d, stroke: INK, 'stroke-width': 8 }, svg);
        text(svg, 'UPSTAGE / BACKLINE', { x: w / 2, y: -10, 'font-size': 13, 'text-anchor': 'middle', fill: '#777' });
        text(svg, 'STAGE RIGHT', { x: 0, y: d + 34, 'font-size': 14, 'font-weight': 'bold' });
        text(svg, 'STAGE LEFT', { x: w, y: d + 34, 'font-size': 14, 'font-weight': 'bold', 'text-anchor': 'end' });
        text(svg, 'PUBLIKUM / AUDIENCE', { x: w / 2, y: d + 72, 'font-size': 24, 'font-weight': 'bold', 'text-anchor': 'middle', 'letter-spacing': 3 });

        const layer = el('g', { id: 'spLayer' }, svg);
        state.items.forEach((item, index) => drawItem(layer, item, index));

        $('spItems').value = JSON.stringify(state.items);
    };

    // ---------- Eigenschaften des markierten Elements ----------
    const syncProps = () => {
        if (!props) return;
        const item = selectedItem();
        props.hidden = !item;
        if (!item) return;

        const def = element(item.type);
        $('spPropTitle').textContent = def.label;
        $('spLabelCaption').textContent = item.type === 'text' ? 'Text' : 'Beschriftung';
        $('spLabel').value = item.label || '';
        $('spRotation').value = item.rotation || 0;
        $('spRotationOut').textContent = `${item.rotation || 0}°`;

        const sizeMode = item.type === 'riser' ? 'riser' : item.type === 'text' ? 'font' : 'scale';
        props.querySelectorAll('[data-sp-size]').forEach(box => { box.hidden = box.dataset.spSize !== sizeMode; });

        const scale = Math.round(item.w / def.w * 100);
        $('spScale').value = scale;
        $('spScaleOut').textContent = `${scale} %`;
        $('spFont').value = item.h;
        $('spFontOut').textContent = item.h;
        // Nur beim Riser füllen – sonst würden die versteckten Felder die Formular-Prüfung beim Speichern blockieren
        $('spRiserW').value = item.type === 'riser' ? item.w / 100 : '';
        $('spRiserD').value = item.type === 'riser' ? item.h / 100 : '';
    };

    const select = index => {
        state.selected = index;
        render();
        syncProps();
    };

    const change = mutate => {
        const item = selectedItem();
        if (!item) return;
        mutate(item);
        render();
        markDirty();
    };

    const add = (type, x, y) => {
        if (state.items.length >= (cfg.maxItems || 200)) {
            alert(`Es passen höchstens ${cfg.maxItems} Elemente auf einen Stage Plot.`);
            return;
        }
        const def = element(type);
        // Ohne Zielpunkt: versetzt um die Bühnenmitte, damit neue Elemente nicht übereinander liegen
        const offset = (addCount++ % 6) * 30;
        const item = {
            type,
            x: snap(clamp(x ?? state.width / 2 - 75 + offset, 0, state.width)),
            y: snap(clamp(y ?? state.depth / 2 - 75 + offset, 0, state.depth)),
            w: def.w,
            h: def.h,
            rotation: 0,
            label: type === 'text' ? 'Text' : '',
        };
        state.items.push(item);
        select(state.items.length - 1);
        markDirty();
        if (type === 'text') $('spLabel')?.select();
    };

    const removeSelected = () => {
        if (state.selected === null) return;
        state.items.splice(state.selected, 1);
        select(null);
        markDirty();
    };

    const duplicateSelected = () => {
        const item = selectedItem();
        if (!item) return;
        if (state.items.length >= (cfg.maxItems || 200)) return;
        state.items.push({ ...item, x: clamp(item.x + 40, 0, state.width), y: clamp(item.y + 40, 0, state.depth) });
        select(state.items.length - 1);
        markDirty();
    };

    const reorder = toFront => {
        const item = selectedItem();
        if (!item) return;
        state.items.splice(state.selected, 1);
        if (toFront) state.items.push(item); else state.items.unshift(item);
        select(toFront ? state.items.length - 1 : 0);
        markDirty();
    };

    const rotateSelected = degrees => change(item => {
        let rotation = Math.round(((item.rotation || 0) + degrees) % 360);
        if (rotation > 180) rotation -= 360;
        if (rotation <= -180) rotation += 360;
        item.rotation = rotation;
        syncProps();
    });

    // ---------- PNG zeichnen ----------
    const exportPng = () => new Promise((resolve, reject) => {
        const clone = svg.cloneNode(true);
        clone.querySelectorAll('.sp-noexport').forEach(node => node.remove());
        const [, , vbWidth, vbHeight] = svg.getAttribute('viewBox').split(' ').map(Number);
        const scale = EXPORT_WIDTH / vbWidth;
        clone.setAttribute('width', Math.round(vbWidth * scale));
        clone.setAttribute('height', Math.round(vbHeight * scale));
        clone.removeAttribute('tabindex');

        const url = URL.createObjectURL(new Blob([new XMLSerializer().serializeToString(clone)], { type: 'image/svg+xml;charset=utf-8' }));
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(vbWidth * scale);
            canvas.height = Math.round(vbHeight * scale);
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            URL.revokeObjectURL(url);
            resolve(canvas.toDataURL('image/png'));
        };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('Bild konnte nicht gezeichnet werden'));
        };
        img.src = url;
    });

    // ---------- Bedienung ----------
    readStageSize();
    state.grid = $('spGrid')?.checked ?? true;
    render();

    $('spGrid')?.addEventListener('change', e => {
        state.grid = e.target.checked;
        render();
    });

    form.querySelectorAll('[data-sp-stage]').forEach(input => input.addEventListener('change', () => {
        readStageSize();
        render();
        markDirty();
    }));

    form.addEventListener('input', e => { if (!e.target.closest('#spProps')) markDirty(); });

    // Schreibgeschützt: nur ansehen
    if (cfg.readonly) return;

    // Symbol-Leiste: Klick legt in die Mitte, Ziehen genau dorthin
    form.querySelectorAll('[data-sp-add]').forEach(button => {
        button.addEventListener('click', () => add(button.dataset.spAdd));
        button.addEventListener('dragstart', e => {
            e.dataTransfer.setData('text/plain', button.dataset.spAdd);
            e.dataTransfer.effectAllowed = 'copy';
        });
    });
    svg.addEventListener('dragover', e => e.preventDefault());
    svg.addEventListener('drop', e => {
        e.preventDefault();
        const type = e.dataTransfer.getData('text/plain');
        if (!cfg.elements?.[type]) return;
        const point = toStage(e);
        add(type, point.x, point.y);
    });

    // Verschieben mit Maus, Stift oder Finger
    svg.addEventListener('pointerdown', e => {
        const target = e.target.closest('.sp-item');
        if (!target) {
            select(null);
            return;
        }
        const index = Number(target.dataset.index);
        if (index !== state.selected) select(index);
        const item = state.items[index];
        const start = toStage(e);
        drag = { start, x: item.x, y: item.y, moved: false };
        svg.setPointerCapture(e.pointerId);
        svg.focus({ preventScroll: true });
        e.preventDefault();
    });
    svg.addEventListener('pointermove', e => {
        const item = selectedItem();
        if (!drag || !item) return;
        const point = toStage(e);
        const x = snap(clamp(drag.x + point.x - drag.start.x, 0, state.width));
        const y = snap(clamp(drag.y + point.y - drag.start.y, 0, state.depth));
        if (x === item.x && y === item.y) return;
        item.x = x;
        item.y = y;
        drag.moved = true;
        render();
    });
    const endDrag = () => {
        if (drag?.moved) markDirty();
        drag = null;
    };
    svg.addEventListener('pointerup', endDrag);
    svg.addEventListener('pointercancel', endDrag);
    // Durch das Pointer-Capture landet der Doppelklick auf der Bühne – die Auswahl stimmt aber schon
    svg.addEventListener('dblclick', () => {
        if (selectedItem()) $('spLabel')?.focus();
    });

    // Tastatur, solange kein Eingabefeld aktiv ist
    document.addEventListener('keydown', e => {
        if (e.target.closest('input, textarea, select') || selectedItem() === null) return;
        const step = e.shiftKey ? 25 : SNAP;
        const moves = { ArrowLeft: [-step, 0], ArrowRight: [step, 0], ArrowUp: [0, -step], ArrowDown: [0, step] };

        if (moves[e.key]) {
            const [dx, dy] = moves[e.key];
            change(item => {
                item.x = clamp(item.x + dx, 0, state.width);
                item.y = clamp(item.y + dy, 0, state.depth);
            });
        } else if (e.key === 'Delete' || e.key === 'Backspace') {
            removeSelected();
        } else if (e.key === 'Escape') {
            select(null);
        } else if (e.key.toLowerCase() === 'r' && !e.ctrlKey && !e.metaKey) {
            rotateSelected(e.shiftKey ? -15 : 15);
        } else if (e.key.toLowerCase() === 'd' && (e.ctrlKey || e.metaKey)) {
            duplicateSelected();
        } else {
            return;
        }
        e.preventDefault();
    });

    // Eigenschaften-Leiste
    $('spLabel')?.addEventListener('input', e => change(item => { item.label = e.target.value; }));
    $('spRotation')?.addEventListener('input', e => change(item => {
        item.rotation = Number(e.target.value);
        $('spRotationOut').textContent = `${item.rotation}°`;
    }));
    $('spScale')?.addEventListener('input', e => change(item => {
        const def = element(item.type);
        item.w = Math.round(def.w * e.target.value / 100);
        item.h = Math.round(def.h * e.target.value / 100);
        $('spScaleOut').textContent = `${e.target.value} %`;
    }));
    $('spFont')?.addEventListener('input', e => change(item => {
        item.h = Number(e.target.value);
        $('spFontOut').textContent = item.h;
    }));
    $('spRiserW')?.addEventListener('input', e => change(item => { item.w = Math.round(clamp(parseFloat(e.target.value) || 0.5, 0.5, cfg.maxStage) * 100); }));
    $('spRiserD')?.addEventListener('input', e => change(item => { item.h = Math.round(clamp(parseFloat(e.target.value) || 0.5, 0.5, cfg.maxStage) * 100); }));

    form.addEventListener('click', e => {
        const rotate = e.target.closest('[data-sp-rotate]');
        if (rotate) {
            rotateSelected(Number(rotate.dataset.spRotate));
            return;
        }

        const action = e.target.closest('[data-sp-action]')?.dataset.spAction;
        if (action === 'delete') removeSelected();
        if (action === 'duplicate') duplicateSelected();
        if (action === 'front' || action === 'back') reorder(action === 'front');

        if (e.target.closest('[data-sp-clear]') && state.items.length && confirm('Alle Elemente von der Bühne entfernen?')) {
            state.items = [];
            select(null);
            markDirty();
        }

        const template = e.target.closest('[data-sp-template]');
        if (template) {
            const items = cfg.templates?.[template.dataset.spTemplate] || [];
            if (state.items.length && !confirm('Die Vorlage ersetzt alle Elemente auf der Bühne. Weiter?')) return;
            // Vorlagen sind für 8 × 6 m gebaut und werden auf die eingestellte Bühne verteilt
            const fx = state.width / 800;
            const fy = state.depth / 600;
            state.items = items.map(item => ({ ...item, x: snap(item.x * fx), y: snap(item.y * fy) }));
            select(null);
            markDirty();
        }
    });

    // Speichern: zuerst das Bild zeichnen, dann absenden
    form.addEventListener('submit', async e => {
        if (form.dataset.ready === '1') return;
        e.preventDefault();
        // Nur Felder prüfen, die mitgeschickt werden – die Eigenschaften-Leiste hat keine Namen
        const invalid = [...form.elements].find(field => field.name && !field.checkValidity());
        if (invalid) {
            invalid.reportValidity();
            return;
        }

        const button = $('spSave');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Wird gespeichert …';

        select(null);
        try {
            $('spImage').value = await exportPng();
        } catch {
            $('spImage').value = '';
            alert('Das Bild für PDF und PNG konnte in diesem Browser nicht erstellt werden. Die Elemente werden trotzdem gespeichert.');
        }

        dirty = false;
        form.dataset.ready = '1';
        form.requestSubmit ? form.requestSubmit() : form.submit();
    });

    // PDF und PNG zeigen den gespeicherten Stand
    document.querySelectorAll('[data-sp-download]').forEach(link => link.addEventListener('click', e => {
        if (dirty && !confirm('Ihr habt ungespeicherte Änderungen – der Download zeigt den zuletzt gespeicherten Stand. Trotzdem herunterladen?')) {
            e.preventDefault();
        }
    }));

    window.addEventListener('beforeunload', e => {
        if (dirty) e.preventDefault();
    });
})();
