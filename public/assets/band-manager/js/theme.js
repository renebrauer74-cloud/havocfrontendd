// Hell-/Dunkelmodus umschalten – sofort sichtbar, danach im Hintergrund speichern
(() => {
    const root = document.documentElement;
    const current = () => root.getAttribute('data-bs-theme') === 'light' ? 'light' : 'dark';

    function remember(theme) {
        // Cookie: damit auch die Login-Seite auf diesem Gerät das Design kennt
        document.cookie = `hv_theme=${theme}; path=/; max-age=31536000; SameSite=Lax`;
    }

    function apply(theme) {
        root.setAttribute('data-bs-theme', theme);
        document.querySelectorAll('[data-hv-theme-set]').forEach(btn => {
            const on = btn.dataset.hvThemeSet === theme;
            btn.classList.toggle('active', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        document.querySelectorAll('[data-hv-theme-toggle] i').forEach(icon => {
            icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
        });
    }

    function save(theme) {
        remember(theme);
        const cfg = window.HV_THEME || {};
        if (!cfg.saveUrl) return; // nicht eingeloggt → nur Cookie
        fetch(cfg.saveUrl, {
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': cfg.csrf, 'Accept': 'application/json' },
            body: new URLSearchParams({ theme }),
        }).catch(() => {});
    }

    function set(theme) {
        if (theme !== 'light' && theme !== 'dark' || theme === current()) return;
        apply(theme);
        save(theme);
    }

    document.addEventListener('click', e => {
        const setBtn = e.target.closest('[data-hv-theme-set]');
        if (setBtn) return set(setBtn.dataset.hvThemeSet);
        if (e.target.closest('[data-hv-theme-toggle]')) set(current() === 'dark' ? 'light' : 'dark');
    });

    // Gespeicherte Einstellung des Benutzers auch als Cookie merken (für die Login-Seite)
    remember(current());
})();