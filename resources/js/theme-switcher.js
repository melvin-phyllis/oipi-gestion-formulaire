const themeStorageKey = 'risfm-theme';

const currentTheme = () => document.documentElement.getAttribute('data-bs-theme') === 'dark'
    ? 'dark'
    : 'light';

const synchronizeThemeControls = (theme = currentTheme()) => {
    const nextThemeLabel = theme === 'dark'
        ? 'Activer le mode clair'
        : 'Activer le mode sombre';

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-label', nextThemeLabel);
        button.setAttribute('title', nextThemeLabel);
        button.setAttribute('aria-pressed', String(theme === 'dark'));

        const label = button.querySelector('[data-theme-label]');
        if (label) label.textContent = nextThemeLabel;
    });
};

const applyTheme = (theme) => {
    document.documentElement.setAttribute('data-bs-theme', theme);
    document.documentElement.style.colorScheme = theme;

    try {
        localStorage.setItem(themeStorageKey, theme);
    } catch (error) {
        // Le thème reste appliqué même si le stockage local est indisponible.
    }

    synchronizeThemeControls(theme);
    document.dispatchEvent(new CustomEvent('risfm:theme-changed', {
        detail: { theme },
    }));
};

const toggleTheme = (button, event) => {
    const nextTheme = currentTheme() === 'dark' ? 'light' : 'dark';
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!document.startViewTransition || reduceMotion) {
        applyTheme(nextTheme);

        return;
    }

    const bounds = button.getBoundingClientRect();
    const x = event.clientX > 0 ? event.clientX : bounds.left + (bounds.width / 2);
    const y = event.clientY > 0 ? event.clientY : bounds.top + (bounds.height / 2);
    const radius = Math.hypot(
        Math.max(x, window.innerWidth - x),
        Math.max(y, window.innerHeight - y),
    );

    document.documentElement.classList.add('risfm-theme-transition');

    const transition = document.startViewTransition(() => applyTheme(nextTheme));

    transition.ready.then(() => {
        document.documentElement.animate(
            {
                clipPath: [
                    `circle(0px at ${x}px ${y}px)`,
                    `circle(${radius}px at ${x}px ${y}px)`,
                ],
            },
            {
                duration: 550,
                easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
                pseudoElement: '::view-transition-new(root)',
            },
        );
    }).catch(() => {
        // Le thème est déjà appliqué : seule l'animation est facultative.
    });

    transition.finished.finally(() => {
        document.documentElement.classList.remove('risfm-theme-transition');
    });
};

const initializeThemeSwitcher = () => {
    synchronizeThemeControls();

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-theme-toggle]');
        if (!button) return;

        toggleTheme(button, event);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeThemeSwitcher, { once: true });
} else {
    initializeThemeSwitcher();
}
