// Kleine Progressive-Enhancements für die öffentlichen Seiten.

// Sprach-Toggle DE / EN auf der Artist-Seite
document.querySelectorAll('[data-lang-switch]').forEach((box) => {
    box.querySelectorAll('[data-lang]').forEach((button) => {
        button.addEventListener('click', () => {
            const lang = button.dataset.lang;
            box.querySelectorAll('[data-lang]').forEach((b) => b.classList.toggle('is-active', b === button));
            box.querySelectorAll('[data-lang-content]').forEach((c) => { c.hidden = c.dataset.langContent !== lang; });
        });
    });
});

// Externe Links innerhalb eines anderen Links (z.B. Ticket-Button in der Show-Zeile)
document.querySelectorAll('[data-external-link]').forEach((el) => {
    const open = (event) => {
        event.preventDefault();
        event.stopPropagation();
        window.open(el.dataset.externalLink, '_blank', 'noopener');
    };
    el.addEventListener('click', open);
    el.addEventListener('keydown', (event) => { if (event.key === 'Enter') open(event); });
});
