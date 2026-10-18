/*
 * Cloud-Speicher: Dateien nacheinander hochladen (je Datei ein Request mit Fortschrittsbalken),
 * Drag & Drop und der gemeinsame Dialog „Umbenennen / verschieben“.
 */
(() => {
    const drop = document.getElementById('clDrop');
    const input = document.getElementById('clFiles');
    const queue = document.getElementById('clQueue');

    const sizeLabel = bytes => {
        const units = ['B', 'KB', 'MB', 'GB'];
        let i = 0;
        while (bytes >= 1024 && i < units.length - 1) { bytes /= 1024; i++; }
        return `${bytes.toFixed(i > 1 ? 1 : 0).replace('.', ',')} ${units[i]}`;
    };

    // ---- Upload ---------------------------------------------------------------------------------

    if (drop && input && queue) {
        const maxBytes = +drop.dataset.maxBytes;
        let free = +drop.dataset.free;
        let running = false;
        const pending = [];
        let uploaded = 0;

        const row = file => {
            const el = document.createElement('div');
            el.className = 'cl-queue-row';
            el.innerHTML = '<div class="d-flex justify-content-between gap-2 small"><span class="cl-queue-name"></span><span class="cl-queue-state text-muted"></span></div>'
                + '<div class="progress mt-1" style="height:4px"><div class="progress-bar" style="width:0%"></div></div>';
            el.querySelector('.cl-queue-name').textContent = `${file.name} (${sizeLabel(file.size)})`;
            queue.hidden = false;
            queue.append(el);

            return {
                progress: percent => { el.querySelector('.progress-bar').style.width = `${percent}%`; },
                state: (text, type = 'muted') => {
                    const state = el.querySelector('.cl-queue-state');
                    state.className = `cl-queue-state text-${type}`;
                    state.textContent = text;
                    if (type === 'danger') el.querySelector('.progress-bar').classList.add('bg-danger');
                    if (type === 'success') el.querySelector('.progress-bar').classList.add('bg-success');
                },
            };
        };

        const errorOf = xhr => {
            if (xhr.status === 413) return 'Zu groß für den Server.';
            if (xhr.status === 419) return 'Sitzung abgelaufen – bitte Seite neu laden.';
            try {
                const json = JSON.parse(xhr.responseText);
                return Object.values(json.errors ?? {})[0]?.[0] ?? json.message ?? 'Upload fehlgeschlagen.';
            } catch {
                return 'Upload fehlgeschlagen – bitte erneut versuchen.';
            }
        };

        const upload = ({ file, ui }) => new Promise(resolve => {
            const data = new FormData();
            data.append('file', file);
            if (drop.dataset.folder) data.append('folder_id', drop.dataset.folder);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', drop.dataset.url);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', drop.dataset.csrf);
            xhr.upload.addEventListener('progress', e => { if (e.lengthComputable) ui.progress(Math.round(e.loaded / e.total * 100)); });
            xhr.addEventListener('load', () => {
                if (xhr.status === 201) {
                    ui.progress(100);
                    ui.state('Fertig', 'success');
                    free -= file.size;
                    uploaded++;
                } else {
                    ui.state(errorOf(xhr), 'danger');
                }
                resolve();
            });
            xhr.addEventListener('error', () => { ui.state('Verbindung unterbrochen – bitte erneut versuchen.', 'danger'); resolve(); });
            ui.state('Lädt hoch …');
            xhr.send(data);
        });

        const run = async () => {
            if (running) return;
            running = true;
            while (pending.length) await upload(pending.shift());
            running = false;

            // Liste neu laden, damit die neuen Dateien erscheinen
            if (uploaded) setTimeout(() => location.reload(), 900);
        };

        const add = files => {
            [...files].forEach(file => {
                const ui = row(file);
                if (maxBytes > 0 && file.size > maxBytes) {
                    ui.state(`Zu groß (max. ${sizeLabel(maxBytes)})`, 'danger');
                } else if (file.size > free) {
                    ui.state(`Nicht genug Speicherplatz (noch ${sizeLabel(Math.max(0, free))} frei)`, 'danger');
                } else {
                    ui.state('Wartet …');
                    pending.push({ file, ui });
                }
            });
            run();
        };

        input.addEventListener('change', () => { add(input.files); input.value = ''; });

        ['dragenter', 'dragover'].forEach(type => drop.addEventListener(type, e => { e.preventDefault(); drop.classList.add('is-over'); }));
        ['dragleave', 'drop'].forEach(type => drop.addEventListener(type, e => { e.preventDefault(); drop.classList.remove('is-over'); }));
        drop.addEventListener('drop', e => add(e.dataTransfer.files));

        window.addEventListener('beforeunload', e => { if (running) e.preventDefault(); });
    }

    // ---- Umbenennen / verschieben ------------------------------------------------------------------

    const modalEl = document.getElementById('clEditModal');
    if (!modalEl) return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const form = document.getElementById('clEditForm');
    const name = document.getElementById('clEditName');
    const target = document.getElementById('clEditTarget');
    const hint = document.getElementById('clEditHint');

    document.querySelectorAll('[data-cl-edit]').forEach(button => button.addEventListener('click', () => {
        const isFolder = button.dataset.clEdit === 'folder';
        const id = button.dataset.id;

        form.action = button.dataset.action;
        name.value = button.dataset.name;
        target.name = isFolder ? 'parent_id' : 'folder_id';
        target.value = button.dataset.target ?? '';
        hint.hidden = isFolder;

        // Einen Ordner nicht in sich selbst oder seine Unterordner verschieben
        [...target.options].forEach(option => {
            const ancestors = (option.dataset.ancestors ?? '').split(',');
            option.disabled = isFolder && (option.value === id || ancestors.includes(id));
        });

        modal.show();
        modalEl.addEventListener('shown.bs.modal', () => name.select(), { once: true });
    }));
})();
