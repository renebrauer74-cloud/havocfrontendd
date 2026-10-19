/* Band-Websites (Website Creator): Navigation, Erscheinen beim Scrollen, Player nach Zustimmung, Großansicht der Galerie */
(() => {
    const nav = document.querySelector('[data-bw-nav]');
    const toggle = document.querySelector('[data-bw-nav-toggle]');

    // Navigation: beim Scrollen hinterlegt, auf dem Handy ausklappbar
    const onScroll = () => nav?.classList.toggle('is-scrolled', window.scrollY > 40);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    toggle?.addEventListener('click', () => {
        const open = nav.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.style.overflow = open ? 'hidden' : '';
    });

    // Sprungmarken selbst scrollen – funktioniert so auch in der Vorschau (iframe srcdoc)
    document.querySelectorAll('a[href^="#"]').forEach(link => link.addEventListener('click', event => {
        const id = link.getAttribute('href').slice(1);
        const target = id === 'top' ? document.body : document.getElementById(id);
        if (!target) return;
        event.preventDefault();
        nav?.classList.remove('is-open');
        toggle?.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        id === 'top' ? window.scrollTo({ top: 0, behavior: 'smooth' }) : target.scrollIntoView({ behavior: 'smooth' });
    }));

    // Aktiver Menüpunkt und Erscheinen beim Scrollen
    if ('IntersectionObserver' in window) {
        const reveal = new IntersectionObserver(entries => entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                reveal.unobserve(entry.target);
            }
        }), { rootMargin: '0px 0px -8% 0px' });
        document.querySelectorAll('[data-bw-reveal]').forEach(el => reveal.observe(el));

        const links = [...document.querySelectorAll('.bw-nav-links a')];
        const spy = new IntersectionObserver(entries => entries.forEach(entry => {
            if (entry.isIntersecting) {
                links.forEach(link => link.classList.toggle('is-active', link.getAttribute('href') === '#' + entry.target.id));
            }
        }), { rootMargin: '-45% 0px -50% 0px' });
        document.querySelectorAll('.bw-section[id]').forEach(section => spy.observe(section));
    } else {
        document.querySelectorAll('[data-bw-reveal]').forEach(el => el.classList.add('is-visible'));
    }

    // Spotify / YouTube erst nach Klick laden (Datenschutz)
    document.querySelectorAll('[data-bw-embed]').forEach(box => box.querySelector('[data-bw-embed-load]')?.addEventListener('click', () => {
        const frame = document.createElement('iframe');
        frame.src = box.dataset.bwEmbed + (box.dataset.bwEmbed.includes('youtube') ? '?autoplay=1&rel=0' : '');
        frame.title = box.dataset.bwEmbedTitle;
        frame.loading = 'lazy';
        frame.allow = 'autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture';
        frame.allowFullscreen = true;
        box.replaceChildren(frame);
    }));

    // Großansicht der Galerie mit Pfeiltasten
    const lightbox = document.querySelector('[data-bw-lightbox]');
    const photos = [...document.querySelectorAll('[data-bw-lightbox-open]')];
    let current = 0;

    const show = index => {
        current = (index + photos.length) % photos.length;
        const photo = photos[current];
        lightbox.querySelector('img').src = photo.dataset.bwLightboxOpen;
        lightbox.querySelector('figcaption').textContent = photo.dataset.bwCaption || '';
        lightbox.hidden = false;
        document.body.style.overflow = 'hidden';
    };
    const close = () => {
        lightbox.hidden = true;
        document.body.style.overflow = '';
    };

    photos.forEach((photo, index) => photo.addEventListener('click', () => show(index)));
    lightbox?.querySelector('[data-bw-lightbox-close]').addEventListener('click', close);
    lightbox?.querySelector('[data-bw-lightbox-prev]').addEventListener('click', () => show(current - 1));
    lightbox?.querySelector('[data-bw-lightbox-next]').addEventListener('click', () => show(current + 1));
    lightbox?.addEventListener('click', event => event.target === lightbox && close());
    document.addEventListener('keydown', event => {
        if (!lightbox || lightbox.hidden) return;
        if (event.key === 'Escape') close();
        if (event.key === 'ArrowLeft') show(current - 1);
        if (event.key === 'ArrowRight') show(current + 1);
    });
})();
