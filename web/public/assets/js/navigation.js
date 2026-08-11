(function () {
    const toggle = document.querySelector('.menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.nav-overlay');
    const closers = document.querySelectorAll('[data-close-menu]');

    if (!toggle || !sidebar || !overlay) {
        return;
    }

    function setMenu(open) {
        document.body.classList.toggle('menu-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    toggle.addEventListener('click', () => {
        setMenu(!document.body.classList.contains('menu-open'));
    });

    closers.forEach((closer) => {
        closer.addEventListener('click', () => setMenu(false));
    });

    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setMenu(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setMenu(false);
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 760) {
            setMenu(false);
        }
    });
})();
