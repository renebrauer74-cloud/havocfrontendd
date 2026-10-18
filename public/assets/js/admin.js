// Admin-Dashboard: Drag & Drop für Bild-Uploads, Lösch-Bestätigung, Auto-Submit.

document.querySelectorAll('[data-drop-zone]').forEach((zone) => {
    const group = zone.parentElement;
    const input = group.querySelector('[data-drop-input]');
    const label = zone.querySelector('[data-drop-label]');
    const preview = group.querySelector('[data-drop-preview]');
    const remove = group.querySelector('[data-drop-remove]');
    const initialLabel = label.textContent;
    const initialSrc = preview.getAttribute('src');

    const showFile = () => {
        const file = input.files[0];
        if (!file) return;
        if (!file.type.startsWith('image/')) {
            alert('Bitte nur Bilder hochladen!');
            input.value = '';
            return;
        }
        label.textContent = file.name;
        preview.src = URL.createObjectURL(file);
        preview.hidden = false;
        if (remove) remove.checked = false;
    };

    // „Bild entfernen“: Vorschau ausblenden und eine bereits gewählte Datei verwerfen
    remove?.addEventListener('change', () => {
        if (remove.checked) {
            input.value = '';
            label.textContent = 'Bild wird beim Speichern entfernt';
        } else {
            label.textContent = initialLabel;
            preview.src = initialSrc;
        }
        preview.hidden = remove.checked;
    });

    zone.addEventListener('click', () => input.click());
    zone.addEventListener('dragover', (event) => { event.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', (event) => {
        event.preventDefault();
        zone.classList.remove('drag-over');
        if (event.dataTransfer.files.length) {
            input.files = event.dataTransfer.files;
            showFile();
        }
    });
    input.addEventListener('change', showFile);
});

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!confirm(form.dataset.confirm)) event.preventDefault();
    });
});

document.querySelectorAll('[data-autosubmit]').forEach((select) => {
    select.addEventListener('change', () => select.form.submit());
});
