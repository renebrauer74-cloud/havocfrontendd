// Kleine Progressive-Enhancements für die öffentlichen Seiten.

// Burger-Menü: mobile Navigation als Vollbild-Overlay
const navToggle = document.querySelector('[data-nav-toggle]');
if (navToggle) {
    const header = navToggle.closest('.site-header');
    const nav = document.getElementById(navToggle.getAttribute('aria-controls'));
    const mobileQuery = window.matchMedia('(max-width: 767px)');
    const isOpen = () => header.classList.contains('is-open');

    const setOpen = (open, { restoreFocus = false } = {}) => {
        header.classList.toggle('is-open', open);
        document.documentElement.classList.toggle('nav-open', open);
        navToggle.setAttribute('aria-expanded', String(open));
        navToggle.setAttribute('aria-label', open ? 'Menü schließen' : 'Menü öffnen');
        if (open) {
            nav.querySelector('a')?.focus();
        } else if (restoreFocus) {
            navToggle.focus();
        }
    };

    navToggle.addEventListener('click', () => {
        header.classList.add('is-animated');
        setOpen(!isOpen());
    });
    nav.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setOpen(false)));

    document.addEventListener('keydown', (event) => {
        if (!isOpen()) return;
        if (event.key === 'Escape') {
            setOpen(false, { restoreFocus: true });
            return;
        }
        // Fokus bleibt im geöffneten Menü (Logo, "X", Links)
        if (event.key === 'Tab') {
            const focusable = [...header.querySelectorAll('a, button')];
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });

    // Beim Wechsel auf Desktop-Breite das Overlay schließen
    mobileQuery.addEventListener('change', (event) => {
        header.classList.remove('is-animated');
        if (!event.matches) setOpen(false);
    });
}

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
