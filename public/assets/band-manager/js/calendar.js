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

        eventDidMount: info => {
            const p = info.event.extendedProps;
            const tip = [p.gigStatus, p.note].filter(Boolean).join(' – ');
            if (tip) info.el.title = tip;
        },

        select: info => {
            const end = new Date(info.end);
            end.setDate(end.getDate() - 1); // FullCalendar-Ende ist exklusiv
            openModal(info.startStr.slice(0, 10), fmt(end));
        },
    });
    calendar.render();

    if (!modal) return;

    function openModal(from, to) {
        current = { from, to };
        rangeLabel.textContent = from === to ? human(from) : `${human(from)} – ${human(to)}`;
        form.reset();

        const existing = from === to ? entries[from] : null;
        if (existing) {
            const radio = form.querySelector(`input[name="status"][value="${existing.extendedProps.status}"]`);
            if (radio) radio.checked = true;
            form.elements.note.value = existing.extendedProps.note || '';
        }
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
            const data = await send('POST', { ...current, status: fd.get('status'), note: fd.get('note') });
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
