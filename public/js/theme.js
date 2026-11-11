(() => {
    const storageKey = 'librenms-stats-theme';
    const validThemes = ['auto', 'light', 'dark'];
    let theme = 'auto';

    try {
        const saved = localStorage.getItem(storageKey);
        if (validThemes.includes(saved)) {
            theme = saved;
        }
    } catch {
        // Keep automatic mode when browser storage is unavailable.
    }

    // Apply before the stylesheet loads to avoid flashing the wrong theme.
    document.documentElement.dataset.theme = theme;

    document.addEventListener('DOMContentLoaded', () => {
        const control = document.querySelector('.theme-control');
        const options = [...control.querySelectorAll('input[name="theme"]')];
        function applyTheme(value) {
            theme = validThemes.includes(value) ? value : 'auto';
            document.documentElement.dataset.theme = theme;
            for (const option of options) {
                option.checked = option.value === theme;
            }
        }

        applyTheme(theme);
        control.hidden = false;
        control.addEventListener('change', event => {
            if (!options.includes(event.target)) return;
            applyTheme(event.target.value);
            try {
                if (theme === 'auto') {
                    localStorage.removeItem(storageKey);
                } else {
                    localStorage.setItem(storageKey, theme);
                }
            } catch {
                // The selected theme still applies for this page.
            }
        });
        window.addEventListener('storage', event => {
            if (event.key === storageKey || event.key === null) {
                applyTheme(event.newValue);
            }
        });
    });
})();
