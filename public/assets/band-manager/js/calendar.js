// Ampel-Kalender (FullCalendar) – lädt und speichert über die JSON-API des Band-Managers
document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.CALENDAR_CONFIG;
    const el = document.getElementById('calendar');
    if (!cfg || !el) return;

    const api = cfg.api;
    const pad = n => String(n).padStart(2, '0');
    const fmt = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const human = s => s.split('-').reverse().join('.');

    const modalEl = document.getElementById('statusModal');
    const modal = cfg.canEdit && modalEl ? new bootstrap.Modal(modalEl) : null;
    const form = document.getElementById('statusForm');
    const rangeLabel = document.getElementById('statusRange');

    let entries = {};   // 'YYYY-MM-DD' => Verfügbarkeits-Event
    let current = null; // { from, to } – beides inklusiv

    async function send(method, body) {
        const res = await fetch(api, {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
            body: JSON.stringify(body),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || data.error || 'Speichern fehlgeschlagen.');
        return data;
    }

    const calendar = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        locale: 'de',
        firstDay: 1,
        height: 'auto',
        selectable: !!modal,
        selectLongPressDelay: 300,
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,multiMonthYear' },
        buttonText: { today: 'Heute', month: 'Monat', year: 'Jahr' },

        events: async (info, success, failure) => {
            try {
                const res = await fetch(`${api}?start=${info.startStr.slice(0, 10)}&end=${info.endStr.slice(0, 10)}`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error('Laden fehlgeschlagen');
                const data = await res.json();
                entries = {};
                data.forEach(ev => { if (ev.extendedProps?.type === 'availability') entries[ev.start] = ev; });
                success(data);
            } catch (e) { failure(e); }
        },

        // Kalendereinträge: Uhrzeit + Titel direkt im Kästchen
        eventContent: info => {
            const p = info.event.extendedProps;
            if (p.type !== 'availability') return true;

            const box = document.createElement('div');
            box.className = 'hv-av-label';
            if (p.time) {
                const time = document.createElement('span');
                time.className = 'hv-av-time';
                time.textContent = p.time;
                box.append(time);
            }
            const text = document.createElement('span');
            text.className = 'hv-av-text';
            text.textContent = info.event.title;
            box.append(text);
            return { domNodes: [box] };
        },

        eventDidMount: info => {
            const p = info.event.extendedProps;
            const tip = p.type === 'availability'
                ? [p.statusLabel, p.time || 'ganztägig', p.title, p.note].filter(Boolean).join(' – ')
                : [p.gigStatus, p.note].filter(Boolean).join(' – ');
            if (tip) info.el.title = tip;
        },

        // Mehrere Tage: ziehen (mobil: gedrückt halten und ziehen)
        select: info => {
            const end = new Date(info.end);
            end.setDate(end.getDate() - 1); // FullCalendar-Ende ist exklusiv
            openModal(info.startStr.slice(0, 10), fmt(end));
        },

        // Ein Tag: einfaches Antippen reicht auch mobil (dort feuert „select“ erst nach langem Drücken)
        dateClick: info => {
            if (modal) openModal(info.dateStr.slice(0, 10), info.dateStr.slice(0, 10));
        },
    });
    calendar.render();

    if (!modal) return;

    const allDay = form.elements.all_day;
    const times = document.getElementById('statusTimes');
    const titleHint = document.getElementById('statusTitleHint');

    // „Ganztägig“ blendet die Uhrzeiten aus, „Sonstiges“ verlangt einen Titel
    function syncForm() {
        times.classList.toggle('d-none', allDay.checked);
        form.elements.start_time.required = !allDay.checked;

        const needsTitle = !!form.querySelector('input[name="status"]:checked')?.hasAttribute('data-requires-title');
        form.elements.title.required = needsTitle;
        titleHint.textContent = needsTitle ? '*' : '(optional)';
    }
    allDay.addEventListener('change', () => {
        syncForm();
        if (!allDay.checked) form.elements.start_time.focus();
    });
    form.querySelectorAll('input[name="status"]').forEach(radio => radio.addEventListener('change', syncForm));

    // Klick und Auswahl können beide feuern – das Fenster nur einmal öffnen
    let isOpen = false;
    modalEl.addEventListener('hidden.bs.modal', () => {
        isOpen = false;
        calendar.unselect();
    });

    function openModal(from, to) {
        if (isOpen) return;
        isOpen = true;
        current = { from, to };
        rangeLabel.textContent = from === to ? human(from) : `${human(from)} – ${human(to)}`;
        form.reset();

        const existing = from === to ? entries[from] : null;
        if (existing) {
            const p = existing.extendedProps;
            const radio = form.querySelector(`input[name="status"][value="${p.status}"]`);
            if (radio) radio.checked = true;
            form.elements.title.value = p.title || '';
            form.elements.note.value = p.note || '';
            allDay.checked = !p.startTime;
            form.elements.start_time.value = p.startTime || '';
            form.elements.end_time.value = p.endTime || '';
        }
        syncForm();
        modal.show();
    }

    function done() {
        modal.hide();
        calendar.unselect();
        calendar.refetchEvents();
    }

    form.addEventListener('submit', async ev => {
        ev.preventDefault();
        const fd = new FormData(form);
        try {
            const data = await send('POST', {
                ...current,
                status: fd.get('status'),
                title: fd.get('title'),
                all_day: allDay.checked,
                start_time: allDay.checked ? null : fd.get('start_time'),
                end_time: allDay.checked ? null : fd.get('end_time'),
                note: fd.get('note'),
            });
            done();
            if (data.warnings && data.warnings.length) alert('⚠️ ' + data.warnings.join('\n'));
        } catch (e) { alert(e.message); }
    });

    document.getElementById('btnClear').addEventListener('click', async () => {
        if (!confirm('Einträge im gewählten Zeitraum löschen?')) return;
        try { await send('DELETE', current); done(); }
        catch (e) { alert(e.message); }
    });
});
